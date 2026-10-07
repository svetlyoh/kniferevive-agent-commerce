<?php
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Settings,StripeSetup,Fault};
if (($argv[2]??'')!=='setup' || DB_NAME!=='krev_agent_sandbox' || $wpdb->prefix!=='krev_sandbox_') throw new RuntimeException('Sandbox fence failed.');
$passed=0;
$savedPermalinks=get_option('permalink_structure');update_option('permalink_structure','/%postname%/');
function check(bool $ok,string $label): void {global $passed; if(!$ok)throw new RuntimeException('FAIL: '.$label);$passed++;echo 'PASS: '.$label."\n";}
function rejects(callable $f,string $code,string $label): void {try{$f();}catch(Fault $e){check($e->codeName===$code,$label);return;}throw new RuntimeException('FAIL: '.$label);}
delete_option('krev_agent_stripe_test_webhook');
update_option('krev_agent_settings',Settings::defaults(),false);
update_option('woocommerce_stripe_settings',['test_secret_key'=>'sk_test_fixtureonly','secret_key'=>'sk_live_fixtureonly']);
check(Settings::stripeKey(false)==='','gateway keys require explicit opt-in');
$s=Settings::get();$s['stripe_use_woocommerce_keys']=true;update_option('krev_agent_settings',$s,false);
check(Settings::stripeKey(false)==='sk_test_fixtureonly','test mode uses only test key');
check(Settings::stripeKey(true)==='sk_live_fixtureonly','live mode uses only live key');
$sealed=StripeSetup::seal('whsec_fixtureonly');
check(!str_contains($sealed,'fixtureonly') && StripeSetup::open($sealed)==='whsec_fixtureonly','signing secret encrypted and recovered');
$raw=base64_decode($sealed);$raw[0]=chr(ord($raw[0])^1);
check(StripeSetup::open(base64_encode($raw))==='','ciphertext tampering rejected');
check(StripeSetup::open('invalid')==='','invalid ciphertext rejected');
wp_set_current_user(0);
rejects(static fn()=>StripeSetup::testWebhook(),'AUTHORIZATION_REQUIRED','guest cannot register processor endpoint');
wp_set_current_user(1);$calls=0;$requests=[];$fail=true;
add_filter('pre_http_request',static function($pre,$args,$url)use(&$calls,&$requests,&$fail){
 if($url!=='https://api.stripe.com/v1/webhook_endpoints')return $pre;
 $calls++;$requests[]=$args;
 if($fail)return new WP_Error('fixture_timeout','Synthetic timeout');
 parse_str($args['body'],$body);
 return ['headers'=>[],'body'=>json_encode(['id'=>'we_fixtureonly','livemode'=>false,'url'=>$body['url'],'secret'=>'whsec_fixtureonly']),'response'=>['code'=>200],'cookies'=>[]];
},10,3);
rejects(static fn()=>StripeSetup::testWebhook(),'PAYMENT_STATUS_UNKNOWN','uncertain registration retains original request');
$fail=false;StripeSetup::testWebhook();
check($calls===2 && $requests[0]['headers']['Idempotency-Key']===$requests[1]['headers']['Idempotency-Key'],'registration retry uses original idempotency key');
check($requests[1]['sslverify']===true && $requests[1]['redirection']===0,'processor transport verifies TLS and forbids redirects');
parse_str($requests[1]['body'],$body);
check($body['url']==='https://kniferevive.com/wp-json/kniferevive-agent/v1/stripe/webhook' && count($body['enabled_events'])===5 && !isset($body['connect']),'endpoint targets merchant URL and limited account events');
check(Settings::webhookSecret(false)==='whsec_fixtureonly' && Settings::webhookSecret(true)==='','test signing secret cannot become live signing secret');
$stored=get_option('krev_agent_stripe_test_webhook');
check(!str_contains(json_encode($stored),'whsec_fixtureonly'),'database option contains no plaintext secret');
StripeSetup::testWebhook();check($calls===2,'repeated setup does not register duplicate endpoint');
update_option('woocommerce_stripe_settings',['test_secret_key'=>'sk_test_changed']);
rejects(static fn()=>StripeSetup::testWebhook(),'MANUAL_REVIEW_REQUIRED','credential change requires original endpoint review');
check(Settings::rails()===[],'setup leaves payments disabled');
delete_option('krev_agent_stripe_test_webhook');update_option('krev_agent_settings',Settings::defaults(),false);
update_option('permalink_structure',$savedPermalinks);
echo "$passed merchant setup assertions passed; all processor requests were fixtures.\n";
