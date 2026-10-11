<?php
/**
 * Plugin Name: KnifeRevive Product Attributes
 * Description: Category-scoped WooCommerce attributes, Dokan fields, and safe catalog backfill tools.
 * Version: 0.3.4
 * Author: KnifeRevive
 * Text Domain: kniferevive-product-attributes
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'KREV_PA_VERSION', '0.3.4' );
define( 'KREV_PA_FILE', __FILE__ );
define( 'KREV_PA_DIR', plugin_dir_path( __FILE__ ) );
define( 'KREV_PA_URL', plugin_dir_url( __FILE__ ) );

require_once KREV_PA_DIR . 'includes/class-krev-pa-config.php';
require_once KREV_PA_DIR . 'includes/class-krev-pa-tech-categories.php';
require_once KREV_PA_DIR . 'includes/class-krev-pa-condition-resolver.php';
require_once KREV_PA_DIR . 'includes/class-krev-pa-condition-migrator.php';
require_once KREV_PA_DIR . 'includes/class-krev-pa-setup.php';
require_once KREV_PA_DIR . 'includes/class-krev-pa-editor.php';
require_once KREV_PA_DIR . 'includes/class-krev-pa-migrator.php';
require_once KREV_PA_DIR . 'includes/class-krev-pa-admin.php';
require_once KREV_PA_DIR . 'includes/class-krev-pa-cli.php';

register_activation_hook( __FILE__, array( 'KREV_PA_Setup', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		KREV_PA_Setup::init();
		KREV_PA_Tech_Categories::init();
		KREV_PA_Condition_Resolver::init();
		KREV_PA_Editor::init();
		KREV_PA_Admin::init();
		KREV_PA_CLI::init();
	}
);
