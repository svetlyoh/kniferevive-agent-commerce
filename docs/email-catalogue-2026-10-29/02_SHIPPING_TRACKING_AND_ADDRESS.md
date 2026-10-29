# Shipping, tracking, screenshots and address changes

Audit date: October 29, 2026 (America/Los_Angeles).


Seller Orders 1.7.0-rc1 / Order Address & Contact 1.0.0. Buyer receives the shipping message. Seller email is Reply-To when valid. Shipment email feature flag must allow sending.

## Shipment subject variants

Each has the exact pattern **`KnifeRevive: {heading} - Order #{order_number}`**.

| Event / condition | Heading inserted into subject |
| --- | --- |
| Shipped, tracked | Your items have shipped |
| Shipped, untracked | Your items have shipped - tracking not provided |
| Tracking added | Tracking is now available |
| Corrected shipment | Your shipment details were updated |
| Previous tracking changed, new tracking present | Your tracking number has been updated |
| Previous tracking changed, now untracked | Tracking details have been updated |
| Label prepared only | Your shipping label is ready |
| Unused label voided | An unused shipping label was canceled |
| Fallback | Shipping update |

## Shared standard body

“Your seller has sent you a shipping update about your KnifeRevive order.”

Sections/labels: **Order details**, **Order**, **Placed**, **Seller**, **Carrier**, **Service**, **Tracking number** (or **New tracking number**), **Items in this shipment**, **Shipped**, **Estimated arrival**, **Message from your seller**, **Seller note**, **Screenshots attached**, **Need a hand?**

Values are populated from the order and committed shipment: order number/date/timezone, store name, carrier/service, tracking, purchased item names and quantities, actual shipped date, optional arrival range, seller-formatted explanation, optional seller note, attachment descriptions.

Conditional standard sentences:

- “Use the new tracking number below. The previous number is no longer the current tracking for this shipment.”
- “The previous tracking number is no longer current. Ask your seller for the latest details.”
- “Previous tracking - no longer current” (old number is struck through).
- “Carrier tracking link unavailable. Ask your seller for details.”
- “Label canceled. The unused label is no longer current. Check your order for any other shipment or new label.”
- “Label ready. Your seller has a shipping label. Carrier handover and delivery have not been confirmed.”
- “Actual mailing date not provided.”
- “(seller estimate, not guaranteed).” / “No reliable arrival estimate has been provided.”
- “The images below are attached to this email as separate files. These are seller-supplied screenshots, not carrier verification.”
- “Ask {store_name} directly about this shipment.”
- “You can also reply to this email.”
- “Checked out as a guest? Use your original order confirmation or reply to your seller for help accessing your order.”

Buttons/links: **Track on {carrier}**, **Visit {seller_name} storefront**, **Need a hand? Contact your seller**, **View your order and seller contact options** (sign in required), **Contact KnifeRevive support**. Each Screenshot {index} ({extension}) is a separate attachment. Carrier/tracking explanations vary by service; see the exact source reference below.

## Shipping address update

**Subject:** `[KnifeRevive] Shipping address updated — Order #{order_number}`

**Body:** “Your seller, {seller_name}, updated the shipping address for their items in order #{order_number}.”

Labels: **Ship to — current address**, **Previous address — no longer used for these items** (old address struck through), **Reason**, **Applies to these items** ({item_name} × {quantity}).

“If you did not request this change, reply to this email or contact KnifeRevive support before dispatch.”

“This updates the delivery address only. Billing details, item prices, taxes, shipping charges and payment remain unchanged. A shipping-address update is not confirmation of dispatch.”

Links: **View your order**, **Contact support**. Address values and explanation are variable, not generic copy.


## Source and exact wording reference


### Source: [class-krev-shipment-mail.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-seller-orders/includes/class-krev-shipment-mail.php)

SHA-256: `b3da040c8533b911286234690ff5c6e03829e4333033800c080bbd883ca22e7b`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your items have shipped
- Your items have shipped - tracking not provided
- Tracking is now available
- Your shipment details were updated
- An unused shipping label was canceled
- Your shipping label is ready
- Your tracking number has been updated
- Tracking details have been updated
- Shipping update
- Marketplace seller
- Service not provided
- updated tracking for
- shared a shipping update for
- their items in order #
- Date unavailable
- Your seller has sent you a shipping update about your KnifeRevive order. Order details Order #
- Use the new tracking number below. The previous number is no longer the current tracking for this shipment.
- The previous tracking number is no longer current. Ask your seller for the latest details.
- New tracking number
- Tracking number
- Not provided
- " style="display:inline-block;background:#173e33;color:#ffffff!important;padding:12px 18px;text-decoration:none;border-radius:8px;font-weight:700">Track on
- Carrier tracking link unavailable. Ask your seller for details.
- Previous tracking - no longer current
- Items in this shipment
- Label canceled. The unused label is no longer current. Check your order for any other shipment or new label.
- Label ready. Your seller has a shipping label. Carrier handover and delivery have not been confirmed.
- Actual mailing date not provided.
- Estimated arrival:
- (seller estimate, not guaranteed).
- No reliable arrival estimate has been provided.
- Message from your seller
- Seller note:
- Screenshots attached The images below are attached to this email as separate files. These are seller-supplied screenshots, not carrier verification.
- Question about KnifeRevive order #
- directly about this shipment. <a href="
- You can also reply to this email.
- ">View your order and seller contact options (sign in required). Checked out as a guest? Use your original order confirmation or reply to your seller for help accessing your order. <a href="
- ">Contact KnifeRevive support

<details><summary>Exact template/renderer source, beginning at line 90</summary>

```php
    public static function message(WC_Order $order,array $s,string $kind): array {
        $buyer=self::buyer_order($order);$previous=$s['previous_tracking']??null;
        $tracked=$kind!=='label_voided'&&($s['tracking_mode']??'')==='tracked'&&!empty($s['tracking_number']);
        $headings=['shipped'=>$tracked?'Your items have shipped':'Your items have shipped - tracking not provided','tracking_added'=>'Tracking is now available','corrected'=>'Your shipment details were updated'];
        $heading=$kind==='label_voided'?'An unused shipping label was canceled':($kind==='label_ready'?'Your shipping label is ready':($previous?($tracked?'Your tracking number has been updated':'Tracking details have been updated'):($headings[$kind]??'Shipping update')));
        $seller=get_userdata((int)$s['seller_id']);$info=function_exists('dokan_get_store_info')?dokan_get_store_info((int)$s['seller_id']):[];
        $seller_name=$info['store_name']??($seller?$seller->display_name:'Marketplace seller');
        $carrier=self::carrier($s);$service=KREV_Shipment_Services::services()[$s['service']]['label']??'Service not provided';
        if(($s['service']??'')==='other'&&!empty($s['service_description']))$service=$s['service_description'];
        $body='<p style="font-size:16px;line-height:1.6">'.esc_html($seller_name).' has '.($previous?'updated tracking for':'shared a shipping update for').' their items in order <strong>#'.esc_html($buyer->get_order_number()).'</strong>.</p>';
        $placed=$buyer->get_date_created();$date=$placed?wp_date('F j, Y g:i a T',$placed->getTimestamp(),wp_timezone()).' ('.wp_timezone_string().')':'Date unavailable';
        $body='<p style="font-size:16px;line-height:1.6">Your seller has sent you a shipping update about your KnifeRevive order.</p><h2 style="font-size:20px">Order details</h2><table role="presentation" width="100%" style="width:100%;table-layout:fixed;border-collapse:collapse"><tr><td style="width:25%;padding:8px;vertical-align:top"><strong>Order</strong></td><td style="padding:8px;overflow-wrap:anywhere">#'.esc_html($buyer->get_order_number()).'</td></tr><tr><td style="padding:8px;vertical-align:top"><strong>Placed</strong></td><td style="padding:8px;overflow-wrap:anywhere">'.esc_html($date).'</td></tr><tr><td style="padding:8px;vertical-align:top"><strong>Seller</strong></td><td style="padding:8px;overflow-wrap:anywhere">'.esc_html($seller_name).'</td></tr></table>';
        if($previous)$body.='<p style="font-size:16px;line-height:1.6">'.($tracked?'Use the new tracking number below. The previous number is no longer the current tracking for this shipment.':'The previous tracking number is no longer current. Ask your seller for the latest details.').'</p>';
        $body.='<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="width:100%;table-layout:fixed;border:1px solid #d9e2dc;border-radius:10px"><tr><td style="padding:16px;word-break:break-word;overflow-wrap:anywhere;font-size:16px;line-height:1.6"><strong>Carrier</strong><br>'.esc_html($carrier).'<br><br><strong>Service</strong><br>'.esc_html($service).'<br><br><strong>'.($previous?'New tracking number':'Tracking number').'</strong><br>';
        $body.=$tracked?'<span style="font-size:18px;font-weight:700;word-break:break-all;overflow-wrap:anywhere">'.esc_html($s['tracking_number']).'</span>':'Not provided';
        $url=$tracked?KREV_Shipment_Services::url($s['carrier'],$s['tracking_number']):'';
        if($url)$body.='<p style="margin:16px 0 0"><a href="'.esc_url($url).'" style="display:inline-block;background:#173e33;color:#ffffff!important;padding:12px 18px;text-decoration:none;border-radius:8px;font-weight:700">Track on '.esc_html($carrier).'</a></p>';
        elseif($tracked)$body.='<p>Carrier tracking link unavailable. Ask your seller for details.</p>';
        $body.='</td></tr></table>';
        if($previous){
            $old_service=KREV_Shipment_Services::services()[$previous['service']??'unknown']['label']??'Service not provided';
            if(($previous['service']??'')==='other'&&!empty($previous['service_description']))$old_service=$previous['service_description'];
            $body.='<p style="font-size:15px;line-height:1.6"><strong>Previous tracking - no longer current</strong><br>'.esc_html(self::carrier($previous).' - '.$old_service).'<br><del style="text-decoration:line-through;word-break:break-all;overflow-wrap:anywhere">'.esc_html($previous['tracking_number']).'</del></p>';
        }
        $body.='<h3 style="font-size:18px">Items in this shipment</h3><ul style="padding-left:20px">';
        foreach($s['items'] as $id=>$qty){$item=$order->get_item($id);if($item)$body.='<li style="font-size:16px;line-height:1.6;overflow-wrap:anywhere">'.esc_html($item->get_name().' x '.$qty).'</li>';}
        $body.='</ul><p>'.esc_html(KREV_Shipment_Services::tracking_text($s)).'</p>';
        $body.=$kind==='label_voided'?'<p><strong>Label canceled.</strong> The unused label is no longer current. Check your order for any other shipment or new label.</p>':($kind==='label_ready'?'<p><strong>Label ready.</strong> Your seller has a shipping label. Carrier handover and delivery have not been confirmed.</p>':'<p><strong>Shipped:</strong> '.esc_html($s['date_shipped']?:'Actual mailing date not provided.').'</p>');
        $body.=$s['eta_start']?'<p><strong>Estimated arrival:</strong> '.esc_html($s['eta_start'].' - '.$s['eta_end']).' (seller estimate, not guaranteed).</p>':'<p>No reliable arrival estimate has been provided.</p>';
        if(!$tracked&&!empty($s['reason_note']))$body.='<h2 style="font-size:20px">Message from your seller</h2><div style="font-size:16px;line-height:1.6;overflow-wrap:anywhere;word-break:break-word">'.KREV_Shipment_Message::html($s['reason_note']).'</div>';
        if($s['seller_note'])$body.='<p><strong>Seller note:</strong> '.nl2br(esc_html($s['seller_note'])).'</p>';
        if(!empty($s['message_screenshots'])){
            $body.='<h2 style="font-size:20px">Screenshots attached</h2><p>The images below are attached to this email as separate files. These are seller-supplied screenshots, not carrier verification.</p><ul>';
            foreach($s['message_screenshots'] as $i=>$image)$body.='<li>Screenshot '.esc_html($i+1).' ('.esc_html(strtoupper($image['extension']??'image')).')</li>';
            $body.='</ul>';
        }
        if(function_exists('dokan_get_store_url'))$body.='<p><a href="'.esc_url(dokan_get_store_url((int)$s['seller_id'])).'">Visit '.esc_html($seller_name).' storefront</a></p>';
        $email=$seller&&is_email($seller->user_email)?$seller->user_email:'';
        $order_url=$buyer->get_view_order_url().'#krev-shipping-delivery';
        $contact=$email?'mailto:'.$email.'?subject='.rawurlencode('Question about KnifeRevive order #'.$buyer->get_order_number()):$order_url;
        $body.='<h3 style="font-size:20px;margin-top:24px">Need a hand?</h3><p>Ask '.esc_html($seller_name).' directly about this shipment.</p><p><a href="'.esc_url($contact).'" style="display:inline-block;background:#1b6b4d;color:#ffffff!important;padding:14px 20px;text-decoration:none;border-radius:8px;font-size:16px;font-weight:700">Need a hand? Contact your seller</a></p>';
        $body.='<p>'.($email?'You can also reply to this email. ':'').'<a href="'.esc_url($order_url).'">View your order and seller contact options</a> (sign in required).</p><p>Checked out as a guest? Use your original order confirmation or reply to your seller for help accessing your order.</p><p><a href="'.esc_url('mailto:'.KREV_Order_Contact::EMAIL).'">Contact KnifeRevive support</a></p>';
        $headers=['Content-Type: text/html; charset=UTF-8'];if($email)$headers[]='Reply-To: '.$email;
        return ['heading'=>$heading,'subject'=>'KnifeRevive: '.$heading.' - Order #'.$buyer->get_order_number(),'body'=>$body,'headers'=>$headers];
    }
```

</details>


### Source: [class-krev-shipment-services.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-seller-orders/includes/class-krev-shipment-services.php)

SHA-256: `00cf2f7adca528d60e37768373799d8191b45f36e13f2e8a7ceedc2c3cd928da`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Label ready. This tracking number does not confirm carrier handover or delivery.
- Tracking supplied by seller. A number is not proof of carrier acceptance.
- Tracking not included with this mail service. Ordinary USPS First-Class Mail letters and flats do not include USPS Tracking.
- Seller reports that this service does not include tracking.
- No tracking information has been provided for this shipment.

<details><summary>Exact template/renderer source, beginning at line 65</summary>

```php
    public static function tracking_text($s) {
        if(($s['state']??'')==='label_ready')return 'Label ready. This tracking number does not confirm carrier handover or delivery.';
        if($s['tracking_mode']==='tracked')return 'Tracking supplied by seller. A number is not proof of carrier acceptance.';
        if(!empty(self::services()[$s['service']]['untracked']) && $s['untracked_reason']==='service_without_tracking')return 'Tracking not included with this mail service. Ordinary USPS First-Class Mail letters and flats do not include USPS Tracking.';
        return $s['untracked_reason']==='service_without_tracking'?'Seller reports that this service does not include tracking.':'No tracking information has been provided for this shipment.';
    }
```

</details>


### Source: [service.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-order-address-contact/includes/service.php)

SHA-256: `eaa589718dbefdadf3b201db709c0687926e5c3e663f8c831a881d6a1f9fd93a`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your seller,
- , updated the shipping address for their items in order #
- . Ship to — current address
- Previous address — no longer used for these items
- Applies to these items
- If you did not request this change, reply to this email or contact KnifeRevive support before dispatch. <a href="
- ">View your order · <a href="
- ">Contact support This updates the delivery address only. Billing details, item prices, taxes, shipping charges and payment remain unchanged. A shipping-address update is not confirmation of dispatch.

<details><summary>Exact template/renderer source, beginning at line 103</summary>

```php
    public static function address_email(array $s,array $d): string {
        $body='<p>Your seller, <strong>'.esc_html(self::seller_name($s['seller_id'])).'</strong>, updated the shipping address for their items in order #'.esc_html($s['parent']->get_order_number()).'.</p><h3>Ship to — current address</h3><p>'.self::formatted($d['after']).'</p><h3>Previous address — no longer used for these items</h3><p><del>'.self::formatted($d['before']).'</del></p><p><strong>Reason:</strong> '.nl2br(esc_html($d['reason'])).'</p><h3>Applies to these items</h3><ul>';
        foreach($s['order']->get_items() as $item)$body.='<li>'.esc_html($item->get_name()).' × '.esc_html($item->get_quantity()).'</li>';
        $body.='</ul><p>If you did not request this change, reply to this email or contact KnifeRevive support before dispatch.</p><p><a href="'.esc_url($s['parent']->get_view_order_url()).'">View your order</a> · <a href="'.esc_url(home_url('/contact/')).'">Contact support</a></p><p>This updates the delivery address only. Billing details, item prices, taxes, shipping charges and payment remain unchanged. A shipping-address update is not confirmation of dispatch.</p>';
        return $body;
    }
```

</details>
