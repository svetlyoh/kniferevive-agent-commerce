<?php
require __DIR__.'/next-find-bootstrap.php';
use KnifeRevive\CampaignLanding\{Campaign,Admin};
$checks=[];$ids=[];$term_ids=[];
function nf_check($name,$pass){global $checks;$checks[]=['check'=>$name,'pass'=>(bool)$pass];if(!$pass)throw new RuntimeException($name);}
foreach(get_posts(['post_type'=>['page','product'],'post_status'=>array_keys(get_post_stati()),'numberposts'=>-1]) as $old)wp_delete_post($old->ID,true); // Disposable krcl_ fixtures only; bootstrap asserts database/prefix.
delete_option(Campaign::OPTION);nf_check('No measurement enabled by default',Campaign::config()['measurement']===false);
nf_check('Attribution preserves the four standard fields',count(Campaign::attribution(['utm_source'=>'meta','utm_medium'=>'paid_social','utm_campaign'=>'next_find_2026','utm_content'=>'dispatch']))===4);
nf_check('Malformed, array, email and oversized UTMs discarded',Campaign::attribution(['utm_source'=>['x'],'utm_medium'=>'<script>','utm_campaign'=>str_repeat('x',97),'utm_content'=>'a@example.com','redirect'=>'https://evil.invalid'])===[]);
$_GET=['utm_source'=>'meta','utm_medium'=>'paid_social','utm_campaign'=>'next_find_2026','utm_content'=>'dispatch','token'=>'private-token'];
nf_check('Safe link carries campaign without arbitrary query',str_contains(Campaign::url('/sell/'),'utm_content=dispatch')&&!str_contains(Campaign::url('/sell/'),'private-token'));
nf_check('Protocol-relative redirect cannot become a destination',wp_parse_url(Campaign::url('//evil.invalid'),PHP_URL_HOST)==='kniferevive.com');
nf_check('Legal links do not carry query values',!str_contains(Campaign::url('/help/privacy-policy/',false),'utm_'));
$campaign_page_id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Your next find. Your first sale.','post_name'=>'your-next-find','post_content'=>file_get_contents(dirname(__DIR__).'/docs/next-find-page-content.html')]);$ids[]=$campaign_page_id;
update_option(Campaign::OPTION,['page_id'=>$campaign_page_id,'products'=>[],'measurement'=>false]);
$GLOBALS['wp_query']=new WP_Query(['page_id'=>$campaign_page_id]);$GLOBALS['post']=get_post($campaign_page_id);setup_postdata($GLOBALS['post']);
nf_check('Native published Page is campaign-active',Campaign::active());
foreach(Campaign::slots() as $key=>$slot){
 $id=wp_insert_post(['post_type'=>'product','post_status'=>'publish','post_name'=>$slot['slug'],'post_title'=>['wusthof'=>'Wüsthof Gourmet Chef’s Knife','fire-tv'=>'Amazon Fire TV Stick 4K Select with Alexa Voice Remote','cutco'=>'Cutco Petite Chef Knife'][$key]]);$ids[]=$id;
 wp_set_object_terms($id,'simple','product_type');$p=wc_get_product($id);$p->set_regular_price('20');$p->set_stock_status('instock');$p->save();
 nf_check($key.' resolves by verified slug',Campaign::product_id($key)===$id);nf_check($key.' live public WooCommerce card',Campaign::product($key)['available']);
}
foreach(['chefs-knife','technology','books','art','other_finds'] as $slug){$term=get_term_by('slug',$slug,'product_cat');if(!$term){$t=wp_insert_term($slug,'product_cat',['slug'=>$slug]);$term=get_term($t['term_id'],'product_cat');$term_ids[]=$term->term_id;}wp_set_object_terms($ids[1],$slug,'product_cat',true);}
$html=do_shortcode('[kniferevive_next_find]');
nf_check('Exactly one H1',substr_count($html,'<h1 ')===1);nf_check('Both deep links exist',str_contains($html,'id="discover"')&&str_contains($html,'id="dispatch"'));
nf_check('All three actual photo sources in the intended order',strpos($html,'photo-card-wusthof')<strpos($html,'photo-card-fire-tv')&&strpos($html,'photo-card-fire-tv')<strpos($html,'photo-card-cutco'));
nf_check('No hardcoded price or fee promotion',!preg_match('/\$[0-9]|0%|3%|1\.5%/',$html));
nf_check('Dispatch explicitly qualified and conceptual',str_contains($html,'Feature in development')&&str_contains($html,'Conceptual illustration')&&str_contains($html,'Live handoffs are not available'));
nf_check('No account modal or purchase form',!str_contains($html,'<form')&&!str_contains($html,'add-to-cart='));
nf_check('Canonical privacy route',str_contains($html,'/help/privacy-policy/'));nf_check('Seven accessible FAQ disclosures',substr_count($html,'<details>')===7);
nf_check('Read-only page adds no REST endpoint',!str_contains(file_get_contents(dirname(__DIR__).'/wordpress/kniferevive-campaign-landing/includes/Campaign.php'),'register_rest_route'));
nf_check('Master page uses registered shortcode block',parse_blocks(file_get_contents(dirname(__DIR__).'/docs/next-find-page-content.html'))[0]['blockName']==='core/shortcode');
nf_check('Dedicated native template preserves header and footer',substr_count(file_get_contents(dirname(__DIR__).'/theme-overlays/kniferevive/templates/page-your-next-find.html'),'wp:template-part')===2);
nf_check('Canonical strips campaign query values',apply_filters('get_canonical_url','https://kniferevive.com/your-next-find/?utm_source=meta')==='https://kniferevive.com/your-next-find/');
nf_check('Campaign title is scoped through native document filter',apply_filters('pre_get_document_title','old')===Campaign::TITLE);
nf_check('Existing Jetpack gets one campaign description value',apply_filters('jetpack_seo_meta_tags',['description'=>'old'])===['description'=>Campaign::DESCRIPTION]);
$og=Campaign::jetpack_og([]);nf_check('OG uses the approved real-product creative',str_contains($og['og:image'][0],'og-marketplace-1080.webp')&&$og['og:url']==='https://kniferevive.com/your-next-find/');
wp_remove_object_terms($ids[1],'art','product_cat');$empty_art=array_values(array_filter(Campaign::categories(),static fn($c)=>$c['icon']==='art'))[0];nf_check('Empty category routes to current explorer',!$empty_art['live']&&str_contains($empty_art['url'],'/categories/'));wp_set_object_terms($ids[1],'art','product_cat',true);
$purged=[];add_action('kr_campaign_landing_cache_purged',static function($id)use(&$purged){$purged[]=$id;});Campaign::purge_page();nf_check('Landing cache invalidation targets only its Page',$purged===[$campaign_page_id]);
$id=Campaign::product_id('wusthof');$p=wc_get_product($id);
foreach(['draft','private','pending','future','trash'] as $status){$p->set_status($status);$p->save();if($status==='future')wp_update_post(['ID'=>$id,'post_status'=>'future','post_date'=>wp_date('Y-m-d H:i:s',time()+86400),'post_date_gmt'=>gmdate('Y-m-d H:i:s',time()+86400)]);nf_check('No link to '.$status.' product (actual: '.get_post_status($id).', reason: '.Campaign::product('wusthof')['reason'].')',!Campaign::product('wusthof')['available']&&str_contains(Campaign::product('wusthof')['url'],'/product-category/chefs-knife/'));}
$p->set_status('publish');$p->save();wp_update_post(['ID'=>$id,'post_date'=>current_time('mysql')]);$p=wc_get_product($id);
$p->set_stock_status('outofstock');$p->save();nf_check('Sold out gets Explore similar',Campaign::product('wusthof')['reason']==='out_of_stock'&&str_contains(Campaign::render(),'Explore similar'));
$p->set_stock_status('instock');$p->set_catalog_visibility('hidden');$p->save();nf_check('Hidden product never advertised',!Campaign::product('wusthof')['available']);
$p->set_catalog_visibility('visible');$p->save();wp_update_post(['ID'=>$id,'post_password'=>'private']);nf_check('Password protected never advertised',!Campaign::product('wusthof')['available']);wp_update_post(['ID'=>$id,'post_password'=>'']);
$name=wp_strip_all_tags('Escaped “find” & café');$p=wc_get_product($id);$p->set_name($name);$p->save();nf_check('Unicode titles escaped in rendered content',str_contains(Campaign::render(),'Escaped “find” &amp; café'));$p->set_name('Wüsthof Gourmet Chef’s Knife');$p->save();
$config=Campaign::config();$config['products']['wusthof']=PHP_INT_MAX;update_option(Campaign::OPTION,$config,false);nf_check('Removed product uses fallback',Campaign::product('wusthof')['reason']==='product_missing');$config['products']=[];update_option(Campaign::OPTION,$config,false);
nf_check('Settings reject wrong photographed product',is_wp_error(Admin::sanitize(['products'=>['wusthof'=>Campaign::product_id('cutco')]])));
nf_check('Settings reject invalid page type',is_wp_error(Admin::sanitize(['page_id'=>$id])));
wp_update_post(['ID'=>$id,'post_name'=>Campaign::slots()['wusthof']['slug'],'post_status'=>'publish','post_date'=>current_time('mysql'),'post_date_gmt'=>current_time('mysql',true)]);
$valid_settings=Admin::sanitize(['page_id'=>$campaign_page_id,'products'=>['wusthof'=>$id]]);
nf_check('Settings accept approved products without enabling analytics'.(is_wp_error($valid_settings)?': '.$valid_settings->get_error_message():''),!is_wp_error($valid_settings)&&!Admin::sanitize(['page_id'=>$campaign_page_id])['measurement']);
update_option(Campaign::OPTION,$valid_settings,false);wp_update_post(['ID'=>$id,'post_name'=>'renamed-same-photographed-unit']);nf_check('Saved product ID survives a permalink slug change',Campaign::product('wusthof')['available']);nf_check('Owner can retain a previously verified binding after rename',!is_wp_error(Admin::sanitize(['page_id'=>$campaign_page_id,'products'=>['wusthof'=>$id]])));wp_update_post(['ID'=>$id,'post_name'=>Campaign::slots()['wusthof']['slug']]);$valid_settings['products']=[];update_option(Campaign::OPTION,$valid_settings,false);
Campaign::assets();nf_check('Campaign CSS enqueued on its page',wp_style_is('kr-campaign-landing','enqueued'));nf_check('No JS added by default',!wp_script_is('kr-campaign-landing','enqueued'));
$valid_settings['measurement']=true;update_option(Campaign::OPTION,$valid_settings,false);Campaign::assets();nf_check('Missing official readiness integration blocks optional metrics',!wp_script_is('kr-campaign-landing','enqueued'));$valid_settings['measurement']=false;update_option(Campaign::OPTION,$valid_settings,false);
$GLOBALS['wp_query']=new WP_Query(['pagename'=>'unrelated-page']);wp_dequeue_style('kr-campaign-landing');Campaign::assets();nf_check('No CSS or content on unrelated page',!wp_style_is('kr-campaign-landing','enqueued')&&Campaign::render()==='');
$GLOBALS['wp_query']=new WP_Query(['page_id'=>$campaign_page_id]);$GLOBALS['post']=get_post($campaign_page_id);
foreach(['guest','owner'] as $mode){wp_set_current_user($mode==='guest'?0:1);nf_check($mode.' can browse same campaign',Campaign::render()!=='');}
$fixture=dirname(__DIR__).'/.runtime/next-find-preview';wp_mkdir_p($fixture);file_put_contents($fixture.'/fragment.html',Campaign::render());
file_put_contents($fixture.'/fragment-sold-out.html',str_replace('View item<span','Explore similar<span',Campaign::render()));
wp_mkdir_p(dirname(__DIR__).'/output/next-find');file_put_contents(dirname(__DIR__).'/output/next-find/php-results.json',wp_json_encode($checks,JSON_PRETTY_PRINT));
foreach($ids as $id)wp_delete_post($id,true);foreach($term_ids as $id)wp_delete_term($id,'product_cat');
echo count($checks)." native integration checks passed; no external HTTP or mail.\n";
