<?php
defined('ABSPATH') || exit;
final class KR_Order_Messenger_Admin {
    public static function defaults(): array {
        return ['enabled'=>1,'seller_access'=>1,'manager_access'=>0,'reply_to_mode'=>'seller','support_email'=>'','screenshots'=>3,'file_mb'=>4,'total_mb'=>8,'order_hour'=>5,'seller_day'=>20,'history'=>1,'retention_days'=>90,'theme'=>'inherit','layout'=>'summary-first','heading'=>'A message about your order','intro'=>'Your seller has sent you an update about your KnifeRevive order.','message_heading'=>'Message from your seller','subject'=>'[KnifeRevive] Order #{order_id} — Message from your seller','footer_note'=>'','accent'=>'#a7552a','background'=>'#f6f6f4','surface'=>'#ffffff','text'=>'#111111','font_size'=>16,'width'=>620,'logo_url'=>''];
    }
    public static function settings(): array { return array_merge(self::defaults(),(array)get_option('kr_om_settings',[])); }
    public static function reply_to(?array $scope=null): string {
        $s=self::settings();
        if ($s['reply_to_mode']==='seller') { $u=$scope?get_userdata($scope['vendor_id']):null; return $u && is_email($u->user_email)?$u->user_email:''; }
        $support=$s['support_email'];
        if (!$support) $support=class_exists('KREV_Order_Contact') ? KREV_Order_Contact::EMAIL : get_option('woocommerce_email_from_address',get_option('admin_email'));
        // Inherited configured support is trusted; new overrides require a mailbox verification round-trip.
        if ($s['support_email'] && get_option('kr_om_verified_support')!==strtolower($support)) return '';
        return is_email($support)?$support:'';
    }
    public static function register(): void {
        add_action('admin_menu',static function () { add_submenu_page('woocommerce','KnifeRevive Order Messenger','Order Messenger','manage_options','kr-order-messenger',[self::class,'page']); });
        add_action('admin_post_kr_om_settings',[self::class,'save']);
        add_action('admin_post_kr_om_verify_support',[self::class,'verify_support']);
        add_action('admin_post_kr_om_request_verification',[self::class,'request_verification']);
        add_action('admin_post_kr_om_diagnostic',[self::class,'diagnostic']);
        add_action('admin_post_kr_om_brand_preview',[self::class,'brand_preview']);
    }
    public static function sanitize(array $input): array {
        $out=self::defaults();
        foreach (['enabled','seller_access','manager_access','history'] as $key) $out[$key]=empty($input[$key])?0:1;
        foreach (['reply_to_mode'=>['seller','support'],'theme'=>['inherit','custom'],'layout'=>['summary-first','message-first']] as $key=>$allowed) $out[$key]=in_array($input[$key]??'',$allowed,true)?$input[$key]:$out[$key];
        foreach (['screenshots'=>[0,10],'file_mb'=>[1,10],'total_mb'=>[1,20],'order_hour'=>[1,20],'seller_day'=>[1,100],'retention_days'=>[7,365],'font_size'=>[16,22],'width'=>[480,720]] as $key=>[$min,$max]) $out[$key]=max($min,min($max,(int)($input[$key]??$out[$key])));
        $out['total_mb']=max($out['file_mb'],$out['total_mb']);
        foreach (['heading','intro','message_heading','subject','footer_note'] as $key) $out[$key]=sanitize_text_field(mb_substr((string)($input[$key]??$out[$key]),0,$key==='intro'||$key==='footer_note'?1000:240));
        if (!str_contains($out['subject'],'{order_id}')) $out['subject']=self::defaults()['subject'];
        foreach (['heading','message_heading'] as $key) if (!$out[$key]) $out[$key]=self::defaults()[$key];
        foreach (['accent','background','surface','text'] as $key) $out[$key]=sanitize_hex_color($input[$key]??'') ?: $out[$key];
        $out['support_email']=sanitize_email($input['support_email']??'');
        $out['logo_url']=esc_url_raw($input['logo_url']??'',['https','http']);
        return $out;
    }
    public static function guard(string $nonce): void { if (!current_user_can('manage_options')) wp_die('Not permitted.',403); check_admin_referer($nonce); }
    public static function redirect(string $state): void { wp_safe_redirect(add_query_arg(['page'=>'kr-order-messenger','kr_om_notice'=>$state],admin_url('admin.php'))); exit; }
    public static function save(): void { self::guard('kr_om_settings'); $s=self::sanitize(wp_unslash((array)($_POST['settings']??[]))); update_option('kr_om_settings',$s,false); self::redirect('saved'); }
    public static function request_verification(): void {
        self::guard('kr_om_request_verification'); $email=self::settings()['support_email'];
        if (!is_email($email)) self::redirect('support_missing');
        $token=bin2hex(random_bytes(32)); set_transient('kr_om_support_verification',['hash'=>hash('sha256',$token),'email'=>strtolower($email)],DAY_IN_SECONDS);
        $url=add_query_arg(['action'=>'kr_om_verify_support','token'=>$token],admin_url('admin-post.php'));
        $sent=wp_mail($email,'Verify KnifeRevive Order Messenger support Reply-To',"Confirm access to this support mailbox by opening the following link while signed in as a KnifeRevive administrator:\n".$url);
        self::redirect($sent?'verification_sent':'mail_failed');
    }
    public static function verify_support(): void {
        if (!current_user_can('manage_options')) wp_die('Sign in as a marketplace administrator to verify this mailbox.',403);
        $token=sanitize_text_field(wp_unslash($_REQUEST['token']??'')); $pending=get_transient('kr_om_support_verification');
        if (!$pending || !hash_equals($pending['hash'],hash('sha256',$token)) || strtolower(self::settings()['support_email'])!==$pending['email']) wp_die('Verification link expired or changed. Request a new one.',400);
        if ($_SERVER['REQUEST_METHOD']!=='POST') {
            echo '<h1>Verify support Reply-To</h1><form method="post"><input type="hidden" name="action" value="kr_om_verify_support"><input type="hidden" name="token" value="'.esc_attr($token).'">'; wp_nonce_field('kr_om_verify_support'); echo '<button>Confirm this mailbox</button></form>'; return;
        }
        self::guard('kr_om_verify_support'); update_option('kr_om_verified_support',$pending['email'],false); delete_transient('kr_om_support_verification'); self::redirect('verified');
    }
    /** Diagnostic target is an existing registered seller, never a posted email. */
    public static function diagnostic(): void {
        self::guard('kr_om_diagnostic'); $vendor=absint($_POST['test_seller']??0); $user=get_userdata($vendor);
        if (!$user || !dokan_is_user_seller($vendor) || !is_email($user->user_email)) self::redirect('test_seller_invalid');
        $scope=KR_Order_Messenger_Mailer::demo_scope($vendor);
        $email=new KR_Order_Messenger_Mailer($scope,'This is an administrator-requested Order Messenger delivery test. No buyer order has been changed.',[]);
        $sent=$email->deliver($user->user_email);
        self::redirect($sent?'test_accepted':'mail_failed');
    }
    public static function brand_preview(): void {
        self::guard('kr_om_brand_preview'); nocache_headers(); header('Referrer-Policy: no-referrer');
        $mail=new KR_Order_Messenger_Mailer(KR_Order_Messenger_Mailer::demo_scope(get_current_user_id()),'Example order update. This branding preview does not send email.',[]);
        echo $mail->preview();
    }
    public static function page(): void {
        if (!current_user_can('manage_options')) return; $s=self::settings();
        $notices=['saved'=>'Settings saved.','verification_sent'=>'Verification email handed to the mail transport.','verified'=>'Support mailbox verified.','test_accepted'=>'Diagnostic email handed to the mail transport. Check the selected seller’s inbox.','mail_failed'=>'Email transport reported a failure.','support_missing'=>'Save a valid support email first.','test_seller_invalid'=>'Choose an existing seller with a valid account email.'];
        echo '<div class="wrap"><h1>KnifeRevive Order Messenger</h1><p>Seller → buyer order updates, email only. The registered seller email is the default Reply-To; the configured KnifeRevive mail service remains the sender.</p>';
        $notice=sanitize_key($_GET['kr_om_notice']??''); if (isset($notices[$notice])) echo '<div class="notice notice-info"><p>'.esc_html($notices[$notice]).'</p></div>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="kr_om_settings">'; wp_nonce_field('kr_om_settings'); echo '<table class="form-table">';
        foreach (['enabled'=>'Enable Order Messenger','seller_access'=>'Allow enabled Dokan sellers','manager_access'=>'Allow WooCommerce managers on unambiguous single-vendor orders','history'=>'Display seller message attempt history'] as $key=>$label) echo '<tr><th>'.esc_html($label).'</th><td><label><input type="checkbox" name="settings['.$key.']" value="1" '.checked($s[$key],1,false).'> Enabled</label></td></tr>';
        foreach (['reply_to_mode'=>['label'=>'Buyer replies go to','options'=>['seller'=>'Seller’s current registered email (requested default)','support'=>'Verified marketplace support email']],'theme'=>['label'=>'Email and composer theme','options'=>['inherit'=>'Inherit KnifeRevive / WooCommerce branding','custom'=>'Custom colors below']],'layout'=>['label'=>'Email content format','options'=>['summary-first'=>'Order summary, then message','message-first'=>'Message, then order summary']]] as $key=>$field) {
            echo '<tr><th>'.esc_html($field['label']).'</th><td><select name="settings['.$key.']">'; foreach ($field['options'] as $value=>$label) echo '<option value="'.esc_attr($value).'" '.selected($s[$key],$value,false).'>'.esc_html($label).'</option>'; echo '</select></td></tr>';
        }
        $labels=['support_email'=>'Support override (optional, verify after saving)','screenshots'=>'Maximum screenshots','file_mb'=>'Maximum screenshot size (MB)','total_mb'=>'Maximum total image payload (MB)','order_hour'=>'Messages per vendor/order/hour','seller_day'=>'Messages per sender and vendor/day','retention_days'=>'Detailed log retention (days)','heading'=>'Email heading','intro'=>'Email introduction (transactional only)','message_heading'=>'Message heading','subject'=>'Subject template (must include {order_id})','footer_note'=>'Additional transactional footer note','accent'=>'Custom accent color','background'=>'Custom background color','surface'=>'Custom content background','text'=>'Custom text color','font_size'=>'Email/composer font size (px)','width'=>'Email content width (px)','logo_url'=>'Optional logo override URL (blank inherits existing logo)'];
        foreach ($labels as $key=>$label) { $type=is_int($s[$key])?'number':(in_array($key,['accent','background','surface','text'],true)?'color':($key==='support_email'?'email':($key==='logo_url'?'url':'text'))); echo '<tr><th><label for="kr-om-'.$key.'">'.esc_html($label).'</label></th><td><input class="regular-text" id="kr-om-'.$key.'" name="settings['.$key.']" type="'.$type.'" value="'.esc_attr((string)$s[$key]).'"></td></tr>'; }
        echo '</table><p>Subject tokens: {order_id}, {order_number}, {store_name}. Screenshot limits also respect PHP upload limits. Colors affect only Order Messenger when Custom is selected. Layout/text/logo settings are editable here; PHP templates expose WordPress filters for future developer changes.</p>'; submit_button('Save settings'); echo '</form>';
        foreach (['kr_om_brand_preview'=>'Preview saved branding','kr_om_request_verification'=>'Send support mailbox verification'] as $action=>$label) { echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"'.($action==='kr_om_brand_preview'?' target="_blank"':'').'><input type="hidden" name="action" value="'.$action.'">'; wp_nonce_field($action); submit_button($label,'secondary'); echo '</form>'; }
        echo '<h2>Diagnostic test email</h2><p>Recipient is resolved from the selected registered seller account. This sends a clearly labelled test, not a real buyer message.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="kr_om_diagnostic">'; wp_nonce_field('kr_om_diagnostic');
        echo '<label>Registered seller <select name="test_seller">'; foreach (get_users(['role__in'=>['seller','administrator','shop_manager'],'number'=>100,'orderby'=>'display_name']) as $user) if (dokan_is_user_seller($user->ID)) echo '<option value="'.(int)$user->ID.'">'.esc_html($user->display_name).' (#'.(int)$user->ID.')</option>'; echo '</select></label>'; submit_button('Send diagnostic email','secondary'); echo '</form><h2>Delivery audit</h2><p>Accepted means mail-transport handoff, not confirmed inbox delivery. Unknown attempts are never automatically resent.</p><table class="widefat"><thead><tr><th>Order</th><th>Vendor</th><th>Sender</th><th>Status</th><th>Images</th><th>UTC</th></tr></thead><tbody>';
        foreach (KR_Order_Messenger_Log::recent() as $r) echo '<tr><td>#'.(int)$r->order_id.'</td><td>#'.(int)$r->vendor_id.'</td><td>#'.(int)$r->sender_user_id.'</td><td>'.esc_html($r->status).'</td><td>'.(int)$r->attachment_count.'</td><td>'.esc_html($r->created_at_gmt).'</td></tr>';
        echo '</tbody></table></div>';
    }
}
