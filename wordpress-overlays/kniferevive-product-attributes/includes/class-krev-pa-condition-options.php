<?php
defined( 'ABSPATH' ) || exit;

/** Seller condition vocabulary. Browsing hierarchy never changes Google condition semantics. */
final class KREV_PA_Condition_Options {
    public static function profiles() {
        $wear = array( 'New', 'Used', 'Like New', 'Gently Used', 'Moderately Used', 'Well Used', 'Heavily Used / Fair' );
        return array(
            'physical' => array_merge( $wear, array( 'Open Box' ) ),
            'tech' => array_merge( $wear, array( 'Open Box', 'Refurbished', 'For Parts / Repair' ) ),
            'knives' => array_merge( $wear, array( 'Open Box', 'Refurbished', 'For Parts / Repair' ) ),
            'art' => $wear,
            'coins' => array( 'New', 'Used', 'Circulated', 'Uncirculated' ),
            'cards' => $wear,
            'consumables' => array( 'New' ),
            'service' => array(),
        );
    }
    private static function group_profile( $group ) {
        return array( 'tech' => 'tech', 'knives' => 'knives', 'art' => 'art', 'world-coins' => 'coins', 'world-spices' => 'consumables', 'knife-sharpening' => 'service' )[$group] ?? 'physical';
    }
    public static function resolve( $category_id, $meta_id = 0 ) {
        $term = get_term( (int) $category_id, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) ) return array( 'profile' => 'service', 'source' => 'none', 'names' => array(), 'default' => '' );
        $slugs = array( $term->slug );
        foreach ( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) { $ancestor = get_term( $ancestor_id, 'product_cat' ); if ( $ancestor && ! is_wp_error( $ancestor ) ) $slugs[] = $ancestor->slug; }
        $groups = KREV_PA_Config::groups_for_category_slugs( $slugs );
        $group = $groups ? reset( $groups ) : 'other-finds';
        $profile = null; $source = 'category';
        // Every managed Google child has a policy, with more specific product-type overrides.
        if ( KREV_PA_Tech_Categories::is_managed_child( $term->term_id ) ) {
            $mapping = KREV_PA_Tech_Categories::mapping( $term->term_id );
            $path = $mapping['path'] ?? '';
            $profile = self::group_profile( $group ); $source = 'subcategory';
            if ( preg_match( '/Collectibles > Coins & Currency(?: >|$)/i', $path ) ) $profile = 'coins';
            elseif ( preg_match( '/Collectibles > Collectible Trading Cards(?: >|$)/i', $path ) ) $profile = 'cards';
            elseif ( 'tech' === $group && preg_match( '/Mobile Phone Cases|Mobile Phone Charms|Screen Protectors|Headphone Cushions|Computer Covers|Stylus Pen Nibs|Mobile Phone Stands|SIM Card|Pre-Paid Cards/i', $path ) ) $profile = 'physical';
            elseif ( 'home-garden' === $group && preg_match( '/(?: > Plants(?: >|$)| > Seeds(?: >|$)|Fertilizers|Potting Soils|Plant Food|Pesticides|Herbicides|Cleaning Solutions|Laundry Detergent)/i', $path ) ) $profile = 'consumables';
            elseif ( 'home-garden' === $group && str_contains( $path, 'Appliances' ) ) $profile = 'tech';
        }
        if ( null === $profile && $meta_id && KREV_PA_Metacategories::is_meta( $meta_id ) && in_array( get_term( $meta_id, 'product_cat' )->slug, $slugs, true ) === false ) {
            $meta = get_term( $meta_id, 'product_cat' );
            $valid_group = KREV_PA_Config::groups_for_category_slugs( array( get_term( $meta->parent, 'product_cat' )->slug ) );
            if ( in_array( $group, $valid_group, true ) ) { $profile = self::meta_profile( $meta->slug ); $source = 'metacategory'; }
        }
        if ( null === $profile && KREV_PA_Metacategories::is_meta( $term->term_id ) ) { $profile = self::meta_profile( $term->slug ); $source = 'metacategory'; }
        if ( null === $profile ) $profile = self::group_profile( $group );
        return array( 'profile' => $profile, 'source' => $source, 'names' => self::profiles()[$profile], 'default' => 'tech' === $group ? 'Used' : '' );
    }
    private static function meta_profile( $slug ) { return array( 'cell-phone-accessories' => 'physical', 'pokemon-cards' => 'cards' )[$slug] ?? 'tech'; }
    public static function valid_value( $value, array $policy ) {
        $value = trim( (string) $value );
        if ( str_starts_with( $value, '__krev_custom__:' ) ) return false;
        if ( ctype_digit( $value ) ) { $term = get_term( (int) $value, 'pa_condition' ); $value = $term && ! is_wp_error( $term ) ? $term->name : ''; }
        if ( 'pre-owned' === strtolower( $value ) ) $value = 'Used';
        return in_array( strtolower( $value ), array_map( 'strtolower', $policy['names'] ), true );
    }
}
