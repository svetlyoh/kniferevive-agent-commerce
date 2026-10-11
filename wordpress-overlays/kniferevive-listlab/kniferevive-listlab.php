<?php
/**
 * Plugin Name: KnifeRevive ListLab
 * Description: KnifeRevive's mobile-first seller listing manager and editor.
 * Version: 1.8.2
 * Requires Plugins: woocommerce
 * Text Domain: kniferevive-listlab
 */

defined( 'ABSPATH' ) || exit;

define( 'KREV_LISTLAB_VERSION', '1.8.2' );
define( 'KREV_LISTLAB_FILE', __FILE__ );
define( 'KREV_LISTLAB_PATH', plugin_dir_path( __FILE__ ) );
define( 'KREV_LISTLAB_URL', plugin_dir_url( __FILE__ ) );

require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-dokan-adapter.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-policies.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-attributes.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-categories.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-timed-offers.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-whatnot-demo.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-product-reader.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-views.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-product-writer.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-image-handler.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-video-handler.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-rest.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-endpoint.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-account-dashboard.php';
require_once KREV_LISTLAB_PATH . 'includes/class-krev-listlab-plugin.php';

register_activation_hook( __FILE__, array( 'KREV_ListLab_Plugin', 'activate' ) );
add_action( 'plugins_loaded', array( 'KREV_ListLab_Plugin', 'boot' ) );
