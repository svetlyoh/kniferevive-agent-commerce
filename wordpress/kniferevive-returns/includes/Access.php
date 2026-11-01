<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

final class Access {
    public static function admin(int $actor=0): bool {
        $actor=$actor?:get_current_user_id();
        return $actor>0 && (user_can($actor,'manage_woocommerce') || user_can($actor,'krev_returns_manage'));
    }
    public static function buyer(\WC_Order $order, int $actor=0): bool {
        $actor=$actor?:get_current_user_id(); $root=Support::root($order);
        if ($actor && $root->get_customer_id() && $actor===(int)$root->get_customer_id()) return true;
        return !$root->get_customer_id() && Guest::authorized($root);
    }
    public static function view(array $r, int $actor=0): string {
        $actor=$actor?:get_current_user_id();
        if (self::admin($actor)) return 'admin';
        $order=wc_get_order($r['order_id']);
        if ($order && self::buyer($order,$actor)) return 'buyer';
        if ($actor && $actor===(int)$r['seller_id']) return 'seller';
        $v=\KREV_Return_Workflow::details($r['id'])['data']['v2']??[];
        if ($actor && $actor===(int)($v['recipient_seller_id']??0)) return 'recipient';
        Support::fail('not_found','Return not found.',404);
    }
    public static function requireRole(array $r, array $roles): string {
        $role=self::view($r);
        if (!in_array($role,$roles,true)) Support::fail('forbidden','This return action is not available for your account.',403);
        return $role;
    }
}
