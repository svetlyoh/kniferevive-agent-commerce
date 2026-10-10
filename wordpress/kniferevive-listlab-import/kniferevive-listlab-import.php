<?php
/**
 * Plugin Name: KnifeRevive Marketplace Imports
 * Description: Prepare Facebook Marketplace items from Muse, apply category pricing and complete seller drafts in ListLab.
 * Version: 1.0.2
 * Requires Plugins: woocommerce, kniferevive-listlab
 * License: MIT-0
 */
defined( 'ABSPATH' ) || exit;
define( 'KREV_IMPORT_VERSION', '1.0.2' );
define( 'KREV_IMPORT_PATH', plugin_dir_path( __FILE__ ) );
define( 'KREV_IMPORT_URL', plugin_dir_url( __FILE__ ) );
require_once KREV_IMPORT_PATH . 'includes/class-pricing.php';
require_once KREV_IMPORT_PATH . 'includes/class-package-defaults.php';
require_once KREV_IMPORT_PATH . 'includes/class-imports.php';
add_action( 'before_woocommerce_init', static function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
} );
add_action( 'plugins_loaded', array( 'KREV_Marketplace_Imports', 'boot' ), 40 );
register_deactivation_hook( __FILE__, static function() { wp_clear_scheduled_hook( 'krev_import_cleanup' ); } );
