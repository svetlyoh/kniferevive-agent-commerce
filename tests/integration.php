<?php
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Api,Commerce,Domain,Fault,Payments,Settings,Store};
$passed=0;
echo 'Order storage: '.(\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()?'HPOS':'legacy').' ('.\WC_Data_Store::load('order')->get_current_class_name().")\n";
function check(bool $condition,string $label): void { global $passed; if (!$condition) throw new RuntimeException('FAIL: '.$label); $passed++; echo "PASS: $label\n"; }
function rejected(callable $work,string $code,string $label): void {
    try { $work(); } catch (Fault $e) { check($e->codeName===$code,$label.' ('.$e->codeName.')'); return; }
    throw new RuntimeException('FAIL: '.$label.' did not reject');
}
function api(string $method,string $path,array $body=[],string $token='',string $key=''): WP_REST_Response {
    $r=new WP_REST_Request($method,'/kniferevive-agent/v1'.$path);
    if ($method==='POST') { $r->set_header('Content-Type','application/json'); $r->set_body(json_encode((object)$body)); }
    if ($token) $r->set_header('X-Krev-Agent-Session',$token);
    if ($key) $r->set_header('Idempotency-Key',$key);
    return rest_do_request($r);
}
function resetClock(string $id): void { $a=Store::get($id)['data']; $a['checked_at']=0; Store::update($id,$a); }

// Truncate only the dedicated sandbox tables, with a fixed verified prefix.
if ($wpdb->prefix!=='krev_sandbox_' || DB_NAME!=='krev_agent_sandbox') throw new RuntimeException('Sandbox fence rejected.');
foreach (['records','idem','holds','slots'] as $suffix) $wpdb->query('TRUNCATE TABLE '.Store::table($suffix));
wp_set_current_user(1);
update_option('woocommerce_calc_taxes','no');
update_option('woocommerce_manage_stock','yes');
wp_insert_term('Knife Sharpening','product_cat',['slug'=>'knife-sharpening']);
$term=get_term_by('slug','knife-sharpening','product_cat');
$p=new WC_Product_Simple(); $p->set_name('Synthetic service <b>knife</b>'); $p->set_regular_price('7.00'); $p->set_price('7.00'); $p->set_status('publish'); $p->set_virtual(true); $p->set_category_ids([$term->term_id]); $p->save();
wp_update_post(['ID'=>$p->get_id(),'post_author'=>1]);
$slots=[['id'=>'intake-test','kind'=>'customer_dropoff','start'=>gmdate('Y-m-d\TH:i:s\Z',time()+86400),'end'=>gmdate('Y-m-d\TH:i:s\Z',time()+90000),'capacity'=>1],
    ['id'=>'return-test','kind'=>'customer_collection','start'=>gmdate('Y-m-d\TH:i:s\Z',time()+172800),'end'=>gmdate('Y-m-d\TH:i:s\Z',time()+176400),'capacity'=>1]];
$settings=Settings::validate(['enabled'=>true,'pricing_verified'=>true,'stripe_enabled'=>true,'merchant_ids'=>[1],
    'services'=>[['product_id'=>$p->get_id(),'definition'=>'Synthetic ordinary sharpening test service.']], 'postal_codes'=>['94110'],'location'=>'Synthetic test location',
    'policy_url'=>'https://kniferevive.com/test-service-policy/','policy_version'=>'fixture-1','slots'=>$slots]);
update_option('krev_agent_settings',$settings,false); Store::syncSlots($slots);
$session=api('POST','/sessions',[],'','session-first-fixture')->get_data(); $owner=$session['session_id']; $token=$session['session_token'];
$other=api('POST','/sessions',[],'','session-other-fixture')->get_data();
check($session===api('POST','/sessions',[],'','session-first-fixture')->get_data(),'session issuance is idempotent');
check(api('GET','/capabilities')->get_status()===200,'anonymous capabilities');
$catalog=new WP_REST_Request('GET','/kniferevive-agent/v1/catalog'); $catalog->set_query_params(['category'=>'sharpening']);
$catalogResponse=rest_do_request($catalog);
check($catalogResponse->get_status()===200,'anonymous structured catalog');
check(api('POST','/quotes',['approved'=>true],$token,'invalid-quote-fixture')->get_status()===422,'unknown action fields rejected');
check(api('POST','/quotes',[],Domain::token(Domain::id()),'invalid-token-fixture')->get_status()===404,'forged or absent session rejected');
check(Domain::cents('12.09')===1209,'integer money conversion');
rejected(static fn()=>Domain::cents('1e9'),'INVALID_AMOUNT','scientific money rejected');
check(!Domain::httpsHost('https://checkout.stripe.com.evil.invalid/','checkout.stripe.com'),'lookalike payment host rejected');
check(!Domain::httpsHost('https://attacker@checkout.stripe.com/','checkout.stripe.com'),'URL userinfo rejected');
rejected(static fn()=>Domain::slotTime('2026-11-01T01:30:00-05:00'),'INVALID_SETTINGS','incorrect DST offset rejected');
check(Domain::slotTime('2026-11-01T01:30:00-07:00')!==Domain::slotTime('2026-11-01T01:30:00-08:00'),'DST duplicate hour distinguished');
$_SERVER['HTTP_ORIGIN']='https://attacker.invalid'; $_SERVER['HTTP_SEC_FETCH_SITE']='cross-site';
check(!Api::firstPartyForm(),'foreign review form origin rejected');
$_SERVER['HTTP_ORIGIN']='null'; $_SERVER['HTTP_SEC_FETCH_SITE']='same-origin';
check(Api::firstPartyForm(),'same-origin browser form under no-referrer accepted');
$_SERVER['HTTP_ORIGIN']=WP_HOME; unset($_SERVER['HTTP_SEC_FETCH_SITE']);
$duplicateService=$settings; $duplicateService['services'][]=$settings['services'][0];
rejected(static fn()=>Settings::validate($duplicateService),'INVALID_SETTINGS','ambiguous duplicate service definitions rejected');

// Load the actual installed gateway-fee calculator, with a synthetic fee option.
require_once ABSPATH.'wp-content/plugins/conditional-extra-fees-for-woocommerce/includes/class-common-cart.php';
require_once ABSPATH.'wp-content/plugins/conditional-extra-fees-for-woocommerce/public/class-apply-payment-processing-fee.php';
update_option('pisol_cefw_payment_gateway_charges',['stripe'=>['apply_fee'=>1,'amount'=>1,'fee_type'=>'fixed','name'=>'Synthetic processing fee']]);
$input=['items'=>[['product_id'=>$p->get_id(),'quantity'=>2]],'postal_code'=>'94110','intake'=>['kind'=>'customer_dropoff','slot_id'=>'intake-test'],
    'return'=>['kind'=>'customer_collection','slot_id'=>'return-test'],'rail'=>'stripe_checkout','booking_mode'=>'scheduled',
    'customer'=>['name'=>'Synthetic Shopper','email'=>'shopper@example.invalid','billing'=>['address_1'=>'123 Fixture Street','city'=>'San Francisco','state'=>'CA','postcode'=>'94110','country'=>'US']]];
// Merchant round-trip pricing must preserve the odd cent and address verification.
$courierSettings=$settings; $courierSettings['pending_scheduling']=true;
$courierSettings['transport']=[['kind'=>'courier_pickup','fee_minor'=>400,'taxable'=>false,'tax_class'=>''],['kind'=>'courier_delivery','fee_minor'=>400,'taxable'=>false,'tax_class'=>'']];
$courierSettings['transport_round_trip_minor']=799;
update_option('krev_agent_settings',Settings::validate($courierSettings),false);
$courierInput=$input; $courierInput['booking_mode']='pending_scheduling';
$courierInput['intake']=['kind'=>'courier_pickup','address'=>$input['customer']['billing']];
$courierInput['return']=['kind'=>'courier_delivery','address'=>$input['customer']['billing']];
rejected(static fn()=>Commerce::price($courierInput),'ADDRESS_REVIEW_REQUIRED','round-trip discount never bypasses address verification');
$fixtureAddressVerifier=static fn()=>true; add_filter('krev_agent_address_verified',$fixtureAddressVerifier);
$roundTrip=Commerce::price($courierInput);
check($roundTrip['total_minor']===2299,'synthetic combined-price fixture preserves its exact total');
$roundTripFees=array_column($roundTrip['fees'],'amount','name');
check(Domain::cents(wc_format_decimal($roundTripFees['Sharpening courier pickup'],2))===400 && Domain::cents(wc_format_decimal($roundTripFees['Sharpening return delivery'],2))===399,'round-trip fee lines allocate the odd cent deterministically');
$oneWay=$courierInput; $oneWay['return']=['kind'=>'customer_collection'];
check(Commerce::price($oneWay)['total_minor']===1900,'synthetic one-way pickup preserves its configured fee');
$oneWay=$courierInput; $oneWay['intake']=['kind'=>'customer_dropoff'];
check(Commerce::price($oneWay)['total_minor']===1900,'synthetic one-way return preserves its configured fee');
$selfHandoff=$oneWay; $selfHandoff['return']=['kind'=>'customer_collection'];
check(Commerce::price($selfHandoff)['total_minor']===1500,'customer drop-off and collection incur no courier fee');
$courierSettings['transport_round_trip_minor']=null; update_option('krev_agent_settings',Settings::validate($courierSettings),false);
check(Commerce::price($courierInput)['total_minor']===2300,'null combined price retains existing per-leg behavior');
$perTrip=$courierSettings; $perTrip['transport'][0]['fee_minor']=799; $perTrip['transport'][1]['fee_minor']=799; $perTrip['transport_round_trip_minor']=1598;
update_option('krev_agent_settings',Settings::validate($perTrip),false);
$merchantQuote=Commerce::price($courierInput);
check($merchantQuote['total_minor']===3098,'confirmed $7.99 per merchant trip charges $15.98 for both trips');
$merchantFees=array_column($merchantQuote['fees'],'amount','name');
check(Domain::cents(wc_format_decimal($merchantFees['Sharpening courier pickup'],2))===799 && Domain::cents(wc_format_decimal($merchantFees['Sharpening return delivery'],2))===799,'both merchant trip fee lines are $7.99');
$pickupOnly=$courierInput; $pickupOnly['return']=['kind'=>'customer_collection'];
check(Commerce::price($pickupOnly)['total_minor']===2299,'confirmed merchant pickup only charges $7.99');
check(Commerce::price($oneWay)['total_minor']===2299,'confirmed merchant return only charges $7.99');
$perTrip['transport_round_trip_minor']=null; update_option('krev_agent_settings',Settings::validate($perTrip),false);
check(Commerce::price($courierInput)['total_minor']===3098,'per-trip summation without an override also charges $15.98');
$badCourier=$courierSettings; $badCourier['transport_round_trip_minor']=801;
rejected(static fn()=>Settings::validate($badCourier),'INVALID_SETTINGS','combined fee cannot exceed separate trip fees');
$badCourier['transport_round_trip_minor']=799; $badCourier['transport'][1]['taxable']=true;
rejected(static fn()=>Settings::validate($badCourier),'INVALID_SETTINGS','combined fee rejects incompatible tax treatment');
$badCourier=$courierSettings; $badCourier['transport_round_trip_minor']=799; array_pop($badCourier['transport']);
rejected(static fn()=>Settings::validate($badCourier),'INVALID_SETTINGS','combined fee rejects incomplete courier configuration');
$staged=$settings; $staged['transport_round_trip_minor']=799;
check(Settings::validate($staged)['transport']===[],'confirmed combined price can be staged without enabling courier transport');
remove_filter('krev_agent_address_verified',$fixtureAddressVerifier);
update_option('krev_agent_settings',$settings,false);
$ordersBefore=count(wc_get_orders(['limit'=>-1]));
$oldCart=WC()->cart; $oldSession=WC()->session;
$q=Commerce::quote($input,$owner,'quote-first-fixture');
check(count(wc_get_orders(['limit'=>-1]))===$ordersBefore,'quote creates no WooCommerce order');
check((int)$wpdb->get_var('SELECT COUNT(*) FROM '.Store::table('holds'))===0,'quote creates no appointment hold');
check(WC()->cart===$oldCart && WC()->session===$oldSession,'quote restores existing cart and session');
check($q['data']['total_minor']===1500,'existing WooCommerce gateway fee included in quote');
check(Commerce::quote($input,$owner,'quote-first-fixture')['id']===$q['id'],'quote idempotency returns identical resource');
$different=$input; $different['items'][0]['quantity']=3;
rejected(static fn()=>Commerce::quote($different,$owner,'quote-first-fixture'),'IDEMPOTENCY_CONFLICT','changed input cannot reuse quote key');
rejected(static fn()=>Store::get($q['id'],'quote',$other['session_id']),'NOT_FOUND','cross-customer quote inaccessible');
$outOfArea=$input; $outOfArea['postal_code']='90001';
rejected(static fn()=>Commerce::price($outOfArea),'OUT_OF_AREA','out-of-area service rejected');
$scope=$input; $scope['requires_assessment']=true;
rejected(static fn()=>Commerce::price($scope),'ASSESSMENT_REQUIRED','assessment work cannot be prepaid blindly');
check(api('POST','/checkout-attempts',['quote_id'=>$q['id'],'quote_hash'=>$q['data']['quote_hash'],'consent_id'=>Domain::id()],$token,'unapproved-checkout')->get_status()===404,'checkout requires a server-issued consent');

$processor=['creates'=>0,'paid'=>false,'refund'=>0,'last_body'=>null,'timeout'=>false];
add_filter('pre_http_request',static function ($pre,$args,$url) use (&$processor) {
    if (!str_starts_with($url,'https://api.stripe.com/')) return $pre;
    if ($processor['timeout']) return new WP_Error('synthetic_timeout','Fixture timeout');
    parse_str($args['body']??'',$body);
    if ($args['method']==='POST' && str_ends_with($url,'/checkout/sessions')) { $processor['creates']++; $processor['last_body']=$body; }
    $body=$processor['last_body'];
    $sum=0; foreach ($body['line_items'] as $line) $sum+=(int)$line['price_data']['unit_amount'];
    $session=['id'=>'cs_test_fixture','metadata'=>$body['metadata'],'currency'=>'usd','amount_total'=>$sum,'livemode'=>false,'mode'=>'payment','url'=>'https://checkout.stripe.com/c/pay/cs_test_fixture',
        'payment_status'=>$processor['paid']?'paid':'unpaid','status'=>'open','payment_intent'=>$processor['paid']?['id'=>'pi_fixture','latest_charge'=>['id'=>'ch_fixture','currency'=>'usd','amount'=>$sum,'amount_refunded'=>$processor['refund'],'disputed'=>false]]:null];
    return ['headers'=>[],'body'=>json_encode($session),'response'=>['code'=>200,'message'=>'OK'],'cookies'=>[]];
},10,3);
$consent=Commerce::grant($q['id'],$owner);
$attemptInput=['quote_id'=>$q['id'],'quote_hash'=>$q['data']['quote_hash'],'consent_id'=>$consent];
$a=Commerce::attempt($attemptInput,$owner,'checkout-first-fixture');
check($a['data']['payment_state']==='pending','authorized hosted checkout created');
check($processor['creates']===1,'one processor session created');
check(count(wc_get_orders(['limit'=>-1]))===$ordersBefore+1,'one pending WooCommerce order created');
check(Commerce::attempt($attemptInput,$owner,'checkout-retry-fixture')['id']===$a['id'],'same quote cannot create a second attempt');
check($processor['creates']===1,'retry does not create duplicate payment session');
check(Commerce::status($a)['payment_state']!=='paid','redirect or checkout URL does not prove payment');
$boundSession=['id'=>$a['data']['provider_id'],'metadata'=>['attempt_id'=>$a['id'],'quote_hash'=>$a['data']['quote']['quote_hash']],
    'currency'=>'usd','amount_total'=>1501,'livemode'=>false,'mode'=>'payment','payment_status'=>'paid','payment_intent'=>'pi_fixture'];
rejected(static fn()=>Domain::settledSession($boundSession,$a['data'],false),'MANUAL_REVIEW_REQUIRED','wrong processor amount rejected');
$boundSession['amount_total']=1500; $boundSession['metadata']['quote_hash']=str_repeat('f',64);
rejected(static fn()=>Domain::settledSession($boundSession,$a['data'],false),'MANUAL_REVIEW_REQUIRED','wrong processor quote binding rejected');
$secondQuoteInput=$input; $secondQuoteInput['customer']['email']='second@example.invalid';
rejected(static fn()=>Commerce::quote($secondQuoteInput,$other['session_id'],'quote-second-fixture'),'SLOT_UNAVAILABLE','final held appointment unavailable to another shopper');
resetClock($a['id']); $processor['paid']=true; Payments::reconcile($a['id']); $settled=Store::get($a['id']);
check($settled['data']['payment_state']==='paid','verified payment marks paid');
check($settled['data']['booking_state']==='confirmed','verified payment confirms both held windows');
$o=wc_get_order($settled['data']['order_id']); check($o->get_transaction_id()==='pi_fixture','WooCommerce stores verified payment reference');
resetClock($a['id']); Payments::reconcile($a['id']);
check(count(wc_get_orders(['limit'=>-1]))===$ordersBefore+1,'duplicate settlement creates no extra order');
check(api('GET','/orders/'.$a['id'],[],$other['session_token'])->get_status()===404,'cross-customer order access rejected');
$event=json_encode(['id'=>'evt_fixture','livemode'=>false,'type'=>'checkout.session.completed','data'=>['object'=>['metadata'=>['attempt_id'=>$a['id']]]]]);
$header='t='.time().',v1='.hash_hmac('sha256',time().'.'.$event,KREV_AGENT_STRIPE_TEST_WEBHOOK_SECRET);
check(Payments::webhook($event,$header)['received']===true,'signed webhook accepted');
check(Payments::webhook($event,$header)['received']===true,'duplicate webhook is harmless');
rejected(static fn()=>Payments::webhook($event.' ',$header),'INVALID_SIGNATURE','tampered webhook rejected');
check(!Domain::stripeSignature($event,'t='.(time()-1000).',v1=invalid',KREV_AGENT_STRIPE_TEST_WEBHOOK_SECRET,time()),'stale signature rejected');
$processor['refund']=1500; resetClock($a['id']); Payments::reconcile($a['id']); $refunded=Store::get($a['id']);
check($refunded['data']['payment_state']==='refunded' && $refunded['data']['booking_state']==='cancelled','verified full refund cancels booking');
check(Domain::cents(wc_format_decimal(wc_get_order($o->get_id())->get_total_refunded(),2))===1500,'external refund reconciled into WooCommerce');
resetClock($a['id']); Payments::reconcile($a['id']);
check(count(wc_get_order($o->get_id())->get_refunds())===1,'duplicate refund evidence does not double refund accounting');
check(Store::get($a['id'])['data']['payment_state']==='refunded' && Store::get($a['id'])['data']['booking_state']==='cancelled','duplicate refund reconciliation retains terminal payment and booking state');
$change=api('POST','/orders/'.$a['id'].'/change-requests',['action'=>'reschedule','reason'=>'Synthetic test'], $token,'change-first-fixture');
check($change->get_data()['refund_state']==='not_issued','change request does not assert a refund');

// Late payment: release an expired hold, then independently verify that it is never reclaimed.
$late=Domain::id(); Store::transaction(static function () use ($late) { Store::hold([['slot_id'=>'intake-test','kind'=>'customer_dropoff']],$late,time()+30); });
$wpdb->update(Store::table('holds'),['expires'=>time()-1],['attempt_id'=>$late]);
check(Store::confirm($late,time())===false,'late payment cannot confirm an expired hold');
Store::release($late);
check(Store::confirm($late,time()-10)===false,'released hold never reclaimed with older payment time');
check(Store::confirm(Domain::id(),time())===false,'missing reservation cannot claim a confirmed booking');
rejected(static fn()=>Domain::fields(['approved'=>true],['quote_id']),'INVALID_REQUEST','approval booleans are not an authorization channel');
// Tax calculation uses real WooCommerce rates, not a hand-written percentage formula.
$rateId=WC_Tax::_insert_tax_rate(['tax_rate_country'=>'US','tax_rate_state'=>'CA','tax_rate'=>'10.0000','tax_rate_name'=>'Synthetic tax','tax_rate_priority'=>1,'tax_rate_compound'=>0,'tax_rate_shipping'=>0,'tax_rate_order'=>1,'tax_rate_class'=>'']);
update_option('woocommerce_calc_taxes','yes'); WC_Cache_Helper::invalidate_cache_group('taxes');
$taxed=Commerce::price($input);
check($taxed['tax_minor']===140 && $taxed['total_minor']===1640,'WooCommerce tax and non-taxable fee computed correctly');
WC_Tax::_delete_tax_rate($rateId); update_option('woocommerce_calc_taxes','no'); WC_Cache_Helper::invalidate_cache_group('taxes');

$courierSettings=$settings;
$courierSettings['transport']=[['kind'=>'courier_pickup','fee_minor'=>300,'taxable'=>false,'tax_class'=>''],['kind'=>'courier_delivery','fee_minor'=>400,'taxable'=>false,'tax_class'=>'']];
$courierSettings['pending_scheduling']=true; update_option('krev_agent_settings',$courierSettings,false);
$courier=$input; $courier['booking_mode']='pending_scheduling';
$courier['intake']=['kind'=>'courier_pickup','address'=>$input['customer']['billing']];
$courier['return']=['kind'=>'courier_delivery','address'=>$input['customer']['billing']];
rejected(static fn()=>Commerce::price($courier),'ADDRESS_REVIEW_REQUIRED','postal eligibility cannot approve a courier address');
$verifier=static fn($approved,$address,$kind) => $address['address_1']==='123 Fixture Street';
add_filter('krev_agent_address_verified',$verifier,10,3); $transportQuote=Commerce::price($courier); remove_filter('krev_agent_address_verified',$verifier,10);
check($transportQuote['total_minor']===2200 && count($transportQuote['fees'])===3,'pickup and delivery priced as separate legs');
check($transportQuote['booking_mode']==='pending_scheduling','unscheduled prepayment retains pending appointment mode');
update_option('krev_agent_settings',$settings,false);

// Price changes require a new consent; a payment timeout retains the same attempt.
$stale=Commerce::quote($input,$owner,'quote-stale-fixture'); $p->set_price('8.00'); $p->set_regular_price('8.00'); $p->save();
rejected(static fn()=>Commerce::grant($stale['id'],$owner),'QUOTE_CHANGED','price change invalidates previous quote approval');
$p->set_price('7.00'); $p->set_regular_price('7.00'); $p->save();
$unknown=Commerce::quote($input,$owner,'quote-timeout-fixture'); $unknownConsent=Commerce::grant($unknown['id'],$owner);
$railQuote=Commerce::quote($input,$owner,'quote-rail-switch-fixture'); $railConsent=Commerce::grant($railQuote['id'],$owner);
$processor['timeout']=true; $unknownRequest=['quote_id'=>$unknown['id'],'quote_hash'=>$unknown['data']['quote_hash'],'consent_id'=>$unknownConsent];
$unknownAttempt=Commerce::attempt($unknownRequest,$owner,'checkout-timeout-fixture');
check($unknownAttempt['data']['payment_state']==='unknown','processor timeout is unknown, not failure or paid');
check(Commerce::attempt($unknownRequest,$owner,'checkout-timeout-fixture')['id']===$unknownAttempt['id'],'timeout retries retain the original attempt');
$pauseData=Store::get($unknownAttempt['id'])['data']; $pauseData['failures']=7; Store::update($unknownAttempt['id'],$pauseData);
Payments::start($unknownAttempt['id']); $paused=Store::get($unknownAttempt['id'])['data'];
check($paused['failures']===8 && $paused['auto_paused'] && $paused['next_check_at']>time(),'repeated payment failures pause automatic retries with backoff');
check(!in_array($unknownAttempt['id'],array_column(Store::attemptsForJobs(),'id'),true),'paused payment excluded from background jobs');
$railRequest=['quote_id'=>$railQuote['id'],'quote_hash'=>$railQuote['data']['quote_hash'],'consent_id'=>$railConsent];
$railData=Store::get($unknownAttempt['id'])['data']; $railData['quote']['rail']='lightning'; Store::update($unknownAttempt['id'],$railData);
rejected(static fn()=>Commerce::attempt($railRequest,$owner,'checkout-rail-switch-fixture'),'PAYMENT_UNRESOLVED','fresh quote cannot switch rails around an unresolved equivalent purchase');
$railData['quote']['rail']='stripe_checkout'; Store::update($unknownAttempt['id'],$railData);
$unknownData=$unknownAttempt['data']; $unknownData['hold_expires']=time()-1; Store::update($unknownAttempt['id'],$unknownData); resetClock($unknownAttempt['id']);
Payments::reconcile($unknownAttempt['id']);
check(Store::get($unknownAttempt['id'])['data']['booking_state']==='expired','unknown creation releases expired appointment holds');
$processor['timeout']=false;

// A verified payment cannot consume a stock allocation that already expired.
$processor['paid']=false; $processor['refund']=0;
$stockProduct=new WC_Product_Simple(); $stockProduct->set_name('Synthetic stock-controlled service'); $stockProduct->set_regular_price('7.00'); $stockProduct->set_price('7.00');
$stockProduct->set_status('publish'); $stockProduct->set_virtual(true); $stockProduct->set_category_ids([$term->term_id]); $stockProduct->set_manage_stock(true); $stockProduct->set_stock_quantity(2); $stockProduct->save();
wp_update_post(['ID'=>$stockProduct->get_id(),'post_author'=>1]);
$stockSettings=$settings; $stockSettings['pending_scheduling']=true; $stockSettings['services'][]=['product_id'=>$stockProduct->get_id(),'definition'=>'Synthetic stock-controlled sharpening test']; update_option('krev_agent_settings',$stockSettings,false);
$stockInput=$input; $stockInput['booking_mode']='pending_scheduling'; unset($stockInput['intake']['slot_id'],$stockInput['return']['slot_id']);
$stockInput['items'][0]['product_id']=$stockProduct->get_id();
$stockQuote=Commerce::quote($stockInput,$owner,'quote-stock-fixture'); $stockConsent=Commerce::grant($stockQuote['id'],$owner);
$stockAttempt=Commerce::attempt(['quote_id'=>$stockQuote['id'],'quote_hash'=>$stockQuote['data']['quote_hash'],'consent_id'=>$stockConsent],$owner,'checkout-stock-fixture');
check($stockAttempt['data']['payment_state']==='pending','stock-managed order creates checkout after native reservation');
check((int)$wpdb->get_var($wpdb->prepare('SELECT stock_quantity FROM '.$wpdb->prefix.'wc_reserved_stock WHERE order_id=%d',$stockAttempt['data']['order_id']))===2,'native stock reservation exists before checkout is returned');
$wpdb->query($wpdb->prepare('UPDATE '.$wpdb->prefix.'wc_reserved_stock SET expires=NOW()-INTERVAL 1 MINUTE WHERE order_id=%d',$stockAttempt['data']['order_id']));
$processor['paid']=true; resetClock($stockAttempt['id']); Payments::reconcile($stockAttempt['id']);
check(Store::get($stockAttempt['id'])['data']['payment_state']==='review_required' && !wc_get_order($stockAttempt['data']['order_id'])->is_paid(),'expired stock allocation requires review before fulfillment');
update_option('krev_agent_settings',$settings,false);

// Two processes compete for one InnoDB-locked job slot.
$raceSlot=['id'=>'race-fixture','kind'=>'customer_dropoff','start'=>gmdate('Y-m-d\TH:i:s\Z',time()+86400),'end'=>gmdate('Y-m-d\TH:i:s\Z',time()+90000),'capacity'=>1];
Store::syncSlots([...$slots,$raceSlot]);
$command=[PHP_BINARY,'-d','extension_dir='.ini_get('extension_dir'),'-d','extension=mysqli','-d','extension=mbstring','-d','extension=openssl',__DIR__.'/race-worker.php',$argv[1]];
$workers=[];
for ($i=0;$i<2;$i++) { $pipes=[]; $process=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes); fclose($pipes[0]); $workers[]=[$process,$pipes]; }
$results=[];
foreach ($workers as [$process,$pipes]) { $results[]=trim(stream_get_contents($pipes[1])); $err=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]); check(proc_close($process)===0,'reservation worker completed'); }
sort($results); check($results===['RESERVED','SLOT_UNAVAILABLE'],'concurrent buyers cannot overbook the final slot');

// Exercise the installed Lightning coordinator and bridge client with HTTP fixtures only.
foreach (['Settings','Repository','Client','Coordinator','FeeWaiver'] as $class) require_once ABSPATH.'wp-content/plugins/kniferevive-lightning-payments/includes/'.$class.'.php';
\KnifeRevive\Lightning\Repository::install();
$wpdb->query('TRUNCATE TABLE '.$wpdb->prefix.'krev_ln_attempts');
$wpdb->query('TRUNCATE TABLE '.$wpdb->prefix.'krev_ln_events');
foreach (['accept'=>'yes','merchants'=>'1','bridge_url'=>'https://lightning-api.kniferevive.com','token'=>str_repeat('synthetic',8),'store_id'=>'sandbox','lifetime'=>600,'maximum'=>10000] as $key=>$value) \KnifeRevive\Lightning\Settings::set($key,$value);
\KnifeRevive\Lightning\FeeWaiver::register();
$lightning=['creates'=>0,'settled'=>false,'invoice'=>null];
add_filter('pre_http_request',static function ($pre,$args,$url) use (&$lightning) {
    if (!str_starts_with($url,'https://lightning-api.kniferevive.com/')) return $pre;
    if ($args['method']==='POST') {
        $lightning['creates']++; $body=json_decode($args['body'],true);
        if (!$lightning['invoice']) $lightning['invoice']=array_merge($body,['state'=>'awaiting-payment','bolt11'=>'lnbcsyntheticfixture','payment_hash'=>str_repeat('a',64),'wallet_id'=>'fixture-wallet','checking_id'=>'fixture-check',
            'network'=>'bc','amount_sat'=>$body['fiat_minor']*10,'amount_msat'=>$body['fiat_minor']*10000,'expires_at'=>time()+600,'rate'=>'100000','quote_at'=>time()]);
    }
    $data=$lightning['invoice']; if ($lightning['settled']) $data['state']='settled';
    return ['headers'=>[],'body'=>json_encode($data),'response'=>['code'=>200,'message'=>'OK'],'cookies'=>[]];
},10,3);
$lightningSettings=$settings; $lightningSettings['lightning_enabled']=true; $lightningSettings['live_verified']=true; update_option('krev_agent_settings',$lightningSettings,false);
$lnInput=$input; $lnInput['rail']='lightning'; $lnInput['items'][0]['quantity']=1; // Different purchase from the unresolved two-knife fixture.
$lnQuote=Commerce::quote($lnInput,$owner,'quote-lightning-fixture');
$lnConsent=Commerce::grant($lnQuote['id'],$owner); $lnRequest=['quote_id'=>$lnQuote['id'],'quote_hash'=>$lnQuote['data']['quote_hash'],'consent_id'=>$lnConsent];
$lnAttempt=Commerce::attempt($lnRequest,$owner,'checkout-lightning-fixture');
check($lnAttempt['data']['payment_state']==='pending' && $lightning['creates']===1,'installed Lightning coordinator creates one order-bound invoice');
check(Commerce::attempt($lnRequest,$owner,'checkout-lightning-retry')['id']===$lnAttempt['id'] && $lightning['creates']===1,'Lightning retry does not create another invoice');
$lightning['settled']=true; resetClock($lnAttempt['id']); Payments::reconcile($lnAttempt['id']); $lnPaid=Store::get($lnAttempt['id']);
check($lnPaid['data']['payment_state']==='paid' && $lnPaid['data']['booking_state']==='confirmed','installed Lightning settlement confirms payment and booking');
check(wc_get_order($lnPaid['data']['order_id'])->get_transaction_id()===str_repeat('a',64),'Lightning order stores verified payment hash');
$lnRow=\KnifeRevive\Lightning\Repository::for_order($lnPaid['data']['order_id']); $badInvoice=json_decode($lnRow->payload,true); $badInvoice['network']='tb';
try { \KnifeRevive\Lightning\Client::validate($lnRow,$badInvoice); throw new RuntimeException('Wrong network accepted'); } catch (RuntimeException $e) { check($e->getMessage()==='Invalid payment binding.','installed Lightning client rejects wrong network'); }
$badInvoice=json_decode($lnRow->payload,true); $badInvoice['amount_msat']++;
try { \KnifeRevive\Lightning\Client::validate($lnRow,$badInvoice); throw new RuntimeException('Wrong amount accepted'); } catch (RuntimeException $e) { check($e->getMessage()==='Invalid payment binding.','installed Lightning client rejects wrong amount'); }
update_option('krev_agent_settings',Settings::defaults(),false);
check(Settings::rails()===[],'default configuration disables all payments');
file_put_contents(dirname(__DIR__).'/.runtime/contract-samples.json',json_encode(['Session'=>$session,'Catalog'=>$catalogResponse->get_data(),'Quote'=>Api::quoteResponse($q,$owner),'Status'=>Commerce::status($lnPaid),'Capabilities'=>Api::capabilities(null),'QuoteInput'=>$input,'CheckoutInput'=>$attemptInput],JSON_PRETTY_PRINT));
echo "\n$passed behavioral assertions passed. External requests and customer emails were blocked.\n";
