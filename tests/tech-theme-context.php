<?php
/** Route-level contract for the shared Tech palette, without a live DB. */
define('ABSPATH', __DIR__);
class WP_Post { public $ID=20; public $post_name=''; public $post_content=''; }
$context=[]; $hooks=[];
function add_filter($name,$callback,$priority=10) { global $hooks; $hooks[$name]=$callback; }
function add_action($name,$callback,$priority=10) { global $hooks; $hooks[$name]=$callback; }
function is_admin() { global $context; return $context['admin']??false; }
function is_page($slug=null) { global $context; return $slug===null ? isset($context['page']) : ($context['page']??'')===$slug; }
function is_product() { global $context; return $context['product']??false; }
function is_product_category() { global $context; return $context['category']??false; }
function get_queried_object_id() { return 20; }
function get_queried_object() { global $context; return $context['object']??new WP_Post; }
function kr_is_technology_product($id) { global $context; return $context['under_tech']??false; }
function kr_is_technology_term($term) { return in_array($term,['technology','tech-grandchild'],true); }
function get_term_by($field,$value,$taxonomy) { return $value; }
function get_query_var($key) { global $context; return $context[$key]??''; }
function kr_metacategories() { return ['iphones'=>['slugs'=>['technology']], 'future-tech-meta'=>['slugs'=>['tech-grandchild']], 'collectibles'=>['slugs'=>['collectibles']]]; }
function has_shortcode($content,$tag) { return strpos($content,'['.$tag.']')!==false; }
function get_post_ancestors($page) { global $context; return $context['ancestors']??[]; }
function get_post($id) { $p=new WP_Post; $p->post_name=$id===100?'technology-trade-desk':'other'; return $p; }
function is_tax($taxonomy,$slug) { global $context; return ($context['brand']??'')===$slug; }
function get_stylesheet_directory_uri() { return 'https://example.test/theme'; }
function kr_asset_version($path) { return 'test'; }
function wp_enqueue_style(...$args) { global $styles; $styles[]=$args; }
require __DIR__.'/../theme-overlays/kniferevive/inc/tech-theme.php';
$shortcodePage=new WP_Post; $shortcodePage->post_content='[kniferevive_technology_trade_desk]';
$cases=[
 'Tech landing'=>[['page'=>'technology-trade-desk'],true],
 'Tech descendant product'=>[['product'=>true,'under_tech'=>true],true],
 'Non-Tech product'=>[['product'=>true,'under_tech'=>false],false],
 'Nested Tech category'=>[['category'=>true,'object'=>'tech-grandchild'],true],
 'Non-Tech category'=>[['category'=>true,'object'=>'collectibles'],false],
 'Tech meta with Google filter'=>[['kr_collection'=>'iphones','kr_google_category'=>'267'],true],
 'Future Tech meta'=>[['kr_collection'=>'future-tech-meta'],true],
 'Non-Tech meta'=>[['kr_collection'=>'collectibles'],false],
 'Descendant Tech page'=>[['page'=>'future-child','ancestors'=>[99,100]],true],
 'Standalone Tech shortcode page'=>[['page'=>'custom-tech','object'=>$shortcodePage],true],
 'General directory'=>[['page'=>'categories'],false],
 'Legacy Dell archive'=>[['brand'=>'dell'],true],
 'Admin screen'=>[['admin'=>true,'page'=>'technology-trade-desk'],false],
];
$checks=0;
foreach($cases as $label=>[$context,$expected]) {
 if(kr_is_tech_context()!==$expected) throw new Exception($label);
 $classes=$hooks['body_class'](['existing']);
 if(in_array('kr-tech-theme',$classes,true)!==$expected) throw new Exception($label.' body');
 $styles=[]; $hooks['wp_enqueue_scripts']();
 if(count($styles)!==($expected?1:0)) throw new Exception($label.' stylesheet');
 if($expected && !str_ends_with($styles[0][1],'/assets/css/tech-theme.css')) throw new Exception($label.' canonical path');
 $checks+=3;
}
echo "$checks Tech route/class/enqueue checks passed\n";
