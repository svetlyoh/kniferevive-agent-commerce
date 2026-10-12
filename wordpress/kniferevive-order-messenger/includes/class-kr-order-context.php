<?php
defined('ABSPATH') || exit;
final class KR_Order_Messenger_Context {
    public static function recipient(array $scope): string {
        $order=$scope['buyer_order'];
        if ($order->get_customer_id()) {
            $user=get_userdata($order->get_customer_id());
            // Registered orders never fall back to a different billing mailbox.
            return $user && is_email($user->user_email) ? $user->user_email : '';
        }
        return is_email($order->get_billing_email()) ? $order->get_billing_email() : '';
    }
    public static function subject(array $scope): string {
        $order=$scope['requested'];
        $template=KR_Order_Messenger_Admin::settings()['subject'];
        $text=strtr($template,['{order_id}'=>(string)$order->get_id(),'{order_number}'=>(string)$order->get_order_number(),'{store_name}'=>self::store($scope['vendor_id'])]);
        return mb_substr(str_replace(["\r","\n"],' ',wp_strip_all_tags($text)),0,240);
    }
    public static function store(int $vendor): string {
        $info=dokan_get_store_info($vendor); $user=get_userdata($vendor);
        return sanitize_text_field((string)($info['store_name']??($user?$user->display_name:'Seller')));
    }
    public static function data(array $scope): array {
        $order=$scope['requested']; $date=$scope['buyer_order']->get_date_created(); $settings=KR_Order_Messenger_Admin::settings();
        $placed=$date ? wp_date(wc_date_format().' '.wc_time_format().' T',$date->getTimestamp(),wp_timezone()) : 'Unavailable';
        $items=[];
        foreach ($scope['order']->get_items('line_item') as $item) $items[]=['name'=>$item->get_name(),'quantity'=>$item->get_quantity()];
        $recipient=self::recipient($scope); $reply=KR_Order_Messenger_Admin::reply_to($scope);
        $registered=(bool)$scope['buyer_order']->get_customer_id();
        return ['order_id'=>$order->get_id(),'order_number'=>(string)$order->get_order_number(),'seller_order_id'=>$scope['order']->get_id(),'placed'=>$placed,'timezone'=>wp_timezone_string(),'store_name'=>self::store($scope['vendor_id']),'items'=>$items,'recipient'=>$recipient,'reply_to'=>$reply,'subject'=>self::subject($scope),'can_send'=>(bool)$recipient && (bool)$reply,'disabled_reason'=>!$recipient?"Can't ping this buyer — no valid email on the order.":(!$reply?'Reply-To is unavailable or needs verification. Please contact the site administrator.':''),'buyer_url'=>$registered ? wc_get_endpoint_url('view-order',$scope['buyer_order']->get_id(),wc_get_page_permalink('myaccount')) : $scope['order']->get_checkout_order_received_url(),'guest'=>$registered?false:true,'attachments'=>KR_Order_Messenger_Attachments::limits(),'logo'=>KR_Order_Messenger_Admin::settings()['logo_url'] ?: (string)get_option('woocommerce_email_header_image'),'max_message_length'=>10000];
    }
    public static function message($raw) {
        if (!is_string($raw) || strlen($raw)>40000) return new WP_Error('kr_om_message','Enter a message of up to 10,000 characters.',['status'=>400]);
        $message=trim(str_replace(["\r\n","\r"],"\n",$raw));
        if ($message==='' || mb_strlen($message)>10000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$message)) return new WP_Error('kr_om_message','Enter a message of up to 10,000 characters.',['status'=>400]);
        // Treat the entire message as plain text. Markup is escaped at the render boundary.
        return $message;
    }
}
