<?php
/**
 * Plugin Name: KnifeRevive Returns
 * Description: Private buyer returns and Return Desk, using Seller Orders and ShipKit's existing authorities.
 * Version: 0.1.1-rc1
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce, kniferevive-seller-orders, kniferevive-shipkit
 */
defined('ABSPATH') || exit;
define('KR_RETURNS_FILE', __FILE__);
define('KR_RETURNS_VERSION', '0.1.1-rc1');

/** WP's dependency headers do not guarantee include order. In particular,
 * ReturnScope implements interfaces declared by the ShipKit plugin file. */
function krev_returns_load_components(): bool {
    if (!interface_exists('KnifeRevive\\ShipKit\\Scope') || !interface_exists('KnifeRevive\\ShipKit\\ScopeAccess')) return false;
    foreach (['Support','Contract','Policy','Access','Store','Cases','Files','Guest','Labels','Refunds','Inspection','Tracking','Money','Jobs','Rest','Screens','Plugin'] as $name) {
        require_once __DIR__.'/includes/'.$name.'.php';
    }
    return true;
}
add_action('before_woocommerce_init', static function () {
    if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', KR_RETURNS_FILE, true);
    }
});
add_action('plugins_loaded', static function () {
    if (!krev_returns_load_components()) {
        add_action('admin_notices', static function () { echo '<div class="notice notice-error"><p>KnifeRevive Returns needs the coordinated ShipKit update active. Returns routes remain unavailable.</p></div>'; });
        return;
    }
    \KnifeRevive\Returns\Plugin::boot();
}, 110);
register_activation_hook(__FILE__, static function () {
    if (!krev_returns_load_components()) wp_die('Activate the coordinated ShipKit dependency before KnifeRevive Returns.');
    \KnifeRevive\Returns\Plugin::activate();
});
