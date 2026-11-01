<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

final class Refunds {
    /** Preserve immutable transfers; old Connect reversal code is never used for
     * a v2 case. Until exact entitlement policy is approved, verified refunds
     * create a source-specific recovery review, not an automatic bank clawback. */
    public static function settlementReview(\WC_Order $root,\WC_Order_Refund $refund): void {
        $id=(string)$refund->get_meta('_krev_refund_claim_v2');$c=\KREV_Refund_Authority::claims($root)[$id]??null;
        if(!$c||$c['state']!=='succeeded')return;
        $plan=$c['plan'];$r=Store::row($plan['case_id']);$db=\KnifeRevive\ShipKit\Application::db();$key='refund-review-'.substr(hash('sha256',$id),0,48);
        $db->transaction(fn()=>$db->put('return_refund_review',$key,['id'=>$key,'seller_id'=>(int)$r['seller_id'],'case_id'=>(int)$r['id'],'order_id'=>$root->get_id(),'refund_id'=>$refund->get_id(),'claim'=>$id,'amount_minor'=>$plan['amount_minor'],'currency'=>'USD','state'=>'entitlement_and_funding_review','transferred_history_immutable'=>true,'automatic_transfer_reversal'=>false,'created_at'=>$c['verified_at']],true));
    }
    public static function managedRoot(\WC_Order $root): bool {
        $orders=[$root];foreach(wc_get_orders(['parent'=>$root->get_id(),'limit'=>101,'return'=>'objects'])as$o)$orders[]=$o;
        if(count($orders)>101)Support::fail('scope','The parent payment needs a paginated allocation review.',503);
        foreach($orders as$o)foreach(\KREV_Returns::for_order($o->get_id())as$r)if(!empty(\KREV_Return_Workflow::details($r['id'])['data']['v2']))return true;
        return false;
    }
    /** Integer minor-unit partition, with deterministic per-unit rounding. */
    public static function portion(int $total,int $units,int $start,int $quantity): int {
        if($total<0||$units<1||$start<0||$quantity<1||$start+$quantity>$units)Support::fail('allocation','The original paid-unit allocation needs review.');
        return intdiv($total,$units)*$quantity+max(0,min($start+$quantity,$total%$units)-min($start,$total%$units));
    }
    public static function weighted(int $total,array $weights): array {
        $sum=array_sum($weights);if($total<0||$sum<=0)Support::fail('allocation','The shipping/fee allocation needs original positive item values.');
        ksort($weights,SORT_NUMERIC);$out=[];$fractions=[];
        foreach($weights as$id=>$w){$out[$id]=intdiv($total*$w,$sum);$fractions[$id]=($total*$w)%$sum;}
        arsort($fractions,SORT_NUMERIC);$left=$total-array_sum($out);foreach($fractions as$id=>$f)if($left-->0)++$out[$id];return$out;
    }
    public static function chargedHash(\WC_Order $o): string {
        $data=['currency'=>$o->get_currency(),'transaction'=>$o->get_transaction_id(),'gateway'=>$o->get_payment_method(),'total'=>$o->get_total(),'lines'=>[]];foreach($o->get_items(['line_item','shipping','fee'])as$id=>$item)$data['lines'][$id]=['type'=>$item->get_type(),'quantity'=>$item->get_quantity(),'total'=>$item->get_total(),'taxes'=>$item->get_taxes()];return Support::hash($data);
    }
    private static function cents(mixed $n): int {if(!is_numeric($n)||!is_finite((float)$n))Support::fail('amount','Original payment amounts need review.');return(int)round((float)$n*100);}
    public static function proposal(array $r,array $in): array {
        $o=wc_get_order($r['order_id']);$root=Support::root($o);$line=$o->get_item($r['order_item_id']);$v=Store::state($r);
        if(!$line||$v['legacy']||$v['case']!=='approved')Support::fail('review','Approve this case under the reviewed purchase-time evidence first.');
        if($root->get_currency()!=='USD'||!$root->get_transaction_id())Support::fail('payment','Resolve the original USD payment identity before proposing a refund.');
        $parentId=$o->get_parent_id()?(int)$line->get_meta('_dokan_parent_order_item_id'):$line->get_id();$paid=$root->get_item($parentId);
        if(!$paid instanceof \WC_Order_Item_Product||$paid->get_product_id()!==$line->get_product_id()||$paid->get_variation_id()!==$line->get_variation_id())Support::fail('parent','The purchased item cannot be mapped unambiguously to the original charged parent.');
        $quantity=Support::integer($in['quantity']??$r['quantity']);$ordered=(int)$paid->get_quantity();$already=abs((int)$root->get_qty_refunded_for_item($parentId));
        if($quantity>(int)$r['quantity']||$already+$quantity>$ordered)Support::fail('quantity','Choose only unrefunded units in this case.');
        if(empty($v['no_return_needed'])&&(($v['inspection']['state']??'')!=='completed'||$quantity>(int)($v['inspection']['received_quantity']??0)))Support::fail('inspection','Complete inspection or explicitly approve a refund without return first.');
        if(!empty($v['inspection']['discrepancy'])&&empty($v['inspection']['admin_finding']))Support::fail('inspection','Resolve the reported inspection discrepancy.');
        if(($v['resolution_requested']??'')==='manual_exchange'&&empty($in['refund_agreed']))Support::fail('resolution','Record agreement to refund instead of the requested manual exchange.');
        $amount=self::portion(self::cents($paid->get_total()),$ordered,$already,$quantity);$taxes=[];
        foreach($paid->get_taxes()['total']??[]as$tid=>$tax)$taxes[$tid]=self::portion(self::cents($tax),$ordered,$already,$quantity)/100;
        $lines=[$parentId=>['qty'=>$quantity,'refund_total'=>$amount/100,'refund_tax'=>$taxes]];$shipping=0;$fees=0;
        if(!empty($in['include_shipping'])||!empty($in['include_fees'])){
            if(empty($in['allocation_reviewed'])||!trim(Support::text($in['allocation_finding']??'')))Support::fail('allocation','Review original shipping/fee rights and record the deterministic allocation finding.');
            $weights=[];foreach($root->get_items()as$id=>$item)$weights[$id]=max(0,self::cents($item->get_total()));
            foreach(['shipping'=>'include_shipping','fee'=>'include_fees']as$type=>$flag)if(!empty($in[$flag]))foreach($root->get_items($type)as$id=>$extra){
                if((float)$extra->get_total()<0)Support::fail('allocation','Negative fees require separate tax/allocation review.');
                $share=self::weighted(self::cents($extra->get_total()),$weights)[$parentId];$net=self::portion($share,$ordered,$already,$quantity);$et=[];
                foreach($extra->get_taxes()['total']??[]as$tid=>$tax)$et[$tid]=self::portion(self::weighted(self::cents($tax),$weights)[$parentId],$ordered,$already,$quantity)/100;
                $remaining=max(0,self::cents($extra->get_total())-self::cents($root->get_total_refunded_for_item($id,$type)));
                if($net>$remaining)Support::fail('allocation','Original shipping/fee refunds already consumed this allocation.');
                $lines[$id]=['qty'=>0,'refund_total'=>$net/100,'refund_tax'=>$et];if($type==='shipping')$shipping+=$net;else$fees+=$net;
            }
        }
        $total=0;foreach($lines as$l)$total+=self::cents($l['refund_total'])+self::cents(array_sum($l['refund_tax']));
        $plan=['case_id'=>(int)$r['id'],'root_order_id'=>$root->get_id(),'source_order_id'=>$o->get_id(),'source_item_id'=>$line->get_id(),'parent_item_id'=>$parentId,'quantity'=>$quantity,
            'currency'=>'USD','gateway'=>$root->get_payment_method(),'transaction'=>$root->get_transaction_id(),'amount_minor'=>$total,'line_items'=>$lines,'item_minor'=>$amount,
            'shipping_minor'=>$shipping,'fee_minor'=>$fees,'postage_deduction_minor'=>0,'restocking_fee_minor'=>0,
            'allocation_rule'=>'original_discounted_item_value_largest_remainder_then_units_v1','allocation_finding'=>Support::text($in['allocation_finding']??''),
            'refund_agreed'=>!empty($in['refund_agreed']),'charged_snapshot_hash'=>self::chargedHash($root),'original_refunded_minor'=>self::cents($root->get_total_refunded()),'created_at'=>time()];
        if($total<1||$total>self::cents($root->get_remaining_refund_amount()))Support::fail('payment','The remaining original charge cannot cover this proposal.');
        $plan['hash']=Support::hash($plan);return$plan;
    }
    public static function command(int $id,string $action,array $in): array {
        $r=Store::row($id);Access::requireRole($r,['admin']);
        if($action==='refund_prepare')return Store::edit($id,$in,$action,['admin'],static function($r,&$v)use($in){
            if(in_array($v['refund']['state'],['submitting','pending','unknown','succeeded'],true))Support::fail('refund','Reconcile the existing original-payment claim; no second refund will be submitted.');
            $v['refund']=['state'=>'draft','verified_quantity'=>0,'proposal'=>self::proposal($r,$in)];
        });
        if($action==='refund_approve')return Store::edit($id,$in,$action,['admin'],static function($r,&$v)use($in){
            $p=$v['refund']['proposal']??null;
            if(!$p||$v['refund']['state']!=='draft'||empty($in['confirm'])||!hash_equals($p['hash'],(string)($in['proposal_hash']??'')))Support::fail('quote','Review and approve the exact original-payment refund proposal.');
            $v['refund']['state']='approved';$v['refund']['approved_at']=time();$v['refund']['approved_by']=get_current_user_id();$v['refund']['amount_minor']=$p['amount_minor'];$v['refund']['currency']='USD';
        });
        if($action==='refund_execute'){
            if(!Policy::settings()['refund_execution'])Support::fail('paused','Buyer refund execution is paused. No money was sent.',503);
            // Capability remains off until native/concurrent writers and the
            // installed original gateway have passed the separate launch gate.
            if(!Plugin::financialReady())Support::fail('readiness','The original-gateway and competing-writer launch proofs are incomplete. Refund execution remains unavailable.',503);
            $r=Store::edit($id,$in,$action,['admin'],static function($r,&$v)use($in){
                if($v['refund']['state']!=='approved'||empty($in['confirm']))Support::fail('refund','Approve the exact refund before submitting.');
                $p=$v['refund']['proposal'];$root=Support::root(wc_get_order($r['order_id']));
                if((int)round((float)$root->get_total_refunded()*100)!==$p['original_refunded_minor']||!hash_equals($p['charged_snapshot_hash'],self::chargedHash($root)))Support::fail('changed','Another refund changed this payment. Review a new proposal.');
                $v['refund']['state']='submitting';$v['refund']['claim_id']='return-'.$r['id'].'-'.substr($p['hash'],0,24);
            });
            $v=Store::state($r);$p=$v['refund']['proposal'];$root=wc_get_order($p['root_order_id']);
            try{$c=\KREV_Refunds::execute_reviewed($root,$p,$v['refund']['claim_id']);}
            catch(\Throwable $e){$c=['state'=>'unknown','needs_attention'=>true];}
            return self::projectClaim($id,$c);
        }
        Support::fail('refund','Unsupported refund command.',422);
    }
    public static function projectClaim(int $id,array $claim): array {
        $r=Store::row($id);return Store::locked(wc_get_order($r['order_id']),static function()use($id,$claim){
            $r=Store::row($id);$v=Store::state($r);$v['refund']=array_merge($v['refund'],array_intersect_key($claim,array_flip(['state','woo_refund_id','manual_record_only','needs_attention','provider_receipt','verified_at'])));
            if(($claim['state']??'')==='succeeded')$v['refund']['verified_quantity']=$v['refund']['proposal']['quantity'];
            return Store::save($r,$v,'refund_status');
        });
    }
    /** Trusted verified gateway adapter only. A browser cannot assert success.
     * Production adapters remain unavailable until their exact schema is audited. */
    public static function reconcileVerified(int $id,array $proof): array {
        $r=Store::row($id);$v=Store::state($r);$f=$v['refund'];$p=$f['proposal']??null;
        if(!$p||!in_array($f['state'],['submitting','pending','unknown'],true))Support::fail('refund','No pending refund claim needs reconciliation.');
        if(empty($proof['verified'])||!in_array($proof['status']??'',['pending','succeeded','failed'],true)||($proof['transaction']??'')!==$p['transaction']||($proof['gateway']??'')!==$p['gateway']||($proof['amount_minor']??0)!==$p['amount_minor']||($proof['currency']??'')!=='USD'||!preg_match('/^[a-f0-9]{64}$/D',$proof['evidence_hash']??'')||empty($proof['refund_id']))Support::fail('proof','Original-gateway refund proof does not match this reservation.');
        $root=wc_get_order($p['root_order_id']);$state=$proof['status']==='failed'?'failed_verified':$proof['status'];
        $claim=\KREV_Refund_Authority::update($root,$f['claim_id'],['state'=>$state,'provider_receipt'=>Support::text($proof['refund_id'],150),'verified_at'=>time(),'evidence_hash'=>$proof['evidence_hash'],'needs_attention'=>$state!=='succeeded']);
        $claim['state']=$proof['status'];$row=self::projectClaim($id,$claim);if($proof['status']==='succeeded'&&!empty($claim['woo_refund_id'])){(new \KR_Connect_Refund())->queue($root->get_id(),(int)$claim['woo_refund_id']);}return$row;
    }
}
