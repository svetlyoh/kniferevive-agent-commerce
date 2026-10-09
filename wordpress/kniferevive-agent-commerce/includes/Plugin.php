<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;
final class Plugin {
    public static function boot(): void {
        if (!class_exists('WooCommerce')) return;
        BookingSession::boot();ListingCheckout::boot();BookingCheckoutFields::boot();StorefrontBooking::boot();
        BookingSeller::boot();BookingOrderBridge::boot();BookingOutbox::boot();BookingLifecycle::boot();
        add_action('woocommerce_cart_calculate_fees',[Booking::class,'nativeFees'],25);
        add_action('rest_api_init',[Api::class,'register']);
        add_action('template_redirect',[Frontend::class,'render'],0);
        add_action('admin_menu',static function () { add_submenu_page('woocommerce','Agent Commerce','Agent Commerce','manage_woocommerce','krev-agent-commerce',[Settings::class,'page']); });
        add_filter('woocommerce_payment_gateways',static function ($gateways) { require_once __DIR__.'/Gateway.php'; $gateways[]=Gateway::class; return $gateways; });
        add_filter('cron_schedules',static function ($s) { $s['krev_agent_minute']=['interval'=>60,'display'=>'Agent commerce reconciliation']; return $s; });
        if (!wp_next_scheduled('krev_agent_reconcile')) wp_schedule_event(time()+60,'krev_agent_minute','krev_agent_reconcile');
        add_action('krev_agent_reconcile',[self::class,'jobs']);
    }
    public static function jobs(): void {
        BookingOutbox::sweep();
        foreach (Store::attemptsForJobs(30) as $row) {
            try {
                if (in_array($row['data']['payment_state'],['creating','unknown'],true) && !$row['data']['provider_id']) Payments::start($row['id']);
                Payments::reconcile($row['id']);
            } catch (\Throwable $e) { /* Retained row remains visible to the operator; never log customer/provider payloads. */ }
        }
        Store::pruneEphemeral();
    }
}
