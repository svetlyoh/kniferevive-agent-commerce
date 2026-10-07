<?php
// Loopback-only synthetic sandbox; never serves the user's normal WordPress configuration.
if (PHP_SAPI!=='cli-server' || !in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)) { http_response_code(403); exit; }
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$assetMap=['storefront.js'=>'text/javascript','storefront.css'=>'text/css'];
foreach ($assetMap as $name=>$type) if ($path==='/wp-content/plugins/kniferevive-agent-commerce/assets/'.$name) {
    header('Content-Type: '.$type); readfile(dirname(__DIR__).'/wordpress/kniferevive-agent-commerce/assets/'.$name); exit;
}
$argv=[__FILE__,getenv('KREV_TEST_WP_ROOT')];
require __DIR__.'/sandbox-bootstrap.php';
add_filter('plugins_url',static function ($url,$relative,$plugin) { return str_ends_with($plugin,'kniferevive-agent-commerce.php') ? WP_HOME.'/wp-content/plugins/kniferevive-agent-commerce'.($relative?'/'.$relative:'') : $url; },10,3);
add_filter('pre_http_request',static function ($pre,$args,$url) {
    if (!str_starts_with($url,'https://api.stripe.com/')) return $pre;
    if ($args['method']==='POST') {
        parse_str($args['body'],$body); $id=$body['metadata']['attempt_id'];
        update_option('krev_ui_provider_'.$id,$body,false);
    } else {
        $a=\KnifeRevive\AgentCommerce\Store::backlog(100); $body=null;
        foreach ($a as $row) if (str_contains($url,'cs_test_'.$row['id'])) $body=get_option('krev_ui_provider_'.$row['id']);
    }
    if (!$body) return new WP_Error('fixture_missing','Synthetic processor fixture missing.');
    $sum=0; foreach ($body['line_items'] as $line) $sum+=(int)$line['price_data']['unit_amount'];
    $id=$body['metadata']['attempt_id'];
    return ['headers'=>[],'body'=>json_encode(['id'=>'cs_test_'.$id,'metadata'=>$body['metadata'],'currency'=>'usd','amount_total'=>$sum,'livemode'=>false,'mode'=>'payment',
        'url'=>'https://checkout.stripe.com/c/pay/cs_test_'.$id,'payment_status'=>'unpaid','status'=>'open','payment_intent'=>null]),'response'=>['code'=>200,'message'=>'OK'],'cookies'=>[]];
},10,3);
if (isset($_GET['rest_route']) && str_starts_with($_GET['rest_route'],'/kniferevive-agent/v1/')) { rest_get_server()->serve_request($_GET['rest_route']); exit; }
if (str_starts_with($path,'/wp-json/kniferevive-agent/v1/')) { rest_get_server()->serve_request(substr($path,strlen('/wp-json'))); exit; }
\KnifeRevive\AgentCommerce\Frontend::render();
http_response_code(404); echo 'Synthetic test route unavailable.';
