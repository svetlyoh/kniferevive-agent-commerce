<?php
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{BookingOrderBridge,Fault};
if(DB_NAME!=='krev_agent_sandbox' || $wpdb->prefix!=='krev_sandbox_')exit(2);
wp_set_current_user(get_user_by('login','sandbox-admin')->ID);
$start=(float)($argv[3]??0);while(microtime(true)<$start)usleep(10000);
for($attempt=0;$attempt<20;$attempt++){
    try{echo BookingOrderBridge::ensure($argv[2])->get_id()."\n";exit;}
    catch(Fault $e){if($e->codeName!=='BUSY'){echo $e->codeName."\n";exit;}usleep(100000);}
}
echo "BUSY\n";
