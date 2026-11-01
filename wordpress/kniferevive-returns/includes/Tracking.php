<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

final class Tracking {
    public static function link(array $v): ?string {
        $l=$v['label'];if(empty($l['tracking'])||empty($l['carrier']))return null;
        return \KREV_Shipment_Services::url(strtolower($l['carrier']),$l['tracking'])?:null;
    }
    public static function command(int $id,array $in): array {
        return Store::edit($id,$in,'tracking_review',['admin'],static function($r,&$v)use($in){
            $state=sanitize_key($in['state']??'');$allowed=['carrier_accepted','in_transit','carrier_delivered','exception','lost_review'];
            $at=filter_var($in['observed_at']??0,FILTER_VALIDATE_INT);$note=Support::text($in['finding']??'');
            if(!in_array($state,$allowed,true)||!$at||$at>time()+60||$at<(int)$v['created_at']||!trim($note)||empty($in['reviewed_evidence'])||($v['label']['state']??'')!=='ready')Support::fail('tracking','Review the exact carrier event and timestamp for this issued return label.',422);
            $old=$v['shipment'];if(($old['observed_at']??0)>$at)Support::fail('tracking','An older carrier observation cannot replace newer evidence.');
            if(in_array($old['state'],['carrier_delivered','receipt_confirmed'],true)&&!in_array($state,['carrier_delivered','exception','lost_review'],true))Support::fail('tracking','Reconcile a delivery correction through Return Desk.');
            $v['shipment']=array_merge($old,['state'=>$state,'observed_at'=>$at,'provenance'=>'admin_reviewed_carrier_evidence','finding'=>$note,'reviewer'=>get_current_user_id(),'carrier_acceptance_verified'=>$state!=='lost_review']);
            if($state==='carrier_delivered'&&!isset($v['deadlines']['inspection'])&&($v['inspection']['state']??'')==='not_due'){$v['inspection']['state']='due';$v['deadlines']['inspection']=Policy::deadline($at,'inspection',Policy::settings()['inspection_business_days'],true);}
            if(in_array($state,['carrier_accepted','in_transit','carrier_delivered'],true))unset($v['deadlines']['handover']);
            // Incoming delivery never updates the original order's outbound
            // delivered_at, fulfillment, completion, payout readiness or stock.
        });
    }
}
