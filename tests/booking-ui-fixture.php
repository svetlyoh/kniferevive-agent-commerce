<?php
/** Disposable fixtures only; no production wp-config.php or provider calls. */
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Domain,Settings};
if(DB_NAME!=='krev_agent_sandbox' || $wpdb->prefix!=='krev_sandbox_')throw new RuntimeException('Sandbox fence failed.');
wp_set_current_user(0);
$vendor=wp_insert_user(['user_login'=>'booking-ui-'.Domain::id(),'user_pass'=>Domain::id(),'user_email'=>Domain::id().'@example.invalid','role'=>'seller']);update_user_meta($vendor,'dokan_enable_selling','yes');
$term=get_term_by('slug','knife-sharpening','product_cat');
if(!$term){wp_insert_term('Sharpening','product_cat',['slug'=>'knife-sharpening']);$term=get_term_by('slug','knife-sharpening','product_cat');}
$p=new WC_Product_Simple();$p->set_name('Large Knife Sharpening');$p->set_status('publish');$p->set_regular_price('7');$p->set_price('7');$p->set_virtual(true);$p->set_category_ids([$term->term_id]);$p->save();wp_update_post(['ID'=>$p->get_id(),'post_author'=>$vendor]);
update_option('permalink_structure','');
update_option('krev_agent_settings',Settings::validate(['booking_enabled'=>true,'booking_location'=>'304 Kapalua Bay Cir, Pittsburg, CA','booking_phone'=>'+1 (415) 483-2814',
    'booking_prepaid_enabled'=>true,'booking_wallet_enabled'=>true,'booking_services'=>[['product_id'=>$p->get_id(),'definition'=>'An 8-inch chef knife qualifies as Large Knife Sharpening.']],
    'booking_weekly_hours'=>[['weekday'=>5,'open'=>'09:00','close'=>'19:00'],['weekday'=>6,'open'=>'09:00','close'=>'19:00'],['weekday'=>7,'open'=>'10:00','close'=>'16:00']]]),false);
echo 'Synthetic booking UI fixture ready; all payments unavailable. Product '.$p->get_id()."\n";
