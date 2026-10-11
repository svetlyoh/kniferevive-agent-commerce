<?php

defined( 'ABSPATH' ) || exit( 1 );

$tests = array();
$assert = static function ( $condition, $name ) use ( &$tests ) {
	$tests[] = array( 'name' => $name, 'pass' => (bool) $condition );
};

$assert( '15990000' === KREV_Product_Mapper::money( '15.99', 'USD' )['amountMicros'], '15.99 USD converts to 15990000 micros' );
$assert( '69000000' === KREV_Product_Mapper::money( '69.00', 'USD' )['amountMicros'], '69.00 USD converts to 69000000 micros' );
$assert( 'IN_STOCK' === KREV_Product_Mapper::map_availability( 'instock', 1 ), 'In-stock availability' );
$assert( 'OUT_OF_STOCK' === KREV_Product_Mapper::map_availability( 'outofstock', 0 ), 'Out-of-stock availability' );
$assert( KREV_Product_Mapper::is_valid_gtin( '036000291452' ), 'Known valid GTIN passes' );
$assert( ! KREV_Product_Mapper::is_valid_gtin( '036000291453' ), 'Invalid GTIN fails' );
$assert( ! KREV_Product_Mapper::is_safe_public_https_url( 'http://localhost/product' ), 'Localhost URL rejected' );
$assert( ! KREV_Product_Mapper::is_safe_public_https_url( 'https://store.test/product' ), '.test URL rejected' );
$assert( KREV_Product_Mapper::is_safe_public_https_url( 'https://kniferevive.com/product', true ), 'Production URL accepted' );
$assert( 'Woo title' === KREV_Product_Mapper::select_merchant_title( 'Woo title', 'Knife subtitle', true ), 'Knife product uses the canonical WooCommerce title' );
$assert( 'Woo title' === KREV_Product_Mapper::select_merchant_title( 'Woo title', 'Art subtitle', false ), 'Non-knife product keeps the WooCommerce title' );
$assert( 'Woo title' === KREV_Product_Mapper::select_merchant_title( 'Woo title', '', true ), 'Knife product without a subtitle falls back to the WooCommerce title' );
$assert( KREV_Product_Mapper::is_knife_category_term( (object) array( 'name' => "Chef's Knife", 'slug' => 'chefs-knife' ) ), 'Knife category is recognized' );
$assert( KREV_Product_Mapper::is_knife_category_term( (object) array( 'name' => 'Cleaver', 'slug' => 'cleaver' ) ), 'Cleaver category is recognized as a knife category' );
$assert( ! KREV_Product_Mapper::is_knife_category_term( (object) array( 'name' => 'Knife Sharpening', 'slug' => 'knife-sharpening' ) ), 'Knife Sharpening is excluded from knife product titles' );
$assert( ! KREV_Product_Mapper::is_knife_category_term( (object) array( 'name' => 'Fine Art', 'slug' => 'art' ) ), 'Non-knife category is excluded from knife product titles' );
$assert( KREV_Product_Mapper::is_excluded_category_term( (object) array( 'name' => 'Knife Sharpening', 'slug' => 'knife-sharpening' ) ), 'Knife Sharpening category is excluded from Merchant sync' );
$assert( ! KREV_Product_Mapper::is_excluded_category_term( (object) array( 'name' => "Chef's Knife", 'slug' => 'chefs-knife' ) ), 'Physical knife category remains eligible for Merchant sync' );
$assert( ! KREV_Product_Mapper::is_confirmed_product_detail_value( 'Not specified' ), 'Local fallback is omitted from Merchant Product Details' );
$assert( ! KREV_Product_Mapper::is_confirmed_product_detail_value( 'unknown' ), 'Unknown marker is omitted from Merchant Product Details' );
$assert( KREV_Product_Mapper::is_confirmed_product_detail_value( '8 in' ), 'Confirmed specification is eligible for Merchant Product Details' );
$assert( KREV_Product_Mapper::is_generic_brand( 'Technology & AI Systems' ), 'Generic Tech collection is not used as Merchant brand' );
$assert( ! KREV_Product_Mapper::is_generic_brand( 'Dell' ), 'Real manufacturer remains eligible as Merchant brand' );

$brand_method = new ReflectionMethod( KREV_Product_Mapper::class, 'resolve_brand' );
$mapper       = new KREV_Product_Mapper();
$zwilling     = wc_get_product( 1684 );
$wusthof      = wc_get_product( 527 );
$assert( $zwilling && 'Zwilling' === $brand_method->invoke( $mapper, $zwilling ), 'Zwilling series resolves to the manufacturer for Merchant' );
$assert( $wusthof && 'Wüsthof' === $brand_method->invoke( $mapper, $wusthof ), 'Wüsthof series resolves to the manufacturer for Merchant' );

$secret_sample = KREV_Logger::sanitize( array( 'Authorization' => 'Bearer abc.def', 'private_key' => 'secret', 'access_token' => 'token' ) );
$assert( '[REDACTED]' === $secret_sample['Authorization'] && '[REDACTED]' === $secret_sample['private_key'] && '[REDACTED]' === $secret_sample['access_token'], 'Secrets are redacted' );

$previous_credentials = get_option( KREV_Merchant_Config::CREDENTIALS_OPTION, null );
$credential_fixture   = array(
	'type'         => 'service_account',
	'client_email' => KREV_Merchant_Config::EXPECTED_SERVICE_ACCOUNT,
	'private_key'  => "-----BEGIN PRIVATE KEY-----\ntest-only-key\n-----END PRIVATE KEY-----\n",
);
$stored_result        = KREV_Merchant_Config::store_credentials_json( wp_json_encode( $credential_fixture ) );
$encrypted_fixture    = get_option( KREV_Merchant_Config::CREDENTIALS_OPTION, false );
$assert( true === $stored_result && is_array( $encrypted_fixture ), 'Credential JSON is encrypted and stored' );
$assert( false === strpos( serialize( $encrypted_fixture ), 'test-only-key' ), 'Stored credential ciphertext contains no plaintext private key' );

$decrypt_method = new ReflectionMethod( KREV_Merchant_Config::class, 'decrypt' );
$decrypted_json = $decrypt_method->invoke( null, $encrypted_fixture );
$decrypted_data = is_wp_error( $decrypted_json ) ? array() : json_decode( $decrypted_json, true );
$assert( KREV_Merchant_Config::EXPECTED_SERVICE_ACCOUNT === ( $decrypted_data['client_email'] ?? '' ), 'Encrypted credential round trip succeeds' );

$tampered_fixture               = $encrypted_fixture;
$tampered_fixture['ciphertext'] = substr( $tampered_fixture['ciphertext'], 0, -2 ) . 'AA';
$assert( is_wp_error( $decrypt_method->invoke( null, $tampered_fixture ) ), 'Tampered credential ciphertext is rejected' );
$assert( is_wp_error( KREV_Merchant_Config::store_credentials_json( '{"type":"service_account"}' ) ), 'Incomplete credential JSON is rejected' );

if ( null === $previous_credentials ) {
	delete_option( KREV_Merchant_Config::CREDENTIALS_OPTION );
} else {
	update_option( KREV_Merchant_Config::CREDENTIALS_OPTION, $previous_credentials, false );
}

$fixture_ids = get_posts(
	array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_key'       => KREV_Product_Mapper::OFFER_META,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	)
);
$product = $fixture_ids ? wc_get_product( reset( $fixture_ids ) ) : false;
if ( $product ) {
	$mapper = new KREV_Product_Mapper();
	$first  = $mapper->resolve_identity( $product, false );
	$second = $mapper->resolve_identity( $product, false );
	$assert( ! is_wp_error( $first ) && $first['offer_id'] === $second['offer_id'], 'Offer ID remains stable' );
	$payload = $mapper->map( $product, false );
	$assert( ! is_wp_error( $payload ) && '0.875' === (string) $payload['productAttributes']['shippingWeight']['value'], 'WooCommerce shipping weight maps correctly' );
	$assert( ! is_wp_error( $payload ) && false === $payload['productAttributes']['identifierExists'], 'No identifiers are fabricated' );
} else {
	$assert( false, 'A synced integration fixture product is available' );
}

$failed = array_values( array_filter( $tests, static fn( $test ) => ! $test['pass'] ) );
echo wp_json_encode( array( 'passed' => count( $tests ) - count( $failed ), 'failed' => count( $failed ), 'tests' => $tests ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . PHP_EOL;

if ( $failed ) {
	exit( 1 );
}
