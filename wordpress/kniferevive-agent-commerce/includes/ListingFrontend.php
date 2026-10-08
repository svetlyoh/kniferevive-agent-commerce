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
        nocache_headers();header('Referrer-Policy: no-referrer');header('X-Robots-Tag: noindex, nofollow, noarchive');header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; connect-src 'self'; img-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
        $message='';$row=null;$owner='';$status=null;
        try {
            $owner=Api::owner();$id=(string)wp_unslash($_GET['intent']??'');if(!Domain::validId($id))Domain::fail('NOT_FOUND','Private intent unavailable.',404);
            $isStatus=($_GET['krev_agent']??'')==='listing-status';$row=ListingCheckout::get($id,$owner,$isStatus);
            if($isStatus)$status=ListingCheckout::status($id,$owner);
            elseif(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
                $post=wp_unslash($_POST);self::authorizeForm($owner,$id,$post);
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
                $message='Review the refreshed quote. No payment or appointment has been created.';
            }
        }catch(\Throwable $e){$message=$e instanceof Fault?$e->getMessage():'The original checkout needs review. Do not retry an uncertain payment.';}
        $assets=plugin_dir_url(FILE).'assets/';
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Review your listing checkout | KnifeRevive</title><link rel="stylesheet" href="'.esc_url($assets.'storefront.css').'"></head><body><main data-attach="'.esc_url(rest_url(Api::NS.'/sessions/attach')).'"><p>KnifeRevive</p><h1>Review your listing checkout</h1><div id="session-status" role="status"></div><p>'.esc_html($message).'</p>';
        if($status){
            echo '<p>Payment: '.esc_html($status['payment_state']).'</p><p>Fulfillment: '.esc_html($status['fulfillment_state']).'</p><p>Appointment: '.esc_html($status['scheduling_state']).'</p><p>Payment verification: '.esc_html($status['payment_verification']).'</p><p>Use your normal WooCommerce receipt/account and the merchant for refund, delivery or scheduling support.</p>';
        }elseif($row){self::review($row,$owner);}
        echo '<p><a href="'.esc_url(wc_get_cart_url()).'">Review your existing cart</a></p><p><a href="'.esc_url(home_url('/shop/')).'">KnifeRevive listings</a></p></main><script src="'.esc_url($assets.'storefront.js').'" defer></script></body></html>';exit;
    }
    private static function review(array $row,string $owner): void {
        $d=$row['data'];$q=$d['quote'];
        echo '<p>This prepares a buyer-completed WooCommerce checkout. No card has been charged. Inventory is not reserved by a quote.</p><ul>';
        foreach($d['selection']['items'] as $line){
            $p=ListingCheckout::product($line['product_id']);
            echo '<li><a href="'.esc_url($p['canonical_url']).'">'.esc_html($p['title']).'</a> × '.esc_html((string)$line['quantity']).' — Seller: '.esc_html($p['seller']['display_name']).'</li>';
            if($p['return_policy']) {
                $terms=$p['return_policy'];
                echo '<li>Return terms: <strong>'.esc_html($terms['label']).'</strong> '.esc_html($terms['description']).' <a href="'.esc_url($p['return_policy_url']).'">Full return policy</a>';
                if($terms['type'])echo ' · Type: '.esc_html($terms['type']);
                if($terms['days'])echo ' · Window: '.esc_html((string)$terms['days']).' days';
                if($terms['fee_terms'])echo ' · Fee terms: '.esc_html($terms['fee_terms']);
                echo '</li>';
            }
            if($p['fulfillment_type']==='service')echo '<li><strong>Sharpening payment does not book an appointment.</strong> '.esc_html($p['fulfillment_note']??'Merchant review required.').' <a href="'.esc_url($p['policy_url']??'').'">Seller service and fulfillment terms</a></li>';
        }
        echo '</ul>';
        if($d['handoff_state']!=='review'){
            echo '<p>This intent has already been handed to its original checkout browser. Do not start another payment if its outcome is uncertain.</p><p><a href="'.esc_url(wc_get_checkout_url()).'">Resume original native checkout</a></p><p><a href="'.esc_url(add_query_arg(['krev_agent'=>'listing-status','intent'=>$row['id']],home_url('/'))).'">Check original purchase status</a></p>';return;
        }
        if($q && !$q['estimate_only']){
            foreach($q['items'] as $line)echo '<p>'.esc_html($line['listing']['title'].' — $'.Domain::decimal($line['total_minor'])).'</p>';
            foreach($q['fees'] as $fee)echo '<p>'.esc_html($fee['name'].' — $'.Domain::decimal($fee['total_minor'])).'</p>';
            echo '<p>Discount: $'.esc_html(Domain::decimal($q['discount_minor'])).'</p><p>Shipping: $'.esc_html(Domain::decimal($q['shipping_minor'])).'</p><p>Taxes: $'.esc_html(Domain::decimal($q['tax_minor'])).'</p><p><strong>All-in total: $'.esc_html(Domain::decimal($q['total_minor'])).' USD</strong></p><p>Native payment method: '.esc_html($q['payment_method']).'. The gateway may require a login or independent payment approval.</p>';
            foreach($q['shipping_rates'] as $package)foreach($package['options'] as $rate)if($rate['id']===$package['selected'])echo '<p>Selected shipping: '.esc_html($rate['label']).'</p>';
            echo '<p>Quote valid until '.esc_html(gmdate('c',$d['quote_expires'])).'. <a href="'.esc_url($q['policy_url']).'">Purchase terms</a> · <a href="'.esc_url($q['return_policy_url']).'">Return and refund policy</a></p><form method="post">';
            self::hidden($owner,$row['id']);echo '<input type="hidden" name="action" value="continue"><input type="hidden" name="quote_hash" value="'.esc_attr($q['quote_hash']).'"><label><input type="checkbox" name="accept" value="yes" required> I accept these items, seller, fulfillment, total and policies. Continue to native checkout; I will authorize payment there.</label><button>Continue to WooCommerce checkout</button></form>';
        }else echo '<p><strong>Estimate only:</strong> enter complete addresses, choose the actual gateway and a native shipping rate where required. The total is unknown until those checks pass.</p>';
        echo '<h2>Prepare or update the quote</h2><form method="post">';self::hidden($owner,$row['id']);echo '<input type="hidden" name="action" value="quote">';
        $c=$d['context'];echo '<label>Receipt email<input name="email" type="email" maxlength="254" required value="'.esc_attr($c['email']??'').'"></label>';
        foreach(['billing'=>'Billing','shipping'=>'Delivery'] as $kind=>$label){echo '<fieldset><legend>'.esc_html($label.' address').'</legend>';
            foreach(['address_1'=>'Street address','address_2'=>'Apartment (optional)','city'=>'City','state'=>'State code','postcode'=>'Postal code','country'=>'Country code'] as $field=>$text){$value=$c[$kind][$field]??($field==='country'?'US':'');echo '<label>'.esc_html($label.' '.$text).'<input name="'.esc_attr($kind.'_'.$field).'" maxlength="150" '.($field==='address_2'?'':'required').' value="'.esc_attr($value).'"></label>';}
            echo '</fieldset>';
        }
        echo '<label>Native payment method<select name="payment_method" required>';
        $gateways=WC()->payment_gateways()->payment_gateways();foreach(Settings::get()['listing_gateway_ids'] as $id)if(isset($gateways[$id]) && $gateways[$id]->enabled==='yes')echo '<option value="'.esc_attr($id).'" '.selected($c['payment_method']??'',$id,false).'>'.esc_html(wp_strip_all_tags($gateways[$id]->get_title())).'</option>';
        echo '</select></label>';
        foreach($q['shipping_rates']??[] as $package){echo '<label>Shipping package '.esc_html((string)($package['package_index']+1)).'<select name="shipping_methods['.esc_attr((string)$package['package_index']).']" required>';
            foreach($package['options'] as $option)echo '<option value="'.esc_attr($option['id']).'" '.selected($package['selected']??'',$option['id'],false).'>'.esc_html($option['label'].' — $'.Domain::decimal($option['cost_minor']+$option['tax_minor'])).'</option>';echo '</select></label>';
        }
        echo '<button>Calculate native quote</button></form>';
    }
    private static function hidden(string $owner,string $id): void {echo '<input type="hidden" name="csrf" value="'.esc_attr(self::csrf($owner,$id,intdiv(time(),600))).'">';}
}
