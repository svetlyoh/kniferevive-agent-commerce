<?php
defined( 'ABSPATH' ) || exit;

/** Seller-scoped durable preparations. The delivery key only routes submissions. */
final class KREV_Import_Inbox {
    const META = '_krev_import_inbox_connection';
    const SCHEMA = '1';
    const LIMIT = 100;

    public static function table() { global $wpdb; return $wpdb->prefix . 'krev_marketplace_inbox'; }

    public static function install() {
        if ( get_option( 'krev_import_inbox_schema' ) === self::SCHEMA ) return;
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table(); $charset = $wpdb->get_charset_collate();
        dbDelta( "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            seller_id bigint(20) unsigned NOT NULL,
            source_hash char(64) NOT NULL,
            record_key varchar(100) NOT NULL,
            product_id bigint(20) unsigned NOT NULL DEFAULT 0,
            archived tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY seller_source (seller_id,source_hash),
            KEY seller_queue (seller_id,archived,id)
        ) {$charset};" );
        if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) update_option( 'krev_import_inbox_schema', self::SCHEMA, false );
    }

    public static function routes() {
        register_rest_route( KREV_Marketplace_Imports::NS, '/inbox', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'index' ), 'permission_callback' => array( __CLASS__, 'permission' ) ) );
        register_rest_route( KREV_Marketplace_Imports::NS, '/inbox/connection', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'connection' ), 'permission_callback' => array( __CLASS__, 'permission' ) ) );
        register_rest_route( KREV_Marketplace_Imports::NS, '/inbox/(?P<id>\d+)', array( 'methods' => 'PATCH', 'callback' => array( __CLASS__, 'archive' ), 'permission_callback' => array( __CLASS__, 'permission' ) ) );
    }

    private static function error( $code, $message, $status = 422 ) { return new WP_Error( $code, $message, array( 'status' => $status ) ); }
    private static function response( $data, $status = 200 ) {
        $r = new WP_REST_Response( $data, $status ); $r->header( 'Cache-Control', 'private, no-store' ); $r->header( 'Referrer-Policy', 'no-referrer' ); return $r;
    }

    public static function permission( WP_REST_Request $request ) {
        if ( ! KREV_Marketplace_Imports::seller_permission() ) return self::error( 'import_inbox_seller', 'Sign in to an enabled seller account to open your inbox.', 403 );
        if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) return self::error( 'import_inbox_nonce', 'Refresh the page and try again.', 403 );
        return true;
    }

    /** Check a destination without impersonating its user or widening its capabilities. */
    public static function seller_enabled( $id ) {
        if ( ! get_user_by( 'id', $id ) || ! user_can( $id, 'edit_products' ) ) return false;
        if ( user_can( $id, 'manage_woocommerce' ) || user_can( $id, 'manage_options' ) ) return true;
        if ( function_exists( 'dokan_is_user_seller' ) && ! dokan_is_user_seller( $id ) ) return false;
        return ! function_exists( 'dokan_is_seller_enabled' ) || dokan_is_seller_enabled( $id );
    }

    public static function connection( WP_REST_Request $request ) {
        $params = $request->get_json_params(); $action = $params['action'] ?? '';
        if ( ! in_array( $action, array( 'connect', 'disconnect' ), true ) ) return self::error( 'import_inbox_action', 'Choose connect or disconnect.' );
        if ( 'disconnect' === $action ) { delete_user_meta( get_current_user_id(), self::META ); return self::response( array( 'connected' => false ) ); }
        if ( true !== ( $params['authorize_queue'] ?? false ) ) return self::error( 'import_inbox_consent', 'Confirm that Muse may send preparations to your inbox.' );
        $cache = $params['preserve_photos'] ?? false;
        if ( ! is_bool( $cache ) ) return self::error( 'import_inbox_consent', 'Choose whether to preserve photos.' );
        if ( $cache && ! current_user_can( 'upload_files' ) ) return self::error( 'import_inbox_photos', 'Your account cannot upload photos.', 403 );
        $secret = bin2hex( random_bytes( 32 ) );
        update_user_meta( get_current_user_id(), self::META, array( 'hash' => hash( 'sha256', $secret ), 'preserve_photos' => $cache ) );
        // Reveal once; the stored hash cannot be used to retrieve or impersonate a seller.
        return self::response( array( 'connected' => true, 'preserve_photos' => $cache, 'inbox_key' => get_current_user_id() . '.' . $secret, 'inbox_url' => self::url() ) );
    }

    public static function destination( $key ) {
        if ( ! is_string( $key ) || ! preg_match( '/^([1-9]\d{0,9})\.([a-f0-9]{64})$/D', $key, $m ) ) return self::error( 'import_inbox_destination', 'This Muse inbox connection is unavailable.', 403 );
        $seller = (int) $m[1]; $config = get_user_meta( $seller, self::META, true );
        if ( ! is_array( $config ) || empty( $config['hash'] ) || ! hash_equals( $config['hash'], hash( 'sha256', $m[2] ) ) || ! self::seller_enabled( $seller ) ) return self::error( 'import_inbox_destination', 'This Muse inbox connection is unavailable.', 403 );
        if ( ! empty( $config['preserve_photos'] ) && ! user_can( $seller, 'upload_files' ) ) return self::error( 'import_inbox_destination', 'This Muse inbox connection is unavailable.', 403 );
        return array( 'seller_id' => $seller, 'preserve_photos' => ! empty( $config['preserve_photos'] ) );
    }

    public static function url( $id = 0 ) {
        $args = array( 'krev_listlab_import' => 'review', 'inbox' => 1 ); if ( $id ) $args['inbox_id'] = (int) $id;
        return add_query_arg( $args, home_url( '/' ) );
    }

    private static function row( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d AND seller_id=%d', absint( $id ), get_current_user_id() ), ARRAY_A );
    }

    public static function link_product( $record_key, $product_id ) {
        global $wpdb; $wpdb->update( self::table(), array( 'product_id' => absint( $product_id ) ), array( 'record_key' => $record_key, 'seller_id' => get_current_user_id() ) );
    }

    public static function record( WP_REST_Request $request ) {
        $allowed = self::permission( $request ); if ( is_wp_error( $allowed ) ) return $allowed;
        $params = $request->get_json_params(); $id = $params['inbox_id'] ?? 0;
        if ( ! is_scalar( $id ) || ! preg_match( '/^[1-9]\d{0,18}$/D', (string) $id ) ) return self::error( 'import_inbox_missing', 'This inbox item is unavailable.', 404 );
        $row = self::row( $id ); $record = $row ? get_option( $row['record_key'] ) : null;
        if ( ! is_array( $record ) || (int) $record['seller_id'] !== get_current_user_id() ) return self::error( 'import_inbox_missing', 'This inbox item is unavailable.', 404 );
        if ( $row['archived'] ) return self::error( 'import_inbox_archived', 'Restore this item in your inbox before opening it.', 409 );
        return array( $row['record_key'], $record );
    }

    public static function enqueue( array $data, array $destination ) {
        global $wpdb;
        self::install(); $seller = $destination['seller_id'];
        $source_hash = hash( 'sha256', $data['source_url'] );
        $lock = 'krev_inbox_delivery_' . hash( 'sha256', (string) $seller );
        if ( ! add_option( $lock, time(), '', false ) ) return self::error( 'import_inbox_busy', 'This item is arriving. Check your inbox before retrying.', 409 );
        $record_key = ''; $delivery_locked = true;
        try {
            $existing = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE seller_id=%d AND source_hash=%s', $seller, $source_hash ), ARRAY_A );
            if ( $existing ) return self::arrival( $existing['id'], true ); // Never overwrite reviewed facts or create replacement products.
            $rows = $wpdb->get_col( $wpdb->prepare( 'SELECT record_key FROM ' . self::table() . ' WHERE seller_id=%d AND archived=0', $seller ) );
            $waiting = 0; foreach ( $rows as $key ) { $r = get_option( $key ); if ( is_array( $r ) && empty( $r['product_id'] ) ) ++$waiting; }
            if ( $waiting >= self::LIMIT ) return self::error( 'import_inbox_full', 'Your inbox has 100 waiting items. Complete or archive some before sending more.', 409 );
            $hour = 'krev_inbox_rate_' . $seller; $count = (int) get_transient( $hour );
            if ( $count >= 20 ) return self::error( 'import_inbox_rate', 'This inbox received 20 new items in an hour. Try later.', 429 );
            set_transient( $hour, $count + 1, HOUR_IN_SECONDS );
            $record_key = KREV_Marketplace_Imports::PREFIX . hash( 'sha256', random_bytes( 32 ) );
            $record = array( 'expires' => 0, 'seller_id' => $seller, 'product_id' => 0, 'data' => $data, 'uploaded' => array(), 'inbox' => true, 'warnings' => array() );
            if ( ! add_option( $record_key, $record, '', false ) ) return self::error( 'import_storage', 'Unable to queue this item.', 503 );
            if ( ! add_option( $record_key . '_lock', time(), '', false ) ) { delete_option( $record_key ); return self::error( 'import_storage', 'Unable to queue this item.', 503 ); }
            if ( false === $wpdb->insert( self::table(), array( 'seller_id' => $seller, 'source_hash' => $source_hash, 'record_key' => $record_key, 'created_at' => current_time( 'mysql', true ) ) ) ) { delete_option( $record_key ); return self::error( 'import_storage', 'Unable to queue this item.', 503 ); }
            $id = (int) $wpdb->insert_id;
            // Only serialize queue insertion/quota. Slow photos must not block every later arrival.
            delete_option( $lock ); $delivery_locked = false;
            if ( $destination['preserve_photos'] ) {
                foreach ( $data['image_urls'] as $index => $url ) {
                    try { $image = KREV_Marketplace_Imports::copy_image( $url, $data['listing']['title'], $data['photo_manifest']['small_accepted'] ?? false, $seller ); }
                    catch ( Throwable $e ) { $image = self::error( 'import_image_copy', 'Photo preservation failed.' ); }
                    if ( is_wp_error( $image ) ) $record['warnings'][] = 'Photo ' . ( $index + 1 ) . ' could not be preserved. Refresh its source photo before creating the draft.';
                    else $record['uploaded'][ hash( 'sha256', $url ) ] = $image;
                    update_option( $record_key, $record, false ); // Preserve partial progress if a request is interrupted.
                }
            } elseif ( $data['image_urls'] ) $record['warnings'][] = 'Original photo links may expire while queued. Photos will be copied when you create the draft.';
            $record['arrival_complete'] = true; update_option( $record_key, $record, false );
            return self::arrival( $id, false );
        } finally { if ( $record_key ) delete_option( $record_key . '_lock' ); if ( $delivery_locked ) delete_option( $lock ); }
    }

    private static function arrival( $id, $duplicate ) {
        // No title, record key, private token, product details or read grant in an anonymous delivery response.
        return self::response( array( 'state' => 'queued', 'inbox_id' => (int) $id, 'already_queued' => $duplicate, 'inbox_url' => self::url(), 'review_url' => self::url( $id ), 'product_created' => false ), $duplicate ? 200 : 201 );
    }

    private static function dto( $row ) {
        $record = get_option( $row['record_key'] );
        if ( ! is_array( $record ) ) return array( 'id' => (int) $row['id'], 'state' => 'attention', 'title' => 'Import requires recovery', 'review_url' => self::url( $row['id'] ) );
        $data = $record['data']; $product_id = absint( $record['product_id'] );
        $product = $product_id && (int) get_post_field( 'post_author', $product_id ) === get_current_user_id() ? wc_get_product( $product_id ) : null;
        $state = $product_id ? ( $product ? ( 'publish' === $product->get_status() ? 'published' : ( 'trash' === $product->get_status() ? 'attention' : 'draft' ) ) : 'attention' ) : ( empty( $record['arrival_complete'] ) ? 'attention' : 'queued' );
        $image = $product ? $product->get_image_id() : ( $record['uploaded'] ? (int) reset( $record['uploaded'] ) : 0 );
        return array( 'id' => (int) $row['id'], 'state' => $row['archived'] ? 'archived' : $state, 'product_status' => $product ? $product->get_status() : '', 'created_at' => str_replace( ' ', 'T', $row['created_at'] ) . 'Z', 'title' => $product ? $product->get_name() : $data['listing']['title'], 'source_price' => $data['source_price'], 'currency' => 'USD', 'source_url' => $data['source_url'], 'thumbnail_url' => $image ? wp_get_attachment_image_url( $image, 'thumbnail' ) : '', 'photo_counts' => $record['photo_counts'] ?? KREV_Import_Photo_Manifest::counts( $data ), 'preserved_photos' => count( $record['uploaded'] ), 'warnings' => $record['warnings'] ?? array(), 'product_id' => $product_id, 'edit_url' => $product && 'trash' !== $product->get_status() ? add_query_arg( array( 'action' => 'edit', 'product_id' => $product_id ), wc_get_account_endpoint_url( 'listlab' ) ) : '', 'review_url' => self::url( $row['id'] ) );
    }

    public static function index( WP_REST_Request $request ) {
        global $wpdb;
        $page = max( 1, min( 10000, absint( $request['page'] ?: 1 ) ) );
        $tab = in_array( $request['tab'], array( 'waiting', 'drafts', 'published', 'archived', 'all' ), true ) ? $request['tab'] : 'waiting';
        $archived = 'archived' === $tab ? 1 : 0;
        $where = $wpdb->prepare( 'q.seller_id=%d AND q.archived=%d', get_current_user_id(), $archived );
        if ( 'waiting' === $tab ) $where .= ' AND q.product_id=0';
        if ( 'drafts' === $tab ) $where .= " AND q.product_id<>0 AND (p.post_status IS NULL OR p.post_status<>'publish')";
        if ( 'published' === $tab ) $where .= " AND p.post_status='publish'";
        $from = self::table() . " q LEFT JOIN {$wpdb->posts} p ON q.product_id=p.ID AND p.post_type='product' AND p.post_author=q.seller_id";
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$from} WHERE {$where}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT q.* FROM {$from} WHERE {$where} ORDER BY q.id ASC LIMIT 20 OFFSET %d", ( $page - 1 ) * 20 ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $config = get_user_meta( get_current_user_id(), self::META, true );
        return self::response( array( 'items' => array_map( array( __CLASS__, 'dto' ), $rows ), 'page' => $page, 'pages' => max( 1, (int) ceil( $total / 20 ) ), 'total' => $total, 'tab' => $tab, 'connected' => is_array( $config ) && ! empty( $config['hash'] ), 'preserve_photos' => ! empty( $config['preserve_photos'] ), 'inbox_url' => self::url() ) );
    }

    public static function archive( WP_REST_Request $request ) {
        global $wpdb;
        $row = self::row( $request['id'] ); if ( ! $row ) return self::error( 'import_inbox_missing', 'This inbox item is unavailable.', 404 );
        $params = $request->get_json_params();
        if ( ! isset( $params['archived'] ) || ! is_bool( $params['archived'] ) ) return self::error( 'import_inbox_archive', 'Choose archive or restore.' );
        if ( get_option( $row['record_key'] . '_lock' ) ) return self::error( 'import_inbox_busy', 'This item is processing. Try after it finishes.', 409 );
        $wpdb->update( self::table(), array( 'archived' => $params['archived'] ? 1 : 0 ), array( 'id' => $row['id'], 'seller_id' => get_current_user_id() ) );
        return self::response( array( 'id' => (int) $row['id'], 'archived' => $params['archived'], 'product_unchanged' => true ) );
    }
}
