# Dispatch buyer and seller emails

Audit date: October 29, 2026 (America/Los_Angeles).


Active Dispatch 0.2.0 source. These are **conditional live-workflow templates**, not proof live handoffs/customer notices are enabled. Sending requires both `notifications_enabled` and `dispatch_live_offers_enabled`, a live route and an authorized participant. Sandbox/demo notices are not sent.

Every subject is **`[KnifeRevive] {subject below}`**.

| Event | Subject suffix |
| --- | --- |
| enrolled | Your Dispatch enrollment is in review |
| invite | Dispatch request: can you ship this? |
| partner_accepted | Your partner is in. Waiting on buyer approval. |
| buyer_review | Your order. A closer handoff to review. |
| buyer_approved | The buyer approved this item and partner |
| committed | Handoff confirmed. Meet your shipping seller. |
| fallback | Your original seller is keeping the order. |
| shipped | Your Dispatch shipment update |
| delivered | Delivery recorded. Settlement is in review. |
| exception | Your handoff is on hold for review. |
| reversed | Handoff reversed. Back with your original seller. |
| settlement | Your Dispatch breakdown has an update. |


**Shared body:** {subject heading}; Order #{order_id} · {state}.

“A nearby seller may reduce shipping distance. Both units were reviewed for matching model, variant, condition and included accessories. They are separate physical items: review their actual photos and details. Faster delivery is an aim, not a confirmed arrival promise.”

**Original seller:** {linked_store_name}. **Fulfilling seller:** {linked_store_name}.

**Previous item location:** {old_visible_location}, struck through after committed change. **New ship-from** or **Proposed ship-from:** {new_visible_location}. Location visibility depends on recipient role.

Seller-only: **Agreed amount: {currency} {amount}.** “Projection only until verified settlement.” Reversal variant: “The projected incentive and partner allocation were reversed.”

**Review your handoff**; **Need a hand? Contact support**. “If you object, ask for review from your handoff page. Dispatch can stop an unshipped route; an already shipped parcel needs reconciliation.”

Non-live preview adds **SANDBOX EMAIL PREVIEW — NOT SENT**. Fallback subject suffix: **Dispatch update**. No default $2 claim is hardcoded in the email body; amounts come from the route.


## Source and exact wording reference


### Source: [class-krd-adapters.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-dispatch/includes/class-krd-adapters.php)

SHA-256: `a7cb0132f7012a3d0badda6f3aa6fabe713d83748e60dd5c440b38fd1662854e`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dispatch update
- A nearby seller may reduce shipping distance. Both units were reviewed for matching model, variant, condition and included accessories. They are separate physical items: review their actual photos and details. Faster delivery is an aim, not a confirmed arrival promise.
- Original seller
- Fulfilling seller
- Previous item location:
- New ship-from
- Proposed ship-from
- Agreed amount:
- The projected incentive and partner allocation were reversed.
- Projection only until verified settlement.
- ">Review your handoff Need a hand? <a href="
- ">Contact support . If you object, ask for review from your handoff page. Dispatch can stop an unshipped route; an already shipped parcel needs reconciliation.
- SANDBOX EMAIL PREVIEW — NOT SENT
- KnifeRevive Dispatch

<details><summary>Exact template/renderer source, beginning at line 81</summary>

```php
    public static function compose(array $r,string $kind,int $recipient): string {
        $buyer=$recipient===(int)$r['buyer_id'];$seller=in_array($recipient,[(int)$r['seller_a'],(int)$r['seller_b']],true);$title=self::SUBJECTS[$kind]??'Dispatch update';$d=$r['data'];
        $body='<h2>'.esc_html($title).'</h2><p>Order #'.esc_html($r['order_id']).' · '.esc_html(str_replace('_',' ',$r['state'])).'</p>';
        $body.='<p>A nearby seller may reduce shipping distance. Both units were reviewed for matching model, variant, condition and included accessories. They are separate physical items: review their actual photos and details. Faster delivery is an aim, not a confirmed arrival promise.</p>';
        foreach(['seller_a'=>'Original seller','seller_b'=>'Fulfilling seller'] as $key=>$label)$body.='<p>'.esc_html($label).': <a href="'.esc_url($d[$key]['url']).'">'.esc_html($d[$key]['name']).'</a></p>';
        $origins=$d['origins']??['a'=>[],'b'=>[]];if($buyer){$visible=static fn($o)=>array_intersect_key($o,array_flip(['state','postcode']));$old=$visible($origins['a']);$new=$visible($origins['b']);}elseif($seller){$v=KRD_Handoff::view($r,$recipient);$old=$v['prior_origin'];$new=$v['new_origin'];}else{$old=[];$new=[];}
        $changed=!empty($d['committed_at'])&&$r['state']!=='CANCELED_WITH_REVERSAL';$body.='<p>Previous item location: '.($changed?'<del>':'').esc_html(implode(', ',array_filter($old))).($changed?'</del>':'').'<br><strong>'.($changed?'New ship-from':'Proposed ship-from').': '.esc_html(implode(', ',array_filter($new))).'</strong></p>';
        if($seller){$amount=$recipient===(int)$r['seller_a']?$d['money']['a_minor']:$d['money']['b_total_minor'];$body.='<p>Agreed amount: '.esc_html(strtoupper($d['money']['currency']).' '.KRD_Money::display($amount,$d['money']['currency'])).'. '.($r['state']==='CANCELED_WITH_REVERSAL'?'The projected incentive and partner allocation were reversed.':'Projection only until verified settlement.').'</p>';}
        $url=add_query_arg($buyer?'buyer_route':'route',$r['id'],wc_get_account_endpoint_url('dispatch'));$body.='<p><a href="'.esc_url($url).'">Review your handoff</a></p><p>Need a hand? <a href="'.esc_url(home_url('/help/')).'">Contact support</a>. If you object, ask for review from your handoff page. Dispatch can stop an unshipped route; an already shipped parcel needs reconciliation.</p>';
        if($r['mode']!=='live')$body='<p><strong>SANDBOX EMAIL PREVIEW — NOT SENT</strong></p>'.$body;
        $body='<div style="background:#142234;color:#f4f1e8;padding:24px;border-radius:12px;font-family:Arial,sans-serif;line-height:1.6">'.str_replace(['<p>','<h2>','<a href='],['<p style="color:#f4f1e8">','<h2 style="color:#f4f1e8">','<a style="color:#bef264" href='],$body).'</div>';if(function_exists('WC')&&WC()->mailer())return WC()->mailer()->wrap_message('KnifeRevive Dispatch',$body);
        return '<html><body style="background:#0c1521;color:#f4f1e8;font-family:Arial,sans-serif;padding:24px"><main style="max-width:600px;margin:auto">'.$body.'</main></body></html>';
    }
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dispatch update

<details><summary>Exact template/renderer source, beginning at line 73</summary>

```php
    public static function dispatch(int $id): bool {
        if(!KRD_Config::on('notifications_enabled')||!KRD_Config::on('dispatch_live_offers_enabled'))return false;
        global $wpdb;$t=KRD_Store::table('outbox');$n=$wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d",$id),ARRAY_A);if(!$n||!in_array($n['state'],['pending','failed'],true)||(int)$n['attempts']>=3)return false;
        $r=KRD_Store::route($n['route_id']);if($r['mode']!=='live')return false;$recipient=(int)$n['recipient'];$u=get_userdata($recipient);if(!$u)return false;
        if(!in_array($recipient,[(int)$r['seller_a'],(int)$r['seller_b'],(int)$r['buyer_id']],true)&&!user_can($u,'manage_options'))return false;
        $claimed=$wpdb->query($wpdb->prepare("UPDATE $t SET state='sending',attempts=attempts+1,updated_at=%d WHERE id=%d AND state IN ('pending','failed') AND attempts<3",time(),$id));if($claimed!==1)return false;
        try{$html=self::compose($r,$n['kind'],$recipient);$sent=wp_mail($u->user_email,'[KnifeRevive] '.(self::SUBJECTS[$n['kind']]??'Dispatch update'),$html,['Content-Type: text/html; charset=UTF-8']);$wpdb->update($t,['state'=>$sent?'sent':'failed','updated_at'=>time()],['id'=>$id,'state'=>'sending']);return $sent;}catch(Throwable $e){$wpdb->update($t,['state'=>'uncertain','updated_at'=>time()],['id'=>$id,'state'=>'sending']);return false;}
    }
```

</details>

<details><summary>Exact template/renderer source, beginning at line 68</summary>

```php
    public static function work(): void {
        if(!KRD_Config::on('notifications_enabled')||!KRD_Config::on('dispatch_live_offers_enabled'))return;
        global $wpdb;$t=KRD_Store::table('outbox');$wpdb->query($wpdb->prepare("UPDATE $t SET state='uncertain' WHERE state='sending' AND updated_at<%d",time()-900));
        $ids=$wpdb->get_col($wpdb->prepare("SELECT id FROM $t WHERE state IN ('pending','failed') AND attempts<3 AND updated_at<%d ORDER BY id ASC LIMIT 20",time()-300));foreach($ids as $id)self::dispatch((int)$id);
    }
```

</details>
