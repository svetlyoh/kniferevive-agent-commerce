<?php
/**
 * Plugin Name: KnifeRevive Merchant Sync
 * Description: Synchronizes eligible WooCommerce products with Google Merchant API v1.
 * Version: 0.8.6
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * Author: KnifeRevive
 * Text Domain: kniferevive-merchant-sync
 */

defined( 'ABSPATH' ) || exit;

define( 'KREV_MERCHANT_SYNC_VERSION', '0.8.6' );
define( 'KREV_MERCHANT_SYNC_FILE', __FILE__ );
define( 'KREV_MERCHANT_SYNC_DIR', plugin_dir_path( __FILE__ ) );

$krev_autoloader = KREV_MERCHANT_SYNC_DIR . 'vendor/autoload.php';
if ( is_readable( $krev_autoloader ) ) {
	require_once $krev_autoloader;
}

require_once KREV_MERCHANT_SYNC_DIR . 'includes/class-krev-merchant-config.php';
require_once KREV_MERCHANT_SYNC_DIR . 'includes/class-krev-logger.php';
require_once KREV_MERCHANT_SYNC_DIR . 'includes/class-krev-google-auth.php';
require_once KREV_MERCHANT_SYNC_DIR . 'includes/class-krev-merchant-api.php';
require_once KREV_MERCHANT_SYNC_DIR . 'includes/class-krev-google-category-mapper.php';
require_once KREV_MERCHANT_SYNC_DIR . 'includes/class-krev-product-mapper.php';
require_once KREV_MERCHANT_SYNC_DIR . 'includes/class-krev-product-sync.php';
require_once KREV_MERCHANT_SYNC_DIR . 'includes/class-krev-sync-queue.php';
require_once KREV_MERCHANT_SYNC_DIR . 'includes/class-krev-admin.php';
require_once KREV_MERCHANT_SYNC_DIR . 'includes/class-krev-cli.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		KREV_Sync_Queue::init();
		KREV_Google_Category_Mapper::init();
		KREV_Admin::init();
		KREV_CLI::init();
		add_filter( 'kniferevive_listlab_duplicate_runtime_meta_keys', static function ( $keys ) { return array_merge( (array) $keys, array( KREV_Product_Mapper::OFFER_META, KREV_Product_Sync::META_INPUT_NAME, KREV_Product_Sync::META_PRODUCT_NAME, KREV_Product_Sync::META_DATASOURCE_NAME, KREV_Product_Sync::META_LAST_SYNC_AT, KREV_Product_Sync::META_LAST_SYNC_HASH, KREV_Product_Sync::META_LAST_ERROR, KREV_Product_Sync::META_API_STATUS, KREV_Product_Sync::META_GOOGLE_STATUS, KREV_Product_Sync::META_GOOGLE_CATEGORY ) ); } );
	},
	20
);

register_activation_hook( __FILE__, array( 'KREV_Sync_Queue', 'activate' ) );
register_activation_hook( __FILE__, array( 'KREV_Google_Category_Mapper', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'KREV_Sync_Queue', 'deactivate' ) );
