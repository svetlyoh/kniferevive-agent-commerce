<?php
ob_start();
require __DIR__.'/sandbox-bootstrap.php';
if(DB_NAME!=='krev_agent_sandbox' || $wpdb->prefix!=='krev_sandbox_')exit(1);
try{wc_reserve_stock_for_order(wc_get_order((int)$argv[2]));echo 'RESERVED';}
catch(\Automattic\WooCommerce\Checkout\Helpers\ReserveStockException $e){echo 'UNAVAILABLE';}
