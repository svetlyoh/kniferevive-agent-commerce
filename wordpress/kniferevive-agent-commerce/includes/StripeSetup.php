<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;
/** Merchant-only test onboarding; no credentials are returned through the public API. */
final class StripeSetup {
    private static function key(): string { return hash('sha256',wp_salt('auth').'|krev-agent-webhooks',true); }
    public static function seal(string $secret): string {
        if (!function_exists('openssl_encrypt')) Domain::fail('UNCONFIGURED','OpenSSL is required to protect the webhook signing secret.',503);
        $iv=random_bytes(12); $tag='';
        $cipher=openssl_encrypt($secret,'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,$iv,$tag,'krev-agent-v1',16);
        if ($cipher===false) Domain::fail('UNCONFIGURED','Webhook secret encryption failed.',503);
        return base64_encode($iv.$tag.$cipher);
    }
    public static function open(string $value): string {
        if (!function_exists('openssl_decrypt')) return '';
        $raw=base64_decode($value,true);
        if ($raw===false || strlen($raw)<29) return '';
        $plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16),'krev-agent-v1');
        return is_string($plain) && preg_match('/^whsec_[A-Za-z0-9]+$/D',$plain) ? $plain : '';
    }
    public static function secret(bool $live): string {
        $row=(array)get_option($live?'krev_agent_stripe_live_webhook':'krev_agent_stripe_test_webhook',[]);
        return self::open((string)($row['encrypted_secret']??''));
    }
    public static function testWebhook(): void {
        if (!current_user_can('manage_options')) Domain::fail('AUTHORIZATION_REQUIRED','Administrator access is required.',403);
        $url=rest_url(Api::NS.'/stripe/webhook');
        if (!Domain::httpsHost($url,'kniferevive.com')) Domain::fail('UNCONFIGURED','Register this webhook from the HTTPS kniferevive.com administration page.',503);
        $key=Settings::stripeKey(false);
        if (!preg_match('/^sk_test_[A-Za-z0-9]+$/D',$key)) Domain::fail('UNCONFIGURED','A Stripe test secret key must be configured first.',503);
        Store::lock('stripe-test-webhook-setup',static function() use ($url,$key) {
            $option='krev_agent_stripe_test_webhook'; $row=(array)get_option($option,[]);
            if (!empty($row['encrypted_secret'])) {
                if (!self::secret(false) || ($row['url']??'')!==$url || !hash_equals((string)($row['key_hash']??''),hash('sha256',$key))) Domain::fail('MANUAL_REVIEW_REQUIRED','The stored test webhook needs administrator review after a URL, key, or encryption-key change.',409);
                return;
            }
            if (!$row) {
                // Preflight encryption before creating an external credential.
                self::seal('whsec_preflight');
                $row=['request_id'=>Domain::id(),'created_at'=>time(),'url'=>$url,'key_hash'=>hash('sha256',$key)];
                update_option($option,$row,false);
            }
            if (time()-(int)$row['created_at']>23*3600 || $row['url']!==$url || !hash_equals($row['key_hash'],hash('sha256',$key))) Domain::fail('MANUAL_REVIEW_REQUIRED','Resolve the original test webhook setup in Stripe before issuing another registration.',409);
            $r=wp_remote_post('https://api.stripe.com/v1/webhook_endpoints',[
                'headers'=>['Authorization'=>'Bearer '.$key,'Content-Type'=>'application/x-www-form-urlencoded','Stripe-Version'=>'2025-03-31.basil','Idempotency-Key'=>'krev-agent-test-webhook-'.$row['request_id']],
                'body'=>http_build_query(['url'=>$url,'api_version'=>'2025-03-31.basil','description'=>'KnifeRevive Agent Commerce test events','enabled_events'=>['checkout.session.completed','checkout.session.expired','checkout.session.async_payment_succeeded','checkout.session.async_payment_failed','charge.refunded']]),
                'timeout'=>12,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>65536]);
            if (is_wp_error($r) || wp_remote_retrieve_response_code($r)!==200) Domain::fail('PAYMENT_STATUS_UNKNOWN','Stripe test webhook registration could not be verified. Retry this same setup; inspect Stripe if it remains uncertain.',503,true);
            $data=json_decode(wp_remote_retrieve_body($r),true,32,JSON_THROW_ON_ERROR);
            if (($data['livemode']??null)!==false || ($data['url']??'')!==$url || !preg_match('/^we_[A-Za-z0-9]+$/D',(string)($data['id']??'')) || !preg_match('/^whsec_[A-Za-z0-9]+$/D',(string)($data['secret']??''))) Domain::fail('MANUAL_REVIEW_REQUIRED','Stripe returned an unexpected webhook binding. Review the original registration.',409);
            $row['endpoint_id']=$data['id']; $row['encrypted_secret']=self::seal($data['secret']);
            update_option($option,$row,false);
            if (!self::secret(false)) Domain::fail('MANUAL_REVIEW_REQUIRED','The signing secret could not be retained. Review this endpoint in Stripe.',409);
        });
    }
}
