<?php
namespace KnifeRevive\SellerContact;
defined('ABSPATH') || exit;

/** Resolves contacts anew; no contact cache or second store of seller preferences. */
final class Resolver {
    const WA_ENABLED='_kniferevive_whatsapp_enabled';
    const WA_NUMBER='_kniferevive_whatsapp_number';
    const TG_ENABLED='_kr_telegram_enabled';
    const TG_MODE='_kr_telegram_mode';
    const TG_USER='_kr_telegram_username';
    const TG_BOT='_kr_telegram_bot_username';

    public static function number(mixed $raw): string {
        if (!is_string($raw) || !preg_match('/^\+?[0-9 ()\-.]+$/D', trim($raw))) { return ''; }
        $digits=preg_replace('/[^0-9]/', '', $raw);
        // Existing canonical international digits are allowed; never prepend or infer a country code.
        return preg_match('/^[1-9][0-9]{7,14}$/D', $digits)?$digits:'';
    }
    public static function username(mixed $raw): string {
        if (!is_string($raw)) { return ''; }
        $raw=trim($raw);
        if (preg_match('~^https://t\.me/([A-Za-z][A-Za-z0-9_]{4,31})/?$~D', $raw, $m)) { $raw=$m[1]; }
        $raw=ltrim($raw, '@');
        return preg_match('/^[A-Za-z][A-Za-z0-9_]{4,31}$/D', $raw)?$raw:'';
    }
    public static function active_seller(int $id): bool {
        return $id>0 && function_exists('dokan_is_user_seller') && function_exists('dokan_is_seller_enabled')
            && dokan_is_user_seller($id, true) && dokan_is_seller_enabled($id) && user_can($id,'edit_products');
    }
    private static function denied(string $reason): array { return ['eligible'=>false,'reason'=>$reason,'channels'=>[],'seller_id'=>0,'product_id'=>0]; }

    public static function for_seller(int $seller_id): array {
        if (!Config::on()) { return self::denied('feature_off'); }
        if (!function_exists('dokan_get_store_url')) { return self::denied('dokan_unavailable'); }
        if (!self::active_seller($seller_id)) { return self::denied('seller_inactive'); }
        if (!Config::seller_allowed($seller_id)) { return self::denied('outside_pilot'); }
        $info=function_exists('dokan_get_store_info')?(array)dokan_get_store_info($seller_id):[];
        $user=get_userdata($seller_id);
        $name=trim(wp_strip_all_tags(html_entity_decode((string)($info['store_name']??''),ENT_QUOTES|ENT_HTML5,'UTF-8')));
        if ($name==='') { $name=$user?wp_strip_all_tags((string)$user->display_name):''; }
        if ($name==='') { return self::denied('store_unavailable'); }
        $channels=[];
        $wa='1'===(string)get_option('kr_whatsapp_enabled','1') && '1'===(string)get_user_meta($seller_id,self::WA_ENABLED,true);
        $number=$wa?self::number(get_user_meta($seller_id,self::WA_NUMBER,true)):'';
        if ($number!=='') { $channels['whatsapp']=['url'=>'https://wa.me/'.$number,'label'=>'WhatsApp','mode'=>'direct']; }
        $tg='1'===(string)get_option('kr_telegram_enabled','1') && '1'===(string)get_user_meta($seller_id,self::TG_ENABLED,true);
        $mode=(string)get_user_meta($seller_id,self::TG_MODE,true);
        $handle=$tg && in_array($mode,['personal','bot'],true)?self::username(get_user_meta($seller_id,$mode==='bot'?self::TG_BOT:self::TG_USER,true)):'';
        if ($handle!=='') { $channels['telegram']=['url'=>'https://t.me/'.$handle,'label'=>'Telegram','mode'=>$mode]; }
        if (!$channels) { return self::denied('no_valid_opted_in_channel'); }
        $question='Hi '.$name.', I saw your store on KnifeRevive and have a question.';
        if (isset($channels['whatsapp'])) { $channels['whatsapp']['url'].='?text='.rawurlencode($question); }
        return ['eligible'=>true,'reason'=>'eligible','seller_id'=>$seller_id,'product_id'=>0,'store_name'=>$name,
            'store_url'=>dokan_get_store_url($seller_id),'channels'=>$channels,'question'=>$question];
    }
    public static function for_product(int|\WC_Product $input): array {
        $product=$input instanceof \WC_Product?$input:(function_exists('wc_get_product')?wc_get_product($input):false);
        if (!$product) { return self::denied('product_unavailable'); }
        if ($product->is_type('variation')) { $product=wc_get_product($product->get_parent_id()); }
        if (!$product || $product->get_status()!=='publish' || get_post_field('post_password',$product->get_id())!=='' || $product->get_catalog_visibility()==='hidden') { return self::denied('product_not_public'); }
        if (!in_array($product->get_type(),['simple','variable','external','grouped'],true)) { return self::denied('unsupported_product_type'); }
        // Resolve the parent FIRST; Dokan's product-owner filter remains authoritative.
        $owner=function_exists('dokan_get_vendor_by_product')?(int)dokan_get_vendor_by_product($product,true):0;
        $out=self::for_seller($owner);
        if (!$out['eligible']) { return $out; }
        $id=$product->get_id();$url=get_permalink($id);
        if (!$url || wp_parse_url($url,PHP_URL_HOST)!==wp_parse_url(home_url('/'),PHP_URL_HOST)) { return self::denied('canonical_url_unavailable'); }
        $out['product_id']=$id;$out['product_url']=$url;
        $out['question']='Hi, I saw “'.wp_strip_all_tags(html_entity_decode($product->get_name(),ENT_QUOTES|ENT_HTML5,'UTF-8')).'” on KnifeRevive and have a question about it: '.$url;
        if (isset($out['channels']['whatsapp'])) { $out['channels']['whatsapp']['url']=strtok($out['channels']['whatsapp']['url'],'?').'?text='.rawurlencode($out['question']); }
        if (isset($out['channels']['telegram']) && $out['channels']['telegram']['mode']==='bot') { $out['channels']['telegram']['url'].='?start=krp_'.$id; }
        return $out;
    }
    public static function diagnostic(int $id): array {
        $p=wc_get_product($id);$d=self::for_product($id);
        return ['product_id'=>$id,'type'=>$p?$p->get_type():'missing','status'=>$p?$p->get_status():'missing',
            'eligible'=>$d['eligible'],'reason'=>$d['reason'],'seller_id'=>$d['seller_id'],
            'whatsapp'=>isset($d['channels']['whatsapp']),'telegram'=>isset($d['channels']['telegram'])];
    }
}
