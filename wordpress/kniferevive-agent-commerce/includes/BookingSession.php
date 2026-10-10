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
        $private=($_GET['krev_agent']??'')==='booking';
        $orderPay=isset($_GET['krev_booking_checkout'],$_GET['krev_order_payment'],$_GET['pay_for_order'],$_GET['key']);
        $tagged=isset($_GET['krev_booking_checkout']) && (!empty($_GET['wc-ajax']) || wp_doing_ajax() || $orderPay);
        if(!$private && !$tagged)return $handler;
        $id=(string)wp_unslash($private?($_GET['booking']??''):$_GET['krev_booking_checkout']);
        if(!Domain::validId($id)){if($tagged)Domain::fail('AUTHORIZATION_REQUIRED','Use the private booking checkout.',403);return $handler;}
        try{
            Booking::get($id,Api::bookingOwner($id));
            // WooCommerce has not registered order storage yet. The original
            // order/key binding is checked at wp_loaded, before native pay_action.
        }catch(\Throwable $e){if($tagged)Domain::fail('AUTHORIZATION_REQUIRED','Use the private booking checkout.',403);return $handler;}
        self::$id=$id;require_once __DIR__.'/BookingNativeSession.php';return BookingNativeSession::class;
    }
    public static function ajaxUrl(string $url,string $id): string {
        // Keep Woo's %%endpoint%% placeholder unchanged.
        return $url.(str_contains($url,'?')?'&':'?').'krev_booking_checkout='.rawurlencode($id);
    }
    /** An authorized details POST prepares only this booking's independent cart. */
    public static function prepare(array $booking,string $owner,array $context): void {
        Booking::get($booking['id'],$owner);
        if(!WC()->session || !WC()->cart || !WC()->customer)wc_load_cart();
        if(!(WC()->session instanceof BookingNativeSession) || self::$id!==$booking['id']){
            WC()->session->save_data();
            // Detach the ordinary cart's persistence callbacks before switching runtimes.
            // Its saved quantities, pending orders and authenticated cookie remain intact.
            global $wp_filter;
            foreach($wp_filter as $hook=>$filter)foreach($filter->callbacks as $priority=>$callbacks)foreach($callbacks as $callback){
                $fn=$callback['function'];if(is_array($fn) && ($fn[0] instanceof \WC_Cart_Session || $fn[0] instanceof \WC_Cart))remove_action($hook,$fn,$priority);
            }
            self::$id=$booking['id'];require_once __DIR__.'/BookingNativeSession.php';
            WC()->session=new BookingNativeSession();WC()->session->init();WC()->cart=new \WC_Cart();WC()->customer=new \WC_Customer(get_current_user_id(),true);
        }
        ListingCheckout::resumeBookingCart($booking['id'],$owner,$context);
    }
}
