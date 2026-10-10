<?php
/** Reset only disposable native shipping/tax/session fixtures in the named sandbox. */
require __DIR__.'/sandbox-bootstrap.php';
if(DB_NAME!=='krev_agent_sandbox' || DB_HOST!==KREV_TEST_DB_HOST || $wpdb->prefix!=='krev_sandbox_')throw new RuntimeException('Fixture fence failed.');
foreach(['woocommerce_shipping_zones','woocommerce_shipping_zone_locations','woocommerce_shipping_zone_methods','woocommerce_tax_rates','woocommerce_tax_rate_locations','woocommerce_sessions'] as $name){
    if($wpdb->query('TRUNCATE TABLE '.$wpdb->prefix.$name)===false)throw new RuntimeException('Cannot reset synthetic fixture.');
}
delete_transient('wc_shipping_method_count');WC_Cache_Helper::invalidate_cache_group('shipping_zones');WC_Cache_Helper::invalidate_cache_group('taxes');WC_Cache_Helper::get_transient_version('shipping',true);
update_option('woocommerce_pickup_location_settings',['enabled'=>'no']);update_option('pickup_location_pickup_locations',[]);
$checkout=wc_get_page_id('checkout');if($checkout>0)wp_update_post(['ID'=>$checkout,'post_content'=>'[woocommerce_checkout]']);
echo 'PASS: fenced native fixtures reset.';
