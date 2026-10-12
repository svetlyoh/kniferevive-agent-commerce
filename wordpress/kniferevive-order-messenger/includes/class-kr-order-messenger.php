<?php
defined('ABSPATH') || exit;
final class KR_Order_Messenger {
    public static function available(): bool { return class_exists('WooCommerce') && class_exists('KREV_Orders_Endpoints') && function_exists('dokan') && function_exists('dokan_is_seller_enabled') && function_exists('dokan_is_user_seller'); }
    public static function boot(): void {
        if (!self::available()) { add_action('admin_notices',static function () { if (current_user_can('activate_plugins')) echo '<div class="notice notice-warning"><p>KnifeRevive Order Messenger is inactive: WooCommerce, Dokan and KnifeRevive Seller Orders must be enabled.</p></div>'; }); return; }
        // Seller Orders registers a WordPress rewrite endpoint. WooCommerce's
        // endpoint conditional also needs its query-variable map populated.
        add_filter('woocommerce_get_query_vars',[self::class,'endpoint_query_vars']);
        // Initialize the real WooCommerce mailer before defining the subclass.
        WC()->mailer(); require_once KR_OM_PATH.'includes/class-kr-order-message-mailer.php';
        add_action('rest_api_init',['KR_Order_Messenger_REST','routes']);
        add_action('init',static function () { if (get_option('kr_om_db_version')!==KR_OM_VERSION) KR_Order_Messenger_Log::install(); });
        add_action('woocommerce_account_seller-orders_endpoint',[self::class,'screen'],30);
        add_action('woocommerce_account_sharpening-orders_endpoint',[self::class,'screen'],30);
        add_action('wp_enqueue_scripts',[self::class,'assets'],110);
        add_action('template_redirect',static function () { if (function_exists('is_wc_endpoint_url') && (is_wc_endpoint_url('seller-orders') || is_wc_endpoint_url('sharpening-orders'))) { if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE',true); nocache_headers(); header('Referrer-Policy: no-referrer'); } });
        add_filter('woocommerce_email_styles',['KR_Order_Messenger_Mailer','styles'],999,2);
        add_action('kr_om_cleanup',['KR_Order_Messenger_Log','cleanup']);
        add_action('kr_om_sweep',['KR_Order_Messenger_Attachments','sweep']);
        add_action('woocommerce_delete_order',['KR_Order_Messenger_Log','remove_order']);
        KR_Order_Messenger_Admin::register();
    }
    public static function endpoint_query_vars(array $vars): array {
        if (!isset($vars['seller-orders'])) $vars['seller-orders']='seller-orders';
        if (!isset($vars['sharpening-orders'])) $vars['sharpening-orders']='sharpening-orders';
        return $vars;
    }
    public static function screen($value=''): void {
        if (!absint($value)) return; $scope=KR_Order_Messenger_Permissions::scope(absint($value)); if (is_wp_error($scope)) return;
        $order_id=absint($value); include KR_OM_PATH.'templates/order-message-composer.php';
    }
    public static function assets(): void {
        if (!function_exists('is_wc_endpoint_url')) return;
        $order_id=0;
        foreach (['seller-orders','sharpening-orders'] as $endpoint) {
            if (is_wc_endpoint_url($endpoint)) { $order_id=absint(get_query_var($endpoint)); break; }
        }
        if (!$order_id) return;
        $scope=KR_Order_Messenger_Permissions::scope($order_id); if (is_wp_error($scope)) return;
        $base=plugin_dir_url(KR_OM_FILE);
        wp_enqueue_style('kr-order-messenger',$base.'assets/css/order-messenger.css',[],KR_OM_VERSION);
        wp_enqueue_script('kr-order-messenger',$base.'assets/js/order-messenger.js',[],KR_OM_VERSION,true);
        wp_localize_script('kr-order-messenger','KROrderMessenger',['api'=>rest_url('kniferevive/v1/order-messages/'),'nonce'=>wp_create_nonce('wp_rest')]);
        $s=KR_Order_Messenger_Admin::settings();
        if ($s['theme']==='custom') wp_add_inline_style('kr-order-messenger','.kr-om-root{--kr-om-accent:'.$s['accent'].';--kr-om-bg:'.$s['surface'].';--kr-om-text:'.$s['text'].';--kr-om-backdrop:'.$s['background'].'}');
        wp_add_inline_style('kr-order-messenger','.kr-om-root{--kr-om-font:'.(int)$s['font_size'].'px}');
    }
}
