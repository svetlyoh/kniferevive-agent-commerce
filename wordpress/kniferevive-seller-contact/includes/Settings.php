<?php
namespace KnifeRevive\SellerContact;
defined('ABSPATH') || exit;

final class Settings {
    private static array $dirty=[];
    public static function boot(): void {
        add_action('init',static fn()=>add_rewrite_endpoint('seller-contact',EP_ROOT|EP_PAGES));
        add_filter('woocommerce_get_query_vars',static function($v){$v['seller-contact']='seller-contact';return $v;});
        add_filter('woocommerce_account_menu_items',static function($v){if(Resolver::active_seller(get_current_user_id())){unset($v['whatsapp-settings'],$v['telegram-settings']);$v['seller-contact']='Buyer messaging';}return $v;},80);
        add_action('woocommerce_account_seller-contact_endpoint',[self::class,'account']);
        add_action('dokan_settings_form_bottom',[self::class,'dokan_fields'],50,2);
        add_action('dokan_store_profile_saved',[self::class,'dokan_saved'],50,2);
        add_action('template_redirect',[self::class,'account_save'],30);
        add_action('template_redirect',[self::class,'legacy_account'],15);
        add_action('admin_menu',static fn()=>add_submenu_page('woocommerce','Seller contact','Seller contact','manage_woocommerce','kr-seller-contact',[self::class,'admin']));
        add_action('admin_post_krc_save',[self::class,'admin_save']);
        foreach(['added_user_meta','updated_user_meta','deleted_user_meta'] as $hook){add_action($hook,static function($mid,$id,$key){if(in_array($key,[Resolver::WA_ENABLED,Resolver::WA_NUMBER,Resolver::TG_ENABLED,Resolver::TG_MODE,Resolver::TG_USER,Resolver::TG_BOT,'dokan_enable_selling','dokan_profile_settings'],true))self::$dirty[(int)$id]=true;},20,3);}
        add_action('shutdown',static function(){foreach(array_keys(self::$dirty) as $id)self::invalidate($id);},20);
    }
    public static function can_edit(int $id): bool { return is_user_logged_in() && get_current_user_id()===$id && Resolver::active_seller($id) && current_user_can('edit_products'); }
    public static function validate(array $raw): array|\WP_Error {
        foreach(['krc_whatsapp_enabled','krc_telegram_enabled'] as $key){if(isset($raw[$key])&&!in_array($raw[$key],['0','1'],true))return new \WP_Error('invalid_contact','Check the messaging choices.');}
        $wa=($raw['krc_whatsapp_enabled']??'')==='1';$tg=($raw['krc_telegram_enabled']??'')==='1';
        foreach(['krc_whatsapp_number','krc_telegram_username','krc_telegram_bot','krc_telegram_mode'] as $k){if(isset($raw[$k])&&!is_string($raw[$k]))return new \WP_Error('invalid_contact','Check the messaging fields.');}
        $number=Resolver::number($raw['krc_whatsapp_number']??'');$user=Resolver::username($raw['krc_telegram_username']??'');$bot=Resolver::username($raw['krc_telegram_bot']??'');$mode=$raw['krc_telegram_mode']??'personal';
        if (($wa&&!$number)||trim($raw['krc_whatsapp_number']??'')!==''&&!$number) { return new \WP_Error('invalid_whatsapp','Enter an international WhatsApp number including its country code.'); }
        if (!in_array($mode,['personal','bot'],true) || ($tg&&!($mode==='bot'?$bot:$user)) || (trim($raw['krc_telegram_username']??'')!==''&&!$user) || (trim($raw['krc_telegram_bot']??'')!==''&&!$bot)) { return new \WP_Error('invalid_telegram','Enter a valid public Telegram username and account type.'); }
        return [Resolver::WA_ENABLED=>$wa?'1':'0',Resolver::WA_NUMBER=>$number,Resolver::TG_ENABLED=>$tg?'1':'0',Resolver::TG_MODE=>$mode,Resolver::TG_USER=>$user,Resolver::TG_BOT=>$bot];
    }
    public static function save(int $id,array $raw,string $nonce): bool|\WP_Error {
        if (!self::can_edit($id) || !wp_verify_nonce($nonce,'krc_contact_save')) { return new \WP_Error('contact_forbidden','Sign in again and retry saving your messaging choices.'); }
        $values=self::validate($raw);if(is_wp_error($values))return $values;
        foreach($values as $key=>$value){update_user_meta($id,$key,$value);}
        self::$dirty[$id]=true;
        return true;
    }
    public static function invalidate(int $id): void {
        // Coalesced for all channel writes in a request, including legacy settings/profile updates.
        $products=get_posts(['post_type'=>'product','post_status'=>'any','author'=>$id,'fields'=>'ids','posts_per_page'=>-1]);
        foreach($products as $pid){clean_post_cache($pid);if(function_exists('wc_delete_product_transients'))wc_delete_product_transients($pid);if(function_exists('wp_cache_post_change'))wp_cache_post_change($pid);}
        do_action('kr_seller_contact_settings_changed',$id); // Host/CDN integration point, never contact values.
    }
    private static function fields(int $id): void {
        $get=static fn($k)=>(string)get_user_meta($id,$k,true);
        echo '<fieldset class="krc-settings"><legend>Buyer messaging</legend><p>Your enabled WhatsApp number or Telegram username may be visible to visitors. Keep payments on KnifeRevive.</p>';
        wp_nonce_field('krc_contact_save','krc_contact_nonce');
        echo '<label class="krc-check"><input type="checkbox" name="krc_whatsapp_enabled" value="1" '.checked($get(Resolver::WA_ENABLED),'1',false).'> Allow buyers to contact me on WhatsApp</label><label>WhatsApp international number<input type="tel" name="krc_whatsapp_number" autocomplete="tel" maxlength="32" placeholder="+country code and number" value="'.esc_attr($get(Resolver::WA_NUMBER)).'"></label><p>Include the country code. We will not guess it.</p>';
        echo '<label class="krc-check"><input type="checkbox" name="krc_telegram_enabled" value="1" '.checked($get(Resolver::TG_ENABLED),'1',false).'> Allow buyers to contact me on Telegram</label><label>Telegram account type<select name="krc_telegram_mode"><option value="personal" '.selected($get(Resolver::TG_MODE),'personal',false).'>Personal account</option><option value="bot" '.selected($get(Resolver::TG_MODE),'bot',false).'>Seller bot</option></select></label><label>Public Telegram username<input name="krc_telegram_username" maxlength="32" value="'.esc_attr($get(Resolver::TG_USER)).'"></label><label>Seller bot username, if used<input name="krc_telegram_bot" maxlength="32" value="'.esc_attr($get(Resolver::TG_BOT)).'"></label>';
        $n=Resolver::number($get(Resolver::WA_NUMBER));$mode=$get(Resolver::TG_MODE);$u=Resolver::username($get($mode==='bot'?Resolver::TG_BOT:Resolver::TG_USER));
        echo '<div class="krc-test-links">';
        if($n)echo '<a href="'.esc_url('https://wa.me/'.$n).'" target="_blank" rel="noopener noreferrer">Test WhatsApp link</a>';
        if($u)echo '<a href="'.esc_url('https://t.me/'.$u).'" target="_blank" rel="noopener noreferrer">Test Telegram link</a>';
        echo '</div><p>These private test links open the saved destination; they do not send a message. Save changes before testing.</p><div class="krc-preview"><strong>Preview</strong><p>On listings: Message seller. On your storefront: Contact seller.</p>'.Renderer::store($id).'</div></fieldset>';
    }
    public static function account(): void {
        $id=get_current_user_id();if(!self::can_edit($id)){echo '<p>Sign in with your active seller account to manage buyer messaging.</p>';return;}
        echo '<section class="krc-account"><h2>Buyer messaging</h2><form method="post"><input type="hidden" name="krc_account_save" value="1">';self::fields($id);echo '<button type="submit">Save messaging choices</button></form></section>';
    }
    public static function account_save(): void {
        if(!is_account_page()||!is_wc_endpoint_url('seller-contact')||($_SERVER['REQUEST_METHOD']??'')!=='POST'||!isset($_POST['krc_account_save']))return;
        $raw=wp_unslash($_POST);$nonce=is_string($raw['krc_contact_nonce']??null)?$raw['krc_contact_nonce']:'';
        $ok=self::save(get_current_user_id(),$raw,$nonce);wc_add_notice(is_wp_error($ok)?$ok->get_error_message():'Messaging choices saved.',is_wp_error($ok)?'error':'success');
        wp_safe_redirect(wc_get_account_endpoint_url('seller-contact'));exit;
    }
    public static function legacy_account(): void {
        global $wp;
        if(!is_account_page()||!self::can_edit(get_current_user_id())||(!isset($wp->query_vars['whatsapp-settings'])&&!isset($wp->query_vars['telegram-settings'])))return;
        // Old bookmarks lead to the shared editor; do not run the legacy ten-digit country-code guesser.
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST')wc_add_notice('Review and save your channel choices in Buyer messaging. Your existing settings have not changed.','notice');
        wp_safe_redirect(wc_get_account_endpoint_url('seller-contact'));exit;
    }
    public static function dokan_fields(mixed $user_id=null,mixed $profile=null): void { $id=get_current_user_id();if(self::can_edit($id))self::fields($id); }
    public static function dokan_saved(mixed $seller_id,mixed $unused=null): void {
        if(!isset($_POST['krc_contact_nonce']))return;$raw=wp_unslash($_POST);
        $ok=self::save(absint($seller_id),$raw,is_string($raw['krc_contact_nonce'])?$raw['krc_contact_nonce']:'');
        if(is_wp_error($ok)&&function_exists('wc_add_notice'))wc_add_notice($ok->get_error_message(),'error');
    }
    public static function admin_save(): void {
        if(!current_user_can('manage_woocommerce'))wp_die('Not allowed.','',['response'=>403]);check_admin_referer('krc_admin_save');
        $next=Config::defaults();foreach(['enabled','detail','card','storefront','analytics'] as $key)$next[$key]=isset($_POST[$key])&&$_POST[$key]==='1';
        if(isset($_POST['seller_allowlist'])&&!is_string($_POST['seller_allowlist']))wp_die('Use comma-separated seller IDs.','',['response'=>422]);
        $raw=is_string($_POST['seller_allowlist']??null)?wp_unslash($_POST['seller_allowlist']):'';
        if($raw!==''&&!preg_match('/^[0-9,\s]+$/D',$raw))wp_die('Use comma-separated seller IDs.','',['response'=>422]);
        $next['seller_allowlist']=array_slice(array_values(array_unique(array_filter(array_map('absint',preg_split('/[\s,]+/',trim($raw)))))),0,100);
        update_option(Config::OPTION,$next,false);wp_safe_redirect(admin_url('admin.php?page=kr-seller-contact&saved=1'));exit;
    }
    public static function admin(): void {
        if(!current_user_can('manage_woocommerce'))return;$c=Config::get();
        echo '<div class="wrap"><h1>Seller contact</h1><p>One secondary contact action. Checkout remains on KnifeRevive. Seller channel opt-ins are preserved.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="krc_save">';wp_nonce_field('krc_admin_save');
        foreach(['enabled'=>'Enable unified contact UI','detail'=>'Product detail action','card'=>'Shop/card action','storefront'=>'Storefront action','analytics'=>'First-party adoption analytics (requires statistics consent)'] as $key=>$label){echo '<p><label><input type="checkbox" name="'.esc_attr($key).'" value="1" '.checked($c[$key],true,false).'> '.esc_html($label).'</label></p>';}
        echo '<p><label>Pilot seller IDs, comma separated; blank means all otherwise eligible sellers<br><input type="text" name="seller_allowlist" value="'.esc_attr(implode(',',$c['seller_allowlist'])).'" class="regular-text"></label></p><p>No sticky bar is added. A single channel opens directly; two channels use a chooser.</p><p>Analytics defaults off. It stays blocked without a statistics-consent API, even if this checkbox is enabled. The existing Meta choice is not reused.</p>';submit_button('Save contact settings');echo '</form>';
        echo '<h2>Private eligibility diagnostic</h2><form method="get"><input type="hidden" name="page" value="kr-seller-contact"><label>Product ID <input name="product_id" type="number" min="1"></label> <button class="button">Check eligibility</button></form>';
        if(isset($_GET['product_id'])&&is_scalar($_GET['product_id'])){echo '<pre>'.esc_html(wp_json_encode(Resolver::diagnostic(absint($_GET['product_id'])),JSON_PRETTY_PRINT)).'</pre>';}
        self::coverage();
        echo '<h2>Aggregate adoption report · last7 UTC calendar days</h2><p>Counts are deduplicated per ephemeral daily browser session and placement, across products. A blank channel is the overall session count; channel rows are separate breakdowns and must not be added together as unique buyers. Handoffs are link activations, not proof of messages sent, replies, app launches or sales.</p><table class="widefat"><thead><tr><th>Day</th><th>Event</th><th>Placement</th><th>Channel</th><th>Device</th><th>Count</th></tr></thead><tbody>';
        foreach(Analytics::report() as $row){echo '<tr>';foreach(['day','event','placement','channel','device','total'] as $k)echo '<td>'.esc_html($row[$k]).'</td>';echo '</tr>';}
        echo '</tbody></table><p>No events means no measurements are available. It does not mean no conversations occurred.</p></div>';
    }
    private static function coverage(): void {
        // Bounded, explicitly labelled inventory sample; this is not a buyer-view conversion KPI.
        $users=get_users(['capability'=>'dokandar','fields'=>'ID','number'=>200,'orderby'=>'ID','order'=>'ASC']);
        $active=0;$wa=0;$tg=0;$either=0;
        foreach($users as $id){
            $id=(int)$id;if(!Resolver::active_seller($id))continue;$active++;
            $w=get_user_meta($id,Resolver::WA_ENABLED,true)==='1'&&Resolver::number(get_user_meta($id,Resolver::WA_NUMBER,true))!=='';
            $mode=get_user_meta($id,Resolver::TG_MODE,true);
            $t=get_user_meta($id,Resolver::TG_ENABLED,true)==='1'&&in_array($mode,['personal','bot'],true)&&Resolver::username(get_user_meta($id,$mode==='bot'?Resolver::TG_BOT:Resolver::TG_USER,true))!=='';
            $wa+=(int)$w;$tg+=(int)$t;$either+=(int)($w||$t);
        }
        $products=get_posts(['post_type'=>'product','post_status'=>'publish','fields'=>'ids','posts_per_page'=>200,'orderby'=>'ID','order'=>'DESC']);$eligible=0;
        foreach($products as $id)if(Resolver::for_product((int)$id)['eligible'])$eligible++;
        echo '<h2>Channel availability and listing coverage</h2><p>'.esc_html(sprintf('Inventory sample: up to200 sellers with Dokan capability and200 latest published products. %d active sellers; %d with valid WhatsApp opt-in; %d with valid Telegram opt-in; %d with either. Opt-ins are counted independently of rollout flags.',$active,$wa,$tg,$either)).'</p><p>'.esc_html(sprintf('%d of%d sampled published products are contact-eligible under the current feature flags, seller pilot and channel settings. This is inventory coverage, not buyer-view coverage.',$eligible,count($products))).'</p>';
    }
}
