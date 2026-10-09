<?php
/** Actual WordPress/WooCommerce behavior in the isolated synthetic database only. */
ob_start();set_exception_handler(static function(Throwable $e){fwrite(STDERR,'FAIL: '.$e->getMessage()."\n");exit(1);});
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Api,Booking,BookingCoverage,Domain,Fault,ListingCheckout,Settings,Store};
if(DB_NAME!=='krev_agent_sandbox' || $wpdb->prefix!=='krev_sandbox_')throw new RuntimeException('Sandbox fence failed.');
$passed=0;
function bookingCheck(bool $ok,string $message): void {global $passed;if(!$ok)throw new RuntimeException($message);++$passed;echo 'PASS: '.$message."\n";}
function bookingReject(callable $fn,string $code): void {try{$fn();}catch(Fault $e){bookingCheck($e->codeName===$code,'rejects '.$code);return;}throw new RuntimeException('Did not reject '.$code);}
$wpdb->query('DELETE FROM '.Store::table('holds')." WHERE slot_id LIKE 'booking-%'");
$wpdb->query('DELETE FROM '.Store::table('slots')." WHERE id LIKE 'booking-%'");
wp_set_current_user(get_user_by('login','sandbox-admin')->ID);
$term=get_term_by('slug','knife-sharpening','product_cat');if(!$term){wp_insert_term('Sharpening','product_cat',['slug'=>'knife-sharpening']);$term=get_term_by('slug','knife-sharpening','product_cat');}
$vendor=wp_insert_user(["user_login"=>"booking-vendor-".Domain::id(),"user_pass"=>Domain::id(),"user_email"=>Domain::id()."@example.invalid","role"=>"seller"]);update_user_meta($vendor,"dokan_enable_selling","yes");
$p=new WC_Product_Simple();$p->set_name('Large Knife Sharpening');$p->set_status('publish');$p->set_regular_price('7');$p->set_price('7');$p->set_virtual(true);$p->set_category_ids([$term->term_id]);$p->set_manage_stock(true);$p->set_stock_quantity(100);$p->save();wp_update_post(['ID'=>$p->get_id(),'post_author'=>$vendor]);
$s=Settings::validate(['booking_require_payment_submission'=>false,'booking_trip_fee_minor'=>799,'booking_round_trip_fee_minor'=>1598,'booking_enabled'=>true,'booking_location'=>'Synthetic merchant address','booking_services'=>[['product_id'=>$p->get_id(),'definition'=>'An 8-inch chef knife is Large Knife Sharpening.']],
    'booking_weekly_hours'=>[['weekday'=>5,'open'=>'09:00','close'=>'19:00'],['weekday'=>6,'open'=>'09:00','close'=>'19:00'],['weekday'=>7,'open'=>'10:00','close'=>'16:00']]]);
update_option('krev_agent_settings',$s,false);update_option('woocommerce_calc_taxes','no');update_option('pisol_cefw_payment_gateway_charges',[]);
bookingCheck(Booking::enabled() && !Settings::operational(),'unpaid booking independent of unconfigured legacy payment settings');
bookingCheck(!Booking::prepaymentEnabled(),'prepayment remains gated');
$days=Booking::availability()['days'];bookingCheck(count($days)>5 && $days[0]['available_jobs']===null,'unknown capacity is not unlimited or confirmed');
$date=$days[1]['date'];$owner=Domain::id();$foreign=Domain::id();foreach([$owner,$foreign] as $id)Store::put($id,'session','synthetic',time()+7200,['token_hash'=>hash('sha256',Domain::token($id))]);
$input=['items'=>[['product_id'=>$p->get_id(),'quantity'=>1]],'mode'=>'pay_later_dropoff','preferred_date'=>$date,'postal_code'=>'94565'];
bookingCheck(BookingCoverage::check('94565')['prepayment_eligible'] && BookingCoverage::check('95112')['pickup_eligible'],'Contra Costa and Santa Clara ZIPs qualify for prepayment and pickup');
bookingCheck(BookingCoverage::check('94103')['message']==='You’re in our Bay Area zone! Drop-off is available. Pickup isn’t in your area yet — it’s coming in the near future.','other Bay Area ZIPs clearly distinguish drop-off and future pickup');
bookingCheck(BookingCoverage::check('90001')['message']==='Not in our zone just yet. We currently sharpen in the SF Bay Area only, so service isn’t available in this ZIP.','outside Bay Area ZIPs clearly report service unavailable');
bookingCheck(BookingCoverage::check('95033')['address_review_required'],'cross-county ZIPs require a street address review');
bookingCheck(BookingCoverage::check('00000')['coverage_state']==='address_review_required','unknown ZIP is not falsely classified outside Bay Area');
bookingCheck(Booking::create(array_replace($input,['postal_code'=>'90001']),$owner,'booking-outside-fixture')['data']['input']['postal_code']==='', 'unpaid drop-off ignores ZIP coverage');
bookingCheck(Booking::create(array_replace($input,['postal_code'=>'00000']),$owner,'booking-unknown-fixture')['data']['input']['postal_code']==='', 'unpaid drop-off ignores unknown ZIP');
bookingReject(static fn()=>Booking::create(array_replace($input,['postal_code'=>'94103','mode'=>'prepaid_pickup']),$owner,'booking-no-pickup-fixture'),'PICKUP_UNAVAILABLE');
bookingReject(static fn()=>Booking::create(array_replace($input,['postal_code'=>'94103','mode'=>'prepaid_dropoff']),$owner,'booking-no-prepay-fixture'),'PREPAYMENT_AREA_UNAVAILABLE');
$sf=Booking::create(array_replace($input,['postal_code'=>'94103']),$owner,'booking-sf-dropoff-fixture');bookingCheck($sf['data']['input']['mode']==='pay_later_dropoff','other Bay Area ZIPs can request unpaid drop-off');
$row=Booking::create($input,$owner,'booking-unpaid-fixture');$same=Booking::create($input,$owner,'booking-unpaid-fixture');bookingCheck($row['id']===$same['id'],'draft creation is idempotent');
bookingReject(static fn()=>Booking::get($row['id'],$foreign),'NOT_FOUND');
$response=Booking::response($row);bookingCheck($response['service_subtotal_minor']===700 && $response['merchant_trip_fee_minor']===0 && $response['total_minor']===null,'8-inch large service estimate is $7 with no drop-off fee');
bookingCheck(!$response['appointment_confirmed'] && $response['booking_state']==='draft','draft does not reserve appointment');
$contact=['customer'=>['name'=>'Synthetic Customer','email'=>'customer@example.invalid']];
$row=Booking::submit($row['id'],$owner,$contact,true);bookingCheck($row['data']['booking_state']==='requested' && !$row['data']['listing_intent'],'request creates no checkout or charge');
bookingCheck(Booking::submit($row['id'],$owner,$contact,true)['data']['submitted_at']===$row['data']['submitted_at'],'double submission creates no duplicate');
bookingCheck(!str_contains(json_encode(Booking::response($row)),'customer@example.invalid'),'API status does not expose private contact fields');
bookingReject(static fn()=>Booking::confirm($row['id']),'CAPACITY_UNCONFIGURED');
$s['booking_daily_capacity']=1;update_option('krev_agent_settings',$s,false);
$second=Booking::create(array_replace($input,['mode'=>'prepaid_dropoff']),$foreign,'booking-capacity-second');Booking::submit($second['id'],$foreign,$contact,true);
Booking::confirm($row['id']);bookingCheck(Booking::remaining($date)===0,'confirmed unpaid job consumes real daily capacity');
Booking::confirm($row['id']);bookingCheck(Booking::remaining($date)===0,'merchant confirm replay does not double-reserve');
bookingReject(static fn()=>Booking::confirm($second['id']),'SLOT_UNAVAILABLE');
Booking::cancel($row['id'],$owner);bookingCheck(Booking::remaining($date)===1,'cancellation releases capacity');
Booking::confirm($second['id']);bookingReject(static fn()=>Booking::checkout($second['id'],$foreign),'BOOKING_PREPAYMENT_DISABLED');
$closed=(new DateTimeImmutable($date))->modify('next Monday')->format('Y-m-d');bookingReject(static fn()=>Booking::create(array_replace($input,['preferred_date'=>$closed]),$owner,'booking-closed-fixture'),'SLOT_UNAVAILABLE');
bookingReject(static fn()=>Booking::create(array_replace($input,['preferred_date'=>'2026-02-30']),$owner,'booking-date-fixture'),'INVALID_REQUEST');
bookingReject(static fn()=>Booking::create(array_replace($input,['total_minor'=>1]),$owner,'booking-price-fixture'),'INVALID_REQUEST');
$pickupInput=array_replace($input,['mode'=>'prepaid_pickup','preferred_date'=>$days[2]['date'],'pickup_address'=>['address_1'=>'1 Synthetic Street','city'=>'Pittsburg','state'=>'CA','country'=>'US','postcode'=>'94565']]);
$pickup=Booking::create($pickupInput,$owner,'booking-pickup-fixture');$pickup=Booking::submit($pickup['id'],$owner,$contact,true);
$s['booking_pickup_postal_codes']=['94565'];update_option('krev_agent_settings',$s,false);bookingReject(static fn()=>Booking::confirm($pickup['id'],false),'ADDRESS_REVIEW_REQUIRED');Booking::confirm($pickup['id'],true);
bookingCheck(Booking::response(Booking::get($pickup['id'],$owner))['merchant_trip_fee_minor']===799,'one merchant pickup costs $7.99');
$s=array_replace($s,['booking_prepaid_enabled'=>true,'booking_policy_url'=>'https://kniferevive.com/terms-and-conditions/','booking_policy_version'=>'synthetic-only','listing_handoff_enabled'=>true,'listing_pricing_verified'=>true,'listing_gateway_ids'=>['stripe'],'listing_policy_url'=>'https://kniferevive.com/terms-and-conditions/','listing_policy_version'=>'synthetic-only','return_policy_url'=>'https://kniferevive.com/return-policy/','listing_services'=>[['product_id'=>$p->get_id(),'terms_url'=>'https://kniferevive.com/terms-and-conditions/','fulfillment_note'=>'Synthetic booking test only.','policy_version'=>'synthetic-only','native_fulfillment_verified'=>true]]]);
update_option('krev_agent_settings',Settings::validate($s),false);
class BookingTestGateway extends WC_Payment_Gateway {public function __construct(){$this->id='stripe';$this->enabled='yes';$this->title='Synthetic gateway';$this->settings=['testmode'=>'yes'];}public function is_available(){return true;}}
add_filter('woocommerce_payment_gateways',static fn()=>[BookingTestGateway::class],1000);WC()->payment_gateways()->init();
$intent=Booking::checkout($pickup['id'],$owner);bookingCheck(Booking::checkout($pickup['id'],$owner)['id']===$intent['id'],'payment preparation reuses original intent');
bookingReject(static fn()=>ListingCheckout::create(['items'=>$pickupInput['items'],'booking_id'=>$pickup['id']],$foreign,'booking-foreign-payment'),'NOT_FOUND');
$context=['billing'=>$pickupInput['pickup_address'],'email'=>'customer@example.invalid','payment_method'=>'stripe'];
$quote=ListingCheckout::quote($intent['id'],$context,$owner,'booking-native-quote-fixture');
bookingReject(static fn()=>ListingCheckout::quote($intent['id'],array_replace($context,['shipping'=>array_replace($pickupInput['pickup_address'],['postcode'=>'94103'])]),$owner,'booking-address-switch-fixture'),'ADDRESS_REVIEW_REQUIRED');
bookingCheck($quote['data']['quote']['total_minor']===1499,'native quote charges $7 service plus one $7.99 pickup');
bookingCheck($quote['data']['quote']['fees'][0]['total_minor']===799,'native fee ledger contains the merchant trip');
bookingCheck(\KnifeRevive\AgentCommerce\BookingAuthorization::payment(Booking::get($pickup['id'],$owner))==='needs_user','contact grant and calculated quote do not authorize purchase review');
$q=$quote['data']['quote'];$url=ListingCheckout::handoff($intent['id'],$owner,$q['quote_hash']);bookingCheck($url===wc_get_checkout_url() && Domain::cents(wc_format_decimal(WC()->cart->get_total('edit'),2))===1499,'native buyer cart matches quote and retains transport fee');
bookingCheck(\KnifeRevive\AgentCommerce\BookingAuthorization::payment(Booking::get($pickup['id'],$owner))==='authorized_for_quote','accepted current native quote records purchase review independently of settlement');
$approvedIntent=Store::get($intent['id'],'listing');$stale=$approvedIntent['data'];$stale['quote_expires']=time()-1;Store::update($intent['id'],$stale);
bookingCheck(\KnifeRevive\AgentCommerce\BookingAuthorization::payment(Booking::get($pickup['id'],$owner))==='expired','expired quote cannot retain purchase authority');Store::update($intent['id'],$approvedIntent['data']);
bookingCheck(!wc_get_orders(['meta_key'=>'_krev_listing_intent','meta_value'=>$intent['id']]),'cart handoff creates no paid order');
WC()->cart->empty_cart();bookingCheck(WC()->session->get('krev_booking_id')===null,'cleared cart detaches booking fee context');
Booking::cancel($pickup['id'],$owner);bookingReject(static fn()=>Booking::checkout($pickup['id'],$owner),'BOOKING_PREPAYMENT_DISABLED');
bookingCheck(!Booking::response(Booking::get($pickup['id'],$owner))['direct_wallet_enabled'],'unverified autonomous wallet order creation is not advertised');
bookingReject(static fn()=>Booking::walletInvoice($second['id'],$foreign),'WALLET_PAYMENT_DISABLED');
// Only synthetic native order/invoice rows are seeded. No invoice is issued.
foreach(['Settings','Repository','Client','Coordinator'] as $class)require_once ABSPATH.'wp-content/plugins/kniferevive-lightning-payments/includes/'.$class.'.php';
\KnifeRevive\Lightning\Repository::install();\KnifeRevive\Lightning\Settings::set('accept','yes');\KnifeRevive\Lightning\Settings::set('merchants',(string)$vendor);
$s['booking_wallet_enabled']=true;$s['booking_wallet_verified']=true;$s['listing_gateway_ids']=['stripe','krev_lightning'];update_option('krev_agent_settings',Settings::validate($s),false);
bookingCheck(Booking::walletEnabled(),'wallet availability requires explicit verified native Lightning enablement');
bookingReject(static fn()=>Booking::walletInvoice($second['id'],$owner),'NOT_FOUND');
bookingReject(static fn()=>Booking::walletInvoice($second['id'],$foreign),'NATIVE_INVOICE_REQUIRED');
$walletIntent=Booking::checkout($second['id'],$foreign);$wd=$walletIntent['data'];$wd['context']['payment_method']='krev_lightning';$wd['quote']=['total_minor'=>700];
$order=wc_create_order(['status'=>'pending']);$order->add_product($p,1);$order->set_payment_method('krev_lightning');$order->update_meta_data('_krev_listing_intent',$walletIntent['id']);$order->update_meta_data('_krev_service_booking',$second['id']);$order->calculate_totals();$order->save();$wd['order_id']=$order->get_id();Store::update($walletIntent['id'],$wd);
(new \Automattic\WooCommerce\Checkout\Helpers\ReserveStock())->reserve_stock_for_order($order,15);
$native=\KnifeRevive\Lightning\Repository::create($order);$payload=json_decode($native->payload,true);$payload=array_replace($payload,['attempt_uuid'=>$native->uuid,'state'=>'awaiting-payment','bolt11'=>'lnbc1syntheticnotspendable','payment_hash'=>bin2hex(random_bytes(32)),'wallet_id'=>'synthetic-wallet','checking_id'=>'synthetic-check','network'=>'bc','amount_sat'=>100,'amount_msat'=>100000,'expires_at'=>time()+600]);\KnifeRevive\Lightning\Repository::save($native,'awaiting-payment',$payload);
$invoice=Booking::walletInvoice($second['id'],$foreign);bookingCheck($invoice['payable'] && $invoice['amount_sat']===100 && !isset($invoice['wallet_id'],$invoice['checking_id']) && !$order->is_paid(),'wallet invoice exposes scoped existing invoice without merchant credentials or fake payment');
bookingCheck(Booking::walletInvoice($second['id'],$foreign)['payment_hash']===$invoice['payment_hash'],'invoice read reuses the original payment hash without new order or invoice');
$order->set_total('8');$order->save();bookingReject(static fn()=>Booking::walletInvoice($second['id'],$foreign),'PAYMENT_UNRESOLVED');$order->set_total('7');$order->save();
$payload['expires_at']=time()-1;\KnifeRevive\Lightning\Repository::save($native,'awaiting-payment',$payload);bookingReject(static fn()=>Booking::walletInvoice($second['id'],$foreign),'PAYMENT_UNRESOLVED');
$s['booking_wallet_verified']=false;update_option('krev_agent_settings',Settings::validate($s),false);bookingReject(static fn()=>Booking::walletInvoice($second['id'],$foreign),'WALLET_PAYMENT_DISABLED');
$access=Booking::accessToken(Booking::get($second['id'],$foreign));
bookingCheck(Booking::accessOwner($second['id'],$access)===$foreign,'booking token grants only its original booking owner');
bookingReject(static fn()=>Booking::accessOwner($pickup['id'],$access),'AUTHORIZATION_REQUIRED');
bookingReject(static fn()=>Booking::accessOwner($second['id'],$access.'x'),'AUTHORIZATION_REQUIRED');
$wpdb->update(Store::table('records'),['expires'=>time()-1],['id'=>$owner]);$request=new WP_REST_Request('GET');$request->set_header('X-Krev-Agent-Session',Domain::token($owner));bookingReject(static fn()=>Api::owner($request),'AUTHORIZATION_REQUIRED');
$request->set_header('X-Krev-Booking',Booking::accessToken(Booking::get($pickup['id'],$owner)));bookingCheck(Api::bookingOwner($pickup['id'],$request)===$owner,'booking-scoped access survives expired general shopping session');
$late=Booking::create(array_replace($input,['mode'=>'prepaid_dropoff','preferred_date'=>$days[6]['date']]),$owner,'booking-delayed-payment-fixture');Booking::submit($late['id'],$owner,$contact,true);Booking::confirm($late['id']);
$lateIntent=Booking::checkout($late['id'],$owner);bookingCheck($lateIntent['owner']!==$owner && (int)Store::get($owner,'session')['expires']<time(),'delayed checkout creates fresh scope without reviving expired token');
bookingCheck(Booking::checkout($late['id'],$owner)['id']===$lateIntent['id'],'delayed checkout retains original intent and payment session');
bookingCheck(Booking::response(Booking::get($late['id'],$owner))['payment_state']==='not_started','delayed booking status reads its separate native checkout owner');
$expired=$row;$expired['data']['preferred_window']['date']='2000-01-01';bookingCheck(isset(Booking::response($expired)['booking_state']),'historical status does not revalidate a past service day');
$samples=['Booking'=>Booking::response(Booking::get($second['id'],$foreign)),'BookingCoverage'=>BookingCoverage::check('94565'),'WalletInvoice'=>$invoice];file_put_contents(dirname(__DIR__).'/.runtime/booking-contract-samples.json',wp_json_encode($samples));
// Synthetic native payment facts: redirects/order existence/manual status alone are insufficient.
dokan()->order->maybe_split_orders($order->get_id());dokan_sync_insert_order($order->get_id());
$order->set_status('processing');$order->set_date_paid(time());$order->set_transaction_id('synthetic-only-no-processor-send');$order->save();
$beforeEvidence=Booking::response(Booking::get($second['id'],$foreign));
bookingCheck(!in_array('payment.verified',array_column($beforeEvidence['events'],'type'),true),'manual paid-looking native order does not emit verified payment event');
ListingCheckout::paymentObserved($order->get_id());$verified=Booking::response(Booking::get($second['id'],$foreign));
bookingCheck($verified['payment_state']==='paid' && count(array_filter($verified['events'],static fn($e)=>$e['type']==='payment.verified'))===1,'scoped polling reconciles one authoritative native payment event');
Booking::response(Booking::get($second['id'],$foreign));bookingCheck(count(array_filter(\KnifeRevive\AgentCommerce\BookingEvents::recent($second['id']),static fn($e)=>$e['type']==='payment.verified'))===1,'payment polling replay does not duplicate settlement fact');
$raceDate=$days[4]['date'];$s['booking_daily_capacity']=1;update_option('krev_agent_settings',$s,false);$race=[];
foreach([$owner,$foreign] as $raceOwner){$r=Booking::create(array_replace($input,['preferred_date'=>$raceDate]),$raceOwner,'booking-race-'.Domain::id());$race[]=Booking::submit($r['id'],$raceOwner,$contact,true)['id'];}
$start=microtime(true)+4;$processes=[];
foreach($race as $rid){$cmd=[PHP_BINARY,'-n','-d','extension_dir='.ini_get('extension_dir'),'-d','extension=mysqli','-d','extension=mbstring','-d','extension=openssl','-d','extension=curl','-d','memory_limit=512M',__DIR__.'/booking-race-worker.php',$argv[1],$rid,(string)$start];$pipes=[];$proc=proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($proc))throw new RuntimeException('Cannot start race worker');fclose($pipes[0]);$processes[]=[$proc,$pipes];}
$outcomes=[];foreach($processes as [$proc,$pipes]){$outcomes[]=trim(stream_get_contents($pipes[1]));$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($proc)!==0)throw new RuntimeException('Race worker failed: '.$error);}
sort($outcomes);bookingCheck($outcomes===['SLOT_UNAVAILABLE','reserved'] && Booking::remaining($raceDate)===0,'two processes racing one job reserve exactly one service day');
echo $passed." booking assertions passed. No real processor request was sent.\n";
