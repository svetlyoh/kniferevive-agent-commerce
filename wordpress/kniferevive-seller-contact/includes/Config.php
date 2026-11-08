<?php
namespace KnifeRevive\SellerContact;
defined('ABSPATH') || exit;

final class Config {
    const OPTION='kr_seller_contact_v1';
    public static function defaults(): array {
        return ['enabled'=>false,'detail'=>true,'card'=>true,'storefront'=>true,'analytics'=>false,'seller_allowlist'=>[]];
    }
    public static function get(): array {
        $saved=get_option(self::OPTION, []);
        return array_merge(self::defaults(), is_array($saved)?$saved:[]);
    }
    public static function on(string $placement=''): bool {
        $c=self::get();
        return $c['enabled']===true && (!$placement || !empty($c[$placement]));
    }
    public static function seller_allowed(int $seller_id): bool {
        $ids=array_map('absint', (array) self::get()['seller_allowlist']);
        return !$ids || in_array($seller_id, $ids, true);
    }
}
