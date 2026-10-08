<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

final class Commerce {
    public static function isService($product): bool {
        return $product && has_term('knife-sharpening', 'product_cat', $product->get_parent_id() ?: $product->get_id());
    }
    public static function catalog(array $args): array {
        Domain::fields($args, ['category','search','page','per_page']);
        $category = $args['category'] ?? 'technology';
        if (!in_array($category, ['technology','sharpening'], true)) Domain::fail('INVALID_REQUEST', 'Choose technology or sharpening.');
        $page = Domain::integer($args['page'] ?? 1, 1, 100);
        $per = Domain::integer($args['per_page'] ?? 10, 1, 20);
        $query = ['status'=>'publish','limit'=>$per,'page'=>$page,'paginate'=>true,'orderby'=>'ID','order'=>'ASC',
            'category'=>[$category === 'sharpening' ? 'knife-sharpening' : Settings::get()['technology_category']]];
        if (isset($args['search'])) $query['s'] = Domain::text($args['search'], 100);
        $found = wc_get_products($query); $items = [];
        foreach ($found->products as $p) {
            $attrs = [];
            foreach ($p->get_attributes() as $name=>$attr) $attrs[$name] = ['values'=>array_map('wp_strip_all_tags', $attr->is_taxonomy() ? wp_get_post_terms($p->get_id(), $name, ['fields'=>'names']) : $attr->get_options()), 'verification'=>'seller_claim'];
            $updated = $p->get_date_modified();
            $items[] = ['product_id'=>$p->get_id(),'variant_id'=>null,'category'=>$category,'title'=>wp_strip_all_tags($p->get_name()),
                'canonical_url'=>get_permalink($p->get_id()),'condition'=>$p->get_attribute('pa_condition') ?: null,'attributes'=>(object)$attrs,
                'currency'=>get_woocommerce_currency(),'unit_price_minor'=>$p->get_price() === '' ? null : Domain::cents(wc_format_decimal($p->get_price(),2)),
                'price_status'=>'catalog_before_tax_and_fees','availability_status'=>$p->get_stock_status(),'available_quantity'=>$p->get_stock_quantity(),
                'purchasable'=>$p->is_purchasable(),'fulfillment_options'=>$category === 'sharpening' ? self::area(null)['modes'] : [],
                'shipping_estimate_status'=>$category === 'sharpening' ? 'quote_required' : 'existing_checkout_required',
                'fee_summary'=>['status'=>'quote_required'],'return_policy_url'=>Settings::get()['return_policy_url'] ?: null,
                'policy_version'=>Settings::get()['policy_version'] ?: null,'updated_at'=>$updated ? $updated->date('c') : null,
                'service_definition'=>self::definition($p->get_id()),'seller_id'=>(int)get_post_field('post_author',$p->get_id()),
                'checkout_mode'=>$category === 'technology' ? 'existing_woocommerce_checkout' : 'capabilities_required'];
        }
        return ['schema_version'=>'1.0','items'=>$items,'page'=>$page,'per_page'=>$per,'total'=>(int)$found->total,'pages'=>(int)$found->max_num_pages,'fetched_at'=>gmdate('c'),'cache_ttl_seconds'=>30];
    }
    private static function definition(int $id): ?string {
        foreach (Settings::get()['services'] as $s) if ($s['product_id'] === $id) return $s['definition'];
        return null;
    }
    public static function area(?string $postal): array {
        $s = Settings::get();
        if ($postal !== null) Domain::postal($postal);
        $ready = Settings::operational();
        return ['status'=>$ready ? 'configured' : 'unconfigured','postal_code'=>$postal,'eligible'=>$ready && $postal !== null ? in_array($postal,$s['postal_codes'],true) : null,
            'eligibility'=>'preliminary_postal_code_only','modes'=>$ready ? ['customer_dropoff','customer_collection', ...array_column($s['transport'],'kind')] : [],
            'courier_address_verification_required'=>true,'location'=>$ready ? $s['location'] : null,'policy_url'=>$s['policy_url'] ?: null];
    }
    public static function normalize(array $input): array {
        Domain::fields($input, ['items','postal_code','intake','return','rail','booking_mode','customer','requires_assessment','source'], ['items','postal_code','intake','return','rail','booking_mode']);
        if (!Settings::operational()) Domain::fail('UNCONFIGURED', 'Service booking settings are not configured.', 503);
        if (get_woocommerce_currency() !== 'USD' || wc_get_price_decimals() !== 2) Domain::fail('UNCONFIGURED', 'This adapter requires USD with two decimal places.', 503);
        $input['postal_code'] = Domain::postal($input['postal_code']);
        if (self::area($input['postal_code'])['eligible'] !== true) Domain::fail('OUT_OF_AREA', 'This postal code is outside the configured service area.');
        if (!is_array($input['items']) || !array_is_list($input['items']) || !$input['items'] || count($input['items']) > 10) Domain::fail('INVALID_REQUEST', 'Use one to ten service lines.');
        $seen = []; $quantity = 0;
        foreach ($input['items'] as $item) {
            Domain::fields($item,['product_id','quantity'],['product_id','quantity']);
            $id = Domain::integer($item['product_id'],1,PHP_INT_MAX); $quantity += Domain::integer($item['quantity'],1,30);
            if (isset($seen[$id]) || $quantity > 50) Domain::fail('INVALID_REQUEST', 'Use unique product IDs and at most 50 knives.');
            $seen[$id] = true; $p = wc_get_product($id);
            if (!$p || $p->get_status() !== 'publish' || !$p->is_type('simple') || !$p->is_purchasable() || !$p->is_in_stock() || !$p->has_enough_stock($item['quantity']) || !self::isService($p) || self::definition($id) === null
                || !in_array((int)get_post_field('post_author',$id), Settings::get()['merchant_ids'],true)) Domain::fail('SERVICE_UNAVAILABLE', 'A selected service is unavailable or outside this checkout scope.');
            if (!user_can((int)get_post_field('post_author',$id),'manage_woocommerce')) Domain::fail('SERVICE_UNAVAILABLE','Direct service checkout is limited to operator-owned products.');
        }
        if (!in_array($input['rail'],Settings::rails(),true)) Domain::fail('PAYMENT_METHOD_UNAVAILABLE', 'This payment method is not enabled.');
        if (isset($input['requires_assessment']) && !is_bool($input['requires_assessment'])) Domain::fail('INVALID_REQUEST', 'Assessment flag must be boolean.');
        if ($input['requires_assessment'] ?? false) Domain::fail('ASSESSMENT_REQUIRED', 'Request an operator assessment before prepayment.');
        if (!in_array($input['booking_mode'],['scheduled','pending_scheduling'],true)) Domain::fail('INVALID_REQUEST', 'Unknown booking mode.');
        if ($input['booking_mode'] === 'pending_scheduling' && !Settings::get()['pending_scheduling']) Domain::fail('UNCONFIGURED', 'Unscheduled prepayment is not enabled.');
        foreach (['intake','return'] as $leg) {
            if (!is_array($input[$leg])) Domain::fail('INVALID_REQUEST', 'Invalid handoff leg.');
            Domain::fields($input[$leg],['kind','slot_id','address'],['kind']);
            $modes = $leg === 'intake' ? ['customer_dropoff','courier_pickup'] : ['customer_collection','courier_delivery'];
            if (!in_array($input[$leg]['kind'],$modes,true)) Domain::fail('INVALID_REQUEST', 'Choose a valid intake and return mode.');
            if ($input['booking_mode'] === 'scheduled') {
                $slot = Domain::text($input[$leg]['slot_id'] ?? '',64); $found = false;
                foreach (Store::slots($input[$leg]['kind']) as $s) if ($s['slot_id'] === $slot && $s['available_jobs'] > 0) $found = true;
                if (!$found) Domain::fail('SLOT_UNAVAILABLE', 'Choose an available handoff window.',409);
            } elseif (isset($input[$leg]['slot_id'])) Domain::fail('INVALID_REQUEST', 'Unscheduled services do not have confirmed windows.');
            if (str_starts_with($input[$leg]['kind'],'courier_')) {
                $available = array_column(Settings::get()['transport'],'kind');
                if (!in_array($input[$leg]['kind'],$available,true)) Domain::fail('OUT_OF_AREA','This courier service is unavailable.');
                $input[$leg]['address'] = self::address($input[$leg]['address'] ?? [],$input['postal_code']);
                if (apply_filters('krev_agent_address_verified', false, $input[$leg]['address'],$input[$leg]['kind']) !== true) Domain::fail('ADDRESS_REVIEW_REQUIRED','This courier address needs merchant verification before quoting transport.');
            } elseif (isset($input[$leg]['address'])) Domain::fail('INVALID_REQUEST','Customer handoff uses the configured merchant location.');
        }
        if ($input['booking_mode'] === 'scheduled') {
            $times = [];
            foreach (Store::slots() as $s) $times[$s['slot_id']] = strtotime($s['start_at']);
            if ($times[$input['return']['slot_id']] <= $times[$input['intake']['slot_id']]) Domain::fail('INVALID_REQUEST','The return window must follow intake.');
        }
        if (isset($input['customer'])) {
            Domain::fields($input['customer'],['name','email','phone','billing'],['name','email','billing']);
            $input['customer']['name'] = Domain::text($input['customer']['name'],100);
            $input['customer']['email'] = Domain::text($input['customer']['email'],254);
            if (!$input['customer']['name'] || !is_email($input['customer']['email'])) Domain::fail('INVALID_REQUEST','A name and valid receipt email are required.');
            if (isset($input['customer']['phone'])) $input['customer']['phone'] = Domain::text($input['customer']['phone'],30);
            $input['customer']['billing'] = self::address($input['customer']['billing'],$input['postal_code']);
        }
        $input['source'] = Domain::text($input['source'] ?? 'kniferevive-concierge',80);
        usort($input['items'],static fn($a,$b) => $a['product_id'] <=> $b['product_id']);
        return $input;
    }
    private static function address(mixed $address, string $postal): array {
        if (!is_array($address)) Domain::fail('INVALID_REQUEST','Invalid address.');
        Domain::fields($address,['address_1','address_2','city','state','postcode','country'],['address_1','city','state','postcode','country']);
        foreach ($address as $k=>$v) $address[$k] = Domain::text($v,150);
        if (!$address['address_1'] || !$address['city'] || $address['country'] !== 'US' || $address['state'] !== 'CA' || $address['postcode'] !== $postal) Domain::fail('INVALID_REQUEST','Use a complete California address matching the quote postal code.');
        return $address;
    }
    public static function price(array $input): array {
        if (!class_exists(QuoteCart::class, false)) require_once __DIR__ . '/QuoteCart.php';
        $input = self::normalize($input);
        $wc = WC(); $saved = [$wc->cart,$wc->session,$wc->customer];
        $customer = new \WC_Customer();
        $billing = $input['customer']['billing'] ?? ['country'=>'US','state'=>'CA','postcode'=>$input['postal_code'],'city'=>''];
        foreach (['country','state','postcode','city','address_1','address_2'] as $field) {
            $customer->{'set_billing_' . $field}($billing[$field] ?? '');
            $customer->{'set_shipping_' . $field}($billing[$field] ?? '');
        }
        $customer->set_calculated_shipping(true);
        $session = new QuoteSession();
        $session->set('chosen_payment_method',$input['rail'] === 'lightning' ? 'krev_lightning' : 'stripe');
        $cart = new QuoteCart(); $wc->cart = $cart; $wc->session = $session; $wc->customer = $customer;
        $transport = static function ($active) use ($cart,$input) {
            if ($active !== $cart) return;
            $settings=Settings::get(); $fees=array_column($settings['transport'],null,'kind');
            if ($input['intake']['kind']==='courier_pickup' && $input['return']['kind']==='courier_delivery' && $settings['transport_round_trip_minor'] !== null) {
                // Allocate the rounding cent deterministically; one-way prices stay unchanged.
                $fees['courier_pickup']['fee_minor']=min($fees['courier_pickup']['fee_minor'],$settings['transport_round_trip_minor']);
                $fees['courier_delivery']['fee_minor']=$settings['transport_round_trip_minor']-$fees['courier_pickup']['fee_minor'];
            }
            foreach (['intake','return'] as $leg) {
                $fee=$fees[$input[$leg]['kind']]??null;
                if ($fee) $cart->add_fee($leg === 'intake' ? 'Sharpening courier pickup' : 'Sharpening return delivery',Domain::decimal($fee['fee_minor']),$fee['taxable'],$fee['tax_class']);
            }
        };
        add_action('woocommerce_cart_calculate_fees',$transport,20);
        try {
            foreach ($input['items'] as $item) {
                $p = wc_get_product($item['product_id']); $key = (string)$p->get_id();
                // Direct isolated contents avoid add-to-cart persistence and stock reservation side effects.
                $cart->cart_contents[$key] = ['key'=>$key,'product_id'=>$p->get_id(),'variation_id'=>0,'variation'=>[],'quantity'=>$item['quantity'],'data'=>clone $p,'data_hash'=>wc_get_cart_item_data_hash($p)];
            }
            $cart->calculate_totals();
            $lines = [];
            foreach ($cart->get_cart() as $line) $lines[] = ['product_id'=>$line['product_id'],'quantity'=>$line['quantity'],'name'=>wp_strip_all_tags($line['data']->get_name()),'tax_class'=>$line['data']->get_tax_class(),
                'subtotal'=>$line['line_subtotal'],'total'=>$line['line_total'],'subtotal_tax'=>$line['line_subtotal_tax'],'total_tax'=>$line['line_tax'],'taxes'=>$line['line_tax_data']];
            $fees = [];
            foreach ($cart->get_fees() as $fee) $fees[] = ['name'=>wp_strip_all_tags($fee->name),'amount'=>$fee->amount,'total'=>$fee->total,'tax'=>$fee->tax,'taxes'=>$fee->tax_data,'taxable'=>$fee->taxable,'tax_class'=>$fee->tax_class];
            $total = Domain::cents(wc_format_decimal($cart->get_total('edit'),2));
            if ($total < 1 || $total > Settings::get()['max_minor']) Domain::fail('BUDGET_EXCEEDED','This total exceeds the merchant checkout limit.');
            $q = ['input'=>$input,'currency'=>'USD','items'=>$lines,'fees'=>$fees,'tax_minor'=>Domain::cents(wc_format_decimal($cart->get_total_tax(),2)),
                'total_minor'=>$total,'discount_minor'=>Domain::cents(wc_format_decimal($cart->get_discount_total(),2)), 'rail'=>$input['rail'],
                'booking_mode'=>$input['booking_mode'],'policy_version'=>Settings::get()['policy_version'],'policy_url'=>Settings::get()['policy_url'],'location'=>Settings::get()['location'],
                'settings_hash'=>Domain::digest(Settings::get()),'requires_customer'=>!isset($input['customer']),'quote_version'=>1,'requires_manual_assessment'=>false];
            $q['quote_hash'] = Domain::digest($q);
            return $q;
        } finally {
            remove_action('woocommerce_cart_calculate_fees',$transport,20);
            [$wc->cart,$wc->session,$wc->customer] = $saved;
        }
    }
    public static function quote(array $input, string $owner, string $key): array {
        // Idempotency stores the priced result once; discovery has no commerce mutation.
        return Store::idempotent($owner,'quote',$key,$input,static function () use ($input,$owner) {
            $q = self::price($input); $id = Domain::id(); $expiry = time()+600;
            Store::put($id,'quote',$owner,$expiry,$q); return Store::get($id);
        });
    }
    public static function publicQuote(array $row): array {
        $q = $row['data'];
        return ['quote_id'=>$row['id'],'expires_at'=>gmdate('c',(int)$row['expires']),'quote_version'=>$q['quote_version'],'quote_hash'=>$q['quote_hash'],
            'currency'=>$q['currency'],'items'=>array_map(static fn($i) => ['product_id'=>$i['product_id'],'name'=>$i['name'],'quantity'=>$i['quantity'],'subtotal_minor'=>Domain::cents(wc_format_decimal($i['subtotal'],2)),'total_minor'=>Domain::cents(wc_format_decimal($i['total'],2))],$q['items']),
            'fees'=>array_map(static fn($f) => ['name'=>$f['name'],'total_minor'=>Domain::cents(wc_format_decimal($f['total'],2))],$q['fees']),
            'tax_minor'=>$q['tax_minor'],'discount_minor'=>$q['discount_minor'],'total_minor'=>$q['total_minor'],'rail'=>$q['rail'],'booking_mode'=>$q['booking_mode'],
            'intake'=>$q['input']['intake'],'return'=>$q['input']['return'],'location'=>$q['location'],'policy_url'=>$q['policy_url'],'policy_version'=>$q['policy_version'],
            'requires_customer'=>$q['requires_customer'],'requires_manual_assessment'=>false,'return_policy_url'=>Settings::get()['return_policy_url'] ?: null];
    }
    public static function grant(string $quoteId, string $owner): string {
        return Store::lock('quote:' . $quoteId,static function () use ($quoteId,$owner) {
            $q = Store::get($quoteId,'quote',$owner);
            if ((int)$q['expires'] < time()) Domain::fail('QUOTE_EXPIRED','Obtain a fresh quote.',409);
            if ($q['data']['requires_customer']) Domain::fail('CUSTOMER_REQUIRED','Complete the private contact form before approval.');
            $fresh = self::price($q['data']['input']);
            if (!hash_equals($q['data']['quote_hash'],$fresh['quote_hash'])) Domain::fail('QUOTE_CHANGED','The quote changed. Obtain and review a fresh quote.',409);
            if (!empty($q['data']['consent_id'])) return $q['data']['consent_id'];
            $id = Domain::id();
            Store::put($id,'consent',$owner,(int)$q['expires'],['quote_id'=>$quoteId,'quote_hash'=>$fresh['quote_hash'],'consumed'=>false,'approved_at'=>time()]);
            $q['data']['consent_id']=$id; Store::update($quoteId,$q['data']); return $id;
        });
    }
    public static function attempt(array $input, string $owner, string $key): array {
        Domain::fields($input,['quote_id','quote_hash','consent_id'],['quote_id','quote_hash','consent_id']);
        foreach (['quote_id','consent_id'] as $id) if (!Domain::validId($input[$id])) Domain::fail('INVALID_REQUEST','Invalid resource reference.');
        return Store::lock('quote:' . $input['quote_id'], static function () use ($input,$owner,$key) {
            $q = Store::get($input['quote_id'],'quote',$owner);
            $purchase=$q['data']['input']; unset($purchase['rail'],$purchase['source'],$purchase['customer'],$purchase['requires_assessment']);
            $purchaseHash=Domain::digest($purchase);
            return Store::lock('purchase:'.Domain::digest([$owner,$purchaseHash]),static function () use ($input,$owner,$key,$q,$purchaseHash) {
            if (!is_string($input['quote_hash']) || !hash_equals($q['data']['quote_hash'],$input['quote_hash'])) Domain::fail('QUOTE_CHANGED','The quote does not match.',409);
            $consent = Store::get($input['consent_id'],'consent',$owner);
            if (($consent['data']['quote_id'] ?? '') !== $q['id'] || !hash_equals($consent['data']['quote_hash'],$input['quote_hash'])) Domain::fail('AUTHORIZATION_REQUIRED','Approval does not cover this quote.',403);
            $row = Store::idempotent($owner,'checkout',$key,$input,static function () use ($q,$owner,$consent,$purchaseHash) {
                if (!empty($q['data']['attempt_id'])) return Store::get($q['data']['attempt_id'],'attempt',$owner);
                if (Store::unresolvedPurchase($owner,$purchaseHash)) Domain::fail('PAYMENT_UNRESOLVED','An equivalent purchase already has an unresolved payment. Check the original attempt before another checkout or payment method.',409);
                if ((int)$q['expires'] < time() || (int)$consent['expires'] < time() || $consent['data']['consumed']) Domain::fail('AUTHORIZATION_REQUIRED','Approval expired or was used.',403);
                $fresh = self::price($q['data']['input']);
                if (!hash_equals($fresh['quote_hash'],$q['data']['quote_hash'])) Domain::fail('QUOTE_CHANGED','Obtain and review a fresh quote.',409);
                $id=Domain::id(); $holdExpiry=time()+($fresh['rail']==='stripe_checkout' ? 2400 : 1200);
                $legs=[];
                if ($fresh['booking_mode']==='scheduled') foreach (['intake','return'] as $leg) $legs[]=$fresh['input'][$leg];
                Store::hold($legs,$id,$holdExpiry);
                $a=['id'=>$id,'purchase_hash'=>$purchaseHash,'quote'=>$fresh,'quote_id'=>$q['id'],'order_id'=>0,'provider_id'=>'','payment_state'=>'creating','booking_state'=>$legs ? 'held' : 'pending_scheduling',
                    'hold_expires'=>$holdExpiry,'created_at'=>time(),'checked_at'=>0,'environment'=>Settings::get()['stripe_live'] ? 'live' : 'test'];
                Store::put($id,'attempt',$owner,time()+7*86400,$a);
                $qd=$q['data']; $qd['attempt_id']=$id; Store::update($q['id'],$qd);
                $cd=$consent['data']; $cd['consumed']=true; Store::update($consent['id'],$cd);
                return Store::get($id);
            });
            Payments::start($row['id']); return Store::get($row['id'],'attempt',$owner);
            });
        });
    }
    /** Called under the attempt lock. Uncertain prior order creation is never retried blindly. */
    public static function order(array &$a): \WC_Order {
        if ($a['order_id']) {
            $o=wc_get_order($a['order_id']); if (!$o) Domain::fail('MANUAL_REVIEW_REQUIRED','Order unavailable.',409); return $o;
        }
        if (!empty($a['order_creation_started'])) {
            $found=wc_get_orders(['limit'=>2,'meta_key'=>'_krev_agent_attempt','meta_value'=>$a['id']]);
            if (count($found)!==1) Domain::fail('MANUAL_REVIEW_REQUIRED','An interrupted order requires operator reconciliation.',409);
            $a['order_id']=$found[0]->get_id(); Store::update($a['id'],$a); return $found[0];
        }
        $a['order_creation_started']=true; Store::update($a['id'],$a);
        $q=$a['quote']; $o=new \WC_Order(); $o->set_status('pending'); $o->set_currency('USD'); $o->set_created_via('kniferevive-agent');
        $o->set_payment_method($q['rail']==='lightning' ? 'krev_lightning' : 'krev_agent_checkout');
        $o->set_payment_method_title($q['rail']==='lightning' ? 'Bitcoin Lightning' : 'Stripe hosted Checkout');
        $c=$q['input']['customer']; $o->set_billing_first_name($c['name']); $o->set_billing_email($c['email']); $o->set_billing_phone($c['phone']??'');
        foreach ($c['billing'] as $field=>$value) $o->{'set_billing_'.$field}($value);
        foreach ($q['items'] as $line) {
            $item=new \WC_Order_Item_Product(); $item->set_product(wc_get_product($line['product_id'])); $item->set_quantity($line['quantity']);
            $item->set_subtotal($line['subtotal']); $item->set_total($line['total']); $item->set_tax_class($line['tax_class']); $item->set_taxes($line['taxes']); $o->add_item($item);
        }
        foreach ($q['fees'] as $fee) {
            $item=new \WC_Order_Item_Fee(); $item->set_name($fee['name']); $item->set_amount($fee['amount']); $item->set_total($fee['total']); $item->set_tax_class($fee['tax_class']);
            $item->set_tax_status($fee['taxable']?'taxable':'none'); $item->set_taxes(['total'=>$fee['taxes']]); $o->add_item($item);
        }
        $o->set_total(Domain::decimal($q['total_minor'])); $o->set_cart_tax(Domain::decimal($q['tax_minor']));
        $o->update_meta_data('_krev_agent_attempt',$a['id']); $o->update_meta_data('_krev_agent_quote_hash',$q['quote_hash']);
        $o->update_meta_data('_krev_agent_source',$q['input']['source']); $o->update_meta_data('_krev_agent_booking_state',$a['booking_state']);
        $o->update_meta_data('_krev_agent_handoff',$q['input']['intake']); $o->update_meta_data('_krev_agent_return',$q['input']['return']);
        $o->update_meta_data('_krev_sharpening_handoff',$q['input']['intake']['kind'].' / '.$q['input']['return']['kind']);
        $o->save(); $a['order_id']=$o->get_id(); Store::update($a['id'],$a);
        $o->update_taxes(); $o->save();
        if (class_exists('KREV_Sharpening_Orders')) \KREV_Sharpening_Orders::initialize($o);
        return $o;
    }
    public static function status(array $row): array {
        $a=$row['data']; $o=$a['order_id'] ? wc_get_order($a['order_id']) : null;
        return ['attempt_id'=>$row['id'],'order_reference'=>$row['id'],'payment_state'=>$a['payment_state'],'booking_state'=>$a['booking_state'],
            'rail'=>$a['quote']['rail'],'currency'=>'USD','total_minor'=>$a['quote']['total_minor'],'hold_expires_at'=>gmdate('c',$a['hold_expires']),
            'checkout_url'=>$a['checkout_url']??null,'invoice'=>$a['invoice']??null,'next_action'=>in_array($a['payment_state'],['unknown','review_required'],true)?'wait_or_contact_merchant':($a['payment_state']==='paid'?'follow_handoff_instructions':'complete_payment'),
            'intake'=>$a['quote']['input']['intake'],'return'=>$a['quote']['input']['return'],'location'=>$a['quote']['location'],
            'fulfillment_stage'=>$o ? ($o->get_meta('_krev_sharpening_stage') ?: 'handoff') : null,'woocommerce_status'=>$o ? $o->get_status() : null,
            'environment'=>$a['quote']['rail']==='lightning'?'live':$a['environment'],'status_url'=>rest_url('kniferevive-agent/v1/checkout-attempts/'.$row['id'])];
    }
}
