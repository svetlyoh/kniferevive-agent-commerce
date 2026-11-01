<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

final class Cases {
    public const REASONS=[
        'mismatch'=>'Item not as described','wrong_item'=>'Wrong item received',
        'damage'=>'Damaged in transit','change_of_mind'=>'Changed my mind',
        'unsafe'=>'Item may be unsafe','authenticity'=>'Authenticity concern',
        'missing'=>'Item has not arrived / missing quantity','cancel'=>'Cancel before shipment','other'=>'Other',
    ];
    /** One case per purchased line: a multi-seller selection is partitioned
     * atomically, with each case retaining its own seller/destination/quantity. */
    public static function create(\WC_Order $order, array $in): array {
        if (!Access::buyer($order)) Support::fail('forbidden','Only the verified purchasing customer can open this request.',403);
        if (!Policy::settings()['new_requests']) Support::fail('paused','New return requests are paused. Contact KnifeRevive for help; existing cases remain available.',503);
        $lines=$in['items']??[['item_id'=>$in['order_item_id']??0,'quantity'=>$in['quantity']??1]];
        if (!is_array($lines) || !$lines || count($lines)>20) Support::fail('items','Choose between one and twenty purchased lines.',422);
        $reason=sanitize_key($in['reason']??'');$notes=Support::note($in['notes']??$in['explanation']??'');
        $resolution=sanitize_key($in['resolution']??'refund');$request=Support::key($in['request_key']??'');
        if (!isset(self::REASONS[$reason]) || !trim(wp_strip_all_tags($notes)) || !in_array($resolution,['refund','manual_exchange','help'],true)) Support::fail('input','Choose a reason and explain what happened.',422);
        return Store::locked($order,static function () use($order,$lines,$reason,$notes,$resolution,$request) {
            global $wpdb;
            $fresh=wc_get_order($order->get_id());
            if (!$fresh || !Access::buyer($fresh)) Support::fail('forbidden','Order access changed.',403);
            $results=[];$seen=[];
            foreach ($lines as $line) {
                if (!is_array($line)) Support::fail('items','Choose a purchased line.',422);
                $id=Support::integer($line['item_id']??0);$quantity=Support::integer($line['quantity']??0);
                if (isset($seen[$id])) Support::fail('items','Choose each purchased line only once.',422);
                $seen[$id]=true; $item=$fresh->get_item($id);
                if (!$item instanceof \WC_Order_Item_Product) Support::fail('item','The purchased line was not found.',404);
                $identity=get_current_user_id()?:Guest::identity(Support::root($fresh));
                $key=hash('sha256',$fresh->get_id().':'.$identity.':'.$request.':'.$id);
                $fingerprint=Support::hash([$quantity,$reason,$notes,$resolution]);
                $old=$wpdb->get_var($wpdb->prepare('SELECT return_id FROM '.\KREV_Return_Workflow::table().' WHERE request_key=%s',$key));
                if ($old) {
                    $d=\KREV_Return_Workflow::details($old);
                    if (($d['data']['v2']['request_hash']??'')!==$fingerprint) Support::fail('replay','This submission key belongs to different details.');
                    $results[]=Store::row((int)$old);continue;
                }
                $policy=Policy::resolve($fresh,$item);$seller=\KREV_Orders_Reader::item_owner($item);
                $help=in_array($reason,['missing','cancel'],true)||$policy['physical']===false||!Support::root($fresh)->is_paid();
                if (!$seller) {$help=true;$policy['review_reasons'][]='Seller-at-sale attribution is missing; Return Desk must establish it from purchase-time evidence.';}
                $max=$help?(int)$item->get_quantity():$policy['quantity'];
                if ($quantity>$max) Support::fail('quantity','Another return or refund already covers these units. Choose the remaining quantity.',422);
                $row=['order_id'=>$fresh->get_id(),'order_item_id'=>$id,'seller_id'=>$seller,
                    'customer_id'=>Support::root($fresh)->get_customer_id(),'quantity'=>$quantity,
                    'status'=>'requested','policy_snapshot'=>wp_json_encode($policy['policy']),
                    'reason'=>self::REASONS[$reason],'explanation'=>wp_strip_all_tags($notes),
                    'requested_resolution'=>$resolution,'evidence'=>'[]','condition_note'=>'','rejection_reason'=>'',
                    'created_at'=>gmdate('Y-m-d H:i:s'),'updated_at'=>gmdate('Y-m-d H:i:s')];
                if ($wpdb->insert(\KREV_Returns::table(),$row)===false) Support::fail('storage','The return could not be saved.',503);
                $caseId=(int)$wpdb->insert_id;$now=time();
                $v=['schema'=>2,'legacy'=>false,'kind'=>$help?'order_help':'return','request_hash'=>$fingerprint,
                    'case'=>'seller_response_due','reason_claimed'=>$reason,'finding'=>null,
                    'resolution_requested'=>$resolution,'created_at'=>$now,'seller_at_sale'=>$seller,
                    'policy'=>$policy,'addresses'=>[],'label'=>['state'=>'not_needed'],
                    'shipment'=>['state'=>'awaiting_handover'],'inspection'=>['state'=>'not_due'],
                    'refund'=>['state'=>'draft','verified_quantity'=>0],
                    'postage'=>['state'=>'responsibility_pending','responsible_party'=>null],
                    'deadlines'=>['seller_response'=>Policy::deadline($now,'seller_response',Policy::settings()['response_hours'])],
                    'messages'=>[['id'=>bin2hex(random_bytes(16)),'at'=>$now,'actor'=>get_current_user_id(),'role'=>'buyer','visibility'=>'shared','body'=>$notes]],
                    'evidence'=>[],'commands'=>[], 'review_reasons'=>$policy['review_reasons']];
                if ($help) $v['review_reasons'][]='Order-help case: no instruction to return an item that was not received.';
                $row=Store::row($caseId);$saved=Store::save($row,$v,'submitted');
                if ($wpdb->update(\KREV_Return_Workflow::table(),['request_key'=>$key],['return_id'=>$caseId])===false) Support::fail('storage','Submission identity could not be saved.',503);
                $results[]=$saved;
            }
            return $results;
        });
    }
    public static function command(int $id, string $action, array $in): array {
        $roles=match($action) {
            'message','help'=>['buyer','seller','recipient','admin'],
            'seller_response'=>['seller','recipient'],
            'confirm_origin','handover','withdraw'=>['buyer'],
            'confirm_destination'=>['seller','recipient','admin'],
            default=>['admin'],
        };
        return Store::edit($id,$in,$action,$roles,static function ($r,&$v,$role) use($action,$in) {
            $fields=[];$now=time();
            if(!in_array($action,['message','help','extend_deadline'],true)&&in_array($v['refund']['state'],['submitting','pending','unknown','succeeded'],true))Support::fail('refund','Reconcile the original-payment claim before changing this case’s decision or contents.');
            if(!empty($v['legacy'])&&!in_array($action,['message','help','adopt_legacy'],true))Support::fail('legacy','Review and adopt the preserved legacy case before changing its workflow.');
            switch ($action) {
                case 'message': case 'help': case 'seller_response':
                    $note=Support::note($in['note']??'');
                    if (!trim(wp_strip_all_tags($note))) Support::fail('note','Enter a message.',422);
                    $visibility=$in['visibility']??'shared';
                    if (!in_array($visibility,['shared','admin_private'],true)||($visibility==='admin_private'&&$role!=='admin')) Support::fail('visibility','Private support notes require Return Desk access.',403);
                    $v['messages'][]=['id'=>bin2hex(random_bytes(16)),'at'=>$now,'actor'=>get_current_user_id(),'role'=>$role,'visibility'=>$visibility,'body'=>$note];
                    if ($action==='help') {$v['needs_help']=true;$v['help_requested_at']=$now;}
                    if ($action==='seller_response') {
                        $choice=sanitize_key($in['choice']??'review');if(!in_array($choice,['agree','alternative','review'],true))Support::fail('response','Choose a supported response.',422);
                        $v['seller_response']=['choice'=>$choice,'at'=>$now];$v['case']='admin_review';unset($v['deadlines']['seller_response']);
                    }
                    break;
                case 'confirm_origin': case 'confirm_destination':
                    if (empty($in['confirm'])) Support::fail('address','Confirm the address for this return.');
                    if (in_array($v['label']['state'],['spending_approved','purchasing','purchase_unknown','ready','void_requested'],true)) Support::fail('address','An issued or reserved label cannot be rerouted. Ask Return Desk for a reviewed replacement.');
                    if($v['case']!=='approved'||!empty($v['no_return_needed']))Support::fail('address','Return Desk must approve the physical return first.');
                    $side=$action==='confirm_origin'?'origin':'destination';
                    $v['addresses'][$side]=['value'=>Support::address($in['address']??null),'confirmed_by'=>get_current_user_id(),'confirmed_at'=>$now];
                    $v['label']=['state'=>'pending_address'];
                    break;
                case 'adopt_legacy':
                    $old=\KREV_Return_Workflow::details($r['id'])['data'];$o=wc_get_order($r['order_id']);$i=$o->get_item($r['order_item_id']);$finding=Support::text($in['finding']??'');
                    if(empty($v['legacy'])||!$i||isset($old['refund_claim'])||$r['status']==='refunded'||!trim($finding))Support::fail('legacy','Preserve financial history and reconcile any prior refund claim before adoption.');
                    $policy=Policy::resolve($o,$i);$v=array_merge($v,['legacy'=>false,'kind'=>'return','case'=>'admin_review','created_at'=>strtotime($r['created_at'].' UTC')?:time(),'reason_claimed'=>'other','seller_at_sale'=>(int)$r['seller_id'],'policy'=>$policy,'addresses'=>[],'resolution_requested'=>$r['requested_resolution'],'finding'=>$finding,'legacy_adoption'=>['actor'=>get_current_user_id(),'at'=>time(),'finding'=>$finding]]);
                    $v['messages'][]=['id'=>bin2hex(random_bytes(16)),'at'=>time(),'actor'=>0,'role'=>'buyer','visibility'=>'shared','body'=>esc_html($r['explanation'])];
                    break;
                case 'replace_label':
                    if(!in_array($v['label']['state'],['voided','not_needed'],true)||empty($in['finding']))Support::fail('replacement','Reconcile/void the old label and record why replacement is needed.');
                    $v['label_history'][]=$v['label']+['replacement_finding'=>Support::text($in['finding']),'archived_at'=>time()];$v['label']=['state'=>'pending_address'];unset($v['deadlines']['handover']);
                    break;
                case 'review':
                    $decision=sanitize_key($in['decision']??'');$finding=Support::text($in['finding']??'');
                    if (!in_array($decision,['approved','declined','no_return_needed'],true)||!trim($finding)) Support::fail('decision','Record the decision and its evidence-based finding.',422);
                    if (in_array($v['label']['state'],['purchasing','purchase_unknown','ready','void_requested'],true)) Support::fail('label','Reconcile the existing label before changing this decision.');
                    if ($decision==='approved' && (($v['kind']??'return')==='order_help'||in_array($v['reason_claimed']??'',['unsafe','authenticity'],true))) Support::fail('safety','Use a documented manual resolution for non-delivery, unsafe goods or authenticity concerns; do not issue an ordinary return label.');
                    if ($decision==='approved' && empty($in['reviewed_policy'])) Support::fail('policy','Review the purchase-time policy and any missing historical evidence.');
                    $o=wc_get_order($r['order_id']);$item=$o->get_item($r['order_item_id']);if($decision!=='declined'&&(!$item||(int)$r['quantity']>Policy::available($o,$item,(int)$r['id'])))Support::fail('quantity','Other returns or refunds now cover these units. Review their allocation.');
                    $v['finding']=$finding;$v['decision']=['at'=>$now,'actor'=>get_current_user_id(),'choice'=>$decision,'policy_reviewed'=>!empty($in['reviewed_policy'])];
                    $v['case']=$decision==='declined'?'declined':'approved';$v['no_return_needed']=$decision==='no_return_needed';
                    $v['label']=['state'=>$decision==='approved'?'pending_address':'not_needed'];
                    if ($decision==='approved') $v['deadlines']['address_confirmation']=Policy::deadline($now,'address_confirmation',Policy::settings()['address_hours']);
                    if ($decision==='declined') $fields['rejection_reason']=$finding;
                    break;
                case 'responsibility':
                    $party=sanitize_key($in['party']??'');$finding=Support::text($in['finding']??'');
                    if (!in_array($party,['platform','seller','buyer','pending'],true)||!trim($finding)) Support::fail('responsibility','Record a separate postage finding and responsible party.',422);
                    if ($party==='buyer' && empty($in['buyer_agreement'])) Support::fail('agreement','Buyer payment or refund deduction needs a disclosed policy and a separately recorded exact agreement.');
                    if (!empty($v['postage']['iou_id'])) Support::fail('responsibility','Reconcile the existing postage assessment before replacing its responsibility.');
                    $v['postage']=['state'=>$party==='platform'?'platform_covered':'responsibility_pending','responsible_party'=>$party,'finding'=>$finding,'decided_by'=>get_current_user_id(),'decided_at'=>$now,'buyer_agreement'=>$party==='buyer'?Support::text($in['buyer_agreement']):null];
                    break;
                case 'withdraw':
                    if (in_array($v['label']['state'],['spending_approved','purchasing','purchase_unknown','ready','void_requested'],true)||in_array($v['refund']['state'],['approved','submitting','pending','unknown','succeeded'],true)) Support::fail('withdraw','Contact Return Desk to resolve the existing label or refund first.');
                    $v['case']='withdrawn';$v['withdrawal_note']=Support::text($in['note']??'Withdrawn by buyer');
                    break;
                case 'handover':
                    if ($v['case']!=='approved'||!empty($v['no_return_needed'])||empty($in['confirm'])||($v['label']['state']??'')!=='ready') Support::fail('handover','Confirm actual handover after the approved label is available.');
                    $date=Support::text($in['date']??'',10);
                    if (!\KREV_Shipment_Services::date($date)||$date>wp_date('Y-m-d')) Support::fail('date','Enter the actual handover date.',422);
                    $v['shipment']=['state'=>'buyer_reported_handover','reported_at'=>$now,'date'=>$date,'provenance'=>'buyer_report','carrier_acceptance_verified'=>false];
                    unset($v['deadlines']['handover']);
                    break;
                case 'physical_review':
                    $finding=Support::text($in['finding']??'');if(!trim($finding)||($v['policy']['physical']??null)===false)Support::fail('physical','Only missing sold-item evidence can be reviewed here; services are not return parcels.');
                    $v['physical_review']=['finding'=>$finding,'actor'=>get_current_user_id(),'at'=>time()];break;
                case 'assign_recipient':
                    $recipient=absint($in['seller_id']??0);$why=Support::text($in['finding']??'');$user=get_userdata($recipient);
                    if(!$user||!in_array('seller',$user->roles,true)||!trim($why))Support::fail('recipient','Select a registered return recipient and record the Dispatch/fulfillment evidence.',422);
                    if(!in_array($v['label']['state'],['not_needed','pending_address'],true))Support::fail('recipient','Reconcile the label before changing its recipient.');
                    $v['recipient_seller_id']=$recipient;$v['recipient_assignment']=['at'=>$now,'actor'=>get_current_user_id(),'reason'=>$why];unset($v['addresses']['destination']);
                    break;
                case 'resolve':
                    if(in_array($v['refund']['state'],['submitting','pending','unknown'],true)||in_array($v['label']['state'],['purchasing','purchase_unknown','void_requested'],true))Support::fail('resolve','Reconcile uncertain financial work before closing.');
                    $why=Support::text($in['finding']??'');if(!trim($why))Support::fail('resolve','Explain the final resolution.',422);
                    $v['case']='resolved';$v['resolution_finding']=$why;
                    break;
                case 'extend_deadline':
                    $kind=sanitize_key($in['kind']??'');$deadline=$v['deadlines'][$kind]??null;$due=(int)($in['due_at']??0);$why=Support::text($in['reason']??'');
                    if (!$deadline||!trim($why)||$due<=($deadline['due_at']??0)||$due>$now+365*DAY_IN_SECONDS) Support::fail('deadline','Choose an extension and explain why.',422);
                    $deadline['extensions'][]=['old'=>$deadline['due_at'],'new'=>$due,'at'=>$now,'actor'=>get_current_user_id(),'reason'=>$why];$deadline['due_at']=$due;$v['deadlines'][$kind]=$deadline;
                    break;
                default: Support::fail('command','Unsupported return action.',422);
            }
            return $fields;
        });
    }
    public static function project(array $r): array {
        $role=Access::view($r);$v=Store::state($r);$d=\KREV_Return_Workflow::details($r['id']);$o=wc_get_order($r['order_id']);$i=$o?->get_item($r['order_item_id']);
        $messages=array_values(array_filter($v['messages']??[],static fn($m)=>$role==='admin'||$m['visibility']==='shared'));
        $evidence=array_values(array_filter($v['evidence']??[],static fn($m)=>$role==='admin'||$m['visibility']==='shared'));
        foreach ($evidence as &$file) unset($file['storage'],$file['body'],$file['original']);unset($file);
        $out=['id'=>(int)$r['id'],'order_id'=>(int)$r['order_id'],'order_number'=>$o?->get_order_number(),
            'item'=>['id'=>(int)$r['order_item_id'],'name'=>$i?->get_name()??$d['data']['item_name']??'Purchased item','quantity'=>(int)$r['quantity']],
            'seller_id'=>(int)$r['seller_id'],'role'=>$role,'revision'=>$d['revision'],'case'=>$v['case'],
            'kind'=>$v['kind']??'return','reason'=>$r['reason'],'requested_resolution'=>$r['requested_resolution'],
            'finding'=>$v['case']==='resolved'?($v['resolution_finding']??$v['finding']??null):($v['finding']??null),'label'=>array_intersect_key($v['label'],array_flip(['state','carrier','service','cost_minor','tracking','document_id','available_at','attempt_id'])),
            'shipment'=>$v['shipment'],'inspection'=>$v['inspection'],
            'refund'=>array_intersect_key($v['refund'],array_flip(['state','amount_minor','currency','verified_quantity','manual_record_only','needs_attention'])),
            'postage'=>$role==='admin'?$v['postage']:array_intersect_key($v['postage'],array_flip(['state','responsible_party','finding','iou_id','amount_minor','due_minor','automatic_deduction','game_plan'])),'deadlines'=>$v['deadlines'],'messages'=>$messages,'evidence'=>$evidence,
            'review_reasons'=>$v['review_reasons']??[],'needs_help'=>$v['needs_help']??false,
            'policy'=>json_decode($r['policy_snapshot'],true)?:[],
            'url'=>Screens::caseUrl((int)$r['id']), 'next_step'=>self::next($v),
        ];
        if ($role==='admin'||$role==='buyer'||$v['case']==='approved') $out['addresses']=$v['addresses']??[];
        if ($role==='admin') {$out['audit']=$d['audit'];$out['financial_review']=$v['refund'];}
        return $out;
    }
    public static function next(array $v): string {
        if (!empty($v['needs_help'])) return 'KnifeRevive is reviewing your request for help.';
        if ($v['case']==='resolved') return 'This case is resolved. Read the recorded outcome or contact support if you need help.';
        if ($v['case']==='declined') return 'Read the decision. You can ask KnifeRevive to review it.';
        if ($v['case']==='withdrawn') return 'This request was withdrawn. Contact support if you need help.';
        if (($v['refund']['state']??'')==='succeeded') return 'Refund sent through the original payment method. Bank timing may vary.';
        if (in_array($v['refund']['state']??'',['unknown','failed'],true)) return 'The refund needs attention. Do not start a second refund.';
        if (($v['refund']['state']??'')==='approved') return 'Your refund is approved. It has not been sent yet.';
        if (in_array($v['refund']['state']??'',['submitting','pending'],true)) return 'Your refund is processing through the original payment method. Payment has not yet been confirmed.';
        if ($v['case']!=='approved') return 'Your request is being reviewed. Do not send the item yet.';
        if (!empty($v['no_return_needed'])) return 'No physical return is needed. Your refund remains under separate review.';
        if (($v['inspection']['state']??'')==='discrepancy_reported') return 'The returned contents need a review before a refund decision.';
        if (($v['inspection']['state']??'')==='completed') return 'Return received and inspected. Refund decision is separate.';
        if (in_array($v['shipment']['state']??'',['carrier_delivered','receipt_confirmed'],true)) return 'Return received — contents inspection is next. Your refund stays under separate review.';
        if (($v['shipment']['state']??'')==='buyer_reported_handover') return 'Drop-off reported. Awaiting carrier acceptance.';
        if (in_array($v['shipment']['state']??'',['carrier_accepted','in_transit'],true)) return 'Your return is on its way. Keep your drop-off receipt.';
        if (($v['label']['state']??'')==='ready') return 'Print or download your return label. Keep your drop-off receipt.';
        return 'Return approved — label being prepared. Contact support if you need help.';
    }
}
