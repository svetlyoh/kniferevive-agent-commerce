<?php
// Real WooCommerce/Dokan/WordPress on isolated krchat_ tables; no production config or mail.
$GLOBALS['krc_statistics_consent']=false;
function wp_has_consent($category){return $category==='statistics'&&!empty($GLOBALS['krc_statistics_consent']);}
require __DIR__.'/seller-contact-bootstrap.php';
use KnifeRevive\SellerContact\{Config,Resolver,Renderer,Plugin,Settings,Analytics};
$out=[];
function chat_check(string $name,bool $pass): void {global $out;$out[]=['check'=>$name,'pass'=>$pass];if(!$pass)throw new RuntimeException($name);}
function chat_product(int $author,string $type='simple',string $status='publish'): WC_Product {
 $id=wp_insert_post(['post_type'=>'product','post_title'=>'Unicode “find” <script>bad</script> & café','post_author'=>$author,'post_status'=>$status]);
 wp_set_object_terms($id,$type,'product_type');$p=wc_get_product($id);$p->set_price('24.99');$p->set_regular_price('24.99');$p->set_catalog_visibility('visible');$p->save();return $p;
}
function chat_request(string $event,array $extra=[]): WP_REST_Request {
 global $p,$seller;$r=new WP_REST_Request('POST','/kniferevive-seller-contact/v1/events');
 $r->set_header('origin',home_url('/'));$r->set_body(wp_json_encode(array_merge(['event'=>$event,'placement'=>'detail','product_id'=>$p->get_id(),'seller_id'=>999999,'session'=>str_repeat('a',32),'channel'=>'','device'=>'desktop'],$extra)));$r->set_header('content-type','application/json');return $r;
}
$seller=wp_insert_user(['user_login'=>'chat-vendor-'.wp_generate_password(8,false),'user_pass'=>wp_generate_password(36),'role'=>'seller','display_name'=>'Fixture Vendor']);
$other=wp_insert_user(['user_login'=>'chat-other-'.wp_generate_password(8,false),'user_pass'=>wp_generate_password(36),'role'=>'seller']);
foreach([$seller,$other] as $uid){update_user_meta($uid,'dokan_enable_selling','yes');update_user_meta($uid,'dokan_profile_settings',['store_name'=>$uid===$seller?'Fixture Store':'Other Store']);}
$p=chat_product($seller);$items=[$p->get_id()];$initial=[Resolver::WA_ENABLED=>'1',Resolver::WA_NUMBER=>'15551234567',Resolver::TG_ENABLED=>'1',Resolver::TG_MODE=>'personal',Resolver::TG_USER=>'FixtureSeller',Resolver::TG_BOT=>'FixtureSellerBot'];
foreach($initial as $k=>$v)update_user_meta($seller,$k,$v);
delete_option(Config::OPTION);chat_check('New release defaults to master OFF',!Config::on());
global $wpdb;foreach(['counts','seen'] as $t)$wpdb->query('DELETE FROM '.Analytics::table($t));
Plugin::activate();foreach($initial as $k=>$v)chat_check('Activation preserves '.$k,get_user_meta($seller,$k,true)===$v);
update_option(Config::OPTION,array_merge(Config::defaults(),['enabled'=>true]));
chat_check('Active seller and both channels resolve',count(Resolver::for_product($p)['channels'])===2);
chat_check('Dokan store identity used',Resolver::for_product($p)['store_name']==='Fixture Store');
foreach([['1','0',1],['0','1',1],['0','0',0],['1','1',2]] as [$wa,$tg,$count]){
 update_user_meta($seller,Resolver::WA_ENABLED,$wa);update_user_meta($seller,Resolver::TG_ENABLED,$tg);chat_check('Opt-in combination '.$wa.$tg,count(Resolver::for_product($p)['channels'])===$count);
}
update_user_meta($seller,Resolver::WA_NUMBER,'123');chat_check('Malformed phone excluded',!isset(Resolver::for_product($p)['channels']['whatsapp']));update_user_meta($seller,Resolver::WA_NUMBER,$initial[Resolver::WA_NUMBER]);
chat_check('Number sanitizer never adds a country code',Resolver::number('4152999611')==='4152999611');
foreach(['12345','https://evil.invalid/user','Fixture?text=x','share/url','admin@example.invalid'] as $bad)chat_check('Unsafe Telegram destination rejected',Resolver::username($bad)==='');
chat_check('Formatted international digits accepted',Resolver::number('+1 (555) 123-4567')==='15551234567');
update_user_meta($seller,'dokan_enable_selling','no');chat_check('Disabled seller has no public contact',!Resolver::for_product($p)['eligible']);update_user_meta($seller,'dokan_enable_selling','yes');
foreach(['draft','private','pending','trash'] as $status){$p->set_status($status);$p->save();chat_check('Non-public product hidden: '.$status,!Resolver::for_product($p)['eligible']);}
$p->set_status('publish');$p->set_catalog_visibility('hidden');$p->save();chat_check('Catalog-hidden product suppressed',!Resolver::for_product($p)['eligible']);$p->set_catalog_visibility('visible');$p->set_stock_status('outofstock');$p->save();chat_check('Public out-of-stock item remains contactable',Resolver::for_product($p)['eligible']);
foreach(['simple','variable','external','grouped'] as $type){$t=chat_product($seller,$type);$items[]=$t->get_id();chat_check('Supported type '.$type,Resolver::for_product($t)['eligible']);}
$parent=new WC_Product_Variable();$parent->set_name('Parent fixture');$parent->set_status('publish');$parent->save();wp_update_post(['ID'=>$parent->get_id(),'post_author'=>$seller]);$items[]=$parent->get_id();
$v=new WC_Product_Variation();$v->set_parent_id($parent->get_id());$v->set_regular_price('5');$v->save();wp_update_post(['ID'=>$v->get_id(),'post_author'=>$other]);$items[]=$v->get_id();chat_check('Variation uses parent seller, not variation author',Resolver::for_product($v)['seller_id']===$seller);
wp_update_post(['ID'=>$p->get_id(),'post_author'=>$other]);chat_check('Ownership changes resolve fresh without prior seller leakage',!Resolver::for_product($p)['eligible']);wp_update_post(['ID'=>$p->get_id(),'post_author'=>$seller]);
$admin=wp_insert_user(['user_login'=>'chat-admin-'.wp_generate_password(8,false),'user_pass'=>wp_generate_password(36),'role'=>'administrator']);$a=new WP_User($admin);$a->remove_cap('dokandar');
$ap=chat_product($admin);$items[]=$ap->get_id();chat_check('Administrator ownership alone is not a seller',!Resolver::for_product($ap)['eligible']);$a->add_cap('dokandar');update_user_meta($admin,'dokan_enable_selling','yes');foreach($initial as $k=>$val)update_user_meta($admin,$k,$val);clean_user_cache($admin);wp_cache_delete($admin,'user_meta');chat_check('Administrator explicitly enabled as a Dokan seller can resolve · '.Resolver::for_product($ap)['reason'],Resolver::for_product($ap)['eligible']);
$d=Resolver::for_product($p);chat_check('WhatsApp context contains canonical product URL',str_contains(rawurldecode($d['channels']['whatsapp']['url']),get_permalink($p->get_id())));chat_check('Personal Telegram has no false prefill',!str_contains($d['channels']['telegram']['url'],'?'));
update_user_meta($seller,Resolver::TG_MODE,'bot');chat_check('Existing seller bot convention preserved',str_ends_with(Resolver::for_product($p)['channels']['telegram']['url'],'?start=krp_'.$p->get_id()));update_user_meta($seller,Resolver::TG_MODE,'personal');
wp_set_current_user(0);$guest=Renderer::product($p->get_id(),'card');wp_set_current_user($seller);chat_check('Owner and guest have consistent contact placement',$guest!==''&&Renderer::product($p->get_id(),'card')!=='');
chat_check('Single compact card trigger for two channels',substr_count($guest,'class="krc-trigger"')===1);chat_check('Unsafe Unicode title escaped',!str_contains($guest,'<script>bad</script>'));chat_check('External links use safe attributes',substr_count($guest,'rel="noopener noreferrer"')===2);
$store=Renderer::store($seller);chat_check('Storefront question is generic, no unrelated product URL',str_contains($store,'Contact seller')&&!str_contains($store,get_permalink($p->get_id())));
chat_check('Published seller with no listings can resolve',Resolver::for_seller($admin)['eligible']);
chat_check('Detail rendered once across two hook paths',Renderer::detail($p->get_id())!==''&&Renderer::detail($p->get_id())==='');
$config=Config::get();$config['seller_allowlist']=[$other];update_option(Config::OPTION,$config);chat_check('Pilot does not expose out-of-cohort sellers',!Resolver::for_product($p)['eligible']);$config['seller_allowlist']=[];update_option(Config::OPTION,$config);
$raw=['krc_whatsapp_enabled'=>'1','krc_whatsapp_number'=>'+1 555 123 4567','krc_telegram_enabled'=>'1','krc_telegram_mode'=>'personal','krc_telegram_username'=>'@FixtureSeller','krc_telegram_bot'=>'FixtureSellerBot'];
chat_check('Forged save nonce rejected',is_wp_error(Settings::save($seller,$raw,'not-valid')));chat_check('Other seller settings cannot be edited',is_wp_error(Settings::save($other,$raw,wp_create_nonce('krc_contact_save'))));
chat_check('Valid owner settings save',Settings::save($seller,$raw,wp_create_nonce('krc_contact_save'))===true);
$raw['krc_whatsapp_number']='https://evil.invalid';chat_check('Invalid settings rejected atomically',is_wp_error(Settings::save($seller,$raw,wp_create_nonce('krc_contact_save')))&&get_user_meta($seller,Resolver::WA_NUMBER,true)==='15551234567');
chat_check('Analytics default off',!Analytics::receive(chat_request('seller_contact_cta_click'))->get_data()['accepted']);
$config=Config::get();$config['analytics']=true;update_option(Config::OPTION,$config);
$_SERVER['HTTP_USER_AGENT']='KnifeRevive QA Chrome';$_SERVER['REMOTE_ADDR']='127.0.0.1';
chat_check('Unknown statistics consent blocks metrics',!Analytics::receive(chat_request('seller_contact_cta_click'))->get_data()['accepted']);$GLOBALS['krc_statistics_consent']=true;
$_SERVER['HTTP_SEC_GPC']='1';chat_check('GPC overrides existing statistics consent',!Analytics::receive(chat_request('seller_contact_cta_click'))->get_data()['accepted']);unset($_SERVER['HTTP_SEC_GPC']);
chat_check('Consented event accepted',Analytics::receive(chat_request('seller_contact_cta_click'))->get_data()['accepted']);chat_check('Duplicate session/product event ignored',!Analytics::receive(chat_request('seller_contact_cta_click'))->get_data()['accepted']);
$bad=chat_request('seller_contact_cta_click',['session'=>'unsafe']);chat_check('Malformed session rejected',!Analytics::receive($bad)->get_data()['accepted']);$bad=chat_request('seller_contact_cta_click');$bad->set_header('origin','https://evil.invalid');chat_check('Foreign origin rejected',!Analytics::receive($bad)->get_data()['accepted']);
chat_check('Unknown event cannot record messages sent',!Analytics::receive(chat_request('messages_sent'))->get_data()['accepted']);
Analytics::receive(chat_request('seller_contact_channel_click',['channel'=>'whatsapp']));$rows=Analytics::report();chat_check('Aggregate report contains adopted events',count($rows)>0);
global $wpdb;$data=$wpdb->get_results('SELECT * FROM '.Analytics::table('counts'),ARRAY_A);$serialized=wp_json_encode($data);chat_check('No phone, handle, IP, question, cookie or raw token in stored counts',!str_contains($serialized,'15551234567')&&!str_contains($serialized,'FixtureSeller')&&!str_contains($serialized,'127.0.0.1')&&!str_contains($serialized,str_repeat('a',32)));
$seen=$wpdb->get_results('SELECT * FROM '.Analytics::table('seen'),ARRAY_A);chat_check('Dedupe storage holds HMAC only',isset($seen[0]['event_key'])&&strlen($seen[0]['event_key'])===64&&!str_contains(wp_json_encode($seen),str_repeat('a',32)));

chat_check('Malformed enabled toggle rejected',is_wp_error(Settings::validate(['krc_whatsapp_enabled'=>['1']])));
$bad=chat_request('seller_contact_cta_click',['product_id'=>['invalid']]);chat_check('Array product ID rejected without coercion',!Analytics::receive($bad)->get_data()['accepted']);
$bad=chat_request('seller_contact_cta_click');$bad->set_header('origin','http://127.0.0.1:9999');chat_check('Foreign same-host port rejected',!Analytics::receive($bad)->get_data()['accepted']);
$wpdb->query($wpdb->prepare('INSERT INTO '.Analytics::table('seen').' (event_key,day) VALUES (%s,%s)',str_repeat('f',64),'2001-01-01'));Analytics::prune();chat_check('Seven-day retention deletes old HMAC keys',(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.Analytics::table('seen').' WHERE day=%s','2001-01-01'))===0);
$pub=chat_product($seller);$items[]=$pub->get_id();Analytics::receive(chat_request('seller_contact_cta_click',['product_id'=>$pub->get_id()]));$count=(int)$wpdb->get_var("SELECT n FROM ".Analytics::table('counts')." WHERE event='seller_contact_cta_click' AND product_id=0 AND seller_id=0 AND channel=''");chat_check('Cross-product session denominator counted once',$count===1);
foreach(['KR_WhatsApp_Product_Button'=>20,'KR_Telegram_Product_Icon'=>30] as $cls=>$priority){add_filter('render_block',[$cls,'add_icon_to_product_price_block'],$priority,3);add_action('woocommerce_single_product_summary',[$cls,'render_single_price_row'],10);}
Plugin::replace_legacy();chat_check('Legacy WhatsApp block renderer removed',has_filter('render_block',['KR_WhatsApp_Product_Button','add_icon_to_product_price_block'])===false);chat_check('Legacy Telegram block renderer removed',has_filter('render_block',['KR_Telegram_Product_Icon','add_icon_to_product_price_block'])===false);
global $wp_query;$wp_query=new WP_Query(['post_type'=>'product','p'=>$p->get_id()]);$block=['blockName'=>'woocommerce/add-to-cart-form','context'=>['postId'=>$p->get_id()]];
$property=new ReflectionProperty(Renderer::class,'detail_done');$property->setValue(null,false);
$render=Plugin::block_detail('',$block);chat_check('Empty out-of-stock purchase block still receives contact',substr_count($render,'class="krc-trigger"')===1);chat_check('Second purchase hook cannot duplicate detail contact',Plugin::block_detail('',$block)==='');
chat_check('Main Product Price block has no competing primary contact',Plugin::block_price('<div>Price</div>', ['context'=>['postId'=>$p->get_id()]])==='<div>Price</div>');
chat_check('Related-product block receives exactly one compact contact',substr_count(Plugin::block_price('<div>Price</div>',['context'=>['postId'=>$pub->get_id()]]),'class="krc-trigger"')===1);

$menu=apply_filters('woocommerce_account_menu_items',['orders'=>'Orders','whatsapp-settings'=>'WhatsApp','telegram-settings'=>'Telegram']);chat_check('One unified private settings menu replaces legacy duplicate screens',isset($menu['seller-contact'])&&!isset($menu['whatsapp-settings'])&&!isset($menu['telegram-settings']));
chat_check('New settings endpoint registered with WooCommerce',isset(WC()->query->get_query_vars()['seller-contact']));
$invalidated=[];add_action('kr_seller_contact_settings_changed',static function($uid)use(&$invalidated){$invalidated[]=$uid;});Settings::invalidate($seller);chat_check('Cache invalidation exposes only seller ID to host integration',$invalidated===[$seller]);
// Public resolver sees opt-out immediately, no stale contact cache.
update_user_meta($seller,Resolver::WA_ENABLED,'0');update_user_meta($seller,Resolver::TG_ENABLED,'0');chat_check('Opt-out removes contact immediately',Renderer::product($p->get_id(),'card')==='');

// Reusable visual fixtures use real resolver/rendering, never production customer data.
foreach($initial as $k=>$val)update_user_meta($seller,$k,$val);
$fixture=dirname(__DIR__).'/.runtime/seller-contact-preview';wp_mkdir_p($fixture);
foreach(['both','whatsapp','telegram','neither'] as $kind){
 update_user_meta($seller,Resolver::WA_ENABLED,in_array($kind,['both','whatsapp'],true)?'1':'0');update_user_meta($seller,Resolver::TG_ENABLED,in_array($kind,['both','telegram'],true)?'1':'0');
 $content='<h1>Find a useful next chapter.</h1><p>Isolated fixture · no production messages or purchases.</p><section class="fixture-product"><div class="fixture-image" role="img" aria-label="Product photo placeholder"></div><div><h2>Micron1100 SSD · fixture</h2><p>$24.99 · public item, currently out of stock</p><button type="button" class="fixture-buy">Add to cart · fixture</button>'.Renderer::product($p->get_id(),'detail').'</div></section><h2>Product cards</h2><section class="fixture-cards"><article><div class="fixture-image"></div><h3>Fixture listing</h3><p>$24.99</p>'.Renderer::product($p->get_id(),'card').'</article></section><h2>Seller storefront</h2>'.Renderer::store($seller);
 ob_start();Renderer::dialog();$dialog=ob_get_clean();
 $html='<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Seller contact · '.$kind.' · local QA</title><link rel="stylesheet" href="contact.css"><style>body{margin:0;padding:20px;font-family:system-ui;color:#172033;background:#fffaf2}main{max-width:1100px;margin:auto}.fixture-product{display:grid;grid-template-columns:1fr 1fr;gap:24px}.fixture-image{aspect-ratio:4/3;background:#e2dacb;border-radius:12px}.fixture-buy{min-height:48px;padding:12px 20px;color:white;background:#172033;border:0;border-radius:8px;font:inherit}.fixture-cards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.fixture-cards article{padding:16px;border:1px solid #807465;border-radius:12px}h1,h2,h3{overflow-wrap:anywhere}@media(max-width:600px){body{padding:16px}.fixture-product{grid-template-columns:1fr}.fixture-cards{grid-template-columns:1fr}}</style><main>'.$content.'</main>'.$dialog.'<script>window.krcContact={analytics:false,events:"/no-collection"};</script><script src="contact.js"></script></html>';
 file_put_contents($fixture.'/'.$kind.'.html',$html);
}
file_put_contents($fixture.'/dark.html',str_replace('<main>','<body class="kr-tech-theme"><main>',str_replace('</style>','body.kr-tech-theme{--kr-text:#f5fbff;--kr-surface:#0b1521;--kr-muted:#b8c4d0;background:#000;color:#f5fbff}</style>',file_get_contents($fixture.'/both.html'))));
copy(dirname(__DIR__).'/wordpress/kniferevive-seller-contact/assets/contact.css',$fixture.'/contact.css');copy(dirname(__DIR__).'/wordpress/kniferevive-seller-contact/assets/contact.js',$fixture.'/contact.js');
wp_mkdir_p(dirname(__DIR__).'/output/seller-contact');file_put_contents(dirname(__DIR__).'/output/seller-contact/php-results.json',wp_json_encode($out,JSON_PRETTY_PRINT));
foreach($items as $pid)wp_delete_post($pid,true);require_once ABSPATH.'wp-admin/includes/user.php';foreach([$seller,$other,$admin] as $uid)wp_delete_user($uid);
echo count($out).' integration checks passed; mail/network blocked; fixture pages prepared.'.PHP_EOL;
