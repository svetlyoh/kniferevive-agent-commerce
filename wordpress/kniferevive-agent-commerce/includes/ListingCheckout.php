<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Prepares native carts only. Gateways own charges, finality, refunds and seller payouts. */
final class ListingCheckout {
    public static function boot(): void {
        add_action('woocommerce_checkout_create_order',[self::class,'bindOrder'],5);
        add_action('woocommerce_checkout_order_created',[self::class,'orderCreated'],5);
        add_action('woocommerce_checkout_validate_order_before_payment',[self::class,'validateBlockOrder'],5,2);
        add_action('woocommerce_store_api_checkout_order_processed',[self::class,'blockOrder'],5);
        add_action('woocommerce_payment_complete',[self::class,'paymentObserved'],200);
        // Order-pay is a native retry surface too; prevent changing the accepted gateway.
        add_action('woocommerce_before_pay_action',[self::class,'validatePayOrder'],5);
        add_action('woocommerce_cart_emptied',[self::class,'clearBrowserIntent']);
    }
    public static function clearBrowserIntent(): void {
        if(WC()->session){WC()->session->set('krev_listing_intent',null);WC()->session->set('krev_listing_owner',null);}
        if(WC()->session)WC()->session->set('krev_booking_id',null);
        // Keep the durable intent/order evidence; only the emptied browser cart is detached.
    }
    public static function enabled(): bool {
        $s=Settings::get();
        $configured=$s['listing_handoff_enabled'] && $s['listing_pricing_verified'] && $s['listing_gateway_ids']
            && $s['listing_policy_url'] && $s['listing_policy_version'] && $s['return_policy_url']
            && get_woocommerce_currency()==='USD' && wc_get_price_decimals()===2;
        if(!$configured)return false;
        foreach(WC()->payment_gateways()->payment_gateways() as $g)if(in_array($g->id,$s['listing_gateway_ids'],true) && $g->enabled==='yes'
            && ($g->get_option('testmode','unknown')==='yes' || $s['listing_live_verified']))return true;
        return false;
    }
    private static function requireEnabled(): void {
        if (!self::enabled()) Domain::fail('LISTING_HANDOFF_DISABLED','Agent listing handoff is unavailable. Use the original listing and normal checkout.',503);
        if (!is_ssl() && wp_get_environment_type()!=='local') Domain::fail('FORBIDDEN','Secure first-party checkout is required.',403);
    }
    public static function seller(int $id): array {
        $user=get_userdata($id);
        $info=function_exists('dokan_get_store_info') ? dokan_get_store_info($id) : [];
        return ['id'=>$id,'display_name'=>wp_strip_all_tags((string)(!empty($info['store_name'])?$info['store_name']:($user?$user->display_name:'')))];
    }
    private static function serviceTerms(int $id): ?array {
        foreach (Settings::get()['listing_services'] as $terms) if ($terms['product_id']===$id) return $terms;
        return null;
    }
    private static function eligible($p): string {
        if (!$p || $p->get_status()!=='publish' || $p->get_catalog_visibility()==='hidden') return 'unavailable';
        if (!$p->is_type('simple')) return 'unsupported_variation';
        if (!$p->is_purchasable() || !$p->is_in_stock() || $p->backorders_allowed()) return 'unavailable';
        $seller=(int)get_post_field('post_author',$p->get_id());
        if (!get_userdata($seller)) return 'unavailable';
        if (function_exists('dokan_is_user_seller')) {
            if (dokan_is_user_seller($seller)) { if (!dokan_is_seller_enabled($seller)) return 'seller_disabled'; }
            elseif (!user_can($seller,'manage_woocommerce')) return 'seller_disabled';
        }
        if ($p->get_meta('_product_addons') || apply_filters('krev_agent_listing_requires_configuration',false,$p)) return 'requires_selection';
        if (Commerce::isService($p)) {
            $terms=self::serviceTerms($p->get_id());
            if (!$terms || !$terms['native_fulfillment_verified']) return 'needs_manual_review';
        }
        return self::enabled() ? 'handoff_only' : 'handoff_disabled';
    }
    /** Read only the native plugin's buyer terms; native checkout still owns snapshots. */
    private static function returnPolicy(int $id): ?array {
        if (!class_exists('KnifeRevive_Return_Policies') || !taxonomy_exists('kr_return_policy')) return null;
        $terms=wp_get_object_terms($id,'kr_return_policy');
        if (is_wp_error($terms) || !$terms) return null;
        $term=reset($terms);$badge=get_term_meta($term->term_id,'_kr_badge_label',true);
        return ['source'=>'native_product_return_policy','name'=>wp_strip_all_tags($term->name),
            'label'=>wp_strip_all_tags((string)($badge?:$term->name)),'description'=>wp_strip_all_tags($term->description),
            'type'=>wp_strip_all_tags((string)get_term_meta($term->term_id,'_kr_policy_type',true)),
            'days'=>(int)get_term_meta($term->term_id,'_kr_return_days',true),
            'fee_terms'=>wp_strip_all_tags((string)get_term_meta($term->term_id,'_kr_return_fee',true))];
    }
    public static function product(int $id): array {
        $p=wc_get_product($id);
        if (!$p || $p->get_status()!=='publish' || $p->get_catalog_visibility()==='hidden' || $p->is_type('variation')) Domain::fail('NOT_FOUND','Listing unavailable.',404);
        $modified=$p->get_date_modified(); $terms=Commerce::isService($p)?self::serviceTerms($id):null;$return=self::returnPolicy($id);
        $variants=[];
        if ($p->is_type('variable')) foreach (array_slice($p->get_children(),0,100) as $variant) {
            $v=wc_get_product($variant);
            if ($v && $v->get_status()==='publish') $variants[]=['variation_id'=>$v->get_id(),'attributes'=>$v->get_variation_attributes(),'in_stock'=>$v->is_in_stock(),'checkout_eligibility'=>'unsupported_variation'];
        }
        return ['product_id'=>$id,'title'=>wp_strip_all_tags($p->get_name()),'type'=>$p->get_type(),'canonical_url'=>get_permalink($id),
            'categories'=>wp_get_post_terms($id,'product_cat',['fields'=>'slugs']),'seller'=>self::seller((int)get_post_field('post_author',$id)),
            'condition'=>wp_strip_all_tags($p->get_attribute('pa_condition'))?:null,'condition_verification'=>'seller_claim',
            'currency'=>get_woocommerce_currency(),'unit_price_minor'=>$p->get_price()==='' || wc_get_price_decimals()!==2 ? null : Domain::cents(wc_format_decimal($p->get_price(),2)),
            'price_status'=>'catalog_before_tax_shipping_fees','stock_status'=>$p->get_stock_status(),'available_quantity'=>$p->get_stock_quantity(),
            'inventory_reserved'=>false,'checkout_eligibility'=>self::eligible($p),'direct_payment_enabled'=>false,'variations'=>$variants,
            'fulfillment_type'=>Commerce::isService($p)?'service':($p->needs_shipping()?'shipping':'virtual'),
            'fulfillment_note'=>$terms['fulfillment_note']??null,'policy_url'=>$terms['terms_url']??(Settings::get()['listing_policy_url']?:null),
            'return_policy'=>$return,'return_policy_url'=>$return?home_url('/return-policy/'):(Settings::get()['return_policy_url']?:null),
            'updated_at'=>$modified?$modified->date('c'):null];
    }
    public static function catalog(array $args): array {
        Domain::fields($args,['search','category','seller','page','per_page']);
        $page=Domain::integer($args['page']??1,1,100);$per=Domain::integer($args['per_page']??10,1,100);
        $query=['status'=>'publish','limit'=>$per,'page'=>$page,'paginate'=>true,'orderby'=>'ID','order'=>'ASC','visibility'=>'visible'];
        if (isset($args['search'])) $query['s']=Domain::text($args['search'],100);
        if (isset($args['category'])) { $slug=Domain::text($args['category'],100); if (!preg_match('/^[a-z0-9-]+$/D',$slug)) Domain::fail('INVALID_REQUEST','Use a category slug.');$query['category']=[$slug]; }
        if (isset($args['seller'])) $query['author']=Domain::integer($args['seller'],1,PHP_INT_MAX);
        $found=wc_get_products($query);$items=[];
        foreach ($found->products as $p) if (!$p->is_type('variation')) $items[]=self::product($p->get_id());
        return ['schema_version'=>'1.1','items'=>$items,'page'=>$page,'per_page'=>$per,'total'=>(int)$found->total,'pages'=>(int)$found->max_num_pages,'fetched_at'=>gmdate('c')];
    }
    private static function selection(array $input): array {
        Domain::fields($input,['items','coupons','source','booking_id'],['items']);
        if (!is_array($input['items']) || !array_is_list($input['items']) || !$input['items'] || count($input['items'])>10) Domain::fail('INVALID_REQUEST','Use one to ten listing lines.');
        $seen=[];$sellers=[];$quantity=0;
        foreach ($input['items'] as $item) {
            if (!is_array($item)) Domain::fail('INVALID_REQUEST','Use listing objects.');
            Domain::fields($item,['product_id','quantity'],['product_id','quantity']);
            $id=Domain::integer($item['product_id'],1,PHP_INT_MAX);$qty=Domain::integer($item['quantity'],1,30);$quantity+=$qty;
            if (isset($seen[$id]) || $quantity>50) Domain::fail('INVALID_REQUEST','Use unique products and at most 50 units.');
            $p=wc_get_product($id);$state=self::eligible($p);
            if (in_array($state,['unsupported_variation','requires_selection'],true)) Domain::fail('UNSUPPORTED_VARIATION','Use the listing page to select unsupported variations or add-ons.');
            if ($state==='needs_manual_review') Domain::fail('NEEDS_MANUAL_REVIEW','Sharpening fulfillment terms must be verified before native prepayment.');
            if ($state!=='handoff_only' || !$p->has_enough_stock($qty) || ($p->is_sold_individually() && $qty!==1)) Domain::fail('LISTING_UNAVAILABLE','A selected listing or quantity is unavailable.');
            if (apply_filters('woocommerce_add_to_cart_validation',true,$id,$qty,0,[])!==true) Domain::fail('LISTING_UNAVAILABLE','Native listing validation refused this purchase.');
            $seen[$id]=true;$sellers[]=(int)get_post_field('post_author',$id);
        }
        if (count(array_unique($sellers))!==1) Domain::fail('MULTI_SELLER_UNSUPPORTED','Use separately reviewed checkouts for different sellers. No charge has been created.');
        $coupons=$input['coupons']??[];
        if (!is_array($coupons) || !array_is_list($coupons) || count($coupons)>5) Domain::fail('INVALID_REQUEST','Use at most five coupon codes.');
        foreach($coupons as &$code) { $code=wc_format_coupon_code(Domain::text($code,100));if (!$code || !(new \WC_Coupon($code))->get_id()) Domain::fail('COUPON_UNAVAILABLE','Coupon unavailable.'); }
        unset($code);$input['coupons']=array_values(array_unique($coupons));sort($input['coupons']);
        $input['source']=Domain::text($input['source']??'kniferevive-concierge',80);
        usort($input['items'],static fn($a,$b)=>$a['product_id']<=>$b['product_id']);
        if(isset($input['booking_id'])){
            if(!Domain::validId($input['booking_id']))Domain::fail('INVALID_REQUEST','Invalid booking reference.');
            if($input['items']!==Booking::paymentSelection($input['booking_id'])['items'])Domain::fail('BOOKING_CHANGED','Checkout items must match the confirmed booking.');
        }
        return $input;
    }
    public static function create(array $input,string $owner,string $key): array {
        self::requireEnabled();if(isset($input['booking_id']))Booking::get($input['booking_id'],$owner);$input=self::selection($input);$purchase=Domain::digest($input['items']);
        return Store::lock('listing-purchase:'.Domain::digest([$owner,$purchase]),static function()use($input,$owner,$key,$purchase){
            return Store::idempotent($owner,'listing-create',$key,$input,static function()use($input,$owner,$purchase){
                if (Store::unresolvedListing($owner,$purchase)) Domain::fail('PAYMENT_UNRESOLVED','A matching native checkout is unresolved. Check its original status.',409);
                $id=Domain::id();Store::put($id,'listing',$owner,time()+1800,['selection'=>$input,'purchase_hash'=>$purchase,'handoff_state'=>'review','quote'=>null,'context'=>[],'order_id'=>null]);return Store::get($id);
            });
        });
    }
    public static function get(string $id,string $owner,bool $allowStatus=false): array {
        $row=Store::get($id,'listing',$owner);
        if (!$allowStatus && (int)$row['expires']<time()) Domain::fail('INTENT_EXPIRED','The review intent expired. Do not retry an unresolved payment.',410);
        if ($allowStatus && (int)Store::get($owner,'session')['expires']<time()) Domain::fail('AUTHORIZATION_REQUIRED','Private status access expired.',401);
        return $row;
    }
    private static function address(array $address): array {
        Domain::fields($address,['address_1','address_2','city','state','postcode','country'],['address_1','city','state','postcode','country']);
        foreach($address as &$value) $value=Domain::text($value,150);unset($value);
        if (!$address['address_1'] || !$address['city'] || $address['country']!=='US'
            || !isset(WC()->countries->get_states('US')[$address['state']]) || !\WC_Validation::is_postcode($address['postcode'],'US')) Domain::fail('INVALID_REQUEST','Use a complete valid US address.');
        $address['postcode']=wc_format_postcode($address['postcode'],'US');$address['address_2']=$address['address_2']??'';return $address;
    }
    private static function context(array $input): array {
        Domain::fields($input,['billing','shipping','email','payment_method','shipping_methods']);
        foreach(['billing','shipping'] as $kind) if (isset($input[$kind])) {if (!is_array($input[$kind]))Domain::fail('INVALID_REQUEST','Invalid address.');$input[$kind]=self::address($input[$kind]);}
        if (isset($input['billing']) && !isset($input['shipping'])) $input['shipping']=$input['billing'];
        if (isset($input['email'])) { $input['email']=Domain::text($input['email'],254);if(!is_email($input['email']))Domain::fail('INVALID_REQUEST','Use a valid receipt email.'); }
        if (isset($input['payment_method'])) {$input['payment_method']=Domain::text($input['payment_method'],80);if(!in_array($input['payment_method'],Settings::get()['listing_gateway_ids'],true))Domain::fail('PAYMENT_METHOD_UNAVAILABLE','This native gateway has not been enabled for agent handoff.');}
        $input['shipping_methods']=$input['shipping_methods']??[];
        if (!is_array($input['shipping_methods']) || !array_is_list($input['shipping_methods']) || count($input['shipping_methods'])>10) Domain::fail('INVALID_REQUEST','Use a list of native shipping rate IDs.');
        foreach($input['shipping_methods'] as &$method)$method=Domain::text($method,150);unset($method);
        return $input;
    }
    private static function minor($value): int { return Domain::cents(wc_format_decimal($value,2)); }
    /** Native hooks execute against disposable objects, not the browser's cart/session. */
    public static function price(array $selection,array $context): array {
        if (!class_exists(QuoteSession::class,false)) require_once __DIR__.'/QuoteCart.php';
        if (!class_exists(ListingCart::class,false)) require_once __DIR__.'/ListingCart.php';
        $selection=self::selection($selection);$context=self::context($context);
        $items=array_map(static fn($line)=>['product_id'=>$line['product_id'],'quantity'=>$line['quantity'],'listing'=>self::product($line['product_id'])],$selection['items']);
        $empty=['currency'=>'USD','items'=>$items,'total_minor'=>null,'estimate_only'=>true,'reason'=>'customer_address_and_gateway_required',
            'shipping_rates'=>[],'fees'=>[],'tax_minor'=>null,'shipping_minor'=>null,'discount_minor'=>null,'payment_methods'=>[],'policy_url'=>Settings::get()['listing_policy_url'],'return_policy_url'=>Settings::get()['return_policy_url']];
        if (!isset($context['billing'],$context['shipping'],$context['email'],$context['payment_method'])) return $empty;
        if(isset($selection['booking_id'])){
            $booking=Store::get($selection['booking_id'],'booking')['data']['input'];$address=$context['shipping'];
            if($address['country']!=='US' || $address['state']!=='CA' || $address['postcode']!==$booking['postal_code'])Domain::fail('ADDRESS_REVIEW_REQUIRED','Service address must match the confirmed booking ZIP code.');
            if(isset($booking['pickup_address']))foreach($booking['pickup_address'] as $field=>$value)if(($address[$field]??'')!==$value)Domain::fail('ADDRESS_REVIEW_REQUIRED','The fulfillment address must match the address approved by KnifeRevive.');
        }
        $wc=WC();$saved=[$wc->cart,$wc->session,$wc->customer];$shipping=$wc->shipping();$savedShipping=[$shipping->packages,$shipping->shipping_methods];
        $customer=new \WC_Customer(get_current_user_id());$customer->set_billing_email($context['email']);
        foreach(['billing','shipping'] as $kind)foreach($context[$kind] as $field=>$value)$customer->{'set_'.$kind.'_'.$field}($value);
        $customer->set_calculated_shipping(true);
        $session=new QuoteSession();$session->set('chosen_payment_method',$context['payment_method']);$session->set('chosen_shipping_methods',$context['shipping_methods']);
        if(isset($selection['booking_id']))$session->set('krev_booking_id',$selection['booking_id']);
        $cart=new ListingCart();$wc->cart=$cart;$wc->session=$session;$wc->customer=$customer;
        // Suspend only native browser-cart persistence callbacks; retain pricing/fee/shipping hooks.
        $suspended=[];global $wp_filter;
        foreach(['woocommerce_after_calculate_totals','woocommerce_applied_coupon'] as $hook)if(isset($wp_filter[$hook]))foreach($wp_filter[$hook]->callbacks as $priority=>$callbacks)foreach($callbacks as $callback){
            $fn=$callback['function'];if(is_array($fn) && ($fn[0] instanceof \WC_Cart_Session || $fn[0] instanceof \WC_Cart)){$suspended[]=[$hook,$fn,$priority,$callback['accepted_args']];remove_action($hook,$fn,$priority);}
        }
        try {
            foreach($selection['items'] as $line){$p=wc_get_product($line['product_id']);$key=$cart->generate_cart_id($p->get_id());
                $data=apply_filters('woocommerce_add_cart_item_data',[],$p->get_id(),0,$line['quantity']);
                $cart->cart_contents[$key]=apply_filters('woocommerce_add_cart_item',array_merge($data,['key'=>$key,'product_id'=>$p->get_id(),'variation_id'=>0,'variation'=>[],'quantity'=>$line['quantity'],'data'=>clone $p,'data_hash'=>wc_get_cart_item_data_hash($p)]),$key);
            }
            foreach($selection['coupons'] as $code)if(!$cart->apply_coupon($code))Domain::fail('COUPON_UNAVAILABLE','Native coupon validation refused this discount.');
            $cart->calculate_totals();
            $gateways=$wc->payment_gateways()->get_available_payment_gateways();
            if(!isset($gateways[$context['payment_method']]) || $context['payment_method']==='krev_agent_checkout')Domain::fail('PAYMENT_METHOD_UNAVAILABLE','Selected native payment method is unavailable.');
            if($gateways[$context['payment_method']]->get_option('testmode','unknown')!=='yes' && !Settings::get()['listing_live_verified'])Domain::fail('PAYMENT_METHOD_UNAVAILABLE','Live or unknown gateway mode needs separate merchant verification.');
            $rates=[];$missing=false;
            foreach($shipping->get_packages() as $index=>$package){
                $options=[];foreach($package['rates']??[] as $rate)$options[]=['id'=>$rate->get_id(),'label'=>wp_strip_all_tags($rate->get_label()),'cost_minor'=>self::minor($rate->get_cost()),'tax_minor'=>self::minor(array_sum($rate->get_taxes()))];
                if(!$options)Domain::fail('SHIPPING_UNAVAILABLE','Native shipping has no eligible rate for this destination.');
                $selected=$context['shipping_methods'][$index]??null;
                if($selected!==null && !isset($package['rates'][$selected]))Domain::fail('SHIPPING_UNAVAILABLE','Choose a current native shipping rate.');
                if($selected===null)$missing=true;
                $rates[]=['package_index'=>$index,'options'=>$options,'selected'=>$selected];
            }
            if(count($context['shipping_methods'])!==count($rates) && $context['shipping_methods'])Domain::fail('SHIPPING_UNAVAILABLE','Shipping selection does not match the current packages.');
            $lines=[];foreach($cart->get_cart() as $line)$lines[]=['product_id'=>$line['product_id'],'quantity'=>$line['quantity'],'listing'=>self::product($line['product_id']),'subtotal_minor'=>self::minor($line['line_subtotal']),'total_minor'=>self::minor($line['line_total']),'tax_minor'=>self::minor($line['line_tax'])];
            $fees=[];foreach($cart->get_fees() as $fee)$fees[]=['name'=>wp_strip_all_tags($fee->name),'total_minor'=>self::minor($fee->total),'tax_minor'=>self::minor($fee->tax)];
            $total=self::minor($cart->get_total('edit'));if($total<1 || $total>Settings::get()['listing_max_minor'])Domain::fail('BUDGET_EXCEEDED','Native cart exceeds the agent handoff limit.');
            $quote=array_replace($empty,['items'=>$lines,'fees'=>$fees,'total_minor'=>$missing?null:$total,'estimate_only'=>$missing,'reason'=>$missing?'shipping_selection_required':null,
                'shipping_rates'=>$rates,'shipping_minor'=>$missing?null:self::minor($cart->get_shipping_total()),'tax_minor'=>self::minor($cart->get_total_tax()),'discount_minor'=>self::minor($cart->get_discount_total()),
                'payment_methods'=>array_values(array_intersect(array_keys($gateways),Settings::get()['listing_gateway_ids'])),'payment_method'=>$context['payment_method'],
                'native_cart_hash'=>$cart->get_cart_hash(),'buyer_context_id'=>get_current_user_id(),'policy_version'=>Settings::get()['listing_policy_version']]);
            $quote['quote_hash']=Domain::digest([$quote,$context,$selection,isset($selection['booking_id'])?Booking::paymentSelection($selection['booking_id'])['fingerprint']:null]);return $quote;
        } finally {
            [$wc->cart,$wc->session,$wc->customer]=$saved;[$shipping->packages,$shipping->shipping_methods]=$savedShipping;
            foreach($suspended as [$hook,$fn,$priority,$accepted])add_action($hook,$fn,$priority,$accepted);
        }
    }
    public static function quote(string $id,array $input,string $owner,string $key): array {
        self::requireEnabled();$context=self::context($input);
        return Store::lock('listing:'.$id,static function()use($id,$context,$owner,$key){
            $row=self::get($id,$owner);if($row['data']['handoff_state']!=='review')Domain::fail('INTENT_ALREADY_USED','Check the original native checkout instead of replacing it.',409);
            $q=Store::idempotent($owner,'listing-quote:'.$id,$key,$context,static function()use($row,$context,$owner){
                $quote=self::price($row['data']['selection'],$context);$qid=Domain::id();Store::put($qid,'listing_quote',$owner,min((int)$row['expires'],time()+600),['quote'=>$quote,'context'=>$context]);return Store::get($qid);
            });
            $data=$row['data'];$data['quote']=$q['data']['quote'];$data['context']=$q['data']['context'];$data['quote_expires']=(int)$q['expires'];Store::update($id,$data);return self::get($id,$owner);
        });
    }
    private static function browserBinding(): string {
        return hash_hmac('sha256',(string)WC()->session->get_customer_id().':'.get_current_user_id(),wp_salt('auth'));
    }
    private static function matchesCart(array $selection): bool {
        $items=[];foreach(WC()->cart->get_cart() as $line){if($line['variation_id'])return false;$items[]=['product_id'=>(int)$line['product_id'],'quantity'=>(int)$line['quantity']];}
        usort($items,static fn($a,$b)=>$a['product_id']<=>$b['product_id']);$coupons=WC()->cart->get_applied_coupons();sort($coupons);
        return $items===$selection['items'] && $coupons===$selection['coupons'];
    }
    /** Called only after the first-party form's CSRF + quote-bound buyer acceptance. */
    public static function handoff(string $id,string $owner,string $hash): string {
        self::requireEnabled();
        if (!WC()->session || !WC()->cart || !WC()->customer) wc_load_cart();
        return Store::lock('listing:'.$id,static function()use($id,$owner,$hash){
            $row=self::get($id,$owner);$d=$row['data'];$binding=self::browserBinding();
            if($d['handoff_state']==='cart_ready'){
                if(!hash_equals($d['browser_binding'],$binding) || !self::matchesCart($d['selection']))Domain::fail('CART_CONFLICT','Use the original checkout browser. Its cart cannot be replaced.',409);
                return wc_get_checkout_url();
            }
            if($d['handoff_state']!=='review')Domain::fail('PAYMENT_UNRESOLVED','Check the original native order or ask the merchant to reconcile it.',409);
            if(!WC()->cart->is_empty() || WC()->session->get('order_awaiting_payment') || WC()->session->get('store_api_draft_order'))Domain::fail('CART_CONFLICT','Your browser has a cart or pending order. Review it separately; nothing was replaced.',409);
            if(empty($d['quote']) || $d['quote']['estimate_only'] || ($d['quote_expires']??0)<time() || !hash_equals($d['quote']['quote_hash']??'',$hash))Domain::fail('QUOTE_CHANGED','Prepare and approve the current all-in quote.',409);
            $fresh=self::price($d['selection'],$d['context']);if(!hash_equals($fresh['quote_hash']??'',$hash))Domain::fail('QUOTE_CHANGED','Items, totals or policies changed. Review a fresh quote.',409);
            $d['browser_binding']=$binding;$d['handoff_state']='preparing_cart';Store::update($id,$d);
            $savedCustomer=clone WC()->customer;
            $savedSession=[];foreach(['customer','chosen_payment_method','chosen_shipping_methods','krev_listing_intent','krev_listing_owner','krev_booking_id'] as $field)$savedSession[$field]=WC()->session->get($field);
            try {
                foreach($d['context']['billing'] as $field=>$value)WC()->customer->{'set_billing_'.$field}($value);
                foreach($d['context']['shipping'] as $field=>$value)WC()->customer->{'set_shipping_'.$field}($value);
                WC()->customer->set_billing_email($d['context']['email']);WC()->customer->set_calculated_shipping(true);
                WC()->session->set('chosen_payment_method',$d['context']['payment_method']);WC()->session->set('chosen_shipping_methods',$d['context']['shipping_methods']);
                WC()->session->set('krev_booking_id',$d['selection']['booking_id']??null);
                foreach($d['selection']['items'] as $line)if(!WC()->cart->add_to_cart($line['product_id'],$line['quantity']))Domain::fail('LISTING_UNAVAILABLE','Native cart refused the selected item.');
                foreach($d['selection']['coupons'] as $code)if(!WC()->cart->apply_coupon($code))Domain::fail('COUPON_UNAVAILABLE','Native checkout refused the coupon.');
                WC()->cart->calculate_totals();
                if(!self::matchesCart($d['selection']) || self::minor(WC()->cart->get_total('edit'))!==$fresh['total_minor'])Domain::fail('QUOTE_CHANGED','Native cart differs from the reviewed quote.');
                WC()->session->set('krev_listing_intent',$id);WC()->session->set('krev_listing_owner',$owner);
                (new \WC_Customer_Data_Store_Session())->save_to_session(WC()->customer);WC()->session->set_customer_session_cookie(true);
                (new \WC_Cart_Session(WC()->cart))->set_session();WC()->session->save_data();
                $d['handoff_state']='cart_ready';$d['approved_at']=time();Store::update($id,$d);return wc_get_checkout_url();
            } catch(\Throwable $e) {
                // The preexisting cart was empty. Remove only this failed new preparation.
                WC()->cart->empty_cart();WC()->customer=$savedCustomer;
                foreach($savedSession as $field=>$value)WC()->session->set($field,$value);
                WC()->session->save_data();$d['handoff_state']='review';Store::update($id,$d);throw $e;
            }
        });
    }
    private static function nativeIntent(): ?array {
        $id=WC()->session?WC()->session->get('krev_listing_intent'):null;
        if(!$id)return null;
        $owner=(string)WC()->session->get('krev_listing_owner');$row=self::get($id,$owner,true);
        if(!hash_equals($row['data']['browser_binding']??'',self::browserBinding()))Domain::fail('FORBIDDEN','This intent belongs to a different checkout browser.',403);
        return $row;
    }
    private static function validateOrder(\WC_Order $order,array $row): void {
        self::requireEnabled();$d=$row['data'];
        $existing=(int)($d['order_id']??0);
        if($existing && $existing!==$order->get_id())Domain::fail('ORDER_ALREADY_EXISTS','Resume the original native order. Do not create another payment.',409);
        if(($d['creation_started']??false) && !$existing && !$order->get_id())Domain::fail('PAYMENT_UNRESOLVED','Interrupted order creation needs merchant reconciliation.',409);
        if((int)$row['expires']<time() || ($d['quote_expires']??0)<time())Domain::fail('INTENT_EXPIRED','The approved quote expired. Check any original payment before another purchase.',410);
        self::selection($d['selection']);
        if(!self::matchesCart($d['selection']))Domain::fail('CART_CONFLICT','The native cart changed. Review a new intent.',409);
        if($order->get_payment_method()!==$d['context']['payment_method'] || self::minor($order->get_total())!==$d['quote']['total_minor'])Domain::fail('QUOTE_CHANGED','The native gateway or total changed. Review a fresh quote.',409);
        $lines=[];foreach($order->get_items() as $line){if($line->get_variation_id())Domain::fail('UNSUPPORTED_VARIATION','Unsupported order selection.');$lines[]=['product_id'=>$line->get_product_id(),'quantity'=>(int)$line->get_quantity()];}
        usort($lines,static fn($a,$b)=>$a['product_id']<=>$b['product_id']);if($lines!==$d['selection']['items'])Domain::fail('CART_CONFLICT','Native order items differ from the approved intent.',409);
        $context=$d['context'];foreach(['billing','shipping'] as $kind)foreach($context[$kind] as $field=>$value){$actual=(string)$order->{'get_'.$kind.'_'.$field}();if($actual!==$value)Domain::fail('QUOTE_CHANGED','The checkout address changed. Review an updated quote.',409);}
        if(strtolower($order->get_billing_email())!==strtolower($context['email']))Domain::fail('QUOTE_CHANGED','The checkout identity changed. Review an updated quote.',409);
        $fresh=self::price($d['selection'],$context);if(!hash_equals($fresh['quote_hash']??'',$d['quote']['quote_hash']))Domain::fail('QUOTE_CHANGED','The listing, seller or policy changed. Review an updated quote.',409);
    }
    public static function bindOrder(\WC_Order $order): void {
        $row=self::nativeIntent();if(!$row)return;
        Store::lock('listing:'.$row['id'],static function()use($row,$order){
            $row=Store::get($row['id'],'listing',$row['owner']);self::validateOrder($order,$row);$d=$row['data'];
            if($d['handoff_state']!=='cart_ready' && $d['handoff_state']!=='order_linked')Domain::fail('PAYMENT_UNRESOLVED','Original order creation needs reconciliation.',409);
            $order->update_meta_data('_krev_listing_intent',$row['id']);$order->update_meta_data('_krev_listing_quote_hash',$d['quote']['quote_hash']);
            if(isset($d['selection']['booking_id']))$order->update_meta_data('_krev_service_booking',$d['selection']['booking_id']);
            $d['creation_started']=true;if($order->get_id()){$d['order_id']=$order->get_id();$d['handoff_state']='order_linked';}Store::update($row['id'],$d);
        });
    }
    public static function orderCreated(\WC_Order $order): void {
        $id=$order->get_meta('_krev_listing_intent');if(!Domain::validId($id))return;
        Store::lock('listing:'.$id,static function()use($id,$order){$row=Store::get($id,'listing');$d=$row['data'];if(!empty($d['order_id']) && (int)$d['order_id']!==$order->get_id())Domain::fail('ORDER_ALREADY_EXISTS','Another native order is already bound.',409);$d['order_id']=$order->get_id();$d['handoff_state']='order_linked';Store::update($id,$d);});
    }
    /** Operator recovery links exactly one already persisted native order; never creates/pays one. */
    public static function recoverOriginalOrder(string $id): int {
        if(!current_user_can('manage_woocommerce'))Domain::fail('FORBIDDEN','Merchant reconciliation requires administrator access.',403);
        if(!Domain::validId($id))Domain::fail('NOT_FOUND','Intent unavailable.',404);
        return Store::lock('listing:'.$id,static function()use($id){
            $row=Store::get($id,'listing');$d=$row['data'];
            $orders=wc_get_orders(['limit'=>2,'meta_key'=>'_krev_listing_intent','meta_value'=>$id]);
            if(count($orders)!==1)Domain::fail('PAYMENT_UNRESOLVED','Find and reconcile the original native order manually; exactly one match is required.',409);
            $order=$orders[0];$items=[];foreach($order->get_items() as $item)$items[]=['product_id'=>$item->get_product_id(),'quantity'=>(int)$item->get_quantity()];usort($items,static fn($a,$b)=>$a['product_id']<=>$b['product_id']);
            if($items!==$d['selection']['items'] || $order->get_meta('_krev_listing_quote_hash')!==($d['quote']['quote_hash']??'')
                || $order->get_payment_method()!==$d['context']['payment_method'] || self::minor($order->get_total())!==$d['quote']['total_minor']
                || (!empty($d['order_id']) && (int)$d['order_id']!==$order->get_id()))Domain::fail('PAYMENT_UNRESOLVED','Original order binding differs from the reviewed intent.',409);
            $d['order_id']=$order->get_id();$d['handoff_state']='order_linked';Store::update($id,$d);return $order->get_id();
        });
    }
    public static function validateBlockOrder(\WC_Order $order,\WP_Error $errors): void {
        try{$row=self::nativeIntent();if($row)self::validateOrder($order,$row);}catch(\Throwable $e){$errors->add('krev_listing_review','Native checkout requires a fresh buyer review or reconciliation of the original order.');}
    }
    public static function blockOrder(\WC_Order $order): void { self::bindOrder($order);$order->save();self::orderCreated($order); }
    public static function validatePayOrder(\WC_Order $order): void {
        $id=$order->get_meta('_krev_listing_intent');if(!Domain::validId($id))return;
        $row=Store::get($id,'listing');$method=wc_clean(wp_unslash($_POST['payment_method']??''));
        if((int)$row['data']['order_id']!==$order->get_id() || $method!==$row['data']['context']['payment_method']
            || self::minor($order->get_total())!==$row['data']['quote']['total_minor']
            || $order->get_meta('_krev_listing_quote_hash')!==$row['data']['quote']['quote_hash'])throw new \Exception('Use the original reviewed payment method and amount; contact KnifeRevive before a different payment.');
    }
    public static function paymentObserved(int $orderId): void {
        $order=wc_get_order($orderId);$id=$order?$order->get_meta('_krev_listing_intent'):null;
        if(!Domain::validId($id) || !$order->is_paid() || !$order->get_date_paid() || !$order->get_transaction_id())return;
        Store::lock('listing:'.$id,static function()use($id,$order){$row=Store::get($id,'listing');$d=$row['data'];if((int)($d['order_id']??0)!==$order->get_id() || $order->get_payment_method()!==$d['context']['payment_method'])return;
            $d['native_payment_observed']=['gateway'=>$order->get_payment_method(),'reference_hash'=>hash('sha256',$order->get_transaction_id()),'observed_at'=>time()];Store::update($id,$d);
        });
    }
    public static function status(string $id,string $owner): array {
        $row=self::get($id,$owner,true);$d=$row['data'];$order=empty($d['order_id'])?null:wc_get_order($d['order_id']);
        $payment='not_started';$state=null;$fulfillment='not_started';$verification='none';
        if($order){
            if($order->get_meta('_krev_listing_intent')!==$id)Domain::fail('NOT_FOUND','Order binding unavailable.',404);
            $state=$order->get_status();$payment='pending';$fulfillment=$state;
            if($order->has_status('refunded') || $order->get_total_refunded()>0)$payment='refund_recorded';
            elseif($order->has_status('cancelled'))$payment='cancelled';
            elseif($order->has_status('failed'))$payment='needs_review';
            elseif($order->is_paid()){
                $evidence=$d['native_payment_observed']??[];
                if($order->get_date_paid() && $order->get_transaction_id() && ($evidence['gateway']??'')===$order->get_payment_method()
                    && hash_equals($evidence['reference_hash']??'',hash('sha256',$order->get_transaction_id()))){$payment='paid';$verification='native_gateway_order_event';}
                else $payment='needs_review';
            }elseif($order->has_status('on-hold'))$payment='needs_review';
        }
        $service=false;foreach($d['selection']['items'] as $line)if(Commerce::isService(wc_get_product($line['product_id'])))$service=true;
        return ['intent_id'=>$id,'handoff_state'=>$d['handoff_state'],'payment_state'=>$payment,'payment_verification'=>$verification,
            'fulfillment_state'=>$fulfillment,'woocommerce_status'=>$state,'scheduling_state'=>$service?'not_booked':'not_applicable',
            'inventory_reserved_at_quote'=>false,'direct_payment_enabled'=>false,'next_action'=>$payment==='paid'?'native_merchant_fulfillment':($order?'check_original_native_order':'buyer_review'),
            'status_url'=>add_query_arg(['krev_agent'=>'listing-status','intent'=>$id],home_url('/'))];
    }
    public static function response(array $row,string $owner): array {
        $out=self::status($row['id'],$owner);$out['expires_at']=gmdate('c',(int)$row['expires']);$out['quote']=$row['data']['quote'];
        if($out['quote'])unset($out['quote']['buyer_context_id'],$out['quote']['native_cart_hash']);
        $out['quote_expires_at']=isset($row['data']['quote_expires'])?gmdate('c',$row['data']['quote_expires']):null;
        $out['review_url']=add_query_arg(['krev_agent'=>'listing-review','intent'=>$row['id']],home_url('/')).'#session='.rawurlencode(Domain::token($owner));
        return $out;
    }
}
