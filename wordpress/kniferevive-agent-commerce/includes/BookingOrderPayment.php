<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Recovery of one existing native order. Never creates a replacement or charges. */
final class BookingOrderPayment {
    public static function boot(): void {
        add_action('wp_loaded',static function(){
            try{self::validateRoute();}catch(\Throwable $e){wp_die('Open your original private booking payment link again. No payment was started.','Private booking payment',['response'=>403]);}
        },1);
        add_filter('woocommerce_get_checkout_payment_url',static function($url,$order){
            if(!(WC()->session instanceof BookingNativeSession) || $order->get_meta('_krev_service_booking')!==BookingSession::$id)return $url;
            return add_query_arg(['krev_booking_checkout'=>BookingSession::$id,'krev_order_payment'=>$order->get_id()],$url);
        },100,2);
        foreach(['wc_stripe_params','wc_stripe_upe_params','wc_stripe_express_checkout_params'] as $filter)add_filter($filter,static function($params){
            if(!isset($_GET['krev_order_payment']) || !(WC()->session instanceof BookingNativeSession))return $params;
            foreach(['ajaxurl','ajax_url','wp_ajax_url'] as $key)if(isset($params[$key]))$params[$key]=BookingSession::ajaxUrl($params[$key],BookingSession::$id);
            return $params;
        },100);
    }
    public static function validateRoute(): void {
        if(!isset($_GET['krev_order_payment']))return;
        $id=(string)wp_unslash($_GET['krev_booking_checkout']??'');$booking=Booking::get($id,Api::bookingOwner($id));$order=ListingCheckout::bookingOrder($booking);
        if(!(WC()->session instanceof BookingNativeSession) || BookingSession::$id!==$id || !$order
            || $order->get_id()!==(int)$_GET['krev_order_payment'] || !hash_equals($order->get_order_key(),(string)wp_unslash($_GET['key']??'')))Domain::fail('AUTHORIZATION_REQUIRED','Use the original order payment link.',403);
    }
    public static function url(\WC_Order $order,string $bookingId): string {
        return add_query_arg(['krev_booking_checkout'=>$bookingId,'krev_order_payment'=>$order->get_id()],$order->get_checkout_payment_url());
    }
    public static function prepare(array $booking,string $owner): string {
        Booking::get($booking['id'],$owner);$order=ListingCheckout::bookingOrder($booking);
        if(!(WC()->session instanceof BookingNativeSession) || BookingSession::$id!==$booking['id'])Domain::fail('FORBIDDEN','Open your private booking link before continuing.',403);
        if(!$order || !$order->needs_payment() || !$order->has_status(['pending','failed']) || $order->get_date_paid() || $order->get_transaction_id())Domain::fail('PAYMENT_UNRESOLVED','Check the original payment with KnifeRevive before paying again.',409);
        // An expired hold may be renewed only after Stripe confirms this very
        // order's original intent is still awaiting buyer approval.
        $hasHold=Booking::activePrepaymentHold($booking['id']);
        $intentId=class_exists('WC_Stripe_Order_Helper')?\WC_Stripe_Order_Helper::get_instance()->get_stripe_intent_id($order):null;
        if($intentId || !$hasHold){
            if(!class_exists('WC_Stripe_API'))Domain::fail('PAYMENT_UNRESOLVED','The original payment needs merchant review.',409);
            if(!is_string($intentId) || !preg_match('/^pi_[a-zA-Z0-9]+$/D',$intentId))Domain::fail('PAYMENT_UNRESOLVED','The original payment needs merchant review.',409);
            $intent=\WC_Stripe_API::retrieve('payment_intents/'.$intentId);
            if(is_wp_error($intent) || ($intent->id??'')!==$intentId || !in_array($intent->status??'',['requires_payment_method','requires_confirmation','requires_action'],true)
                || (int)($intent->amount??-1)!==Domain::cents(wc_format_decimal($order->get_total(),2)) || ($intent->currency??'')!==strtolower($order->get_currency())
                || (string)($intent->metadata->order_id??'')!==(string)$order->get_order_number())Domain::fail('PAYMENT_UNRESOLVED','Check the original Cash App/card payment with KnifeRevive before retrying.',409);
            if(!$hasHold)Store::lock('booking:'.$booking['id'],static function()use($booking,$order,$intent){Booking::renewExistingOrderPaymentHold(Store::get($booking['id'],'booking'),$order,$intent);});
        }
        Booking::paymentSelection($booking['id']);
        // Repair only the missing recipient. Preserve existing names and every
        // amount, item, tax, service destination and processor intent.
        if(!trim($order->get_shipping_first_name().' '.$order->get_shipping_last_name())){
            $names=BookingCheckoutFields::recipient($booking);
            if(!$names['first_name'])Domain::fail('INVALID_REQUEST','Your booking needs a recipient name. Contact KnifeRevive.');
            $order->set_shipping_first_name($names['first_name']);$order->set_shipping_last_name($names['last_name']);$order->save();
        }
        $row=Store::get($booking['data']['listing_intent'],'listing');
        WC()->session->set('krev_booking_id',$booking['id']);WC()->session->set('krev_listing_intent',$row['id']);WC()->session->set('krev_listing_owner',$row['owner']);
        WC()->session->set_customer_session_cookie(true);WC()->session->save_data();
        return self::url($order,$booking['id']);
    }
    public static function render(array $booking,string $owner,\WC_Order $order): never {
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
            $post=wp_unslash($_POST);ListingFrontend::authorizeForm($booking['data']['checkout_owner']??$booking['owner'],$booking['data']['listing_intent'],$post);
            if(($post['action']??'')!=='resume_order')Domain::fail('AUTHORIZATION_REQUIRED','Use the saved order payment button.',403);
            wp_safe_redirect(self::prepare($booking,$owner),303);exit;
        }
        PrivateBrand::headers();PrivateBrand::start('Continue your KnifeRevive payment');
        wc_print_notices();
        echo '<h1>Pick up where you left off</h1><p>KnifeRevive order '.esc_html((string)$order->get_order_number()).'</p><p>Your knives, day and pickup &amp; return plan are saved. This continues the same order.</p><p>'.esc_html(BookingLifecycle::handoffLabel($booking['data']['input'])).'</p><p><strong>Total: '.wp_kses_post($order->get_formatted_order_total()).'</strong></p>';
        if($order->needs_payment() && $order->has_status(['pending','failed'])){
            echo '<form method="post">';ListingFrontend::hidden($booking['data']['checkout_owner']??$booking['owner'],$booking['data']['listing_intent']);
            echo '<input type="hidden" name="action" value="resume_order"><button class="krev-primary-action">Continue payment for this order</button></form><p>If your wallet already charged you, check the original payment before retrying.</p>';
        }else echo '<p>This order is '.esc_html(wc_get_order_status_name($order->get_status())).'. No new payment was started.</p>';
        echo '<p><a href="'.esc_url(add_query_arg(['krev_agent'=>'booking','booking'=>$booking['id']],home_url('/'))).'">Back to my booking</a></p>';PrivateBrand::end();exit;
    }
}
