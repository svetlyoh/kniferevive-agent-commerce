<?php
defined( 'ABSPATH' ) || exit;

final class KREV_Marketplace_Imports {
    const NS = 'kniferevive-listlab-import/v1';
    const PREFIX = 'krev_import_';
    const TTL = 7200;
    const MAX_IMAGE = 5242880;
    const FIELDS = array( 'title', 'description', 'short_description', 'subtitle', 'category_id', 'quantity', 'sku', 'global_unique_id', 'shipping_policy_id', 'return_policy_id', 'weight', 'length', 'width', 'height', 'attributes', 'featured_image_id', 'gallery_ids' );

    public static function boot() {
        if ( ! class_exists( 'KREV_ListLab_Product_Writer' ) || ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', static function() { echo '<div class="notice notice-error"><p>Marketplace Imports requires WooCommerce and KnifeRevive ListLab.</p></div>'; } );
            return;
        }
        add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
        add_action( 'admin_menu', array( 'KREV_Import_Pricing', 'menu' ) );
        add_action( 'template_redirect', array( __CLASS__, 'review_page' ), 1 );
        add_action( 'woocommerce_account_listlab_endpoint', array( __CLASS__, 'listlab_link' ), 5 );
        add_action( 'krev_import_cleanup', array( __CLASS__, 'cleanup' ) );
        add_filter( 'login_redirect', array( __CLASS__, 'login_return' ), 1000, 3 );
        add_filter( 'woocommerce_login_redirect', array( __CLASS__, 'account_login_return' ), 1000, 2 );
        if ( ! wp_next_scheduled( 'krev_import_cleanup' ) ) wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'krev_import_cleanup' );
    }

    public static function routes() {
        register_rest_route( self::NS, '/schema', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'schema' ), 'permission_callback' => '__return_true' ) );
        foreach ( array( 'prepare', 'status', 'preview', 'claim' ) as $action ) {
            register_rest_route( self::NS, '/' . $action, array( 'methods' => 'POST', 'callback' => array( __CLASS__, $action ), 'permission_callback' => 'claim' === $action ? array( __CLASS__, 'seller_permission' ) : '__return_true' ) );
        }
    }

    public static function seller_permission() { return is_user_logged_in() && current_user_can( 'edit_products' ) && KREV_ListLab_Dokan_Adapter::can_sell(); }
    private static function fail( $code, $message, $status = 422 ) { return new WP_Error( $code, $message, array( 'status' => $status ) ); }
    private static function response( $data, $status = 200 ) { $r = new WP_REST_Response( $data, $status ); $r->header( 'Cache-Control', 'private, no-store' ); $r->header( 'Referrer-Policy', 'no-referrer' ); return $r; }

    public static function schema( WP_REST_Request $request ) {
        $schema = ( new KREV_ListLab_REST() )->schema( $request );
        return self::response( array( 'version' => KREV_IMPORT_VERSION, 'prepare_url' => rest_url( self::NS . '/prepare' ), 'currency' => get_woocommerce_currency(), 'categories' => $schema['categories'], 'attributes' => $schema['attributes'], 'listing_fields' => self::FIELDS, 'source_fields' => array( 'source_url', 'source_price', 'currency', 'image_urls' ), 'max_images' => 10, 'expires_in_seconds' => self::TTL, 'creates' => 'private preparation only; enabled seller completes a ListLab draft', 'shipping' => 'Separate native WooCommerce shipping; never copied from Facebook', 'review_url' => add_query_arg( 'krev_listlab_import', 'review', home_url( '/' ) ) ) );
    }

    public static function source_url( $value ) {
        if ( ! is_string( $value ) || strlen( $value ) > 2048 ) return self::fail( 'import_source', 'Provide a direct Facebook Marketplace item link.' );
        $parts = wp_parse_url( $value );
        if ( ! $parts || 'https' !== ( $parts['scheme'] ?? '' ) || ! in_array( strtolower( $parts['host'] ?? '' ), array( 'facebook.com', 'www.facebook.com', 'm.facebook.com' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) || ! preg_match( '#^/marketplace/item/(\d{1,30})/?$#D', $parts['path'] ?? '', $match ) ) return self::fail( 'import_source', 'Open the item and use its direct HTTPS Facebook Marketplace link.' );
        return 'https://www.facebook.com/marketplace/item/' . $match[1] . '/';
    }

    public static function image_url( $value ) {
        if ( ! is_string( $value ) || strlen( $value ) > 4096 ) return false;
        $parts = wp_parse_url( $value ); $host = strtolower( $parts['host'] ?? '' );
        return $parts && 'https' === ( $parts['scheme'] ?? '' ) && ! isset( $parts['user'] ) && ! isset( $parts['pass'] ) && ! isset( $parts['port'] ) && preg_match( '/(?:^|\.)(?:fbcdn\.net|fbsbx\.com)$/D', $host ) ? esc_url_raw( $value ) : false;
    }

    private static function normalize( $data ) {
        if ( ! is_array( $data ) || strlen( wp_json_encode( $data ) ) > 65536 ) return self::fail( 'import_size', 'Import data must be a JSON object below 64 KB.' );
        $unknown = array_diff( array_keys( $data ), array_merge( self::FIELDS, array( 'source_url', 'source_price', 'currency', 'image_urls' ) ) );
        if ( $unknown ) return self::fail( 'import_fields', 'Unsupported import field: ' . implode( ', ', $unknown ) );
        $source = self::source_url( $data['source_url'] ?? '' );
        if ( is_wp_error( $source ) ) return $source;
        if ( 'USD' !== ( $data['currency'] ?? 'USD' ) ) return self::fail( 'import_currency', 'Use an explicitly USD source price; no currency conversion is performed.' );
        $listing = array();
        foreach ( self::FIELDS as $key ) {
            if ( ! array_key_exists( $key, $data ) ) continue;
            $value = $data[ $key ];
            if ( in_array( $key, array( 'attributes', 'gallery_ids' ), true ) ) {
                if ( ! is_array( $value ) ) return self::fail( 'import_field_type', $key . ' must be an object/array.' );
                $listing[ $key ] = $value; continue;
            }
            if ( ! is_scalar( $value ) || is_bool( $value ) ) return self::fail( 'import_field_type', $key . ' must be text or a number.' );
            $listing[ $key ] = in_array( $key, array( 'description', 'short_description' ), true ) ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
        }
        foreach ( array( 'category_id', 'quantity', 'shipping_policy_id', 'return_policy_id', 'featured_image_id' ) as $field ) {
            if ( isset( $listing[ $field ] ) && '' !== $listing[ $field ] && ! preg_match( '/^\d{1,9}$/D', (string) $listing[ $field ] ) ) return self::fail( 'import_integer', $field . ' must be a non-negative whole number.' );
        }
        $category = absint( $listing['category_id'] ?? 0 );
        $term = $category ? get_term( $category, 'product_cat' ) : null;
        if ( ! $term || is_wp_error( $term ) ) return self::fail( 'import_category', 'Choose an existing KnifeRevive category from the import schema.' );
        $quote = KREV_Import_Pricing::quote( $data['source_price'] ?? '', $category );
        if ( is_wp_error( $quote ) ) return $quote;
        foreach ( array( 'weight', 'length', 'width', 'height' ) as $field ) if ( ! empty( $listing[ $field ] ) && ! preg_match( '/^\d+(?:\.\d{1,4})?$/D', (string) $listing[ $field ] ) ) return self::fail( 'import_decimal', $field . ' must be a non-negative number in the store units.' );
        $attrs = $listing['attributes'] ?? array();
        $schema = KREV_ListLab_Attributes::schema( $category );
        $allowed = array_column( $schema, 'taxonomy' );
        foreach ( $attrs as $taxonomy => $values ) {
            if ( ! in_array( $taxonomy, $allowed, true ) || ! is_array( $values ) || count( $values ) > 20 ) return self::fail( 'import_attributes', 'Use the selected category’s native attribute schema.' );
            foreach ( $values as $value ) if ( ! is_scalar( $value ) || is_bool( $value ) || strlen( (string) $value ) > 200 ) return self::fail( 'import_attributes', 'Attribute values must be short text or existing term IDs.' );
            $listing['attributes'][ $taxonomy ] = array_map( 'sanitize_text_field', $values );
        }
        $urls = $data['image_urls'] ?? array();
        if ( ! is_array( $urls ) || count( $urls ) > 10 || count( $listing['gallery_ids'] ?? array() ) > 9 ) return self::fail( 'import_images', 'Use at most 10 images.' );
        $images = array();
        foreach ( $urls as $url ) { $valid = self::image_url( $url ); if ( ! $valid ) return self::fail( 'import_image_host', 'Use original HTTPS Facebook CDN image URLs or upload images in ListLab.' ); $images[] = $valid; }
        foreach ( $listing['gallery_ids'] ?? array() as $id ) if ( ! is_scalar( $id ) || ! preg_match( '/^\d{1,9}$/D', (string) $id ) ) return self::fail( 'import_images', 'Gallery image IDs must be whole numbers.' );
        if ( count( array_unique( $images ) ) + count( $listing['gallery_ids'] ?? array() ) + ( empty( $listing['featured_image_id'] ) ? 0 : 1 ) > 10 ) return self::fail( 'import_images', 'Use at most 10 images total.' );
        if ( empty( $listing['title'] ) || strlen( $listing['title'] ) > 200 || strlen( $listing['description'] ?? '' ) > 16000 ) return self::fail( 'import_title', 'Provide a title (up to 200 characters) and a description below 16 KB.' );
        return array( 'source_url' => $source, 'source_price' => $quote['source_price'], 'currency' => 'USD', 'listing' => $listing, 'image_urls' => array_values( array_unique( $images ) ) );
    }

    public static function prepare( WP_REST_Request $request ) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rate = 'krev_import_rate_' . hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) );
        $count = (int) get_transient( $rate );
        $site_count = (int) get_transient( 'krev_import_site_rate' );
        if ( $count >= 20 || $site_count >= 200 ) return self::fail( 'import_rate', 'Too many imports. Try again in an hour.', 429 );
        set_transient( $rate, $count + 1, HOUR_IN_SECONDS );
        set_transient( 'krev_import_site_rate', $site_count + 1, HOUR_IN_SECONDS );
        $data = self::normalize( $request->get_json_params() );
        if ( is_wp_error( $data ) ) return $data;
        $token = bin2hex( random_bytes( 32 ) ); $key = self::key( $token );
        $record = array( 'expires' => time() + self::TTL, 'seller_id' => 0, 'product_id' => 0, 'data' => $data, 'uploaded' => array() );
        if ( ! add_option( $key, $record, '', false ) ) return self::fail( 'import_storage', 'Unable to prepare the import.', 503 );
        return self::response( array( 'state' => 'prepared', 'expires_in_seconds' => self::TTL, 'review_url' => add_query_arg( 'krev_listlab_import', 'review', home_url( '/' ) ) . '#import=' . $token, 'price' => KREV_Import_Pricing::quote( $data['source_price'], $data['listing']['category_id'] ), 'product_created' => false ), 201 );
    }

    private static function key( $token ) { return self::PREFIX . hash( 'sha256', $token ); }
    private static function record( $request ) {
        $params = $request->get_json_params(); $token = is_array( $params ) ? ( $params['token'] ?? '' ) : '';
        if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/D', $token ) ) return self::fail( 'import_missing', 'This private import link is invalid or expired.', 404 );
        $key = self::key( $token ); $record = get_option( $key );
        if ( ! is_array( $record ) || $record['expires'] <= time() ) return self::fail( 'import_missing', 'This private import link is invalid or expired.', 404 );
        if ( $record['seller_id'] && (int) $record['seller_id'] !== get_current_user_id() ) return self::fail( 'import_owner', 'This import belongs to another seller.', 403 );
        return array( $key, $record );
    }

    public static function status( WP_REST_Request $request ) {
        $found = self::record( $request ); if ( is_wp_error( $found ) ) return $found;
        $record = $found[1]; $listing = $record['data']['listing'];
        $schema_request = new WP_REST_Request( 'GET' ); $schema_request->set_param( 'category_id', $listing['category_id'] );
        $schema = ( new KREV_ListLab_REST() )->schema( $schema_request );
        return self::response( array( 'state' => $record['product_id'] ? 'draft_created' : 'prepared', 'data' => $record['data'], 'price' => KREV_Import_Pricing::quote( $record['data']['source_price'], $listing['category_id'] ), 'schema' => $schema, 'seller_ready' => self::seller_permission(), 'expires_at' => gmdate( 'c', $record['expires'] ), 'listing' => $record['product_id'] ? self::owned_dto( $record['product_id'] ) : null, 'warnings' => $record['warnings'] ?? array() ) );
    }

    private static function changed_data( $request, $record ) {
        $params = $request->get_json_params(); $changes = is_array( $params ) ? ( $params['changes'] ?? array() ) : array();
        if ( ! is_array( $changes ) || array_diff( array_keys( $changes ), self::FIELDS ) ) return self::fail( 'import_changes', 'Unsupported listing changes.' );
        // A category switch uses the new native schema; old-category attributes are not carried silently.
        $listing = $record['data']['listing'];
        if ( isset( $changes['category_id'] ) && (int) $changes['category_id'] !== (int) $listing['category_id'] && ! isset( $changes['attributes'] ) ) $listing['attributes'] = array();
        return self::normalize( array_merge( array( 'source_url' => $record['data']['source_url'], 'source_price' => $record['data']['source_price'], 'currency' => $record['data']['currency'], 'image_urls' => $record['data']['image_urls'] ), $listing, $changes ) );
    }

    public static function preview( WP_REST_Request $request ) {
        $found = self::record( $request ); if ( is_wp_error( $found ) ) return $found;
        $data = self::changed_data( $request, $found[1] ); if ( is_wp_error( $data ) ) return $data;
        $s = new WP_REST_Request( 'GET' ); $s->set_param( 'category_id', $data['listing']['category_id'] );
        return self::response( array( 'data' => $data, 'price' => KREV_Import_Pricing::quote( $data['source_price'], $data['listing']['category_id'] ), 'schema' => ( new KREV_ListLab_REST() )->schema( $s ) ) );
    }

    private static function owned_dto( $id ) {
        $product = wc_get_product( $id );
        return $product && (int) get_post_field( 'post_author', $id ) === get_current_user_id() ? KREV_ListLab_Product_Reader::dto( $product ) : null;
    }

    public static function claim( WP_REST_Request $request ) {
        if ( ! self::seller_permission() ) return self::fail( 'import_seller', 'Sign in to an enabled KnifeRevive seller account.', 403 );
        $found = self::record( $request ); if ( is_wp_error( $found ) ) return $found;
        list( $key, $record ) = $found; $params = $request->get_json_params();
        if ( true !== ( $params['authorized_to_list'] ?? false ) ) return self::fail( 'import_rights', 'Confirm that you can sell this item and use its description and photos.' );
        // Persistent lock: interrupted requests require original-draft recovery, never lease expiry + replacement.
        if ( ! add_option( $key . '_lock', time(), '', false ) ) return self::fail( 'import_busy', 'Import is processing or requires original-draft recovery. Use the same link; do not prepare another import.', 409 );
        $source_lock = '';
        try {
            $record = get_option( $key );
            if ( $record['seller_id'] && (int) $record['seller_id'] !== get_current_user_id() ) return self::fail( 'import_owner', 'This import belongs to another seller.', 403 );
            $uuid = 'marketplace-source:' . hash( 'sha256', $record['data']['source_url'] );
            $lock_name = self::PREFIX . 'source_lock_' . hash( 'sha256', get_current_user_id() . ':' . $uuid );
            if ( ! add_option( $lock_name, time(), '', false ) ) return self::fail( 'import_busy', 'This source item is already processing. Recover its original seller draft.', 409 );
            $source_lock = $lock_name;
            $query = new WP_Query( array( 'post_type' => 'product', 'post_status' => array( 'draft', 'pending', 'publish', 'private', 'trash' ), 'author' => get_current_user_id(), 'meta_key' => '_krev_listlab_client_uuid', 'meta_value' => $uuid, 'fields' => 'ids', 'posts_per_page' => 1 ) );
            $existing = $record['product_id'] ?: ( $query->posts[0] ?? 0 );
            if ( $existing ) {
                $dto = self::owned_dto( $existing );
                if ( ! $dto || 'trash' === $dto['status'] ) return self::fail( 'import_original', 'The original listing needs seller review. A replacement was not created.', 409 );
                $record['seller_id'] = get_current_user_id(); $record['product_id'] = $existing; update_option( $key, $record, false );
                return self::response( array( 'state' => 'existing_listing', 'listing' => $dto, 'warnings' => array_merge( $record['warnings'] ?? array(), array( 'Original listing retained. Repeated imports do not overwrite seller edits.' ) ) ) );
            }
            $data = self::changed_data( $request, $record ); if ( is_wp_error( $data ) ) return $data;
            $price = KREV_Import_Pricing::quote( $data['source_price'], $data['listing']['category_id'] ); if ( is_wp_error( $price ) ) return $price;
            if ( ( $params['expected_price'] ?? null ) !== $price['regular_price'] ) return self::fail( 'import_price_changed', 'Pricing changed or was not reviewed. Refresh the preview before creating the draft.', 409 );
            $listing = $data['listing'];
            foreach ( array_merge( array( $listing['featured_image_id'] ?? 0 ), $listing['gallery_ids'] ?? array() ) as $id ) if ( $id && ! KREV_ListLab_Image_Handler::authorized_image( $id ) ) return self::fail( 'import_images', 'An image is not authorized for this seller.', 403 );
            $record['seller_id'] = get_current_user_id(); update_option( $key, $record, false );
            $images = array(); $warnings = array();
            foreach ( $data['image_urls'] as $index => $url ) {
                $image_key = hash( 'sha256', $url ); $id = $record['uploaded'][ $image_key ] ?? 0;
                if ( $id && KREV_ListLab_Image_Handler::authorized_image( $id ) ) { $images[] = $id; continue; }
                $id = self::copy_image( $url, $listing['title'] );
                if ( is_wp_error( $id ) ) { $warnings[] = 'Photo ' . ( $index + 1 ) . ' could not be copied. Add it in ListLab.'; continue; }
                $record['uploaded'][ $image_key ] = $id; update_option( $key, $record, false ); $images[] = $id;
            }
            if ( empty( $listing['featured_image_id'] ) && $images ) $listing['featured_image_id'] = array_shift( $images );
            $listing['gallery_ids'] = array_values( array_unique( array_merge( $listing['gallery_ids'] ?? array(), $images ) ) );
            $listing['regular_price'] = $price['regular_price']; $listing['publish'] = false; $listing['client_uuid'] = $uuid;
            $dto = KREV_ListLab_Product_Writer::save( $listing );
            if ( is_wp_error( $dto ) ) return $dto;
            $record['product_id'] = $dto['id']; $record['data'] = $data; $record['warnings'] = $warnings; update_option( $key, $record, false );
            $product = wc_get_product( $dto['id'] );
            $product->update_meta_data( '_krev_import_source_url', $data['source_url'] );
            $product->update_meta_data( '_krev_import_source_price', $data['source_price'] );
            $product->update_meta_data( '_krev_import_pricing_snapshot', $price );
            $product->save();
            return self::response( array( 'state' => 'draft_created', 'listing' => self::owned_dto( $dto['id'] ), 'price' => $price, 'warnings' => $warnings ), 201 );
        } finally { if ( $source_lock ) delete_option( $source_lock ); delete_option( $key . '_lock' ); }
    }

    public static function copy_image( $url, $title ) {
        if ( ! self::image_url( $url ) ) return self::fail( 'import_image_host', 'Unsupported image host.' );
        require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
        $temp = wp_tempnam( 'krev-import' ); if ( ! $temp ) return self::fail( 'import_image_temp', 'Unable to stage image.' );
        try {
            $response = wp_safe_remote_get( $url, array( 'timeout' => 10, 'redirection' => 0, 'stream' => true, 'filename' => $temp, 'limit_response_size' => self::MAX_IMAGE + 1, 'cookies' => array(), 'headers' => array( 'Accept' => 'image/jpeg,image/png,image/webp' ) ) );
            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) || ! filesize( $temp ) || filesize( $temp ) > self::MAX_IMAGE ) return self::fail( 'import_image_fetch', 'Image unavailable or too large.' );
            $size = wp_getimagesize( $temp ); $mime = $size['mime'] ?? '';
            $extensions = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp' );
            if ( ! isset( $extensions[ $mime ] ) || $size[0] > 10000 || $size[1] > 10000 || $size[0] * $size[1] > 25000000 ) return self::fail( 'import_image_type', 'Use a bounded JPEG, PNG or WebP photo.' );
            $file = array( 'name' => 'marketplace-' . substr( hash( 'sha256', $url ), 0, 16 ) . '.' . $extensions[ $mime ], 'tmp_name' => $temp );
            $id = media_handle_sideload( $file, 0, $title, array( 'post_author' => get_current_user_id() ) );
            return is_wp_error( $id ) ? $id : (int) $id;
        } finally { if ( is_file( $temp ) ) unlink( $temp ); }
    }

    public static function cleanup() {
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT option_name, option_value FROM ' . $wpdb->options . ' WHERE option_name LIKE %s ORDER BY option_id LIMIT 1000', $wpdb->esc_like( self::PREFIX ) . '%' ) );
        foreach ( $rows as $row ) { $value = maybe_unserialize( $row->option_value ); if ( is_array( $value ) && isset( $value['expires'] ) && $value['expires'] <= time() ) { delete_option( $row->option_name ); delete_option( $row->option_name . '_lock' ); } }
    }

    public static function login_return( $redirect, $requested, $user ) {
        $review = add_query_arg( 'krev_listlab_import', 'review', home_url( '/' ) );
        return $user instanceof WP_User && $review === $requested ? $review : $redirect;
    }

    public static function account_login_return( $redirect, $user ) {
        $review = add_query_arg( 'krev_listlab_import', 'review', home_url( '/' ) );
        $referer = wp_get_referer(); $parts = $referer ? wp_parse_url( $referer ) : array();
        $query = array(); parse_str( $parts['query'] ?? '', $query );
        return $user instanceof WP_User && ( $query['redirect_to'] ?? '' ) === $review ? $review : $redirect;
    }

    public static function listlab_link() { if ( self::seller_permission() ) echo '<p><a class="button" href="' . esc_url( add_query_arg( 'krev_listlab_import', 'review', home_url( '/' ) ) ) . '">Import a Marketplace item from Muse</a></p>'; }

    public static function review_page() {
        if ( 'review' !== ( $_GET['krev_listlab_import'] ?? '' ) ) return;
        nocache_headers(); header( 'Referrer-Policy: no-referrer' ); header( 'X-Robots-Tag: noindex, nofollow' ); header( 'X-Frame-Options: SAMEORIGIN' );
        // Standalone first-party page: theme analytics never receive the private fragment.
        $client = array( 'root' => rest_url( self::NS . '/' ), 'nonce' => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '', 'sellerReady' => self::seller_permission() );
        include KREV_IMPORT_PATH . 'templates/review.php'; exit;
    }
}
