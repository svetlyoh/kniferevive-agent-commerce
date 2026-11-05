<?php
namespace KnifeRevive\CampaignLanding;
defined('ABSPATH') || exit;
final class Admin {
    public static function menu(): void {add_submenu_page('woocommerce','Next Find campaign','Next Find campaign','manage_woocommerce','kr-next-find',[self::class,'page']);}
    public static function sanitize(array $raw): array|\WP_Error {
        $next=['page_id'=>absint(is_scalar($raw['page_id']??null)?$raw['page_id']:0),'products'=>[],'measurement'=>($raw['measurement']??'')==='1'];
        if($next['page_id']&&get_post_type($next['page_id'])!=='page')return new \WP_Error('invalid_page','Choose a WordPress Page ID.');
        foreach(Campaign::slots() as $key=>$slot){$id=$raw['products'][$key]??0;if(!is_scalar($id)||!preg_match('/^[0-9]*$/D',(string)$id))return new \WP_Error('invalid_product','Use numeric product IDs.');$id=absint($id);$bound=absint(Campaign::config()['products'][$key]??0);if($id&&(get_post_type($id)!=='product'||($id!==$bound&&get_post_field('post_name',$id)!==$slot['slug'])))return new \WP_Error('photo_mismatch','The product must match the approved campaign photograph.');$next['products'][$key]=$id;}
        return $next;
    }
    public static function save(): void {
        if(!current_user_can('manage_woocommerce'))wp_die('Not allowed.','',['response'=>403]);check_admin_referer('krcl_save');
        $next=self::sanitize(wp_unslash($_POST));if(is_wp_error($next))wp_die(esc_html($next->get_error_message()),'',['response'=>422]);
        update_option(Campaign::OPTION,$next,false);wp_safe_redirect(admin_url('admin.php?page=kr-next-find&saved=1'));exit;
    }
    public static function page(): void {
        if(!current_user_can('manage_woocommerce'))return;$c=Campaign::config();
        echo '<div class="wrap"><h1>Next Find campaign</h1><p>This tool does not create or publish a page, run ads or modify inventory. Add the supplied shortcode to a draft WordPress Page and use the supplied page template.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="krcl_save">';wp_nonce_field('krcl_save');
        echo '<p><label>Campaign Page ID <input name="page_id" type="number" min="0" value="'.absint($c['page_id']).'"></label></p><p>Product IDs below must identify the photographed items. Blank/zero resolves the verified slug without saving settings. Unavailable items show a category fallback.</p>';
        foreach(Campaign::slots() as $key=>$slot){$resolved=Campaign::product_id($key);$data=Campaign::product($key);echo '<p><label>'.esc_html($slot['label']).' product ID <input type="number" min="0" name="products['.esc_attr($key).']" value="'.absint($c['products'][$key]??$resolved).'"></label> '.esc_html('Current resolution: '.$resolved.' · '.$data['reason']).'</p>';}
        echo '<p><label><input type="checkbox" name="measurement" value="1" '.checked($c['measurement'],true,false).'> Enable approved campaign custom events through the existing official Meta sender</label></p><p>OFF by default. Requires existing Meta Controls readiness, explicit visitor advertising consent, no GPC, and the official fbq sender. No new dataset, pixel, CAPI sender or consent control is created. Leave off until event copy/privacy review is approved.</p>';submit_button('Save campaign settings');echo '</form><p><code>[kniferevive_next_find]</code> · Proposed URL: '.esc_html(home_url('/your-next-find/')).'</p></div>';
    }
}
