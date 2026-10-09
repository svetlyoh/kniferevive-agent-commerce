<?php
/** Only synthetic data in the fenced database. No real payment or buyer email. */
ob_start();
set_exception_handler(static function(Throwable $e){fwrite(STDERR,'FAIL: '.$e->getMessage()."\n");exit(1);});
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Api,Domain,Fault,ListingCheckout,Settings,Store};
if (DB_NAME!=='krev_agent_sandbox' || $wpdb->prefix!=='krev_sandbox_') throw new RuntimeException('Sandbox fence failed.');
$passed=0;
function listingCheck(bool $ok,string $label): void { global $passed; if (!$ok) throw new RuntimeException('FAIL: '.$label); ++$passed; echo "PASS: $label\n"; }
function listingReject(callable $work,string $code,string $label): void {
    try {$work();} catch(Fault $e){listingCheck($e->codeName===$code,$label.' ('.$e->codeName.')');return;}
    throw new RuntimeException('FAIL: '.$label.' did not reject');
}
listingCheck(Settings::defaults()['listing_handoff_enabled']===false,'native handoff defaults off');
foreach (['records','idem'] as $suffix) $wpdb->query('TRUNCATE TABLE '.Store::table($suffix));
foreach(wc_get_products(['limit'=>-1]) as $oldProduct)$oldProduct->delete(true);
wp_set_current_user(0);
$vendor=wp_insert_user(['user_login'=>'listing-vendor-'.Domain::id(),'user_pass'=>Domain::id(),'user_email'=>Domain::id().'@example.invalid','role'=>'seller','display_name'=>'Synthetic Seller']);
$vendor2=wp_insert_user(['user_login'=>'listing-other-'.Domain::id(),'user_pass'=>Domain::id(),'user_email'=>Domain::id().'@example.invalid','role'=>'seller','display_name'=>'Other Synthetic Seller']);
update_user_meta($vendor,'dokan_enable_selling','yes'); update_user_meta($vendor2,'dokan_enable_selling','yes');
function listingProduct(string $category,int $vendor,string $name='Synthetic listing'): WC_Product_Simple {
    $term=get_term_by('slug',$category,'product_cat'); if (!$term){wp_insert_term($category,'product_cat',['slug'=>$category]);$term=get_term_by('slug',$category,'product_cat');}
    $p=new WC_Product_Simple();$p->set_name($name);$p->set_status('publish');$p->set_regular_price('20');$p->set_sale_price('12');$p->set_price('12');$p->set_category_ids([$term->term_id]);$p->set_manage_stock(true);$p->set_stock_quantity(1);$p->set_virtual(true);$p->save();wp_update_post(['ID'=>$p->get_id(),'post_author'=>$vendor]);return $p;
}
$knife=listingProduct('chefs-knife',$vendor,'One-stock used knife');
$tech=listingProduct('technology',$vendor);$art=listingProduct('art',$vendor);$spice=listingProduct('spices',$vendor);
$small=listingProduct('knife-sharpening',$vendor,'Small sharpening');$large=listingProduct('knife-sharpening',$vendor,'Large sharpening');
$other=listingProduct('coins',$vendor2);
$s=Settings::validate(['listing_handoff_enabled'=>true,'listing_pricing_verified'=>true,'listing_gateway_ids'=>['stripe'],'listing_policy_url'=>'https://kniferevive.com/terms-and-conditions/','listing_policy_version'=>'test-1','return_policy_url'=>'https://kniferevive.com/return-policy/']);
update_option('krev_agent_settings',$s,false);
update_option('woocommerce_calc_taxes','no');update_option('woocommerce_manage_stock','yes');
update_option('pisol_cefw_payment_gateway_charges',[]); // Only the fenced synthetic site's fee rules.
class ListingTestGateway extends WC_Payment_Gateway {public function __construct(){$this->id='stripe';$this->enabled='yes';$this->title='Synthetic gateway';$this->settings=['testmode'=>'yes'];}public function is_available(){return true;}}
add_filter('woocommerce_payment_gateways',static function($g){$g=array_values(array_filter($g,static fn($class)=>!str_contains(is_string($class)?$class:get_class($class),'Stripe')));$g[]=ListingTestGateway::class;return $g;},1000);WC()->payment_gateways()->init();
$owner=Domain::id();$foreign=Domain::id();
foreach([$owner,$foreign] as $id)Store::put($id,'session','synthetic',time()+7200,['token_hash'=>hash('sha256',Domain::token($id))]);
$catalog=ListingCheckout::catalog(['per_page'=>100]);$ids=array_column($catalog['items'],'product_id');
foreach([$knife,$tech,$art,$spice,$small,$large] as $p)listingCheck(in_array($p->get_id(),$ids,true),'discovery includes category '.$p->get_category_ids()[0]);
$detail=ListingCheckout::product($knife->get_id());
listingCheck($detail['unit_price_minor']===1200,'WooCommerce current sale price is used');
listingCheck($detail['seller']['display_name']==='Synthetic Seller' && $detail['canonical_url']===get_permalink($knife->get_id()),'original seller and permalink retained');
listingCheck(ListingCheckout::product($small->get_id())['checkout_eligibility']==='needs_manual_review','sharpening without vetted terms blocked');
$customerAuthor=wp_insert_user(['user_login'=>'listing-customer-'.Domain::id(),'user_pass'=>Domain::id(),'user_email'=>Domain::id().'@example.invalid','role'=>'customer']);
$customerListing=listingProduct('art',$customerAuthor);
listingCheck(ListingCheckout::product($customerListing->get_id())['checkout_eligibility']==='seller_disabled','ordinary customer cannot act as a marketplace seller');
$operatorListing=listingProduct('art',1);
update_user_meta(1,'dokan_enable_selling','yes'); // Fenced operator fixture follows native seller eligibility too.
listingCheck(ListingCheckout::product($operatorListing->get_id())['checkout_eligibility']==='handoff_only','existing merchant administrator can sell its own listing without changing capabilities');
$nativeReturn=wp_insert_term('Synthetic return terms '.Domain::id(),'kr_return_policy',['description'=>'Synthetic buyer return terms.']);
wp_set_object_terms($knife->get_id(),[$nativeReturn['term_id']],'kr_return_policy');
update_term_meta($nativeReturn['term_id'],'_kr_badge_label','Synthetic returns');
listingCheck(ListingCheckout::product($knife->get_id())['return_policy']['description']==='Synthetic buyer return terms.','assigned native return policy disclosed as data');
$r=new WP_REST_Request('GET','/kniferevive-agent/v1/listings');$r->set_query_params(['page'=>'1','per_page'=>'100']);$apiListing=rest_do_request($r);listingCheck($apiListing->get_status()===200 && count($apiListing->get_data()['items'])>=7,'registered public listing API accepts numeric query parameters');
$r=new WP_REST_Request('POST','/kniferevive-agent/v1/listing-checkouts');$r->set_header('Content-Type','application/json');$r->set_header('X-Krev-Agent-Session',Domain::token($owner));$r->set_header('Idempotency-Key','listing-api-create');$r->set_body(wp_json_encode(['items'=>[['product_id'=>$tech->get_id(),'quantity'=>1]]]));$apiIntent=rest_do_request($r);listingCheck($apiIntent->get_status()===200 && $apiIntent->get_data()['payment_state']==='not_started','registered private API creates intent without payment');
$r=new WP_REST_Request('GET','/kniferevive-agent/v1/listing-checkouts/'.$apiIntent->get_data()['intent_id'].'/status');$r->set_header('X-Krev-Agent-Session',Domain::token($foreign));listingCheck(rest_do_request($r)->get_status()===404,'registered status API refuses another shopper session');
$input=['items'=>[['product_id'=>$knife->get_id(),'quantity'=>1]]];
$intent=ListingCheckout::create($input,$owner,'listing-create-one');
listingCheck($intent['id']===ListingCheckout::create($input,$owner,'listing-create-one')['id'],'intent creation idempotent');
listingReject(static fn()=>ListingCheckout::create(['items'=>[['product_id'=>$art->get_id(),'quantity'=>1]]],$owner,'listing-create-one'),'IDEMPOTENCY_CONFLICT','same key different items rejected');
listingReject(static fn()=>ListingCheckout::get($intent['id'],$foreign),'NOT_FOUND','foreign intent private');
$estimate=ListingCheckout::quote($intent['id'],[],$owner,'listing-estimate-one');
listingCheck($estimate['data']['quote']['estimate_only'] && $estimate['data']['quote']['total_minor']===null,'unknown address never falsely final');
listingReject(static fn()=>ListingCheckout::create(['items'=>[['product_id'=>$knife->get_id(),'quantity'=>1]],'price'=>1],$owner,'listing-client-price'),'INVALID_REQUEST','client price rejected');
listingReject(static fn()=>ListingCheckout::create(['items'=>[['product_id'=>$knife->get_id(),'quantity'=>1],['product_id'=>$other->get_id(),'quantity'=>1]]],$owner,'listing-many-sellers'),'MULTI_SELLER_UNSUPPORTED','multiple sellers rejected instead of silent splitting');
$address=['address_1'=>'123 Synthetic Street','city'=>'San Francisco','state'=>'CA','postcode'=>'94110','country'=>'US'];
$context=['billing'=>$address,'shipping'=>$address,'email'=>'synthetic@example.invalid','payment_method'=>'stripe'];
$orders=count(wc_get_orders(['limit'=>-1]));
$quote=ListingCheckout::quote($intent['id'],$context,$owner,'listing-final-quote');
file_put_contents(dirname(__DIR__).'/.runtime/listing-first-quote.json',json_encode($quote['data']['quote'],JSON_PRETTY_PRINT));
listingCheck(!$quote['data']['quote']['estimate_only'] && $quote['data']['quote']['total_minor']===1200,'native virtual cart exact total');
listingCheck(count(wc_get_orders(['limit'=>-1]))===$orders,'quotes create no native order');
$coupon=new WC_Coupon();$coupon->set_code('listing-'.Domain::id());$coupon->set_discount_type('fixed_cart');$coupon->set_amount('2');$coupon->save();
wp_update_post(['ID'=>$coupon->get_id(),'post_author'=>$vendor]); // Actual Dokan vendor coupon policy.
$couponInput=['items'=>[['product_id'=>$art->get_id(),'quantity'=>1]],'coupons'=>[$coupon->get_code()]];
$discount=ListingCheckout::create($couponInput,$owner,'listing-coupon-create');
$discount=ListingCheckout::quote($discount['id'],$context,$owner,'listing-coupon-quote');
listingCheck($discount['data']['quote']['discount_minor']===200 && $discount['data']['quote']['total_minor']===1000,'native coupon discount validated');
listingReject(static fn()=>ListingCheckout::create(['items'=>[['product_id'=>$tech->get_id(),'quantity'=>1]],'coupons'=>['not-a-real-coupon']],$owner,'listing-bad-coupon'),'COUPON_UNAVAILABLE','unknown coupon rejected');
$variable=new WC_Product_Variable();$variable->set_name('Unsupported variable');$variable->set_status('publish');$variable->save();
listingReject(static fn()=>ListingCheckout::create(['items'=>[['product_id'=>$variable->get_id(),'quantity'=>1]]],$owner,'listing-variable-test'),'UNSUPPORTED_VARIATION','variable requires tested selection support');
if (!WC()->session) {WC()->session=new WC_Session_Handler();WC()->session->init();}
if (!WC()->customer) WC()->customer=new WC_Customer(0,true);
if (!WC()->cart) WC()->cart=new WC_Cart();
WC()->session->set('krev_listing_fixture_session','synthetic-browser');
WC()->cart->empty_cart();WC()->cart->add_to_cart($tech->get_id(),1);
listingReject(static fn()=>ListingCheckout::handoff($intent['id'],$owner,$quote['data']['quote']['quote_hash']),'CART_CONFLICT','preexisting cart never discarded');
listingCheck(count(WC()->cart->get_cart())===1,'cart conflict preserves contents');
WC()->cart->empty_cart();
wp_update_term($nativeReturn['term_id'],'kr_return_policy',['description'=>'Changed synthetic return terms.']);
listingReject(static fn()=>ListingCheckout::handoff($intent['id'],$owner,$quote['data']['quote']['quote_hash']),'QUOTE_CHANGED','changed native return terms require fresh buyer review');
wp_update_term($nativeReturn['term_id'],'kr_return_policy',['description'=>'Synthetic buyer return terms.']);
$changed=wc_get_product($knife->get_id());$changed->set_sale_price('13');$changed->set_price('13');$changed->save();
listingReject(static fn()=>ListingCheckout::handoff($intent['id'],$owner,$quote['data']['quote']['quote_hash']),'QUOTE_CHANGED','price changes require buyer review');
$changed->set_sale_price('12');$changed->set_price('12');$changed->save();
$quote=ListingCheckout::quote($intent['id'],$context,$owner,'listing-final-again');
$url=ListingCheckout::handoff($intent['id'],$owner,$quote['data']['quote']['quote_hash']);
listingCheck($url===wc_get_checkout_url(),'handoff leads to actual native checkout');
listingCheck(ListingCheckout::handoff($intent['id'],$owner,$quote['data']['quote']['quote_hash'])===$url && count(WC()->cart->get_cart())===1,'double click does not add duplicate lines');
$originalSession=WC()->session;WC()->session=new WC_Session_Handler();WC()->session->init();
listingReject(static fn()=>ListingCheckout::handoff($intent['id'],$owner,$quote['data']['quote']['quote_hash']),'CART_CONFLICT','cross-browser intent replay rejected');WC()->session=$originalSession;
listingCheck(count(wc_get_orders(['limit'=>-1]))===$orders,'handoff has not charged or created an order');
listingReject(static fn()=>ListingCheckout::quote($intent['id'],$context,$owner,'listing-after-handoff'),'INTENT_ALREADY_USED','handed-off intent cannot be rewritten');
$data=['payment_method'=>'stripe','billing_email'=>$context['email'],'billing_first_name'=>'Synthetic','billing_last_name'=>'Shopper'];
foreach(['billing','shipping'] as $kind)foreach($address as $field=>$value)$data[$kind.'_'.$field]=$value;
$orderId=WC()->checkout()->create_order($data);
if(is_wp_error($orderId))throw new RuntimeException('Native order rejected: '.$orderId->get_error_message());
$order=wc_get_order($orderId);
$nativeItems=$order->get_items();listingCheck(str_contains((string)reset($nativeItems)->get_meta('_kr_return_policy_snapshot'),'Synthetic buyer return terms.'),'native plugin retains its assigned return-policy order snapshot');
$status=ListingCheckout::status($intent['id'],$owner);
listingCheck($status['payment_state']==='pending' && $status['scheduling_state']==='not_applicable','order binding never invents paid or appointment');
listingCheck(!isset($status['order_id'],$status['order_key'],$status['billing'],$status['transaction_id']),'status does not expose native keys or buyer data');
listingReject(static fn()=>ListingCheckout::status($intent['id'],$foreign),'NOT_FOUND','foreign order status remains private');
$recovery=Store::get($intent['id'])['data'];$recovery['order_id']=null;$recovery['handoff_state']='cart_ready';Store::update($intent['id'],$recovery);
listingReject(static fn()=>ListingCheckout::recoverOriginalOrder($intent['id']),'FORBIDDEN','recovery requires merchant access');
wp_set_current_user(1);listingCheck(ListingCheckout::recoverOriginalOrder($intent['id'])===$order->get_id(),'interrupted binding recovers only existing native order without charge');wp_set_current_user(0);
if(function_exists('dokan')){
    $allocation=KR_Connect_Allocation::for_order($order);
    listingCheck(!is_wp_error($allocation) && count($allocation)===1,'actual Dokan persists one seller allocation');
    listingCheck(!in_array((int)$orderId,array_map('intval',KREV_Orders_Query::seller_order_ids($vendor)),true),'Seller Orders retains its native virtual-item exclusion');
    wc_release_stock_for_order($order); // Compare another unpaid fixture; never settle either order.
    WC()->session->set('krev_listing_intent',null);WC()->session->set('krev_listing_owner',null);WC()->session->set('order_awaiting_payment',null);
    $ordinaryId=WC()->checkout()->create_order($data);if(is_wp_error($ordinaryId))throw new RuntimeException($ordinaryId->get_error_message());
    $ordinary=KR_Connect_Allocation::for_order(wc_get_order($ordinaryId));
    listingCheck(!is_wp_error($ordinary) && $allocation[0]['gross_amount']===$ordinary[0]['gross_amount'] && $allocation[0]['commission_amount']===$ordinary[0]['commission_amount'] && $allocation[0]['transfer_amount']===$ordinary[0]['transfer_amount'],'ordinary native and agent order seller bookkeeping match (no processor payout)');
    WC()->session->set('krev_listing_intent',$intent['id']);WC()->session->set('krev_listing_owner',$owner);
}
$again=wc_create_order();$again->add_product($knife,1);$again->set_payment_method('stripe');$again->set_total('12');
listingReject(static fn()=>ListingCheckout::bindOrder($again),'ORDER_ALREADY_EXISTS','second order rejected after handoff');
$again->delete(true);
listingReject(static fn()=>ListingCheckout::create($input,$owner,'listing-uncertain-retry'),'PAYMENT_UNRESOLVED','new intent cannot bypass unresolved native payment');
$order->update_status('processing');
listingCheck(ListingCheckout::status($intent['id'],$owner)['payment_state']==='needs_review','manual order status is not provider verification');
$_SERVER['HTTP_ORIGIN']='https://untrusted.invalid';$_SERVER['HTTP_SEC_FETCH_SITE']='cross-site';
listingCheck(!Api::firstPartyForm(),'cross-site approval rejected');
listingReject(static fn()=>KnifeRevive\AgentCommerce\ListingFrontend::authorizeForm($owner,$intent['id'],['csrf'=>'forged']),'FORBIDDEN','listing approval rejects CSRF forgery');
$_SERVER['HTTP_ORIGIN']=WP_HOME;unset($_SERVER['HTTP_SEC_FETCH_SITE']);
listingReject(static fn()=>KnifeRevive\AgentCommerce\ListingFrontend::authorizeForm($owner,$intent['id'],['csrf'=>'forged']),'FORBIDDEN','same-origin request still needs valid CSRF');
KnifeRevive\AgentCommerce\ListingFrontend::authorizeForm($owner,$intent['id'],['csrf'=>KnifeRevive\AgentCommerce\ListingFrontend::csrf($owner,$intent['id'],intdiv(time(),600))]);
listingCheck(true,'scoped first-party CSRF accepted');
WC()->session->set('krev_listing_intent',null);WC()->session->set('krev_listing_owner',null);WC()->cart->empty_cart();
WC()->session->set('order_awaiting_payment',null);
$expired=ListingCheckout::create(['items'=>[['product_id'=>$spice->get_id(),'quantity'=>1]]],$owner,'listing-expired-create');
$wpdb->update(Store::table('records'),['expires'=>time()-1],['id'=>$expired['id']]);
listingReject(static fn()=>ListingCheckout::get($expired['id'],$owner),'INTENT_EXPIRED','expired intent cannot initiate checkout');
if(function_exists('dokan')){
    update_user_meta($vendor2,'dokan_enable_selling','no');
    listingReject(static fn()=>ListingCheckout::create(['items'=>[['product_id'=>$other->get_id(),'quantity'=>1]]],$owner,'listing-disabled-seller'),'LISTING_UNAVAILABLE','disabled native seller rejected');
    update_user_meta($vendor2,'dokan_enable_selling','yes');
}
$s['listing_services']=[['product_id'=>$small->get_id(),'terms_url'=>'https://kniferevive.com/test-service-policy/','policy_version'=>'test-service-1','fulfillment_note'=>'Synthetic customer collection; scheduling must be arranged separately.','native_fulfillment_verified'=>true],['product_id'=>$large->get_id(),'terms_url'=>'https://kniferevive.com/test-service-policy/','policy_version'=>'test-service-1','fulfillment_note'=>'Synthetic customer collection; scheduling must be arranged separately.','native_fulfillment_verified'=>true]];
update_option('krev_agent_settings',Settings::validate($s),false);
foreach([$small,$large] as $service){$serviceIntent=ListingCheckout::create(['items'=>[['product_id'=>$service->get_id(),'quantity'=>1]]],$owner,'listing-service-'.$service->get_id());$serviceQuote=ListingCheckout::quote($serviceIntent['id'],$context,$owner,'listing-service-quote-'.$service->get_id());listingCheck($serviceQuote['data']['quote']['total_minor']===1200 && ListingCheckout::status($serviceIntent['id'],$owner)['scheduling_state']==='not_booked','vetted synthetic sharpening SKU quoted without appointment promise');}
$feeHook=static fn($cart)=>$cart->add_fee('Synthetic native fee',3,false);add_action('woocommerce_cart_calculate_fees',$feeHook);
$actualFee=class_exists('Apply_Payment_Processing_Fee')?100:0;
if($actualFee)update_option('pisol_cefw_payment_gateway_charges',['stripe'=>['apply_fee'=>1,'amount'=>1,'fee_type'=>'fixed','name'=>'Synthetic installed-plugin processing fee']]);
$block=ListingCheckout::create(['items'=>[['product_id'=>$tech->get_id(),'quantity'=>1]]],$owner,'listing-block-intent');$block=ListingCheckout::quote($block['id'],$context,$owner,'listing-block-quote');
listingCheck($block['data']['quote']['total_minor']===1500+$actualFee && array_sum(array_column($block['data']['quote']['fees'],'total_minor'))===300+$actualFee,'native fee hooks and installed processing-fee plugin included in quote');
ListingCheckout::handoff($block['id'],$owner,$block['data']['quote']['quote_hash']);
$controller=new \Automattic\WooCommerce\StoreApi\Utilities\OrderController();$blockOrder=$controller->create_order_from_cart();$blockOrder->set_payment_method('stripe');$blockOrder->set_billing_first_name('Synthetic');$blockOrder->set_billing_last_name('Shopper');$blockOrder->save();
$errors=new WP_Error();do_action('woocommerce_checkout_validate_order_before_payment',$blockOrder,$errors);
listingCheck(!$errors->has_errors(),'native Checkout Block prepayment guard accepts exact reviewed cart');
do_action('woocommerce_store_api_checkout_order_processed',$blockOrder);
listingCheck($blockOrder->get_meta('_krev_listing_intent')===$block['id'] && ListingCheckout::status($block['id'],$owner)['payment_state']==='pending','native Store API processed hook binds original order');
if(function_exists('dokan'))listingCheck(!is_wp_error(KR_Connect_Allocation::for_order($blockOrder)),'Dokan Store API hook retains seller allocation');
$errors=new WP_Error();$blockOrder->set_total('99');do_action('woocommerce_checkout_validate_order_before_payment',$blockOrder,$errors);listingCheck($errors->has_errors(),'Checkout Block changed total blocked before gateway');
remove_action('woocommerce_cart_calculate_fees',$feeHook);update_option('pisol_cefw_payment_gateway_charges',[]);
WC()->session->set('krev_listing_intent',null);WC()->session->set('krev_listing_owner',null);WC()->session->set('store_api_draft_order',null);WC()->cart->empty_cart();
$raceProduct=listingProduct('chefs-knife',$vendor,'Synthetic scarce knife');$raceOrders=[];foreach(range(1,2) as $i){$race=wc_create_order();$race->add_product($raceProduct,1);$race->calculate_totals();$raceOrders[]=$race->get_id();}
$workers=[];foreach($raceOrders as $raceId){$pipes=[];$process=proc_open([PHP_BINARY,'-n','-d','extension_dir='.ini_get('extension_dir'),'-d','extension=mysqli','-d','extension=mbstring','-d','extension=openssl','-d','extension=curl',__DIR__.'/listing-stock-worker.php',$argv[1],(string)$raceId],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$workers[]=[$process,$pipes];}
$results=[];foreach($workers as [$process,$pipes]){$results[]=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);listingCheck(proc_close($process)===0,'native stock worker completed');}sort($results);listingCheck($results===['RESERVED','UNAVAILABLE'],'concurrent native reservations admit only one quantity-1 order (not settlement proof)');
foreach($raceOrders as $raceId)wc_release_stock_for_order(wc_get_order($raceId));
$physical=listingProduct('art',$vendor,'Synthetic shipped item');$physical->set_virtual(false);$physical->set_weight('1');$physical->save();
$zone=new WC_Shipping_Zone();$zone->set_zone_name('Synthetic CA');$zone->set_zone_order(0);$zone->add_location('US:CA','state');$zone->save();
$method=$zone->add_shipping_method('flat_rate');update_option('woocommerce_flat_rate_'.$method.'_settings',['enabled'=>'yes','title'=>'Synthetic flat rate','tax_status'=>'taxable','cost'=>'5']);
update_option('woocommerce_ship_to_countries','');update_option('woocommerce_shipping_cost_requires_address','yes');
$shipped=ListingCheckout::create(['items'=>[['product_id'=>$physical->get_id(),'quantity'=>1]]],$owner,'listing-shipping-intent');
$q=ListingCheckout::quote($shipped['id'],$context,$owner,'listing-shipping-choose');
listingCheck($q['data']['quote']['estimate_only'] && count($q['data']['quote']['shipping_rates'])===1,'native shipping choices required before final total');
$shippingContext=$context;$shippingContext['shipping_methods']=[$q['data']['quote']['shipping_rates'][0]['options'][0]['id']];
$q=ListingCheckout::quote($shipped['id'],$shippingContext,$owner,'listing-shipping-final');
listingCheck($q['data']['quote']['shipping_minor']===500 && $q['data']['quote']['total_minor']===1700,'native selected shipping included');
update_option('woocommerce_calc_taxes','yes');update_option('woocommerce_tax_based_on','shipping');
$tax=WC_Tax::_insert_tax_rate(['tax_rate_country'=>'US','tax_rate_state'=>'CA','tax_rate'=>'10','tax_rate_name'=>'Synthetic tax','tax_rate_priority'=>1,'tax_rate_compound'=>0,'tax_rate_shipping'=>1,'tax_rate_order'=>0,'tax_rate_class'=>'']);
WC_Cache_Helper::invalidate_cache_group('taxes');
$q=ListingCheckout::quote($shipped['id'],$shippingContext,$owner,'listing-shipping-tax');
listingCheck($q['data']['quote']['tax_minor']===170 && $q['data']['quote']['total_minor']===1870,'native product and shipping tax included');
ListingCheckout::handoff($shipped['id'],$owner,$q['data']['quote']['quote_hash']);
$shippingOrderId=WC()->checkout()->create_order($data);if(is_wp_error($shippingOrderId))throw new RuntimeException($shippingOrderId->get_error_message());
listingCheck((string)wc_get_order($shippingOrderId)->get_total()==='18.70','native physical order retains reviewed shipping/tax total');
if(function_exists('dokan'))listingCheck(in_array((int)$shippingOrderId,array_map('intval',KREV_Orders_Query::seller_order_ids($vendor)),true),'actual Seller Orders indexes physical native agent order');
WC_Tax::_delete_tax_rate($tax);WC_Cache_Helper::invalidate_cache_group('taxes');update_option('woocommerce_calc_taxes','no');
$zone->delete();
wp_set_current_user(0);
listingReject(static fn()=>KnifeRevive\AgentCommerce\GatewayDiagnostics::snapshot(),'FORBIDDEN','diagnostics are not public');
wp_set_current_user(1);$diagnostic=KnifeRevive\AgentCommerce\GatewayDiagnostics::snapshot();wp_set_current_user(0);
listingCheck(!str_contains(json_encode($diagnostic),'sk_test_synthetic_fixture') && !str_contains(json_encode($diagnostic),'synthetic-webhook-fixture-only'),'diagnostics omit credential values');
$oldQuote=Domain::id();$abandoned=Domain::id();$financial=Domain::id();$interrupted=Domain::id();
Store::put($oldQuote,'listing_quote',$owner,time()-90000,['context'=>$context]);
Store::put($abandoned,'listing',$owner,time()-90000,['handoff_state'=>'review','context'=>$context,'order_id'=>null]);
Store::put($financial,'listing',$owner,time()-90000,['handoff_state'=>'order_linked','context'=>$context,'order_id'=>$orderId]);
Store::put($interrupted,'listing',$owner,time()-90000,['handoff_state'=>'review','creation_started'=>true,'order_id'=>null]);
Store::pruneEphemeral();
listingReject(static fn()=>Store::get($oldQuote),'NOT_FOUND','expired disposable listing quote context is pruned');
listingReject(static fn()=>Store::get($abandoned),'NOT_FOUND','abandoned review context is pruned after expiry grace');
listingCheck(Store::get($financial)['data']['order_id']===$orderId,'pruning retains linked financial evidence');
listingCheck(Store::get($interrupted)['data']['creation_started']===true,'pruning retains interrupted order-creation evidence');
$samples=['Listing'=>ListingCheckout::product($knife->get_id()),'Listings'=>ListingCheckout::catalog(['per_page'=>10]),'ListingIntent'=>ListingCheckout::response($q,$owner),'ListingStatus'=>ListingCheckout::status($intent['id'],$owner)];
file_put_contents(dirname(__DIR__).'/.runtime/listing-contract-samples.json',json_encode($samples,JSON_PRETTY_PRINT));
echo "$passed listing checkout assertions passed.\n";
