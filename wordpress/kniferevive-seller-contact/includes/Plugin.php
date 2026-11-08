<?php
namespace KnifeRevive\SellerContact;
defined('ABSPATH') || exit;

final class Plugin {
    public static function boot(): void {
        if (!class_exists('WooCommerce')) { return; }
        Settings::boot();Analytics::boot();
        add_action('wp',[self::class,'replace_legacy'],100);
        add_action('woocommerce_single_product_summary',[self::class,'classic_detail'],31);
        add_filter('render_block_woocommerce/add-to-cart-form',[self::class,'block_detail'],90,3);
        add_filter('render_block_woocommerce/product-price',[self::class,'block_price'],90,3);
        add_action('wp_enqueue_scripts',[Renderer::class,'assets'],130);
        add_action('wp_footer',[Renderer::class,'dialog'],90);
    }
    public static function activate(): void {
        add_rewrite_endpoint('seller-contact',EP_ROOT|EP_PAGES);
        Analytics::install();flush_rewrite_rules(false);
        // Do not create/change any seller opt-in or enable the new UI on install.
    }
    public static function deactivate(): void {
        wp_clear_scheduled_hook('kr_seller_contact_prune');flush_rewrite_rules(false);
    }
    public static function replace_legacy(): void {
        foreach (['KR_WhatsApp_Product_Button','KR_Telegram_Product_Icon'] as $class) {
            remove_filter('render_block',[$class,'add_icon_to_product_price_block'],$class==='KR_WhatsApp_Product_Button'?20:30);
            remove_action('woocommerce_after_shop_loop_item_title',[$class,'render_loop_price_row'],10);
            remove_action('woocommerce_single_product_summary',[$class,'render_single_price_row'],10);
        }
        // Current theme post-template contact refresher is replaced by the shared bridge.
        // Classic prices remain WooCommerce's own output; never filter price values or cart totals.
        if (!wp_is_block_theme()) {
            add_action('woocommerce_after_shop_loop_item_title','woocommerce_template_loop_price',10);
            add_action('woocommerce_after_shop_loop_item_title',[self::class,'classic_card'],11);
            add_action('woocommerce_single_product_summary','woocommerce_template_single_price',10);
        }
    }
    private static function block_product(array $block, mixed $instance): int {
        if ($instance instanceof \WP_Block && !empty($instance->context['postId'])) { return absint($instance->context['postId']); }
        if (!empty($block['context']['postId'])) { return absint($block['context']['postId']); }
        global $product;
        return $product instanceof \WC_Product?$product->get_id():absint(get_the_ID());
    }
    public static function classic_detail(): void {
        if (wp_is_block_theme()) { return; }
        global $product;if ($product instanceof \WC_Product) { echo Renderer::detail($product->get_id()); }
    }
    public static function classic_card(): void {
        global $product;if ($product instanceof \WC_Product) { echo Renderer::product($product->get_id(),'card'); }
    }
    public static function block_detail(string $content,array $block,mixed $instance=null): string {
        if (is_admin() || !is_product()) { return $content; }
        $id=self::block_product($block,$instance);
        if ($id!==get_queried_object_id()) { return $content; }
        return $content.Renderer::detail($id);
    }
    public static function block_price(string $content,array $block,mixed $instance=null): string {
        if (is_admin() || is_feed()) { return $content; }
        $id=self::block_product($block,$instance);
        if (is_product() && $id===get_queried_object_id()) { return $content; }
        // Custom card builders delegate explicitly, avoiding nested links/competing block controls.
        return $content.Renderer::product($id,'card');
    }
}
