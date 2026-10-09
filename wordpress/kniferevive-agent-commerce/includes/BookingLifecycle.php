<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Native gateways own money. Booking receipts report ledger facts, never bank arrival. */
final class BookingLifecycle {
    public static function boot(): void {
        add_filter('woocommerce_cart_shipping_packages',static function($packages){
            $id=WC()->session?WC()->session->get('krev_booking_id'):null;
            if($id)foreach($packages as &$package)$package['krev_service_booking']=$id;
            return $packages;
        },100);
        add_filter('woocommerce_package_rates',[self::class,'serviceRates'],100,2);
        add_filter('woocommerce_shipping_package_name',static function($name){return WC()->session?->get('krev_booking_id')?'Pickup & return plan':$name;},100);
        add_filter('woocommerce_cart_shipping_method_full_label',static function($label,$method){
            if($method->get_id()!=='krev_booking_local_pickup')return $label;
            try{$id=WC()->session?->get('krev_booking_id');return $id?esc_html(self::handoffLabel(Store::get($id,'booking')['data']['input'])):$label;}catch(\Throwable $e){return $label;}
        },100,2);
        add_filter('woocommerce_order_shipping_to_display',static function($label,$order){
            try{$id=(string)($order->get_meta('_krev_service_booking')?:$order->get_meta('_krev_unpaid_booking'));if(!Domain::validId($id))return $label;
                $booking=Store::get($id,'booking');if(self::order($booking)?->get_id()!==$order->get_id())return $label;
                return esc_html(self::handoffLabel($booking['data']['input']));
            }catch(\Throwable $e){return $label;}
        },100,2);
        add_filter('woocommerce_available_payment_gateways',[self::class,'bookingGateways'],1000);
        add_action('woocommerce_payment_complete',[self::class,'observeOrder'],220);
        add_action('woocommerce_order_status_changed',[self::class,'observeOrder'],220);
        add_action('woocommerce_order_refunded',[self::class,'observeOrder'],220);
        add_action('woocommerce_refund_deleted',[self::class,'refundDeleted'],220,2);
    }
    /** Keep native AJAX refreshes on the quote's original rail as well as the initial screen. */
    public static function bookingGateways(array $gateways): array {
        $session=WC()->session;if($session instanceof QuoteSession)return $gateways;
        $id=$session?$session->get('krev_booking_id'):null;if(!$id)return $gateways;
        try{
            $booking=Store::get($id,'booking');$intent=Store::get((string)$session->get('krev_listing_intent'),'listing');
            if(($intent['data']['selection']['booking_id']??'')!==$id || ($booking['data']['listing_intent']??'')!==$intent['id']
                || $session->get('krev_listing_owner')!==$intent['owner'])return [];
            $method=$intent['data']['context']['payment_method']??'';
            return isset($gateways[$method])?[$method=>$gateways[$method]]:[];
        }catch(\Throwable $e){return [];}
    }
    /** Service intake is a local handoff; merchant trips are priced as separate native fees. */
    public static function serviceRates(array $rates,array $package): array {
        $id=WC()->session?WC()->session->get('krev_booking_id'):null;if(!$id)return $rates;
        try{$selection=Booking::paymentSelection($id);$items=[];
            foreach($package['contents'] as $line)$items[]=['product_id'=>(int)$line['product_id'],'quantity'=>(int)$line['quantity']];
            usort($items,static fn($a,$b)=>$a['product_id']<=>$b['product_id']);if($items!==$selection['items'])return $rates;
            // Keep the financial quote's rate label stable; human wording uses display filters.
            return ['krev_booking_local_pickup'=>new \WC_Shipping_Rate('krev_booking_local_pickup','Sharpening service handoff',0,[],'local_pickup')];
        }catch(\Throwable $e){return $rates;}
    }
    public static function handoffLabel(array $input): string {
        return (($input['mode']??'')==='prepaid_pickup'?'We pick up':'You drop off').' → '.(($input['return_mode']??'')==='courier_delivery'?'we deliver':'you collect');
    }
    public static function order(array $row): ?\WC_Order {
        return BookingOrderBridge::linked($row)??BookingEvents::nativeOrder($row);
    }
    public static function refunds(?\WC_Order $order): array {
        $recorded=0;$accepted=0;$ids=[];
        if($order)foreach($order->get_refunds() as $refund){
            $amount=Domain::cents(wc_format_decimal($refund->get_amount(),2));$recorded+=$amount;
            if($refund->get_refunded_payment())$accepted+=$amount;
            $ids[]=['reference'=>(string)$refund->get_id(),'amount_minor'=>$amount,
                'state'=>$refund->get_refunded_payment()?'gateway_accepted':'manual_record_only'];
        }
        $total=$order?Domain::cents(wc_format_decimal($order->get_total(),2)):0;
        $state=$recorded?($accepted===$recorded?($recorded>=$total?'full_gateway_accepted':'partial_gateway_accepted'):'manual_review_required'):'not_issued';
        return ['refund_state'=>$state,'refund_recorded_minor'=>$recorded,'refund_gateway_accepted_minor'=>$accepted,
            'refunds'=>$ids,'refund_arrival_verified'=>false,'refund_destination'=>$order?$order->get_payment_method():null];
    }
    /** Native hook failures must not turn a successful gateway callback into a retry charge. */
    public static function observeOrder(int $orderId): void {
        try{
            $order=wc_get_order($orderId);if(!$order || $order instanceof \WC_Order_Refund)return;
            $id=(string)$order->get_meta('_krev_service_booking');if(!Domain::validId($id))$id=(string)$order->get_meta('_krev_unpaid_booking');
            if(!Domain::validId($id))return;$row=Store::get($id,'booking');$bound=self::order($row);
            if(!$bound || $bound->get_id()!==$orderId)return;
            if(in_array($row['data']['booking_state'],['awaiting_payment','requested'],true) && !empty($row['data']['prepayment_requested_at']) && $order->get_date_paid()){
                $facts=ListingCheckout::facts(Store::get($row['data']['listing_intent'],'listing'));
                if($facts['payment_verification']==='native_gateway_order_event' && !Store::confirm($id,$order->get_date_paid()->getTimestamp()))BookingEvents::record($row,'payment.capacity_review_required',$order);
            }
            // Cancellation from native Woo/Dokan is also a service cancellation. It never invents a refund.
            if($order->has_status(['cancelled','refunded']) && $row['data']['booking_state']!=='cancelled')Booking::cancel($id,$row['owner']);
            $row=Store::get($id,'booking');
            BookingEvents::record($row,'woocommerce.order_state',$order,$order->get_status().':'.($order->get_date_modified()?$order->get_date_modified()->getTimestamp():0));
            foreach($order->get_refunds() as $refund)BookingEvents::record($row,
                $refund->get_refunded_payment()?'refund.gateway_accepted':'refund.manual_review_required',$order,(string)$refund->get_id());
            if(!empty($row['data']['listing_intent']))BookingEvents::observe($row);
        }catch(\Throwable $e){/* Scoped status polling reconciles again; no payload or PII logging. */}
    }
    public static function refundDeleted(int $refundId,int $orderId): void {
        self::observeOrder($orderId);
        try{$order=wc_get_order($orderId);$id=(string)$order->get_meta('_krev_service_booking');$row=Store::get($id,'booking');
            if(self::order($row)?->get_id()===$orderId)BookingEvents::record($row,'refund.record_removed',$order,(string)$refundId);
        }catch(\Throwable $e){}
    }
    /** Called after the booking transaction commits, never from inside its SQL transaction. */
    public static function cancelOrder(array $row): void {
        $order=self::order($row);if(!$order)return;
        if(!empty($row['data']['listing_intent'])){
            $facts=ListingCheckout::facts(Store::get($row['data']['listing_intent'],'listing'));
            if($facts['payment_verification']==='native_gateway_order_event'){
                if(!$order->has_status(['cancelled','refunded']))$order->update_status('cancelled','Service booking cancelled. Original payment retained pending separate refund review.');
                BookingEvents::record($row,'cancellation.payment_review_required',$order);return;
            }
        }
        // In-flight gateway orders need reconciliation, even when Woo still says pending.
        if($order->get_payment_method()!==Settings::get()['booking_offline_gateway_id']
            || $order->get_date_paid() || $order->get_transaction_id() || $order->is_paid() || $order->get_total_refunded()>0){
            BookingEvents::record($row,'cancellation.payment_review_required',$order);return;
        }
        if($order->has_status(['pending','on-hold','failed']))$order->update_status('cancelled','Sharpening booking cancelled. No online payment or refund issued.');
        BookingEvents::record($row,'woocommerce.order_cancelled',$order);
    }
    /** Owning seller's explicitly approved full remaining refund, through the original native rail. */
    public static function refundRemaining(string $id,bool $approved): array {
        $row=Store::get($id,'booking');if(!$approved || !BookingSeller::can($row))Domain::fail('FORBIDDEN','Approve the refund from the owning seller account.',403);
        return Store::lock('booking-refund:'.$id,static function()use($id){
            $row=Store::get($id,'booking');$order=self::order($row);
            if(!$order || empty($row['data']['listing_intent']))Domain::fail('REFUND_UNAVAILABLE','No bound online service payment exists.');
            $intent=Store::get($row['data']['listing_intent'],'listing');$status=ListingCheckout::facts($intent);
            if($status['payment_verification']!=='native_gateway_order_event')Domain::fail('REFUND_REVIEW_REQUIRED','Reconcile the original gateway payment before refunding.',409);
            $original=$intent['data'];$items=[];foreach($order->get_items() as $item)$items[]=['product_id'=>$item->get_product_id(),'quantity'=>(int)$item->get_quantity()];
            usort($items,static fn($a,$b)=>$a['product_id']<=>$b['product_id']);
            if($items!==$row['data']['input']['items'] || $order->get_payment_method()!==($original['context']['payment_method']??null)
                || Domain::cents(wc_format_decimal($order->get_total(),2))!==($original['quote']['total_minor']??null)
                || $order->get_meta('_krev_listing_quote_hash')!==($original['quote']['quote_hash']??null))Domain::fail('REFUND_REVIEW_REQUIRED','The original order, amount or quote binding changed. Reconcile it before refunding.',409);
            $attemptId=hash('sha256','booking-refund:'.$order->get_id());
            try{$attempt=Store::get($attemptId,'booking_refund');
                if(($attempt['data']['state']??'')==='gateway_accepted')return self::refunds(wc_get_order($order->get_id()));
                Domain::fail('REFUND_UNRESOLVED','An original refund attempt needs native gateway reconciliation. Do not issue another refund.',409);
            }catch(Fault $e){if($e->codeName!=='NOT_FOUND')throw $e;}
            $gateway=wc_get_payment_gateway_by_order($order);
            if(!$gateway || !$gateway->supports('refunds'))Domain::fail('REFUND_UNSUPPORTED','This payment method requires merchant-assisted refund reconciliation.');
            if(function_exists('dokan_pro') && dokan_pro()->refund->has_pending_request($order->get_id()))Domain::fail('REFUND_REVIEW_REQUIRED','Resolve the original Dokan refund request before issuing another refund.',409);
            $remaining=Domain::cents(wc_format_decimal($order->get_remaining_refund_amount(),2));
            if($remaining<1)return self::refunds($order);
            $lines=[];$sum=0;
            foreach($order->get_items(['line_item','fee','shipping']) as $itemId=>$item){
                $amount=Domain::cents(wc_format_decimal($item->get_total(),2))-Domain::cents(wc_format_decimal($order->get_total_refunded_for_item($itemId,$item->get_type()),2));
                $tax=[];foreach($item->get_taxes()['total']??[] as $rate=>$value){
                    $left=Domain::cents(wc_format_decimal($value,2))-Domain::cents(wc_format_decimal($order->get_tax_refunded_for_item($itemId,$rate,$item->get_type()),2));
                    if($left<0)Domain::fail('REFUND_REVIEW_REQUIRED','Native tax allocation needs administrator review.',409);
                    $tax[$rate]=Domain::decimal($left);$sum+=$left;
                }
                if($amount<0)Domain::fail('REFUND_REVIEW_REQUIRED','Native refund allocation needs administrator review.',409);
                $sum+=$amount;$qty=$item->get_type()==='line_item'?$item->get_quantity()+$order->get_qty_refunded_for_item($itemId):0;
                $lines[$itemId]=['qty'=>$qty,'refund_total'=>Domain::decimal($amount),'refund_tax'=>$tax];
            }
            // Preserve product/fee/tax allocations for native Dokan and Connect accounting.
            if($sum!==$remaining)Domain::fail('REFUND_REVIEW_REQUIRED','Unallocated prior adjustments require administrator review.',409);
            $attempt=['state'=>'processing','booking_id'=>$id,'order_id'=>$order->get_id(),'amount_minor'=>$remaining,'currency'=>$order->get_currency(),
                'actor_user_id'=>get_current_user_id(),'payment_method'=>$order->get_payment_method(),'prior_refunded_minor'=>Domain::cents(wc_format_decimal($order->get_total_refunded(),2)),
                'seller_accounting_state'=>'pending','started_at'=>time()];
            Store::put($attemptId,'booking_refund',$id,time()+365*86400,$attempt);
            try{
                $refund=wc_create_refund(['order_id'=>$order->get_id(),'amount'=>Domain::decimal($remaining),'reason'=>'Seller-approved refund of remaining sharpening service and trips.',
                    'line_items'=>$lines,'refund_payment'=>true,'restock_items'=>false]);
                if(is_wp_error($refund)){$attempt['state']='needs_native_review';Store::update($attemptId,$attempt);Domain::fail('REFUND_UNRESOLVED','Native gateway did not verify the refund. Review the original attempt before retrying.',409);}
                $attempt['state']=$refund->get_refunded_payment()?'gateway_accepted':'needs_native_review';$attempt['refund_id']=$refund->get_id();Store::update($attemptId,$attempt);
                if($refund->get_refunded_payment()){
                    // Dokan Lite observes WC refunds itself; installed Pro explicitly skips that
                    // observer and normally adjusts balances inside its own approval workflow.
                    // This owner-delegated service action therefore uses those same native hooks.
                    if(function_exists('dokan') && dokan()->is_pro_exists()){
                        $vendorAmount=apply_filters('dokan_vendor_earning_in_refund',$refund,$order);
                        if(!is_numeric($vendorAmount) || $vendorAmount<0 || $vendorAmount>(float)$refund->get_amount())Domain::fail('REFUND_REVIEW_REQUIRED','Native vendor refund allocation needs reconciliation.',409);
                        do_action('dokan_refund_adjust_vendor_balance',$vendorAmount,$refund,$order);
                        do_action('dokan_refund_adjust_dokan_orders',$vendorAmount,$refund,$order);
                    }
                    $attempt['seller_accounting_state']='native_hooks_completed';Store::update($attemptId,$attempt);
                }
                self::observeOrder($order->get_id());return self::refunds(wc_get_order($order->get_id()));
            }catch(\Throwable $e){$attempt['state']='needs_native_review';Store::update($attemptId,$attempt);throw $e;}
        });
    }
}
