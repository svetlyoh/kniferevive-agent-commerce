<?php
require __DIR__.'/seller-contact-bootstrap.php';
$src=file_get_contents(dirname(__DIR__).'/wordpress-overlays/kniferevive-seller-orders/templates/seller-orders.php');
$result=(object)['orders'=>[],'max_num_pages'=>0];$sharpening_result=null;$tab='all';$page=1;
ob_start();include dirname(__DIR__).'/wordpress-overlays/kniferevive-seller-orders/templates/seller-orders.php';ob_end_clean();
$seller=wp_insert_user(['user_login'=>'order-link-qa-'.wp_generate_password(8,false),'user_pass'=>wp_generate_password(32),'role'=>'editor']);
get_role('editor')->add_cap('edit_products');get_role('editor')->add_cap('edit_others_products');get_role('editor')->add_cap('edit_published_products');get_role('editor')->add_cap('edit_private_products');
wp_set_current_user($seller);
$rows=[];
foreach(['publish','draft','pending','private','future','trash'] as $status){
 $id=wp_insert_post(['post_type'=>'product','post_title'=>'Listing link isolated QA','post_author'=>$seller,'post_status'=>$status,'post_date'=>$status==='future'?wp_date('Y-m-d H:i:s',time()+86400):current_time('mysql')]);
 update_post_meta($id,'_stock_status','outofstock');update_post_meta($id,'_stock',0);
 $url=$krev_order_listing_url(['product_id'=>$id]);
 if(!$url||($status==='publish'&&str_contains($url,'action=edit'))||($status!=='publish'&&!str_contains($url,'product_id='.$id))){throw new RuntimeException('Listing link failed '.$status);}
 $rows[]=['status'=>$status,'zero_stock'=>true,'pass'=>true];
 wp_delete_post($id,true);
}
if($krev_order_listing_url(['product_id'=>PHP_INT_MAX])!=='')throw new RuntimeException('Deleted product must not create a false link');
$rows[]=['status'=>'deleted','pass'=>true];
wp_set_current_user(0);$id=wp_insert_post(['post_type'=>'product','post_status'=>'draft','post_author'=>$seller,'post_title'=>'Private QA']);
if($krev_order_listing_url(['product_id'=>$id])!=='')throw new RuntimeException('Private record leaked to unauthorized viewer');
$rows[]=['status'=>'unauthorized_draft','pass'=>true];
wp_delete_post($id,true);require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($seller);
file_put_contents(dirname(__DIR__).'/output/seller-contact/order-link-results.json',wp_json_encode($rows,JSON_PRETTY_PRINT));
echo "8 order-link checks passed: six listing states at zero stock, deleted listing, unauthorized draft. Isolated database; mail/network blocked.\n";
