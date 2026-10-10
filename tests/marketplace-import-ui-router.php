<?php
// Loopback-only fixture. Never included in the distributable plugin.
if ( PHP_SAPI !== 'cli-server' || ! in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) ) { http_response_code( 403 ); exit; }
$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$test_root = getenv( 'KREV_TEST_WP_ROOT' );
if ( preg_match( '/\.(js|css)$/D', $path ) && ( str_starts_with( $path, '/wp-content/plugins/kniferevive-listlab/' ) || str_starts_with( $path, '/wp-includes/' ) ) ) {
    $file = realpath( $test_root . $path ); $base = realpath( $test_root );
    if ( $file && $base && str_starts_with( str_replace( '\\', '/', $file ), str_replace( '\\', '/', $base ) . '/' ) ) { header( 'Content-Type: ' . ( str_ends_with( $path, '.js' ) ? 'text/javascript' : 'text/css' ) ); readfile( $file ); exit; }
}
$asset_root = dirname( __DIR__ ) . '/wordpress/kniferevive-listlab-import/assets/';
foreach ( array( 'review.css' => 'text/css', 'review.js' => 'text/javascript' ) as $name => $type ) if ( $path === '/wp-content/plugins/kniferevive-listlab-import/assets/' . $name ) { header( 'Content-Type: ' . $type ); readfile( $asset_root . $name ); exit; }
$argv = array( __FILE__, getenv( 'KREV_TEST_WP_ROOT' ) );
require __DIR__ . '/sandbox-bootstrap.php';
// Match production's pretty REST URLs: native ListLab app appends query filters.
add_filter( 'rest_url', static function( $url, $relative ) { return WP_HOME . '/wp-json/' . ltrim( $relative, '/' ); }, 10, 2 );
require_once ABSPATH . 'wp-content/plugins/kniferevive-listlab/kniferevive-listlab.php';
add_filter( 'plugins_url', static function( $url, $relative, $plugin ) { return str_ends_with( $plugin, 'kniferevive-listlab-import.php' ) ? WP_HOME . '/wp-content/plugins/kniferevive-listlab-import' . ( $relative ? '/' . $relative : '' ) : $url; }, 10, 3 );
require_once dirname( __DIR__ ) . '/wordpress/kniferevive-listlab-import/kniferevive-listlab-import.php';
KREV_Marketplace_Imports::boot();
KREV_ListLab_Plugin::boot();
if ( isset( $_GET['fixture_seller_login'] ) ) { wp_set_current_user( (int) get_option( 'krev_import_ui_seller' ) ); wp_set_auth_cookie( get_current_user_id() ); header( 'Location: /?krev_listlab_import=review' ); exit; }
if ( str_starts_with( $path, '/wp-json/' ) || isset( $_GET['rest_route'] ) ) { global $wp; $wp->query_vars['rest_route'] = $_GET['rest_route'] ?? substr( $path, 8 ); rest_get_server(); ( new KREV_ListLab_REST() )->register(); rest_api_loaded(); exit; }
if ( isset( $_GET['krev_listlab_import'] ) ) KREV_Marketplace_Imports::review_page();
if ( isset( $_GET['listlab'] ) ) {
    global $wp, $wp_query; $wp->query_vars['listlab'] = ''; $wp_query->query_vars['listlab'] = '';
    add_filter( 'woocommerce_is_account_page', '__return_true' );
    ( new KREV_ListLab_Endpoint() )->assets();
    echo '<!doctype html><html><head><meta charset="utf-8"><title>Synthetic native ListLab editor</title>';
    wp_print_styles( array( 'krev-listlab', 'krev-listlab-account' ) );
    echo '</head><body><h1>Synthetic native ListLab editor</h1>';
    include ABSPATH . 'wp-content/plugins/kniferevive-listlab/templates/listlab.php';
    wp_print_footer_scripts(); echo '</body></html>'; exit;
}
if ( isset( $_GET['pricing_settings'] ) ) { $admins = get_users( array( 'role' => 'administrator', 'number' => 1 ) ); wp_set_current_user( $admins[0]->ID ); echo '<!doctype html><html><head><title>Synthetic import pricing</title></head><body>'; KREV_Import_Pricing::admin(); echo '</body></html>'; exit; }
http_response_code( 404 ); echo 'Synthetic import UI fixture only.';
