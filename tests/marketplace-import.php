<?php
/** Native seller CRUD against the existing fenced synthetic database; no real outbound traffic. */
ob_start(); set_exception_handler( static function( Throwable $e ) { fwrite( STDERR, 'FAIL: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine() . "\n" ); exit( 1 ); } );
require __DIR__ . '/sandbox-bootstrap.php';
if ( DB_NAME !== 'krev_agent_sandbox' || DB_HOST !== KREV_TEST_DB_HOST || $wpdb->prefix !== 'krev_sandbox_' ) throw new RuntimeException( 'Sandbox fence failed.' );
require_once ABSPATH . 'wp-content/plugins/kniferevive-listlab/kniferevive-listlab.php';
require_once ABSPATH . 'wp-content/plugins/kniferevive-product-attributes/includes/class-krev-pa-config.php';
require_once ABSPATH . 'wp-content/plugins/kniferevive-product-attributes/includes/class-krev-pa-condition-resolver.php';
require_once dirname( __DIR__ ) . '/wordpress/kniferevive-listlab-import/kniferevive-listlab-import.php';
KREV_Marketplace_Imports::boot();
$passed = 0;
function checkImport( $ok, $label ) { global $passed; if ( ! $ok ) throw new RuntimeException( $label ); ++$passed; echo "PASS: $label\n"; }
function importCall( $action, $data, $method = 'POST' ) { $request = new WP_REST_Request( $method, '/kniferevive-listlab-import/v1/' . $action ); $request->set_header( 'content-type', 'application/json' ); if ( $method === 'GET' ) foreach ( $data as $k => $v ) $request->set_param( $k, $v ); else $request->set_body( wp_json_encode( $data ) ); return rest_do_request( $request ); }
function importToken( $response ) { parse_str( wp_parse_url( $response->get_data()['review_url'], PHP_URL_FRAGMENT ), $fragment ); return $fragment['import']; }
function importError( $response, $code ) { checkImport( $response->get_data()['code'] === $code, $code ); }
delete_option( KREV_Import_Pricing::OPTION );
delete_transient( 'krev_import_rate_' . hash_hmac( 'sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown', wp_salt( 'nonce' ) ) );
$root = wp_insert_term( 'Synthetic import parent ' . wp_generate_uuid4(), 'product_cat' )['term_id'];
$child = wp_insert_term( 'Synthetic import child ' . wp_generate_uuid4(), 'product_cat', array( 'parent' => $root ) )['term_id'];
$sibling = wp_insert_term( 'Synthetic import sibling ' . wp_generate_uuid4(), 'product_cat' )['term_id'];
checkImport( KREV_Import_Pricing::quote( '25.00', $child )['regular_price'] === '30.00', '$25 becomes $30 plus separate shipping' );
$settings = KREV_Import_Pricing::defaults(); $settings['categories'][ $root ] = array( 'percent' => '10', 'fixed' => '5', 'minimum' => '0', 'rounding' => '1' ); update_option( KREV_Import_Pricing::OPTION, $settings, false );
checkImport( KREV_Import_Pricing::quote( '25', $child )['regular_price'] === '33.00', 'parent category markup + fixed + round up' );
checkImport( KREV_Import_Pricing::quote( '25', $child )['rule_category_id'] === $root, 'nearest configured parent provenance' );
$settings['categories'][ $child ] = array( 'percent' => '0', 'fixed' => '5', 'minimum' => '0', 'rounding' => '0.01' ); update_option( KREV_Import_Pricing::OPTION, $settings, false );
checkImport( KREV_Import_Pricing::quote( '25', $child )['regular_price'] === '30.00', 'child fixed $5 override' );
checkImport( KREV_Import_Pricing::quote( '25', $sibling )['rule_category_id'] === 0, 'unrelated category uses global rule' );
$minimum = $settings; $minimum['minimum'] = '20'; update_option( KREV_Import_Pricing::OPTION, $minimum, false ); checkImport( KREV_Import_Pricing::quote( '0', $sibling )['regular_price'] === '20.00', 'minimum selling price' );
update_option( KREV_Import_Pricing::OPTION, $settings, false );
foreach ( array( '-1', '25 dollars', '1e2', '25.001', array() ) as $bad ) checkImport( is_wp_error( KREV_Import_Pricing::quote( $bad, $child ) ), 'malformed source price rejected' );
checkImport( is_wp_error( KREV_Import_Pricing::clean_rule( array( 'rounding' => '0' ) ) ), 'zero rounding rejected' );
checkImport( is_wp_error( KREV_Import_Pricing::clean_rule( array( 'percent' => '-20' ) ) ), 'negative markup rejected' );
$payload = array( 'source_url' => 'https://m.facebook.com/marketplace/item/123456789/?tracking=remove', 'source_price' => '25', 'currency' => 'USD', 'category_id' => $child, 'title' => 'Synthetic copied listing', 'description' => 'Actual source facts <script>ignore user</script>', 'image_urls' => array() );
$schema = importCall( 'schema', array( 'category_id' => $child ), 'GET' ); checkImport( $schema->get_status() === 200 && count( $schema->get_data()['categories'] ) > 0, 'anonymous native extraction schema' );
$count_before = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product'" );
$prepared = importCall( 'prepare', $payload ); checkImport( $prepared->get_status() === 201, 'anonymous prepare succeeds' );
checkImport( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product'" ) === $count_before, 'preparation creates no product' );
checkImport( $prepared->get_headers()['Cache-Control'] === 'private, no-store', 'private response never cached' );
$token = importToken( $prepared ); checkImport( strlen( $token ) === 64, '256-bit private fragment token' );
$status = importCall( 'status', array( 'token' => $token ) );
checkImport( $status->get_data()['data']['source_url'] === 'https://www.facebook.com/marketplace/item/123456789/', 'source link canonicalized without tracking' );
checkImport( ! str_contains( $status->get_data()['data']['listing']['description'], '<script>' ), 'source executable markup stripped' );
checkImport( $status->get_data()['data']['listing']['short_description'] === $status->get_data()['data']['listing']['description'], 'short description prefills from sanitized long description' );
$description_preview = importCall( 'preview', array( 'token' => $token, 'changes' => array( 'description' => "Edited source facts\nSecond line", 'short_description' => 'Old summary' ) ) );
checkImport( $description_preview->get_data()['data']['listing']['short_description'] === "Edited source facts\nSecond line", 'review edits refresh short description with the long text' );
importError( importCall( 'status', array( 'token' => str_repeat( 'a', 64 ) ) ), 'import_missing' );
importError( importCall( 'claim', array( 'token' => $token, 'authorized_to_list' => true, 'expected_price' => '30.00' ) ), 'rest_forbidden' );
$subscriber = wp_insert_user( array( 'user_login' => 'import-subscriber-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password(), 'role' => 'subscriber' ) ); wp_set_current_user( $subscriber );
checkImport( ! KREV_Marketplace_Imports::seller_permission(), 'ordinary shopper cannot create seller drafts' );
wp_set_current_user( 0 );
foreach ( array( 'https://facebook.com.evil/marketplace/item/1/', 'https://facebook.com@evil/marketplace/item/1/', 'http://facebook.com/marketplace/item/1/', 'https://facebook.com/marketplace/', 'https://127.0.0.1/marketplace/item/1/' ) as $url ) checkImport( is_wp_error( KREV_Marketplace_Imports::source_url( $url ) ), 'noncanonical source rejected' );
foreach ( array( 'http://a.fbcdn.net/x', 'https://a.fbcdn.net.evil/x', 'https://127.0.0.1/x', 'https://a.fbcdn.net:8443/x', 'https://user:pass@a.fbcdn.net/x' ) as $url ) checkImport( ! KREV_Marketplace_Imports::image_url( $url ), 'unsafe image URL rejected' );
checkImport( (bool) KREV_Marketplace_Imports::image_url( 'https://scontent.test.fbcdn.net/photo.jpg?token=source' ), 'real Facebook CDN host family accepted' );
$bad = $payload; $bad['author'] = 1; importError( importCall( 'prepare', $bad ), 'import_fields' );
$bad = $payload; $bad['currency'] = 'EUR'; importError( importCall( 'prepare', $bad ), 'import_currency' );
$bad = $payload; $bad['quantity'] = '-1'; importError( importCall( 'prepare', $bad ), 'import_integer' );
$bad = $payload; $bad['attributes'] = array( 'made_up_taxonomy' => array( 'bogus' ) ); importError( importCall( 'prepare', $bad ), 'import_attributes' );
$bad = $payload; $bad['image_urls'] = array_fill( 0, 11, 'https://scontent.test.fbcdn.net/photo.jpg' ); importError( importCall( 'prepare', $bad ), 'import_images' );
add_role( 'import_test_seller', 'Synthetic seller', array( 'read' => true, 'edit_products' => true, 'publish_products' => true, 'upload_files' => true ) );
$seller = wp_insert_user( array( 'user_login' => 'import-seller-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password(), 'role' => 'import_test_seller' ) ); wp_set_current_user( $seller );
checkImport( KREV_Marketplace_Imports::seller_permission(), 'enabled native seller can claim' );
importError( importCall( 'claim', array( 'token' => $token, 'expected_price' => '30.00' ) ), 'import_rights' );
importError( importCall( 'claim', array( 'token' => $token, 'expected_price' => '29.00', 'authorized_to_list' => true ) ), 'import_price_changed' );
$foreign_image = wp_insert_attachment( array( 'post_title' => 'foreign synthetic image', 'post_mime_type' => 'image/png', 'post_author' => $subscriber ), false );
importError( importCall( 'claim', array( 'token' => $token, 'expected_price' => '30.00', 'authorized_to_list' => true, 'changes' => array( 'featured_image_id' => $foreign_image ) ) ), 'import_images' );
$claimed = importCall( 'claim', array( 'token' => $token, 'expected_price' => '30.00', 'authorized_to_list' => true, 'changes' => array( 'quantity' => '2' ) ) );
checkImport( $claimed->get_status() === 201, 'native ListLab writer creates draft' );
$id = $claimed->get_data()['listing']['id']; $product = wc_get_product( $id );
checkImport( $product->get_status() === 'draft', 'incomplete listing never published' );
checkImport( $product->get_regular_price() === '30.00' && $product->get_stock_quantity() === 2, 'calculated price and supplied quantity preserved' );
checkImport( (int) get_post_field( 'post_author', $id ) === $seller, 'draft is seller-owned' );
checkImport( $product->get_meta( '_krev_import_source_price' ) === '25.00', 'original Facebook price retained separately' );
checkImport( $product->get_short_description() === $product->get_description() && $product->get_description() !== '', 'native ListLab draft retains identical descriptions' );
checkImport( $claimed->get_data()['listing']['edit_url'] !== '' && $claimed->get_data()['listing']['view_url'] === '', 'native completion link without false public URL' );
$product->set_name( 'Seller edited title' ); $product->set_short_description( 'Seller edited summary' ); $product->save();
$again = importCall( 'claim', array( 'token' => $token, 'expected_price' => '30.00', 'authorized_to_list' => true, 'changes' => array( 'title' => 'Do not overwrite' ) ) );
checkImport( $again->get_data()['listing']['id'] === $id && $again->get_data()['listing']['title'] === 'Seller edited title', 'retry returns same product and preserves seller edits' );
checkImport( wc_get_product( $id )->get_short_description() === 'Seller edited summary', 'retries preserve independently edited seller summary' );
wp_set_current_user( 0 ); $second_ticket = importCall( 'prepare', $payload ); $second_token = importToken( $second_ticket ); wp_set_current_user( $seller );
$repeat_source = importCall( 'claim', array( 'token' => $second_token, 'expected_price' => '30.00', 'authorized_to_list' => true ) );
checkImport( $repeat_source->get_data()['listing']['id'] === $id, 'different ticket for same seller/source returns original draft' );
wp_set_current_user( $subscriber ); importError( importCall( 'status', array( 'token' => $token ) ), 'import_owner' );
wp_set_current_user( $seller );
$key = 'krev_import_' . hash( 'sha256', $token ); $record = get_option( $key ); $record['expires'] = time() - 1; update_option( $key, $record, false );
importError( importCall( 'status', array( 'token' => $token ) ), 'import_missing' ); KREV_Marketplace_Imports::cleanup(); checkImport( ! get_option( $key ), 'expired preparation removed' );
// Controlled native image sideload; no request reaches the network.
$fixture = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j5XcAAAAASUVORK5CYII=' );
$fake = static function( $pre, $args, $url ) use ( $fixture ) { if ( $url !== 'https://scontent.test.fbcdn.net/fixture.png' ) return $pre; checkImport( $args['redirection'] === 0 && $args['limit_response_size'] === KREV_Marketplace_Imports::MAX_IMAGE + 1 && $args['cookies'] === array(), 'bounded fetch without redirects or seller cookies' ); file_put_contents( $args['filename'], $fixture ); return array( 'headers' => array(), 'response' => array( 'code' => 200 ), 'body' => '' ); };
add_filter( 'pre_http_request', $fake, PHP_INT_MAX, 3 );
add_filter( 'upload_dir', static function( $u ) { $u['basedir'] = dirname( __DIR__ ) . '/.runtime/import-media'; $u['baseurl'] = WP_HOME . '/fixture-media'; $u['path'] = $u['basedir']; $u['url'] = $u['baseurl']; $u['subdir'] = ''; return $u; } );
$image = KREV_Marketplace_Imports::copy_image( 'https://scontent.test.fbcdn.net/fixture.png', 'Synthetic image' ); checkImport( ! is_wp_error( $image ) && KREV_ListLab_Image_Handler::authorized_image( $image ), 'copied photo is native seller-owned media' );
remove_filter( 'pre_http_request', $fake, PHP_INT_MAX );
checkImport( is_wp_error( KREV_Marketplace_Imports::copy_image( 'https://scontent.test.fbcdn.net/missing.png', 'Synthetic image' ) ), 'unavailable photo reported without false image success' );
wp_set_current_user( 0 ); $image_payload = $payload; $image_payload['source_url'] = 'https://www.facebook.com/marketplace/item/999999998/'; $image_payload['image_urls'] = array( 'https://scontent.test.fbcdn.net/missing.png' );
$image_prepared = importCall( 'prepare', $image_payload ); $image_token = importToken( $image_prepared ); wp_set_current_user( $seller );
$failed_image_claim = importCall( 'claim', array( 'token' => $image_token, 'expected_price' => '30.00', 'authorized_to_list' => true ) );
checkImport( $failed_image_claim->get_status() === 201 && count( $failed_image_claim->get_data()['warnings'] ) === 1 && $failed_image_claim->get_data()['listing']['featured_image'] === null, 'failed photo produces explicit warning and incomplete draft' );
$retained_warning = importCall( 'status', array( 'token' => $image_token ) ); checkImport( count( $retained_warning->get_data()['warnings'] ) === 1, 'photo warning persists after page reload' );
wp_set_current_user( 0 ); $price_prepared = importCall( 'prepare', $payload ); $price_token = importToken( $price_prepared );
$price_preview = importCall( 'preview', array( 'token' => $price_token, 'changes' => array( 'category_id' => $root ) ) );
checkImport( $price_preview->get_data()['price']['regular_price'] === '33.00', 'review category switch recalculates using the new rule' );
$price_key = 'krev_import_' . hash( 'sha256', $price_token ); add_option( $price_key . '_lock', time(), '', false ); wp_set_current_user( $seller );
importError( importCall( 'claim', array( 'token' => $price_token, 'expected_price' => '30.00', 'authorized_to_list' => true ) ), 'import_busy' ); delete_option( $price_key . '_lock' );
checkImport( KREV_Marketplace_Imports::login_return( 'elsewhere', add_query_arg( 'krev_listlab_import', 'review', home_url( '/' ) ), wp_get_current_user() ) === add_query_arg( 'krev_listlab_import', 'review', home_url( '/' ) ), 'login returns to original review' );
$technology = get_term_by( 'slug', 'technology', 'product_cat' );
if ( ! $technology ) { $made = wp_insert_term( 'Technology import attributes', 'product_cat', array( 'slug' => 'technology' ) ); $technology = get_term( $made['term_id'], 'product_cat' ); }
foreach ( array( 'condition', 'processor' ) as $slug ) {
    $attribute_id = wc_attribute_taxonomy_id_by_name( $slug );
    if ( ! $attribute_id ) wc_create_attribute( array( 'name' => $slug, 'slug' => $slug, 'type' => 'select' ) );
    if ( ! taxonomy_exists( 'pa_' . $slug ) ) register_taxonomy( 'pa_' . $slug, 'product' );
}
$attribute_payload = $payload; $attribute_payload['source_url'] = 'https://www.facebook.com/marketplace/item/999999997/'; $attribute_payload['category_id'] = $technology->term_id; $attribute_payload['attributes'] = array( 'pa_condition' => array( 'Used' ), 'pa_processor' => array( 'Intel Core i5' ) );
wp_set_current_user( 0 ); $attribute_prepared = importCall( 'prepare', $attribute_payload ); checkImport( $attribute_prepared->get_status() === 201, 'native category attribute payload accepted' );
$attribute_token = importToken( $attribute_prepared ); wp_set_current_user( $seller );
$attribute_claim = importCall( 'claim', array( 'token' => $attribute_token, 'expected_price' => '30.00', 'authorized_to_list' => true ) ); checkImport( $attribute_claim->get_status() === 201, 'native condition/specification draft created' );
$attribute_product = wc_get_product( $attribute_claim->get_data()['listing']['id'] );
checkImport( in_array( 'Intel Core i5', wp_get_object_terms( $attribute_product->get_id(), 'pa_processor', array( 'fields' => 'names' ) ), true ), 'copied CPU stored as real native attribute' );
checkImport( $attribute_product->get_attributes()['pa_processor']->get_visible(), 'copied specification visible for storefront/feed projection' );
checkImport( $attribute_product->get_stock_quantity() === 0 && $attribute_product->get_status() === 'draft', 'unknown inventory creates no available published stock' );
$knife = get_term_by( 'slug', 'chefs-knife', 'product_cat' );
if ( ! $knife ) { $made = wp_insert_term( 'Synthetic chefs knife', 'product_cat', array( 'slug' => 'chefs-knife' ) ); $knife = get_term( $made['term_id'], 'product_cat' ); }
if ( ! taxonomy_exists( 'product_brand' ) ) register_taxonomy( 'product_brand', 'product' );
foreach ( array( 'blade-length', 'edge-type' ) as $slug ) {
    if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) wc_create_attribute( array( 'name' => $slug, 'slug' => $slug, 'type' => 'select' ) );
    if ( ! taxonomy_exists( 'pa_' . $slug ) ) register_taxonomy( 'pa_' . $slug, 'product' );
}
$knife_payload = $payload; $knife_payload['source_url'] = 'https://www.facebook.com/marketplace/item/999999996/'; $knife_payload['category_id'] = $knife->term_id;
$knife_payload['description'] = 'Wusthof chef knife, 8 inches blade, straight edge.'; $knife_payload['short_description'] = 'Stale summary';
$knife_payload['attributes'] = array( 'product_brand' => array( 'Wusthof' ), 'pa_blade-length' => array( '8 inches' ), 'pa_edge-type' => array( 'Straight' ) );
wp_set_current_user( 0 ); $knife_prepared = importCall( 'prepare', $knife_payload ); checkImport( $knife_prepared->get_status() === 201, 'brand and knife specification payload accepted' );
$knife_token = importToken( $knife_prepared ); wp_set_current_user( $seller );
$knife_claim = importCall( 'claim', array( 'token' => $knife_token, 'expected_price' => '30.00', 'authorized_to_list' => true, 'changes' => array( 'description' => "Wusthof chef knife\n8 inches blade, straight edge." ) ) );
checkImport( $knife_claim->get_status() === 201, 'native knife draft created with extracted attributes' );
$knife_product = wc_get_product( $knife_claim->get_data()['listing']['id'] );
checkImport( $knife_product->get_short_description() === "Wusthof chef knife\n8 inches blade, straight edge." && $knife_product->get_description() === $knife_product->get_short_description(), 'final review description prefills both native fields' );
checkImport( in_array( 'Wusthof', wp_get_object_terms( $knife_product->get_id(), 'product_brand', array( 'fields' => 'names' ) ), true ), 'extracted maker stored in native brand taxonomy' );
checkImport( in_array( '8 inches', wp_get_object_terms( $knife_product->get_id(), 'pa_blade-length', array( 'fields' => 'names' ) ), true ) && $knife_product->get_length() === '', 'blade length preserves units and leaves package length unknown' );
checkImport( in_array( 'Straight', wp_get_object_terms( $knife_product->get_id(), 'pa_edge-type', array( 'fields' => 'names' ) ), true ) && in_array( $knife->term_id, $knife_product->get_category_ids(), true ), 'knife type and edge style stored in native category and attribute' );
echo "$passed marketplace import assertions passed.\n"; ob_end_flush();
