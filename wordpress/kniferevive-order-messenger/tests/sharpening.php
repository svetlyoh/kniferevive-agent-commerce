<?php
// Included by the native suite; shares its synthetic fixtures and cleanup.
$term = term_exists('knife-sharpening','product_cat');
$createdTerm = false;
if (!$term) { $term=wp_insert_term('Knife Sharpening','product_cat',['slug'=>'knife-sharpening']); $createdTerm=true; }
$termId=(int)(is_array($term)?$term['term_id']:$term);
try {
    update_option('kr_om_settings',KR_Order_Messenger_Admin::defaults(),false);
    $operator=$newUser('customer'); (new WP_User($operator))->add_cap('krev_manage_sharpening');
    $sharpServiceProduct=$newProduct($a,'QA sharpening service'); wp_set_object_terms($sharpServiceProduct->get_id(),[$termId],'product_cat');
    $sharp=$newOrder(0,$buyer,$sharpServiceProduct);
    // Checkout hooks initialize sharpening metadata through their own order
    // object. A real REST request loads it afresh rather than retaining the
    // pre-hook fixture object's metadata cache.
    $sharp=wc_get_order($sharp->get_id()); $sharp->read_meta_data(true);
    wp_set_current_user($operator);
    $assert(KREV_Orders_Permissions::is_operator() && !current_user_can('manage_woocommerce'),'sharpening-only operator fixture does not manage merchandise');
    $scope=KR_Order_Messenger_Permissions::scope($sharp->get_id());
    $assert(!is_wp_error($scope) && $scope['kind']==='sharpening','native operator messages sharpening order without Dokan vendor inference');
    $context=KR_Order_Messenger_Context::data($scope);
    $assert($context['recipient']===get_userdata($buyer)->user_email,'sharpening recipient uses the registered buyer');
    $assert($context['reply_to']===get_userdata($operator)->user_email,'sharpening replies use the authenticated operator account');
    $assert($context['store_name']==='KnifeRevive Sharpening','sharpening context identifies the service');
    $assert(is_wp_error(KR_Order_Messenger_Permissions::scope($oa->get_id())),'operator exception does not grant merchandise access');
    $assert($call('GET',$sharp->get_id(),'context')->get_status()===200,'sharpening context route authorizes native operator');
    $assert($call('GET',$sharp->get_id(),'context',[],false)->get_status()===403,'sharpening context still requires a valid nonce');
    $assert($call('POST',$sharp->get_id(),'preview',['message'=>'Synthetic sharpening update'])->get_status()===200,'sharpening preview renders without delivery');
    $beforeMessages=count($sent);
    $sharpSend=$call('POST',$sharp->get_id(),'send',['message'=>'Synthetic sharpening update','attachment_count'=>0,'request_id'=>$uuid()]);
    $assert(($sharpSend->get_data()['status']??'')==='accepted','operator sharpening email reaches intercepted transport');
    $assert(count($sent)===$beforeMessages+1 && in_array(get_userdata($buyer)->user_email,(array)$sent[array_key_last($sent)]['to'],true),'operator message goes only to the recorded customer');
    $oldSharpEndpoint=$wp->query_vars['sharpening-orders']??null;
    $oldSharpQuery=$wp_query->get('sharpening-orders');
    $wp->query_vars['sharpening-orders']=(string)$sharp->get_id(); $wp_query->set('sharpening-orders',(string)$sharp->get_id());
    unset($wp->query_vars['seller-orders']); $wp_query->set('seller-orders','');
    $assert(is_wc_endpoint_url('sharpening-orders'),'WooCommerce recognizes sharpening endpoint');
    KR_Order_Messenger::assets();
    $assert(wp_script_is('kr-order-messenger','enqueued') && wp_style_is('kr-order-messenger','enqueued'),'operator sharpening page receives composer assets');
    ob_start(); KR_Order_Messenger::screen((string)$sharp->get_id()); $operatorScreen=ob_get_clean();
    $assert(str_contains($operatorScreen,'Quick Update'),'operator sharpening page has Quick Update');
    foreach ([$buyer,$b,0] as $deniedUser) {
        wp_set_current_user($deniedUser);
        $assert(is_wp_error(KR_Order_Messenger_Permissions::scope($sharp->get_id())),'buyer, unrelated seller and anonymous cannot message sharpening customer');
        $assert(is_wp_error(KREV_Sharpening_Workflow::change_stage($sharp,'blade-check')),'buyer, unrelated seller and anonymous cannot change sharpening stages');
    }
    wp_set_current_user($operator);
    $assert(is_wp_error(KREV_Sharpening_Workflow::change_stage($sharp,'invalid-stage')),'unknown stage rejected');
    $assert(is_wp_error(KREV_Sharpening_Workflow::change_stage($sharp,'return')),'unpaid return stage blocked');
    $sharp->set_status('processing'); $sharp->save();
    $sharp=wc_get_order($sharp->get_id()); $sharp->read_meta_data(true);
    $beforeTotal=$sharp->get_total(); $beforeStatus=$sharp->get_status();
    foreach (array_keys(KREV_Sharpening_Assets::stages()) as $key) {
        $result=KREV_Sharpening_Workflow::change_stage($sharp,$key);
        $assert(!is_wp_error($result) && $result['current_stage']===$key,'operator saves stage '.$key);
        $reloaded=wc_get_order($sharp->get_id());
        $reloaded->read_meta_data(true);
        $assert($reloaded->get_meta('_krev_sharpening_stage')===$key && (float)$reloaded->get_total()===(float)$beforeTotal && $reloaded->get_status()===$beforeStatus,'stage '.$key.' persists without financial/status change: '.wp_json_encode([$reloaded->get_meta('_krev_sharpening_stage'),$beforeTotal,$reloaded->get_total(),$beforeStatus,$reloaded->get_status()]));
    }
    $sharp->update_meta_data('_krev_sharpening_completed','yes'); $sharp->update_meta_data('_krev_sharpening_completed_at',gmdate('Y-m-d H:i:s')); $sharp->save();
    $reopened=KREV_Sharpening_Workflow::change_stage($sharp,'blade-check');
    $assert(!$reopened['workflow_completed'] && $sharp->get_meta('_krev_sharpening_completed_at')==='','earlier step reopens completed sharpening workflow');
    $stageRequest=new WP_REST_Request('POST','/kniferevive/v1/sharpening-orders/'.$sharp->get_id().'/stage');
    $stageRequest->set_body_params(['stage'=>'hone-test']);
    $assert(rest_get_server()->dispatch($stageRequest)->get_status()===200,'native stage REST handler persists authorized choice');
    wp_set_current_user($buyer);
    $deniedStageStatus=rest_get_server()->dispatch($stageRequest)->get_status();
    $assert(in_array($deniedStageStatus,[401,403],true),'native stage REST handler rejects ordinary buyer');
    wp_set_current_user($operator);
    $sharpening=KREV_Sharpening_Workflow::dto($sharp); $order=$sharp;
    ob_start(); include KREV_ORDERS_PATH.'templates/sharpening-order-detail.php'; $stageHtml=ob_get_clean();
    $assert(substr_count($stageHtml,'data-krev-stage=')===8,'operator sees eight illustrated stage buttons');
    if (getenv('KREV_SHARPENING_UI_SAVE')==='1') {
        file_put_contents(dirname(__DIR__,3).'/.runtime/sharpening-ui.html',$stageHtml.$operatorScreen);
        file_put_contents(dirname(__DIR__,3).'/.runtime/sharpening-context.json',wp_json_encode($context));
    }
    wp_set_current_user($buyer);
    ob_start(); include KREV_ORDERS_PATH.'templates/sharpening-order-detail.php'; $buyerHtml=ob_get_clean();
    $assert(!str_contains($buyerHtml,'data-krev-stage=') && !str_contains($buyerHtml,'data-krev-stage-save'),'buyer timeline remains read-only');
    wp_set_current_user($operator);
    $mixed=$newOrder(0,$buyer,$sharpServiceProduct); $mixed->add_product($pb,1); $mixed->save();
    $mixedContext=KR_Order_Messenger_Context::data(KR_Order_Messenger_Permissions::scope($mixed->get_id()));
    $assert(count($mixedContext['items'])===1 && $mixedContext['items'][0]['name']==='QA sharpening service','sharpening operator context excludes unrelated merchandise');
    (new WP_User($operator))->remove_cap('krev_manage_sharpening');
    $assert(is_wp_error(KR_Order_Messenger_Permissions::scope($sharp->get_id())),'revoked operator capability immediately removes messaging access');
    wp_dequeue_script('kr-order-messenger'); wp_dequeue_style('kr-order-messenger'); KR_Order_Messenger::assets();
    $assert(!wp_script_is('kr-order-messenger','enqueued'),'revoked operator cannot load sharpening composer');
    if ($oldSharpEndpoint===null) unset($wp->query_vars['sharpening-orders']); else $wp->query_vars['sharpening-orders']=$oldSharpEndpoint;
    $wp_query->set('sharpening-orders',$oldSharpQuery);
} finally {
    foreach ($orders as $testOrderId) $wpdb->delete(KREV_Sharpening_Workflow::table(),['order_id'=>$testOrderId]);
    if ($createdTerm) wp_delete_term($termId,'product_cat');
}
