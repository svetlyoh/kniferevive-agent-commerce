<?php
/** Fenced, disposable UI fixtures. Native test gateway cannot process a payment. */
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Domain,Settings,Store,Booking};
if(DB_NAME!=='krev_agent_sandbox' || DB_HOST!=='127.0.0.1:11019')throw new RuntimeException('Sandbox fence failed');
wp_set_current_user(0);$wpdb->query('DELETE FROM '.Store::table('holds')." WHERE slot_id LIKE 'booking-%'");
$vendor=wp_insert_user(['user_login'=>'prepay-ui-'.Domain::id(),'user_pass'=>Domain::id(),'user_email'=>Domain::id().'@example.invalid','role'=>'seller']);update_user_meta($vendor,'dokan_enable_selling','yes');
$term=get_term_by('slug','knife-sharpening','product_cat');$services=[];
foreach([['Small Knife Sharpening','5'],['Large Knife Sharpening','7']] as [$name,$price]){$p=new WC_Product_Simple();$p->set_name($name);$p->set_status('publish');$p->set_regular_price($price);$p->set_price($price);$p->set_virtual(false);$p->set_category_ids([$term->term_id]);$p->save();wp_update_post(['ID'=>$p->get_id(),'post_author'=>$vendor]);$services[]=['product_id'=>$p->get_id(),'definition'=>'Synthetic UI service only. No real appointment or charge.'];}
$s=Settings::get();$s['booking_services']=$services;$s['booking_daily_capacity']=4;$s['booking_launch_approved']=true;$s['booking_pay_before_confirmation']=true;$s['booking_pickup_limit_enabled']=false;$s['booking_pickup_postal_codes']=[];$s['booking_policy_url']='https://kniferevive.com/sharpening-cancellations-and-refunds/';$s['booking_policy_version']='synthetic-only';$s['listing_gateway_ids']=['stripe'];update_option('krev_agent_settings',Settings::validate($s),false);
update_option('woocommerce_calc_taxes','no');update_option('pisol_cefw_payment_gateway_charges',[]);update_option('permalink_structure','');
$zone=new WC_Shipping_Zone();$zone->set_zone_name('Synthetic prepaid UI only');$zone->add_location('US:CA','state');$zone->save();$method=$zone->add_shipping_method('local_pickup');update_option('woocommerce_local_pickup_'.$method.'_settings',['enabled'=>'yes','cost'=>'0']);delete_transient('wc_shipping_method_count');
echo 'Synthetic prepaid UI ready; no real payment or email.\n';
