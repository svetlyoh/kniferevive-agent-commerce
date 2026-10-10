<?php
// Loopback-only synthetic sandbox; never serves the user's normal WordPress configuration.
if (PHP_SAPI!=='cli-server' || !in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)) { http_response_code(403); exit; }
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($path==='/fixture-media/Knife_Revive_Logo_OG_V2.jpg'){header('Content-Type: image/jpeg');readfile(dirname(__DIR__).'/.runtime/ui-media/Knife_Revive_Logo_OG_V2.jpg');exit;}
$testRoot=getenv('KREV_TEST_WP_ROOT');$fontRoot=realpath($testRoot.'/wp-content/themes/twentytwentyfour/assets/fonts');
if(str_starts_with($path,'/wp-content/themes/twentytwentyfour/assets/fonts/')){$font=realpath($testRoot.$path);if($font && $fontRoot && str_starts_with(str_replace('\\','/',$font),str_replace('\\','/',$fontRoot).'/') && preg_match('/\.(woff2|woff|ttf)$/',$font)){header('Content-Type: font/woff2');readfile($font);exit;}}
$assetMap=['storefront.js'=>'text/javascript','booking.js'=>'text/javascript','storefront.css'=>'text/css','booking-checkout.css'=>'text/css'];
foreach ($assetMap as $name=>$type) if ($path==='/wp-content/plugins/kniferevive-agent-commerce/assets/'.$name) {
    header('Content-Type: '.$type); readfile(dirname(__DIR__).'/wordpress/kniferevive-agent-commerce/assets/'.$name); exit;
}
// Native checkout assets, served only from the installed sandbox core/plugin tree.
if(preg_match('/\.(js|css)$/D',$path) && (str_starts_with($path,'/wp-includes/') || str_starts_with($path,'/wp-content/plugins/') || str_starts_with($path,'/wp-content/themes/'))){
    $file=realpath($testRoot.$path);$base=realpath($testRoot);
    if($file && $base && str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$base).'/')){header('Content-Type: '.(str_ends_with($path,'.js')?'text/javascript':'text/css'));readfile($file);exit;}
}
$argv=[__FILE__,getenv('KREV_TEST_WP_ROOT')];
require __DIR__.'/sandbox-bootstrap.php';
if(getenv('KREV_LISTING_UI')==='1'){
    class ListingUiGateway extends WC_Payment_Gateway {public function __construct(){$this->id='stripe';$this->enabled='yes';$this->title='Synthetic native test gateway';$this->supports=['products','refunds'];$this->settings=['testmode'=>'yes'];}public function is_available(){return true;}public function process_payment($id){throw new RuntimeException('Synthetic browser must not initiate payment.');}}
    add_filter('woocommerce_payment_gateways',static fn($g)=>[ListingUiGateway::class],1000);WC()->payment_gateways()->init();
    if(!WC()->session || !WC()->cart || !WC()->customer)wc_load_cart();
    if(getenv('KREV_STOREFRONT_UI')==='1' && ($_SERVER['REQUEST_METHOD']??'GET')==='GET' && isset($_GET['krev_ui_cart'])){
        WC()->cart->empty_cart();$services=\KnifeRevive\AgentCommerce\Settings::get()['booking_services'];
        WC()->cart->add_to_cart($services[0]['product_id'],2);WC()->cart->add_to_cart($services[1]['product_id'],1);
        if($_GET['krev_ui_cart']==='mixed')WC()->cart->add_to_cart((int)get_option('krev_ui_storefront_goods'),1);
        WC()->cart->calculate_totals();WC()->session->set_customer_session_cookie(true);WC()->session->save_data();
    }
    if((int)($_GET['page_id']??0)===(int)get_option('woocommerce_checkout_page_id')){
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Synthetic native WooCommerce checkout</title></head><body><h1>Native WooCommerce checkout</h1>';
        echo do_shortcode('[woocommerce_checkout]');echo '</body></html>';exit;
    }
}
add_filter('plugins_url',static function ($url,$relative,$plugin) { return str_ends_with($plugin,'kniferevive-agent-commerce.php') ? WP_HOME.'/wp-content/plugins/kniferevive-agent-commerce'.($relative?'/'.$relative:'') : $url; },10,3);
if(getenv('KREV_STOREFRONT_UI')==='1' && $path==='/checkout/'){
    // Use real WordPress POST query parsing, so reserved fields cannot hide
    // routing bugs behind a forced woocommerce_is_checkout override.
    global $wp;
    $wp->parse_request(['page_id'=>wc_get_page_id('checkout')]);
    $wp->query_posts();$wp->handle_404();$wp->register_globals();do_action('wp',$wp);
    \KnifeRevive\AgentCommerce\StorefrontBooking::render();
}
if(isset($_GET['wc-ajax'])){WC_AJAX::do_wc_ajax();exit;}
if(getenv('KREV_BOOKING_UI')==='1' && (isset($_GET['krev_ui_vendor']) || isset($_GET['krev_ui_vendor_orders']))){
    // Synthetic loopback fixture only; never included in the distributable plugin.
    wp_set_current_user((int)get_option('krev_ui_booking_vendor'));
    if(isset($_GET['krev_ui_vendor']))\KnifeRevive\AgentCommerce\BookingSeller::render();
    \KnifeRevive\AgentCommerce\PrivateBrand::headers();\KnifeRevive\AgentCommerce\PrivateBrand::start('Native Dokan Orders (synthetic verification)');
    echo '<h1>Native Dokan seller Orders</h1><p>Synthetic local database. No real customer or payment.</p>';
    dokan_get_template_part('orders/listing','',['user_orders'=>dokan()->order->all(['seller_id'=>get_current_user_id()]),'bulk_order_statuses'=>wc_get_order_statuses(),'allow_shipment'=>'off','wc_shipping_enabled'=>false,'num_of_pages'=>1,'pagenum'=>1]);
    \KnifeRevive\AgentCommerce\PrivateBrand::end();exit;
}
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
if(getenv('KREV_BOOKING_UI')==='1' && isset($_GET['krev_ui_nojs']))ob_start(static function($html){foreach(headers_list() as $header)if(str_starts_with($header,'Content-Security-Policy:'))header(str_replace("script-src 'self'","script-src 'none'",$header),true);return $html;});
\KnifeRevive\AgentCommerce\Frontend::render();
http_response_code(404); echo 'Synthetic test route unavailable.';
