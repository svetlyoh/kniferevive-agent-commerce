<?php
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Domain,Settings,Store,Booking,BookingSeller,BookingOrderBridge,BookingOutbox,BookingAuthorization,Api,Fault};
if(DB_NAME!=='krev_agent_sandbox' || $wpdb->prefix!=='krev_sandbox_')throw new RuntimeException('Sandbox fence failed.');
$wpdb->query('DELETE FROM '.Store::table('holds')." WHERE slot_id LIKE 'booking-%'");
$wpdb->query('DELETE FROM '.Store::table('slots')." WHERE id LIKE 'booking-%'");
$checks=0;
function bridgeCheck($condition,$message){global $checks;if(!$condition)throw new RuntimeException('FAIL: '.$message);$checks++;echo 'PASS: '.$message."\n";}
function bridgeReject(callable $work,string $code){try{$work();}catch(Fault $e){bridgeCheck($e->codeName===$code,'rejects '.$code);return;}throw new RuntimeException('Expected '.$code);}
$admin=get_users(['role'=>'administrator','number'=>1])[0]->ID;wp_set_current_user($admin);
$vendor=wp_insert_user(['user_login'=>'bridge-'.Domain::id(),'user_pass'=>Domain::id(),'user_email'=>Domain::id().'@example.invalid','role'=>'seller']);update_user_meta($vendor,'dokan_enable_selling','yes');
$other=wp_insert_user(['user_login'=>'other-'.Domain::id(),'user_pass'=>Domain::id(),'user_email'=>Domain::id().'@example.invalid','role'=>'seller']);update_user_meta($other,'dokan_enable_selling','yes');
$term=get_term_by('slug','knife-sharpening','product_cat');if(!$term){wp_insert_term('Sharpening','product_cat',['slug'=>'knife-sharpening']);$term=get_term_by('slug','knife-sharpening','product_cat');}
$p=new WC_Product_Simple();$p->set_name('Large Knife Sharpening');$p->set_status('publish');$p->set_regular_price('7');$p->set_virtual(false);$p->set_manage_stock(true);$p->set_stock_quantity(20);$p->set_category_ids([$term->term_id]);$p->save();wp_update_post(['ID'=>$p->get_id(),'post_author'=>$vendor]);
$s=Settings::validate(['booking_enabled'=>true,'booking_location'=>'Synthetic merchant pickup address','booking_services'=>[['product_id'=>$p->get_id(),'definition'=>'8-inch chef knife.']],
    'booking_weekly_hours'=>[['weekday'=>5,'open'=>'09:00','close'=>'19:00'],['weekday'=>6,'open'=>'09:00','close'=>'19:00'],['weekday'=>7,'open'=>'10:00','close'=>'16:00']], 'booking_daily_capacity'=>10]);
update_option('krev_agent_settings',$s,false);update_option('woocommerce_cod_settings',['enabled'=>'yes','title'=>'Owner-approved synthetic pay-at-drop-off arrangement'],false);WC()->payment_gateways()->init();
update_option('woocommerce_calc_taxes','no');update_option('pisol_cefw_payment_gateway_charges',[]);
$owner=Domain::id();Store::put($owner,'session','synthetic',time()+7200,['token_hash'=>hash('sha256',Domain::token($owner))]);
$days=Booking::availability()['days'];$date=$days[array_key_last($days)]['date'];$beforeCapacity=Booking::remaining($date);$input=['items'=>[['product_id'=>$p->get_id(),'quantity'=>1]],'mode'=>'pay_later_dropoff','preferred_date'=>$date,'postal_code'=>'94565'];
$contact=['customer'=>['name'=>'Synthetic Customer','email'=>'customer@example.invalid','phone'=>'']];
$row=Booking::create($input,$owner,'bridge-no-order-'.Domain::id());
$draftResponse=Booking::response($row);bridgeCheck(str_contains($draftResponse['review_url'],'referral=') && !str_contains($draftResponse['review_url'],'booking_access='),'new bot handoff URL carries opaque referral rather than a private access token');
bridgeCheck(Booking::referral($row['data']['referral_id'])['id']===$row['id'],'unexpired referral opens only the contact-free service draft');
bridgeCheck(BookingAuthorization::address($row)==='needs_user','draft has no inferred contact grant');
bridgeReject(static fn()=>Booking::submit($row['id'],$owner,$contact),'ADDRESS_AUTHORIZATION_REQUIRED');
$row=Booking::submit($row['id'],$owner,$contact,true);
bridgeReject(static fn()=>Booking::referral($row['data']['referral_id']),'AUTHORIZATION_REQUIRED');
bridgeCheck(!BookingOrderBridge::linked($row),'default keeps real orders disabled');
bridgeCheck(BookingAuthorization::address($row)==='granted_for_order','first-party consent is booking/principal bound');
bridgeCheck(BookingAuthorization::payment($row)==='needs_user','contact consent is not payment authority');
$events=Booking::response($row)['events'];bridgeCheck(count($events)===1 && $events[0]['type']==='booking.request_received','durable request event does not pretend an order exists');
bridgeCheck(!str_contains(json_encode($events),'customer@example.invalid') && !str_contains(json_encode($events),'booking_access'),'event envelope contains no PII or access token');
$jobs=BookingOutbox::jobs($row['id']);bridgeCheck(count($jobs)===3,'customer seller and admin each get a durable recipient job');
bridgeReject(static fn()=>BookingOutbox::recover($row['id'],'seller',false),'FORBIDDEN');BookingOutbox::recover($row['id'],'seller',true);bridgeCheck(count(BookingOutbox::jobs($row['id']))===3,'historical recipient reconciliation reuses existing deterministic job');
$roles=[];foreach($jobs as $job)$roles[$job['data']['role']]=$job;
bridgeCheck($roles['seller']['data']['recipient']===get_userdata($vendor)->user_email,'seller recipient comes from product ownership');
remove_all_filters('pre_wp_mail');$sent=[];$fail=true;
add_filter('pre_wp_mail',static function($pre,$atts)use(&$sent,&$fail){$sent[]=$atts;if($fail){do_action('wp_mail_failed',new WP_Error('synthetic_failure','Synthetic mail failure'));return false;}do_action('wp_mail_succeeded',$atts);return true;},PHP_INT_MAX,2);
BookingOutbox::send($roles['seller']['id']);$job=Store::get($roles['seller']['id']);
bridgeCheck($job['data']['state']==='pending' && $job['data']['attempts']===1,'mail failure remains retryable instead of being marked notified');
BookingOutbox::send($job['id']);bridgeCheck(count($sent)===1,'backoff prevents immediate duplicate sends');
$d=$job['data'];$d['next_attempt']=time();Store::update($job['id'],$d);$fail=false;BookingOutbox::send($job['id']);
bridgeCheck(Store::get($job['id'])['data']['state']==='accepted_by_mailer','success is mailer acceptance not delivered');
bridgeCheck(!str_contains($sent[1]['message'],'booking_access='),'seller email contains no customer private token');
BookingOutbox::send($job['id']);bridgeCheck(count($sent)===2,'accepted recipient job is not automatically resent');
foreach(['customer','admin'] as $role)BookingOutbox::send($roles[$role]['id']);bridgeCheck(count($sent)===4,'distinct roles are sent once');
Booking::submit($row['id'],$owner,$contact,true);bridgeCheck(count(BookingOutbox::jobs($row['id']))===3,'human POST replay does not duplicate recipient jobs');
wp_set_current_user($vendor);bridgeCheck(BookingSeller::can($row) && !current_user_can('manage_woocommerce'),'seller inbox does not grant administrator capabilities');
bridgeCheck(in_array($row['id'],array_column(BookingSeller::rows(),'id'),true),'seller request inbox sees its booking without an order');
wp_set_current_user($other);bridgeCheck(!BookingSeller::can($row) && !in_array($row['id'],array_column(BookingSeller::rows(),'id'),true),'different seller cannot access the customer request');
bridgeReject(static fn()=>Booking::confirm($row['id']),'FORBIDDEN');
wp_set_current_user($admin);$s['booking_order_timing']='on_submit';$s['booking_order_verified']=true;$s['booking_offline_gateway_id']='cod';update_option('krev_agent_settings',Settings::validate($s),false);
$order=BookingOrderBridge::ensure($row['id']);$row=Booking::get($row['id'],$owner);
bridgeCheck($order->has_status('pending') && !$order->is_paid() && !$order->get_date_paid() && !$order->get_transaction_id(),'native order remains unpaid and unconfirmed');
bridgeCheck($order->get_total()==='7.00','native total is $7 for one knife');
$sellerShare=dokan()->commission->get_earning_by_order($order,'seller');$adminShare=dokan()->commission->get_earning_by_order($order,'admin');
bridgeCheck(is_numeric($sellerShare) && is_numeric($adminShare) && abs($sellerShare+$adminShare-7)<0.001,'native Dokan seller and admin commission shares reconcile to the order total');
bridgeCheck((int)$order->get_meta('_dokan_vendor_id')===$vendor,'native Dokan attributes the original seller');
$orders=dokan()->order->all(['seller_id'=>$vendor,'order_id'=>$order->get_id(),'return'=>'ids']);bridgeCheck(in_array($order->get_id(),array_map('intval',$orders),true),'native seller order query includes unpaid request');
$otherOrders=dokan()->order->all(['seller_id'=>$other,'order_id'=>$order->get_id(),'return'=>'ids']);bridgeCheck(!in_array($order->get_id(),array_map('intval',$otherOrders),true),'native seller orders exclude foreign order');
$shipping=array_values($order->get_items('shipping'));bridgeCheck(count($shipping)===1 && $shipping[0]->get_method_id()==='local_pickup' && (float)$shipping[0]->get_total()===0.0,'native local pickup line has no merchant trip fee');
bridgeCheck(!count($order->get_items('fee')),'unpaid drop-off adds no courier fee');
bridgeCheck(!$order->needs_payment(),'offline request has no premature pay-for-order action');
wc_reduce_stock_levels($order->get_id());bridgeCheck(wc_get_product($p->get_id())->get_stock_quantity()===20,'unconfirmed order cannot reduce product stock');
bridgeCheck(Booking::remaining($date)===$beforeCapacity,'native order creation does not reserve booking capacity');
bridgeCheck(BookingOrderBridge::ensure($row['id'])->get_id()===$order->get_id(),'order retry reuses original native order');
// The custom first-party Seller Orders UI is distinct from native Dokan Orders.
wp_set_current_user($vendor);$beforeSends=count($sent);$beforeJobs=count(BookingOutbox::jobs($row['id']));$beforeEvents=Booking::response($row)['events'];
$cards=BookingSeller::appointments([],'local-pickup');$mine=array_values(array_filter($cards,static fn($c)=>$c['order_id']===$order->get_id()));
bridgeCheck(count($mine)===1 && $mine[0]['date']===$date && $mine[0]['state']==='Requested — confirmation required','owning seller sees requested native booking under Local Pickup');
bridgeCheck($mine[0]['items']===['Large Knife Sharpening × 1'] && str_contains($mine[0]['total'],'7.00') && $mine[0]['payment']==='Unpaid — pay when you collect','appointment card preserves native service quantity and unpaid total');
bridgeCheck(!str_contains(json_encode($mine),'customer@example.invalid') && !str_contains(json_encode($mine),'booking_access=') && str_contains($mine[0]['review_url'],'booking='.$row['id']),'card links to authenticated booking review without customer PII or private token');
bridgeCheck(count(BookingOrderBridge::findOrders($row['id']))===1 && count($sent)===$beforeSends && count(BookingOutbox::jobs($row['id']))===$beforeJobs && Booking::response($row)['events']===$beforeEvents,'listing cards creates no orders, notifications, events or payments');
bridgeCheck(BookingSeller::appointments([],'needs-fulfillment')===[] && BookingSeller::appointments([],'returns')===[],'booking cards cannot enter merchandise fulfillment or returns');
wp_set_current_user($other);bridgeCheck(BookingSeller::appointments([],'local-pickup')===[],'foreign seller cannot see appointment cards');
wp_set_current_user(0);bridgeCheck(BookingSeller::appointments([],'local-pickup')===[],'logged-out visitor cannot see appointment cards');
wp_set_current_user($vendor);update_user_meta($vendor,'dokan_enable_selling','no');bridgeCheck(BookingSeller::appointments([],'local-pickup')===[],'disabled seller cannot see appointment cards');update_user_meta($vendor,'dokan_enable_selling','yes');
$order->update_meta_data('_dokan_vendor_id',$other);$order->save();bridgeCheck(BookingSeller::appointments([],'local-pickup')===[],'mismatched native seller attribution suppresses card');$order->update_meta_data('_dokan_vendor_id',$vendor);$order->save();
$shipping[0]->set_method_id('flat_rate');$shipping[0]->save();bridgeCheck(BookingSeller::appointments([],'local-pickup')===[],'non-local shipping cannot appear as local pickup');$shipping[0]->set_method_id('local_pickup');$shipping[0]->save();
wp_update_post(['ID'=>$p->get_id(),'post_author'=>$other]);bridgeCheck(BookingSeller::appointments([],'local-pickup')===[],'transferred service ownership cannot expose the prior seller booking');wp_update_post(['ID'=>$p->get_id(),'post_author'=>$vendor]);
$s['booking_daily_capacity']=null;update_option('krev_agent_settings',Settings::validate($s),false);bridgeReject(static fn()=>Booking::confirm($row['id']),'CAPACITY_UNCONFIGURED');
bridgeCheck(Store::get($row['id'])['data']['booking_state']==='requested' && BookingSeller::appointments([],'local-pickup')[0]['state']==='Requested — confirmation required','missing capacity cannot produce a false confirmed card');$s['booking_daily_capacity']=10;update_option('krev_agent_settings',Settings::validate($s),false);
if(getenv('KREV_SELLER_ORDERS_CANDIDATE')==='1'){
    $tab='local-pickup';$page=1;$_GET['tab']=$tab;ob_start();(new KREV_Orders_Endpoints())->seller_orders();$html=ob_get_clean();unset($_GET['tab']);
    bridgeCheck(substr_count($html,'Sharpening booking · Order #'.$order->get_order_number())===1 && !str_contains($html,'No seller orders match this filter.'),'actual production-baseline endpoint renders one card instead of an empty state');
    wp_set_current_user($admin);$_GET['tab']=$tab;ob_start();(new KREV_Orders_Endpoints())->seller_orders();$adminHtml=ob_get_clean();unset($_GET['tab']);
    bridgeCheck(substr_count($adminHtml,'Sharpening booking · Order #'.$order->get_order_number())===1 && !str_contains($adminHtml,'Knife Sharpening #'.$order->get_order_number()),'operator view replaces duplicate legacy sharpening row');
    $appointment_cards=[$mine[0]];$appointment_cards[0]['items']=['<script>alert("unsafe")</script>'];$result=(object)['orders'=>[],'max_num_pages'=>0];$sharpening_result=null;
    ob_start();include KREV_ORDERS_PATH.'templates/seller-orders.php';$escaped=ob_get_clean();bridgeCheck(!str_contains($escaped,'<script>') && str_contains($escaped,'&lt;script&gt;'),'appointment service titles are escaped in actual deployed template');
}
wp_set_current_user($admin);$capacitySnapshot=Settings::get();Settings::saveDailyCapacity(4);$capacitySaved=Settings::get();$capacitySnapshot['booking_daily_capacity']=4;
bridgeCheck($capacitySaved===$capacitySnapshot,'dedicated capacity control changes only the daily limit');
bridgeReject(static fn()=>Settings::saveDailyCapacity(0),'INVALID_REQUEST');bridgeReject(static fn()=>Settings::saveDailyCapacity(201),'INVALID_REQUEST');
wp_set_current_user($vendor);bridgeReject(static fn()=>Settings::saveDailyCapacity(4),'FORBIDDEN');wp_set_current_user($admin);Settings::saveDailyCapacity(10);
$events=Booking::response($row)['events'];bridgeCheck(count($events)===2 && count(array_filter($events,static fn($e)=>$e['type']==='woocommerce.order_created'))===1,'native order event emitted once after seller validation');
$d=$row['data'];unset($d['unpaid_order_id']);Store::update($row['id'],$d);bridgeCheck(BookingOrderBridge::ensure($row['id'])->get_id()===$order->get_id(),'crash between native save and booking link recovers same order');
$d=Store::get($row['id'])['data'];$d['address_consent']['expires_at']=time()-1;Store::update($row['id'],$d);bridgeReject(static fn()=>BookingOrderBridge::ensure($row['id']),'ADDRESS_AUTHORIZATION_REQUIRED');
Booking::shareConsent($row['id'],$owner,true);Booking::revokeConsent($row['id'],$owner);bridgeCheck(BookingAuthorization::address(Booking::get($row['id'],$owner))==='revoked','sharing grant can be revoked independently of payment');
bridgeReject(static fn()=>Booking::confirm($row['id']),'ADDRESS_AUTHORIZATION_REQUIRED');Booking::shareConsent($row['id'],$owner,true);
wp_set_current_user($vendor);Booking::confirm($row['id']);bridgeCheck(Booking::get($row['id'],$owner)['data']['booking_state']==='confirmed','owning seller may confirm without administrator power');
wp_set_current_user($admin);$paidPref=Booking::create(array_replace($input,['mode'=>'prepaid_dropoff','postal_code'=>'94565']),$owner,'bridge-paid-preference-'.Domain::id());Booking::submit($paidPref['id'],$owner,$contact,true);
bridgeReject(static fn()=>BookingOrderBridge::ensure($paidPref['id']),'ORDER_BRIDGE_INELIGIBLE');bridgeReject(static fn()=>Booking::checkout($paidPref['id'],$owner),'BOOKING_PREPAYMENT_DISABLED');
update_user_meta($vendor,'dokan_enable_selling','no');bridgeCheck(!BookingSeller::seller($row),'disabled seller loses request access');bridgeReject(static fn()=>BookingOrderBridge::ensure($row['id']),'SELLER_UNAVAILABLE');update_user_meta($vendor,'dokan_enable_selling','yes');
$p2=clone $p;$p2->set_id(0);$p2->set_name('Other seller service');$p2->save();wp_update_post(['ID'=>$p2->get_id(),'post_author'=>$other]);$s['booking_services'][]=['product_id'=>$p2->get_id(),'definition'=>'Synthetic other seller'];update_option('krev_agent_settings',Settings::validate($s),false);
bridgeReject(static fn()=>Booking::create(array_replace($input,['items'=>[['product_id'=>$p->get_id(),'quantity'=>1],['product_id'=>$p2->get_id(),'quantity'=>1]]]),$owner,'bridge-mixed-'.Domain::id()),'MULTIPLE_SELLERS');
$cap=Api::capabilities(null);bridgeCheck($cap['booking']['agent_event_polling_supported'] && !$cap['booking']['agent_event_push_supported'] && !$cap['booking']['host_address_grants_supported'],'capabilities advertise implemented polling only');
$r=new WP_REST_Request('POST');$r->set_header('Content-Type','application/json');$r->set_body(json_encode(array_replace($input,['customer'=>$contact['customer']])));bridgeReject(static fn()=>Api::bookingCreate($r),'INVALID_REQUEST');
Booking::cancel($row['id'],$owner);$cancel=Booking::response(Booking::get($row['id'],$owner));bridgeCheck($cancel['booking_state']==='cancelled' && $cancel['refund_state']==='not_issued' && wc_get_order($order->get_id())->has_status('cancelled'),'cancellation closes unpaid native service order without fabricating refund');
$confirmedJobs=array_values(array_filter(BookingOutbox::jobs($row['id']),static fn($j)=>$j['data']['stage']==='confirmed'));
BookingOutbox::send($confirmedJobs[0]['id']);bridgeCheck(Store::get($confirmedJobs[0]['id'])['data']['last_error']==='obsolete_notification_suppressed','cancelled booking suppresses stale confirmation email');
bridgeReject(static fn()=>BookingOrderBridge::ensure($row['id']),'ORDER_BRIDGE_INELIGIBLE');
// Compatibility failures retain a durable request and never pretend an order exists.
update_option('woocommerce_cod_settings',['enabled'=>'no'],false);WC()->payment_gateways()->init();
$gateway=WC()->payment_gateways()->payment_gateways()['cod'];$gateway->enabled='no';
$failedRequest=Booking::create($input,$owner,'bridge-gateway-disabled-'.Domain::id());$failedRequest=Booking::submit($failedRequest['id'],$owner,$contact,true);
bridgeCheck($failedRequest['data']['booking_state']==='requested' && $failedRequest['data']['order_bridge_state']==='needs_admin_review' && !BookingOrderBridge::linked($failedRequest),'disabled offline method retains visible request without a false order reference');
bridgeCheck($failedRequest['data']['order_bridge_error']==='OFFLINE_METHOD_UNAVAILABLE','integration failure records a sanitized actionable reason');
$gateway->enabled='yes';
// Owner-selected deferred timing creates no order until native seller confirmation.
$s['booking_order_timing']='on_confirm';update_option('krev_agent_settings',Settings::validate($s),false);
$deferred=Booking::create($input,$owner,'bridge-deferred-'.Domain::id());$deferred=Booking::submit($deferred['id'],$owner,$contact,true);
bridgeCheck(!BookingOrderBridge::linked($deferred),'on-confirm configuration does not create an order on submission');
bridgeReject(static fn()=>BookingOrderBridge::ensure($deferred['id']),'CONFIRMATION_REQUIRED');
Booking::confirm($deferred['id']);$deferred=Booking::get($deferred['id'],$owner);$deferredOrder=BookingOrderBridge::linked($deferred);
bridgeCheck($deferredOrder && $deferredOrder->has_status('pending') && $deferredOrder->get_meta('_krev_booking_confirmation')==='confirmed','seller confirmation creates one unpaid native order in approved deferred mode');
$deferredOrder->set_status('on-hold');$deferredOrder->save();bridgeReject(static fn()=>BookingOrderBridge::ensure($deferred['id']),'NATIVE_ORDER_UNRESOLVED');
bridgeCheck(count(BookingOrderBridge::findOrders($deferred['id']))===1,'stale native order status never creates a replacement order');
$deferredOrder->set_status('pending');$deferredOrder->save();
// Contact sharing cannot survive an altered quantity/date/identity.
$altered=$deferred;$altered['data']['input']['items'][0]['quantity']=2;
bridgeCheck(BookingAuthorization::address($altered)==='needs_user','changed service selection invalidates original contact sharing grant');
// Terminal transport failures are bounded; uncertain sends require explicit administrator review.
$failureJob=BookingOutbox::jobs($failedRequest['id'])[0];$fd=$failureJob['data'];$fd['attempts']=4;$fd['next_attempt']=time();Store::update($failureJob['id'],$fd);$fail=true;BookingOutbox::send($failureJob['id']);
bridgeCheck(Store::get($failureJob['id'])['data']['state']==='failed' && Store::get($failureJob['id'])['data']['attempts']===5,'mail transport stops automatic retries after five failed attempts');
bridgeReject(static fn()=>BookingOutbox::retry($failureJob['id'],false),'FORBIDDEN');BookingOutbox::retry($failureJob['id'],true);$fail=false;BookingOutbox::send($failureJob['id']);
bridgeCheck(Store::get($failureJob['id'])['data']['state']==='accepted_by_mailer','administrator-authorized retry reuses original notification job');
$uncertain=BookingOutbox::jobs($failedRequest['id'])[1];$ud=$uncertain['data'];$ud['state']='sending';$ud['started_at']=time();Store::update($uncertain['id'],$ud);$beforeSends=count($sent);BookingOutbox::send($uncertain['id']);
bridgeCheck(count($sent)===$beforeSends,'uncertain worker crash is not automatically resent');bridgeReject(static fn()=>BookingOutbox::retry($uncertain['id'],true),'BUSY');
$race=Booking::create($input,$owner,'bridge-concurrency-'.Domain::id());Booking::submit($race['id'],$owner,$contact,true);
$s['booking_order_timing']='on_submit';update_option('krev_agent_settings',Settings::validate($s),false);update_option('woocommerce_cod_settings',['enabled'=>'yes','title'=>'Synthetic pay-at-drop-off'],false);
$start=microtime(true)+4;$processes=[];
foreach([1,2] as $worker){$cmd=[PHP_BINARY,'-n','-d','extension_dir='.ini_get('extension_dir'),'-d','extension=mysqli','-d','extension=mbstring','-d','extension=openssl','-d','extension=curl','-d','memory_limit=512M',__DIR__.'/booking-order-race-worker.php',$argv[1],$race['id'],(string)$start];$pipes=[];$proc=proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($proc))throw new RuntimeException('Cannot start order race worker');fclose($pipes[0]);$processes[]=[$proc,$pipes];}
$outcomes=[];foreach($processes as [$proc,$pipes]){$outcomes[]=trim(stream_get_contents($pipes[1]));$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($proc)!==0)throw new RuntimeException('Order race worker failed: '.$error);}
bridgeCheck(ctype_digit($outcomes[0]) && $outcomes[0]===$outcomes[1] && count(BookingOrderBridge::findOrders($race['id']))===1,'two independent workers racing submission link exactly one native order ('.implode(', ',$outcomes).')');
echo "$checks booking bridge assertions passed. Email and processor evidence is synthetic only.\n";
