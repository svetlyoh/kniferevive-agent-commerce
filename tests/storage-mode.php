<?php
/** Switch only the fenced synthetic database, after native WooCommerce synchronization. */
require __DIR__.'/sandbox-bootstrap.php';
if ($wpdb->prefix!=='krev_sandbox_' || DB_NAME!=='krev_agent_sandbox') throw new RuntimeException('Sandbox fence rejected.');
$mode=$argv[2]??'';
if (!in_array($mode,['legacy','hpos'],true)) throw new RuntimeException('Choose legacy or hpos.');
$sync=wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer::class);
$sync->create_database_tables();
update_option('woocommerce_custom_orders_table_data_sync_enabled','yes');
for ($batch=0;$batch<100;$batch++) {
    $ids=$sync->get_next_batch_to_process(100);
    if (!$ids) break;
    $sync->process_batch($ids);
}
if ($sync->has_orders_pending_sync()) throw new RuntimeException('Storage mode unchanged: synchronization remains pending.');
update_option('woocommerce_custom_orders_table_enabled',$mode==='hpos'?'yes':'no');
if (get_option('woocommerce_custom_orders_table_enabled')!==($mode==='hpos'?'yes':'no')) throw new RuntimeException('Storage switch rejected.');
echo "Synthetic database set to $mode. Run tests in a fresh process.\n";
