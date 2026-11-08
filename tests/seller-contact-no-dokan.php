<?php
define('ABSPATH',__DIR__);
function get_option($name,$fallback=[]){return ['enabled'=>true];}
require dirname(__DIR__).'/wordpress/kniferevive-seller-contact/includes/Config.php';
require dirname(__DIR__).'/wordpress/kniferevive-seller-contact/includes/Resolver.php';
$r=\KnifeRevive\SellerContact\Resolver::for_seller(1);
if($r['eligible']||$r['reason']!=='dokan_unavailable')throw new RuntimeException('Missing Dokan must fail closed.');
echo "Missing-Dokan dependency check passed.\n";
