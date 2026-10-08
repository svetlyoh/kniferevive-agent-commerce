<?php
/**
 * Plugin Name: KnifeRevive Seller Orders
 * Description: Native WooCommerce seller fulfillment, marketplace shipping policies, merchandise returns, and knife-sharpening workflow.
 * Version: 1.1.5
 * Author: KnifeRevive
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * WC requires at least: 9.0
 * WC tested up to: 11.1
 * Text Domain: kniferevive-seller-orders
 */

defined( 'ABSPATH' ) || exit;

define( 'KREV_ORDERS_VERSION', '1.1.5' );
// UI-only update: retain the existing schema without scheduling an unnecessary backfill.
define( 'KREV_ORDERS_SCHEMA_VERSION', '1.1.4' );
define( 'KREV_ORDERS_FILE', __FILE__ );
define( 'KREV_ORDERS_PATH', plugin_dir_path( __FILE__ ) );
define( 'KREV_ORDERS_URL', plugin_dir_url( __FILE__ ) );

$krev_orders_files = array(
	'class-krev-orders-permissions.php',
	'class-krev-orders-query.php',
	'class-krev-shipping-policies.php',
	'class-krev-shipping-reader.php',
	'class-krev-shipment-tracking.php',
	'class-krev-returns.php',
	'class-krev-return-permissions.php',
	'class-krev-refunds.php',
	'class-krev-sharpening-assets.php',
	'class-krev-sharpening-orders.php',
	'class-krev-sharpening-workflow.php',
	'class-krev-orders-reader.php',
	'class-krev-orders-actions.php',
	'class-krev-orders-endpoints.php',
	'class-krev-orders-rest.php',
	'class-krev-orders-plugin.php',
);

foreach ( $krev_orders_files as $krev_orders_file ) {
	require_once KREV_ORDERS_PATH . 'includes/' . $krev_orders_file;
}

add_action( 'before_woocommerce_init', static function () {
	if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', KREV_ORDERS_FILE, true );
	}
} );

register_activation_hook( __FILE__, array( 'KREV_Orders_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'KREV_Orders_Plugin', 'deactivate' ) );
add_action( 'plugins_loaded', array( 'KREV_Orders_Plugin', 'boot' ), 20 );
