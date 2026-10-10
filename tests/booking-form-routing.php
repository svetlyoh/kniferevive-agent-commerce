<?php
/** Real WordPress request parsing: contact fields must not become page selectors. */
ob_start();set_exception_handler(static function(Throwable $e){fwrite(STDERR,'FAIL: '.$e->getMessage()."\n");exit(1);});
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{BookingFrontend,Settings};
if(DB_NAME!=='krev_agent_sandbox' || DB_HOST!==KREV_TEST_DB_HOST)throw new RuntimeException('Sandbox fence failed');
$checks=0;
function routeCheck(bool $ok,string $label):void{global $checks;if(!$ok)throw new RuntimeException($label);++$checks;echo "PASS: $label\n";}
$checkout=(int)get_option('woocommerce_checkout_page_id');
routeCheck($checkout>0 && get_post_status($checkout)==='publish','native checkout fixture exists');
// Render the actual customer fields, including a previously saved private request.
$contact=new ReflectionMethod(BookingFrontend::class,'contact');
ob_start();$contact->invoke(null,['customer'=>['name'=>'Synthetic Routing Buyer','email'=>'buyer@example.invalid']]);$html=ob_get_clean();
routeCheck(str_contains($html,'name="customer_name"') && str_contains($html,'value="Synthetic Routing Buyer"'),'saved contact name renders under safe form field');
routeCheck(!str_contains($html,'name="name"'),'contact form omits WordPress name query variable');
preg_match_all('/<input[^>]*name="([^"]+)"/',$html,$fields);
global $wp,$wp_query,$wp_the_query;
routeCheck(!array_intersect($fields[1],$wp->public_query_vars),'all rendered contact fields are outside public routing variables');
$_SERVER['REQUEST_URI']='/?page_id='.$checkout;$_SERVER['REQUEST_METHOD']='POST';
$_GET=['page_id'=>(string)$checkout];$_POST=['name'=>'Synthetic Routing Buyer'];
$wp=new WP();$wp->parse_request();$wp_query=new WP_Query($wp->query_vars);$wp_the_query=$wp_query;
do_action('wp',$wp);
// Each browser request has a fresh Woo page-detection cache; this CLI covers three.
$pageCache=new ReflectionProperty(\Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::class,'is_checkout_page');$pageCache->setValue(null,null);
routeCheck(($wp->query_vars['name']??'')==='Synthetic Routing Buyer' && !is_checkout(),'old customer field hijacks native checkout routing');
$_POST=['customer_name'=>'Synthetic Routing Buyer','email'=>'buyer@example.invalid','action'=>'coverage'];
$wp=new WP();$wp->parse_request();$wp_query=new WP_Query($wp->query_vars);$wp_the_query=$wp_query;
$pageCache->setValue(null,null);
routeCheck(!isset($wp->query_vars['name']) && is_checkout(),'renamed field preserves native checkout on POST');
$_POST['action']='submit';$wp=new WP();$wp->parse_request();$wp_query=new WP_Query($wp->query_vars);$wp_the_query=$wp_query;$pageCache->setValue(null,null);
routeCheck(is_checkout(),'Continue to payment POST stays on checkout before private handoff');
echo 'PASS: '.$checks." booking routing assertions; no booking, order, payment or email created.\n";ob_end_flush();
