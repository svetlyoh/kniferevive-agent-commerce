<?php
require __DIR__.'/sandbox-bootstrap.php';
use KnifeRevive\AgentCommerce\{Domain,Fault,Store};
$attempt=Domain::id();
try {
    Store::transaction(static function () use ($attempt) {
        Store::hold([['slot_id'=>'race-fixture','kind'=>'customer_dropoff']],$attempt,time()+300);
        usleep(250000);
    });
    echo "RESERVED\n";
} catch (Fault $e) { echo $e->codeName."\n"; }
