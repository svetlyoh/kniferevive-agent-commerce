# Seller onboarding and passkey encouragement

Audit date: October 29, 2026 (America/Los_Angeles).


Active Seller Onboarding 0.3.1. Transactional notices require onboarding email/flow flags, participating seller state and the recorded completion conditions. These are not a promise that every sign-up has received both messages.

## Stripe payout setup verified

**Subject:** Payout setup checked. Keep going.

**Heading:** One step closer.

**Body:** Stripe has verified your payout setup. Head back to KnifeRevive to finish your shop setup. Stripe can request more information later.

**Button:** Finish my setup

## Seller setup completed

**Subject:** Your KnifeRevive seller setup is saved

**Heading:** You’re set. What’s your first find?

**Body:** Your shop details, dispatch location and verified Stripe payout setup are saved. Add a passkey to secure your sign-in, then create your first listing. Listings still follow the marketplace’s review and publishing rules.

**Button:** Open my seller setup

## Shared footer

KNIFEREVIVE · SELLER SETUP

**Add a passkey** · **Need a hand?**

“We’ll never ask for your bank details or device PIN by email. Open KnifeRevive directly if you’re unsure about a link.”

Initial WooCommerce account-creation/password setup mail is a separate sender; see 11_NATIVE_WOOCOMMERCE.md and 12_ACCOUNT_SECURITY_AND_OTHER_SENDERS.md. No additional standalone “passkey added successfully” email was established by the source search.


## Source and exact wording reference


### Source: [class-notifications.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/onboarding-hotfix-dispatch-v4-2026-10-23/src/kniferevive-seller-onboarding/includes/class-notifications.php)

SHA-256: `e9b4edd2f9cdc0f4320b5f71ef991f30e71c496d40935f5106657b995ccebb21`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Payout setup checked. Keep going.
- One step closer.
- Stripe has verified your payout setup. Head back to KnifeRevive to finish your shop setup. Stripe can request more information later.
- Finish my setup
- Your KnifeRevive seller setup is saved
- You’re set. What’s your first find?
- Your shop details, dispatch location and verified Stripe payout setup are saved. Add a passkey to secure your sign-in, then create your first listing. Listings still follow the marketplace’s review and publishing rules.
- Open my seller setup

<details><summary>Exact template/renderer source, beginning at line 26</summary>

```php
 public static function copy(string $kind):array {
  return $kind==='payout_ready'
   ? ['subject'=>'Payout setup checked. Keep going.','heading'=>'One step closer.','body'=>'Stripe has verified your payout setup. Head back to KnifeRevive to finish your shop setup. Stripe can request more information later.','button'=>'Finish my setup','url'=>Flow::url('finish')]
   : ['subject'=>'Your KnifeRevive seller setup is saved','heading'=>'You’re set. What’s your first find?','body'=>'Your shop details, dispatch location and verified Stripe payout setup are saved. Add a passkey to secure your sign-in, then create your first listing. Listings still follow the marketplace’s review and publishing rules.','button'=>'Open my seller setup','url'=>Flow::url('finish')];
 }
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- KNIFEREVIVE · SELLER SETUP
- ">Need a hand? We’ll never ask for your bank details or device PIN by email. Open KnifeRevive directly if you’re unsure about a link.

<details><summary>Exact template/renderer source, beginning at line 31</summary>

```php
 public static function html(string $kind):string {
  $c=self::copy($kind);
  $body='<div style="background:#0c1521;color:#f4f1e8;padding:28px;border-radius:12px;font-family:Arial,sans-serif;line-height:1.6"><p style="color:#bef264;font-size:12px;letter-spacing:2px">KNIFEREVIVE · SELLER SETUP</p><p style="color:#f4f1e8">'.esc_html($c['body']).'</p><p><a style="display:inline-block;background:#bef264;color:#172033;padding:14px 20px;border-radius:8px;text-decoration:none;font-weight:bold" href="'.esc_url($c['url']).'">'.esc_html($c['button']).'</a></p><p><a style="color:#f4f1e8" href="'.esc_url(wc_get_account_endpoint_url('passkeys')).'">Add a passkey</a> · <a style="color:#f4f1e8" href="'.esc_url(home_url('/contact/')).'">Need a hand?</a></p><p style="font-size:13px;color:#b8c4d0">We’ll never ask for your bank details or device PIN by email. Open KnifeRevive directly if you’re unsure about a link.</p></div>';
  return WC()->mailer()->wrap_message($c['heading'],$body);
 }
```

</details>

<details><summary>Exact template/renderer source, beginning at line 11</summary>

```php
 public static function allowed(int $id,string $kind):bool {
  if(!Plugin::enabled('emails')||!Flow::enabled()||!Flow::participating($id)||!Status::seller($id))return false;
  if($kind==='payout_ready')return Status::stripe($id)['state']==='ready';
  return $kind==='setup_complete'&&(bool)get_user_meta($id,Flow::COMPLETE,true);
 }
```

</details>

<details><summary>Exact template/renderer source, beginning at line 36</summary>

```php
 public static function send($seller,$kind):void {
  $id=absint($seller);$kind=is_string($kind)?$kind:'';if(!in_array($kind,self::KINDS,true))return;
  $key=self::key($id,$kind);$event=get_option($key);if(!is_array($event)||!in_array($event['state']??'',['queued','retry'],true))return;
  $permitted=Plugin::enabled('emails')&&Flow::enabled()&&Flow::participating($id)&&Status::seller($id);
  // The queued event records a successful verification; delayed cron is not itself a loss of that evidence.
  if($kind==='payout_ready')$permitted=$permitted&&get_user_meta($id,'_kr_connect_account_status',true)==='ready';
  if($kind==='setup_complete')$permitted=$permitted&&(bool)get_user_meta($id,Flow::COMPLETE,true);
  if(!$permitted){$event['state']='blocked';update_option($key,$event,false);return;}
  $lock=$key.'_claim';if(!add_option($lock,gmdate('c'),'','no'))return;
  try {
   $event=get_option($key);if(!in_array($event['state']??'',['queued','retry'],true))return;
   $user=get_userdata($id);if(!$user||!is_email($user->user_email)){$event['state']='failed';update_option($key,$event,false);return;}
   $event['state']='sending';$event['attempts']=(int)$event['attempts']+1;update_option($key,$event,false);
   // A crashed/uncertain send stays "sending" for manual investigation, never an automatic duplicate.
   $c=self::copy($kind);$accepted=WC()->mailer()->send($user->user_email,$c['subject'],self::html($kind));
   $event['state']=$accepted?'accepted':($event['attempts']<3?'retry':'failed');$event['updated_at']=gmdate('c');update_option($key,$event,false);
   if(!$accepted&&$event['attempts']<3)self::schedule($id,$kind,300*$event['attempts']);
  }finally {delete_option($lock);}
 }
```

</details>
