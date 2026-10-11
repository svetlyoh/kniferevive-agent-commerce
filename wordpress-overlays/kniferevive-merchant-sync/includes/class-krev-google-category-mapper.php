<?php

defined( 'ABSPATH' ) || exit;

/** Maps KnifeRevive product categories to Google's taxonomy without persisting it on products. */
final class KREV_Google_Category_Mapper {
	const OPTION = 'krev_merchant_google_category_mappings';
	const INITIALIZED_OPTION = 'krev_merchant_google_category_mappings_initialized';
	const DEFAULTS_VERSION_OPTION = 'krev_merchant_google_category_mapping_defaults_version';
	const DEFAULTS_VERSION = 1;

	public static function init() {
		$current = get_option( self::OPTION, false );
		$initialized = (bool) get_option( self::INITIALIZED_OPTION, false );
		// A prior unfinished screen could have saved only blank placeholder rows.
		// Seed those safely, but preserve a deliberately empty set saved in the
		// current category-grid interface.
		if ( false === $current || self::is_prior_suggested_defaults( $current ) || ( ! $initialized && self::contains_only_blank_rows( $current ) ) ) {
			$current = self::default_mappings();
			update_option( self::OPTION, $current, false );
			update_option( self::INITIALIZED_OPTION, true, false );
		}
		// Replace only the old bundled Tech-to-Desktop mapping; custom mappings remain.
		foreach ( (array) $current as $index => $row ) {
			$term = get_term( (int) ( $row['product_cat_term_id'] ?? 0 ), 'product_cat' );
			if ( $term && ! is_wp_error( $term ) && 'technology' === $term->slug && '325' === (string) ( $row['google_category_id'] ?? '' ) && 'Electronics > Computers > Desktop Computers' === ( $row['google_category_path'] ?? '' ) ) {
				$current[$index]['google_category_id'] = '222'; $current[$index]['google_category_path'] = 'Electronics';
				update_option( self::OPTION, $current, false );
			}
		}
		self::apply_missing_defaults( is_array( $current ) ? $current : array() );
	}

	public static function activate() {
		self::init();
	}

	public static function mappings() {
		$mappings = get_option( self::OPTION, array() );
		$initialized = (bool) get_option( self::INITIALIZED_OPTION, false );
		if ( self::is_prior_suggested_defaults( $mappings ) || ( ! $initialized && self::contains_only_blank_rows( $mappings ) ) ) {
			$mappings = self::default_mappings();
			update_option( self::OPTION, $mappings, false );
			update_option( self::INITIALIZED_OPTION, true, false );
		}
		$mappings = self::apply_missing_defaults( is_array( $mappings ) ? $mappings : array() );
		return array_values( $mappings );
	}

	public static function default_mappings() {
		$rows = array();
		$rows[] = self::default_all_knives_mapping();
		$rows[] = self::default_row( '', self::term_id_for_slug( 'world-spices' ), '1529', 'Food, Beverages & Tobacco > Food Items > Seasonings & Spices > Herbs & Spices', true );
		$rows[] = self::default_row( '', self::term_id_for_slug( 'world-coins' ), '543606', 'Arts & Entertainment > Hobbies & Creative Arts > Collectibles > Collectible Coins & Currency > Collectible Coins', true );
		$rows[] = self::default_row( '', self::term_id_for_slug( 'technology' ), '222', 'Electronics', true );
		$rows[] = self::default_row( '', self::term_id_for_slug( 'art' ), '500044', 'Home & Garden > Decor > Artwork > Posters, Prints, & Visual Artwork', true );
		return $rows;
	}

	public static function default_all_knives_mapping() {
		return self::default_row( 'knives', 0, '665', 'Home & Garden > Kitchen & Dining > Kitchen Tools & Utensils > Kitchen Knives', true );
	}

	public static function save( $submitted ) {
		$rows = is_array( $submitted ) ? $submitted : array();
		$clean = array();
		$seen = array();
		foreach ( $rows as $index => $row ) {
			$row = is_array( $row ) ? $row : array();
			$term_id = absint( $row['product_cat_term_id'] ?? 0 );
			$group = ! $term_id && 'knives' === sanitize_key( $row['special_group'] ?? '' ) ? 'knives' : '';
			$id = trim( sanitize_text_field( $row['google_category_id'] ?? '' ) );
			$path = trim( sanitize_text_field( $row['google_category_path'] ?? '' ) );
			$enabled = ! empty( $row['enabled'] );
			// The grid sends an unchecked, empty row for each WooCommerce category.
			// Those are intentionally not mappings and must not be saved as errors.
			if ( '' === $id && '' === $path ) {
				if ( $enabled ) return new WP_Error( 'krev_google_category_id', sprintf( 'Mapping row %d needs a Google Category ID and taxonomy path.', $index + 1 ) );
				continue;
			}
			if ( ! preg_match( '/^[1-9]\d*$/', $id ) ) return new WP_Error( 'krev_google_category_id', sprintf( 'Mapping row %d needs a positive numeric Google Category ID.', $index + 1 ) );
			if ( '' === $path ) return new WP_Error( 'krev_google_category_path', sprintf( 'Mapping row %d needs a Google taxonomy path.', $index + 1 ) );
			if ( ! $term_id && '' === $group ) return new WP_Error( 'krev_google_category_term', sprintf( 'Mapping row %d needs a KnifeRevive product category.', $index + 1 ) );
			if ( $term_id && ! term_exists( $term_id, 'product_cat' ) ) return new WP_Error( 'krev_google_category_term', sprintf( 'Mapping row %d uses a category that no longer exists.', $index + 1 ) );
			$key = $group ? 'group:' . $group : 'term:' . $term_id;
			if ( $enabled ) {
				if ( isset( $seen[ $key ] ) ) return new WP_Error( 'krev_google_category_duplicate', 'Use each enabled KnifeRevive category only once.' );
				$seen[ $key ] = true;
			}
			$clean[] = array(
				'enabled' => $enabled, 'product_cat_term_id' => $term_id, 'special_group' => $group,
				'google_category_id' => $id, 'google_category_path' => $path,
				'include_descendants' => ! empty( $row['include_descendants'] ), 'priority' => count( $clean ) + 1,
			);
		}
		return $clean;
	}

	public static function resolve_for_product( WC_Product $product ) {
		$terms = wp_get_post_terms( $product->get_id(), 'product_cat' );
		if ( is_wp_error( $terms ) ) return null;
		$candidates = array();
		foreach ( self::mappings() as $index => $mapping ) {
			if ( empty( $mapping['enabled'] ) || ! preg_match( '/^[1-9]\d*$/', (string) ( $mapping['google_category_id'] ?? '' ) ) ) continue;
			if ( 'knives' === ( $mapping['special_group'] ?? '' ) ) {
				if ( self::is_kitchen_knife_product( $product, $terms ) ) $candidates[] = self::candidate( $mapping, $index, 0, 0 );
				continue;
			}
			$mapped_term = get_term( absint( $mapping['product_cat_term_id'] ?? 0 ), 'product_cat' );
			if ( ! $mapped_term || is_wp_error( $mapped_term ) ) continue;
			foreach ( $terms as $term ) {
				if ( (int) $term->term_id === (int) $mapped_term->term_id ) { $candidates[] = self::candidate( $mapping, $index, 1, self::term_depth( $mapped_term ) ); continue; }
				if ( ! empty( $mapping['include_descendants'] ) && in_array( (int) $mapped_term->term_id, array_map( 'intval', get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) ), true ) ) $candidates[] = self::candidate( $mapping, $index, 0, self::term_depth( $mapped_term ) );
			}
		}
		if ( class_exists( 'KREV_PA_Tech_Categories' ) ) {
			foreach ( $terms as $term ) {
				$mapping = KREV_PA_Tech_Categories::mapping( $term->term_id );
				if ( $mapping ) $candidates[] = self::candidate( array( 'google_category_id' => $mapping['id'], 'google_category_path' => $mapping['path'], 'product_cat_term_id' => $term->term_id ), 100000, 1, self::term_depth( $term ) );
			}
		}
		$winner = self::choose_candidate( $candidates );
		if ( ! $winner ) return null;
		if ( isset( $candidates[1] ) && $winner['exact'] === $candidates[1]['exact'] && $winner['depth'] === $candidates[1]['depth'] ) KREV_Logger::info( 'Equal Google category mappings matched; first mapping row won.', array( 'product_id' => $product->get_id(), 'selected_mapping' => $winner['priority'] ) );
		KREV_Logger::info( 'Google product category mapping applied.', array( 'product_id' => $product->get_id(), 'google_category_id' => $winner['google_category_id'], 'mapping_priority' => $winner['priority'] ) );
		return array( 'id' => $winner['google_category_id'], 'path' => $winner['google_category_path'], 'priority' => $winner['priority'] );
	}

	/** Deterministic precedence: exact match, greater category depth, then first table row. */
	public static function choose_candidate( array $candidates ) {
		if ( ! $candidates ) return null;
		usort( $candidates, static function( $a, $b ) { return array( $b['exact'], $b['depth'], $a['priority'] ) <=> array( $a['exact'], $a['depth'], $b['priority'] ); } );
		return $candidates[0];
	}

	public static function affected_published_product_ids( array $old, array $new ) {
		$keys = array_unique( array_merge( self::mapping_keys( $old ), self::mapping_keys( $new ) ) );
		if ( ! $keys ) return array();
		$ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 500, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) );
		$result = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product || ( new KREV_Product_Mapper() )->is_excluded_from_merchant( $product ) ) continue;
			$terms = wp_get_post_terms( $id, 'product_cat' ); if ( is_wp_error( $terms ) ) continue;
			foreach ( $keys as $key ) {
				if ( 'group:knives' === $key && self::is_kitchen_knife_product( $product, $terms ) ) { $result[] = (int) $id; break; }
				if ( 0 === strpos( $key, 'term:' ) ) { $term_id = (int) substr( $key, 5 ); foreach ( $terms as $term ) if ( (int) $term->term_id === $term_id || in_array( $term_id, array_map( 'intval', get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) ), true ) ) { $result[] = (int) $id; break 2; } }
			}
		}
		return array_values( array_unique( $result ) );
	}

	private static function default_row( $group, $term_id, $id, $path, $children ) { return array( 'enabled' => true, 'product_cat_term_id' => (int) $term_id, 'special_group' => $group, 'google_category_id' => $id, 'google_category_path' => $path, 'include_descendants' => (bool) $children, 'priority' => 0 ); }
	private static function apply_missing_defaults( array $mappings ) {
		if ( self::DEFAULTS_VERSION <= (int) get_option( self::DEFAULTS_VERSION_OPTION, 0 ) ) return $mappings;
		$known = array();
		foreach ( $mappings as $mapping ) {
			$term_id = absint( $mapping['product_cat_term_id'] ?? 0 );
			$key = $term_id ? 'term:' . $term_id : ( 'knives' === ( $mapping['special_group'] ?? '' ) ? 'group:knives' : '' );
			if ( '' !== $key ) $known[ $key ] = true;
		}
		foreach ( self::default_mappings() as $mapping ) {
			$term_id = absint( $mapping['product_cat_term_id'] ?? 0 );
			$key = $term_id ? 'term:' . $term_id : ( 'knives' === ( $mapping['special_group'] ?? '' ) ? 'group:knives' : '' );
			if ( '' !== $key && ! isset( $known[ $key ] ) ) {
				$mapping['priority'] = count( $mappings ) + 1;
				$mappings[] = $mapping;
				$known[ $key ] = true;
			}
		}
		update_option( self::OPTION, array_values( $mappings ), false );
		update_option( self::DEFAULTS_VERSION_OPTION, self::DEFAULTS_VERSION, false );
		return $mappings;
	}
	private static function contains_only_blank_rows( $mappings ) { if ( ! is_array( $mappings ) || ! $mappings ) return true; foreach ( $mappings as $mapping ) if ( is_array( $mapping ) && '' !== trim( (string) ( $mapping['google_category_id'] ?? '' ) ) ) return false; return true; }
	private static function is_prior_suggested_defaults( $mappings ) { if ( ! is_array( $mappings ) || 5 !== count( $mappings ) ) return false; $ids = array_map( static fn( $mapping ) => (string) ( $mapping['google_category_id'] ?? '' ), $mappings ); return array( '665', '1529', '543606', '325', '500044' ) === $ids && 'knives' === (string) ( $mappings[0]['special_group'] ?? '' ); }
	private static function term_id_for_slug( $slug ) { $term = get_term_by( 'slug', $slug, 'product_cat' ); return $term && ! is_wp_error( $term ) ? (int) $term->term_id : 0; }
	private static function term_depth( $term ) { return count( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) ); }
	private static function candidate( $mapping, $index, $exact, $depth ) { return array_merge( $mapping, array( 'exact' => $exact, 'depth' => $depth, 'priority' => (int) ( $mapping['priority'] ?? $index + 1 ) ) ); }
	private static function mapping_keys( $mappings ) { $keys = array(); foreach ( $mappings as $mapping ) { if ( empty( $mapping['enabled'] ) ) continue; $keys[] = 'knives' === ( $mapping['special_group'] ?? '' ) ? 'group:knives' : ( ! empty( $mapping['product_cat_term_id'] ) ? 'term:' . (int) $mapping['product_cat_term_id'] : '' ); } return array_filter( $keys ); }
	private static function is_kitchen_knife_product( WC_Product $product, $terms ) { if ( ( new KREV_Product_Mapper() )->is_excluded_from_merchant( $product ) ) return false; if ( class_exists( 'KREV_PA_Config' ) && in_array( 'knives', KREV_PA_Config::groups_for_product( $product->get_id() ), true ) ) return true; foreach ( $terms as $term ) { if ( KREV_Product_Mapper::is_knife_category_term( $term ) ) return true; foreach ( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) { $ancestor = get_term( $ancestor_id, 'product_cat' ); if ( $ancestor && ! is_wp_error( $ancestor ) && KREV_Product_Mapper::is_knife_category_term( $ancestor ) ) return true; } } return false; }
}
