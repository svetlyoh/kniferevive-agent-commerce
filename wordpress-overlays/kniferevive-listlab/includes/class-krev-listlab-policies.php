<?php
defined( 'ABSPATH' ) || exit;

final class KREV_ListLab_Policies {
	public static function shipping_classes() {
		$terms = get_terms( array( 'taxonomy' => 'product_shipping_class', 'hide_empty' => false ) );
		return is_wp_error( $terms ) ? array() : array_map( static function ( $term ) {
			return array( 'id' => (int) $term->term_id, 'name' => $term->name );
		}, $terms );
	}

	public static function return_policies( $category_id = 0 ) {
		if ( ! taxonomy_exists( 'kr_return_policy' ) ) { return array(); }
		$terms = get_terms( array( 'taxonomy' => 'kr_return_policy', 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) { return array(); }
		return array_values( array_filter( array_map( static function ( $term ) use ( $category_id ) {
			$allowed = array_filter( array_map( 'absint', (array) get_term_meta( $term->term_id, '_kr_allowed_categories', true ) ) );
			if ( $allowed && ! array_intersect( array_merge( array( (int) $category_id ), array_map( 'intval', get_ancestors( $category_id, 'product_cat', 'taxonomy' ) ) ), $allowed ) ) { return null; }
			return array( 'id' => (int) $term->term_id, 'name' => (string) ( get_term_meta( $term->term_id, '_kr_badge_label', true ) ?: $term->name ) );
		}, $terms ) ) );
	}

	public static function valid_return_policy( $term_id, $category_id ) {
		if ( ! $term_id ) { return true; }
		$term = get_term( $term_id, 'kr_return_policy' );
		if ( ! $term || is_wp_error( $term ) ) { return false; }
		$allowed = array_filter( array_map( 'absint', (array) get_term_meta( $term_id, '_kr_allowed_categories', true ) ) );
		return ! $allowed || (bool) array_intersect( array_merge( array( (int) $category_id ), array_map( 'intval', get_ancestors( $category_id, 'product_cat', 'taxonomy' ) ) ), $allowed );
	}
}
