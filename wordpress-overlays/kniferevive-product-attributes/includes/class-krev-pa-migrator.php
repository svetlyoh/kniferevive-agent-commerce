<?php

defined( 'ABSPATH' ) || exit;

final class KREV_PA_Migrator {
	private $apply;
	private $rows = array();
	private $products_changed = array();
	private $source_counts = array();

	public function __construct( $apply = false ) {
		$this->apply = (bool) $apply;
	}

	public function run() {
		$product_ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'draft' ),
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}
			$this->process_product( $product );
		}

		return array(
			'generated_at'     => gmdate( 'c' ),
			'mode'             => $this->apply ? 'apply' : 'dry-run',
			'products_scanned' => count( $product_ids ),
			'products_changed' => count( array_unique( $this->products_changed ) ),
			'rows'             => $this->rows,
			'source_counts'    => $this->source_counts,
			'manual_review'    => array_values(
				array_unique(
					array_map(
						static function ( $row ) {
							return $row['needs_manual_review'] ? $row['product_id'] : null;
						},
						$this->rows
					)
				)
			),
		);
	}

	private function process_product( WC_Product $product ) {
		$groups = KREV_PA_Config::groups_for_product( $product->get_id() );
		$category_slugs = KREV_PA_Config::category_slugs_for_product( $product->get_id() );
		if ( ! $groups ) {
			return;
		}

		$is_anomaly = $this->is_anomaly( $product, $groups );
		$attributes_to_process = array();
		foreach ( $groups as $group ) {
			$attributes_to_process = array_merge( $attributes_to_process, $this->migration_slugs( $group ) );
		}
		$attributes_to_process = array_values( array_unique( $attributes_to_process ) );

		$changed = false;
		foreach ( $attributes_to_process as $slug ) {
			$existing = KREV_PA_Editor::attribute_values( $product, $slug );
			$group = $this->group_for_attribute( $slug, $groups );
			if ( $existing ) {
				$this->record( $product, $category_slugs, $group, $slug, $existing, $existing, 'existing attribute', $is_anomaly );
				continue;
			}

			if ( $is_anomaly ) {
				$this->record( $product, $category_slugs, $group, $slug, array(), array(), 'manual review', true );
				continue;
			}

			$resolved = $this->resolve( $product, $group, $slug );
			$values = $resolved['values'];
			$source = $resolved['source'];
			if ( ! $values ) {
				$values = array( 'Not specified' );
				$source = 'fallback';
			}

			$this->record( $product, $category_slugs, $group, $slug, array(), $values, $source, false );
			if ( $this->apply && KREV_PA_Editor::set_taxonomy_attribute( $product, $slug, $values ) ) {
				$changed = true;
			}
		}

		if ( $changed ) {
			$product->save();
			$this->products_changed[] = $product->get_id();
		}
	}

	private function migration_slugs( $group ) {
		switch ( $group ) {
			case 'knives':
				return array( 'blade-length', 'blade-steel', 'edge-type', 'handle-material', 'country-of-origin' );
			case 'tech':
				return KREV_PA_Config::group_attributes()['tech'];
			case 'art':
				return KREV_PA_Config::group_attributes()['art'];
			case 'world-coins':
				return KREV_PA_Config::group_attributes()['world-coins'];
			default:
				return array();
		}
	}

	private function group_for_attribute( $slug, array $groups ) {
		foreach ( $groups as $group ) {
			if ( in_array( $slug, $this->migration_slugs( $group ), true ) ) {
				return $group;
			}
		}
		return reset( $groups );
	}

	private function is_anomaly( WC_Product $product, array $groups ) {
		$title = strtolower( html_entity_decode( wp_strip_all_tags( $product->get_name() ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		return in_array( 'knives', $groups, true ) && str_contains( $title, 'reverse withdrawal payment' );
	}

	private function resolve( WC_Product $product, $group, $slug ) {
		if ( 'tech' === $group && 'brand' === $slug ) {
			$brand = $this->structured_brand( $product );
			if ( $brand ) {
				return array( 'values' => array( $brand ), 'source' => 'structured metadata' );
			}
		}
		if ( 'art' === $group && 'condition' === $slug ) {
			$condition = $this->structured_condition( $product );
			if ( $condition ) {
				return array( 'values' => array( $condition ), 'source' => 'structured metadata' );
			}
		}

		foreach ( $this->text_sources( $product ) as $source => $text ) {
			$values = $this->extract( $group, $slug, $text, $product );
			if ( $values ) {
				return array( 'values' => $values, 'source' => $source );
			}
		}

		return array( 'values' => array(), 'source' => 'fallback' );
	}

	private function text_sources( WC_Product $product ) {
		return array(
			'title'       => $this->plain_text( $product->get_name() ),
			'description' => $this->plain_text( $product->get_short_description() . ' ' . $product->get_description() ),
		);
	}

	private function extract( $group, $slug, $text, WC_Product $product ) {
		if ( '' === $text ) {
			return array();
		}
		if ( 'knives' === $group ) {
			return $this->extract_knife( $slug, $text, $product );
		}
		if ( 'tech' === $group ) {
			return $this->extract_tech( $slug, $text );
		}
		if ( 'art' === $group ) {
			return $this->extract_art( $slug, $text );
		}
		if ( 'world-coins' === $group ) {
			return $this->extract_coin( $slug, $text );
		}
		return array();
	}

	private function extract_knife( $slug, $text, WC_Product $product ) {
		switch ( $slug ) {
			case 'blade-length':
				if ( preg_match( '/\b(\d{1,2}(?:\.\d+)?)\s*(?:-|\s)?(?:inch(?:es)?|in\b|["”])\s*(?:(?:chef|paring|bread|utility|santoku|steak|butcher|boning|fillet|carving|nakiri|tomato|oyster)\b|knife\b)/i', $text, $match ) || preg_match( '/\b(?:blade(?:\s+length)?|blade)\s*(?::|is|of)?\s*(\d{1,2}(?:\.\d+)?)\s*(inch(?:es)?|in\b|["”]|cm\b)/i', $text, $match ) ) {
					$unit = isset( $match[2] ) && 0 === stripos( $match[2], 'cm' ) ? 'cm' : 'in';
					return array( $this->format_number( $match[1] ) . ' ' . $unit );
				}
				break;
			case 'blade-steel':
				foreach ( array( 'X50CrMoV15', 'VG-MAX', 'VG10', 'high-carbon stainless steel', 'high carbon stainless steel', 'stainless steel', 'carbon steel', 'Damascus steel' ) as $steel ) {
					if ( false !== stripos( $text, $steel ) ) {
						return array( strcasecmp( $steel, 'high carbon stainless steel' ) === 0 ? 'High-carbon stainless steel' : $steel );
					}
				}
				break;
			case 'edge-type':
				if ( preg_match( '/\bpartially[- ]serrated\b/i', $text ) ) {
					return array( 'Partially Serrated' );
				}
				if ( preg_match( '/\b(?:granton|hollow[- ](?:ground|edge))\b/i', $text ) ) {
					return array( 'Granton' );
				}
				if ( preg_match( '/\bserrated\b/i', $text ) ) {
					return array( 'Serrated' );
				}
				if ( preg_match( '/\b(?:straight|plain) edge\b/i', $text ) ) {
					return array( 'Straight' );
				}
				$categories = KREV_PA_Config::category_slugs_for_product( $product->get_id() );
				if ( array_intersect( $categories, array( 'bread-knife', 'serated-utility-knife' ) ) ) {
					return array( 'Serrated' );
				}
				break;
			case 'handle-material':
				$patterns = array(
					'Polyoxymethylene (POM)' => '/\b(?:POM|polyoxymethylene)\b/i',
					'Polypropylene' => '/\bpolypropylene\b/i',
					'Synthetic' => '/\bsynthetic\s+(?:handle|grip)/i',
					'Wood' => '/\bwood(?:en)?\s+(?:handle|grip)/i',
					'Stainless steel' => '/\bstainless steel\s+(?:handle|grip)/i',
				);
				foreach ( $patterns as $value => $pattern ) {
					if ( preg_match( $pattern, $text ) ) {
						return array( $value );
					}
				}
				break;
			case 'country-of-origin':
				if ( preg_match( '/\b(?:made|manufactured|crafted|forged)\s+in\s+(Germany|Japan|United States|USA|France|Spain|China|Taiwan|Switzerland|Italy)\b/i', $text, $match ) ) {
					return array( 0 === strcasecmp( $match[1], 'USA' ) ? 'United States' : ucwords( strtolower( $match[1] ) ) );
				}
				if ( preg_match( '/\bSolingen,?\s+Germany\b/i', $text ) ) {
					return array( 'Germany' );
				}
				break;
		}
		return array();
	}

	private function extract_tech( $slug, $text ) {
		switch ( $slug ) {
			case 'brand':
				if ( preg_match( '/\b(Dell|HP|Lenovo|Apple|Acer|ASUS|Microsoft|Intel|AMD)\b/i', $text, $match ) ) {
					return array( strtoupper( $match[1] ) === 'HP' ? 'HP' : ucfirst( strtolower( $match[1] ) ) );
				}
				break;
			case 'processor':
				if ( preg_match( '/\b((?:Intel\s+)?(?:Core\s+)?i[3579](?:[- ]\d{3,5}[A-Z]{0,2})?|AMD\s+(?:Ryzen|Athlon)[^,;.]{0,25})\b/i', $text, $match ) ) {
					$value = trim( $match[1] );
					if ( preg_match( '/^i[3579]/i', $value ) ) {
						$value = 'Intel Core ' . $value;
					}
					return array( $value );
				}
				break;
			case 'memory':
				if ( preg_match( '/\b(\d{1,3})\s*GB\s*(?:DDR[2-5]?\s*)?(?:RAM|memory)?\b/i', $text, $match ) ) {
					return array( (int) $match[1] . ' GB' );
				}
				break;
			case 'storage':
				if ( preg_match( '/\b(\d+(?:\.\d+)?)\s*(TB|GB)\s*(SSD|HDD|NVMe|solid[- ]state drive|hard drive)?\b/i', $text, $match ) ) {
					$value = $this->format_number( $match[1] ) . ' ' . strtoupper( $match[2] );
					if ( ! empty( $match[3] ) ) {
						$type = preg_match( '/solid/i', $match[3] ) ? 'SSD' : ( preg_match( '/hard/i', $match[3] ) ? 'HDD' : strtoupper( $match[3] ) );
						$value .= ' ' . $type;
					}
					return array( $value );
				}
				break;
			case 'graphics':
				if ( preg_match( '/\b((?:NVIDIA|GeForce|AMD Radeon|Radeon|Intel (?:UHD|HD|Iris))[^,;.]{0,35})/i', $text, $match ) ) {
					return array( trim( $match[1] ) );
				}
				break;
			case 'operating-system':
				if ( preg_match( '/\b(Ubuntu(?:\s+\d{2}\.\d{2}(?:\.\d+)?\s*(?:LTS)?)?|Windows\s+(?:10|11)(?:\s+Pro|\s+Home)?|macOS(?:\s+[A-Za-z]+|\s+\d+(?:\.\d+)*)?|Linux)\b/i', $text, $match ) ) {
					return array( trim( $match[1] ) );
				}
				break;
			case 'form-factor':
				$types = array( 'Mini PC' => '/\bmini\s*PC\b/i', 'Small form factor (SFF)' => '/\b(?:small form factor|SFF)\b/i', 'Laptop' => '/\blaptop\b/i', 'Tower' => '/\b(?:mini|mid|full)?\s*tower\b/i', 'Desktop' => '/\bdesktop\b/i' );
				foreach ( $types as $value => $pattern ) {
					if ( preg_match( $pattern, $text ) ) {
						return array( $value );
					}
				}
				break;
		}
		return array();
	}

	private function extract_art( $slug, $text ) {
		switch ( $slug ) {
			case 'artist':
				if ( preg_match( '/\bJordan Ivanov\b/i', $text ) ) {
					return array( 'Jordan Ivanov' );
				}
				if ( preg_match( '/\b(?:artist|by)\s*[:\-]?\s*([A-Z][\p{L}\'’-]+(?:\s+[A-Z][\p{L}\'’-]+){1,3})\b/u', $text, $match ) ) {
					return array( trim( $match[1] ) );
				}
				break;
			case 'medium':
				$media = array(
					'Mixed media'      => '/\bmixed[- ]media\s+(?:painting|composition|artwork|work)\b/i',
					'Oil on canvas'    => '/\boil on canvas\b/i',
					'Acrylic on canvas'=> '/\bacrylic on canvas\b/i',
					'Watercolor'       => '/\bwatercolou?r\b/i',
					'Gouache'          => '/\bgouache\b/i',
					'Collage'          => '/\b(?:medium\s*:\s*|is an?\s+)collage\b/i',
					'Ink on paper'     => '/\bink on paper\b/i',
					'Sculpture'        => '/\b(?:medium\s*:\s*|is an?\s+)sculpture\b/i',
				);
				foreach ( $media as $medium => $pattern ) {
					if ( preg_match( $pattern, $text ) ) {
						return array( $medium );
					}
				}
				break;
			case 'art-style':
				$values = array();
				foreach ( array( 'Abstract', 'Modernist', 'Expressionist', 'Impressionist', 'Realism', 'Contemporary', 'Surrealist' ) as $style ) {
					if ( preg_match( '/\b' . preg_quote( $style, '/' ) . '\b/i', $text ) ) {
						$values[] = $style;
					}
				}
				return $values;
			case 'subject':
				$subjects = array( 'Human profile' => '/\bhuman profile\b/i', 'Human face' => '/\bhuman face\b/i', 'Portrait' => '/\bportrait\b/i', 'Landscape' => '/\blandscape\b/i', 'Still life' => '/\bstill life\b/i' );
				foreach ( $subjects as $value => $pattern ) {
					if ( preg_match( $pattern, $text ) ) {
						return array( $value );
					}
				}
				break;
			case 'dimensions':
				if ( preg_match( '/\b(?:artwork|painting|canvas|image|dimensions?|measures?)\s*(?:dimensions?)?\s*(?::|is|are)?\s*(\d+(?:\.\d+)?\s*(?:in|inch(?:es)?|["”])?\s*[x×]\s*\d+(?:\.\d+)?(?:\s*[x×]\s*\d+(?:\.\d+)?)?\s*(?:in|inch(?:es)?|["”]|cm)?)\b/i', $text, $match ) ) {
					return array( preg_replace( '/\s*[x×]\s*/u', ' × ', trim( $match[1] ) ) );
				}
				break;
			case 'year-period':
				if ( preg_match( '/\b(?:created|painted|dated|signed|circa|c\.)\s*(?:in\s*)?(19\d{2}|20\d{2})\b/i', $text, $match ) ) {
					return array( $match[1] );
				}
				break;
			case 'condition':
				if ( preg_match( '/\b(?:condition\s*[:\-]?\s*)?(like new|gently used|moderately used|well used|heavily used|for parts|new)\b/i', $text, $match ) ) {
					return array( ucwords( strtolower( $match[1] ) ) );
				}
				break;
		}
		return array();
	}

	private function extract_coin( $slug, $text ) {
		switch ( $slug ) {
			case 'country':
				if ( preg_match( '/\b(United States|Canada|France|British India|India|Mexico|United Kingdom|Great Britain|Germany|Italy|Spain|Australia|Japan|China)\b/i', $text, $match ) ) {
					return array( ucwords( strtolower( $match[1] ) ) );
				}
				break;
			case 'denomination':
				if ( preg_match( '/\b(\d+(?:\.\d+)?\s+(?:dollars?|cents?|francs?|pesos?|pence|penny|euros?|marks?|yen|rupees?|shillings?))\b/i', $text, $match ) ) {
					return array( ucwords( strtolower( $match[1] ) ) );
				}
				break;
			case 'coin-year':
				if ( preg_match( '/\b(1[6-9]\d{2}|20\d{2})\b/', $text, $match ) ) {
					return array( $match[1] );
				}
				break;
			case 'composition':
				if ( preg_match( '/\b(silver|gold|copper[- ]nickel|bronze|aluminum|aluminium|copper|nickel|zinc)\b/i', $text, $match ) ) {
					return array( ucwords( strtolower( str_replace( 'aluminium', 'aluminum', $match[1] ) ) ) );
				}
				break;
			case 'coin-grade':
				if ( preg_match( '/\b(MS[- ]?\d{2}|AU[- ]?\d{2}|XF[- ]?\d{2}|VF[- ]?\d{2}|AU|XF|VF|circulated|uncirculated|proof)\b/i', $text, $match ) ) {
					return array( strtoupper( $match[1] ) === $match[1] ? $match[1] : ucfirst( strtolower( $match[1] ) ) );
				}
				break;
		}
		return array();
	}

	private function structured_brand( WC_Product $product ) {
		foreach ( array( 'product_brand', 'pwb-brand' ) as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}
			$names = wp_get_post_terms( $product->get_id(), $taxonomy, array( 'fields' => 'names' ) );
			if ( ! is_wp_error( $names ) && $names ) {
				$name = trim( reset( $names ) );
				if ( ! in_array( strtolower( $name ), array( 'technology', 'technology & ai systems' ), true ) ) {
					return $name;
				}
			}
		}
		return '';
	}

	private function structured_condition( WC_Product $product ) {
		$valid = array( 'like new', 'gently used', 'moderately used', 'well used', 'heavily used / fair', 'for parts / repair', 'new' );
		$tags = wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) );
		if ( is_wp_error( $tags ) ) {
			return '';
		}
		foreach ( $tags as $tag ) {
			if ( in_array( strtolower( trim( $tag ) ), $valid, true ) ) {
				return trim( $tag );
			}
		}
		return '';
	}

	private function record( WC_Product $product, array $categories, $group, $slug, array $existing, array $proposed, $source, $manual_review ) {
		$definitions = KREV_PA_Config::all_definitions();
		$this->source_counts[ $source ] = 1 + ( $this->source_counts[ $source ] ?? 0 );
		$this->rows[] = array(
			'product_id'          => $product->get_id(),
			'product_title'       => $this->plain_text( $product->get_name() ),
			'publication_status'  => $product->get_status(),
			'categories'          => $categories,
			'logical_group'       => $group,
			'attribute'           => $definitions[ $slug ]['name'] ?? $slug,
			'attribute_taxonomy'  => wc_attribute_taxonomy_name( $slug ),
			'existing_value'      => $existing,
			'proposed_value'      => $proposed,
			'source'              => $source,
			'needs_manual_review' => (bool) $manual_review,
		);
	}

	private function plain_text( $value ) {
		return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	}

	private function format_number( $number ) {
		return rtrim( rtrim( number_format( (float) $number, 4, '.', '' ), '0' ), '.' );
	}
}
