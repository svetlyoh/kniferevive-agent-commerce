<?php
namespace KnifeRevive\SellerContact;
defined('ABSPATH') || exit;

/** Aggregate first-party metrics only. No destinations, messages, emails or raw IPs in storage. */
final class Analytics {
    const EVENTS=['seller_contact_eligible_view','seller_contact_cta_impression','seller_contact_cta_click','seller_contact_chooser_open','seller_contact_channel_click','seller_contact_external_handoff'];
    public static function table(string $name): string { global $wpdb;return $wpdb->prefix.'krc_'.$name; }
    public static function install(): void {
        global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$collate=$wpdb->get_charset_collate();
        dbDelta('CREATE TABLE '.self::table('counts')." (
id bigint unsigned NOT NULL AUTO_INCREMENT,
day date NOT NULL,
event varchar(48) NOT NULL,
placement varchar(16) NOT NULL,
channel varchar(10) NOT NULL DEFAULT '',
product_id bigint unsigned NOT NULL DEFAULT 0,
seller_id bigint unsigned NOT NULL DEFAULT 0,
device varchar(8) NOT NULL,
n bigint unsigned NOT NULL DEFAULT 0,
PRIMARY KEY  (id),
UNIQUE KEY dimensions (day,event,placement,channel,product_id,seller_id,device)
) $collate;");
        dbDelta('CREATE TABLE '.self::table('seen')." (
event_key char(64) NOT NULL,
day date NOT NULL,
PRIMARY KEY  (event_key),
KEY day (day)
) $collate;");
        update_option('kr_seller_contact_schema','1',false);
    }
    public static function boot(): void {
        add_action('rest_api_init',static function(){register_rest_route('kniferevive-seller-contact/v1','/events',['methods'=>'POST','permission_callback'=>'__return_true','callback'=>[self::class,'receive']]);});
        add_action('kr_seller_contact_prune',[self::class,'prune']);
        add_action('init',static function(){if(Config::on('analytics') && !wp_next_scheduled('kr_seller_contact_prune'))wp_schedule_event(time()+3600,'daily','kr_seller_contact_prune');});
    }
    public static function consent(): bool {
        if (($_SERVER['HTTP_SEC_GPC']??'')==='1') { return false; }
        // The existing Meta marketing cookie is deliberately not reused for unrelated analytics.
        return function_exists('wp_has_consent') && wp_has_consent('statistics')===true;
    }
    public static function receive(\WP_REST_Request $request): \WP_REST_Response {
        $ignore=static fn()=>new \WP_REST_Response(['accepted'=>false],200);
        if (!Config::on('analytics') || !self::consent() || get_option('kr_seller_contact_schema')!=='1') { return $ignore(); }
        if (strlen($request->get_body())>2048 || preg_match('/bot|crawler|spider|headless/i',$_SERVER['HTTP_USER_AGENT']??'')) { return $ignore(); }
        $origin=(string)$request->get_header('origin');
        if (!$origin || wp_parse_url($origin,PHP_URL_HOST)!==wp_parse_url(home_url('/'),PHP_URL_HOST) || wp_parse_url($origin,PHP_URL_SCHEME)!==wp_parse_url(home_url('/'),PHP_URL_SCHEME) || wp_parse_url($origin,PHP_URL_PORT)!==wp_parse_url(home_url('/'),PHP_URL_PORT)) { return $ignore(); }
        $event=$request->get_param('event');$place=$request->get_param('placement');$token=$request->get_param('session');$device=$request->get_param('device');$channel=$request->get_param('channel')??'';
        if (!is_string($event)||!in_array($event,self::EVENTS,true)||!is_string($place)||!in_array($place,['detail','card','storefront'],true)||!is_string($token)||!preg_match('/^[a-f0-9]{32}$/D',$token)||!in_array($device,['mobile','desktop'],true)||!in_array($channel,['','telegram','whatsapp'],true)) { return $ignore(); }
        $pid=$request->get_param('product_id');$sid=$request->get_param('seller_id');
        $valid_id=static fn($v)=>(is_int($v)&&$v>=0)||(is_string($v)&&preg_match('/^[0-9]{1,18}$/D',$v));
        if (!$valid_id($pid) || !$valid_id($sid)) { return $ignore(); }
        $data=$place==='storefront'?Resolver::for_seller(absint($sid)):Resolver::for_product(absint($pid));
        if (!$data['eligible'] || ($channel!==''&&!isset($data['channels'][$channel]))) { return $ignore(); }
        if (in_array($event,['seller_contact_channel_click','seller_contact_external_handoff'],true) && $channel==='') { return $ignore(); }
        // A client seller ID is never authoritative for a product event.
        $sid=$data['seller_id'];$pid=$data['product_id'];$day=gmdate('Y-m-d');
        $rate='krc_rate_'.hash_hmac('sha256',($_SERVER['REMOTE_ADDR']??'local').':'.gmdate('YmdHi'),wp_salt('nonce'));
        $hits=(int)get_transient($rate);if($hits>=120)return $ignore();set_transient($rate,$hits+1,120);
        global $wpdb;
        $accepted=false;
        // Product/store rollup plus a separately deduplicated all-products session denominator.
        $scopes=[[$pid,$sid,$channel],[0,0,$channel]];
        if($channel!=='')$scopes[]=[0,0,''];
        foreach ($scopes as [$scope_product,$scope_seller,$scope_channel]) {
            $key=hash_hmac('sha256',implode('|',[$day,$token,$event,$place,$scope_channel,$scope_product,$scope_seller]),wp_salt('auth'));
            $fresh=$wpdb->query($wpdb->prepare('INSERT IGNORE INTO '.self::table('seen').' (event_key,day) VALUES (%s,%s)',$key,$day));
            if ($fresh!==1)continue;
            $ok=$wpdb->query($wpdb->prepare('INSERT INTO '.self::table('counts').' (day,event,placement,channel,product_id,seller_id,device,n) VALUES (%s,%s,%s,%s,%d,%d,%s,1) ON DUPLICATE KEY UPDATE n=n+1',$day,$event,$place,$scope_channel,$scope_product,$scope_seller,$device));
            $accepted=$accepted||$ok!==false;
        }
        return new \WP_REST_Response(['accepted'=>$accepted],200);
    }
    public static function prune(): void {
        global $wpdb;$cut=gmdate('Y-m-d',time()-6*DAY_IN_SECONDS);
        foreach(['seen','counts'] as $t){$wpdb->query($wpdb->prepare('DELETE FROM '.self::table($t).' WHERE day < %s',$cut));}
    }
    public static function report(): array {
        if (get_option('kr_seller_contact_schema')!=='1') { return []; }
        global $wpdb;return $wpdb->get_results('SELECT day,event,placement,channel,device,SUM(n) AS total FROM '.self::table('counts').' WHERE product_id=0 AND seller_id=0 GROUP BY day,event,placement,channel,device ORDER BY day DESC LIMIT 500',ARRAY_A)?:[];
    }
}
