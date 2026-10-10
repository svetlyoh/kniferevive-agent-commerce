<?php
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Domain,ListingCheckout,Settings,Store};
if(DB_NAME!=='krev_agent_sandbox' || $wpdb->prefix!=='krev_sandbox_')exit(1);
wp_set_current_user(0);
$vendor=wp_insert_user(['user_login'=>'listing-ui-vendor-'.Domain::id(),'user_pass'=>Domain::id(),'user_email'=>Domain::id().'@example.invalid','role'=>'seller','display_name'=>'Synthetic browser seller']);update_user_meta($vendor,'dokan_enable_selling','yes');
class ListingUiSetupGateway extends WC_Payment_Gateway{public function __construct(){$this->id='stripe';$this->enabled='yes';$this->settings=['testmode'=>'yes'];}public function is_available(){return true;}}
add_filter('woocommerce_payment_gateways',static fn($g)=>[ListingUiSetupGateway::class],1000);WC()->payment_gateways()->init();
$p=new WC_Product_Simple();$p->set_name('Synthetic listing browser test');$p->set_regular_price('12');$p->set_price('12');$p->set_status('publish');$p->set_virtual(true);$p->set_manage_stock(true);$p->set_stock_quantity(10);$p->save();wp_update_post(['ID'=>$p->get_id(),'post_author'=>$vendor]);
if(taxonomy_exists('kr_return_policy')){$term=wp_insert_term('Synthetic browser return policy '.Domain::id(),'kr_return_policy',['description'=>'Synthetic buyer return policy displayed for review.']);wp_set_object_terms($p->get_id(),[$term['term_id']],'kr_return_policy');update_term_meta($term['term_id'],'_kr_badge_label','Synthetic browser returns');}
update_option('pisol_cefw_payment_gateway_charges',[]);
update_option('krev_agent_settings',Settings::validate(['listing_handoff_enabled'=>true,'listing_pricing_verified'=>true,'listing_gateway_ids'=>['stripe'],'listing_policy_url'=>'https://kniferevive.com/terms-and-conditions/','listing_policy_version'=>'ui-native-1','return_policy_url'=>'https://kniferevive.com/return-policy/']),false);
update_option('woocommerce_calc_taxes','no');update_option('permalink_structure','');
$page=wp_insert_post(['post_title'=>'Synthetic native checkout','post_content'=>'[woocommerce_checkout]','post_type'=>'page','post_status'=>'publish']);update_option('woocommerce_checkout_page_id',$page);
$owner=Domain::id();Store::put($owner,'session','synthetic-ui',time()+7200,['token_hash'=>hash('sha256',Domain::token($owner))]);
$intent=ListingCheckout::create(['scope'=>'goods','items'=>[['product_id'=>$p->get_id(),'quantity'=>2]]],$owner,'listing-ui-'.Domain::id());
file_put_contents(dirname(__DIR__).'/.runtime/listing-ui-fixture.json',json_encode(ListingCheckout::response($intent,$owner)));
echo "Synthetic listing UI fixture ready.\n";
