<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Billing can change; the consent-bound service address never follows billing edits. */
final class BookingCheckoutFields {
    public static function boot(): void {
        add_filter('woocommerce_cart_needs_shipping_address',static fn($needed)=>self::active()?false:$needed,1000);
        add_filter('woocommerce_checkout_posted_data',[self::class,'posted'],1000);
        add_action('woocommerce_checkout_update_order_review',[self::class,'review'],5);
        add_action('woocommerce_before_calculate_totals',static function(){
            if(!self::active() || !Domain::validId((string)WC()->session->get('krev_listing_intent')))return;
            try{[$booking,$owner,$intent]=self::original();foreach($intent['data']['context']['shipping'] as $field=>$value)WC()->customer->{'set_shipping_'.$field}($value);}catch(\Throwable $e){/* Original order guard rejects an invalid or expired binding. */}
        },1);
        add_action('woocommerce_checkout_before_customer_details',static function(){if(self::active())echo '<details class="krev-billing-details" open><summary>Billing address · different from your trip address?</summary><p class="krev-fine-print">Update billing here. Your approved pickup or return address stays the same.</p>';});
        add_action('woocommerce_checkout_after_customer_details',static function(){if(self::active())echo '</details>';});
    }
    private static function active(): bool {return WC()->session instanceof BookingNativeSession && Domain::validId((string)WC()->session->get('krev_booking_id'));}
    private static function original(): array {
        $booking=Store::get((string)WC()->session->get('krev_booking_id'),'booking');
        $owner=Api::bookingOwner($booking['id']);Booking::get($booking['id'],$owner);
        return [$booking,$owner,ListingCheckout::bookingCart($booking)];
    }
    public static function posted(array $data): array {
        if(!self::active())return $data;
        [$booking,$owner,$intent]=self::original();
        foreach($intent['data']['context']['shipping'] as $field=>$value)$data['shipping_'.$field]=$value;
        $data['ship_to_different_address']=false;
        return $data;
    }
    /** WooCommerce has already checked its native update-review nonce. */
    public static function review(string $serialized): void {
        if(!self::active())return;
        try{
            [$booking,$owner,$intent]=self::original();parse_str($serialized,$fields);
            $billing=[];foreach(['address_1','address_2','city','state','postcode','country'] as $field)$billing[$field]=(string)($fields['billing_'.$field]??'');
            $context=['billing'=>$billing,'shipping'=>$intent['data']['context']['shipping'],'email'=>(string)($fields['billing_email']??''),'payment_method'=>'stripe'];
            // Fail incomplete billing gracefully while the user types; never publish a partial quote.
            foreach(['address_1','city','state','postcode','country'] as $field)if(!$billing[$field])return;
            if(!is_email($context['email']))return;
            ListingCheckout::resumeBookingCart($booking['id'],$owner,$context);
            // Core's subsequent shipping/customer update must retain the fixed service address.
            foreach(['country','state','postcode','city','address_1','address_2'] as $field)$_POST['s_'.($field==='address_1'?'address':$field)]=$context['shipping'][$field];
        }catch(\Throwable $e){wc_add_notice('Your saved KnifeRevive checkout needs review. Refresh this page; do not start another payment.','error');}
    }
}
