# ShipKit, label options, postage and Label Lifeline

Audit date: October 29, 2026 (America/Los_Angeles).


Active ShipKit 0.6.3-rc1; seller transactional notices (operator_alert is for the configured operator/admin). Actual notice queues and settings were not modified or exercised.

| Kind | Exact subject |
| --- | --- |
| lifeline | Your Label Lifeline update · KnifeRevive |
| quote_ready | Your label options are ready · KnifeRevive |
| adjustment | Your shipping cost review · KnifeRevive |
| refund_confirmed | Your postage refund was confirmed · KnifeRevive |
| buffer_review | Your postage buffer is ready for review · KnifeRevive |
| adjustment_reminder | Your shipping review deadline · KnifeRevive |
| operator_alert | Ship Desk needs an operator · KnifeRevive |

Heading is the subject text before “ · KnifeRevive”.

**Shared standard body:** “This ShipKit update reflects your records as of {date_time_timezone}.” Then a snapshot table: field names replace underscores with spaces; monetary values use USD with two decimals where specified by the renderer.

“Review the exact amount and current status before taking action. A seller payment does not mean a label has been purchased. Refunds return to the original payment source.”

**Button:** Open ShipKit / request review

Snapshot rows vary with notice kind. Exact fields and conditions appear below. Shipping-to-buyer notices after label/shipment changes use the separate shipping mailer in 02_SHIPPING_TRACKING_AND_ADDRESS.md.


## Source and exact wording reference


### Source: [Notices.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-shipkit/includes/Notices.php)

SHA-256: `1e5841ddcf0a4bddcb858bdea36112f7130d19d99de8012605b1d5face196d7e`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Separate platform-funded relief. Any payable remains subject to verified payout checks; a waived IOU is not new cash.
- Review pending. No award or payout is promised.
- Check the actual provider dispute deadline now. A seller review does not pause carrier billing.
- Match the provider charge before assessing a seller.
- Review the aged funded/purchase queue; unknown purchases must not be retried blindly.
- Resolve the source refund and its actual processor status.
- A review date is not automatic evidence that carrier charges are final.
- Seller email unavailable.
- Your Label Lifeline update
- Your label options are ready
- Your shipping cost review
- Your postage refund was confirmed
- Your postage buffer is ready for review
- Your shipping review deadline
- Ship Desk needs an operator
- This ShipKit update reflects your records as of
- Review the exact amount and current status before taking action. A seller payment does not mean a label has been purchased. Refunds return to the original payment source. <a style="display:inline-block;background:#173e33;color:#fff;padding:14px;border-radius:10px" href="
- ">Open ShipKit / request review

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
namespace KnifeRevive\ShipKit;

/** Transactional seller notices; shipment messages remain solely in the canonical shipment outbox. */
final class Notices {
    public function __construct(private Repository $db){}
    private function enqueue(int $seller,string $kind,string $key,array $snapshot): void {$id=hash('sha256',$kind.':'.$key);$this->db->transaction(function()use($seller,$kind,$id,$snapshot){if($this->db->get('notice',$id))return;$this->db->put('notice',$id,['id'=>$id,'seller_id'=>$seller,'kind'=>$kind,'snapshot'=>$snapshot,'created_at'=>time(),'state'=>'queued']);});}
    public function scan(): void {
        foreach($this->db->list('attempt')as$a)if($a['state']==='quoted'&&$a['quote']['expires']>time())$this->enqueue($a['seller_id'],'quote_ready',$a['quote']['hash'],['attempt'=>$a['id'],'order'=>$a['order_id'],'label_minor'=>$a['quote']['label'],'buffer_minor'=>$a['quote']['buffer'],'expires'=>$a['quote']['expires']]);
        foreach($this->db->list('adjustment')as$a)if(in_array($a['state'],['assessed','part_collected','seller_disputed','reversed'],true)&&!(($a['iou_policy']??'')===ShippingIou::VERSION&&$a['state']==='assessed'&&($a['collected']??0)===0&&($a['credited']??0)===0)){$s=['case'=>$a['id'],'state'=>$a['state'],'amount_due'=>$a['amount_due']??0,'reason'=>$a['raw']['reason'],'due_at'=>$a['due_at']??null];$this->enqueue($a['seller_id'],'adjustment',Check::hash($s),$s);}
        foreach($this->db->list('lifeline')as$c)$this->enqueue($c['seller_id'],'lifeline',Check::hash([$c['id'],$c['state'],$c['reason'],$c['payable_minor']??0,$c['waived_minor']??0]),['incident'=>$c['id'],'state'=>$c['state'],'reason'=>$c['reason'],'payable_minor'=>$c['payable_minor']??0,'waived_minor'=>$c['waived_minor']??0,'meaning'=>$c['state']==='awarded'?'Separate platform-funded relief. Any payable remains subject to verified payout checks; a waived IOU is not new cash.':'Review pending. No award or payout is promised.']);
        $policy=Policy::get();$owner=(int)($policy['reviewed_by']??0);
        foreach($this->db->list('adjustment')as$a){if(($a['amount_due']??0)>0&&isset($a['due_at'])&&!in_array($a['state'],['seller_disputed','provider_disputed'],true)&&$a['due_at']<time()+($policy['deadline_alert_hours']??0)*3600){$s=['case'=>$a['id'],'amount_due'=>$a['amount_due'],'due_at'=>$a['due_at'],'due_revision'=>count($a['due_history']??[]),'stage'=>$a['due_at']<time()?'overdue':'due_soon'];$this->enqueue($a['seller_id'],'adjustment_reminder',Check::hash($s),$s);}
            $deadline=$a['provider_facts']['provider_dispute_deadline']??null;if($owner&&$deadline&&strtotime($deadline.' 00:00:00 UTC')<time()+($policy['deadline_alert_hours']??0)*3600&&!in_array($a['state'],['reversed','platform_cost','matched'],true))$this->enqueue($owner,'operator_alert',Check::hash([$a['id'],$deadline]),['case'=>$a['id'],'provider_deadline'=>$deadline,'action_owner'=>$policy['escalation_owner'],'next_action'=>'Check the actual provider dispute deadline now. A seller review does not pause carrier billing.']);
            if($owner&&$a['state']==='unmatched')$this->enqueue($owner,'operator_alert','unmatched-'.$a['id'],['case'=>$a['id'],'action_owner'=>$policy['escalation_owner'],'next_action'=>'Match the provider charge before assessing a seller.']);}
        foreach($this->db->list('attempt')as$a)if($owner&&in_array($a['state'],['funded','purchase_claimed','reconciling'],true)&&$a['created_at']<time()-($policy['queue_alert_hours']??24)*3600)$this->enqueue($owner,'operator_alert','queue-'.$a['id'],['attempt'=>$a['id'],'action_owner'=>$policy['escalation_owner'],'next_action'=>'Review the aged funded/purchase queue; unknown purchases must not be retried blindly.']);
        foreach($this->db->list('refund')as$r)if($owner&&$r['state']!=='paid'&&($r['created_at']??time())<time()-($policy['refund_alert_hours']??24)*3600)$this->enqueue($owner,'operator_alert','refund-'.$r['id'],['refund'=>$r['id'],'action_owner'=>$policy['escalation_owner'],'next_action'=>'Resolve the source refund and its actual processor status.']);
        foreach($this->db->list('refund')as$r)if($r['state']==='paid')$this->enqueue($r['seller_id'],'refund_confirmed',$r['id'],['amount_minor'=>$r['amount'],'source'=>$r['source'],'receipt'=>$r['receipt']]);
        foreach($this->db->list('attempt')as$a)if(isset($a['quote'])&&($a['buffer_review_override']['review_at']??$a['quote']['buffer_review_at']??PHP_INT_MAX)<time()&&in_array($a['state'],['label_confirmed','projected'],true))$this->enqueue($a['seller_id'],'buffer_review',$a['id'].':'.($a['buffer_review_override']['review_at']??$a['quote']['buffer_review_at']),['order'=>$a['order_id'],'attempt'=>$a['id'],'review_at'=>$a['buffer_review_override']['review_at']??$a['quote']['buffer_review_at'],'meaning'=>'A review date is not automatic evidence that carrier charges are final.']);
    }
    public function send(string $id): void {
        $n=$this->db->transaction(function()use($id){$n=$this->db->get('notice',$id);if(!$n||$n['state']!=='queued')return null;$s=$n['snapshot'];$stale=false;if($n['kind']==='adjustment'){$a=$this->db->get('adjustment',$s['case']);$stale=!$a||($a['amount_due']??0)!==($s['amount_due']??0)||(isset($s['state'])&&$a['state']!==$s['state'])||($a['due_at']??null)!==($s['due_at']??null);}if($n['kind']==='lifeline'){$c=$this->db->get('lifeline',$s['incident']);$stale=!$c||$c['state']!==$s['state']||$c['reason']!==$s['reason'];}if($n['kind']==='adjustment_reminder'){$a=$this->db->get('adjustment',$s['case']);$stale=!$a||($a['due_at']??null)!==$s['due_at']||count($a['due_history']??[])!==$s['due_revision']||($a['amount_due']??0)<=0||in_array($a['state'],['seller_disputed','provider_disputed'],true);}if($n['kind']==='quote_ready'){$a=$this->db->get('attempt',$s['attempt']);$stale=!$a||$a['state']!=='quoted'||$a['quote']['expires']!==$s['expires']||$s['expires']<=time();}if($n['kind']==='buffer_review'){$a=$this->db->get('attempt',$s['attempt']);$remaining=0;foreach($a['holds']??[]as$h){$v=$this->db->get('hold',$h);if(($v['purpose']??'')==='buffer')$remaining+=$v['amount']-($v['used']??0)-($v['released']??0);}$stale=!$a||!$remaining||($a['buffer_review_override']['review_at']??$a['quote']['buffer_review_at']??null)!==$s['review_at'];}if($stale){$n['state']='superseded';$this->db->put('notice',$id,$n);return null;}$n['state']='sending';$n['attempted_at']=time();$this->db->put('notice',$id,$n);return$n;});if(!$n)return;$u=get_userdata($n['seller_id']);$ok=false;
        try{if(!$u||!is_email($u->user_email))throw new Problem('email','Seller email unavailable.');$title=['lifeline'=>'Your Label Lifeline update','quote_ready'=>'Your label options are ready','adjustment'=>'Your shipping cost review','refund_confirmed'=>'Your postage refund was confirmed','buffer_review'=>'Your postage buffer is ready for review','adjustment_reminder'=>'Your shipping review deadline','operator_alert'=>'Ship Desk needs an operator'][$n['kind']];$body='<p>This ShipKit update reflects your records as of '.esc_html(wp_date('M j, Y g:i a T',$n['created_at'])).'.</p><table role="presentation" style="width:100%;table-layout:fixed">';foreach($n['snapshot']as$k=>$v)$body.='<tr><td style="padding:8px;overflow-wrap:anywhere">'.esc_html(str_replace('_',' ',$k)).'</td><td style="padding:8px;overflow-wrap:anywhere">'.esc_html(str_contains($k,'minor')||$k==='amount_due'?'USD '.number_format((int)$v/100,2):(string)$v).'</td></tr>';$body.='</table><p>Review the exact amount and current status before taking action. A seller payment does not mean a label has been purchased. Refunds return to the original payment source.</p><p><a style="display:inline-block;background:#173e33;color:#fff;padding:14px;border-radius:10px" href="'.esc_url(wc_get_account_endpoint_url('shipkit')).'">Open ShipKit / request review</a></p>';$html=WC()->mailer()->wrap_message($title,$body);$ok=(bool)WC()->mailer()->send($u->user_email,$title.' · KnifeRevive',$html,['Content-Type: text/html; charset=UTF-8']);}
        catch(\Throwable $e){$ok=false;}$this->db->transaction(function()use($id,$ok){$n=$this->db->get('notice',$id);$n['state']=$ok?'accepted_by_mail_transport':'delivery_unknown';$n['finished_at']=time();$this->db->put('notice',$id,$n);});
    }
}

```

</details>
