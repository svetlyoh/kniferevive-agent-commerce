# Seller payment, earnings and My Money emails

Audit date: October 29, 2026 (America/Los_Angeles).


Active Stripe Connect 0.7.2-rc1 owns these templates; My Money 1.2.1-rc1 is the linked destination, not a separate mail executor. **Availability is gated** by `KR_CONNECT_SETTLEMENT_NOTIFICATIONS_ENABLED === true` and an administrator capability in dispatch(). Live constant and sender execution were not verified in this read-only audit. A template existing does not establish that it is automatically sent.

The subjects below have **no automatic `[KnifeRevive]` prefix** in this sender. Exact standard body follows each subject:


## paid

**Subject:** Your sale is in — here’s what happens next

> Payment is recorded. Your seller transfer is held for review. Open the order for shipping and earnings details.

## shipping

**Subject:** Add shipping details to keep your payout moving

> Add tracking when available. Without tracking, the review clock starts when you mark the order Shipped. Eligibility starts seven days later, subject to other checks and the approved batch.

## delivered

**Subject:** Delivered — your earnings are in review

> Independent delivery evidence was recorded. Any eligibility date is an estimate and remains subject to refund and dispute checks.

## eligible

**Subject:** Your earnings are ready for the next transfer review

> Your earnings passed the recorded checks for review. A transfer still requires owner approval and a final recheck.

## sent

**Subject:** We’ve sent your earnings to Stripe

> The verified transfer was sent to your Stripe Express account. This is not confirmation that your bank received it.

## held

**Subject:** An update on your payout

> Your seller transfer needs review. Open My Money for the available explanation, or contact KnifeRevive for help.

## refund

**Subject:** Your earnings changed for an order

> A refund or cancellation needs reconciliation. Open the existing order breakdown for recorded amounts and status.

## payout

**Subject:** Stripe payout update

> A processor payout update was recorded. Only the specifically verified payout status should be shown; a transfer is not bank receipt.

## owed

**Subject:** Your seller balance needs attention

> An existing reconciled seller balance needs review. No card or bank debit is authorized by this notice. Contact support to review the source records.

## recovery_review

**Subject:** Action needed: proposed recovery review

> Review the itemized amount, proposed method, legal basis and meaningful advance notice with support. This notification does not authorize a debit.

Each permitted message adds **Order #{suborder_id}**, **View My Money** and **Need a hand?**. The sent notice also adds **Transfer: {amount} {currency}**. `sent` requires a recorded verified transfer. The `payout`, `owed` and `recovery_review` templates are explicitly blocked in this release pending authoritative adapters. Some existing wording mentions owner approval; that is recorded copy, not a statement that the current automatic payout schedule is off. No payout controls were changed by this documentation task.


## Source and exact wording reference


### Source: [class-kr-connect-settlement-events.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-stripe-connect/includes/class-kr-connect-settlement-events.php)

SHA-256: `0cf847c5279bb64d9cd3058ec28f4e2239150f50dd01e570229b24b7fa086a11`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your sale is in — here’s what happens next
- Payment is recorded. Your seller transfer is held for review. Open the order for shipping and earnings details.
- Add shipping details to keep your payout moving
- Add tracking when available. Without tracking, the review clock starts when you mark the order Shipped. Eligibility starts seven days later, subject to other checks and the approved batch.
- Delivered — your earnings are in review
- Independent delivery evidence was recorded. Any eligibility date is an estimate and remains subject to refund and dispute checks.
- Your earnings are ready for the next transfer review
- Your earnings passed the recorded checks for review. A transfer still requires owner approval and a final recheck.
- We’ve sent your earnings to Stripe
- The verified transfer was sent to your Stripe Express account. This is not confirmation that your bank received it.
- An update on your payout
- Your seller transfer needs review. Open My Money for the available explanation, or contact KnifeRevive for help.
- Your earnings changed for an order
- A refund or cancellation needs reconciliation. Open the existing order breakdown for recorded amounts and status.
- Stripe payout update
- A processor payout update was recorded. Only the specifically verified payout status should be shown; a transfer is not bank receipt.
- Your seller balance needs attention
- An existing reconciled seller balance needs review. No card or bank debit is authorized by this notice. Contact support to review the source records.
- Action needed: proposed recovery review
- Review the itemized amount, proposed method, legal basis and meaningful advance notice with support. This notification does not authorize a debit.

<details><summary>Exact template/renderer source, beginning at line 58</summary>

```php
    public static function templates(): array {return array(
        'paid'=>array('Your sale is in — here’s what happens next','Payment is recorded. Your seller transfer is held for review. Open the order for shipping and earnings details.'),
        'shipping'=>array('Add shipping details to keep your payout moving','Add tracking when available. Without tracking, the review clock starts when you mark the order Shipped. Eligibility starts seven days later, subject to other checks and the approved batch.'),
        'delivered'=>array('Delivered — your earnings are in review','Independent delivery evidence was recorded. Any eligibility date is an estimate and remains subject to refund and dispute checks.'),
        'eligible'=>array('Your earnings are ready for the next transfer review','Your earnings passed the recorded checks for review. A transfer still requires owner approval and a final recheck.'),
        'sent'=>array('We’ve sent your earnings to Stripe','The verified transfer was sent to your Stripe Express account. This is not confirmation that your bank received it.'),
        'held'=>array('An update on your payout','Your seller transfer needs review. Open My Money for the available explanation, or contact KnifeRevive for help.'),
        'refund'=>array('Your earnings changed for an order','A refund or cancellation needs reconciliation. Open the existing order breakdown for recorded amounts and status.'),
        'payout'=>array('Stripe payout update','A processor payout update was recorded. Only the specifically verified payout status should be shown; a transfer is not bank receipt.'),
        'owed'=>array('Your seller balance needs attention','An existing reconciled seller balance needs review. No card or bank debit is authorized by this notice. Contact support to review the source records.'),
        'recovery_review'=>array('Action needed: proposed recovery review','Review the itemized amount, proposed method, legal basis and meaningful advance notice with support. This notification does not authorize a debit.')
    );}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- ">View My Money · <a href="

<details><summary>Exact template/renderer source, beginning at line 70</summary>

```php
    public static function dispatch(int $outbox_id): bool {
        if(!defined('KR_CONNECT_SETTLEMENT_NOTIFICATIONS_ENABLED')||KR_CONNECT_SETTLEMENT_NOTIFICATIONS_ENABLED!==true||!current_user_can('manage_options')){return false;}
        global $wpdb;$t=KR_Connect_Settlement_Store::table('outbox');$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM $t WHERE id=%d",$outbox_id),ARRAY_A);if(!$r||$r['state']!=='pending'){return false;}
        $row=KR_Connect_Settlement_Store::ledger((int)$r['ledger_id']);$seller=$row?get_userdata((int)$row['vendor_id']):null;$tpl=self::templates()[$r['kind']]??null;if(!$seller||!$tpl){return false;}
        // Sent emails require an actual recorded transfer, never a cron-started or queued status.
        if($r['kind']==='sent'&&(!$row['stripe_transfer_id']||!$row['transferred_at']||$row['status']!=='transferred')){return false;}
        // Payout/owed/recovery notices need richer authoritative adapters not supplied by this release.
        if(in_array($r['kind'],array('payout','owed','recovery_review'),true)){return false;}
        if($wpdb->query($wpdb->prepare("UPDATE $t SET state='sending' WHERE id=%d AND state='pending'",$outbox_id))!==1){return false;}
        $body='<p>'.esc_html($tpl[1]).'</p><p>Order #'.absint($row['suborder_id']).'</p>';
        if($r['kind']==='sent'){$body.='<p>Transfer: '.esc_html(KR_Connect_Money::from_minor((int)$row['transfer_amount'],$row['currency']).' '.strtoupper($row['currency'])).'</p>';}
        $body.='<p><a href="'.esc_url(wc_get_account_endpoint_url('my-money')).'">View My Money</a> · <a href="'.esc_url(home_url('/contact/')).'">Need a hand?</a></p>';
        $ok=wp_mail($seller->user_email,$tpl[0],WC()->mailer()->wrap_message($tpl[0],$body),array('Content-Type: text/html; charset=UTF-8'));
        $wpdb->update($t,array('state'=>$ok?'sent':'failed'),array('id'=>$outbox_id));return $ok;
    }
```

</details>
