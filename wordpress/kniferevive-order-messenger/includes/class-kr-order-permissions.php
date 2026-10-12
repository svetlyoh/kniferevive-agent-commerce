<?php
defined('ABSPATH') || exit;
final class KR_Order_Messenger_Permissions {
    public static function denied(): WP_Error { return new WP_Error('kr_om_forbidden','Order messaging is unavailable for this account.',['status'=>403]); }
    public static function recorded_vendor(WC_Order $order): int {
        global $wpdb;
        if (wc_string_to_bool((string)$order->get_meta('has_sub_order'))) return 0;
        $meta=(int)$order->get_meta('_dokan_vendor_id');
        // This is Dokan's ledger, not a WooCommerce storage table. No current product-author fallback.
        $ids=array_values(array_unique(array_map('intval',$wpdb->get_col($wpdb->prepare("SELECT seller_id FROM {$wpdb->prefix}dokan_orders WHERE order_id=%d",$order->get_id())))));
        $ids=array_values(array_filter($ids));
        if (count($ids)>1 || ($meta && $ids && $meta!==$ids[0])) return 0;
        return $meta ?: ($ids[0]??0);
    }
    /** Scope always contains only one recorded seller's order/items. */
    public static function scope(int $order_id) {
        $settings=KR_Order_Messenger_Admin::settings(); $uid=get_current_user_id();
        if (!$uid || !$settings['enabled']) return self::denied();
        $order=wc_get_order($order_id);
        if (!$order instanceof WC_Order || $order->get_type()!=='shop_order' || $order->has_status(['trash','auto-draft','checkout-draft'])) return self::denied();
        // Sharpening uses its existing operator permission, without enabling
        // global manager messaging for marketplace merchandise.
        if (class_exists('KREV_Sharpening_Orders') && class_exists('KREV_Orders_Permissions') && KREV_Sharpening_Orders::is_sharpening_order($order) && KREV_Orders_Permissions::is_operator()) {
            $buyer=$order->get_parent_id() ? wc_get_order($order->get_parent_id()) : $order;
            if (!$buyer instanceof WC_Order || ($order->get_customer_id() && $buyer->get_customer_id() && $order->get_customer_id()!==$buyer->get_customer_id())) return self::denied();
            return ['requested'=>$order,'order'=>$order,'buyer_order'=>$buyer,'vendor_id'=>$uid,'sender_id'=>$uid,'kind'=>'sharpening'];
        }
        $manager=$settings['manager_access'] && current_user_can('manage_woocommerce');
        $seller=$settings['seller_access'] && dokan_is_user_seller($uid) && dokan_is_seller_enabled($uid);
        if (!$manager && !$seller) return self::denied();
        $effective=$order; $vendor=self::recorded_vendor($order);
        $children=dokan()->order->get_child_orders($order->get_id());
        if ($children || wc_string_to_bool((string)$order->get_meta('has_sub_order'))) {
            if (!$seller) return self::denied(); // Do not invent a vendor selection for a manager on mixed orders.
            $matches=[];
            foreach ($children as $child) {
                if ($child instanceof WC_Order && $child->get_parent_id()===$order->get_id() && self::recorded_vendor($child)===$uid) $matches[]=$child;
            }
            if (count($matches)!==1) return self::denied();
            $effective=$matches[0]; $vendor=$uid;
        }
        if (!$vendor || (!$manager && $vendor!==$uid) || !$effective->get_items('line_item')) return self::denied();
        if (!$manager && !dokan_is_seller_enabled($vendor)) return self::denied();
        $buyer=$effective->get_parent_id() ? wc_get_order($effective->get_parent_id()) : $effective;
        if (!$buyer instanceof WC_Order || ($effective->get_parent_id() && $buyer->get_id()!==$effective->get_parent_id())) return self::denied();
        // A conflicting customer relationship is a corrupt scope; fail closed.
        if ($effective->get_customer_id() && $buyer->get_customer_id() && $effective->get_customer_id()!==$buyer->get_customer_id()) return self::denied();
        return ['requested'=>$order,'order'=>$effective,'buyer_order'=>$buyer,'vendor_id'=>$vendor,'sender_id'=>$uid];
    }
}
