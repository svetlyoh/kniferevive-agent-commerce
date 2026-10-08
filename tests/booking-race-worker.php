<?php
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Booking,Fault};
if(DB_NAME!=='krev_agent_sandbox' || $wpdb->prefix!=='krev_sandbox_')exit(2);
wp_set_current_user(get_user_by('login','sandbox-admin')->ID);
$start=(float)($argv[3]??0);while(microtime(true)<$start)usleep(10000);
try{Booking::confirm($argv[2]);echo "reserved\n";}catch(Fault $e){echo $e->codeName."\n";}
