<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Facts for scoped polling; no remote callbacks, PII, or authorization grants. */
final class BookingEvents {
    /** Reconcile native facts during authorized polling; never creates an order or payment. */
    public static function nativeOrder(array $row): ?\WC_Order {
        if(empty($row['data']['listing_intent']))return null;
        $intent=Store::get($row['data']['listing_intent'],'listing');$order=empty($intent['data']['order_id'])?null:wc_get_order($intent['data']['order_id']);
        if(!$order || $order->get_meta('_krev_listing_intent')!==$intent['id'] || $order->get_meta('_krev_service_booking')!==$row['id'])return null;
        $seller=BookingSeller::seller($row);if(!$seller || !function_exists('dokan'))return null;
        $visible=dokan()->order->all(['seller_id'=>$seller,'order_id'=>$order->get_id(),'return'=>'ids']);
        if(is_wp_error($visible) || !in_array($order->get_id(),array_map('intval',$visible),true))return null;
        return $order;
    }
    public static function observe(array $row): void {
        $order=self::nativeOrder($row);if(!$order)return;
        $intent=Store::get($row['data']['listing_intent'],'listing');
        self::record($row,'woocommerce.order_created',$order);
        $status=ListingCheckout::facts($intent);
        if($status['payment_state']==='paid' && $status['payment_verification']==='native_gateway_order_event')self::record($row,'payment.verified',$order);
        if($status['payment_state']==='refund_recorded')self::record($row,'refund.review_required',$order);
    }
    public static function record(array $row,string $type,?\WC_Order $order=null,string $occurrence=''): void {
        $id=hash('sha256',$row['id'].':'.$type.($occurrence!==''?':'.$occurrence:''));
        try { Store::get($id,'booking_event'); return; } catch(Fault $e) { if($e->codeName!=='NOT_FOUND')throw $e; }
        $payment='not_started';if(!empty($row['data']['listing_intent'])){
            try{$payment=ListingCheckout::facts(Store::get($row['data']['listing_intent'],'listing'))['payment_state'];}catch(\Throwable $e){$payment='unknown';}
        }
        if(!$order)$order=BookingOrderBridge::linked($row)??self::nativeOrder($row);
        Store::put($id,'booking_event',$row['id'],time()+90*86400,[
            'schema_version'=>'1','event_id'=>$id,'type'=>$type,'booking_reference'=>$row['id'],
            'correlation_id'=>$row['data']['referral_id']??null,
            'order_reference'=>$order?(string)$order->get_order_number():null,
            'booking_state'=>$row['data']['booking_state'],'order_state'=>$order?$order->get_status():null,
            'payment_state'=>$payment,'refund_state'=>BookingLifecycle::refunds($order)['refund_state'],'merchant_confirmation_required'=>$row['data']['booking_state']==='requested',
            'occurred_at'=>gmdate('c')]);
    }
    public static function recent(string $id): array {
        global $wpdb;
        $rows=$wpdb->get_col($wpdb->prepare('SELECT data FROM '.Store::table('records')." WHERE kind='booking_event' AND owner=%s ORDER BY updated DESC,id DESC LIMIT 20",$id));
        return array_map(static fn($json)=>json_decode($json,true,32,JSON_THROW_ON_ERROR),array_reverse($rows));
    }
}
