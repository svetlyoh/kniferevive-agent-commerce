<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;
final class Payments {
    private static function failed(array &$a, \Throwable $e): void {
        $a['payment_state']=$e instanceof Fault && $e->codeName==='MANUAL_REVIEW_REQUIRED' ? 'review_required' : 'unknown';
        $a['failures']=($a['failures']??0)+1; $a['checked_at']=time();
        $a['last_error']=$e instanceof Fault ? $e->codeName : 'INTERNAL_ERROR';
        $a['next_check_at']=time()+min(3600,15*(2**min(8,$a['failures'])));
        $a['auto_paused']=$a['payment_state']==='review_required' || $a['failures']>=8;
        Store::update($a['id'],$a);
    }
    public static function stripe(string $method, string $path, array $body, bool $live, string $key = ''): array {
        if (!preg_match('#^/v1/(?:checkout/sessions(?:/cs_(?:test_|live_)[A-Za-z0-9]+(?:/expire)?)?|refunds)$#D',$path)) Domain::fail('INVALID_REQUEST','Unsupported processor operation.');
        $secret=Settings::stripeKey($live);
        if (!$secret || !str_starts_with($secret,$live?'sk_live_':'sk_test_')) Domain::fail('PAYMENT_METHOD_UNAVAILABLE','Processor credentials are not configured.',503);
        $headers=['Authorization'=>'Bearer '.$secret,'Content-Type'=>'application/x-www-form-urlencoded','Stripe-Version'=>'2025-03-31.basil'];
        if ($key) $headers['Idempotency-Key']=$key;
        $url='https://api.stripe.com'.$path;
        if ($method==='GET' && $body) $url.='?'.http_build_query($body);
        $r=wp_remote_request($url,['method'=>$method,'headers'=>$headers,'body'=>$method==='GET'?null:http_build_query($body),
            'timeout'=>12,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>262144]);
        if (is_wp_error($r)) Domain::fail('PAYMENT_STATUS_UNKNOWN','Processor status is temporarily unavailable.',503,true);
        $status=wp_remote_retrieve_response_code($r);
        if ($status<200 || $status>=300) Domain::fail('PAYMENT_STATUS_UNKNOWN','Processor request could not be verified.',503,true);
        $result=json_decode(wp_remote_retrieve_body($r),true,64,JSON_THROW_ON_ERROR);
        if (!is_array($result)) Domain::fail('PAYMENT_STATUS_UNKNOWN','Invalid processor response.',503,true);
        return $result;
    }
    public static function fingerprint(\WC_Order $o): string {
        $lines=[];
        foreach ($o->get_items() as $item) $lines[]=[$item->get_product_id(),$item->get_quantity(),(string)$item->get_total(),$item->get_taxes()];
        $fees=[]; foreach ($o->get_fees() as $item) $fees[]=[$item->get_name(),(string)$item->get_total(),$item->get_taxes()];
        return Domain::digest([$o->get_currency(),Domain::cents($o->get_total()),$o->get_payment_method(),$lines,$fees,$o->get_billing_postcode()]);
    }
    private static function reserved(\WC_Order $o): bool {
        global $wpdb;
        foreach ($o->get_items() as $item) {
            $p=$item->get_product(); if (!$p) return false;
            if (!$p->managing_stock() || $p->backorders_allowed()) continue;
            $qty=$wpdb->get_var($wpdb->prepare("SELECT stock_quantity FROM {$wpdb->prefix}wc_reserved_stock WHERE order_id=%d AND product_id=%d AND expires>NOW()",$o->get_id(),$p->get_stock_managed_by_id()));
            if ($qty===null || (float)$qty<$item->get_quantity()) return false;
        }
        return true;
    }
    public static function start(string $id): void {
        Store::lock('attempt:'.$id,static function () use ($id) {
            $a=Store::get($id,'attempt')['data'];
            if (!in_array($a['payment_state'],['creating','unknown'],true)) return;
            try {
                $o=Commerce::order($a);
                if (empty($a['order_fingerprint'])) { $a['order_fingerprint']=self::fingerprint($o); Store::update($id,$a); }
                if (!hash_equals($a['order_fingerprint'],self::fingerprint($o)) || !$o->has_status('pending')) Domain::fail('MANUAL_REVIEW_REQUIRED','Order changed before payment.',409);
                if ($a['quote']['rail']==='lightning') self::startLightning($a,$o);
                else self::startStripe($a,$o);
            } catch (\Throwable $e) {
                self::failed($a,$e);
            }
        });
    }
    private static function startStripe(array &$a, \WC_Order $o): void {
        if ($a['provider_id']) return;
        // Never reuse a create key beyond Stripe's retention window. Operator reconciliation then applies.
        if (time()-$a['created_at']>23*3600 || time()>$a['hold_expires']-120) Domain::fail('MANUAL_REVIEW_REQUIRED','The creation window expired; reconcile the original attempt.',409);
        $body=$a['provider_request']??null;
        (new \Automattic\WooCommerce\Checkout\Helpers\ReserveStock())->reserve_stock_for_order($o,max(1,(int)ceil(($a['hold_expires']-time())/60)));
        if (!self::reserved($o)) Domain::fail('MANUAL_REVIEW_REQUIRED','Stock reservation could not be established before payment.',409);
        if ($body===null) {
            $items=[]; $sum=0;
            foreach ($a['quote']['items'] as $line) {
                $minor=Domain::cents(wc_format_decimal($line['total'],2)); $sum+=$minor;
                if ($minor) $items[]=['price_data'=>['currency'=>'usd','unit_amount'=>$minor,'product_data'=>['name'=>$line['name'].' × '.$line['quantity']]],'quantity'=>1];
            }
            foreach ($a['quote']['fees'] as $fee) {
                $minor=Domain::cents(wc_format_decimal($fee['total'],2)); $sum+=$minor;
                if ($minor) $items[]=['price_data'=>['currency'=>'usd','unit_amount'=>$minor,'product_data'=>['name'=>$fee['name']]],'quantity'=>1];
            }
            if ($a['quote']['tax_minor']) { $sum+=$a['quote']['tax_minor']; $items[]=['price_data'=>['currency'=>'usd','unit_amount'=>$a['quote']['tax_minor'],'product_data'=>['name'=>'Applicable taxes']], 'quantity'=>1]; }
            if ($sum!==$a['quote']['total_minor']) Domain::fail('MANUAL_REVIEW_REQUIRED','Quote line totals need review.',409);
            $body=['mode'=>'payment','payment_method_types'=>['card'],'line_items'=>$items,'customer_email'=>$o->get_billing_email(),
                'client_reference_id'=>$a['id'],'metadata'=>['attempt_id'=>$a['id'],'quote_hash'=>$a['quote']['quote_hash']],
                'payment_intent_data'=>['metadata'=>['attempt_id'=>$a['id']]],'expires_at'=>time()+2100,
                'success_url'=>Frontend::statusUrl($a['id']),'cancel_url'=>Frontend::statusUrl($a['id'])];
            $a['provider_request']=$body; Store::update($a['id'],$a);
        }
        $session=self::stripe('POST','/v1/checkout/sessions',$body,$a['environment']==='live','krev-agent-'.$a['id']);
        if (!is_string($session['id']??null) || !preg_match('/^cs_(?:test_|live_)[A-Za-z0-9]+$/D',$session['id']) || !Domain::httpsHost($session['url']??'','checkout.stripe.com')) Domain::fail('MANUAL_REVIEW_REQUIRED','Checkout destination could not be verified.',409);
        $a['provider_id']=$session['id'];
        Domain::settledSession($session,$a,$a['environment']==='live');
        $a['checkout_url']=$session['url']; $a['payment_state']='pending'; $a['checked_at']=time(); $a['failures']=0; $a['auto_paused']=false; $a['next_check_at']=time()+60; Store::update($a['id'],$a);
        $o->update_meta_data('_krev_agent_checkout_session',$session['id']); $o->save();
    }
    private static function startLightning(array &$a, \WC_Order $o): void {
        if (!class_exists('KnifeRevive\\Lightning\\Coordinator')) Domain::fail('PAYMENT_METHOD_UNAVAILABLE','Lightning adapter unavailable.',503);
        if (time()>$a['hold_expires'] && !\KnifeRevive\Lightning\Repository::for_order($o->get_id())) Domain::fail('MANUAL_REVIEW_REQUIRED','The reservation expired before invoice creation.',409);
        // A fee change after approval is a re-quote requirement, never a new amount hidden in the invoice.
        if (class_exists('KnifeRevive\\Lightning\\FeeWaiver')) {
            foreach ($o->get_fees() as $fee) if (\KnifeRevive\Lightning\FeeWaiver::is_marketplace_fee($fee)) Domain::fail('MANUAL_REVIEW_REQUIRED','Lightning fee repricing is required.',409);
        }
        $row=\KnifeRevive\Lightning\Coordinator::create($o->get_id());
        if (!$row) Domain::fail('PAYMENT_STATUS_UNKNOWN','Invoice creation is unverified.',503,true);
        $a['provider_id']=$row->uuid; self::lightningDisplay($a,$row); Store::update($a['id'],$a);
    }
    private static function lightningDisplay(array &$a, $row): void {
        $payload=json_decode($row->payload,true);
        if (!empty($payload['bolt11'])) {
            $a['invoice']=['bolt11'=>$payload['bolt11'],'amount_sat'=>$payload['amount_sat']??null,'expires_at'=>$payload['expires_at']??null,
                'payment_hash'=>$payload['payment_hash']??null,'network'=>'bitcoin_mainnet','fiat_minor'=>$a['quote']['total_minor'],
                'merchant_origin'=>'https://kniferevive.com','rate'=>$payload['rate']??null,'rate_locked_at'=>isset($payload['quote_at'])?gmdate('c',(int)$payload['quote_at']):null];
        }
        $a['payment_state']=match($row->state) {
            'settled'=>'paid','expired','expired-unpaid'=>'expired','review-required'=>'review_required',
            'creating','creation-unknown','verifying'=>'unknown', 'awaiting-payment'=>'pending', default=>'review_required'
        };
        $a['checked_at']=time(); $a['next_check_at']=time()+60;
    }
    public static function reconcile(string $id): void {
        Store::lock('attempt:'.$id,static function () use ($id) {
            $a=Store::get($id,'attempt')['data'];
            if (time()-$a['checked_at']<5) return;
            try {
                $o=$a['order_id'] ? wc_get_order($a['order_id']) : null;
                if (!$o) Domain::fail('MANUAL_REVIEW_REQUIRED','Order creation needs reconciliation.',409);
                if (!$a['provider_id']) {
                    $a['payment_state']='unknown'; $a['checked_at']=time();
                    if ($a['hold_expires']<time()) { Store::release($id); if ($a['booking_state']==='held') $a['booking_state']='expired'; }
                    Store::update($id,$a); return;
                }
                if ($a['quote']['rail']==='lightning') {
                    $row=\KnifeRevive\Lightning\Repository::by_uuid($a['provider_id']);
                    if (!$row) Domain::fail('MANUAL_REVIEW_REQUIRED','Invoice record unavailable.',409);
                    \KnifeRevive\Lightning\Coordinator::reconcile($row);
                    $row=\KnifeRevive\Lightning\Repository::by_uuid($a['provider_id']); self::lightningDisplay($a,$row);
                    $o=wc_get_order($a['order_id']);
                    if ($a['payment_state']==='paid') {
                        $payload=json_decode($row->payload,true);
                        if (!$o->is_paid() || $o->get_transaction_id()!==($payload['payment_hash']??'') || !hash_equals($a['order_fingerprint'],self::fingerprint($o))) Domain::fail('MANUAL_REVIEW_REQUIRED','Settlement requires operator review.',409);
                        self::book($a,$o);
                    }
                } else {
                    $session=self::stripe('GET','/v1/checkout/sessions/'.$a['provider_id'],['expand'=>['payment_intent.latest_charge']],$a['environment']==='live');
                    $intent=Domain::settledSession($session,$a,$a['environment']==='live');
                    if ($intent) {
                        if (!hash_equals($a['order_fingerprint'],self::fingerprint($o)) || !hash_equals((string)$o->get_meta('_krev_agent_quote_hash'),$a['quote']['quote_hash'])) Domain::fail('MANUAL_REVIEW_REQUIRED','Order no longer matches the paid quote.',409);
                        $charge=$session['payment_intent']['latest_charge']??null;
                        if (!is_array($charge) || ($charge['currency']??'')!=='usd' || ($charge['amount']??null)!==$a['quote']['total_minor']) Domain::fail('MANUAL_REVIEW_REQUIRED','Charge could not be verified.',409);
                        $fullyRefunded=$o->has_status('refunded') && ($charge['amount_refunded']??null)===$a['quote']['total_minor'];
                        if (!$o->is_paid() && !$fullyRefunded) {
                            if (!$o->has_status('pending') || $o->get_total_refunded()>0 || !self::reserved($o)) Domain::fail('MANUAL_REVIEW_REQUIRED','Paid order has an unexpected state or expired stock reservation.',409);
                            // Persist evidence first, then one idempotent WooCommerce completion path.
                            $a['verified_intent']=$intent; Store::update($id,$a);
                            $o->update_meta_data('_krev_agent_payment_intent',$intent); $o->save(); $o->payment_complete($intent);
                        }
                        if ($o->get_transaction_id()!==$intent) Domain::fail('MANUAL_REVIEW_REQUIRED','Order payment reference differs.',409);
                        $a['payment_state']='paid';
                        self::refundEvidence($a,$o,$charge);
                        if ($a['payment_state']==='paid') self::book($a,$o);
                        if (!empty($charge['disputed'])) {
                            $a['payment_state']='review_required'; $o->update_meta_data('_krev_agent_dispute_review','yes'); $o->save();
                            if ($o->has_status('processing')) $o->update_status('on-hold','Agent checkout dispute requires operator review.');
                        }
                    } else {
                        $a['payment_state']=($session['status']??'')==='expired'?'expired':'pending';
                    }
                }
                if ($a['payment_state']==='expired' || ($a['payment_state']!=='paid' && $a['hold_expires']<time())) {
                    Store::release($id);
                    if ($a['booking_state']==='held') $a['booking_state']='expired';
                }
                $a['checked_at']=time(); $a['failures']=0; $a['auto_paused']=$a['payment_state']==='review_required';
                $a['next_check_at']=time()+(in_array($a['payment_state'],['paid','refunded'],true)?3600:60);
                Store::update($id,$a);
            } catch (\Throwable $e) {
                self::failed($a,$e);
            }
        });
    }
    private static function book(array &$a, \WC_Order $o): void {
        if ($a['booking_state']==='confirmed') return;
        if ($a['quote']['booking_mode']==='pending_scheduling') { $a['booking_state']='pending_scheduling'; return; }
        $a['booking_state']=Store::confirm($a['id'],time())?'confirmed':'pending_scheduling';
        $o->update_meta_data('_krev_agent_booking_state',$a['booking_state']); $o->save();
        if ($a['booking_state']!=='confirmed' && empty($a['late_payment_noted'])) {
            $o->add_order_note('Payment verified after appointment hold expiry. Contact customer to arrange a new appointment or refund.'); $a['late_payment_noted']=true;
        }
    }
    private static function refundEvidence(array &$a, \WC_Order $o, array $charge): void {
        $refunded=Domain::integer($charge['amount_refunded']??0,0,$a['quote']['total_minor']);
        $recorded=Domain::cents(wc_format_decimal($o->get_total_refunded(),2));
        if ($refunded>$recorded) {
            $refund=wc_create_refund(['order_id'=>$o->get_id(),'amount'=>Domain::decimal($refunded-$recorded),'reason'=>'Verified Stripe Checkout refund','refund_payment'=>false,'restock_items'=>false]);
            if (is_wp_error($refund)) Domain::fail('MANUAL_REVIEW_REQUIRED','Refund accounting needs review.',409);
        }
        if ($refunded===$a['quote']['total_minor']) { $a['payment_state']='refunded'; $a['booking_state']='cancelled'; Store::release($a['id']); }
        elseif ($refunded) $a['payment_state']='review_required';
    }
    public static function webhook(string $body, string $signature): array {
        $environment=null;
        foreach ([false,true] as $live) if (Domain::stripeSignature($body,$signature,Settings::webhookSecret($live),time())) { $environment=$live; break; }
        if ($environment===null) Domain::fail('INVALID_SIGNATURE','Webhook signature rejected.',400);
        $event=json_decode($body,true,64,JSON_THROW_ON_ERROR);
        if (($event['livemode']??null)!==$environment || !preg_match('/^evt_[A-Za-z0-9]+$/D',$event['id']??'')) Domain::fail('INVALID_SIGNATURE','Invalid event environment.',400);
        $obj=$event['data']['object']??[]; $attempt=$obj['metadata']['attempt_id']??'';
        // Charge events may not retain PI metadata. Lookup by verified linked intent, never a customer ID.
        if (!Domain::validId($attempt) && isset($obj['payment_intent'])) {
            $attempt=Store::attemptForIntent((string)$obj['payment_intent'])??'';
        }
        if (!Domain::validId($attempt)) return ['received'=>true];
        $a=Store::get($attempt,'attempt')['data'];
        if (($a['environment']==='live')!==$environment) Domain::fail('INVALID_SIGNATURE','Event does not match attempt environment.',400);
        // Persist receipt before processing. Retries still reconcile if a prior delivery crashed.
        $eventId=Domain::digest(['stripe-event',$event['id']]);
        try { Store::get($eventId,'event'); } catch (Fault $e) { if ($e->codeName!=='NOT_FOUND') throw $e; Store::put($eventId,'event',$attempt,time()+30*86400,['event_id'=>$event['id']]); }
        self::reconcile($attempt); return ['received'=>true];
    }
    public static function refund(int $orderId, mixed $amount, string $reason): bool|\WP_Error {
        try {
            $o=wc_get_order($orderId); $id=$o ? $o->get_meta('_krev_agent_attempt') : '';
            if (!Domain::validId($id) || !current_user_can('manage_woocommerce')) Domain::fail('FORBIDDEN','Refund permission required.',403);
            return Store::lock('attempt:'.$id,static function () use ($id,$o,$amount,$reason) {
                $a=Store::get($id,'attempt')['data'];
                if ($a['quote']['rail']!=='stripe_checkout' || empty($a['verified_intent'])) Domain::fail('MANUAL_REVIEW_REQUIRED','Refund through the merchant payment workflow.',409);
                $minor=Domain::cents(wc_format_decimal($amount,2)); if (!$minor) Domain::fail('INVALID_AMOUNT','A positive refund is required.');
                $key='krev-agent-refund-'.Domain::digest([$id,$minor,(string)$o->get_total_refunded()]);
                if (!empty($a['refund_request']) && $a['refund_request']['state']!=='succeeded' && ($a['refund_request']['key']!==$key || $a['refund_request']['amount_minor']!==$minor)) Domain::fail('MANUAL_REVIEW_REQUIRED','Resolve the original refund before requesting another.',409);
                $a['refund_request']=['key'=>$key,'amount_minor'=>$minor,'state'=>'unknown']; Store::update($id,$a);
                $response=self::stripe('POST','/v1/refunds',['payment_intent'=>$a['verified_intent'],'amount'=>$minor],$a['environment']==='live',$key);
                if (($response['status']??'')!=='succeeded') Domain::fail('PAYMENT_STATUS_UNKNOWN','Refund is pending; verify its processor status before retrying.',503);
                $a['refund_request']['state']='succeeded'; $a['refund_request']['provider_id']=$response['id']??''; Store::update($id,$a);
                $o->add_order_note('Verified Stripe hosted checkout refund: '.sanitize_text_field($response['id']??'')); return true;
            });
        } catch (\Throwable $e) { return new \WP_Error('krev_agent_refund','Refund could not be verified. Check the original processor request before retrying.'); }
    }
}
