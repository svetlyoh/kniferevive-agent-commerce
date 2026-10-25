<?php
namespace KnifeRevive\SellerContact;
defined('ABSPATH') || exit;

final class Renderer {
    const WARNING='You are leaving KnifeRevive to chat. Never send payment or card details in messages. Complete purchases through KnifeRevive checkout.';
    private static bool $used=false;
    private static bool $detail_done=false;
    public static function used(): bool { return self::$used; }
    public static function product(int $id, string $placement='card'): string {
        if(is_admin()||is_feed()||(function_exists('is_checkout')&&is_checkout())||(function_exists('is_cart')&&is_cart()))return '';
        if (!in_array($placement,['card','detail'],true) || !Config::on($placement)) { return ''; }
        $data=Resolver::for_product($id);
        return self::markup($data,$placement);
    }
    public static function store(int $id): string {
        return Config::on('storefront')?self::markup(Resolver::for_seller($id),'storefront'):'';
    }
    public static function detail(int $id): string {
        if (self::$detail_done) { return ''; }
        $out=self::product($id,'detail');
        if ($out!=='') { self::$detail_done=true; }
        return $out;
    }
    public static function markup(array $data,string $placement): string {
        if (empty($data['eligible'])) { return ''; }
        self::$used=true;
        self::assets(true);
        $id=wp_unique_id('krc-');$name=$data['store_name'];$label=$placement==='storefront'?'Contact seller':'Message seller';
        $attrs=' data-krc-placement="'.esc_attr($placement).'" data-krc-product="'.esc_attr($data['product_id']).'" data-krc-seller="'.esc_attr($data['seller_id']).'" data-krc-store="'.esc_attr($name).'" data-krc-channels="'.count($data['channels']).'"';
        $out='<div class="krc-contact krc-contact--'.esc_attr($placement).'"'.$attrs.'>';
        if (count($data['channels'])===1) {
            $channel=array_key_first($data['channels']);$choice=$data['channels'][$channel];
            $out.='<a class="krc-trigger" data-krc-channel="'.esc_attr($channel).'" href="'.esc_url($choice['url']).'" target="_blank" rel="noopener noreferrer" aria-label="'.esc_attr($label.' · '.$name.' · opens '.$choice['label'].' in a new tab').'" title="'.esc_attr(self::WARNING).'">'.esc_html($label).'</a>';
            if ($channel==='telegram') { $out.='<details class="krc-copy-fallback"><summary>Copy a question</summary>'.self::copy_box($data['question']).'</details>'; }
        } else {
            $out.='<details class="krc-chooser"><summary class="krc-trigger" aria-controls="'.esc_attr($id).'">'.esc_html($label).'</summary><div class="krc-options" id="'.esc_attr($id).'" role="group" aria-label="'.esc_attr('Message '.$name).'"><h3 class="krc-options-title">'.esc_html('Message '.$name).'</h3><div class="krc-channel-list">';
            foreach ($data['channels'] as $channel=>$choice) {
                $out.='<a class="krc-channel" data-krc-channel="'.esc_attr($channel).'" href="'.esc_url($choice['url']).'" target="_blank" rel="noopener noreferrer"><strong>'.esc_html($choice['label']).'</strong><span>'.esc_html($channel==='whatsapp'?'Open chat in WhatsApp':'Open seller chat in Telegram').'</span></a>';
            }
            $out.='</div><p class="krc-warning">'.esc_html(self::WARNING).'</p>'.self::copy_box($data['question']).'<p><a class="krc-help" href="'.esc_url(home_url('/contact/')).'">Need help? Contact KnifeRevive</a></p></div></details>';
        }
        if ($placement!=='card') {
            $out.='<p class="krc-disclosure">Chat opens WhatsApp or Telegram. For your protection, pay only through KnifeRevive checkout.</p>';
        }
        if ($placement==='detail') {
            $out.='<p class="krc-seller">Sold by <a href="'.esc_url($data['store_url']).'">'.esc_html($name).'</a> · <a href="'.esc_url($data['store_url']).'">Visit seller store</a></p>';
        }
        return $out.'</div>';
    }
    private static function copy_box(string $question): string {
        return '<div class="krc-copy"><button type="button" class="krc-copy-button" hidden>Copy question</button><label>Question to copy<textarea readonly rows="3">'.esc_textarea($question).'</textarea></label><p class="krc-copy-status" role="status" aria-live="polite"></p></div>';
    }
    public static function assets(bool $force=false): void {
        // Small shared files: late custom card rendering must not depend on an earlier single-product hook.
        if (is_admin() || is_feed() || (function_exists('is_checkout') && is_checkout()) || (function_exists('is_cart') && is_cart())) { return; }
        if(!Config::on()&&!(function_exists('is_wc_endpoint_url')&&is_wc_endpoint_url('seller-contact')))return;
        if(wp_script_is('kr-seller-contact','enqueued'))return;
        $relevant=(function_exists('is_woocommerce')&&is_woocommerce()) || is_search() || is_front_page() || (function_exists('dokan_is_store_page')&&dokan_is_store_page()) || (function_exists('is_wc_endpoint_url')&&is_wc_endpoint_url('seller-contact')) || is_page(['art-trade-gallery','technology-trade-desk','other-finds','books','categories','collectibles','home-garden','art','world-coins','world-spices']);
        $relevant=(bool)apply_filters('kr_seller_contact_asset_page',$relevant);
        if (!$relevant&&!$force) { return; }
        wp_enqueue_style('kr-seller-contact',plugins_url('assets/contact.css',KRC_FILE),[],KRC_VERSION);
        wp_enqueue_script('kr-seller-contact',plugins_url('assets/contact.js',KRC_FILE),[],KRC_VERSION,true);
        // Consent state is read dynamically by the script, never embedded in cached HTML.
        wp_add_inline_script('kr-seller-contact','window.krcContact='.wp_json_encode(['events'=>rest_url('kniferevive-seller-contact/v1/events'),'analytics'=>Config::on('analytics'),'consentApi'=>function_exists('wp_has_consent')]).';','before');
    }
    public static function dialog(): void {
        if (!Config::on() || (!self::$used && !wp_script_is('kr-seller-contact','enqueued'))) { return; }
        echo '<dialog id="krc-dialog" class="krc-dialog" aria-labelledby="krc-dialog-title"><section class="krc-dialog-panel"><header><h2 id="krc-dialog-title">Message seller</h2><button class="krc-close" type="button" aria-label="Close seller contact options">Close</button></header><div class="krc-dialog-body"></div></section></dialog>';
    }
}
