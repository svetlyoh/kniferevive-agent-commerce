<?php
define('ABSPATH',__DIR__);
require dirname(__DIR__).'/wordpress/kniferevive-returns/includes/Contract.php';
use KnifeRevive\Returns\Contract;
$count=0;function verify($v,$label){global$count;if(!$v)throw new RuntimeException($label);$count++;echo 'PASS '.$label.PHP_EOL;}
$sold=['policy'=>['type'=>'returns','days'=>15,'fee'=>'5'],'anchor'=>null,'policy_hash'=>'original','seller_at_sale'=>9];
$cfg=['enabled'=>true,'version'=>'returns-15-reviewed','approved_at'=>100,'approved_by'=>7,'effective_at'=>200,'days'=>15,'anchor'=>'delivery'];
verify(Contract::apply($sold,[],300,'')===$sold,'absent contract leaves historical unknowns unchanged');
verify(Contract::apply($sold,$cfg,150,$cfg['version'])===$sold,'pre-effective purchase unchanged');
verify(Contract::apply($sold,$cfg,300,'')===$sold,'undisclosed checkout cannot acquire a new anchor');
verify(Contract::apply($sold,$cfg,300,'another-version')===$sold,'wrong disclosure cannot acquire a new anchor');
foreach(['enabled'=>false,'approved_by'=>0,'approved_at'=>0,'effective_at'=>99,'days'=>30,'anchor'=>'purchase','version'=>'bad version']as$key=>$value){$bad=$cfg;$bad[$key]=$value;verify(Contract::apply($sold,$bad,300,$cfg['version'])===$sold,'rejects unsupported '.$key);}
$existing=$sold;$existing['anchor']='purchase';verify(Contract::apply($existing,$cfg,300,$cfg['version'])===$existing,'existing purchased anchor is immutable');
$no=$sold;$no['policy']=['type'=>'no_returns','days'=>0];verify(Contract::apply($no,$cfg,300,$cfg['version'])===$no,'No Returns rights are not rewritten');
$thirty=$sold;$thirty['policy']['days']=30;verify(Contract::apply($thirty,$cfg,300,$cfg['version'])===$thirty,'unlaunched thirty-day policy is not installed');
$new=Contract::apply($sold,$cfg,200,$cfg['version']);verify($new['anchor']==='delivery','reviewed disclosed effective purchase gets prospective anchor');
verify($new['policy']===$sold['policy']&&$new['policy_hash']===$sold['policy_hash']&&$new['seller_at_sale']===9,'sold fee, hash and seller remain unchanged');
verify($new['prospective_contract']['unknown_delivery']==='human_review','unknown delivery remains reviewable');
verify(!isset($new['delivered_at'],$new['until'],$new['automatically_approved']),'contract cannot invent receipt, expiry or approval');
verify(Contract::apply($new,$cfg,300,$cfg['version'])===$new,'repeated application does not rewrite purchased contract');
echo 'TOTAL '.$count.' policy contract checks'.PHP_EOL;
