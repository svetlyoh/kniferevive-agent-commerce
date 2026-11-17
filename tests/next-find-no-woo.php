<?php
define('ABSPATH',__DIR__);define('OBJECT','OBJECT');
function home_url($path){return 'https://kniferevive.com'.$path;}
function wp_unslash($v){return $v;}
function add_query_arg($args,$url){return $url;}
require dirname(__DIR__).'/wordpress/kniferevive-campaign-landing/includes/Campaign.php';
$p=\KnifeRevive\CampaignLanding\Campaign::product('wusthof');
if($p['available']||$p['reason']!=='woocommerce_unavailable')throw new RuntimeException('WooCommerce missing fallback failed');
echo "Missing WooCommerce and Dokan: safe category fallback passed.\n";
