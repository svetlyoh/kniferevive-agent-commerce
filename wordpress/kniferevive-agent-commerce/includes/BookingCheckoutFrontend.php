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
            if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
                $post=wp_unslash($_POST);$original=Store::get($booking['data']['listing_intent']??'','listing');
                ListingFrontend::authorizeForm($original['owner'],$original['id'],$post);
                if(($post['action']??'')!=='resume' || ($post['accept']??'')!=='yes')Domain::fail('AUTHORIZATION_REQUIRED','Confirm your saved details before continuing.',403);
                ListingCheckout::resumeBookingCart($id,$owner);
                wp_safe_redirect(add_query_arg(['krev_agent'=>'booking','booking'=>$id,'payment'=>'1'],home_url('/')),303);exit;
            }
            $intent=ListingCheckout::bookingCart($booking);
        }catch(\Throwable $e){
            PrivateBrand::headers();PrivateBrand::start('Continue your sharpening booking','data-booking-attach="'.esc_url(rest_url(Api::NS.'/bookings/'.$id.'/attach')).'" data-attach="'.esc_url(rest_url(Api::NS.'/sessions/attach')).'"');
            echo '<h1>Let’s finish your booking</h1>';
            if(isset($booking)){
                echo '<p>Booking reference: '.esc_html($id).'</p><p>Your knife journey: '.esc_html(BookingLifecycle::handoffLabel($booking['data']['input'])).'</p>';
                if($e instanceof Fault && !in_array($e->codeName,['CART_CONFLICT','INTENT_EXPIRED','SLOT_UNAVAILABLE'],true))echo '<p role="alert">'.esc_html($e->getMessage()).'</p>';
                elseif(($_SERVER['REQUEST_METHOD']??'GET')==='POST')echo '<p role="alert">'.esc_html($e instanceof Fault?$e->getMessage():'Your original checkout needs merchant review.').'</p>';
                if(Domain::validId($booking['data']['listing_intent']??'')){
                    try{
                        $original=Store::get($booking['data']['listing_intent'],'listing');
                        if(($original['data']['selection']['booking_id']??'')===$id && $original['owner']===($booking['data']['checkout_owner']??$booking['owner']))ListingFrontend::bookingReview($original,$original['owner']);
                    }catch(\Throwable $reviewError){echo '<p>Your saved checkout needs merchant review. Nothing was replaced or charged.</p>';}
                }
            }else echo '<p>Open the private booking link in your confirmation email to continue. A booking reference alone can’t unlock your personal details.</p>';
            echo '<p><a href="'.esc_url(add_query_arg(['krev_agent'=>'booking','booking'=>$id],home_url('/'))).'">Open my booking details</a></p>';PrivateBrand::end(true);exit;
        }
        nocache_headers();header('Referrer-Policy: no-referrer');header('X-Robots-Tag: noindex, nofollow, noarchive');header('X-Content-Type-Options: nosniff');
        // This route uses the installed checkout's normal script/frame policy. The restricted
        // discovery form's self-only CSP would prevent Stripe tokenization and 3DS.
        add_filter('woocommerce_is_checkout','__return_true');
        // Stripe owns its iframe. Use its documented appearance filter, never card DOM access.
        add_filter('woocommerce_get_script_data',static function($params,$handle)use($id){if($handle==='wc-checkout' && is_array($params) && isset($params['wc_ajax_url']))$params['wc_ajax_url']=BookingSession::ajaxUrl($params['wc_ajax_url'],$id);return $params;},100,2);
        foreach(['wc_stripe_params','wc_stripe_express_checkout_params'] as $filter)add_filter($filter,static function($params)use($id){foreach(['ajaxurl','ajax_url','wp_ajax_url'] as $key)if(isset($params[$key]))$params[$key]=BookingSession::ajaxUrl($params[$key],$id);return $params;},100);
        add_filter('wc_stripe_upe_params',static function($params)use($id){
            foreach(['ajaxurl','ajax_url','wp_ajax_url'] as $key)if(isset($params[$key]))$params[$key]=BookingSession::ajaxUrl($params[$key],$id);
            $params['appearance']=(object)['theme'=>'stripe','labels'=>'above','variables'=>(object)[
                'fontFamily'=>'system-ui, sans-serif','fontSizeBase'=>'17px','colorPrimary'=>'#174e37','colorText'=>'#202124',
                'colorBackground'=>'#ffffff','colorDanger'=>'#a12c22','borderRadius'=>'10px','spacingUnit'=>'5px'],
                'rules'=>(object)['.Input'=>(object)['padding'=>'14px','border'=>'1px solid #aebdb2','boxShadow'=>'none'],
                    '.Input:focus'=>(object)['borderColor'=>'#174e37','boxShadow'=>'0 0 0 3px rgba(23,78,55,0.14)'],
                    '.Label'=>(object)['fontWeight'=>'500','marginBottom'=>'8px']]];
            return $params;
        },100);
        add_filter('wc_stripe_elements_styling',static fn()=>['base'=>['fontFamily'=>'system-ui, sans-serif','fontSize'=>'17px','color'=>'#202124','::placeholder'=>['color'=>'#637168']],'invalid'=>['color'=>'#a12c22']],100);
        add_filter('woocommerce_available_payment_gateways',static function($gateways){return isset($gateways['stripe'])?['stripe'=>$gateways['stripe']]:[];},1000);
        $name=$booking['data']['input']['customer']['name']??'';$parts=explode(' ',trim($name),2);
        add_filter('woocommerce_checkout_get_value',static function($value,$field)use($parts){
            if($value!==null && $value!=='')return $value;
            return match($field){'billing_first_name','shipping_first_name'=>$parts[0]??'','billing_last_name','shipping_last_name'=>$parts[1]??'',default=>$value};
        },10,2);
        add_filter('woocommerce_checkout_get_value',static function($value,$field)use($intent){
            if(isset($_POST[$field]))return wc_clean(wp_unslash($_POST[$field]));
            if($value!==null && $value!=='')return $value;
            if($field==='billing_email')return $intent['data']['context']['email'];
            if(str_starts_with($field,'billing_'))return $intent['data']['context']['billing'][substr($field,8)]??$value;
            return $value;
        },20,2);
        wp_enqueue_style('krev-booking-checkout',plugin_dir_url(FILE).'assets/storefront.css',[],VERSION);
        wp_enqueue_style('krev-booking-payment',plugin_dir_url(FILE).'assets/booking-checkout.css',['krev-booking-checkout'],VERSION);
        echo '<!doctype html><html lang="'.esc_attr(get_bloginfo('language')?:'en').'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pay for knife sharpening | KnifeRevive</title>';wp_head();
        echo '</head><body class="krev-private krev-booking-payment woocommerce woocommerce-checkout"><a class="krev-skip" href="#booking-main">Skip to payment</a><header class="krev-header"><a href="'.esc_url(home_url('/')).'">'.(PrivateBrand::logo()?:'KnifeRevive').'</a></header><main id="booking-main"><p class="krev-eyebrow">Last step · KnifeRevive secure payment</p><h1>Let’s get your knives sharp</h1><ol class="krev-steps" aria-label="Booking screens"><li>1. Your knife game plan</li><li aria-current="step">2. Secure payment</li></ol><div class="krev-journey"><strong>Your pickup &amp; return plan</strong><p>'.esc_html(BookingLifecycle::handoffLabel($booking['data']['input'])).'</p><p>Trip fee: $'.esc_html(Domain::decimal(Booking::transportFee($booking))).' per order, plus configured tax.</p></div><p class="krev-fine-print">Confirm your total and pay. KnifeRevive confirms your day afterward. Unperformed services and trips receive a full refund if we cannot accept your request.</p><details><summary>Booking details &amp; cancellation terms</summary><p><a href="'.esc_url(add_query_arg(['krev_agent'=>'booking','booking'=>$id],home_url('/'))).'">Open my booking details</a> · <a href="'.esc_url(Settings::get()['booking_policy_url']).'">Cancellation and refund terms</a></p></details>';
        wc_print_notices();echo do_shortcode('[woocommerce_checkout]');PrivateBrand::support();echo '</main>';wp_footer();echo '</body></html>';exit;
    }
}
