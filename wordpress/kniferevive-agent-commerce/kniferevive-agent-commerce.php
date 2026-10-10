<?php
/**
 * Plugin Name: KnifeRevive Agent Commerce
 * Description: Structured shopping, consent-bound prepaid sharpening, and verified payment handoffs.
 * Version: 0.5.18
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce
 * License: GPL-2.0-or-later
 */
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;
const VERSION = '0.5.18';
const FILE = __FILE__;
foreach (['Domain', 'Settings', 'Store', 'StripeSetup', 'Commerce', 'Payments', 'ListingCheckout', 'BookingCoverage', 'BookingSession', 'Booking', 'BookingAuthorization', 'BookingEvents', 'BookingSeller', 'BookingOrderBridge', 'BookingLifecycle', 'BookingOutbox', 'PrivateBrand', 'GatewayDiagnostics', 'Api', 'ListingFrontend', 'BookingFrontend', 'BookingCheckoutFrontend', 'BookingCheckoutFields', 'BookingOrderPayment', 'StorefrontBooking', 'Frontend', 'Plugin'] as $class) {
    require_once __DIR__ . '/includes/' . $class . '.php';
}
register_activation_hook(__FILE__, [Store::class, 'install']);
register_deactivation_hook(__FILE__, static function () { wp_clear_scheduled_hook('krev_agent_reconcile'); });
add_action('before_woocommerce_init', static function () {
    if (class_exists('Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', FILE, true);
    }
});
add_action('plugins_loaded', [Plugin::class, 'boot'], 30);
