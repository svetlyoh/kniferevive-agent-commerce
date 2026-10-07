<?php
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Commerce,Domain,Settings,Store};
$products=wc_get_products(['category'=>['knife-sharpening'],'status'=>'publish','limit'=>1]);
if (!$products) throw new RuntimeException('Run integration.php before creating the UI fixture.');
$p=$products[0]; wp_update_post(['ID'=>$p->get_id(),'post_author'=>1]); $p->set_price('7.00'); $p->set_regular_price('7.00'); $p->save();
$slots=[['id'=>'ui-intake','kind'=>'customer_dropoff','start'=>gmdate('Y-m-d\TH:i:s\Z',time()+86400),'end'=>gmdate('Y-m-d\TH:i:s\Z',time()+90000),'capacity'=>10],
    ['id'=>'ui-return','kind'=>'customer_collection','start'=>gmdate('Y-m-d\TH:i:s\Z',time()+172800),'end'=>gmdate('Y-m-d\TH:i:s\Z',time()+176400),'capacity'=>10]];
$s=Settings::validate(['enabled'=>true,'pricing_verified'=>true,'stripe_enabled'=>true,'merchant_ids'=>[1],
    'services'=>[['product_id'=>$p->get_id(),'definition'=>'Synthetic ordinary sharpening test service.']],'postal_codes'=>['94110'],'location'=>'Synthetic test location',
    'policy_url'=>'https://kniferevive.com/test-service-policy/','policy_version'=>'ui-fixture-1','slots'=>$slots]);
update_option('krev_agent_settings',$s,false); Store::syncSlots($slots);
$owner=Domain::id(); Store::put($owner,'session','ui-synthetic-fixture',time()+7200,['token_hash'=>hash('sha256',Domain::token($owner))]);
$input=['items'=>[['product_id'=>$p->get_id(),'quantity'=>2]],'postal_code'=>'94110','intake'=>['kind'=>'customer_dropoff','slot_id'=>'ui-intake'],
    'return'=>['kind'=>'customer_collection','slot_id'=>'ui-return'],'rail'=>'stripe_checkout','booking_mode'=>'scheduled'];
$q=Commerce::quote($input,$owner,'ui-quote-'.Domain::id());
file_put_contents(dirname(__DIR__).'/.runtime/ui-fixture.json',json_encode(\KnifeRevive\AgentCommerce\Api::quoteResponse($q,$owner)));
echo "Private synthetic UI fixture saved locally.\n";
