<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

use KnifeRevive\ShipKit\Problem;

/** One case store: existing Seller Orders rows and versioned detail JSON. */
final class Store {
    public static function row(int $id): array {
        $r=\KREV_Returns::get($id);
        if (!$r) Support::fail('not_found','Return not found.',404);
        return $r;
    }
    public static function state(array $r): array {
        $d=\KREV_Return_Workflow::details($r['id']);
        if (!empty($d['data']['v2'])) return $d['data']['v2'];
        $map=['requested'=>'submitted','approved'=>'approved','rejected'=>'declined','closed'=>'resolved','escalated'=>'admin_review','refunded'=>'resolved'];
        return [
            'schema'=>2,'legacy'=>true,'case'=>$map[$r['status']]??'admin_review',
            'label'=>['state'=>'not_needed'],'shipment'=>['state'=>$r['return_tracking']?'buyer_reported_handover':'awaiting_handover','carrier'=>$r['return_carrier'],'tracking'=>$r['return_tracking'],'date'=>$r['date_shipped'],'provenance'=>'legacy_report_unverified'],
            'inspection'=>['state'=>$r['received_quantity']?'due':'not_due'],
            'refund'=>['state'=>$r['status']==='refunded'?'unknown':'draft','verified_quantity'=>0],
            'postage'=>['state'=>'responsibility_pending','responsible_party'=>null],
            'messages'=>[['id'=>'legacy-request','at'=>strtotime($r['created_at'].' UTC')?:0,'actor'=>(int)$r['customer_id'],'role'=>'buyer','visibility'=>'shared','body'=>esc_html($r['explanation'])]],'evidence'=>[],'deadlines'=>[],'commands'=>[],
            'review_reasons'=>['Legacy case requires review; its approval, label and refund evidence were not invented.'],
        ];
    }
    public static function locked(\WC_Order $order, callable $work): mixed {
        $result=\KREV_Order_Cancellations::with_lock(Support::root($order),static function () use($work) {
            return \KREV_Return_Workflow::atomic(static function() use($work) {
                try { return $work(); } catch (Problem $e) { return Support::error($e); }
            });
        });
        if (is_wp_error($result)) throw new Problem('return_storage',$result->get_error_message(),(int)($result->get_error_data()['status']??503));
        return $result;
    }
    public static function save(array $r, array $v, string $event, array $fields=[]): array {
        global $wpdb;
        $d=\KREV_Return_Workflow::details($r['id']);$data=$d['data'];$data['v2']=$v;
        $map=['submitted'=>'requested','seller_response_due'=>'requested','awaiting_evidence'=>'requested','admin_review'=>'escalated','approved'=>'approved','declined'=>'rejected','withdrawn'=>'closed','resolved'=>'closed'];
        $status=$map[$v['case']]??$r['status'];
        $fields=array_intersect_key($fields,array_flip(['return_carrier','return_tracking','date_shipped','received_date','received_quantity','condition_note','rejection_reason']));
        if ($wpdb->update(\KREV_Returns::table(),$fields+['status'=>$status,'updated_at'=>gmdate('Y-m-d H:i:s')],['id'=>$r['id']])===false || !\KREV_Return_Workflow::store($r['id'],$data,$event,$r['status'],$status)) Support::fail('storage','Return changes could not be committed.',503);
        if (!Jobs::enqueue((int)$r['id'],$event)) Support::fail('storage','The return notification could not be queued with its change.',503);
        return self::row((int)$r['id']);
    }
    public static function edit(int $id, array $input, string $command, array $roles, callable $work): array {
        $row=self::row($id);Access::requireRole($row,$roles);$order=wc_get_order($row['order_id']);
        if (!$order) Support::fail('not_found','Order not found.',404);
        return self::locked($order,static function () use($id,$input,$command,$roles,$work) {
            $r=self::row($id);$role=Access::requireRole($r,$roles);$d=\KREV_Return_Workflow::details($id);$v=self::state($r);
            $key=Support::key($input['request_key']??'');$identity=get_current_user_id()?:Guest::identity(Support::root(wc_get_order($r['order_id'])));
            $index=hash('sha256',$identity.':'.$key);$payload=$input;unset($payload['_wpnonce'],$payload['request_key']);
            $hash=Support::hash([$command,$payload]);
            if (isset($v['commands'][$index])) {
                if ($v['commands'][$index]['hash']!==$hash) Support::fail('replay','This command was already used for different details.');
                return $r;
            }
            if ((int)($input['expected_revision']??-1)!==$d['revision']) Support::fail('changed','This return changed. Reload and review its current details.');
            if (count($v['commands'])>=500) Support::fail('review','This case needs an archive review before further edits.');
            $fields=$work($r,$v,$role)??[];
            $v['commands'][$index]=['hash'=>$hash,'actor'=>get_current_user_id(),'at'=>time()];
            return self::save($r,$v,$command,$fields);
        });
    }
}
