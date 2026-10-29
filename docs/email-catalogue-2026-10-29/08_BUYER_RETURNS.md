# Buyer returns and seller return updates

Audit date: October 29, 2026 (America/Los_Angeles).


Returns 0.1.0-rc2 and Seller Orders 1.7.0-rc1 are active. Last deployment verification left new return requests, return label issuance and refund execution OFF. This audit did not enable them or independently re-read their settings. Template presence is not proof an operational financial return was completed.

## New coordinated return workspace

**Subject:** `KnifeRevive return update · Order #{order_number}`

Recipients: buyer, seller, assigned return recipient and support as allowed. Admin-private messages notify admin only. No private conversation text/evidence is copied into the generic update.

**Body:** “Your KnifeRevive return has an update.” **Order #{order_number} · Return #{return_id}**; {item_title} · Qty {quantity}; {next_step_sentence}; **View return details**; “Sign in as the purchasing customer or assigned seller. Guest buyers can request a new secure email-access link.” **Need help?**

The exact next-step sentence is selected from Cases::next(), reproduced below. Includes “Your refund is approved. It has not been sent yet.” and “Refund sent through the original payment method. Bank timing may vary.” These represent separate states.

## Guest access link

**Subject:** Your secure KnifeRevive return link

**Body:** “Open your private KnifeRevive return workspace. This link expires in 30 minutes and can be used once.” **Open return workspace**. “If you did not request it, ignore this email.” Actual secure link is generated per request and is not included in this report.

## Legacy return outbox (compatibility source; not an additional promised active workflow)

**Subject:** `[KnifeRevive] Return {Title_Cased_Event} — Order #{order_number}`

Body labels: Return #{return_id} / Order #{order_number}; Item; Quantity; Status; Reason; Purchased policy; Requested resolution; Rejection reason; Decision / support note; Confirmed return address; Confirmed ship-by date; Purchased return deduction; Return carrier; Tracking; Shipped; Received quantity; Receipt date; Condition; Recorded refund; View return (sign in); Support.

Standard caveats: “Seller approval is permission to return, not a payment refund. Replacement requests need manual coordination.” / “No prepaid label is created.” / “Marketplace management must review before issuing funds.” / “Payment settlement depends on the payment provider.” These are legacy-path copy, not descriptions of the new label workspace.

For legacy `refunded` mail, the buyer is removed from recipients because WooCommerce owns the buyer's financial refund email. The new generic workspace update is separate from the native financial email; deduplication/status coordination remains a documented launch gap.


## Source and exact wording reference


### Source: [Jobs.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-returns/includes/Jobs.php)

SHA-256: `eedfb76d978b5d857647bcf05f9fb05f0e54213dac0eb618d0e5b08761b83708`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Purchased item
- Your KnifeRevive return has an update. Order #
- ">View return details Sign in as the purchasing customer or assigned seller. Guest buyers can request a new secure email-access link. <a href="
- ">Need help?
- KnifeRevive return update · Order #

<details><summary>Exact template/renderer source, beginning at line 13</summary>

```php
    public static function enqueue(int $id,string $event): bool {
        $r=Store::row($id);$v=Store::state($r);$d=\KREV_Return_Workflow::details($id);$o=wc_get_order($r['order_id']);if(!$o)return false;
        $private=$event==='message'&&($v['messages'][array_key_last($v['messages'])]['visibility']??'')==='admin_private';
        $seller=get_userdata($r['seller_id']);$recipients=['admin'=>\KREV_Order_Contact::EMAIL];
        if(!$private){$recipients['buyer']=Support::root($o)->get_billing_email();if($seller)$recipients['seller']=$seller->user_email;$recipient=get_userdata((int)($v['recipient_seller_id']??0));if($recipient)$recipients['recipient']=$recipient->user_email;}
        $item=$o->get_item($r['order_item_id']);$title=$item?->get_name()??'Purchased item';
        foreach($recipients as$role=>$email){
            if(!is_email($email))continue;
            $link=$role==='admin'?admin_url('admin.php?page=kr-return-desk&case='.$id):Screens::caseUrl($id);
            $body='<p>Your KnifeRevive return has an update.</p><p><strong>Order #'.esc_html($o->get_order_number()).' · Return #'.$id.'</strong><br>'.esc_html($title).' · Qty '.(int)$r['quantity'].'</p>';
            $body.='<p>'.esc_html(Cases::next($v)).'</p><p><a href="'.esc_url($link).'">View return details</a></p><p>Sign in as the purchasing customer or assigned seller. Guest buyers can request a new secure email-access link.</p><p><a href="'.esc_url(home_url('/help/contact/')).'">Need help?</a></p>';
            $eventIdentity=$event==='label_ready'?($v['label']['document_id']??$d['revision']):$d['revision'];
            $key=hash('sha256','returns-v2:'.$id.':'.$eventIdentity.':'.$event.':'.$role.':'.strtolower($email));
            if(!self::mail($id,$key,$email,'KnifeRevive return update · Order #'.$o->get_order_number(),$body))return false;
        }
        return true;
    }
```

</details>


### Source: [Cases.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-returns/includes/Cases.php)

SHA-256: `81544f567f44c3095a1e89025630c2030c6356ae5382cd5e47459691548f07e0`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- KnifeRevive is reviewing your request for help.
- This case is resolved. Read the recorded outcome or contact support if you need help.
- Read the decision. You can ask KnifeRevive to review it.
- This request was withdrawn. Contact support if you need help.
- Refund sent through the original payment method. Bank timing may vary.
- The refund needs attention. Do not start a second refund.
- Your refund is approved. It has not been sent yet.
- Your refund is processing through the original payment method. Payment has not yet been confirmed.
- Your request is being reviewed. Do not send the item yet.
- No physical return is needed. Your refund remains under separate review.
- The returned contents need a review before a refund decision.
- Return received and inspected. Refund decision is separate.
- Return received — contents inspection is next. Your refund stays under separate review.
- Drop-off reported. Awaiting carrier acceptance.
- Your return is on its way. Keep your drop-off receipt.
- Print or download your return label. Keep your drop-off receipt.
- Return approved — label being prepared. Contact support if you need help.

<details><summary>Exact template/renderer source, beginning at line 192</summary>

```php
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
```

</details>


### Source: [Guest.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-returns/includes/Guest.php)

SHA-256: `e84fc5409a6faa7a77cd72c99946103f4bd98e0f217207a487c7ffdde1e53388`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Please wait before requesting another secure link.
- If this is a guest order with matching contact details, a secure return-access link will be emailed to its checkout address.
- Open your private KnifeRevive return workspace. This link expires in 30 minutes and can be used once. <a href="
- ">Open return workspace If you did not request it, ignore this email.
- Your secure KnifeRevive return link
- The secure link could not be queued.

<details><summary>Exact template/renderer source, beginning at line 11</summary>

```php
    public static function request(array $in): array {
        $rate='kr_returns_guest_'.substr(self::digest($_SERVER['REMOTE_ADDR']??'unknown'),0,32);
        $count=(int)get_transient($rate);
        if($count>=5) Support::fail('rate','Please wait before requesting another secure link.',429);
        set_transient($rate,$count+1,HOUR_IN_SECONDS);
        $generic=['message'=>'If this is a guest order with matching contact details, a secure return-access link will be emailed to its checkout address.'];
        $o=wc_get_order(absint($in['order_id']??0));$email=sanitize_email($in['email']??'');
        if(!$o||$o->get_parent_id()||$o->get_customer_id()||!$email||!hash_equals(strtolower($o->get_billing_email()),strtolower($email)))return$generic;
        $token=bin2hex(random_bytes(32));$hash=self::digest($token);$db=Application::db();
        $db->transaction(fn()=>$db->put('return_guest_grant',$hash,['id'=>$hash,'seller_id'=>0,'order_id'=>$o->get_id(),'contact_hash'=>self::contact($o),'expires'=>time()+1800,'used'=>false,'site'=>home_url()],true));
        $url=home_url('/?kr_return_access=1#token='.$token);
        $body='<p>Open your private KnifeRevive return workspace. This link expires in 30 minutes and can be used once.</p><p><a href="'.esc_url($url).'">Open return workspace</a></p><p>If you did not request it, ignore this email.</p>';
        if(!Jobs::mail(0,hash('sha256','guest:'.$hash),$o->get_billing_email(),'Your secure KnifeRevive return link',$body)) Support::fail('storage','The secure link could not be queued.',503);
        \KREV_Return_Mail::schedule();return$generic;
    }
```

</details>


### Source: [class-krev-return-mail.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-seller-orders/includes/class-krev-return-mail.php)

SHA-256: `b3b52fd4b6f6d040c9d0988d5eaf75a4ccbf66ab4bceb374aac777e8c5ba6594`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Purchased item
- Purchased policy:
- See order
- Requested resolution:
- Seller approval is permission to return, not a payment refund. Replacement requests need manual coordination.
- Rejection reason:
- Decision / support note:
- Confirmed return address:
- Confirmed ship-by date:
- Purchased return deduction:
- . No prepaid label is created.
- Return carrier:
- Received quantity:
- Receipt date:
- Marketplace management must review before issuing funds.
- Recorded refund:
- . Payment settlement depends on the payment provider.
- View return (sign in):

<details><summary>Exact template/renderer source, beginning at line 25</summary>

```php
    public static function queue($id,$event){
        global $wpdb;$r=KREV_Returns::get($id);$o=$r?wc_get_order($r['order_id']):false;if(!$o)return false;
        $d=KREV_Return_Workflow::details($id);$item=$o->get_item($r['order_item_id']);$seller=get_userdata($r['seller_id']);
        $name=$d['data']['item_name']??($item?$item->get_name():'Purchased item');$policy=json_decode($r['policy_snapshot'],true)?:[];
        $body='Return #'.$id.' / Order #'.$o->get_order_number()."\nItem: ".$name.' / Quantity: '.$r['quantity']."\nStatus: ".ucwords(str_replace('-',' ',$r['status']))."\nReason: ".$r['reason']."\n".$r['explanation']."\nPurchased policy: ".($policy['label']??$policy['name']??'See order')."\nRequested resolution: ".$r['requested_resolution']."\nSeller approval is permission to return, not a payment refund. Replacement requests need manual coordination.\n";
        if($r['rejection_reason'])$body.="\nRejection reason: ".$r['rejection_reason']."\n";
        if(!empty($d['data']['decision_note']))$body.="\nDecision / support note: ".$d['data']['decision_note']."\n";
        if(!empty($d['data']['approval'])){$a=$d['data']['approval'];$body.="\nConfirmed return address:\n".implode("\n",array_filter($a['address']))."\n".$a['instructions']."\n";if($a['ship_by'])$body.='Confirmed ship-by date: '.$a['ship_by']."\n";$body.='Purchased return deduction: '.wc_format_decimal($policy['fee']??$policy['return_cost']??0,2).' '.$o->get_currency().". No prepaid label is created.\n";}
        if($r['return_tracking'])$body.="\nReturn carrier: ".$r['return_carrier']."\nTracking: ".$r['return_tracking']."\nShipped: ".$r['date_shipped']."\n";
        if($r['received_quantity'])$body.="\nReceived quantity: ".$r['received_quantity']."\nReceipt date: ".$r['received_date']."\nCondition: ".$r['condition_note']."\nMarketplace management must review before issuing funds.\n";
        if(!empty($d['data']['refund_result']))$body.="\nRecorded refund: ".$d['data']['refund_result']['amount'].' '.$o->get_currency().". Payment settlement depends on the payment provider.\n";
        $recipients=array_filter([$o->get_billing_email(),$seller?$seller->user_email:KREV_Order_Contact::EMAIL]);
        if(in_array($event,['escalated','received','refunded'],true))$recipients[]=KREV_Order_Contact::EMAIL;
        // WooCommerce owns the financial refund email; this outbox informs seller/support only.
        if($event==='refunded')$recipients=array_filter($recipients,static fn($email)=>strtolower($email)!==strtolower($o->get_billing_email()));
        foreach(array_unique(array_map('strtolower',$recipients)) as $email){if(!is_email($email))continue;$buyer=strtolower($email)===strtolower($o->get_billing_email());$link=$buyer?$o->get_view_order_url():wc_get_account_endpoint_url('seller-orders').$o->get_id().'/';$text=$body."\nView return (sign in): ".$link."\nSupport: ".KREV_Order_Contact::EMAIL;
            $key=hash('sha256',$id.':'.$d['revision'].':'.$event.':'.$email);if($wpdb->query($wpdb->prepare('INSERT IGNORE INTO '.self::table().' (event_key,return_id,recipient,subject,body,status) VALUES (%s,%d,%s,%s,%s,%s)',$key,$id,$email,'[KnifeRevive] Return '.ucwords(str_replace('-',' ',$event)).' — Order #'.$o->get_order_number(),$text,'queued'))===false)return false;
        }return true;
    }
```

</details>
