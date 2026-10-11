<?php
defined( 'ABSPATH' ) || exit;

/** Six predefined, evidence-only fields for each managed Google subcategory. */
final class KREV_PA_Specifications {
    public static function catalog() {
        static $catalog;
        if ( null === $catalog ) $catalog = json_decode( file_get_contents( KREV_PA_DIR . 'data/category-specifications-2026-10-11.json' ), true ) ?: array();
        return $catalog;
    }
    public static function definitions() { return self::catalog()['fields'] ?? array(); }
    public static function group_slugs( $group ) {
        $slugs = self::catalog()['defaults'][$group] ?? array();
        foreach ( self::catalog()['profiles'] ?? array() as $row ) if ( $row['group'] === $group ) $slugs = array_merge( $slugs, $row['fields'] );
        return array_values( array_unique( array_merge( $slugs, array( 'model-code', 'mpn' ) ) ) );
    }
    public static function profile( $category_id ) {
        $term = get_term( (int) $category_id, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) ) return null;
        $catalog = self::catalog();
        if ( isset( $catalog['profiles'][$term->slug] ) ) return $catalog['profiles'][$term->slug];
        $group = KREV_PA_Tech_Categories::group_for( $term->term_id );
        if ( ! isset( $catalog['defaults'][$group] ) ) return null;
        return array( 'group' => $group, 'google_id' => '', 'path' => '', 'fields' => $catalog['defaults'][$group] );
    }
    public static function listing_slugs( $category_id ) {
        $profile = self::profile( $category_id );
        return $profile ? array_merge( array( 'brand', 'condition' ), $profile['fields'], array( 'model-code', 'mpn' ) ) : null;
    }
    public static function schema( $category_id ) {
        $profile = self::profile( $category_id );
        if ( ! $profile ) return array();
        return array( 'specification_profile' => array(
            'google_id' => $profile['google_id'], 'google_path' => $profile['path'],
            'fields' => array_map( static fn( $slug ) => array( 'taxonomy' => wc_attribute_taxonomy_name( $slug ), 'label' => self::definitions()[$slug]['name'] ), $profile['fields'] ),
            'identifier_fields' => array( 'sku', 'pa_model-code', 'pa_mpn', 'global_unique_id' ),
            'unknown_values' => 'Leave unknown specifications and identifiers blank; do not invent values.',
        ) );
    }
    public static function merchant_details() {
        $definitions = self::definitions(); $mapping = array();
        foreach ( array( 'technology' => 'tech', 'home-garden' => 'home-garden', 'collectibles' => 'collectibles' ) as $group => $key ) {
            foreach ( self::group_slugs( $group ) as $slug ) $mapping[$key][$slug] = array( 'Product specifications', $definitions[$slug]['name'] );
        }
        return $mapping;
    }
}
