<?php

defined( 'ABSPATH' ) || exit;

final class KREV_Product_Mapper {
	const OFFER_META            = '_krev_google_offer_id';
	const CONDITION_META        = '_krev_google_condition';
	const LISTING_SUBTITLE_META = '_kniferevive_listing_subtitle';

	public function map( WC_Product $product, $persist_offer_id = true ) {
		// Backfill the database field even when another eligibility check (such
		// as a missing description) prevents this knife from being submitted.
		if ( $this->is_knife_product( $product ) ) {
			self::populate_listing_subtitle( $product );
		}

		$eligibility = $this->validate_eligibility( $product );
		if ( is_wp_error( $eligibility ) ) {
			return $eligibility;
		}

		$offer = $this->resolve_identity( $product, $persist_offer_id );
		if ( is_wp_error( $offer ) ) {
			return $offer;
		}

		// Prefer ListLab's concise Short Description. Preserve older products by
		// falling back to their long description only when no short copy exists.
		$description = $this->plain_text( $product->get_short_description() );
		if ( '' === $description ) {
			$description = $this->plain_text( $product->get_description() );
		}

		$condition = $this->resolve_condition( $product );
		if ( is_wp_error( $condition ) ) {
			return $condition;
		}
		$attributes = array(
			'title'             => $this->merchant_title( $product ),
			'description'       => $description,
			'link'              => $this->production_url( get_permalink( $product->get_id() ) ),
			'imageLink'         => $this->production_url( wp_get_attachment_url( $product->get_image_id() ) ),
			'availability'      => self::map_availability( $product->get_stock_status(), $product->get_stock_quantity() ),
			'condition'         => $condition,
			'price'             => self::money( $product->get_sale_price() ? $product->get_regular_price() : $product->get_price(), get_woocommerce_currency() ),
		);

		$gallery = array();
		foreach ( $product->get_gallery_image_ids() as $attachment_id ) {
			$url = $this->production_url( wp_get_attachment_url( $attachment_id ) );
			if ( ! is_wp_error( $url ) ) {
				$gallery[] = $url;
			}
		}
		if ( $gallery ) {
			$attributes['additionalImageLinks'] = array_values( array_unique( $gallery ) );
		}

		if ( $product->get_sale_price() ) {
			$attributes['salePrice'] = self::money( $product->get_sale_price(), get_woocommerce_currency() );
		}

		$product_types = $this->product_types( $product );
		if ( $product_types ) {
			$attributes['productTypes'] = $product_types;
		}

		$google_category = KREV_Google_Category_Mapper::resolve_for_product( $product );
		if ( $google_category ) {
			$attributes['googleProductCategory'] = $google_category['id'];
		}

		$brand = $this->resolve_brand( $product );
		if ( '' !== $brand ) {
			$attributes['brand'] = $brand;
		}

		$gtin = $this->resolve_gtin( $product );
		if ( '' !== $gtin ) {
			$attributes['gtins'] = array( $gtin );
		}

		$mpn = $this->first_meta( $product->get_id(), array( '_wc_gla_mpn', '_google_mpn', '_mpn', 'mpn' ) );
		if ( '' !== $mpn ) {
			$attributes['mpn'] = $this->plain_text( $mpn );
		}

		if ( '' !== $gtin || ( '' !== $mpn && '' !== $brand ) ) {
			$attributes['identifierExists'] = true;
		}

		$weight = $this->shipping_weight( $product );
		if ( $weight ) {
			$attributes['shippingWeight'] = $weight;
		}

		// Rebuild the complete KnifeRevive-owned detail set from the product's
		// current category and global attributes on every mapping pass. This
		// prevents specifications from a previous category remaining in Google.
		$product_details = $this->product_details( $product );
		if ( $product_details ) {
			$attributes['productDetails'] = $product_details;
		}

		if ( is_wp_error( $attributes['link'] ) || is_wp_error( $attributes['imageLink'] ) ) {
			return new WP_Error( 'krev_unsafe_url', 'The product landing page or featured image is not a public production HTTPS URL.' );
		}

		return array(
			'offerId'          => $offer['offer_id'],
			'contentLanguage'  => $offer['content_language'],
			'feedLabel'        => $offer['feed_label'],
			'productAttributes'=> $attributes,
		);
	}

	public function validate_eligibility( WC_Product $product ) {
		if ( $this->is_excluded_from_merchant( $product ) ) {
			return new WP_Error( 'krev_excluded_category', 'Knife Sharpening services are excluded from Google Merchant synchronization.' );
		}
		if ( 'publish' !== $product->get_status() ) {
			return new WP_Error( 'krev_not_published', 'Only published WooCommerce products are eligible.' );
		}
		if ( ! $product->is_type( array( 'simple', 'variation' ) ) ) {
			return new WP_Error( 'krev_unsupported_product_type', 'This product type is not yet eligible for Merchant sync.' );
		}
		if ( ! is_numeric( $product->get_price() ) || (float) $product->get_price() <= 0 ) {
			return new WP_Error( 'krev_invalid_price', 'The product does not have a valid positive price.' );
		}
		if ( '' === trim( $this->plain_text( $product->get_short_description() . ' ' . $product->get_description() ) ) ) {
			return new WP_Error( 'krev_missing_description', 'The product does not have a Short Description or long description.' );
		}
		if ( ! $product->get_image_id() ) {
			return new WP_Error( 'krev_missing_image', 'The product does not have a featured image.' );
		}

		return true;
	}

	public function resolve_identity( WC_Product $product, $persist = true ) {
		$stored = trim( (string) $product->get_meta( self::OFFER_META, true, 'edit' ) );
		if ( '' !== $stored ) {
			return array( 'offer_id' => $stored, 'content_language' => KREV_Merchant_Config::DEFAULT_LANGUAGE, 'feed_label' => KREV_Merchant_Config::DEFAULT_FEED_LABEL, 'source' => 'krev_meta' );
		}

		$legacy_ids = $product->get_meta( '_wc_gla_google_ids', true, 'edit' );
		if ( is_array( $legacy_ids ) ) {
			foreach ( $legacy_ids as $feed_label => $legacy_id ) {
				if ( preg_match( '/^(?:online:)?([a-z]{2}):([A-Z0-9_-]+):(.+)$/', (string) $legacy_id, $matches ) ) {
					$identity = array( 'offer_id' => $matches[3], 'content_language' => $matches[1], 'feed_label' => $matches[2], 'source' => 'google_for_woocommerce' );
					if ( $persist ) {
						update_post_meta( $product->get_id(), self::OFFER_META, $identity['offer_id'] );
					}
					return $identity;
				}
			}
		}

		$identity = array(
			'offer_id'        => 'kr-wc-' . $product->get_id(),
			'content_language' => KREV_Merchant_Config::DEFAULT_LANGUAGE,
			'feed_label'       => KREV_Merchant_Config::DEFAULT_FEED_LABEL,
			'source'           => 'woocommerce_id',
		);
		if ( $persist ) {
			update_post_meta( $product->get_id(), self::OFFER_META, $identity['offer_id'] );
		}

		return $identity;
	}

	public static function money( $amount, $currency ) {
		return array(
			'amountMicros' => (string) (int) round( (float) $amount * 1000000 ),
			'currencyCode' => strtoupper( (string) $currency ),
		);
	}

	public static function map_availability( $stock_status, $stock_quantity = null ) {
		if ( 'outofstock' === $stock_status || ( null !== $stock_quantity && (float) $stock_quantity <= 0 ) ) {
			return 'OUT_OF_STOCK';
		}
		return 'IN_STOCK';
	}

	public static function is_valid_gtin( $gtin ) {
		$gtin = preg_replace( '/[\s-]+/', '', (string) $gtin );
		if ( ! preg_match( '/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/', $gtin ) ) {
			return false;
		}
		$sum = 0;
		$position = 0;
		for ( $i = strlen( $gtin ) - 2; $i >= 0; $i--, $position++ ) {
			$sum += (int) $gtin[ $i ] * ( 0 === $position % 2 ? 3 : 1 );
		}
		return ( 10 - ( $sum % 10 ) ) % 10 === (int) substr( $gtin, -1 );
	}

	public static function is_safe_public_https_url( $url, $require_kniferevive = false ) {
		$parts = wp_parse_url( (string) $url );
		if ( 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) ) || empty( $parts['host'] ) ) {
			return false;
		}
		$host = strtolower( (string) $parts['host'] );
		if ( 'localhost' === $host || '127.0.0.1' === $host || '::1' === $host || str_ends_with( $host, '.local' ) || str_ends_with( $host, '.test' ) || preg_match( '/^(?:10\.|192\.168\.|172\.(?:1[6-9]|2\d|3[01])\.)/', $host ) ) {
			return false;
		}

		return ! $require_kniferevive || in_array( $host, array( 'kniferevive.com', 'www.kniferevive.com' ), true );
	}

	public static function select_merchant_title( $product_name, $listing_subtitle, $is_knife_product ) {
		return (string) $product_name;
	}

	public static function is_knife_category_term( $term ) {
		if ( ! is_object( $term ) || 'knife-sharpening' === (string) ( $term->slug ?? '' ) ) {
			return false;
		}

		return 1 === preg_match( '/(?:knife|cleaver)$/i', trim( (string) ( $term->name ?? '' ) ) );
	}

	public static function is_excluded_category_term( $term ) {
		return is_object( $term ) && 'knife-sharpening' === (string) ( $term->slug ?? '' );
	}

	public function is_excluded_from_merchant( WC_Product $product ) {
		$terms = wp_get_post_terms( $product->get_id(), 'product_cat' );
		if ( is_wp_error( $terms ) ) {
			return false;
		}

		foreach ( $terms as $term ) {
			if ( self::is_excluded_category_term( $term ) ) {
				return true;
			}

			foreach ( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'product_cat' );
				if ( $ancestor && ! is_wp_error( $ancestor ) && self::is_excluded_category_term( $ancestor ) ) {
					return true;
				}
			}
		}

		return false;
	}

	public static function populate_listing_subtitle( WC_Product $product ) {
		$stored = trim( (string) $product->get_meta( self::LISTING_SUBTITLE_META, true, 'edit' ) );
		if ( '' !== $stored ) {
			return $stored;
		}

		if ( ! function_exists( 'kniferevive_generate_listing_subtitle' ) ) {
			return '';
		}

		$generated = sanitize_text_field( kniferevive_generate_listing_subtitle( $product ) );
		if ( '' !== $generated ) {
			$product->update_meta_data( self::LISTING_SUBTITLE_META, $generated );
			$product->save_meta_data();
		}

		return $generated;
	}

	private function merchant_title( WC_Product $product ) {
		$is_knife = $this->is_knife_product( $product );
		$subtitle = $product->get_meta( self::LISTING_SUBTITLE_META, true, 'edit' );
		if ( $is_knife && '' === trim( (string) $subtitle ) ) {
			$subtitle = self::populate_listing_subtitle( $product );
		}

		$title = self::select_merchant_title(
			$product->get_name(),
			$subtitle,
			$is_knife
		);

		return $this->plain_text( $title );
	}

	private function is_knife_product( WC_Product $product ) {
		if ( function_exists( 'kniferevive_is_knife_product' ) ) {
			return kniferevive_is_knife_product( $product->get_id() );
		}
		$terms = wp_get_post_terms( $product->get_id(), 'product_cat' );
		if ( is_wp_error( $terms ) ) {
			return false;
		}

		foreach ( $terms as $term ) {
			if ( self::is_knife_category_term( $term ) ) {
				return true;
			}

			foreach ( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'product_cat' );
				if ( $ancestor && ! is_wp_error( $ancestor ) && self::is_knife_category_term( $ancestor ) ) {
					return true;
				}
			}
		}

		return false;
	}

	private function resolve_condition( WC_Product $product ) {
		if ( class_exists( 'KREV_PA_Condition_Resolver' ) ) {
			$resolved = KREV_PA_Condition_Resolver::resolve( $product );
			if ( empty( $resolved['merchant_condition'] ) ) {
				return new WP_Error( 'krev_condition_unresolved', 'Merchant condition is unresolved: ' . $resolved['reason'] );
			}
			return strtoupper( $resolved['merchant_condition'] );
		}
		return new WP_Error( 'krev_condition_resolver_missing', 'KnifeRevive Product Attributes condition resolver is unavailable; no default condition was sent.' );
	}

	private function resolve_brand( WC_Product $product ) {
		if ( ! taxonomy_exists( 'product_brand' ) ) {
			return '';
		}

		$terms = wp_get_post_terms( $product->get_id(), 'product_brand' );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		// Storefront cards may show the assigned series, but Merchant brand must
		// resolve to the top-level manufacturer when a hierarchy exists.
		usort(
			$terms,
			static function ( $left, $right ) {
				return count( get_ancestors( $right->term_id, 'product_brand', 'taxonomy' ) )
					<=> count( get_ancestors( $left->term_id, 'product_brand', 'taxonomy' ) );
			}
		);
		$term      = reset( $terms );
		$ancestors = get_ancestors( $term->term_id, 'product_brand', 'taxonomy' );
		if ( $ancestors ) {
			$root = get_term( (int) end( $ancestors ), 'product_brand' );
			if ( $root && ! is_wp_error( $root ) ) {
				$term = $root;
			}
		}

		$brand = $this->plain_text( $term->name );
		return self::is_generic_brand( $brand ) ? '' : $brand;
	}

	public static function is_generic_brand( $brand ) {
		return in_array(
			strtolower( trim( (string) $brand ) ),
			array( 'technology', 'technology & ai systems', 'tech', 'knife revive', 'kniferevive' ),
			true
		);
	}

	public static function is_confirmed_product_detail_value( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return false;
		}
		return ! in_array( strtolower( $value ), array( 'not specified', 'unknown', 'n/a', 'na', 'none', 'not applicable' ), true );
	}

	private function product_details( WC_Product $product ) {
		if ( ! class_exists( 'KREV_PA_Config' ) ) {
			return array();
		}

		$groups = KREV_PA_Config::groups_for_product( $product->get_id() );
		$mapping = KREV_PA_Config::merchant_details();
		$details = array();
		foreach ( array( 'knives', 'tech', 'art', 'world-coins', 'world-spices' ) as $group ) {
			if ( ! in_array( $group, $groups, true ) || empty( $mapping[ $group ] ) ) {
				continue;
			}
			foreach ( $mapping[ $group ] as $slug => $names ) {
				$values = $this->global_attribute_values( $product, $slug );
				natcasesort( $values );
				foreach ( $values as $value ) {
					$value = $this->plain_text( $value );
					if ( ! self::is_confirmed_product_detail_value( $value ) ) {
						continue;
					}
					$details[] = array(
						'sectionName'    => $names[0],
						'attributeName'  => $names[1],
						'attributeValue' => $value,
					);
				}
			}
		}

		return $details;
	}

	private function global_attribute_values( WC_Product $product, $slug ) {
		$taxonomy = wc_attribute_taxonomy_name( $slug );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}
		$names = wp_get_post_terms( $product->get_id(), $taxonomy, array( 'fields' => 'names' ) );
		if ( is_wp_error( $names ) ) {
			return array();
		}
		return array_values( array_unique( array_filter( array_map( 'trim', $names ) ) ) );
	}

	private function resolve_gtin( WC_Product $product ) {
		$value = trim( (string) $product->get_global_unique_id( 'edit' ) );
		if ( '' === $value ) {
			$value = $this->first_meta( $product->get_id(), array( '_wc_gla_gtin', '_global_unique_id', '_gtin', '_upc', '_ean', 'gtin', 'upc', 'ean' ) );
		}
		$value = preg_replace( '/[\s-]+/', '', $value );

		return self::is_valid_gtin( $value ) ? $value : '';
	}

	private function shipping_weight( WC_Product $product ) {
		$value = (float) $product->get_weight( 'edit' );
		if ( $value <= 0 ) {
			return null;
		}
		$units = array( 'lbs' => 'lb', 'lb' => 'lb', 'oz' => 'oz', 'kg' => 'kg', 'g' => 'g' );
		$unit  = (string) get_option( 'woocommerce_weight_unit', 'kg' );

		return isset( $units[ $unit ] ) ? array( 'value' => $value, 'unit' => $units[ $unit ] ) : null;
	}

	private function product_types( WC_Product $product ) {
		$paths = array();
		$terms = wp_get_post_terms( $product->get_id(), 'product_cat' );
		if ( is_wp_error( $terms ) ) {
			return $paths;
		}
		foreach ( $terms as $term ) {
			$names = array( $this->plain_text( $term->name ) );
			$ancestors = get_ancestors( $term->term_id, 'product_cat', 'taxonomy' );
			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'product_cat' );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					array_unshift( $names, $this->plain_text( $ancestor->name ) );
				}
			}
			$paths[] = implode( ' > ', array_unique( $names ) );
		}

		return array_values( array_unique( array_filter( $paths ) ) );
	}

	private function production_url( $url ) {
		$url = (string) $url;
		if ( '' === $url ) {
			return new WP_Error( 'krev_empty_url', 'Required product URL is empty.' );
		}
		$parts = wp_parse_url( $url );
		if ( empty( $parts['path'] ) ) {
			return new WP_Error( 'krev_invalid_url', 'Required product URL is invalid.' );
		}
		$production = KREV_Merchant_Config::production_origin() . $parts['path'];
		if ( ! empty( $parts['query'] ) ) {
			$production .= '?' . $parts['query'];
		}
		$check = wp_parse_url( $production );
		if ( ! self::is_safe_public_https_url( $production, true ) ) {
			return new WP_Error( 'krev_nonproduction_url', 'Only public kniferevive.com HTTPS URLs may be submitted.' );
		}

		return $production;
	}

	private function first_meta( $product_id, array $keys ) {
		foreach ( $keys as $key ) {
			$value = trim( (string) get_post_meta( $product_id, $key, true ) );
			if ( '' !== $value ) {
				return $value;
			}
		}
		return '';
	}

	private function plain_text( $text ) {
		$text = html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = $this->repair_mojibake( $text );
		return trim( preg_replace( '/\s+/u', ' ', $text ) );
	}

	private function repair_mojibake( $text ) {
		if ( ! function_exists( 'mb_convert_encoding' ) || ! preg_match( '/(?:Ã|Â|â|ðŸ)/u', $text ) ) {
			return $text;
		}
		$candidate = @mb_convert_encoding( $text, 'Windows-1252', 'UTF-8' );
		if ( ! is_string( $candidate ) || ! mb_check_encoding( $candidate, 'UTF-8' ) ) {
			return $text;
		}
		$before = preg_match_all( '/(?:Ã|Â|â|ðŸ)/u', $text );
		$after  = preg_match_all( '/(?:Ã|Â|â|ðŸ)/u', $candidate );

		return $after < $before ? $candidate : $text;
	}
}
