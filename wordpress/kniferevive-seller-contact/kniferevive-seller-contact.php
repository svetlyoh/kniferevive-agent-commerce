<?php
/**
 * Plugin Name: KnifeRevive Seller Contact
 * Description: One consent-aware buyer-to-seller contact experience using existing seller channel settings. No order messages or payments.
 * Version: 1.0.0
 * Requires at least: 6.7
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce, dokan-lite
 * Text Domain: kniferevive-seller-contact
 */
defined('ABSPATH') || exit;
define('KRC_FILE', __FILE__);
define('KRC_VERSION', '1.0.0');
foreach (['Config','Resolver','Renderer','Analytics','Settings','Plugin'] as $class) { require_once __DIR__.'/includes/'.$class.'.php'; }
register_activation_hook(__FILE__, ['KnifeRevive\SellerContact\Plugin','activate']);
register_deactivation_hook(__FILE__, ['KnifeRevive\SellerContact\Plugin','deactivate']);
add_action('plugins_loaded', ['KnifeRevive\SellerContact\Plugin','boot'], 90);
add_action('before_woocommerce_init', static function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/** Bridge functions are intentionally present while OFF: never revive duplicate legacy controls. */
function kr_seller_contact_markup(int $product_id, string $placement='card'): string {
    return \KnifeRevive\SellerContact\Renderer::product($product_id, $placement);
}
function kr_seller_store_contact_markup(int $seller_id): string {
    return \KnifeRevive\SellerContact\Renderer::store($seller_id);
}
