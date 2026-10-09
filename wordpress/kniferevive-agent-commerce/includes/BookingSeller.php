<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

final class BookingSeller {
    public static function seller(array $row): int {
        $ids=[];foreach($row['data']['input']['items'] as $item)$ids[]=(int)get_post_field('post_author',$item['product_id']);
        $ids=array_unique($ids);if(count($ids)!==1)return 0;$seller=(int)reset($ids);
        if(isset($row['data']['seller_id']) && (int)$row['data']['seller_id']!==$seller)return 0;
        return $seller && get_userdata($seller) && function_exists('dokan_is_user_seller') && dokan_is_user_seller($seller) && dokan_is_seller_enabled($seller)?$seller:0;
    }
    public static function can(array $row): bool {
        return current_user_can('manage_woocommerce') || (is_user_logged_in() && self::seller($row)===get_current_user_id());
    }
    public static function url(): string {return function_exists('dokan_get_navigation_url')?add_query_arg('krev_booking_inbox','1',dokan_get_navigation_url('')):home_url('/');}
    public static function boot(): void {
        add_filter('krev_seller_orders_appointments',[self::class,'appointments'],10,3);
        add_filter('dokan_get_dashboard_nav',static function($nav){$nav['krev-bookings']=['title'=>'Sharpening requests','icon'=>'<i class="fas fa-calendar" aria-hidden="true"></i>','url'=>self::url(),'pos'=>35];return $nav;});
        // Query-based entry avoids rewrite flushes and supports old/new dashboard routing.
        add_action('template_redirect',static function(){if(isset($_GET['krev_booking_inbox']))self::render();},-1);
    }
    public static function rows(): array {
        if(!is_user_logged_in())return [];
        global $wpdb;$rows=$wpdb->get_results('SELECT * FROM '.Store::table('records')." WHERE kind='booking' AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.booking_state'))<>'draft' ORDER BY updated DESC LIMIT 500",ARRAY_A);
        $out=[];foreach($rows as $r){$r['data']=json_decode($r['data'],true,32,JSON_THROW_ON_ERROR);if(self::can($r))$out[]=$r;if(count($out)>=50)break;}return $out;
    }
    /** Read-only adapter for the first-party Seller Orders screen. References are not access grants. */
    public static function appointments(array $cards,string $tab,int $page=1): array {
        if(!is_user_logged_in() || !in_array($tab,['local-pickup','all'],true))return $cards;
        foreach(self::rows() as $row){
            $d=$row['data'];$i=$d['input'];$seller=self::seller($row);
            if(!$seller || !in_array($d['booking_state'],['requested','confirmed'],true))continue;
            $order=BookingLifecycle::order($row);
            if(!$order || (int)$order->get_meta('_dokan_vendor_id')!==$seller)continue;
            if($i['mode']==='pay_later_dropoff' && ((int)$order->get_meta('_krev_booking_seller')!==$seller || $i['return_mode']!=='customer_collection'))continue;
            $shipping=array_values($order->get_items('shipping'));
            if(count($shipping)!==1 || $shipping[0]->get_method_id()!=='local_pickup')continue;
            $expected=[];foreach($i['items'] as $item)$expected[(int)$item['product_id']]=(int)$item['quantity'];
            $items=[];foreach($order->get_items() as $item){$id=$item->get_product_id();if(!isset($expected[$id]) || $expected[$id]!==$item->get_quantity()){ $items=[];break; }unset($expected[$id]);$items[]=$item->get_name().' × '.$item->get_quantity();}
            if(!$items || $expected)continue;
            $cards[]=['order_id'=>$order->get_id(),'order_number'=>$order->get_order_number(),'items'=>$items,'date'=>$i['preferred_date'],
                'handoff'=>($i['mode']==='prepaid_pickup'?'KnifeRevive pickup':'Customer drop-off').' · '.($i['return_mode']==='courier_delivery'?'KnifeRevive return delivery':'Customer collection'),
                'state'=>$d['booking_state']==='confirmed'?'Service day confirmed':'Requested — confirmation required',
                'order_status'=>wc_get_order_status_name($order->get_status()),'payment'=>$order->is_paid()?'Paid':($i['mode']==='pay_later_dropoff'?'Unpaid — pay at drop-off':'Online payment — '.$order->get_status()),
                'total'=>html_entity_decode(wp_strip_all_tags($order->get_formatted_order_total()),ENT_QUOTES,'UTF-8'),
                'review_url'=>add_query_arg('booking',$row['id'],self::url())];
        }return $cards;
    }
    public static function render(): never {
        if(!is_user_logged_in() || (!current_user_can('manage_woocommerce') && (!function_exists('dokan_is_user_seller') || !dokan_is_user_seller(get_current_user_id()) || !dokan_is_seller_enabled(get_current_user_id()))))wp_die('Sign in with the service seller account.',403);
        PrivateBrand::headers();$message='';
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
            try{if(!Api::firstPartyForm() || !wp_verify_nonce(wp_unslash($_POST['csrf']??''),'krev_seller_booking'))Domain::fail('FORBIDDEN','Reload the seller inbox.',403);
                $row=Store::get((string)wp_unslash($_POST['booking_id']??''),'booking');if(!self::can($row))Domain::fail('NOT_FOUND','Request unavailable.',404);
                $action=(string)wp_unslash($_POST['action']??'confirm');
                if($action==='cancel'){if(($_POST['cancel_approved']??'')!=='yes')Domain::fail('FORBIDDEN','Approve cancellation.',403);Booking::cancel($row['id'],$row['owner']);$message='Booking cancelled. Review any online payment separately; cancellation does not issue a refund.';}
                elseif($action==='refund'){BookingLifecycle::refundRemaining($row['id'],($_POST['refund_approved']??'')==='yes');$message='Native refund result recorded. Review gateway acceptance in the receipt; bank arrival is not verified.';}
                elseif($action==='confirm'){Booking::confirm($row['id'],($_POST['address_reviewed']??'')==='yes');$message='Service day confirmed. No payment was taken.';}
                else Domain::fail('INVALID_REQUEST','Unknown seller action.');
            }catch(\Throwable $e){$message=$e instanceof Fault?$e->getMessage():'This request needs administrator review.';}
        }
        PrivateBrand::start('Sharpening requests');echo '<h1>Seller sharpening requests</h1><p>This inbox works independently of email. Requested dates require confirmation. Orders and payments have separate states.</p>';
        if($message)echo '<p role="status">'.esc_html($message).'</p>';
        $capacityReady=Settings::get()['booking_daily_capacity']!==null;
        if(!$capacityReady)echo '<p role="status">Daily booking capacity is not configured. An administrator must set the number of jobs per open day before any request can be confirmed.</p>';
        $focus=(string)wp_unslash($_GET['booking']??'');$found=false;
        foreach(self::rows() as $row){if($focus!=='' && $focus!==$row['id'])continue;$found=true;$d=$row['data'];$i=$d['input'];$order=BookingOrderBridge::linked($row)??BookingEvents::nativeOrder($row);
            echo '<section><h2>'.esc_html($i['preferred_date'].' · '.$d['booking_state']).'</h2><p>Booking '.esc_html($row['id']).'</p><p>'.esc_html($i['mode'].' · '.$i['return_mode']).'</p>';
            foreach($d['catalog_snapshot'] as $item)echo '<p>'.esc_html($item['title'].' × '.$item['quantity']).'</p>';
            if($i['notes'])echo '<p>Service note: '.esc_html($i['notes']).'</p>';
            echo '<p>Payment: '.esc_html(Booking::response($row)['payment_state']).'</p>';
            echo '<p>'.esc_html(implode(' · ',$i['customer'])).'</p>';
            if(isset($i['pickup_address']))echo '<p>'.esc_html(implode(', ',$i['pickup_address'])).'</p>';
            echo '<p>'.($order?'WooCommerce order '.esc_html($order->get_order_number()).' · '.esc_html($order->get_status()):'No WooCommerce order yet.').'</p>';
            if($order){$url=current_user_can('manage_woocommerce')?$order->get_edit_order_url():(function_exists('dokan_get_navigation_url')?wp_nonce_url(add_query_arg(['order_id'=>$order->get_id()],dokan_get_navigation_url('orders')),'dokan_view_order'):'');if($url)echo '<p><a href="'.esc_url($url).'">'.(current_user_can('manage_woocommerce')?'View WooCommerce order':'View seller order').'</a></p>';}
            if($d['booking_state']==='requested'){echo '<form method="post"><input type="hidden" name="csrf" value="'.esc_attr(wp_create_nonce('krev_seller_booking')).'"><input type="hidden" name="booking_id" value="'.esc_attr($row['id']).'"><label><input type="checkbox" name="address_reviewed" value="yes"> I verified the exact trip address and county, where required.</label><button'.($capacityReady?'':' disabled').'>Confirm requested day</button></form>';}
            $refund=BookingLifecycle::refunds($order);echo '<p>Refund: '.esc_html($refund['refund_state']).' · Recorded $'.esc_html(Domain::decimal($refund['refund_recorded_minor'])).' · Gateway accepted $'.esc_html(Domain::decimal($refund['refund_gateway_accepted_minor'])).'. Arrival in the buyer account is not verified.</p>';
            if($d['booking_state']!=='cancelled')echo '<form method="post"><input type="hidden" name="csrf" value="'.esc_attr(wp_create_nonce('krev_seller_booking')).'"><input type="hidden" name="booking_id" value="'.esc_attr($row['id']).'"><input type="hidden" name="action" value="cancel"><label><input type="checkbox" name="cancel_approved" value="yes" required> Cancel this service booking. Any online payment needs separate refund review.</label><button>Cancel service booking</button></form>';
            if($order && !empty($d['listing_intent']) && $order->get_date_paid() && $order->get_remaining_refund_amount()>0)echo '<form method="post"><input type="hidden" name="csrf" value="'.esc_attr(wp_create_nonce('krev_seller_booking')).'"><input type="hidden" name="booking_id" value="'.esc_attr($row['id']).'"><input type="hidden" name="action" value="refund"><label><input type="checkbox" name="refund_approved" value="yes" required> Approve refund of the entire remaining $'.esc_html(wc_format_decimal($order->get_remaining_refund_amount(),2)).' to the original payment method, including unperformed trips. A full native refund closes the service order and releases its appointment allocation.</label><button>Refund remaining payment</button></form>';
            echo '</section>';
        }if(!$found)echo '<p>No sharpening request is available for this account.</p>';PrivateBrand::end();exit;
    }
}
