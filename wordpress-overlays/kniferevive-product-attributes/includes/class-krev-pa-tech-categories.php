<?php
defined( 'ABSPATH' ) || exit;

/** Managed Google subcategories. The historical class name retains existing Tech integrations. */
final class KREV_PA_Tech_Categories {
    const PAGE = 'krev-tech-categories';
    const ID_META = '_krev_google_taxonomy_id';
    const PATH_META = '_krev_google_taxonomy_path';
    const PROGRESS = 'krev_tech_taxonomy_20261010';

    public static function groups() { return array( 'technology' => 'Tech', 'world-coins' => 'World Coins', 'art' => 'Art', 'home-garden' => 'Home & Garden', 'collectibles' => 'Collectibles' ); }
    public static function init() {
        add_action( 'admin_menu', static function() { add_submenu_page( 'woocommerce', 'Google Subcategories', 'Google Subcategories', 'manage_woocommerce', self::PAGE, array( __CLASS__, 'page' ) ); } );
        add_action( 'admin_post_krev_tech_categories', array( __CLASS__, 'handle' ) );
    }
    public static function catalog( $group = 'technology' ) {
        static $rows;
        if ( null === $rows ) $rows = json_decode( file_get_contents( KREV_PA_DIR . 'data/google-category-groups-2026-10-11.json' ), true );
        return is_array( $rows ) ? ( $rows[$group] ?? array() ) : array();
    }
    public static function root_id( $group = 'technology' ) {
        if ( ! isset( self::groups()[$group] ) ) return 0;
        $term = get_term_by( 'slug', $group, 'product_cat' );
        return $term && ! is_wp_error( $term ) ? (int) $term->term_id : 0;
    }
    public static function group_for( $id ) {
        $term = get_term( (int) $id, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) ) return '';
        $ancestors = array_merge( array( (int) $term->term_id ), array_map( 'intval', get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) ) );
        foreach ( self::groups() as $group => $label ) if ( self::root_id( $group ) && in_array( self::root_id( $group ), $ancestors, true ) ) return $group;
        return '';
    }
    public static function parent_id( $id ) { $group = self::group_for( $id ); return $group ? self::root_id( $group ) : 0; }
    public static function is_managed_child( $id ) { if ( class_exists( 'KREV_PA_Metacategories' ) && KREV_PA_Metacategories::is_meta( $id ) ) return false; $root = self::parent_id( $id ); return $root && (int) $id !== $root; }
    public static function is_child( $id ) { return 'technology' === self::group_for( $id ) && self::is_managed_child( $id ); }
    public static function mapping( $id ) {
        $group = self::group_for( $id );
        if ( ! $group ) return null;
        if ( ! self::is_managed_child( $id ) ) {
            if ( class_exists( 'KREV_PA_Metacategories' ) && KREV_PA_Metacategories::is_meta( $id ) ) return null;
            if ( 'home-garden' === $group ) return array( 'id' => '536', 'path' => 'Home & Garden' );
            if ( 'collectibles' === $group ) return array( 'id' => '216', 'path' => 'Arts & Entertainment > Hobbies & Creative Arts > Collectibles' );
            return null;
        }
        $google_id = (string) get_term_meta( $id, self::ID_META, true );
        foreach ( self::catalog( $group ) as $row ) if ( (string) $row['id'] === $google_id ) return array( 'id' => $google_id, 'path' => $row['path'] );
        return null;
    }
    public static function progress_key( $group ) { return 'technology' === $group ? self::PROGRESS : 'krev_google_taxonomy_20261011_' . $group; }
    /** Bounded, idempotent imports preserve native IDs and existing product assignments. */
    public static function import_batch( $limit = 50, $group = 'technology' ) {
        if ( ! isset( self::groups()[$group] ) ) return new WP_Error( 'krev_google_group', 'Choose a supported category group.' );
        $root = self::root_id( $group );
        if ( ! $root && in_array( $group, array( 'home-garden', 'collectibles' ), true ) ) {
            $created = wp_insert_term( self::groups()[$group], 'product_cat', array( 'slug' => $group ) );
            if ( is_wp_error( $created ) ) return $created;
            $root = (int) $created['term_id'];
        }
        if ( ! $root ) return new WP_Error( 'krev_tech_root', 'The existing parent category is missing.' );
        $rows = self::catalog( $group );
        if ( ! $rows ) return new WP_Error( 'krev_tech_catalog', 'The bundled Google taxonomy could not be read.' );
        $key = self::progress_key( $group ); $offset = min( count( $rows ), absint( get_option( $key, 0 ) ) );
        foreach ( array_slice( $rows, $offset, min( 100, max( 1, (int) $limit ) ) ) as $row ) {
            $slug = $row['slug'] ?? ( ( 'technology' === $group ? 'tech' : $group ) . '-google-' . $row['id'] );
            $term = get_term_by( 'slug', $slug, 'product_cat' );
            if ( $term && (int) $term->parent !== $root ) return new WP_Error( 'krev_tech_conflict', 'A reserved Google category slug already belongs to another parent. No category was moved.' );
            if ( ! $term ) {
                $created = wp_insert_term( $row['name'], 'product_cat', array( 'slug' => $slug, 'parent' => $root ) );
                if ( is_wp_error( $created ) ) return $created;
                $term = get_term( $created['term_id'], 'product_cat' );
            }
            $a = update_term_meta( $term->term_id, self::ID_META, (string) $row['id'] );
            $b = update_term_meta( $term->term_id, self::PATH_META, $row['path'] );
            if ( is_wp_error( $a ) || is_wp_error( $b ) ) return new WP_Error( 'krev_tech_meta', 'The Google mapping could not be saved. Retry this batch.' );
            update_option( $key, ++$offset, false );
        }
        return array( 'completed' => $offset, 'total' => count( $rows ) );
    }
    public static function handle() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'You cannot manage Google subcategories.' );
        check_admin_referer( 'krev_tech_categories' );
        $group = sanitize_key( wp_unslash( $_POST['group'] ?? 'technology' ) );
        $result = self::import_batch( 100, $group );
        update_user_meta( get_current_user_id(), '_krev_tech_import_error', is_wp_error( $result ) ? $result->get_error_message() : '' );
        wp_safe_redirect( add_query_arg( 'page', self::PAGE, admin_url( 'admin.php' ) ) ); exit;
    }
    public static function page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) return;
        echo '<div class="wrap"><h1>Google Subcategories</h1><p>Official Google taxonomy retrieved October 11, 2026. Searchable native children are mapped to their exact Google IDs. Tech also includes merchant aliases for iPhones, mobile devices, smart watches and Apple Watches. Existing products are preserved.</p><p><a href="https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt" target="_blank" rel="noopener">Official Google product taxonomy</a></p>';
        $error = get_user_meta( get_current_user_id(), '_krev_tech_import_error', true );
        if ( $error ) echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
        foreach ( self::groups() as $group => $label ) {
            $total = count( self::catalog( $group ) ); $done = min( $total, absint( get_option( self::progress_key( $group ), 0 ) ) );
            echo '<h2>' . esc_html( $label ) . '</h2><p><strong>' . esc_html( "$done of $total subcategories imported" ) . '</strong></p>';
            if ( $done < $total ) {
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="krev_tech_categories"><input type="hidden" name="group" value="' . esc_attr( $group ) . '">';
                wp_nonce_field( 'krev_tech_categories' ); submit_button( 'Import next 100 ' . $label . ' subcategories' ); echo '</form>';
            } else echo '<p>Import complete. Searchable ListLab subcategories and automatic Google feed mappings are ready.</p>';
        }
        echo '</div>';
    }
}
