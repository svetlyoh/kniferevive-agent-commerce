<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

use KnifeRevive\ShipKit\{Application,Check,Ledger,ShippingIou};

final class Money {
    /** Seller debt is a reclassification of the already funded platform cost.
     * It grants no right to sweep earnings, postpone the label, or freeze payouts. */
    public static function assess(int $id,array $in): array {
        $r=Store::row($id);Access::requireRole($r,['admin']);
        $r=Store::edit($id,$in,'postage_iou',['admin'],static function($r,&$v)use($in){
            if(($v['postage']['responsible_party']??'')!=='seller'||empty($v['postage']['finding'])||empty($in['confirm_responsibility'])||empty($in['policy_finding'])||empty($v['label']['cost_minor'])||$v['label']['state']!=='ready')Support::fail('iou','Establish seller responsibility under the applicable sold policy and exact incurred postage first.');
            $v['postage']['iou_intent']=['id'=>'return-postage-'.$r['id'],'amount'=>(int)$v['label']['cost_minor'],'policy_finding'=>Support::text($in['policy_finding']),'actor'=>get_current_user_id(),'at'=>time()];
        });
        $v=Store::state($r);$intent=$v['postage']['iou_intent'];$db=Application::db();$key=$intent['id'];
        $db->transaction(function()use($db,$r,$v,$intent,$key){
            $old=$db->get('adjustment',$key);if($old){if($old['raw']['amount']!==$intent['amount']||(int)$old['seller_id']!==(int)$r['seller_id'])Support::fail('iou','This return-postage assessment already differs. Reconcile it.');return;}
            $c=['id'=>$key,'seller_id'=>(int)$r['seller_id'],'attempt_id'=>$v['label']['attempt_id'],'return_case'=>(int)$r['id'],'assessment_origin'=>'return_postage','raw'=>['account'=>'platform-return','external'=>$key,'label'=>$v['label']['attempt_id'],'amount'=>$intent['amount'],'currency'=>'usd','evidence'=>Support::hash($intent),'reason'=>$v['postage']['finding']],
                'state'=>'assessed','responsibility'=>'seller_measurement','responsibility_basis'=>'return_case_finding','reviewer'=>$intent['actor'],'review_evidence'=>Support::hash($intent),'contributions'=>[],'collected'=>0,'credited'=>0,'amount_due'=>$intent['amount'],'policy_finding'=>$intent['policy_finding']];
            $db->post('return-iou:'.$r['id'],[Check::debit('seller-'.$r['seller_id'].'-receivable',$intent['amount']),Check::credit('platform-return-postage-expense',$intent['amount'])],['return_case'=>(int)$r['id'],'finding'=>$v['postage']['finding'],'source'=>'separate responsibility determination','cash_or_deduction_authorized'=>false]);
            $db->put('adjustment',$key,$c,true);
        });
        return self::sync($id,$key);
    }
    public static function gamePlan(int $id,array $in): array {
        $r=Store::row($id);Access::requireRole($r,['admin']);$r=Store::edit($id,$in,'postage_plan',['admin'],static function($r,&$v)use($in){if(empty($v['postage']['iou_id']))Support::fail('plan','Establish this case’s Shipping IOU first.');$v['postage']['game_plan_intent']=['schedule'=>$in['schedule']??[],'exposure_cap_minor'=>Support::integer($in['exposure_cap_minor']??0,0,100000),'finding'=>Support::text($in['finding']??'')];});
        $v=Store::state($r);$db=Application::db();$flow=new ShippingIou($db,new Ledger($db));$p=$v['postage']['game_plan_intent'];foreach($p['schedule']as&$row){if(isset($row['due_date'])){$date=\DateTimeImmutable::createFromFormat('!Y-m-d',(string)$row['due_date'],new \DateTimeZone(Policy::settings()['timezone']));if(!$date||$date->format('Y-m-d')!==$row['due_date'])Support::fail('date','Choose a valid Game Plan date.',422);$row['due_at']=$date->setTime(18,0)->getTimestamp();unset($row['due_date']);}}unset($row);$flow->gamePlan($v['postage']['iou_id'],get_current_user_id(),$p['schedule'],$p['exposure_cap_minor'],$p['finding']);return self::sync($id,$v['postage']['iou_id']);
    }
    public static function sync(int $id,string $key): array {
        $r=Store::row($id);$c=Application::db()->get('adjustment',$key);if(!$c||($c['return_case']??0)!==$id)Support::fail('scope','Return postage assessment not found.');
        return Store::locked(wc_get_order($r['order_id']),static function()use($id,$c,$key){$r=Store::row($id);$v=Store::state($r);$due=ShippingIou::due($c);$v['postage']=array_merge($v['postage'],['iou_id'=>$key,'state'=>$due?($c['state']==='seller_disputed'?'iou_disputed':'iou_open'):($c['credited']?'credited':'paid'),'amount_minor'=>$c['raw']['amount'],'due_minor'=>$due,'automatic_deduction'=>false,'game_plan'=>isset($c['game_plan'])?array_intersect_key($c['game_plan'],array_flip(['state','schedule','exposure_cap_minor','automatic_installments'])):null]);return Store::save($r,$v,'postage_status');});
    }
    private static function dto(array $r): array {
        $v=Store::state($r);$o=wc_get_order($r['order_id']);$cost=$v['label']['cost_minor']??null;$c=!empty($v['postage']['iou_id'])?Application::db()->get('adjustment',$v['postage']['iou_id']):null;
        return ['id'=>'return:'.$r['id'],'kind'=>'return_shipping','label'=>'Return #'.$r['id'].' · Order #'.$o->get_order_number(),'amount_label'=>'Recorded return label cost · not a balance debit',
            'amount'=>\KnifeRevive\MyMoney\Money::dto($cost,'USD'),'occurred_at'=>gmdate('c',strtotime($r['updated_at'].' UTC')),'order_number'=>$o->get_order_number(),
            'status'=>['label'=>ucwords(str_replace('_',' ',$v['postage']['state'])),'source'=>'Common ShipKit attempt / IOU projection'],
            'verification'=>'recorded_operator_evidence','bank_payout'=>'not_available','due_minor'=>$c?ShippingIou::due($c):0];
    }
    public static function activity(mixed $unused,\KnifeRevive\MyMoney\Repository $repo,\KnifeRevive\MyMoney\Range $range,int $size,string $cursor): array {
        global$wpdb;$context=Support::hash([$repo->seller,$range->dto(),$size,'return_shipping']);$position=null;
        if($cursor){
            if(strlen($cursor)>2000||!preg_match('/^([A-Za-z0-9_-]+)\.([a-f0-9]{64})$/D',$cursor,$m)||!hash_equals(hash_hmac('sha256',$m[1],wp_salt('nonce')),$m[2]))throw new \InvalidArgumentException('Reload the first Return shipping page.');
            $p=json_decode(base64_decode(strtr($m[1],'-_','+/')),true);if(!$p||$p['context']!==$context||$p['expires']<time()||!is_int($p['id'])||!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D',$p['date']))throw new \InvalidArgumentException('The Return shipping page expired.');$position=$p;
        }
        $sql='SELECT r.* FROM '.\KREV_Returns::table().' r INNER JOIN '.\KREV_Return_Workflow::table().' d ON d.return_id=r.id WHERE r.seller_id=%d AND r.updated_at>=%s AND r.updated_at<%s AND d.data LIKE %s';$args=[$repo->seller,$range->utc_start,$range->utc_end,'%"v2"%'];
        if($position){$sql.=' AND (r.updated_at<%s OR (r.updated_at=%s AND r.id<%d))';array_push($args,$position['date'],$position['date'],$position['id']);}$sql.=' ORDER BY r.updated_at DESC,r.id DESC LIMIT %d';$args[]=$size+1;
        $rows=$wpdb->get_results($wpdb->prepare($sql,$args),ARRAY_A);if(!is_array($rows)||$wpdb->last_error)Support::fail('money','Return shipping records are unavailable; no balance fallback was substituted.',503);
        $more=count($rows)>$size;$rows=array_slice($rows,0,$size);$items=array_map([self::class,'dto'],$rows);$next=null;
        if($more&&$rows){$last=$rows[array_key_last($rows)];$raw=rtrim(strtr(base64_encode(wp_json_encode(['context'=>$context,'expires'=>time()+1800,'date'=>$last['updated_at'],'id'=>(int)$last['id']])),'+/','-_'),'=');$next=$raw.'.'.hash_hmac('sha256',$raw,wp_salt('nonce'));}
        return ['items'=>$items,'next_cursor'=>$next,'warnings'=>[],'range'=>array_merge($range->dto(),['basis'=>'Return shipping: latest case-update dates; amounts describe current label cost and separate IOUs, not new sales or balance totals.'])];
    }
    public static function detail(mixed $missing,\KnifeRevive\MyMoney\Repository $repo,int $id): mixed {
        try{$r=Store::row($id);if((int)$r['seller_id']!==$repo->seller||Store::state($r)['legacy'])return$missing;$v=Store::state($r);$dto=self::dto($r);$c=!empty($v['postage']['iou_id'])?Application::db()->get('adjustment',$v['postage']['iou_id']):null;
            $part=static fn($label,$amount,$source)=>['label'=>$label,'amount'=>\KnifeRevive\MyMoney\Money::dto($amount,'USD'),'source'=>$source];$dto['breakdown']=[$part('Actual recorded label cost',$v['label']['cost_minor']??null,'Shared ShipKit attempt'),$part('Separate Shipping IOU due',$c?ShippingIou::due($c):0,'Common assessment; not netted from seller balance'),$part('Explicit payments retained',$c?max(0,$c['collected']-array_sum($c['source_credits']??[])):0,'Authorized named-source payments only')];
            $dto['products']=[['name'=>$dto['label'],'quantity'=>(int)$r['quantity']]];$dto['original_earnings']=null;$dto['changes']=[];$dto['journey']=[];$dto['notes']=['This is descriptive current case activity. Do not add the label cost and its IOU as two expenses. A paid IOU or provider credit is not a product sale or bank payout.','A buyer product refund uses the original charged payment and separate entitlement review. Existing transfer receipts are preserved.'];$dto['view_order_url']=Screens::caseUrl($id);$dto['payout']=$repo->payout_link();return$dto;
        }catch(\Throwable $e){return$missing;}
    }
    /** Adds descriptive activity to the common projection, never adds a balance. */
    public static function project(array $money,int $seller): array {
        global$wpdb;$rows=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.\KREV_Returns::table().' WHERE seller_id=%d ORDER BY id DESC LIMIT 101',$seller),ARRAY_A);
        if(count($rows)>100){$money['return_shipping_status']='needs_paginated_review';return$money;}
        foreach($rows as$r){$v=Store::state($r);if($v['legacy'])continue;$cost=$v['label']['cost_minor']??0;
            if($cost)$money['money_moves'][]=['category'=>'return_shipping','reference'=>'return-'.$r['id'],'amount_minor'=>$cost,'state'=>$v['label']['state'],'balance_effect'=>'Platform funded; any Shipping IOU is separate','source'=>'ShipKit attempt '.$v['label']['attempt_id'],'at'=>$v['label']['available_at']??null];
            $f=$v['refund'];if(!empty($f['amount_minor']))$money['money_moves'][]=['category'=>'buyer_return_refund','reference'=>'return-'.$r['id'],'amount_minor'=>$f['amount_minor'],'state'=>$f['state'],'balance_effect'=>'Original buyer payment; existing Connect refund reconciliation governs earnings','source'=>'Original charged order; not a Stripe Express payout','at'=>$f['approved_at']??null];
        }
        $money['return_hold_policy']='OFF — no new return-review payout hold was enabled';return$money;
    }
}
