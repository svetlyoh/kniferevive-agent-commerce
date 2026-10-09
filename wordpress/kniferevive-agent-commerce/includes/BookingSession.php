<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Separate native checkout storage; a route tag is never an authorization grant. */
final class BookingSession {
    public static string $id='';
    public static function boot(): void {
        add_filter('woocommerce_session_handler',static function($handler){
            try{return self::resolve($handler);}catch(Fault $e){
                // An unauthorized tagged payment must never fall back to the
                // ordinary cart, especially when logged-in nonces are shared.
                wp_send_json(['result'=>'failure','messages'=>'Open your private booking link again before paying. Your other cart has not been changed.'],403);
            }
        },100);
        add_filter('woocommerce_persistent_cart_enabled',static fn($enabled)=>WC()->session instanceof BookingNativeSession?false:$enabled,100);
    }
    public static function resolve(string $handler): string {
        $private=($_GET['krev_agent']??'')==='booking';$tagged=isset($_GET['krev_booking_checkout']) && (!empty($_GET['wc-ajax']) || wp_doing_ajax());
        if(!$private && !$tagged)return $handler;
        $id=(string)wp_unslash($private?($_GET['booking']??''):$_GET['krev_booking_checkout']);
        if(!Domain::validId($id)){if($tagged)Domain::fail('AUTHORIZATION_REQUIRED','Use the private booking checkout.',403);return $handler;}
        try{Booking::get($id,Api::bookingOwner($id));}catch(\Throwable $e){if($tagged)Domain::fail('AUTHORIZATION_REQUIRED','Use the private booking checkout.',403);return $handler;}
        self::$id=$id;require_once __DIR__.'/BookingNativeSession.php';return BookingNativeSession::class;
    }
    public static function ajaxUrl(string $url,string $id): string {
        // Keep Woo's %%endpoint%% placeholder unchanged.
        return $url.(str_contains($url,'?')?'&':'?').'krev_booking_checkout='.rawurlencode($id);
    }
}
