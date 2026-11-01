<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

final class Plugin {
    public static function ready(): bool {return class_exists('KREV_Refund_Authority')&&method_exists('KREV_Return_Mail','enqueue_message')&&class_exists('KnifeRevive\ShipKit\Application')&&method_exists('KnifeRevive\ShipKit\Documents','storeImageEvidence')&&method_exists('KnifeRevive\ShipKit\Assisted','approvePlatformReturn')&&method_exists('KnifeRevive\ShipKit\ShippingIou','gamePlan');}
    /** No browser checkbox can manufacture launch evidence. Proofs must match
     * this site/environment and installed source, and be refreshed after changes. */
    public static function financialReady(): bool {
        if(!self::ready())return false;$p=\KnifeRevive\ShipKit\Application::db()->get('return_launch','refunds');
        return $p&&($p['site']??'')===home_url()&&($p['environment']??'')===\KR_Connect_Settings::environment()&&($p['release']??'')===KR_RETURNS_VERSION&&($p['expires']??0)>time()&&
            !empty($p['parent_writer_races_verified'])&&!empty($p['original_gateway_refund_verified'])&&!empty($p['provider_lookup_verified'])&&!empty($p['canonical_entitlements_verified'])&&
            ($p['source_hash']??'')===hash_file('sha256',__DIR__.'/Refunds.php');
    }
    public static function boot(): void {
        if(!self::ready()){add_action('admin_notices',static function(){echo '<div class="notice notice-error"><p>KnifeRevive Returns needs the coordinated Seller Orders and ShipKit updates. No return or money routes were enabled.</p></div>';});return;}
        self::activate();
        add_action('rest_api_init',[Rest::class,'register']);add_filter('rest_pre_serve_request',[Files::class,'serve'],10,3);
        add_filter('krev_shipkit_document_scope_access',[Files::class,'scopeAccess'],10,3);add_filter('krev_shipkit_private_document_access',[Files::class,'documentAccess'],10,3);
        add_filter('krev_shipkit_return_can_void',[Labels::class,'canVoid'],10,2);
        add_filter('krev_shipkit_return_projection',[Labels::class,'project'],10,2);add_filter('krev_shipkit_money_projection',[Money::class,'project'],10,2);
        add_filter('krev_my_money_return_activity',[Money::class,'activity'],10,5);add_filter('krev_my_money_return_detail',[Money::class,'detail'],10,3);
        add_action('woocommerce_checkout_create_order_line_item',[Policy::class,'checkoutSnapshot'],999,4);
        add_action('woocommerce_checkout_order_created',[Policy::class,'processed'],999);
        add_action('woocommerce_store_api_checkout_order_processed',[Policy::class,'processed'],999);
        add_action('admin_menu',[Screens::class,'menu']);add_action('wp_enqueue_scripts',[Screens::class,'assets']);add_action('admin_enqueue_scripts',[Screens::class,'assets']);
        add_action('template_redirect',[Screens::class,'route']);add_action('admin_post_kr_returns_policy',[Screens::class,'savePolicy']);
        add_action('kr_returns_deadlines',[Jobs::class,'tick']);
        if(!wp_next_scheduled('kr_returns_deadlines'))wp_schedule_event(time()+300,'hourly','kr_returns_deadlines');
    }
    public static function activate(): void {
        if(!self::ready())return;
        // Existing case authority is preserved; only its notification schema was
        // extended by the coordinated Seller Orders migration.
        if(get_option('kr_returns_schema')!=='1'){\KREV_Return_Mail::install();update_option('kr_returns_schema','1',false);}
    }
}
