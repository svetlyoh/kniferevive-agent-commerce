<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;
final class ListingFrontend {
    public static function csrf(string $owner,string $id,int $bucket): string {return hash_hmac('sha256','listing:'.$owner.':'.$id.':'.$bucket,wp_salt('nonce'));}
    public static function authorizeForm(string $owner,string $id,array $post): void {
        if(!Api::firstPartyForm())Domain::fail('FORBIDDEN','Use the private first-party review form.',403);
        $csrf=(string)($post['csrf']??'');$bucket=intdiv(time(),600);
        if(!hash_equals(self::csrf($owner,$id,$bucket),$csrf) && !hash_equals(self::csrf($owner,$id,$bucket-1),$csrf))Domain::fail('FORBIDDEN','The review form expired.',403);
    }
    public static function render(): never {
        PrivateBrand::headers();
        $message='';$row=null;$owner='';$status=null;
        try {
            $owner=Api::owner();$id=(string)wp_unslash($_GET['intent']??'');if(!Domain::validId($id))Domain::fail('NOT_FOUND','Private intent unavailable.',404);
            $isStatus=($_GET['krev_agent']??'')==='listing-status';$row=ListingCheckout::get($id,$owner,$isStatus);
            if($isStatus)$status=ListingCheckout::status($id,$owner);
            elseif(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
                $post=wp_unslash($_POST);self::authorizeForm($owner,$id,$post);
                if(($post['action']??'')==='resume_order'){wp_safe_redirect(ListingCheckout::goodsPaymentUrl($id,$owner),303);exit;}
                if(($post['action']??'')==='continue'){
                    if(($post['accept']??'')!=='yes')Domain::fail('AUTHORIZATION_REQUIRED','Review and accept the current purchase and terms.',403);
                    $url=ListingCheckout::handoff($id,$owner,(string)($post['quote_hash']??''));
                    $target=parse_url($url);$origin=parse_url(home_url('/'));
                    foreach(['scheme','host','port'] as $part)if(($target[$part]??null)!==($origin[$part]??null))Domain::fail('FORBIDDEN','Native checkout must remain on the merchant origin.',403);
                    wp_safe_redirect($url,303);exit;
                }
                if(($post['action']??'')!=='quote')Domain::fail('INVALID_REQUEST','Unknown review action.');
                $context=['email'=>$post['email']??'','payment_method'=>$post['payment_method']??'','shipping_methods'=>array_values((array)($post['shipping_methods']??[]))];
                foreach(['billing','shipping'] as $kind)foreach(['address_1','address_2','city','state','postcode','country'] as $field)$context[$kind][$field]=(string)($post[$kind.'_'.$field]??'');
                $row=ListingCheckout::quote($id,$context,$owner,'buyer-listing-quote-'.Domain::digest($context).'-'.Domain::id());
                $message='Review the refreshed quote. No payment has been created.';
            }
        }catch(\Throwable $e){$message=$e instanceof Fault?$e->getMessage():'The original checkout needs review. Do not retry an uncertain payment.';}
        $assets=plugin_dir_url(FILE).'assets/';
        PrivateBrand::start('Review your listing checkout','data-attach="'.esc_url(rest_url(Api::NS.'/sessions/attach')).'"');
        echo '<h1>Review your listing checkout</h1><p>'.esc_html($message).'</p>';
        if($status){
            echo '<p>Payment: '.esc_html($status['payment_state']).'</p><p>Fulfillment: '.esc_html($status['fulfillment_state']).'</p><p>Appointment: '.esc_html($status['scheduling_state']).'</p><p>Payment verification: '.esc_html($status['payment_verification']).'</p><p>Use your normal WooCommerce receipt/account and the merchant for refund, delivery or scheduling support.</p>';
        }elseif($row){self::review($row,$owner);}
        echo '<p><a href="'.esc_url(wc_get_cart_url()).'">Review your existing cart</a></p><p><a href="'.esc_url(home_url('/shop/')).'">KnifeRevive listings</a></p>';PrivateBrand::end();exit;
    }
    public static function bookingReview(array $row,string $owner,array $values=[]): void {
        $d=$row['data'];$booking=Store::get($d['selection']['booking_id'],'booking');$input=$booking['data']['input'];
        if(!in_array($booking['data']['booking_state'],['awaiting_payment','requested','confirmed'],true)){echo '<p>This booking is closed. Any payment already made needs a separate merchant refund review.</p>';return;}
        if(!empty($d['order_id'])){
            echo '<section class="krev-payment-next"><h2>Your saved KnifeRevive order</h2><p>Your booking already has an order. Continue or check that same order here.</p><p><a class="krev-primary-link" href="'.esc_url(add_query_arg(['krev_agent'=>'booking','booking'=>$booking['id'],'payment'=>'1'],home_url('/'))).'">Open my original order payment</a></p></section>';return;
        }
        if($d['handoff_state']!=='review'){
            if($d['handoff_state']!=='cart_ready' || !empty($d['creation_started']) || !empty($d['order_id'])){echo '<p>An order may already exist. Check this booking’s original order or contact KnifeRevive before paying again.</p>';return;}
            try{if(!WC()->session || !WC()->cart || !WC()->customer)wc_load_cart();ListingCheckout::bookingCart($booking);$ready=true;}catch(\Throwable $e){$ready=false;}
            echo '<section class="krev-payment-next"><h2>Pick up where you left off</h2><p>Your booking is saved. No order has been linked yet. You’ll review the full total before approving payment.</p>';
            if($ready)echo '<p><a class="krev-primary-link" href="'.esc_url(add_query_arg(['krev_agent'=>'booking','booking'=>$booking['id'],'payment'=>'1'],home_url('/'))).'">Continue to secure payment</a></p>';
            else{
                echo '<form method="post" action="'.esc_url(add_query_arg(['krev_agent'=>'booking','booking'=>$booking['id'],'payment'=>'1'],home_url('/'))).'">';self::hidden($owner,$row['id']);
                echo '<input type="hidden" name="action" value="resume"><label class="krev-check"><input type="checkbox" name="accept" value="yes" required> Continue with my saved booking and billing details in this browser. I’ll review the total before paying.</label><button class="krev-primary-action">Continue to secure payment</button></form>';
            }
            echo '</section>';return;
        }
        $c=$d['context'];$address=$c['billing']??$input['pickup_address']??['state'=>'CA','country'=>'US','postcode'=>$input['postal_code']];
        $complete=true;foreach(['address_1','city','state','postcode'] as $field)if(!trim((string)($values['billing_'.$field]??$address[$field]??'')))$complete=false;
        echo '<section class="krev-payment-next"><h2>Next up: secure payment</h2><p>You’ll see the full total and choose your card or available wallet on the next screen. You only pay when you confirm there.</p><p><strong>Your knife journey:</strong> '.esc_html(BookingLifecycle::handoffLabel($input)).'. Merchant trips are charged separately; this is a local sharpening service, not parcel shipping.</p><form method="post" class="krev-payment-continue" action="'.esc_url(add_query_arg(['krev_agent'=>'booking','booking'=>$booking['id']],home_url('/'))).'">';
        self::hidden($owner,$row['id']);echo '<input type="hidden" name="action" value="pay"><details'.(!$complete || $values?' open':'').'><summary>Check or edit billing details</summary><p>Your pickup address is prefilled when available. Change it here if your billing address is different.</p><label for="payment-receipt-email">Receipt email</label><input id="payment-receipt-email" name="email" type="email" autocomplete="email" maxlength="254" required value="'.esc_attr($values['email']??$c['email']??$input['customer']['email']).'"><fieldset><legend>Billing address</legend>';
        foreach(['address_1'=>'Street address','address_2'=>'Apartment (optional)','city'=>'City','state'=>'State code','postcode'=>'ZIP code'] as $field=>$label){
            $name='billing_'.$field;$value=$values[$name]??$address[$field]??'';
            echo '<label for="payment-'.esc_attr($name).'">'.esc_html($label).'</label><input id="payment-'.esc_attr($name).'" name="'.esc_attr($name).'" autocomplete="billing '.esc_attr(['address_1'=>'address-line1','address_2'=>'address-line2','city'=>'address-level2','state'=>'address-level1','postcode'=>'postal-code'][$field]).'" maxlength="150" value="'.esc_attr($value).'"'.($field==='address_2'?'':' required').'>';
        }
        echo '<input type="hidden" name="billing_country" value="US"></fieldset></details><p><a href="'.esc_url(Settings::get()['booking_policy_url']).'">Cancellation and refund terms</a></p><label class="krev-check"><input type="checkbox" name="accept" value="yes" required> Continue with these booking and billing details. I’ll review the full total and approve payment on the secure payment screen.</label><button class="krev-primary-action">Continue to secure payment</button></form></section>';
    }
    /** Prepare the native total privately; final payment approval stays in WooCommerce. */
    public static function bookingPayment(string $id,string $owner,array $post): void {
        if(($post['accept']??'')!=='yes')Domain::fail('AUTHORIZATION_REQUIRED','Confirm your details before continuing to secure payment.',403);
        $row=Store::get($id,'listing');if($row['owner']!==$owner || empty($row['data']['selection']['booking_id']))Domain::fail('FORBIDDEN','Use your original booking payment screen.',403);
        if($row['data']['handoff_state']==='review'){
            $booking=Store::get($row['data']['selection']['booking_id'],'booking');$input=$booking['data']['input'];$billing=[];
            foreach(['address_1','address_2','city','state','postcode','country'] as $field)$billing[$field]=(string)($post['billing_'.$field]??'');
            $context=['email'=>$post['email']??'','billing'=>$billing,'shipping'=>$input['pickup_address']??$billing,'payment_method'=>'stripe'];
            ListingCheckout::resumeBookingCart($booking['id'],$owner,$context);return;
        }
        ListingCheckout::handoff($id,$owner,(string)($row['data']['quote']['quote_hash']??''));
    }
    private static function review(array $row,string $owner): void {
        $d=$row['data'];$q=$d['quote'];
        echo '<p>This prepares a buyer-completed WooCommerce checkout. No card has been charged. Inventory is not reserved by a quote.</p><ul>';
        foreach($d['selection']['items'] as $line){
            $p=ListingCheckout::product($line['product_id']);
            echo '<li><a href="'.esc_url($p['canonical_url']).'">'.esc_html($p['title']).'</a> × '.esc_html((string)$line['quantity']).' — Seller: '.esc_html($p['seller']['display_name']).'</li>';
            if(!isset($d['selection']['booking_id']) && $p['return_policy']) {
                $terms=$p['return_policy'];
                echo '<li>Return terms: <strong>'.esc_html($terms['label']).'</strong> '.esc_html($terms['description']).' <a href="'.esc_url($p['return_policy_url']).'">Full return policy</a>';
                if($terms['type'])echo ' · Type: '.esc_html($terms['type']);
                if($terms['days'])echo ' · Window: '.esc_html((string)$terms['days']).' days';
                if($terms['fee_terms'])echo ' · Fee terms: '.esc_html($terms['fee_terms']);
                echo '</li>';
            }
            if($p['fulfillment_type']==='service'){
                if(isset($d['selection']['booking_id']))echo '<li>Payment is for your sharpening request. The merchant must confirm the day and any trip address. <a href="'.esc_url(Settings::get()['booking_policy_url']).'">Sharpening cancellation and refund terms</a></li>';
                else echo '<li><strong>Sharpening payment does not book an appointment.</strong> '.esc_html($p['fulfillment_note']??'Merchant review required.').' <a href="'.esc_url($p['policy_url']??'').'">Seller service and fulfillment terms</a></li>';
            }
        }
        echo '</ul>';
        if(!empty($d['order_id']) && !isset($d['selection']['booking_id'])){
            $status=ListingCheckout::status($row['id'],$owner);echo '<p>Your original native order is saved. Payment: '.esc_html($status['payment_state']).'.</p>';
            if($status['payment_state']==='pending'){echo '<form method="post">';self::hidden($owner,$row['id']);echo '<input type="hidden" name="action" value="resume_order"><button>Continue payment for this order</button></form><p>If your wallet already charged you, check that original payment first.</p>';}
            echo '<p><a href="'.esc_url($status['status_url']).'">Check original purchase status</a></p>';return;
        }
        if($d['handoff_state']!=='review'){
            echo '<p>This intent has already been handed to its original checkout browser. Do not start another payment if its outcome is uncertain.</p><p><a href="'.esc_url(isset($d['selection']['booking_id'])?add_query_arg(['krev_agent'=>'booking','booking'=>$d['selection']['booking_id'],'payment'=>'1'],home_url('/')):wc_get_checkout_url()).'">Resume original native checkout</a></p><p><a href="'.esc_url(add_query_arg(['krev_agent'=>'listing-status','intent'=>$row['id']],home_url('/'))).'">Check original purchase status</a></p>';return;
        }
        if($q && !$q['estimate_only']){
            foreach($q['items'] as $line)echo '<p>'.esc_html($line['listing']['title'].' — $'.Domain::decimal($line['total_minor'])).'</p>';
            foreach($q['fees'] as $fee)echo '<p>'.esc_html($fee['name'].' — $'.Domain::decimal($fee['total_minor'])).'</p>';
            echo '<p>Discount: $'.esc_html(Domain::decimal($q['discount_minor'])).'</p><p>Shipping: $'.esc_html(Domain::decimal($q['shipping_minor'])).'</p><p>Taxes: $'.esc_html(Domain::decimal($q['tax_minor'])).'</p><p><strong>All-in total: $'.esc_html(Domain::decimal($q['total_minor'])).' USD</strong></p><p>Native payment method: '.esc_html($q['payment_method']).'. The gateway may require a login or independent payment approval.</p>';
            foreach($q['shipping_rates'] as $package)foreach($package['options'] as $rate)if($rate['id']===$package['selected'])echo '<p>Selected shipping: '.esc_html($rate['label']).'</p>';
            foreach($q['shipping_rates'] as $package)foreach($package['options'] as $rate)if($rate['id']===$package['selected'] && in_array($rate['method_id'],['local_pickup','pickup_location'],true)){
                if($rate['pickup_location'])echo '<p>Pickup location: '.esc_html($rate['pickup_location']).'</p>';
                if($rate['pickup_address'])echo '<p>Pickup address: '.esc_html($rate['pickup_address']).'</p>';
                if(!$rate['pickup_location'] && !$rate['pickup_address'])echo '<p>Confirm the pickup location shown by the native method or with your seller before paying.</p>';
            }
            echo '<p>Quote valid until '.esc_html(gmdate('c',$d['quote_expires'])).'. <a href="'.esc_url($q['policy_url']).'">Purchase terms</a> · <a href="'.esc_url($q['return_policy_url']).'">Return and refund policy</a></p><form method="post">';
            self::hidden($owner,$row['id']);echo '<input type="hidden" name="action" value="continue"><input type="hidden" name="quote_hash" value="'.esc_attr($q['quote_hash']).'"><label><input type="checkbox" name="accept" value="yes" required> I accept these items, seller, fulfillment, total and policies. Continue to native checkout; I will authorize payment there.</label><button>Continue to secure payment</button></form>';
        }else echo '<p><strong>Estimate only:</strong> enter complete addresses, choose the actual gateway and a native shipping rate where required. The total is unknown until those checks pass.</p>';
        echo '<h2>Prepare or update the quote</h2><form method="post">';self::hidden($owner,$row['id']);echo '<input type="hidden" name="action" value="quote">';
        $c=$d['context'];
        if(isset($d['selection']['booking_id'])){
            $booking=Store::get($d['selection']['booking_id'],'booking');
            if(in_array(BookingAuthorization::address($booking),['granted_for_order','merchant_review_required'],true)){
                $c['email']=$c['email']??$booking['data']['input']['customer']['email'];
                foreach(['billing','shipping'] as $kind)if(!isset($c[$kind]) && isset($booking['data']['input']['pickup_address']))$c[$kind]=$booking['data']['input']['pickup_address'];
            }
        }
        echo '<label>Receipt email<input name="email" type="email" maxlength="254" required value="'.esc_attr($c['email']??'').'"></label>';
        foreach(['billing'=>'Billing','shipping'=>'Delivery'] as $kind=>$label){echo '<fieldset><legend>'.esc_html($label.' address').'</legend>';
            foreach(['address_1'=>'Street address','address_2'=>'Apartment (optional)','city'=>'City','state'=>'State code','postcode'=>'Postal code','country'=>'Country code'] as $field=>$text){$value=$c[$kind][$field]??($field==='country'?'US':'');echo '<label>'.esc_html($label.' '.$text).'<input name="'.esc_attr($kind.'_'.$field).'" maxlength="150" '.($field==='address_2'?'':'required').' value="'.esc_attr($value).'"></label>';}
            echo '</fieldset>';
        }
        echo '<label>Native payment method<select name="payment_method" required>';
        $gateways=WC()->payment_gateways()->payment_gateways();foreach(Settings::get()['listing_gateway_ids'] as $id)if(isset($gateways[$id]) && $gateways[$id]->enabled==='yes')echo '<option value="'.esc_attr($id).'" '.selected($c['payment_method']??'',$id,false).'>'.esc_html(wp_strip_all_tags($gateways[$id]->get_title())).'</option>';
        echo '</select></label>';
        foreach($q['shipping_rates']??[] as $package){echo '<label>Shipping package '.esc_html((string)($package['package_index']+1)).'<select name="shipping_methods['.esc_attr((string)$package['package_index']).']" required>';
            echo '<option value="">Choose a native shipping method</option>';
            foreach($package['options'] as $option)echo '<option value="'.esc_attr($option['id']).'" '.selected($package['selected']??'',$option['id'],false).'>'.esc_html($option['label'].' — $'.Domain::decimal($option['cost_minor']+$option['tax_minor'])).'</option>';echo '</select></label>';
        }
        echo '<button>Calculate native quote</button></form>';
    }
    public static function hidden(string $owner,string $id): void {echo '<input type="hidden" name="csrf" value="'.esc_attr(self::csrf($owner,$id,intdiv(time(),600))).'">';}
}
