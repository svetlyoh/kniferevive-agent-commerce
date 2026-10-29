# Seller orders, buyer questions and cancellation

Audit date: October 29, 2026 (America/Los_Angeles).


These senders belong to active Seller Orders 1.7.0-rc1 and Order Address & Contact 1.0.0. Plugin activity is verified; actual delivery, recipient settings and hooks were not exercised.

## New seller order

Recipient: each seller whose items are in the order. Trigger: processing or on-hold transition; per-seller sent marker prevents a repeat.

**Subject:** `[KnifeRevive] New seller order #{order_number}`

**Standard body:**

> You received a KnifeRevive order #{order_number}.
>
> {item_name} x {quantity}
>
> Manage your items: {seller_order_url}
>
> Buyers may request cancellation before you mark their items shipped. Approve a request using Buyer Request, or cancel unavailable items using Out of stock. Cancellation triggers a refund; the Support Crew handles refunds requiring manual processing.
>
> Questions about your right to cancel? KnifeRevive Support Crew (site admin): {support_email}

## Buyer message / cancellation request

Recipient: chosen order seller. Reply-To: signed-in buyer's verified account email.

**Subjects:**

- `[KnifeRevive] Buyer message — Order #{order_number}`
- `[KnifeRevive] Buyer cancellation request — Order #{order_number}`

**Body:** Buyer: {buyer_display_name} <{buyer_email}>; Order #{order_number}; {buyer_message}; “Reply to this email to respond directly to the buyer.” Cancellation requests also say: “The order is not cancelled yet. To approve, open Seller Orders and choose Cancel Order with reason Buyer Request.” Followed by {seller_order_url} and KnifeRevive Support Crew (site admin): {support_email}.

## Buyer question through the address/contact module

**Subject:** `[KnifeRevive] Buyer question — Order #{order_number}`

**Body:** “A buyer has a quick question about order #{order_number}.” {buyer_message}. “Reply to this email to respond directly to the buyer.” **Open your seller order** button.

These are two separate code paths; documenting both does not mean one click necessarily sends both.

## Seller cancellation

Recipients: buyer, seller and support (deduplicated).

**Subject:** `[KnifeRevive] Seller cancellation — Order #{order_number}`

**Body labels:** Order #; Reason; Cancellation / refund status; Refund amount ({amount} {currency}).

**Conditional text:** “The payment has not been confirmed refunded. The Support Crew will arrange the refund.” / “Only this seller's items and item taxes are included. Shared shipping and fees require Support Crew review. Other sellers' items remain active.” Ends with KnifeRevive Support Crew (site admin): {support_email}.


## Source and exact wording reference


### Source: [class-krev-order-contact.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-seller-orders/includes/class-krev-order-contact.php)

SHA-256: `d2d35774c002355b2c12323c45e7b9cdae61f3b068a9cc581f7b56a7be4bb34a`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- You received a KnifeRevive order #
- Manage your items:
- / Buyers may request cancellation before you mark their items shipped. Approve a request using Buyer Request, or cancel unavailable items using Out of stock. Cancellation triggers a refund; the Support Crew handles refunds requiring manual processing. Questions about your right to cancel?
- (site admin):
- [KnifeRevive] New seller order #

<details><summary>Exact template/renderer source, beginning at line 43</summary>

```php
	public static function seller_new_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) { return; }
		KREV_Order_Cancellations::with_lock( $order, static function ( $fresh ) {
			foreach ( KREV_Orders_Permissions::seller_ids_for_order( $fresh ) as $seller_id ) {
				$key = '_krev_seller_order_email_' . $seller_id;
				$seller = get_userdata( $seller_id );
				if ( $fresh->get_meta( $key ) || ! $seller || ! is_email( $seller->user_email ) ) { continue; }
				$items = array_filter( $fresh->get_items(), static function ( $item ) use ( $seller_id ) { return KREV_Orders_Reader::item_owner( $item ) === $seller_id; } );
				$body = 'You received a KnifeRevive order #' . $fresh->get_order_number() . ".\n\n";
				foreach ( $items as $item ) { $body .= $item->get_name() . ' x ' . $item->get_quantity() . "\n"; }
				$body .= "\nManage your items: " . wc_get_account_endpoint_url( 'seller-orders' ) . $fresh->get_id() . "/\n\nBuyers may request cancellation before you mark their items shipped. Approve a request using Buyer Request, or cancel unavailable items using Out of stock. Cancellation triggers a refund; the Support Crew handles refunds requiring manual processing.\n\nQuestions about your right to cancel? " . self::NAME . ' (site admin): ' . self::EMAIL;
				if ( wp_mail( $seller->user_email, '[KnifeRevive] New seller order #' . $fresh->get_order_number(), $body ) ) { $fresh->update_meta_data( $key, gmdate( 'c' ) ); $fresh->save(); }
			}
		} );
	}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Only the buyer who placed the order may message its seller.
- Seller not found on this order.
- Choose a valid message type.
- Enter a message of up to 5,000 characters.
- Cancellation requests are unavailable after shipment, completion, or cancellation.
- Your cancellation request was already sent. Seller approval is pending.
- Please wait a minute before sending another message.
- An email address is unavailable. Contact
- Buyer cancellation request
- Buyer message
- Reply to this email to respond directly to the buyer.
- The order is not cancelled yet. To approve, open Seller Orders and choose Cancel Order with reason Buyer Request.
- (site admin):
- s authenticated From address; Reply-To is the verified buyer account email. if ( ! wp_mail( $seller->user_email, $subject, $body, array(
- . $buyer->user_email ) ) ) { return new WP_Error(
- ); } set_transient( $rate_key, 1, MINUTE_IN_SECONDS ); return array(

<details><summary>Exact template/renderer source, beginning at line 60</summary>

```php
	public static function send( WC_Order $order, array $data ) {
		if ( ! get_current_user_id() || get_current_user_id() !== (int) $order->get_customer_id() ) { return new WP_Error( 'krev_contact_forbidden', 'Only the buyer who placed the order may message its seller.', array( 'status' => 403 ) ); }
		$seller_id = absint( $data['seller_id'] ?? 0 );
		if ( ! in_array( $seller_id, KREV_Orders_Permissions::seller_ids_for_order( $order ), true ) ) { return new WP_Error( 'krev_contact_seller', 'Seller not found on this order.', array( 'status' => 403 ) ); }
		$type = sanitize_key( $data['type'] ?? 'message' );
		if ( ! in_array( $type, array( 'message', 'cancel-request' ), true ) ) { return new WP_Error( 'krev_contact_type', 'Choose a valid message type.', array( 'status' => 422 ) ); }
		$message = sanitize_textarea_field( $data['message'] ?? '' );
		if ( '' === $message || strlen( $message ) > 5000 ) { return new WP_Error( 'krev_contact_message', 'Enter a message of up to 5,000 characters.', array( 'status' => 422 ) ); }
		return KREV_Order_Cancellations::with_lock( $order, static function ( $fresh ) use ( $seller_id, $type, $message ) {
			$state = KREV_Order_Cancellations::state( $fresh, $seller_id );
			if ( 'cancel-request' === $type && ! $state['can_request'] ) { return new WP_Error( 'krev_cancel_unavailable', 'Cancellation requests are unavailable after shipment, completion, or cancellation.', array( 'status' => 409 ) ); }
			if ( 'cancel-request' === $type && ! empty( $state['requested_at'] ) ) { return array( 'message' => 'Your cancellation request was already sent. Seller approval is pending.' ); }
			$rate_key = 'krev_contact_' . $fresh->get_id() . '_' . get_current_user_id() . '_' . $seller_id;
			if ( get_transient( $rate_key ) ) { return new WP_Error( 'krev_contact_rate', 'Please wait a minute before sending another message.', array( 'status' => 429 ) ); }
			$buyer = get_userdata( get_current_user_id() ); $seller = get_userdata( $seller_id );
			if ( ! $buyer || ! is_email( $buyer->user_email ) || ! $seller || ! is_email( $seller->user_email ) ) { return new WP_Error( 'krev_contact_email', 'An email address is unavailable. Contact ' . self::EMAIL . '.', array( 'status' => 422 ) ); }
			$subject = '[KnifeRevive] ' . ( 'cancel-request' === $type ? 'Buyer cancellation request' : 'Buyer message' ) . ' — Order #' . $fresh->get_order_number();
			$body = 'Buyer: ' . $buyer->display_name . ' <' . $buyer->user_email . ">\nOrder #" . $fresh->get_order_number() . "\n\n" . $message;
			$body .= "\n\nReply to this email to respond directly to the buyer.\n";
			if ( 'cancel-request' === $type ) { $body .= "The order is not cancelled yet. To approve, open Seller Orders and choose Cancel Order with reason Buyer Request.\n"; }
			$body .= wc_get_account_endpoint_url( 'seller-orders' ) . $fresh->get_id() . "/\n\n" . self::NAME . ' (site admin): ' . self::EMAIL;
			// Keep the site's authenticated From address; Reply-To is the verified buyer account email.
			if ( ! wp_mail( $seller->user_email, $subject, $body, array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $buyer->user_email ) ) ) { return new WP_Error( 'krev_contact_send', 'The email could not be sent. Please try again or contact ' . self::EMAIL . '.', array( 'status' => 502 ) ); }
			if ( 'cancel-request' === $type ) {
				$fresh->update_meta_data( '_krev_cancel_request_' . $seller_id, array( 'requested_at' => gmdate( 'c' ), 'buyer_id' => get_current_user_id(), 'message' => $message ) );
				$fresh->save();
				$fresh->add_order_note( 'Buyer cancellation request emailed to seller #' . $seller_id . '. Seller approval pending.' );
			}
			set_transient( $rate_key, 1, MINUTE_IN_SECONDS );
			return array( 'message' => 'cancel-request' === $type ? 'Cancellation request sent to the seller. Your order remains active until the seller approves.' : 'Message sent to the seller. Replies will go to your account email.' );
		} );
	}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- (site admin):
- Questions about seller cancellations or returns? Email us.
- Questions about your order, cancellations, or returns? Email us.

<details><summary>Exact template/renderer source, beginning at line 34</summary>

```php
	public static function email_support( $order, $sent_to_admin, $plain_text, $email ) {
		if ( ! $order instanceof WC_Order ) { return; }
		$seller = $sent_to_admin || ( is_object( $email ) && str_contains( (string) $email->id, 'vendor' ) );
		if ( $plain_text ) {
			echo "\n" . self::NAME . ' (site admin): ' . self::EMAIL . "\n";
			echo $seller ? "Questions about seller cancellations or returns? Email us.\n" : "Questions about your order, cancellations, or returns? Email us.\n";
		} else { echo wp_kses_post( self::support_html( $seller ) ); }
	}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Questions about your right to cancel an order, a buyer cancellation request, or a return?
- Questions about your order, cancellations, or returns?
- (site admin): <a href="mailto:

<details><summary>Exact template/renderer source, beginning at line 19</summary>

```php
	public static function support_html( $seller = false ) {
		$text = $seller ? 'Questions about your right to cancel an order, a buyer cancellation request, or a return?' : 'Questions about your order, cancellations, or returns?';
		return '<p class="krev-support">' . esc_html( $text ) . ' Contact <strong>' . esc_html( self::NAME ) . '</strong> (site admin): <a href="mailto:' . esc_attr( self::EMAIL ) . '">' . esc_html( self::EMAIL ) . '</a>.</p>';
	}
```

</details>


### Source: [class-krev-order-cancellations.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-seller-orders/includes/class-krev-order-cancellations.php)

SHA-256: `e8b3e8264e9bfd2f94519a9865dd020a90d57769be36a28bd9e85160324ad5b7`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Order #%s Reason: %s Cancellation / refund status: %s Refund amount: %s %s
- The payment has not been confirmed refunded. The Support Crew will arrange the refund.
- Only this seller's items and item taxes are included. Shared shipping and fees require Support Crew review. Other sellers' items remain active.
- (site admin):
- [KnifeRevive] Seller cancellation — Order #

<details><summary>Exact template/renderer source, beginning at line 109</summary>

```php
	private static function notify( WC_Order $order, array $cancel ) {
		$body = sprintf( "Order #%s\nReason: %s\nCancellation / refund status: %s\nRefund amount: %s %s\n", $order->get_order_number(), $cancel['reason_label'], $cancel['status'], $cancel['amount'], $cancel['currency'] );
		if ( 'refund-pending' === $cancel['status'] ) { $body .= "The payment has not been confirmed refunded. The Support Crew will arrange the refund.\n"; }
		if ( ! empty( $cancel['shared_charges_review'] ) ) { $body .= "Only this seller's items and item taxes are included. Shared shipping and fees require Support Crew review. Other sellers' items remain active.\n"; }
		$body .= "\n" . KREV_Order_Contact::NAME . ' (site admin): ' . KREV_Order_Contact::EMAIL;
		$buyer = get_userdata( $order->get_customer_id() ); $seller = get_userdata( $cancel['seller_id'] );
		foreach ( array_unique( array_filter( array( $buyer ? $buyer->user_email : $order->get_billing_email(), $seller ? $seller->user_email : '', KREV_Order_Contact::EMAIL ) ) ) as $recipient ) { wp_mail( $recipient, '[KnifeRevive] Seller cancellation — Order #' . $order->get_order_number(), $body ); }
	}
```

</details>


### Source: [service.php](C:/Users/Svet/Documents/KnifeRevive_Agent_Commerce/wordpress/kniferevive-order-address-contact/includes/service.php)

SHA-256: `eaa589718dbefdadf3b201db709c0687926e5c3e663f8c831a881d6a1f9fd93a`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Sign in as the buyer and choose a seller from this order.
- Reopen the message composer.
- Write a message of up to 3,000 characters.
- Email is unavailable. Use an available contact button or contact support.
- Message identifier conflict.
- This message is already saved. Reopen it without edits to check its email status.
- WHERE kind='message' AND buyer_order_id=%d AND seller_id=%d AND actor_id=%d AND created_at>=%s LIMIT 1
- Give it a minute before sending another message.
- A buyer has a quick question about order #
- Reply to this email to respond directly to the buyer. <a href="
- ">Open your seller order
- [KnifeRevive] Buyer question — Order #
- Message sent. Watch your account email for the seller’s reply.
- Your message is saved and queued. Check back shortly.
- Your message is saved, but the email could not be sent. Contact support if it does not retry.
- Your message is being submitted. Please check back before sending another.
- Email status is uncertain. Contact support before sending again.
- Your message is saved. Contact support to check its email status.

<details><summary>Exact template/renderer source, beginning at line 109</summary>

```php
    public static function message(int $id,array $input) {
        $order=wc_get_order($id);$scopes=$order?self::buyer_scopes($order):[];$seller=(int)($input['seller_id']??0);$scope=null;
        foreach($scopes as $s)if($s['seller_id']===$seller)$scope=$s;
        if(!$scope)return self::error('Sign in as the buyer and choose a seller from this order.',403);
        $key=$input['request_key']??'';$message=$input['message']??'';
        if(!is_string($key)||!preg_match('/^[0-9a-f-]{36}$/D',$key)||!is_string($message))return self::error('Reopen the message composer.',422);
        $message=sanitize_textarea_field($message);if(mb_strlen($message)<1||mb_strlen($message)>3000)return self::error('Write a message of up to 3,000 characters.',422);
        $u=get_userdata(get_current_user_id());$vendor=get_userdata($seller);if(!$u||!is_email($u->user_email)||!$vendor||!is_email($vendor->user_email))return self::error('Email is unavailable. Use an available contact button or contact support.',422);
        $result=KREV_Order_Cancellations::with_lock($scope['parent'],static function($fresh)use($scope,$key,$message,$u,$vendor,$id){
            $old=KREV_OAC_Store::request($key);if($old){if($old['kind']!=='message'||(int)$old['actor_id']!==$u->ID||(int)$old['seller_id']!==$vendor->ID||(int)$old['buyer_order_id']!==$scope['parent']->get_id())return self::error('Message identifier conflict.',409);$saved=json_decode($old['payload'],true);if(($saved['message_hash']??'')!==hash('sha256',$message))return self::error('This message is already saved. Reopen it without edits to check its email status.',409);return KREV_OAC_Store::public_row($old);}
            global $wpdb;$recent=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.KREV_OAC_Store::table()." WHERE kind='message' AND buyer_order_id=%d AND seller_id=%d AND actor_id=%d AND created_at>=%s LIMIT 1",$scope['parent']->get_id(),$vendor->ID,$u->ID,gmdate('Y-m-d H:i:s',time()-60)));
            if($recent)return self::error('Give it a minute before sending another message.',429);
            $body='<p>A buyer has a quick question about order #'.esc_html($fresh->get_order_number()).'.</p><p>'.nl2br(esc_html($message)).'</p><p>Reply to this email to respond directly to the buyer.</p><p><a href="'.esc_url(wc_get_account_endpoint_url('seller-orders').$scope['order']->get_id().'/').'">Open your seller order</a></p>';
            $event=KREV_OAC_Store::insert(['request_key'=>$key,'kind'=>'message','order_id'=>$scope['order']->get_id(),'buyer_order_id'=>$fresh->get_id(),'seller_id'=>$vendor->ID,'actor_id'=>$u->ID,'payload'=>wp_json_encode(['message_hash'=>hash('sha256',$message)]),'recipient'=>$vendor->user_email,'subject'=>'[KnifeRevive] Buyer question — Order #'.$fresh->get_order_number(),'body'=>$body,'reply_to'=>$u->user_email]);
            return is_wp_error($event)?$event:KREV_OAC_Store::public_row(KREV_OAC_Store::get($event));
        });
        if(is_wp_error($result))return $result;KREV_OAC_Store::queue($result['id']);$row=KREV_OAC_Store::get($result['id']);return ['id'=>$result['id'],'notification'=>$row['mail_state'],'message'=>['accepted'=>'Message sent. Watch your account email for the seller’s reply.','pending'=>'Your message is saved and queued. Check back shortly.','failed'=>'Your message is saved, but the email could not be sent. Contact support if it does not retry.','sending'=>'Your message is being submitted. Please check back before sending another.','review-required'=>'Email status is uncertain. Contact support before sending again.'][$row['mail_state']]??'Your message is saved. Contact support to check its email status.'];
    }
```

</details>
