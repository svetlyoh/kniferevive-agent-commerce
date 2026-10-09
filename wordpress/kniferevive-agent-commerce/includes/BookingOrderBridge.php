<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Single-seller unpaid local pickup only. Native gateways are never invoked here. */
final class BookingOrderBridge {
    public static function enabled(): bool {
        $s=Settings::get();return $s['booking_order_timing']!=='disabled' && $s['booking_order_verified'] && $s['booking_offline_gateway_id']!=='';
    }
    public static function boot(): void {
        add_filter('woocommerce_order_data_store_cpt_get_orders_query',static function($query,$vars){
            if(isset($vars['krev_booking_reference']))$query['meta_query'][]=['key'=>'_krev_unpaid_booking','value'=>$vars['krev_booking_reference']];return $query;
        },10,2);
        add_filter('woocommerce_order_needs_payment',static fn($needs,$order)=>$order->get_meta('_krev_unpaid_booking')?false:$needs,20,2);
        add_filter('woocommerce_can_reduce_order_stock',static fn($can,$order)=>$order->get_meta('_krev_unpaid_booking') && $order->get_meta('_krev_booking_confirmation')!=='confirmed'?false:$can,20,2);
    }
    public static function linked(array $row): ?\WC_Order {
        $id=$row['data']['unpaid_order_id']??0;if(!$id)return null;
        $order=wc_get_order($id);return $order && $order->get_meta('_krev_unpaid_booking')===$row['id']?$order:null;
    }
    public static function findOrders(string $id): array {
        $query=['type'=>'shop_order','limit'=>2,'return'=>'objects'];
        if(\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled())$query['meta_query']=[['key'=>'_krev_unpaid_booking','value'=>$id]];
        else $query['krev_booking_reference']=$id;
        return wc_get_orders($query);
    }
    public static function transition(string $id,string $stage): void {
        $s=Settings::get();if(!self::enabled() || ($s['booking_order_timing']==='on_submit'?$stage!=='requested':$stage!=='confirmed'))return;
        try{self::ensure($id);}catch(\Throwable $e){
            Store::lock('booking:'.$id,static function()use($id,$e){$r=Store::get($id,'booking');$d=$r['data'];
                $d['order_bridge_state']='needs_admin_review';$d['order_bridge_error']=$e instanceof Fault?$e->codeName:'NATIVE_ORDER_UNRESOLVED';Store::update($id,$d);});
        }
    }
    public static function reconcile(string $id,bool $confirmed): void {
        if(!current_user_can('manage_woocommerce') || !$confirmed)Domain::fail('FORBIDDEN','An administrator must approve reconciling the original booking into an unpaid order.',403);
        self::ensure($id);
    }
    public static function ensure(string $id): \WC_Order {
        return Store::lock('booking:'.$id,static function()use($id){
            if(!self::enabled())Domain::fail('ORDER_BRIDGE_DISABLED','The unpaid order bridge needs owner approval and native Dokan verification.',503);
            $row=Store::get($id,'booking');$d=$row['data'];$i=$d['input'];$seller=BookingSeller::seller($row);
            if(!in_array($d['booking_state'],['requested','confirmed'],true) || $i['mode']!=='pay_later_dropoff' || $i['return_mode']!=='customer_collection' || !empty($d['listing_intent']))Domain::fail('ORDER_BRIDGE_INELIGIBLE','Only unpaid drop-off requests without another checkout can use this bridge.');
            if(Settings::get()['booking_order_timing']==='on_confirm' && $d['booking_state']!=='confirmed')Domain::fail('CONFIRMATION_REQUIRED','Merchant confirmation is required before order creation.');
            if(!$seller || !function_exists('dokan') || !method_exists(dokan()->order,'maybe_split_orders'))Domain::fail('SELLER_UNAVAILABLE','A verified active Dokan service seller is required.');
            Booking::normalize($i,false);
            if(!in_array(BookingAuthorization::address($row),['granted_for_order','merchant_review_required'],true))Domain::fail('ADDRESS_AUTHORIZATION_REQUIRED','The customer must approve contact sharing for this booking.');
            $gateway=WC()->payment_gateways()->payment_gateways()[Settings::get()['booking_offline_gateway_id']]??null;
            if(!$gateway || !in_array($gateway->id,['cod','bacs','cheque'],true) || $gateway->enabled!=='yes')Domain::fail('OFFLINE_METHOD_UNAVAILABLE','The owner-approved native offline arrangement is unavailable.');
            $mappingId=hash('sha256','booking-order:'.$id);
            try{$mapping=Store::get($mappingId,'booking_order');}catch(Fault $e){if($e->codeName!=='NOT_FOUND')throw $e;Store::put($mappingId,'booking_order',$id,time()+365*86400,['state'=>'preparing','order_id'=>null]);$mapping=Store::get($mappingId);}
            $order=self::linked($row);if(!$order && !empty($mapping['data']['order_id']))$order=wc_get_order($mapping['data']['order_id']);
            $found=self::findOrders($id);
            if(count($found)>1)Domain::fail('DUPLICATE_ORDER_REVIEW','Multiple native orders need administrator reconciliation.');
            if(!$order && $found)$order=$found[0];
            if(!$order && !empty($mapping['data']['order_id']))Domain::fail('NATIVE_ORDER_UNRESOLVED','The previously linked native order is missing. Do not create another.');
            if(!$order){
                $order=new \WC_Order();$order->set_created_via('krev_booking_request');$order->set_status('pending');$order->set_currency(get_woocommerce_currency());
                $order->set_billing_first_name($i['customer']['name']);$order->set_billing_email($i['customer']['email']);$order->set_billing_phone($i['customer']['phone']??'');
                $order->set_billing_country('US');$order->set_billing_state('CA');$order->set_billing_postcode($i['postal_code']);
                $order->set_customer_note($i['notes']);
                $order->set_payment_method($gateway->id);$order->set_payment_method_title('Pay when collecting from KnifeRevive — '.$gateway->get_title());
                $order->update_meta_data('_krev_unpaid_booking',$id);$order->update_meta_data('_krev_booking_seller',$seller);
                $order->update_meta_data('_krev_booking_confirmation',$d['booking_state']);$order->update_meta_data('_krev_requested_service_day',$i['preferred_date']);
                $order->update_meta_data('_krev_pay_at_service','yes');
                // Save reference with the first CRUD save. Crash recovery searches this marker before any new order.
                $order->save();Store::update($mappingId,['state'=>'building','order_id'=>$order->get_id()]);
            }
            if($order->get_meta('_krev_unpaid_booking')!==$id || (int)$order->get_meta('_krev_booking_seller')!==$seller || $order->is_paid() || $order->get_transaction_id() || !$order->has_status('pending'))Domain::fail('NATIVE_ORDER_UNRESOLVED','Existing order state needs administrator review; no new order was created.');
            if($order->get_meta('_krev_booking_order_built')!=='yes'){
                $existing=[];foreach($order->get_items() as $item)$existing[$item->get_product_id()]=$item;
                foreach($i['items'] as $item){$product=wc_get_product($item['product_id']);
                    if(isset($existing[$product->get_id()])){if($existing[$product->get_id()]->get_quantity()!==$item['quantity'])Domain::fail('NATIVE_ORDER_UNRESOLVED','Existing order quantity needs review.');unset($existing[$product->get_id()]);}
                    else $order->add_product($product,$item['quantity']);
                }
                if($existing)Domain::fail('NATIVE_ORDER_UNRESOLVED','Existing order items need review.');
                if(!$order->get_items('shipping')){$pickup=new \WC_Order_Item_Shipping();$pickup->set_method_id('local_pickup');$pickup->set_method_title('Customer drop-off and collection');$pickup->set_total(0);$order->add_item($pickup);}
                $order->calculate_totals();$order->update_meta_data('_krev_booking_order_built','yes');$order->save();
            }
            dokan()->order->maybe_split_orders($order->get_id());
            // Native idempotent sync repairs an interrupted seller index without invented ledger rows.
            if(function_exists('dokan_sync_insert_order'))dokan_sync_insert_order($order->get_id());
            $order=wc_get_order($order->get_id());
            $visible=dokan()->order->all(['seller_id'=>$seller,'order_id'=>$order->get_id(),'return'=>'ids','limit'=>2]);
            if(is_wp_error($visible) || !in_array($order->get_id(),array_map('intval',$visible),true) || (int)$order->get_meta('_dokan_vendor_id')!==$seller)Domain::fail('SELLER_ORDER_UNRESOLVED','The native order is not yet verified in the seller orders.');
            $d['unpaid_order_id']=$order->get_id();$d['order_bridge_state']='linked';unset($d['order_bridge_error']);Store::update($id,$d);
            Store::update($mappingId,['state'=>'linked','order_id'=>$order->get_id()]);BookingEvents::record(Store::get($id,'booking'),'woocommerce.order_created',$order);
            return $order;
        });
    }
}
