<?php

defined( 'ABSPATH' ) || exit;

/** Canonical condition/sharpening policy shared by ListLab, storefront, schema and feeds. */
final class KREV_PA_Condition_Resolver {
	const META_SHARPENED       = '_krev_sharpened';
	const META_CLASSIFICATION  = '_krev_condition_classification';
	const META_DERIVED         = '_krev_condition_classification_derived';
	const META_RESTORED        = '_krev_refurb_restoration_confirmed';
	const META_LIKE_NEW        = '_krev_refurb_like_new_confirmed';
	const META_WARRANTY        = '_krev_refurb_warranty_terms';
	const META_EVIDENCE_REVIEW = '_krev_refurb_evidence_needs_review';
	const META_REVIEW_REASON   = '_krev_condition_review_reason';
	const SHARP_TAG_SLUG       = 'sharpened';
	const CLASSIFICATION_TAXONOMY = 'krev_condition_class';

	private static $syncing = false;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_classification_taxonomy' ), 20 );
		add_action( 'set_object_terms', array( __CLASS__, 'reconcile_external_sharp_tag' ), 20, 6 );
		add_action( 'deleted_term_relationships', array( __CLASS__, 'reconcile_external_sharp_removal' ), 20, 3 );
		add_filter( 'woocommerce_structured_data_product_offer', array( __CLASS__, 'schema_offer_condition' ), 20, 2 );
		add_filter( 'woocommerce_gla_product_attribute_value_condition', array( __CLASS__, 'google_for_woocommerce_condition' ), 20, 2 );
		add_filter( 'woocommerce_gla_product_attribute_values', array( __CLASS__, 'google_for_woocommerce_overrides' ), 20, 2 );
		add_filter( 'woocommerce_product_query_tax_query', array( __CLASS__, 'include_derived_refurbished_in_filter' ), 20, 2 );
	}

	public static function register_classification_taxonomy() {
		register_taxonomy( self::CLASSIFICATION_TAXONOMY, 'product', array( 'labels' => array( 'name' => 'KnifeRevive classifications', 'singular_name' => 'KnifeRevive classification' ), 'public' => false, 'show_ui' => false, 'show_in_rest' => false, 'query_var' => true, 'rewrite' => false ) );
		if ( ! term_exists( 'refurbished', self::CLASSIFICATION_TAXONOMY ) ) { wp_insert_term( 'Refurbished', self::CLASSIFICATION_TAXONOMY, array( 'slug' => 'refurbished' ) ); }
	}

	public static function is_knife( $product_id ) {
		return in_array( 'knives', KREV_PA_Config::groups_for_product( (int) $product_id ), true );
	}

	public static function is_tech( $product_id ) {
		return in_array( 'tech', KREV_PA_Config::groups_for_product( (int) $product_id ), true );
	}

	/** Strictly normalize a submitted REST boolean. */
	public static function normalize_boolean( $value ) {
		if ( true === $value || false === $value ) {
			return $value;
		}
		if ( 1 === $value || '1' === $value || 'true' === $value ) {
			return true;
		}
		if ( 0 === $value || '0' === $value || 'false' === $value ) {
			return false;
		}
		return new WP_Error( 'krev_invalid_boolean', 'Use true or false.' );
	}

	public static function sharpened_state( $product_id ) {
		$product_id = (int) $product_id;
		$tagged     = has_term( self::SHARP_TAG_SLUG, 'product_tag', $product_id );
		if ( ! metadata_exists( 'post', $product_id, self::META_SHARPENED ) ) {
			return array( 'value' => $tagged, 'conflict' => false, 'source' => $tagged ? 'legacy-sharp-tag' : 'default' );
		}
		$stored = get_post_meta( $product_id, self::META_SHARPENED, true );
		if ( ! in_array( $stored, array( 'yes', 'no' ), true ) ) {
			return array( 'value' => $tagged, 'conflict' => true, 'source' => 'malformed-meta' );
		}
		$value = 'yes' === $stored;
		return array( 'value' => $value, 'conflict' => $value !== $tagged, 'source' => 'canonical-meta' );
	}

	public static function evidence( $product_id ) {
		return array(
			'restoration_confirmed' => 'yes' === get_post_meta( $product_id, self::META_RESTORED, true ),
			'like_new_confirmed'    => 'yes' === get_post_meta( $product_id, self::META_LIKE_NEW, true ),
			'warranty_terms'        => trim( (string) get_post_meta( $product_id, self::META_WARRANTY, true ) ),
			'needs_review'          => 'yes' === get_post_meta( $product_id, self::META_EVIDENCE_REVIEW, true ),
		);
	}

	public static function cosmetic_grade( WC_Product $product ) {
		return trim( (string) $product->get_attribute( 'pa_condition' ) );
	}

	/** Return normalized facts without mutating the product. */
	public static function resolve( WC_Product $product ) {
		$id       = $product->get_id();
		$is_knife = self::is_knife( $id );
		$is_tech  = self::is_tech( $id );
		$sharp    = self::sharpened_state( $id );
		$active   = $is_knife && $sharp['value'];
		$grade    = self::cosmetic_grade( $product );
		$evidence = self::evidence( $id );
		$qualified = $evidence['restoration_confirmed'] && $evidence['like_new_confirmed'] && '' !== $evidence['warranty_terms'] && ! $evidence['needs_review'];
		$merchant = null;
		$reason   = '';
		$review   = false;

		$normalized_grade = strtolower( preg_replace( '/\s+/', ' ', $grade ) );
		$is_explicit_new  = (bool) preg_match( '/^(new|new unopened|new in (?:the )?box|unused unopened)$/', $normalized_grade );

		if ( $sharp['conflict'] && $is_knife ) {
			$review = true;
			$reason = 'Sharpened metadata and the Sharp tag conflict.';
		} elseif ( $active ) {
			if ( '' === $normalized_grade ) {
				$review = true;
				$reason = 'Actual condition is missing.';
			} elseif ( $is_explicit_new ) {
				$merchant = 'new';
				$reason   = 'A sharpened knife with an explicit New condition remains new.';
			} else {
				$merchant = 'refurbished';
				$reason   = 'Sharpened knife with a non-New Item Condition resolves to refurbished.';
			}
		} else {
			$override = strtolower( trim( (string) $product->get_meta( '_krev_google_condition', true, 'edit' ) ) );
			if ( in_array( $override, array( 'new', 'used', 'refurbished' ), true ) ) {
				$merchant = 'refurbished' === $override && ! $qualified ? null : $override;
				$review   = null === $merchant;
				$reason   = $review ? 'Refurbished is selected but required evidence is incomplete.' : 'Resolved from the explicit Merchant condition.';
			} else {
				if ( preg_match( '/\b(open[ -]?box|like[ -]?new|used|pre[- ]owned|excellent|good|fair|parts|repair|gently|moderately|heavily)\b/', $normalized_grade ) ) {
					$merchant = $is_tech && $qualified ? 'refurbished' : 'used';
					$reason   = $is_tech && $qualified ? 'Used tech item has complete refurbishment evidence.' : 'Resolved from the cosmetic/base condition.';
				} elseif ( $is_explicit_new ) {
					$merchant = 'new';
					$reason   = 'Condition explicitly identifies a new item.';
				} elseif ( str_contains( $normalized_grade, 'refurb' ) && $qualified ) {
					$merchant = 'refurbished';
					$reason   = 'Refurbished condition has complete evidence.';
				} else {
					$review = true;
					$reason = '' === $grade ? 'Actual condition is missing.' : 'Condition facts are missing or contradictory.';
				}
			}
		}

		$classification = $active ? 'refurbished' : strtolower( trim( (string) $product->get_meta( self::META_CLASSIFICATION, true, 'edit' ) ) );
		if ( ! $is_knife && 'yes' === $product->get_meta( self::META_DERIVED, true, 'edit' ) ) {
			$classification = '';
		}
		$public_condition = $merchant ? ucfirst( $merchant ) : 'Review required';
		// Google requires the canonical value "used". Present the more helpful
		// customer-facing wording only for Technology listings.
		if ( $is_tech && 'used' === $merchant ) {
			$public_condition = 'Pre-owned';
		}
		if ( $active && 'used' === $merchant ) {
			$public_condition = 'Used — sharpened';
		}

		return array(
			'is_knife'            => $is_knife,
			'is_tech'             => $is_tech,
			'sharpened'           => $active,
			'sharpened_history'   => (bool) $sharp['value'],
			'sharpened_conflict'  => (bool) $sharp['conflict'],
			'classification'      => $classification,
			'classification_label'=> 'refurbished' === $classification ? 'Refurbished' : ( $classification ? ucfirst( $classification ) : '' ),
			'cosmetic_grade'      => $grade,
			'merchant_condition'  => $merchant,
			'schema_condition'    => $merchant ? 'https://schema.org/' . ucfirst( $merchant ) . 'Condition' : '',
			'public_condition'    => $public_condition,
			'evidence'            => $evidence,
			'review_required'     => $review,
			'reason'              => $reason,
		);
	}

	/** Persist an authoritative checkbox value, preserving all unrelated tags. */
	public static function set_sharpened( WC_Product $product, $value ) {
		$normalized = self::normalize_boolean( $value );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}
		if ( ! self::is_knife( $product->get_id() ) ) {
			return new WP_Error( 'krev_sharpened_non_knife', 'Sharpened can only be changed for knife listings.' );
		}
		self::$syncing = true;
		$product->update_meta_data( self::META_SHARPENED, $normalized ? 'yes' : 'no' );
		if ( $normalized ) {
			wp_set_object_terms( $product->get_id(), self::SHARP_TAG_SLUG, 'product_tag', true );
			$product->update_meta_data( self::META_CLASSIFICATION, 'refurbished' );
			$product->update_meta_data( self::META_DERIVED, 'yes' );
			wp_set_object_terms( $product->get_id(), 'refurbished', self::CLASSIFICATION_TAXONOMY, true );
		} else {
			wp_remove_object_terms( $product->get_id(), self::SHARP_TAG_SLUG, 'product_tag' );
			if ( 'yes' === $product->get_meta( self::META_DERIVED, true, 'edit' ) ) {
				$product->delete_meta_data( self::META_CLASSIFICATION );
				$product->delete_meta_data( self::META_DERIVED );
			}
			wp_remove_object_terms( $product->get_id(), 'refurbished', self::CLASSIFICATION_TAXONOMY );
		}
		self::$syncing = false;
		return true;
	}

	public static function save_evidence( WC_Product $product, array $data ) {
		$map = array( 'restoration_confirmed' => self::META_RESTORED, 'like_new_confirmed' => self::META_LIKE_NEW );
		foreach ( $map as $field => $meta ) {
			if ( ! array_key_exists( $field, $data ) ) { continue; }
			$value = self::normalize_boolean( $data[ $field ] );
			if ( is_wp_error( $value ) ) { return new WP_Error( 'krev_' . $field, ucfirst( str_replace( '_', ' ', $field ) ) . ' must be true or false.' ); }
			$product->update_meta_data( $meta, $value ? 'yes' : 'no' );
		}
		if ( array_key_exists( 'warranty_terms', $data ) ) {
			$product->update_meta_data( self::META_WARRANTY, sanitize_textarea_field( $data['warranty_terms'] ) );
		}
		$product->delete_meta_data( self::META_EVIDENCE_REVIEW );
		return true;
	}

	public static function persist_resolution( WC_Product $product ) {
		$resolved = self::resolve( $product );
		if ( $resolved['sharpened'] ) {
			$product->update_meta_data( self::META_CLASSIFICATION, 'refurbished' );
			$product->update_meta_data( self::META_DERIVED, 'yes' );
			wp_set_object_terms( $product->get_id(), 'refurbished', self::CLASSIFICATION_TAXONOMY, true );
		} elseif ( ! $resolved['is_knife'] && 'yes' === $product->get_meta( self::META_DERIVED, true, 'edit' ) ) {
			$product->delete_meta_data( self::META_CLASSIFICATION );
			$product->delete_meta_data( self::META_DERIVED );
			wp_remove_object_terms( $product->get_id(), 'refurbished', self::CLASSIFICATION_TAXONOMY );
		}
		if ( $resolved['review_required'] ) {
			$product->update_meta_data( self::META_REVIEW_REASON, $resolved['reason'] );
		} else {
			$product->delete_meta_data( self::META_REVIEW_REASON );
		}
		return $resolved;
	}

	public static function reconcile_external_sharp_tag( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		if ( self::$syncing || 'product_tag' !== $taxonomy || 'product' !== get_post_type( $object_id ) || ! self::is_knife( $object_id ) ) { return; }
		$product = wc_get_product( $object_id );
		if ( ! $product ) { return; }
		self::$syncing = true;
		$tagged = has_term( self::SHARP_TAG_SLUG, 'product_tag', $object_id );
		$product->update_meta_data( self::META_SHARPENED, $tagged ? 'yes' : 'no' );
		if ( $tagged ) {
			$product->update_meta_data( self::META_CLASSIFICATION, 'refurbished' );
			$product->update_meta_data( self::META_DERIVED, 'yes' );
			wp_set_object_terms( $object_id, 'refurbished', self::CLASSIFICATION_TAXONOMY, true );
		} elseif ( 'yes' === $product->get_meta( self::META_DERIVED, true, 'edit' ) ) {
			$product->delete_meta_data( self::META_CLASSIFICATION );
			$product->delete_meta_data( self::META_DERIVED );
			wp_remove_object_terms( $object_id, 'refurbished', self::CLASSIFICATION_TAXONOMY );
		}
		$product->save();
		self::$syncing = false;
		if ( class_exists( 'KREV_Sync_Queue' ) ) { KREV_Sync_Queue::enqueue( $object_id ); }
	}

	public static function reconcile_external_sharp_removal( $object_id, $tt_ids, $taxonomy ) {
		self::reconcile_external_sharp_tag( $object_id, array(), $tt_ids, $taxonomy, false, array() );
	}

	public static function schema_offer_condition( $offer, $product ) {
		if ( ! $product instanceof WC_Product ) { return $offer; }
		$resolved = self::resolve( $product );
		if ( $resolved['schema_condition'] ) { $offer['itemCondition'] = $resolved['schema_condition']; }
		else { unset( $offer['itemCondition'] ); }
		return $offer;
	}

	public static function google_for_woocommerce_condition( $condition, $product ) {
		if ( ! $product instanceof WC_Product ) { return ''; }
		$resolved = self::resolve( $product );
		return $resolved['merchant_condition'] ?: '';
	}

	public static function google_for_woocommerce_overrides( $overrides, $product ) {
		$overrides = is_array( $overrides ) ? $overrides : array();
		if ( ! $product instanceof WC_Product ) { return $overrides; }
		$resolved = self::resolve( $product );
		if ( $resolved['merchant_condition'] ) { $overrides['condition'] = $resolved['merchant_condition']; }
		else { unset( $overrides['condition'] ); }
		return $overrides;
	}

	/** Let the existing Condition=Refurbished archive filter include derived classifications without replacing cosmetic grade. */
	public static function include_derived_refurbished_in_filter( $tax_query, $query ) {
		if ( empty( $_GET['filter_condition'] ) || 'refurbished' !== sanitize_title( wp_unslash( $_GET['filter_condition'] ) ) ) { return $tax_query; } // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( $tax_query as $index => $clause ) {
			if ( ! is_array( $clause ) || 'pa_condition' !== ( $clause['taxonomy'] ?? '' ) ) { continue; }
			$tax_query[ $index ] = array( 'relation' => 'OR', $clause, array( 'taxonomy' => self::CLASSIFICATION_TAXONOMY, 'field' => 'slug', 'terms' => array( 'refurbished' ) ) );
		}
		return $tax_query;
	}
}
