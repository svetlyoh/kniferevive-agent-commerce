<?php
defined( 'ABSPATH' ) || exit;

final class KREV_ListLab_Attributes {
	private const CUSTOM_VALUE_PREFIX = '__krev_custom__:';

	public static function is_knife_category( $category_id ) {
		if ( ! class_exists( 'KREV_PA_Config' ) ) { return false; }
		$term = get_term( (int) $category_id, 'product_cat' ); $slugs = array();
		if ( ! $term || is_wp_error( $term ) ) { return false; }
		$slugs[] = $term->slug;
		foreach ( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) { $ancestor = get_term( $ancestor_id, 'product_cat' ); if ( $ancestor && ! is_wp_error( $ancestor ) ) { $slugs[] = $ancestor->slug; } }
		return in_array( 'knives', KREV_PA_Config::groups_for_category_slugs( $slugs ), true );
	}

	public static function validate( $category_id, array $submitted, $require_required_fields = false ) {
		foreach ( self::schema( $category_id ) as $field ) {
			$submitted_values = array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $submitted[ $field['taxonomy'] ] ?? array() ) ), static fn( $value ) => '' !== $value ) );
			if ( $require_required_fields && ! empty( $field['required'] ) && ! $submitted_values ) {
				return new WP_Error( 'listlab_required_attribute', $field['label'] . ' is required.', array( 'status' => 422, 'errors' => array( $field['taxonomy'] => 'Select an item condition.' ) ) );
			}
			if ( 'pa_msrp' !== $field['taxonomy'] ) { continue; }
			$raw = trim( sanitize_text_field( (string) reset( $submitted_values ) ) );
			if ( '' !== $raw && ( ! preg_match( '/^\d+(?:\.\d{1,2})?$/', $raw ) || (float) $raw < 0 ) ) {
				return new WP_Error( 'listlab_msrp', 'MSRP must be a non-negative number with no more than two decimal places.', array( 'status' => 422, 'errors' => array( 'pa_msrp' => 'Enter a valid MSRP.' ) ) );
			}
		}
		return true;
	}

	public static function validate_product_required_attributes( WC_Product $product ) {
		$categories  = $product->get_category_ids();
		$category_id = $categories ? (int) reset( $categories ) : 0;
		$submitted   = array();
		foreach ( self::schema( $category_id ) as $field ) {
			if ( empty( $field['required'] ) ) { continue; }
			$submitted[ $field['taxonomy'] ] = wp_get_object_terms( $product->get_id(), $field['taxonomy'], array( 'fields' => 'ids' ) );
		}
		return self::validate( $category_id, $submitted, true );
	}

	public static function schema( $category_id ) {
		$slugs = array(); $groups = array();
		if ( class_exists( 'KREV_PA_Config' ) ) {
			$term = get_term( $category_id, 'product_cat' );
			$category_slugs = array();
			if ( $term && ! is_wp_error( $term ) ) {
				$category_slugs[] = $term->slug;
				foreach ( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) { $ancestor = get_term( $ancestor_id, 'product_cat' ); if ( $ancestor && ! is_wp_error( $ancestor ) ) { $category_slugs[] = $ancestor->slug; } }
			}
			$groups = KREV_PA_Config::groups_for_category_slugs( $category_slugs );
			$slugs = KREV_PA_Config::allowed_slugs_for_groups( $groups );
			$definitions = KREV_PA_Config::all_definitions();
		} else { $definitions = array(); }
		$fields = array();
		if ( taxonomy_exists( 'product_brand' ) && array_intersect( $groups, array( 'knives', 'tech', 'world-spices', 'art' ) ) ) {
			$brand_terms = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false, 'number' => 0 ) );
			$fields[] = array( 'slug' => 'brand', 'taxonomy' => 'product_brand', 'label' => in_array( 'art', $groups, true ) ? 'Artist / Brand' : 'Brand', 'field_type' => 'select', 'allow_new_value' => true, 'options' => is_wp_error( $brand_terms ) ? array() : array_map( static function( $term ) { return array( 'id' => (int) $term->term_id, 'name' => $term->name ); }, $brand_terms ) );
		}
		foreach ( $slugs as $slug ) {
			if ( 'brand' === $slug || ( 'artist' === $slug && in_array( 'art', $groups, true ) ) ) { continue; }
			$taxonomy = wc_attribute_taxonomy_name( $slug );
			if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
			$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'number' => 0 ) );
			$label = $definitions[ $slug ]['name'] ?? ucwords( str_replace( '-', ' ', $slug ) );
			if ( 'condition' === $slug && ! in_array( 'knives', $groups, true ) ) { $label = 'Item Condition'; }
			$required = 'condition' === $slug && array_intersect( $groups, array( 'tech', 'world-spices', 'world-coins' ) );
			$is_tech_condition = 'condition' === $slug && in_array( 'tech', $groups, true );
			$options = is_wp_error( $terms ) ? array() : array_map( static function( $term ) use ( $is_tech_condition ) {
				$name = $term->name;
				if ( $is_tech_condition && 'used' === strtolower( trim( $name ) ) ) { $name = 'Pre-owned'; }
				return array( 'id' => (int) $term->term_id, 'name' => $name );
			}, $terms );
			$default_value = '';
			if ( $is_tech_condition && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( 'used' === strtolower( trim( $term->name ) ) ) { $default_value = (string) $term->term_id; break; }
				}
			}
			$fields[] = array( 'slug' => $slug, 'taxonomy' => $taxonomy, 'label' => $label, 'field_type' => 'msrp' === $slug ? 'decimal' : 'select', 'default_value' => $default_value, 'required' => (bool) $required, 'allow_new_value' => true, 'options' => $options );
		}
		return $fields;
	}

	public static function save( WC_Product $product, $category_id, array $submitted ) {
		$validation = self::validate( $category_id, $submitted );
		if ( is_wp_error( $validation ) ) { return $validation; }
		$schema = self::schema( $category_id ); $allowed = array_column( $schema, 'taxonomy' ); $attributes = $product->get_attributes();
		foreach ( $schema as $field ) {
			$taxonomy = $field['taxonomy']; $values = array_map( 'sanitize_text_field', (array) ( $submitted[ $taxonomy ] ?? array() ) ); $values = array_values( array_filter( $values, static fn( $value ) => '' !== $value ) ); $ids = array();
			if ( 'pa_msrp' === $taxonomy && $values ) {
				$raw = trim( (string) reset( $values ) );
				if ( ! preg_match( '/^\d+(?:\.\d{1,2})?$/', $raw ) || (float) $raw < 0 ) {
					return new WP_Error( 'listlab_msrp', 'MSRP must be a non-negative number with no more than two decimal places.', array( 'status' => 422, 'errors' => array( 'pa_msrp' => 'Enter a valid MSRP.' ) ) );
				}
				$values = array( wc_format_decimal( $raw, 2 ) );
			}
			foreach ( $values as $value ) {
				$is_custom = str_starts_with( (string) $value, self::CUSTOM_VALUE_PREFIX );
				$value     = trim( $is_custom ? substr( (string) $value, strlen( self::CUSTOM_VALUE_PREFIX ) ) : (string) $value );
				if ( '' === $value ) { continue; }
				$term = ! $is_custom && 'pa_msrp' !== $taxonomy && ctype_digit( $value )
					? get_term( (int) $value, $taxonomy )
					: get_term_by( 'name', $value, $taxonomy );
				if ( ( ! $term || is_wp_error( $term ) ) && $field['allow_new_value'] ) { $made = wp_insert_term( $value, $taxonomy ); $term = ! is_wp_error( $made ) ? get_term( $made['term_id'], $taxonomy ) : null; }
				if ( $term && ! is_wp_error( $term ) ) { $ids[] = (int) $term->term_id; }
			}
			wp_set_object_terms( $product->get_id(), $ids, $taxonomy, false );
			if ( 'product_brand' === $taxonomy ) { continue; }
			if ( ! $ids ) { unset( $attributes[ $taxonomy ] ); continue; }
			$attribute = new WC_Product_Attribute(); $attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) ); $attribute->set_name( $taxonomy ); $attribute->set_options( array_values( array_unique( $ids ) ) ); $attribute->set_position( count( $attributes ) ); $attribute->set_visible( true ); $attribute->set_variation( false ); $attributes[ $taxonomy ] = $attribute;
		}
		foreach ( $attributes as $name => $attribute ) { if ( $attribute->is_taxonomy() && ! in_array( $name, $allowed, true ) && isset( $submitted[ $name ] ) ) { unset( $attributes[ $name ] ); } }
		$product->set_attributes( $attributes );
		return true;
	}
}
