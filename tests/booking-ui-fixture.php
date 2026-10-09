<?php
/** Disposable fixtures only; no production wp-config.php or provider calls. */
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Domain,Settings,Store,Booking};
if(DB_NAME!=='krev_agent_sandbox' || $wpdb->prefix!=='krev_sandbox_')throw new RuntimeException('Sandbox fence failed.');
wp_set_current_user(0);
$vendor=wp_insert_user(['user_login'=>'booking-ui-'.Domain::id(),'user_pass'=>Domain::id(),'user_email'=>Domain::id().'@example.invalid','role'=>'seller']);update_user_meta($vendor,'dokan_enable_selling','yes');
$term=get_term_by('slug','knife-sharpening','product_cat');
if(!$term){wp_insert_term('Sharpening','product_cat',['slug'=>'knife-sharpening']);$term=get_term_by('slug','knife-sharpening','product_cat');}
$p=new WC_Product_Simple();$p->set_name('Large Knife Sharpening');$p->set_status('publish');$p->set_regular_price('7');$p->set_price('7');$p->set_virtual(true);$p->set_category_ids([$term->term_id]);$p->save();wp_update_post(['ID'=>$p->get_id(),'post_author'=>$vendor]);
update_option('permalink_structure','');
update_option('timezone_string','America/Los_Angeles');
update_option('krev_agent_settings',Settings::validate(['booking_enabled'=>true,'booking_location'=>'304 Kapalua Bay Cir, Pittsburg, CA','booking_phone'=>'+1 (415) 483-2814',
    'booking_prepaid_enabled'=>true,'booking_wallet_enabled'=>true,'booking_order_timing'=>'on_submit','booking_order_verified'=>true,'booking_offline_gateway_id'=>'cod','booking_daily_capacity'=>10,'booking_services'=>[['product_id'=>$p->get_id(),'definition'=>'An 8-inch chef knife qualifies as Large Knife Sharpening.']],
    'booking_weekly_hours'=>[['weekday'=>5,'open'=>'09:00','close'=>'19:00'],['weekday'=>6,'open'=>'09:00','close'=>'19:00'],['weekday'=>7,'open'=>'10:00','close'=>'16:00']]]),false);
update_option('woocommerce_cod_settings',['enabled'=>'yes','title'=>'Synthetic pay-at-drop-off'],false);update_option('woocommerce_calc_taxes','no');update_option('pisol_cefw_payment_gateway_charges',[]);
update_option('krev_ui_booking_vendor',$vendor,false);
switch_theme('kniferevive');
$uploads=wp_upload_dir();wp_mkdir_p($uploads['basedir']);$logo=$uploads['basedir'].'/Knife_Revive_Logo_OG_V2.jpg';
copy(ABSPATH.'wp-content/uploads/2024/08/Knife_Revive_Logo_OG_V2.jpg',$logo);
$attachment=wp_insert_attachment(['post_title'=>'Existing KnifeRevive site logo (synthetic attachment)','post_mime_type'=>'image/jpeg','post_status'=>'inherit'],$logo);
update_post_meta($attachment,'_wp_attached_file','Knife_Revive_Logo_OG_V2.jpg');set_theme_mod('custom_logo',$attachment);
$owner=Domain::id();Store::put($owner,'session','synthetic-ui',time()+7200,['token_hash'=>hash('sha256',Domain::token($owner))]);
$draft=Booking::create(['items'=>[['product_id'=>$p->get_id(),'quantity'=>1]],'mode'=>'pay_later_dropoff','preferred_date'=>Booking::availability()['days'][0]['date'],'postal_code'=>'94565'],$owner,'ui-referral-'.Domain::id());
file_put_contents(dirname(__DIR__).'/.runtime/booking-ui-referral.json',wp_json_encode(['url'=>Booking::response($draft)['review_url'],'booking'=>$draft['id'],'vendor'=>$vendor]));
echo 'Synthetic booking UI fixture ready; no gateway process_payment or external mail allowed. Product '.$p->get_id()."\n";
