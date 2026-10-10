<?php
ob_start();set_exception_handler(static function(Throwable $e){fwrite(STDERR,'FAIL: '.$e->getMessage().' at '.$e->getLine()."\n");exit(1);});
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Domain,Fault,ListingCheckout,Settings,Store};
if(DB_NAME!=='krev_agent_sandbox' || DB_HOST!==KREV_TEST_DB_HOST || $wpdb->prefix!=='krev_sandbox_')throw new RuntimeException('Sandbox fence failed');
$checks=0;function goodsCheck(bool $ok,string $label): void {global $checks;if(!$ok)throw new RuntimeException($label);++$checks;echo "PASS: $label\n";}
function goodsReject(callable $work,string $code): void {try{$work();}catch(Fault $e){goodsCheck($e->codeName===$code,$code);return;}throw new RuntimeException('Missing rejection '.$code);}
wp_set_current_user(0);
$vendor=wp_insert_user(['user_login'=>'fulfill-'.Domain::id(),'user_pass'=>Domain::id(),'user_email'=>Domain::id().'@example.invalid','role'=>'seller']);update_user_meta($vendor,'dokan_enable_selling','yes');
class GoodsFixtureGateway extends WC_Payment_Gateway {public function __construct(){$this->id='stripe';$this->enabled='yes';$this->supports=['products','refunds'];$this->settings=['testmode'=>'yes'];}public function is_available(){return true;}}
add_filter('woocommerce_payment_gateways',static fn($g)=>[GoodsFixtureGateway::class],1000);WC()->payment_gateways()->init();
update_option('krev_agent_settings',Settings::validate(['listing_handoff_enabled'=>true,'listing_pricing_verified'=>true,'listing_gateway_ids'=>['stripe'],'listing_policy_url'=>'https://kniferevive.com/terms-and-conditions/','listing_policy_version'=>'goods-fixture','return_policy_url'=>'https://kniferevive.com/return-policy/']),false);
update_option('woocommerce_calc_taxes','no');update_option('pisol_cefw_payment_gateway_charges',[]);update_option('woocommerce_ship_to_countries','');
$owner=Domain::id();$foreign=Domain::id();foreach([$owner,$foreign] as $id)Store::put($id,'session','fixture',time()+7200,['token_hash'=>hash('sha256',Domain::token($id))]);
$class=wp_insert_term('Synthetic heavy '.Domain::id(),'product_shipping_class');
$p=new WC_Product_Simple();$p->set_name('Synthetic goods shipment');$p->set_status('publish');$p->set_price('12');$p->set_regular_price('12');$p->set_weight('2');$p->set_shipping_class_id($class['term_id']);$p->set_manage_stock(true);$p->set_stock_quantity(20);$p->save();wp_update_post(['ID'=>$p->get_id(),'post_author'=>$vendor]);
$zone=new WC_Shipping_Zone();$zone->set_zone_name('Synthetic goods CA');$zone->set_zone_order(0);$zone->add_location('US:CA','state');$zone->save();
$flat=$zone->add_shipping_method('flat_rate');$delivery=$zone->add_shipping_method('flat_rate');$pickup=$zone->add_shipping_method('local_pickup');
foreach([$flat,$delivery] as $instance)update_option('woocommerce_flat_rate_'.$instance.'_settings',['enabled'=>'yes','title'=>$instance===$flat?'Synthetic parcel':'Synthetic native delivery','tax_status'=>'taxable','cost'=>'5','class_cost_'.$class['term_id']=>'3 * [qty]']);
update_option('woocommerce_local_pickup_'.$pickup.'_settings',['enabled'=>'yes','title'=>'Local pickup — Synthetic depot','tax_status'=>'taxable','cost'=>'2']);delete_transient('wc_shipping_method_count');WC_Cache_Helper::get_transient_version('shipping',true);
$address=['address_1'=>'1 Synthetic Street','address_2'=>'','city'=>'Oakland','state'=>'CA','postcode'=>'94612','country'=>'US'];$context=['billing'=>$address,'shipping'=>$address,'email'=>'synthetic@example.invalid','payment_method'=>'stripe'];
$selection=['scope'=>'goods','items'=>[['product_id'=>$p->get_id(),'quantity'=>2]]];
$serviceCategory=get_term_by('slug','knife-sharpening','product_cat');if(!$serviceCategory){wp_insert_term('Knife sharpening','product_cat',['slug'=>'knife-sharpening']);$serviceCategory=get_term_by('slug','knife-sharpening','product_cat');}
$service=new WC_Product_Simple();$service->set_name('Synthetic sharpening service');$service->set_status('publish');$service->set_price('5');$service->set_regular_price('5');$service->set_virtual(true);$service->set_category_ids([$serviceCategory->term_id]);$service->save();wp_update_post(['ID'=>$service->get_id(),'post_author'=>$vendor]);
goodsReject(static fn()=>ListingCheckout::create(['scope'=>'goods','items'=>[['product_id'=>$service->get_id(),'quantity'=>1]]],$owner,'goods-exclude-service'),'SERVICE_EXCLUDED');
$intent=ListingCheckout::create($selection,$owner,'goods-ship-intent');$q=ListingCheckout::quote($intent['id'],$context,$owner,'goods-rates-incomplete');
goodsCheck($q['data']['quote']['estimate_only'] && $q['data']['quote']['shipping_minor']===null,'unknown shipping stays null');
goodsCheck(count($q['data']['quote']['shipping_rates'][0]['options'])===3,'native parcel/delivery/pickup options only');
$context['shipping_methods']=['flat_rate:'.$flat];$q=ListingCheckout::quote($intent['id'],$context,$owner,'goods-parcel-quote');goodsCheck($q['data']['quote']['shipping_minor']===1100 && $q['data']['quote']['total_minor']===3500,'native class and quantity shipping');
goodsCheck(!array_filter($q['data']['quote']['fees'],static fn($fee)=>str_contains(strtolower($fee['name']),'trip')),'no sharpening trip fee on goods');
$pickupContext=$context;$pickupContext['shipping_methods']=['local_pickup:'.$pickup];$pq=ListingCheckout::price($q['data']['selection'],$pickupContext);goodsCheck($pq['shipping_minor']===200 && $pq['total_minor']===2600,'native charged pickup, not invented free pickup');
$deliveryContext=$context;$deliveryContext['shipping_methods']=['flat_rate:'.$delivery];$dq=ListingCheckout::price($q['data']['selection'],$deliveryContext);goodsCheck($dq['shipping_minor']===1100,'delivery uses native rate, no trip charge');
$invalid=$context;$invalid['shipping_methods']=['flat_rate:999999'];goodsReject(static fn()=>ListingCheckout::price($q['data']['selection'],$invalid),'SHIPPING_UNAVAILABLE');
$outside=$context;$outside['shipping']['state']='FL';$outside['shipping']['postcode']='33101';$outside['shipping_methods']=[];goodsReject(static fn()=>ListingCheckout::price($q['data']['selection'],$outside),'SHIPPING_UNAVAILABLE');
// Split one seller's native cart into two packages: do not collapse rate selections.
$split=static function($packages){$first=reset($packages);return [$first,$first];};add_filter('woocommerce_cart_shipping_packages',$split,1000);
$multi=$context;$multi['shipping_methods']=['local_pickup:'.$pickup,'flat_rate:'.$flat];$mq=ListingCheckout::price($q['data']['selection'],$multi);goodsCheck(count($mq['shipping_rates'])===2 && $mq['shipping_rates'][0]['selected']===$multi['shipping_methods'][0] && $mq['shipping_rates'][1]['selected']===$multi['shipping_methods'][1] && $mq['shipping_minor']===1300,'two native package choices retained');
remove_filter('woocommerce_cart_shipping_packages',$split,1000);
if(!WC()->session || !WC()->cart || !WC()->customer)wc_load_cart();WC()->cart->empty_cart();WC()->session->set('order_awaiting_payment',null);WC()->session->set('store_api_draft_order',null);
$saved=$q['data'];$expired=$saved;$expired['quote_expires']=time()-1;Store::update($intent['id'],$expired);goodsReject(static fn()=>ListingCheckout::handoff($intent['id'],$owner,$q['data']['quote']['quote_hash']),'QUOTE_CHANGED');Store::update($intent['id'],$saved);
goodsReject(static fn()=>ListingCheckout::handoff($intent['id'],$owner,str_repeat('0',64)),'QUOTE_CHANGED');
ListingCheckout::handoff($intent['id'],$owner,$q['data']['quote']['quote_hash']);goodsCheck(array_values(WC()->cart->get_cart())[0]['quantity']===2 && WC()->session->get('chosen_shipping_methods')===$context['shipping_methods'],'selected quantity and rate reach native cart');
$data=['billing_email'=>$context['email'],'payment_method'=>'stripe','billing_first_name'=>'Synthetic','billing_last_name'=>'Buyer','shipping_first_name'=>'Synthetic','shipping_last_name'=>'Buyer'];foreach(['billing','shipping'] as $kind)foreach($address as $field=>$value)$data[$kind.'_'.$field]=$value;
$orderId=WC()->checkout()->create_order($data);if(is_wp_error($orderId))throw new RuntimeException($orderId->get_error_message());$order=wc_get_order($orderId);goodsCheck((string)$order->get_total()==='35.00','native order matches reviewed goods total');
$retry=ListingCheckout::goodsPaymentUrl($intent['id'],$owner);goodsCheck(str_contains($retry,(string)$orderId) && $order->get_meta('_krev_listing_intent')===$intent['id'],'same original goods order recovery');
goodsReject(static fn()=>ListingCheckout::goodsPaymentUrl($intent['id'],$foreign),'NOT_FOUND');
$shipping=array_values($order->get_items('shipping'))[0];$shipping->set_instance_id($delivery);$shipping->save();goodsReject(static fn()=>ListingCheckout::goodsPaymentUrl($intent['id'],$owner),'QUOTE_CHANGED');$shipping->set_instance_id($flat);$shipping->save();
WC_Stripe_Order_Helper::get_instance()->update_stripe_intent_id($order,'pi_syntheticGoods');$order->save();$processorState='processing';$processorAmount=3500;
$processor=static function($pre,$args,$url)use(&$processorState,&$processorAmount,$order){if(!str_ends_with($url,'/payment_intents/pi_syntheticGoods'))return $pre;return ['headers'=>[],'body'=>wp_json_encode(['id'=>'pi_syntheticGoods','status'=>$processorState,'amount'=>$processorAmount,'currency'=>'usd','metadata'=>['order_id'=>(string)$order->get_order_number()]]),'response'=>['code'=>200,'message'=>'OK'],'cookies'=>[]];};add_filter('pre_http_request',$processor,10,3);
goodsReject(static fn()=>ListingCheckout::goodsPaymentUrl($intent['id'],$owner),'PAYMENT_UNRESOLVED');$processorState='requires_payment_method';$processorAmount=3501;goodsReject(static fn()=>ListingCheckout::goodsPaymentUrl($intent['id'],$owner),'PAYMENT_UNRESOLVED');$processorAmount=3500;goodsCheck(ListingCheckout::goodsPaymentUrl($intent['id'],$owner)===$retry,'verified unpaid original intent retried without replacement');
remove_filter('pre_http_request',$processor,10);$order->payment_complete('synthetic-goods-only');goodsCheck(ListingCheckout::status($intent['id'],$owner)['payment_state']==='paid','native paid event evidence');goodsReject(static fn()=>ListingCheckout::goodsPaymentUrl($intent['id'],$owner),'PAYMENT_UNRESOLVED');
$zone->delete();WC_Cache_Helper::get_transient_version('shipping',true);WC()->cart->empty_cart();WC()->session->set('order_awaiting_payment',null);
// Match the live store's native block-pickup configuration without inventing a zone.
$oldCheckout=wc_get_page_id('checkout');$oldPickup=get_option('woocommerce_pickup_location_settings');$oldLocations=get_option('pickup_location_pickup_locations');
$blockPage=wp_insert_post(['post_title'=>'Synthetic block checkout','post_content'=>'<!-- wp:woocommerce/checkout /-->','post_type'=>'page','post_status'=>'publish']);update_option('woocommerce_checkout_page_id',$blockPage);
update_option('woocommerce_pickup_location_settings',['enabled'=>'yes','title'=>'Synthetic block pickup','cost'=>'','tax_status'=>'taxable']);
update_option('pickup_location_pickup_locations',[['name'=>'Synthetic pickup depot','enabled'=>true,'address'=>['address_1'=>'1 Synthetic Depot','address_2'=>'','city'=>'Pittsburg','state'=>'CA','postcode'=>'94565','country'=>'US'],'details'=>'Synthetic collection only']]);
WC()->shipping()->load_shipping_methods();WC_Cache_Helper::get_transient_version('shipping',true);
$blockContext=$context;$blockContext['shipping_methods']=['pickup_location:0'];$blockQuote=ListingCheckout::price($q['data']['selection'],$blockContext);
$blockRate=$blockQuote['shipping_rates'][0]['options'][0];
goodsCheck($blockQuote['shipping_minor']===0 && $blockQuote['total_minor']===2400 && $blockRate['method_id']==='pickup_location','native block pickup supplies its actual zero rate without a shipping zone');
goodsCheck($blockRate['pickup_location']==='Synthetic pickup depot' && str_contains($blockRate['pickup_address'],'94565'),'native block pickup name and address reach buyer review');
update_option('woocommerce_checkout_page_id',$oldCheckout);update_option('woocommerce_pickup_location_settings',$oldPickup);update_option('pickup_location_pickup_locations',$oldLocations);wp_delete_post($blockPage,true);
WC()->shipping()->load_shipping_methods();WC_Cache_Helper::get_transient_version('shipping',true);$noMethod=$context;$noMethod['shipping_methods']=[];
goodsReject(static fn()=>ListingCheckout::price($q['data']['selection'],$noMethod),'SHIPPING_UNAVAILABLE');
echo "$checks goods fulfillment assertions passed. Synthetic processor only.\n";
