<?php
/** Fenced, no-network native verification of the original-file mode added after URL recovery. */
require __DIR__ . '/sandbox-bootstrap.php';
if ( DB_NAME !== 'krev_agent_sandbox' || $wpdb->prefix !== 'krev_sandbox_' ) throw new RuntimeException( 'Sandbox fence failed.' );
require_once ABSPATH . 'wp-content/plugins/kniferevive-listlab/kniferevive-listlab.php';
require_once ABSPATH . 'wp-content/plugins/kniferevive-product-attributes/kniferevive-product-attributes.php';
require_once dirname( __DIR__ ) . '/wordpress/kniferevive-listlab-import/kniferevive-listlab-import.php';
KREV_Marketplace_Imports::boot();
add_action( 'rest_api_init', static function() { ( new KREV_ListLab_REST() )->register(); } );
add_filter( 'upload_dir', static function( $u ) { $u['basedir'] = dirname( __DIR__ ) . '/.runtime/import-media'; $u['baseurl'] = WP_HOME . '/fixture-media'; $u['path'] = $u['basedir']; $u['url'] = $u['baseurl']; $u['subdir'] = ''; return $u; } );
$passed = 0;
function verifyFileFlow( $ok, $name ) { global $passed; if ( ! $ok ) throw new RuntimeException( $name ); ++$passed; echo "PASS: $name\n"; }
function fileFlowCall( $path, $body = array(), $method = 'POST' ) {
    $request = new WP_REST_Request( $method, '/kniferevive-listlab-import/v1/' . $path );
    $request->set_header( 'Content-Type', 'application/json' );
    if ( get_current_user_id() ) $request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );
    $request->set_body( wp_json_encode( $body ) ); return rest_do_request( $request );
}
delete_transient( 'krev_import_rate_' . hash_hmac( 'sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown', wp_salt( 'nonce' ) ) ); delete_transient( 'krev_import_site_rate' );
$seller = wp_create_user( 'file-flow-' . wp_generate_uuid4(), wp_generate_password(), 'files-' . wp_generate_uuid4() . '@example.invalid' );
( new WP_User( $seller ) )->set_role( 'administrator' ); wp_set_current_user( $seller );
$key = fileFlowCall( 'inbox/connection', array( 'action' => 'connect', 'authorize_queue' => true, 'preserve_photos' => true ) )->get_data()['inbox_key'];
$category = wp_insert_term( 'Original-file flow ' . wp_generate_uuid4(), 'product_cat' )['term_id'];
$payload = array( 'source_url' => 'https://www.facebook.com/marketplace/item/999777555111/', 'source_price' => '100', 'currency' => 'USD', 'category_id' => $category, 'title' => 'Synthetic original-file item', 'description' => 'Source facts', 'image_urls' => array( 'https://scontent.test.fbcdn.net/original-one.png', 'https://scontent.test.fbcdn.net/original-two.png' ), 'photo_manifest' => array( 'source_count' => 2, 'gallery_complete' => true ), 'inbox_key' => $key, 'photo_delivery_mode' => 'original_files' );
$fetches = 0;
add_filter( 'pre_http_request', static function( $pre, $args, $url ) use ( &$fetches ) { if ( str_contains( $url, 'fbcdn.net' ) ) ++$fetches; return $pre; }, PHP_INT_MAX, 3 );
wp_set_current_user( 0 );
$bad = $payload; unset( $bad['inbox_key'] );
verifyFileFlow( fileFlowCall( 'prepare', $bad )->get_data()['code'] === 'import_photo_mode', 'file mode cannot silently become a private link' );
$receipt = fileFlowCall( 'prepare', $payload ); $data = $receipt->get_data();
verifyFileFlow( $receipt->get_status() === 201 && $data['item_state'] === 'queued' && $data['photo_delivery']['missing'] === 2 && ! $data['photo_delivery']['ready'] && $fetches === 0, 'original-file preparation does not fetch Facebook and reports pending photos' );
$id = $data['inbox_id']; wp_set_current_user( $seller );
$before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product'" );
$blocked = fileFlowCall( 'claim', array( 'inbox_id' => $id, 'authorized_to_list' => true, 'expected_price' => '120.00' ) );
verifyFileFlow( $blocked->get_data()['code'] === 'import_photos_incomplete' && $fetches === 0 && $before === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product'" ), 'claim cannot bypass pending original bytes or fall back to CDN' );
wp_set_current_user( 0 );
foreach ( array( 1, 2 ) as $slot ) {
    $image = imagecreatetruecolor( 600, 600 ); imagefill( $image, 0, 0, imagecolorallocate( $image, 30 * $slot, 90, 150 ) ); ob_start(); imagepng( $image ); $bytes = ob_get_clean(); imagedestroy( $image );
    $result = fileFlowCall( 'inbox/' . $id . '/photos', array( 'inbox_key' => $key, 'photo_index' => $slot, 'image_base64' => base64_encode( $bytes ) ) );
    verifyFileFlow( $result->get_status() === 200 && $result->get_data()['photo_delivery']['preserved'] === $slot, 'native original file slot ' . $slot . ' preserved' );
}
verifyFileFlow( $result->get_data()['photo_delivery']['ready'] && $result->get_data()['photo_delivery']['missing'] === 0 && get_current_user_id() === 0, 'all source files ready without impersonation' );
wp_set_current_user( $seller );
$claimed = fileFlowCall( 'claim', array( 'inbox_id' => $id, 'authorized_to_list' => true, 'expected_price' => '120.00' ) );
verifyFileFlow( $claimed->get_status() === 201 && $claimed->get_data()['photo_counts']['copied'] === 2 && count( $claimed->get_data()['listing']['gallery'] ) === 1 && ! $claimed->get_data()['warnings'] && $fetches === 0, 'native draft has original cover and gallery without any Facebook fetch' );
wp_set_current_user( 0 ); $retained = fileFlowCall( 'prepare', $payload )->get_data();
verifyFileFlow( $retained['already_queued'] && $retained['item_state'] === 'draft' && $retained['photo_delivery']['preserved'] === 2 && $retained['inbox_id'] === $id, 'duplicate receipt identifies retained draft and confirmed native photos' );
wp_set_current_user( $seller );
$off_key = fileFlowCall( 'inbox/connection', array( 'action' => 'connect', 'authorize_queue' => true, 'preserve_photos' => false ) )->get_data()['inbox_key'];
wp_set_current_user( 0 ); $payload['inbox_key'] = $off_key;
verifyFileFlow( fileFlowCall( 'prepare', $payload )->get_data()['code'] === 'import_inbox_photos', 'file mode cannot enable preservation without seller consent' );
$schema = fileFlowCall( 'schema', array(), 'GET' )->get_data();
verifyFileFlow( $schema['inbox']['photo_upload']['supported'] && $schema['inbox']['photo_upload']['prepare_field']['photo_delivery_mode'] === 'original_files', 'live schema contract advertises original-file preparation and upload' );
echo "$passed original-file native checks passed.\n";
