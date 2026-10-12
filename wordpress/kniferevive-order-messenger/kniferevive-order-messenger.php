<?php
/**
 * Plugin Name: KnifeRevive Order Messenger
 * Description: Private seller-to-buyer order emails with screenshots, configurable presentation and delivery audit.
 * Version: 1.0.2
 * Requires at least: 6.6
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce,dokan-lite,kniferevive-seller-orders
 * WC requires at least: 9.0
 * WC tested up to: 11.1
 * Text Domain: kniferevive-order-messenger
 */
defined('ABSPATH') || exit;
define('KR_OM_FILE',__FILE__);
define('KR_OM_PATH',plugin_dir_path(__FILE__));
define('KR_OM_VERSION','1.0.2');
foreach (['class-kr-order-message-admin','class-kr-order-permissions','class-kr-order-context','class-kr-order-message-attachments','class-kr-order-message-log','class-kr-order-message-rest','class-kr-order-messenger'] as $file) require_once KR_OM_PATH.'includes/'.$file.'.php';
add_action('before_woocommerce_init',static function () {
    if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables',KR_OM_FILE,true);
});
register_activation_hook(__FILE__,['KR_Order_Messenger_Log','install']);
register_deactivation_hook(__FILE__,static function () { wp_clear_scheduled_hook('kr_om_cleanup'); wp_clear_scheduled_hook('kr_om_sweep'); });
add_action('plugins_loaded',['KR_Order_Messenger','boot'],40);
