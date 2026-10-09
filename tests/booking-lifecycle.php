<?php
/** Native commerce lifecycle, synthetic gateway only; external HTTP and email blocked. */
ob_start();set_exception_handler(static function(Throwable $e){fwrite(STDERR,'FAIL: '.$e->getMessage()."\n");exit(1);});
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Booking,BookingEvents,BookingLifecycle,BookingSeller,Domain,Fault,ListingCheckout,Settings,Store};
if(DB_NAME!=='krev_agent_sandbox' || DB_HOST!=='127.0.0.1:11019')throw new RuntimeException('Sandbox fence failed');
$wpdb->query('DELETE FROM '.Store::table('holds')." WHERE slot_id LIKE 'booking-%'");
$wpdb->query('DELETE FROM '.Store::table('slots')." WHERE id LIKE 'booking-%'");
$checks=0;function lifecycleCheck(bool $ok,string $label):void{global $checks;if(!$ok)throw new RuntimeException($label);++$checks;echo "PASS: $label\n";}
function lifecycleReject(callable $fn,string $code):void{try{$fn();}catch(Fault $e){lifecycleCheck($e->codeName===$code,'rejects '.$code);return;}throw new RuntimeException('Missing rejection '.$code);}
$admin=get_user_by('login','sandbox-admin')->ID;wp_set_current_user($admin);
$vendor=wp_insert_user(['user_login'=>'lifecycle-'.Domain::id(),'user_pass'=>Domain::id(),'user_email'=>Domain::id().'@example.invalid','role'=>'seller']);update_user_meta($vendor,'dokan_enable_selling','yes');
$other=wp_insert_user(['user_login'=>'lifecycle-other-'.Domain::id(),'user_pass'=>Domain::id(),'user_email'=>Domain::id().'@example.invalid','role'=>'seller']);update_user_meta($other,'dokan_enable_selling','yes');
$term=get_term_by('slug','knife-sharpening','product_cat');$p=new WC_Product_Simple();$p->set_name('Synthetic Small Knife Sharpening');$p->set_status('publish');$p->set_regular_price('5');$p->set_virtual(false);$p->set_category_ids([$term->term_id]);$p->save();wp_update_post(['ID'=>$p->get_id(),'post_author'=>$vendor]);
class LifecycleGateway extends WC_Payment_Gateway{public static int $calls=0;public static bool $uncertain=false;public function __construct(){$this->id='stripe';$this->enabled='yes';$this->supports=['products','refunds'];$this->settings=['testmode'=>'no'];}public function is_available(){return true;}public function process_refund($order_id,$amount=null,$reason=''){++self::$calls;return self::$uncertain?new WP_Error('synthetic_uncertain','Synthetic uncertainty'):true;}}
add_filter('woocommerce_payment_gateways',static fn()=>[LifecycleGateway::class],1000);WC()->payment_gateways()->init();update_option('woocommerce_calc_taxes','no');update_option('pisol_cefw_payment_gateway_charges',[]);
$zone=new WC_Shipping_Zone();$zone->set_zone_name('Synthetic service handoff');$zone->add_location('US:CA','state');$zone->save();$method=$zone->add_shipping_method('local_pickup');update_option('woocommerce_local_pickup_'.$method.'_settings',['enabled'=>'yes','cost'=>'0']);delete_transient('wc_shipping_method_count');
$s=Settings::validate(['booking_enabled'=>true,'booking_prepaid_enabled'=>true,'booking_launch_approved'=>true,'booking_location'=>'Synthetic location',
    'booking_services'=>[['product_id'=>$p->get_id(),'definition'=>'Synthetic small knife']], 'booking_daily_capacity'=>200,
    'booking_weekly_hours'=>[['weekday'=>5,'open'=>'09:00','close'=>'19:00'],['weekday'=>6,'open'=>'09:00','close'=>'19:00'],['weekday'=>7,'open'=>'10:00','close'=>'16:00']],
    'booking_policy_url'=>'https://kniferevive.com/sharpening-cancellations-and-refunds/','booking_policy_version'=>'synthetic-only','listing_gateway_ids'=>['stripe']]);update_option('krev_agent_settings',$s,false);
lifecycleCheck(Booking::prepaymentEnabled() && !ListingCheckout::enabled(),'owner launch permits only service checkout without inventing generic live verification');
lifecycleCheck(!Settings::get()['listing_live_verified'] && !Booking::walletEnabled(),'unverified live evidence and wallet remain false');
$owner=Domain::id();Store::put($owner,'session','synthetic',time()+7200,['token_hash'=>hash('sha256',Domain::token($owner))]);$days=Booking::availability()['days'];$date=end($days)['date'];
$input=['items'=>[['product_id'=>$p->get_id(),'quantity'=>1]],'mode'=>'prepaid_pickup','preferred_date'=>$date,'postal_code'=>'94565'];
$address=['address_1'=>'1 Synthetic Street','address_2'=>'','city'=>'Pittsburg','state'=>'CA','country'=>'US','postcode'=>'94565'];
$contact=['customer'=>['name'=>'Synthetic buyer','email'=>'synthetic@example.invalid'],'pickup_address'=>$address];
function lifecycleBooking():array{global $input,$owner,$contact;$r=Booking::create($input,$owner,Domain::id());$r=Booking::submit($r['id'],$owner,$contact,true);Booking::confirm($r['id'],true);return Store::get($r['id'],'booking');}
$r=lifecycleBooking();$intent=Booking::checkout($r['id'],$owner);$quoted=ListingCheckout::quote($intent['id'],['billing'=>$address,'email'=>'synthetic@example.invalid','payment_method'=>'stripe'],$owner,Domain::id());$quote=$quoted['data']['quote'];
lifecycleCheck(!$quote['estimate_only'] && $quote['total_minor']===1299 && $quote['shipping_minor']===0,'physical small knife plus pickup produces final $12.99 native service total');
lifecycleCheck($quote['policy_url']===$s['booking_policy_url'] && $quote['policy_version']==='synthetic-only','quote binds owner-approved sharpening terms');
lifecycleCheck($quote['shipping_rates'][0]['selected']==='krev_booking_local_pickup','service has native local pickup handoff and separate merchant-trip fee');
function lifecycleOrder(array $r):WC_Order{
    global $p,$owner,$vendor;$intent=Booking::checkout($r['id'],$owner);$d=$intent['data'];$d['context']['payment_method']='stripe';
    $order=wc_create_order(['status'=>'pending']);$order->add_product($p,1);
    $fee=new WC_Order_Item_Fee();$fee->set_name('KnifeRevive merchant transport');$fee->set_amount('7.99');$fee->set_total('7.99');$order->add_item($fee);
    $ship=new WC_Order_Item_Shipping();$ship->set_method_id('local_pickup');$ship->set_method_title('Sharpening service handoff');$ship->set_total(0);$order->add_item($ship);
    $order->set_payment_method('stripe');$order->set_billing_email('synthetic@example.invalid');$order->update_meta_data('_krev_listing_intent',$intent['id']);$order->update_meta_data('_krev_service_booking',$r['id']);$order->calculate_totals();
    $d['quote']=$d['quote']??['total_minor'=>1299,'quote_hash'=>Domain::id()];$order->update_meta_data('_krev_listing_quote_hash',$d['quote']['quote_hash']);
    $order->save();$d['order_id']=$order->get_id();Store::update($intent['id'],$d);dokan()->order->maybe_split_orders($order->get_id());dokan_sync_insert_order($order->get_id());return wc_get_order($order->get_id());
}
$order=lifecycleOrder($r);lifecycleCheck(BookingLifecycle::order(Store::get($r['id']))?->get_id()===$order->get_id(),'native Dokan seller visibility validates service order binding');
$order->payment_complete('synthetic-only-not-a-real-charge');$r=Store::get($r['id']);$status=Booking::response($r);lifecycleCheck($status['payment_state']==='paid','native payment_complete reaches scoped bot status');
$order->set_total('13.99');$order->save();lifecycleReject(static fn()=>BookingLifecycle::refundRemaining($r['id'],true),'REFUND_REVIEW_REQUIRED');$order->set_total('12.99');$order->save();
wp_set_current_user($vendor);$cards=BookingSeller::appointments([],'local-pickup');lifecycleCheck(in_array($order->get_id(),array_column($cards,'order_id'),true),'paid pickup service appears under owning seller Local Pickup');
wp_set_current_user($other);lifecycleReject(static fn()=>BookingLifecycle::refundRemaining($r['id'],true),'FORBIDDEN');wp_set_current_user($vendor);lifecycleReject(static fn()=>BookingLifecycle::refundRemaining($r['id'],false),'FORBIDDEN');
Booking::cancel($r['id'],$owner);$cancelled=Booking::response(Store::get($r['id']));lifecycleCheck(wc_get_order($order->get_id())->has_status('cancelled') && $cancelled['payment_state']==='paid_cancelled_review_required' && $cancelled['refund_state']==='not_issued','paid cancellation closes native order while preserving original charge evidence');
$refund=BookingLifecycle::refundRemaining($r['id'],true);lifecycleCheck($refund['refund_state']==='full_gateway_accepted' && $refund['refund_gateway_accepted_minor']===1299 && !$refund['refund_arrival_verified'],'native original gateway accepts full refund without claiming bank arrival');
$wpdb->update(Store::table('records'),['expires'=>time()-1],['id'=>$owner]);lifecycleCheck(Booking::response(Store::get($r['id']))['refund_state']==='full_gateway_accepted','booking receipt retains refund facts after general shopper session expires');$wpdb->update(Store::table('records'),['expires'=>time()+7200],['id'=>$owner]);
$native=wc_get_order($order->get_id());$nr=$native->get_refunds()[0];lifecycleCheck(count($nr->get_items('line_item'))===1 && count($nr->get_items('fee'))===1,'refund retains product and trip line allocation for native seller accounting');
lifecycleCheck(!KREV_Sharpening_Orders::is_sharpening_order($nr) && count(array_filter(KREV_Sharpening_Orders::orders_for_current_user()->orders,static fn($o)=>!$o instanceof WC_Order))===0,'seller sharpening list excludes refund objects and renders only native orders');
$nativeBalanceCount=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->dokan_vendor_balance WHERE trn_id=%d AND vendor_id=%d AND trn_type='dokan_refund'",$order->get_id(),$vendor));lifecycleCheck($nativeBalanceCount===1,'native Dokan seller balance records one original service refund');
$calls=LifecycleGateway::$calls;BookingLifecycle::refundRemaining($r['id'],true);lifecycleCheck(LifecycleGateway::$calls===$calls,'replayed full refund cannot call gateway twice');
lifecycleCheck((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $wpdb->dokan_vendor_balance WHERE trn_id=%d AND vendor_id=%d AND trn_type='dokan_refund'",$order->get_id(),$vendor))===$nativeBalanceCount,'refund replay cannot adjust seller balance twice');
BookingLifecycle::observeOrder($order->get_id());BookingLifecycle::observeOrder($order->get_id());$events=BookingEvents::recent($r['id']);lifecycleCheck(count(array_filter($events,static fn($e)=>$e['type']==='refund.gateway_accepted'))===1,'duplicate native refund hooks produce one refund occurrence');
lifecycleCheck(!str_contains(json_encode($events),'synthetic@example.invalid') && !str_contains(json_encode($events),'Synthetic Street'),'bot event envelope contains no contact/address data');
wp_set_current_user($admin);$partial=lifecycleBooking();$po=lifecycleOrder($partial);$po->payment_complete('synthetic-only-partial');
$manual=wc_create_refund(['order_id'=>$po->get_id(),'amount'=>'1','refund_payment'=>false]);lifecycleCheck(Booking::response(Store::get($partial['id']))['refund_state']==='manual_review_required','manual refund record is not money returned');
$second=wc_create_refund(['order_id'=>$po->get_id(),'amount'=>'1','refund_payment'=>true]);$pe=BookingEvents::recent($partial['id']);lifecycleCheck(count(array_filter($pe,static fn($e)=>str_starts_with($e['type'],'refund.')))>1,'separate partial refund IDs remain separate occurrences');
lifecycleReject(static fn()=>BookingLifecycle::refundRemaining($partial['id'],true),'REFUND_REVIEW_REQUIRED');
$uncertain=lifecycleBooking();$uo=lifecycleOrder($uncertain);$uo->payment_complete('synthetic-only-uncertain');LifecycleGateway::$uncertain=true;
lifecycleReject(static fn()=>BookingLifecycle::refundRemaining($uncertain['id'],true),'REFUND_UNRESOLVED');$calls=LifecycleGateway::$calls;
lifecycleReject(static fn()=>BookingLifecycle::refundRemaining($uncertain['id'],true),'REFUND_UNRESOLVED');lifecycleCheck(LifecycleGateway::$calls===$calls,'uncertain refund attempt blocks all automatic replacement calls');LifecycleGateway::$uncertain=false;
$forged=lifecycleBooking();$fo=lifecycleOrder($forged);$fo->update_meta_data('_krev_service_booking',$r['id']);$fo->save();lifecycleCheck(!BookingLifecycle::order(Store::get($forged['id'])),'wrong service/order binding cannot expose refund facts');
// Pay-now requests keep merchant confirmation separate and cannot oversell a day.
wp_set_current_user($admin);$s=Settings::get();$s['booking_pay_before_confirmation']=true;$s['booking_daily_capacity']=1;update_option('krev_agent_settings',$s,false);
$wpdb->query('DELETE FROM '.Store::table('holds')." WHERE slot_id LIKE 'booking-%'");
Settings::savePickupCoverage(true,'94565, 94565');$limited=Settings::get();
lifecycleCheck($limited['booking_pickup_postal_codes']===['94565'] && $limited['booking_daily_capacity']===1 && $limited['booking_pay_before_confirmation'],'pickup ZIP editor preserves payment and capacity configuration');
lifecycleCheck(\KnifeRevive\AgentCommerce\BookingCoverage::check('94565')['pickup_eligible'] && !\KnifeRevive\AgentCommerce\BookingCoverage::check('95054')['pickup_eligible'] && \KnifeRevive\AgentCommerce\BookingCoverage::check('95054')['prepayment_eligible'],'custom ZIP list limits pickup while retaining customer drop-off prepayment');
lifecycleReject(static fn()=>Settings::savePickupCoverage(true,'94110'),'INVALID_SETTINGS');
wp_set_current_user($vendor);lifecycleReject(static fn()=>Settings::savePickupCoverage(false,''),'FORBIDDEN');wp_set_current_user($admin);
Settings::savePickupCoverage(true,'');lifecycleCheck(!\KnifeRevive\AgentCommerce\BookingCoverage::check('94565')['pickup_eligible'],'empty enabled ZIP list disables merchant pickup');Settings::savePickupCoverage(false,'');
$pending=[];for($j=0;$j<2;$j++){$a=Booking::create($input,$owner,Domain::id());$pending[]=Booking::submit($a['id'],$owner,$contact,true);}
$a=$pending[0];$ai=Booking::checkout($a['id'],$owner);
lifecycleCheck(Store::get($a['id'])['data']['booking_state']==='requested' && Booking::remaining($date)===0,'pay-now preparation holds one job without confirming the appointment');
lifecycleReject(static fn()=>Booking::checkout($pending[1]['id'],$owner),'SLOT_UNAVAILABLE');
lifecycleCheck(Booking::checkout($a['id'],$owner)['id']===$ai['id'] && (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.Store::table('holds').' WHERE attempt_id=%s',$a['id']))===1,'repeated pay-now preparation retains one checkout and capacity hold');
$aq=ListingCheckout::quote($ai['id'],['billing'=>$address,'email'=>'synthetic@example.invalid','payment_method'=>'stripe'],$owner,Domain::id());
lifecycleCheck(!$aq['data']['quote']['estimate_only'] && $aq['data']['quote']['total_minor']===1299,'unconfirmed pickup request receives final native total without inventing address verification');
if(!WC()->session || !WC()->cart || !WC()->customer)wc_load_cart();WC()->cart->empty_cart();
ListingCheckout::handoff($ai['id'],$owner,$aq['data']['quote']['quote_hash']);$a=Store::get($a['id']);
lifecycleCheck(ListingCheckout::bookingCart($a)['id']===$ai['id'],'same-screen native checkout accepts only the consent-bound original browser cart');
lifecycleCheck(array_keys(BookingLifecycle::bookingGateways(['stripe'=>new LifecycleGateway(),'cod'=>new stdClass()]))===['stripe'],'native AJAX gateway refresh retains the original quoted payment method');
$bad=$a;$bad['id']=Domain::id();lifecycleReject(static fn()=>ListingCheckout::bookingCart($bad),'CART_CONFLICT');
$ao=lifecycleOrder($a);$ao->payment_complete('synthetic-preconfirm-only');$a=Store::get($a['id']);
lifecycleCheck($a['data']['booking_state']==='requested' && Booking::response($a)['payment_state']==='paid' && Booking::activePrepaymentHold($a['id']),'native payment retains capacity while appointment remains requested');
Booking::confirm($a['id'],true);lifecycleCheck(Booking::remaining($date)===0 && (int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.Store::table('holds').' WHERE attempt_id=%s',$a['id']))===1,'merchant confirmation reuses paid capacity without a second allocation');
Booking::cancel($a['id'],$owner);WC()->cart->empty_cart();
$b=$pending[1];$bi=Booking::checkout($b['id'],$owner);$bo=lifecycleOrder($b);
$wpdb->update(Store::table('holds'),['expires'=>time()-1],['attempt_id'=>$b['id']]);$bo->payment_complete('synthetic-late-only');
lifecycleCheck(!Booking::activePrepaymentHold($b['id']) && count(array_filter(BookingEvents::recent($b['id']),static fn($e)=>$e['type']==='payment.capacity_review_required'))===1,'late payment records money and capacity review without reclaiming an expired job');
lifecycleReject(static fn()=>Booking::checkout($b['id'],$owner),'PAYMENT_UNRESOLVED');
Booking::cancel($b['id'],$owner);
file_put_contents(dirname(__DIR__).'/.runtime/lifecycle-contract-samples.json',wp_json_encode(['RefundSummary'=>$refund,'BookingEvent'=>array_values(array_filter(BookingEvents::recent($b['id']),static fn($e)=>$e['type']==='payment.capacity_review_required'))[0]]));
$zone->delete();delete_transient('wc_shipping_method_count');
echo "$checks lifecycle assertions passed. No real payment, refund, or email was sent.\n";
