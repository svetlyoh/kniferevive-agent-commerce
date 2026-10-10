<?php
require __DIR__ . '/sandbox-bootstrap.php';
if ( DB_NAME !== 'krev_agent_sandbox' || $wpdb->prefix !== 'krev_sandbox_' ) exit( 1 );
require_once ABSPATH . 'wp-content/plugins/kniferevive-listlab/kniferevive-listlab.php';
require_once dirname( __DIR__ ) . '/wordpress/kniferevive-listlab-import/kniferevive-listlab-import.php';
KREV_Marketplace_Imports::boot();
$seller = wp_insert_user( array( 'user_login' => 'import-ui-' . wp_generate_uuid4(), 'user_pass' => wp_generate_password(), 'role' => 'import_test_seller' ) );
update_option( 'krev_import_ui_seller', $seller, false );
$category = wp_insert_term( 'Technology · synthetic import', 'product_cat' )['term_id'];
update_option( KREV_Import_Pricing::OPTION, KREV_Import_Pricing::defaults(), false );
$r = new WP_REST_Request( 'POST' ); $r->set_header( 'content-type', 'application/json' ); $r->set_body( wp_json_encode( array( 'source_url' => 'https://www.facebook.com/marketplace/item/987654321/', 'source_price' => '25.00', 'currency' => 'USD', 'category_id' => $category, 'title' => 'Synthetic Marketplace item', 'description' => "Used computer stand.\nCopied item facts from the referenced listing.\nNo real product or transaction.", 'quantity' => '1', 'image_urls' => array() ) ) );
$result = KREV_Marketplace_Imports::prepare( $r );
file_put_contents( dirname( __DIR__ ) . '/.runtime/marketplace-import-ui.json', wp_json_encode( $result->get_data() ) );
echo "Synthetic import UI fixture ready.\n";
