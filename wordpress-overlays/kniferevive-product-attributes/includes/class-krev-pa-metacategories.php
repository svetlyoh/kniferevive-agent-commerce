<?php
defined( 'ABSPATH' ) || exit;

/** Browsing collections are additive native categories, independent of Google feed categories. */
final class KREV_PA_Metacategories {
    const META = '_krev_metacategory';
    public static function labels() { return array( 'iphones' => 'iPhones', 'laptops' => 'Laptops', 'mini-computers' => 'Mini-Computers', 'bluetooth-gadgets' => 'Bluetooth Gadgets', 'ai-tech' => 'AI-Tech', 'pokemon-cards' => 'Pokémon Cards' ); }
    public static function parents() { return array( 'iphones' => 'technology', 'laptops' => 'technology', 'mini-computers' => 'technology', 'bluetooth-gadgets' => 'technology', 'ai-tech' => 'technology', 'pokemon-cards' => 'collectibles' ); }
    public static function init() { add_action( 'init', array( __CLASS__, 'setup' ), 29 ); }
    public static function setup() {
        if ( ! get_option( 'krev_metacategories_20261011_ready_v3', false ) ) {
            $ready = true;
            foreach ( self::labels() as $slug => $label ) {
                $root = KREV_PA_Tech_Categories::root_id( self::parents()[$slug] );
                if ( ! $root ) { $ready = false; continue; }
                $term = get_term_by( 'slug', $slug, 'product_cat' );
                if ( $term && (int) $term->parent !== $root ) return;
                if ( ! $term ) {
                    $created = wp_insert_term( $label, 'product_cat', array( 'slug' => $slug, 'parent' => $root ) );
                    if ( is_wp_error( $created ) ) return;
                    $term = get_term( $created['term_id'], 'product_cat' );
                }
                $result = update_term_meta( $term->term_id, self::META, $slug );
                if ( is_wp_error( $result ) ) return;
            }
            if ( $ready ) update_option( 'krev_metacategories_20261011_ready_v3', true, false );
        }
        // Owner-requested assignment is narrowly bound to this exact existing listing.
        if ( ! get_option( 'krev_metacategories_20261011_dell_done', false ) ) {
            $product = get_page_by_path( 'openclaw-plugin-ready-dell-optiplex-3020-i5-8gb-500gb-ubuntu-pc', OBJECT, 'product' );
            $term = get_term_by( 'slug', 'ai-tech', 'product_cat' );
            if ( $product && $term ) {
                $result = wp_set_object_terms( $product->ID, array( (int) $term->term_id ), 'product_cat', true );
                if ( ! is_wp_error( $result ) ) { wc_delete_product_transients( $product->ID ); update_option( 'krev_metacategories_20261011_dell_done', (int) $product->ID, false ); }
            }
        }
    }
    public static function is_meta( $id ) {
        $term = get_term( (int) $id, 'product_cat' );
        return $term && ! is_wp_error( $term ) && isset( self::labels()[$term->slug] ) && (int) $term->parent === KREV_PA_Tech_Categories::root_id( self::parents()[$term->slug] ) && $term->slug === get_term_meta( $id, self::META, true );
    }
    public static function selected( array $ids ) {
        $choices = array_values( array_filter( array_map( 'intval', $ids ), array( __CLASS__, 'is_meta' ) ) );
        foreach ( $choices as $id ) if ( 'ai-tech' === get_term( $id, 'product_cat' )->slug ) return $id;
        return $choices ? $choices[0] : 0;
    }
    public static function choices( $parent ) {
        $rows = array();
        foreach ( self::labels() as $slug => $label ) {
            if ( (int) $parent !== KREV_PA_Tech_Categories::root_id( self::parents()[$slug] ) ) continue;
            $term = get_term_by( 'slug', $slug, 'product_cat' );
            if ( $term && self::is_meta( $term->term_id ) ) $rows[] = array( 'id' => (int) $term->term_id, 'name' => $label, 'slug' => $slug );
        }
        return $rows;
    }
    public static function valid( $parent, $meta ) { return ! $meta || ( self::is_meta( $meta ) && (int) $parent === (int) get_term( (int) $meta, 'product_cat' )->parent ); }
    public static function catalog() {
        static $catalog;
        if ( null === $catalog ) $catalog = json_decode( file_get_contents( KREV_PA_DIR . 'data/metacategories-2026-10-11.json' ), true ) ?: array();
        return $catalog;
    }
    /** Pure rollup decision; never changes a product's categories or Google attributes. */
    public static function matches( $slug, array $google_ids, $text, $apple, array $explicit = array() ) {
        if ( in_array( $slug, $explicit, true ) ) return true;
        if ( 'ai-tech' === $slug ) return false;
        $matched = array_intersect( array_map( 'strval', $google_ids ), array_map( 'strval', self::catalog()[$slug]['google_ids'] ?? array() ) );
        // Title clues fill a broad/absent Google type; compatibility prose cannot override a specific type.
        $generic = ! $google_ids || ! array_diff( array_map( 'strval', $google_ids ), array( '222', '298' ) );
        if ( 'bluetooth-gadgets' === $slug ) {
            if ( in_array( '5130', array_map( 'strval', $google_ids ), true ) ) return true;
            $bluetooth = (bool) preg_match( '/\bblue[ -]?tooth\b/iu', $text );
            $negative = (bool) preg_match( '/\b(?:no|without|non)[ -]?bluetooth\b|\bbluetooth\s+(?:not\s+supported|unavailable)\b/iu', $text );
            return $bluetooth && ! $negative && ( (bool) $matched || $generic );
        }
        if ( 'pokemon-cards' === $slug ) return (bool) $matched && (bool) preg_match( '/\bpok(?:e|é)mon\b/iu', $text );
        if ( 'iphones' === $slug ) { $iphone = (bool) preg_match( '/\biphone\b/i', $text ); return ( (bool) $matched && ( $apple || $iphone ) ) || ( $generic && $iphone ); }
        if ( 'laptops' === $slug ) return (bool) $matched || ( $generic && (bool) preg_match( '/\b(laptop|notebook|macbook|chromebook)\b/i', $text ) );
        if ( 'mini-computers' === $slug ) return (bool) $matched || ( $generic && (bool) preg_match( '/\b(chromecast|mini[ -]?pc|mini[ -]?computer|optiplex|desktop|raspberry pi|thin client|nuc)\b/i', $text ) );
        return false;
    }
}
