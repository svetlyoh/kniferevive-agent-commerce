<?php
namespace KnifeRevive\Returns;
defined('ABSPATH') || exit;

final class Policy {
    public static function settings(): array {
        return (array)get_option('kr_returns_policy',[]) + [
            'new_requests'=>false, 'label_issuance'=>false, 'refund_execution'=>false,
            'return_holds'=>false, 'first_return_free'=>false, 'workers'=>true,
            'label_budget_minor'=>0, 'label_limit_minor'=>0, 'version'=>'unconfigured',
            'response_hours'=>48, 'address_hours'=>24, 'handover_days'=>7,
            'inspection_business_days'=>2, 'timezone'=>'America/Los_Angeles',
            'business_weekdays'=>[1,2,3,4,5], 'holidays'=>[], 'return_window_anchor'=>null,
        ];
    }
    /** Native Woo checkout hook supplies item, cart key, values, order. */
    public static function checkoutSnapshot(\WC_Order_Item_Product $item, string $cartKey, array $values, \WC_Order $order): void { self::snapshot($item,$values,$order); }
    public static function snapshot(\WC_Order_Item_Product $item, array $values, \WC_Order $order): void {
        if ($item->get_meta('_kr_return_policy_v2',true)) return;
        $product=$values['data']??$item->get_product();
        if (!$product instanceof \WC_Product) return;
        $old=\KREV_Returns::policy_snapshot($item);
        // A policy must actually be selected/disclosed by the native checkout.
        // Never substitute today's product taxonomy for missing sold terms.
        if (!$old) return;
        $owner=(int)($item->get_meta('_krev_purchased_seller_id') ?: get_post_field('post_author',$product->get_parent_id()?:$product->get_id()));
        $p=self::settings();
        $snapshot=[
            'schema'=>1,'captured_at'=>time(),'policy'=>$old,'policy_hash'=>Support::hash($old),
            'policy_version'=>$old['version']??$old['captured_at']??null,
            'seller_at_sale'=>$owner,'market'=>'US','currency'=>$order->get_currency(),
            'anchor'=>$old['anchor']??$p['return_window_anchor'],
            'accepted_reasons'=>$old['accepted_reasons']??null,
            'postage_responsibility'=>$old['postage_responsibility']??null,
            'original_shipping_rule'=>$old['original_shipping_rule']??null,
            'checkout_disclosure_version'=>$old['checkout_disclosure_version']??null,
            'listing'=>[
                'product_id'=>$product->get_id(),'name'=>$item->get_name(),
                'description'=>wp_kses_post($product->get_description()),
                'short_description'=>wp_kses_post($product->get_short_description()),
                'photos'=>array_values(array_filter(array_merge([$product->get_image_id()],$product->get_gallery_image_ids()))),
                'condition'=>$product->get_attribute('pa_item-condition')?:null,
                'sku'=>$product->get_sku()?:null,
            ],
        ];
        $contract=(array)get_option('kr_returns_policy_contract',[]);
        // Disclosure must have been recorded by the native checkout UI. Missing
        // disclosure (including older orders) must retain the unknown anchor.
        $disclosed=$values['_kr_return_contract_version']??'';
        $created=$order->get_date_created()?->getTimestamp()??0;
        $snapshot=Contract::apply($snapshot,$contract,$created,is_string($disclosed)?$disclosed:'');
        $item->add_meta_data('_kr_return_policy_v2',$snapshot,true);
    }
    public static function processed(\WC_Order $order): void {
        if(!$order->get_date_created()||$order->get_date_created()->getTimestamp()<time()-HOUR_IN_SECONDS)return;
        foreach ($order->get_items() as $item) {
            if (!$item instanceof \WC_Order_Item_Product || $item->get_meta('_kr_return_policy_v2')) continue;
            self::snapshot($item,[], $order); $item->save();
        }
    }
    public static function resolve(\WC_Order $order, \WC_Order_Item_Product $item): array {
        $v=$item->get_meta('_kr_return_policy_v2',true); $old=\KREV_Returns::policy_snapshot($item);
        $v=is_array($v)?$v:[]; $p=$v['policy']??$old; $review=[];
        if (!$p) $review[]='Purchase-time policy is missing; current listing policy was not applied.';
        elseif (!$v) $review[]='Legacy purchase-time policy requires review of its window anchor and responsibility.';
        if ($v && (!isset($v['policy_hash']) || !hash_equals($v['policy_hash'],Support::hash($p)))) $review[]='Purchased policy integrity requires review.';
        $anchor=$v['anchor']??null; $at=null;
        if ($anchor==='delivery') {
            // A completion flag or an undated seller claim is not a verified scan.
            $proof=$order->get_meta('_krev_delivery_evidence',true);
            $raw=is_array($proof)&&!empty($proof['verified'])&&!empty($proof['source'])&&!empty($proof['evidence_hash'])?($proof['delivered_at']??null):null;
            $at=is_numeric($raw)?(int)$raw:($raw?strtotime((string)$raw):false);
            if (!$at) $review[]='Delivery-based policy has no verified delivery timestamp.';
        } elseif ($anchor==='purchase') $at=$order->get_date_created()?->getTimestamp();
        else $review[]='The purchased return-window anchor is not established.';
        $days=(int)($p['days']??$p['return_window_days']??0);
        $until=$at && $days>0 ? $at+$days*DAY_IN_SECONDS : null;
        if ($until && $until<time()) $review[]='The recorded policy window ended; an exception can be reviewed.';
        if (($p['type']??'')!=='returns' && empty($p['return_allowed'])) $review[]='The sold policy needs a return exception decision.';
        $product=$item->get_product();
        $physical=$product ? !$product->is_virtual() && !\KREV_Sharpening_Orders::is_sharpening_product($product) : null;
        if ($physical===null) $review[]='Deleted listing; verify the sold physical-item evidence.';
        return ['policy'=>$p,'snapshot'=>$v,'review_reasons'=>array_values(array_unique($review)),
            'requires_review'=>(bool)$review,'until'=>$until,'anchor'=>$anchor,'anchor_at'=>$at?:null,
            'physical'=>$physical,'quantity'=>self::available($order,$item),
            'automatically_approved'=>false];
    }
    public static function available(\WC_Order $order, \WC_Order_Item_Product $item, int $ignore=0): int {
        $root=Support::root($order);$target=$order->get_parent_id()?(int)$item->get_meta('_dokan_parent_order_item_id'):$item->get_id();$paid=$root->get_item($target);
        if(!$paid||$paid->get_product_id()!==$item->get_product_id()||$paid->get_variation_id()!==$item->get_variation_id())return 0;
        $orders=[$root];foreach(wc_get_orders(['parent'=>$root->get_id(),'limit'=>101])as$child)$orders[]=$child;
        if(count($orders)>101)Support::fail('scope','This original payment needs a paginated allocation review.',503);
        $reserved=0;$caseReservations=[];$refunded=max(abs((int)$root->get_qty_refunded_for_item($target)),abs((int)$order->get_qty_refunded_for_item($item->get_id())));
        foreach($orders as$source)foreach(\KREV_Returns::for_order($source->get_id())as$r){
            if((int)$r['id']===$ignore)continue;$line=$source->get_item($r['order_item_id']);if(!$line)continue;
            $canonical=$source->get_parent_id()?(int)$line->get_meta('_dokan_parent_order_item_id'):$line->get_id();if($canonical!==$target)continue;
            $d=\KREV_Return_Workflow::details($r['id'])['data'];$v=$d['v2']??null;
            if($v){if(($v['kind']??'return')==='order_help'||in_array($v['case'],['declined','withdrawn'],true))continue;$reserved+=max(0,(int)$r['quantity']-(int)($v['refund']['verified_quantity']??0));$caseReservations[(int)$r['id']]=true;}
            elseif(!in_array($r['status'],['rejected','closed','refunded'],true))$reserved+=(int)$r['quantity'];
        }
        foreach(\KREV_Refund_Authority::claims($root)as$c)if(\KREV_Refund_Authority::open($c)&&!isset($caseReservations[(int)($c['plan']['case_id']??0)])){$line=$c['plan']['line_items'][$target]??null;if($line&&empty($c['woo_refund_id']))$reserved+=(int)$line['qty'];}
        return max(0,(int)$paid->get_quantity()-$refunded-$reserved);
    }
    public static function deadline(int $at, string $basis, int $amount, bool $business=false): array {
        $p=self::settings();$date=(new \DateTimeImmutable('@'.$at))->setTimezone(new \DateTimeZone($p['timezone']));
        if ($business) {
            if (!$p['business_weekdays'] || $amount>30) Support::fail('calendar','Configure a supported inspection calendar.');
            $left=$amount; $guard=0;
            while ($left>0 && ++$guard<=366) {
                $date=$date->modify('+1 day');
                if (in_array((int)$date->format('N'),$p['business_weekdays'],true) && !in_array($date->format('Y-m-d'),$p['holidays'],true)) --$left;
            }
            if ($left) Support::fail('calendar','The inspection calendar needs review.');
        } else $date=$date->modify('+'.$amount.' '.($basis==='seller_response'||$basis==='address_confirmation'?'hours':'days'));
        return ['basis'=>$basis,'started_at'=>$at,'due_at'=>$date->getTimestamp(),'timezone'=>$p['timezone'],'policy_version'=>$p['version'],'business_calendar'=>$business?['weekdays'=>$p['business_weekdays'],'holidays'=>$p['holidays']]:null,'extensions'=>[],'effect'=>'admin_escalation_only'];
    }
}
