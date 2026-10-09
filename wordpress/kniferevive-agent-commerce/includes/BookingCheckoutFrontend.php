<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Native WooCommerce checkout on the private booking URL; gateways own payment fields. */
final class BookingCheckoutFrontend {
    public static function render(): never {
        $id=(string)wp_unslash($_GET['booking']??'');
        try{
            if(!is_ssl() && wp_get_environment_type()!=='local')Domain::fail('FORBIDDEN','Secure checkout is required.',403);
            $owner=Api::bookingOwner($id);$booking=Booking::get($id,$owner);
            if(!WC()->session || !WC()->cart || !WC()->customer)wc_load_cart();
            $intent=ListingCheckout::bookingCart($booking);
        }catch(\Throwable $e){wp_die(esc_html($e instanceof Fault?$e->getMessage():'Review the original booking before paying.'),'Booking payment unavailable',['response'=>409,'back_link'=>true]);}
        nocache_headers();header('Referrer-Policy: no-referrer');header('X-Robots-Tag: noindex, nofollow, noarchive');header('X-Content-Type-Options: nosniff');
        // This route uses the installed checkout's normal script/frame policy. The restricted
        // discovery form's self-only CSP would prevent Stripe tokenization and 3DS.
        add_filter('woocommerce_is_checkout','__return_true');
        add_filter('woocommerce_available_payment_gateways',static function($gateways){return isset($gateways['stripe'])?['stripe'=>$gateways['stripe']]:[];},1000);
        $name=$booking['data']['input']['customer']['name']??'';$parts=explode(' ',trim($name),2);
        add_filter('woocommerce_checkout_get_value',static function($value,$field)use($parts){
            if($value!==null && $value!=='')return $value;
            return match($field){'billing_first_name','shipping_first_name'=>$parts[0]??'','billing_last_name','shipping_last_name'=>$parts[1]??'',default=>$value};
        },10,2);
        wp_enqueue_style('krev-booking-checkout',plugin_dir_url(FILE).'assets/storefront.css',[],VERSION);
        echo '<!doctype html><html lang="'.esc_attr(get_bloginfo('language')?:'en').'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pay for knife sharpening | KnifeRevive</title>';wp_head();
        echo '</head><body class="krev-private woocommerce woocommerce-checkout"><header class="krev-header"><a href="'.esc_url(home_url('/')).'">'.(PrivateBrand::logo()?:'KnifeRevive').'</a></header><main><h1>Pay for knife sharpening</h1><p>Your payment does not confirm the appointment or pickup address. KnifeRevive reviews the request. If it cannot accept your booking, all unperformed services and trips are fully refundable.</p><p><a href="'.esc_url(add_query_arg(['krev_agent'=>'booking','booking'=>$id],home_url('/'))).'">Booking details and refund status</a> · <a href="'.esc_url(Settings::get()['booking_policy_url']).'">Cancellation and refund terms</a></p>';
        wc_print_notices();echo do_shortcode('[woocommerce_checkout]');PrivateBrand::support();echo '</main>';wp_footer();echo '</body></html>';exit;
    }
}
