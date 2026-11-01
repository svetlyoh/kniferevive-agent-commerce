<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

use KnifeRevive\ShipKit\{Application,Check};

/** Email possession grants only a short return workspace session, not WordPress
 * login, seller access, financial authority or access by order number/email. */
final class Guest {
    private static function digest(string $token): string { return hash_hmac('sha256',$token,wp_salt('auth')); }
    public static function request(array $in): array {
        $rate='kr_returns_guest_'.substr(self::digest($_SERVER['REMOTE_ADDR']??'unknown'),0,32);
        $count=(int)get_transient($rate);
        if($count>=5) Support::fail('rate','Please wait before requesting another secure link.',429);
        set_transient($rate,$count+1,HOUR_IN_SECONDS);
        $generic=['message'=>'If this is a guest order with matching contact details, a secure return-access link will be emailed to its checkout address.'];
        $o=wc_get_order(absint($in['order_id']??0));$email=sanitize_email($in['email']??'');
        if(!$o||$o->get_parent_id()||$o->get_customer_id()||!$email||!hash_equals(strtolower($o->get_billing_email()),strtolower($email)))return$generic;
        $token=bin2hex(random_bytes(32));$hash=self::digest($token);$db=Application::db();
        $db->transaction(fn()=>$db->put('return_guest_grant',$hash,['id'=>$hash,'seller_id'=>0,'order_id'=>$o->get_id(),'contact_hash'=>self::contact($o),'expires'=>time()+1800,'used'=>false,'site'=>home_url()],true));
        $url=home_url('/?kr_return_access=1#token='.$token);
        $body='<p>Open your private KnifeRevive return workspace. This link expires in 30 minutes and can be used once.</p><p><a href="'.esc_url($url).'">Open return workspace</a></p><p>If you did not request it, ignore this email.</p>';
        if(!Jobs::mail(0,hash('sha256','guest:'.$hash),$o->get_billing_email(),'Your secure KnifeRevive return link',$body)) Support::fail('storage','The secure link could not be queued.',503);
        \KREV_Return_Mail::schedule();return$generic;
    }
    public static function sameOrigin(): bool {
        $origin=$_SERVER['HTTP_ORIGIN']??'';$home=wp_parse_url(home_url());$given=$origin?wp_parse_url($origin):[];
        return $origin && ($given['host']??null)===($home['host']??null)&&($given['scheme']??null)===($home['scheme']??null)&&($given['port']??null)===($home['port']??null);
    }
    public static function exchange(string $token): array {
        if(headers_sent()) Support::fail('session','Reload the secure-access page before continuing.',503);
        if(!self::sameOrigin()||!preg_match('/^[a-f0-9]{64}$/D',$token)) Support::fail('guest','This return-access link is invalid or expired.',403);
        $hash=self::digest($token);$db=Application::db();$session=bin2hex(random_bytes(32));$sessionHash=self::digest($session);$csrf=bin2hex(random_bytes(24));
        $saved=$db->transaction(function()use($hash,$db,$sessionHash,$csrf){
            $grant=$db->get('return_guest_grant',$hash);
            if(!$grant||$grant['used']||$grant['expires']<=time()||$grant['site']!==home_url()) Support::fail('guest','This return-access link is invalid or expired.',403);
            $order=wc_get_order($grant['order_id']);
            if(!$order||$order->get_customer_id()||!hash_equals($grant['contact_hash'],self::contact($order))) Support::fail('guest','Request a new secure link for the current order contact.',403);
            $grant['used']=true;$grant['used_at']=time();$db->put('return_guest_grant',$hash,$grant);
            $record=['id'=>$sessionHash,'seller_id'=>0,'order_id'=>$grant['order_id'],'contact_hash'=>$grant['contact_hash'],'expires'=>time()+1200,'csrf'=>$csrf,'site'=>home_url(),'revoked'=>false];
            $db->put('return_guest_session',$sessionHash,$record,true);return$record;
        });
        if(!headers_sent())setcookie('kr_return_session',$session,['expires'=>$saved['expires'],'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
        return ['url'=>home_url('/?kr_returns_order='.$saved['order_id']),'csrf'=>$csrf,'expires'=>$saved['expires']];
    }
    public static function session(): ?array {
        $token=$_COOKIE['kr_return_session']??'';
        if(!is_string($token)||!preg_match('/^[a-f0-9]{64}$/D',$token))return null;
        $r=Application::db()->get('return_guest_session',self::digest($token));
        if(!$r||!empty($r['revoked'])||$r['expires']<=time()||$r['site']!==home_url())return null;
        $o=wc_get_order($r['order_id']);return $o&&!$o->get_customer_id()&&hash_equals($r['contact_hash']??'',self::contact($o))?$r:null;
    }
    private static function contact(\WC_Order $o): string {return self::digest(strtolower($o->get_billing_email()).':'.$o->get_id());}
    public static function authorized(\WC_Order $root): bool {return (self::session()['order_id']??0)===$root->get_id();}
    public static function identity(\WC_Order $root): string {
        $s=self::session();if(!$s||$s['order_id']!==$root->get_id())Support::fail('guest','Request a fresh secure access link.',401);
        return 'guest-'.$s['id'];
    }
    public static function csrf(\WP_REST_Request $request): bool {
        $s=self::session();$header=$request->get_header('X-KR-Return-CSRF');
        return self::sameOrigin()&&$s&&is_string($header)&&hash_equals($s['csrf'],$header);
    }
}
