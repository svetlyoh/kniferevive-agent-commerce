# Knife sharpening booking and service-stage emails

Audit date: October 29, 2026 (America/Los_Angeles).


Active Agent Commerce 0.6.1 and Seller Orders 1.7.0-rc1 provide distinct booking and order-stage messages.

## Booking request / confirmation

**Subject:** `KnifeRevive sharpening {stage} — {preferred_date}`

Stage is the booking event (requested or confirmed). Recipients are separate queued customer/seller/admin jobs.

**Body template:** “Sharpening booking {stage}. Reference: {booking_reference}”; “Requested day: {preferred_date} (Pacific time).”; “Handoff: {mode}; return: {return_mode}.”; {service_title} × {quantity}.

Conditional text: “The service day is confirmed.” / “The requested day awaits merchant confirmation.”; “Pay when you collect your sharpened knives.” / “Online payment verified on the original WooCommerce order.”; “Native order reference: {number} ({status}).” / “No WooCommerce order has been created yet.”

Role-specific endings: **Private booking link:** {secure_customer_link}; **Review in your signed-in seller booking inbox:** {seller_url}; **Review in WooCommerce → Agent Commerce:** {admin_url}. **Support:** {configured_booking_phone}.

## Order-stage notice to buyer

**Subject:** `[KnifeRevive] {stage_label} — Order #{order_number}`

| Stage | Exact stage label |
| --- | --- |
| blade-check | We received your knives / Blade check |
| sharpen-shape | Sharpening has started |
| hone-test | Hone & test |
| pay | Your knives are ready |
| return | Get them back / pickup-return information |

**Body:** `Your KnifeRevive sharpening order is now at: {stage_label}.`


## Source and exact wording reference


### Source: [BookingOutbox.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/email-catalogue-2026-10-29/version-matched-source/kniferevive-agent-commerce/includes/BookingOutbox.php)

SHA-256: `6a378bb7368a137e9d2ca38726176b23aee756f178d14e0ec1d7702af55382f0`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- KnifeRevive sharpening

<details><summary>Exact template/renderer source, beginning at line 64</summary>

```php
    public static function send(string $id): void {
        Store::lock('booking-mail:'.$id,static function()use($id){
            $job=Store::get($id,'booking_mail');$d=$job['data'];
            if($d['state']!=='pending' || $d['next_attempt']>time())return;
            $row=Store::get($d['booking_id'],'booking');
            if($row['data']['booking_state']==='cancelled' || !Booking::merchantVisible($row)){
                $d['state']='failed';$d['last_error']='obsolete_notification_suppressed';Store::update($id,$d);return;
            }
            // Product transfer, disabled vendor, or changed recipients cannot leak the original contact.
            if($d['role']==='seller' && (!BookingSeller::seller($row) || BookingSeller::seller($row)!==$d['seller_id'] || get_userdata($d['seller_id'])->user_email!==$d['recipient'])){
                $d['state']='failed';$d['last_error']='seller_recipient_changed';Store::update($id,$d);return;
            }
            if(!is_email($d['recipient'])){$d['state']='failed';$d['last_error']='invalid_recipient';Store::update($id,$d);return;}
            $d['state']='sending';$d['attempts']++;$d['started_at']=time();Store::update($id,$d);
            $failed=false;$succeeded=false;
            $onFail=static function()use(&$failed){$failed=true;};$onSuccess=static function()use(&$succeeded){$succeeded=true;};
            add_action('wp_mail_failed',$onFail);add_action('wp_mail_succeeded',$onSuccess);
            try{$accepted=wp_mail($d['recipient'],'KnifeRevive sharpening '.$d['stage'].' — '.$row['data']['input']['preferred_date'],self::message($row,$d),['X-Krev-Notification: '.$id]);}
            catch(\Throwable $e){$accepted=false;$failed=true;}
            finally{remove_action('wp_mail_failed',$onFail);remove_action('wp_mail_succeeded',$onSuccess);}
            $accepted=$accepted && !$failed;
            $d['state']=$accepted?'accepted_by_mailer':($d['attempts']>=5?'failed':'pending');
            $d['last_error']=$accepted?null:'mail_transport_failed';$d['next_attempt']=time()+min(3600,60*(2**min(5,$d['attempts'])));
            $d['history'][]=['at'=>time(),'attempt'=>$d['attempts'],'result'=>$accepted?'accepted_by_mailer':'failed','mail_hook_observed'=>$succeeded||$failed];
            Store::update($id,$d);
        });
        $job=Store::get($id,'booking_mail');if($job['data']['state']==='pending')self::schedule($job['owner']);
    }
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Sharpening booking
- Requested day:
- (Pacific time).
- The service day is confirmed.
- The requested day awaits merchant confirmation.
- Pay when you collect your sharpened knives.
- Online payment verified on the original WooCommerce order.
- Native order reference:
- No WooCommerce order has been created yet.
- Private booking link:
- Review in your signed-in seller booking inbox:
- Review in WooCommerce → Agent Commerce:

<details><summary>Exact template/renderer source, beginning at line 92</summary>

```php
    private static function message(array $row,array $job): string {
        $i=$row['data']['input'];$id=$row['id'];
        $body='Sharpening booking '.$job['stage'].'. Reference: '.$id."\nRequested day: ".$i['preferred_date'].' (Pacific time).'."\nHandoff: ".$i['mode'].'; return: '.$i['return_mode'].".\n";
        foreach($row['data']['catalog_snapshot'] as $p)$body.=$p['title'].' × '.$p['quantity']."\n";
        $body.=($row['data']['booking_state']==='confirmed'?'The service day is confirmed.':'The requested day awaits merchant confirmation.').($i['mode']==='pay_later_dropoff'?"\nPay when you collect your sharpened knives.\n":"\nOnline payment verified on the original WooCommerce order.\n");
        $order=BookingOrderBridge::linked($row)??BookingEvents::nativeOrder($row);
        $body.=$order?'Native order reference: '.$order->get_order_number().' ('.$order->get_status().").\n":"No WooCommerce order has been created yet.\n";
        if($job['role']==='customer')$body.='Private booking link: '.add_query_arg(['krev_agent'=>'booking','booking'=>$id],home_url('/')).'#booking_access='.Booking::accessToken($row)."\n";
        elseif($job['role']==='seller')$body.='Review in your signed-in seller booking inbox: '.BookingSeller::url()."\n";
        else $body.='Review in WooCommerce → Agent Commerce: '.admin_url('admin.php?page=krev-agent-commerce')."\n";
        return $body.'Support: '.Settings::get()['booking_phone']."\n";
    }
```

</details>


### Source: [class-krev-sharpening-workflow.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-seller-orders/includes/class-krev-sharpening-workflow.php)

SHA-256: `fa44496188a03196c35e9d8ed254a8c72c7b673b0921979e5165103ff7319cbf`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We received your knives / Blade check
- Sharpening has started
- Your knives are ready
- Get them back / pickup-return information
- Your KnifeRevive sharpening order is now at: %s.

<details><summary>Exact template/renderer source, beginning at line 122</summary>

```php
	private static function notify_customer( WC_Order $order, $stage ) {
		$messages = array( 'blade-check' => 'We received your knives / Blade check', 'sharpen-shape' => 'Sharpening has started', 'hone-test' => 'Hone & test', 'pay' => 'Your knives are ready', 'return' => 'Get them back / pickup-return information' );
		if ( empty( $messages[ $stage ] ) || ! $order->get_billing_email() ) {
			return;
		}
		wp_mail( $order->get_billing_email(), sprintf( '[KnifeRevive] %s — Order #%s', $messages[ $stage ], $order->get_order_number() ), sprintf( 'Your KnifeRevive sharpening order is now at: %s.', $messages[ $stage ] ) );
	}
```

</details>
