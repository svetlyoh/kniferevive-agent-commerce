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
        add_filter('dokan_get_dashboard_nav',static function($nav){$nav['krev-bookings']=['title'=>'Sharpening requests','icon'=>'<i class="fas fa-calendar" aria-hidden="true"></i>','url'=>self::url(),'pos'=>35];return $nav;});
        // Query-based entry avoids rewrite flushes and supports old/new dashboard routing.
        add_action('template_redirect',static function(){if(isset($_GET['krev_booking_inbox']))self::render();},-1);
    }
    public static function rows(): array {
        if(!is_user_logged_in())return [];
        global $wpdb;$rows=$wpdb->get_results('SELECT * FROM '.Store::table('records')." WHERE kind='booking' AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.booking_state'))<>'draft' ORDER BY updated DESC LIMIT 500",ARRAY_A);
        $out=[];foreach($rows as $r){$r['data']=json_decode($r['data'],true,32,JSON_THROW_ON_ERROR);if(self::can($r))$out[]=$r;if(count($out)>=50)break;}return $out;
    }
    public static function render(): never {
        if(!is_user_logged_in() || (!current_user_can('manage_woocommerce') && (!function_exists('dokan_is_user_seller') || !dokan_is_user_seller(get_current_user_id()) || !dokan_is_seller_enabled(get_current_user_id()))))wp_die('Sign in with the service seller account.',403);
        PrivateBrand::headers();$message='';
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
            try{if(!Api::firstPartyForm() || !wp_verify_nonce(wp_unslash($_POST['csrf']??''),'krev_seller_booking'))Domain::fail('FORBIDDEN','Reload the seller inbox.',403);
                $row=Store::get((string)wp_unslash($_POST['booking_id']??''),'booking');if(!self::can($row))Domain::fail('NOT_FOUND','Request unavailable.',404);
                Booking::confirm($row['id'],($_POST['address_reviewed']??'')==='yes');$message='Service day confirmed. No payment was taken.';
            }catch(\Throwable $e){$message=$e instanceof Fault?$e->getMessage():'This request needs administrator review.';}
        }
        PrivateBrand::start('Sharpening requests');echo '<h1>Seller sharpening requests</h1><p>This inbox works independently of email. Requested dates require confirmation. Orders and payments have separate states.</p>';
        if($message)echo '<p role="status">'.esc_html($message).'</p>';
        foreach(self::rows() as $row){$d=$row['data'];$i=$d['input'];$order=BookingOrderBridge::linked($row)??BookingEvents::nativeOrder($row);
            echo '<section><h2>'.esc_html($i['preferred_date'].' · '.$d['booking_state']).'</h2><p>Booking '.esc_html($row['id']).'</p><p>'.esc_html($i['mode'].' · '.$i['return_mode']).'</p>';
            foreach($d['catalog_snapshot'] as $item)echo '<p>'.esc_html($item['title'].' × '.$item['quantity']).'</p>';
            if($i['notes'])echo '<p>Service note: '.esc_html($i['notes']).'</p>';
            echo '<p>Payment: '.esc_html(Booking::response($row)['payment_state']).'</p>';
            echo '<p>'.esc_html(implode(' · ',$i['customer'])).'</p>';
            if(isset($i['pickup_address']))echo '<p>'.esc_html(implode(', ',$i['pickup_address'])).'</p>';
            echo '<p>'.($order?'WooCommerce order '.esc_html($order->get_order_number()).' · '.esc_html($order->get_status()):'No WooCommerce order yet.').'</p>';
            if($order && function_exists('dokan_get_navigation_url'))echo '<p><a href="'.esc_url(wp_nonce_url(add_query_arg(['order_id'=>$order->get_id()],dokan_get_navigation_url('orders')),'dokan_view_order')).'">View seller order</a></p>';
            if($d['booking_state']==='requested'){echo '<form method="post"><input type="hidden" name="csrf" value="'.esc_attr(wp_create_nonce('krev_seller_booking')).'"><input type="hidden" name="booking_id" value="'.esc_attr($row['id']).'"><label><input type="checkbox" name="address_reviewed" value="yes"> I verified the exact trip address and county, where required.</label><button>Confirm requested day</button></form>';}
            echo '</section>';
        }PrivateBrand::end();exit;
    }
}
