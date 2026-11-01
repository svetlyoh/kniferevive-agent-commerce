<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

final class Inspection {
    public static function command(int $id,string $action,array $in): array {
        $roles=$action==='restock'?['admin']:['seller','recipient','admin'];
        return Store::edit($id,$in,$action,$roles,static function($r,&$v,$role)use($action,$in){
            if($v['case']!=='approved'||!empty($v['no_return_needed']))Support::fail('inspection','A physical return must be approved.');
            if($action==='receipt'){
                if(empty($in['confirm'])||!trim(Support::text($in['note']??'')))Support::fail('receipt','Confirm actual receipt and record what arrived.');
                if(($v['inspection']['state']??'')==='completed')Support::fail('receipt','Completed inspection cannot be overwritten by a receipt update.');
                $v['shipment']=array_merge($v['shipment'],['state'=>'receipt_confirmed','received_at'=>time(),'received_by'=>get_current_user_id(),'receipt_note'=>Support::text($in['note']),'provenance'=>'recipient_report']);
                $v['inspection']['state']='due';unset($v['deadlines']['handover']);
                $v['deadlines']['inspection']=Policy::deadline(time(),'inspection',Policy::settings()['inspection_business_days'],true);return[];
            }
            if($action==='inspection'){
                if(($v['inspection']['state']??'')==='completed'||!empty($v['inspection']['restocked_quantity'])||in_array($v['refund']['state'],['approved','submitting','pending','unknown','succeeded'],true))Support::fail('inspection','Completed inspection or a refund/restock claim needs an explicit correction review; it cannot be overwritten.');
                if(!in_array($v['shipment']['state'],['carrier_delivered','receipt_confirmed'],true))Support::fail('inspection','Record receipt or reviewed delivery evidence first.');
                $q=Support::integer($in['received_quantity']??0);$saleable=filter_var($in['saleable_quantity']??0,FILTER_VALIDATE_INT);
                if($q>(int)$r['quantity']||$saleable===false||$saleable<0||$saleable>$q||empty($in['confirm'])||!trim(Support::text($in['condition']??'')))Support::fail('inspection','Record received quantity, condition and saleable units after examining the contents.',422);
                $discrepancy=!empty($in['discrepancy'])||$q<(int)$r['quantity'];
                $v['inspection']=['state'=>$discrepancy?'discrepancy_reported':'accepted','received_quantity'=>$q,'saleable_quantity'=>$saleable,'condition'=>Support::text($in['condition']),
                    'identifier'=>Support::text($in['identifier']??'',200),'missing_components'=>Support::text($in['missing_components']??'',1000),'discrepancy'=>$discrepancy,'inspected_by'=>get_current_user_id(),'at'=>time()];
                if($role==='admin'&&!$discrepancy)$v['inspection']['state']='completed';
                unset($v['deadlines']['inspection']);return['received_quantity'=>$q,'received_date'=>wp_date('Y-m-d'),'condition_note'=>$v['inspection']['condition']];
            }
            if($action==='inspection_review'){
                if($role!=='admin'||!in_array($v['inspection']['state'],['accepted','discrepancy_reported','admin_review'],true)||!trim(Support::text($in['finding']??'')))Support::fail('inspection','Return Desk must review the inspection findings.');
                $v['inspection']['state']='completed';$v['inspection']['admin_finding']=Support::text($in['finding']);$v['inspection']['reviewed_by']=get_current_user_id();$v['inspection']['reviewed_at']=time();return[];
            }
            if($action==='restock'){
                if($v['inspection']['state']!=='completed'||empty($in['confirm']))Support::fail('restock','Complete inspection and explicitly approve saleable restocking.');
                $o=wc_get_order($r['order_id']);$item=$o->get_item($r['order_item_id']);$product=$item?->get_product();$q=Support::integer($in['quantity']??0);$done=(int)($v['inspection']['restocked_quantity']??0);
                if(!$product||!$product->managing_stock()||$q+$done>(int)$v['inspection']['saleable_quantity']||$done>0)Support::fail('restock','Restock only verified saleable units once; a missing or unmanaged product needs manual inventory review.');
                $owner=(int)get_post_field('post_author',$product->get_parent_id()?:$product->get_id());
                if($owner!==(int)$r['seller_id']||!empty($v['recipient_seller_id'])&&$v['recipient_seller_id']!==(int)$r['seller_id'])Support::fail('restock','Dispatch or changed product ownership needs a separate inventory allocation review.');
                $stock=wc_update_product_stock($product,$q,'increase');if($stock===false||$stock===null)Support::fail('restock','The stock update could not be confirmed.');
                $v['inspection']['restocked_quantity']=$q;$v['inspection']['restocked_at']=time();$v['inspection']['restocked_by']=get_current_user_id();return[];
            }
            Support::fail('inspection','Unsupported inspection action.',422);
        });
    }
}
