<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

final class BookingFrontend {
    public static function render(): never {
        nocache_headers();header('Referrer-Policy: no-referrer');header('X-Robots-Tag: noindex, nofollow, noarchive');header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; connect-src 'self'; img-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
        $message='';$row=null;$owner='';$id=(string)wp_unslash($_GET['booking']??'');
        try {
            if(!Booking::enabled() && !$id)Domain::fail('BOOKING_DISABLED','Booking requests are temporarily unavailable. Contact KnifeRevive.',503);
            if($id){$owner=Api::bookingOwner($id);$row=Booking::get($id,$owner);}
            if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
                $post=wp_unslash($_POST);
                if($id){ListingFrontend::authorizeForm($owner,$id,$post);}
                else {
                    if(!Api::firstPartyForm())Domain::fail('FORBIDDEN','Submit your request on the KnifeRevive booking page.',403);
                    if(!wp_verify_nonce($post['csrf']??'','krev_booking_new'))Domain::fail('FORBIDDEN','Reload the booking form and try again.',403);
                    $key=Domain::key($post['request_key']??'');$request=new \WP_REST_Request('POST');$request->set_header('Content-Type','application/json');$request->set_header('Idempotency-Key',$key);$request->set_body('{}');
                    $session=Api::session($request);$owner=$session['session_id'];
                    setcookie('krev_agent_session',$session['session_token'],['expires'=>strtotime($session['expires_at']),'path'=>'/','secure'=>is_ssl(),'httponly'=>true,'samesite'=>'Strict']);
                    $items=[];foreach((array)($post['quantities']??[]) as $product=>$quantity){if(!ctype_digit((string)$product) || !ctype_digit((string)$quantity))Domain::fail('INVALID_REQUEST','Choose whole knife quantities.');if((int)$quantity>0)$items[]=['product_id'=>(int)$product,'quantity'=>(int)$quantity];}
                    $row=Booking::create(['items'=>$items,'mode'=>$post['mode']??'','preferred_date'=>$post['preferred_date']??'','postal_code'=>$post['postcode']??'','return_mode'=>$post['return_mode']??'customer_collection','notes'=>$post['notes']??''],$owner,$key);$id=$row['id'];
                }
                $action=$post['action']??'submit';
                if($action==='checkout'){
                    if(($post['accept']??'')!=='yes')Domain::fail('AUTHORIZATION_REQUIRED','Choose to prepare your prepaid checkout.',403);
                    $intent=Booking::checkout($id,$owner);$response=ListingCheckout::response($intent,$intent['owner']);wp_safe_redirect($response['review_url'],303);exit;
                }
                if($action==='cancel'){$row=Booking::cancel($id,$owner);$message='Booking cancelled. No refund was issued; contact KnifeRevive about any payment.';}
                elseif($action==='submit'){
                    if(($post['accept']??'')!=='yes')Domain::fail('AUTHORIZATION_REQUIRED','Confirm that you want to send this booking request.',403);
                    $contact=['postal_code'=>$post['postcode']??'','customer'=>['name'=>$post['name']??'','email'=>$post['email']??'','phone'=>$post['phone']??'']];
                    if($row['data']['input']['mode']==='prepaid_pickup' || $row['data']['input']['return_mode']==='courier_delivery' || !empty($post['address_1'])){
                        $contact['pickup_address']=['address_1'=>$post['address_1']??'','address_2'=>$post['address_2']??'','city'=>$post['city']??'','state'=>'CA','country'=>'US','postcode'=>$post['postcode']??''];
                    }
                    $row=Booking::submit($id,$owner,$contact);Booking::accessCookie($row);wp_safe_redirect(add_query_arg(['krev_agent'=>'booking','booking'=>$id],home_url('/')),303);exit;
                }else Domain::fail('INVALID_REQUEST','Unknown booking action.');
            }
        }catch(\Throwable $e){$message=$e instanceof Fault?$e->getMessage():'Your request needs review. Check the original booking before trying again.';}
        $assets=plugin_dir_url(FILE).'assets/';$options=Booking::options();
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Book knife sharpening | KnifeRevive</title><link rel="stylesheet" href="'.esc_url($assets.'storefront.css').'"></head><body><main data-booking-attach="'.esc_url(rest_url(Api::NS.'/bookings/'.$id.'/attach')).'" data-attach="'.esc_url(rest_url(Api::NS.'/sessions/attach')).'"><p>KnifeRevive</p><h1>Book knife sharpening</h1><div id="session-status" role="status"></div><p>'.esc_html($message).'</p><p>'.esc_html($options['location']).'</p><p>Friday–Saturday 9am–7pm · Sunday 10am–4pm · Pacific time</p><p>Request a service day below. KnifeRevive confirms availability and pickup arrangements. Return collection or delivery timing is arranged separately.</p>';
        if($row)self::privateView($row,$owner);
        elseif(!$id && Booking::enabled())self::newForm($options);
        if($options['phone'])echo '<p>Questions? <a href="'.esc_attr('tel:'.preg_replace('/[^+0-9]/','',$options['phone'])).'">'.esc_html($options['phone']).'</a></p>';
        echo '<p><a href="'.esc_url(home_url('/#knife-sharpening')).'">KnifeRevive sharpening</a></p></main><script src="'.esc_url($assets.'storefront.js').'" defer></script><script src="'.esc_url($assets.'booking.js').'" defer></script></body></html>';exit;
    }
    private static function newForm(array $options): void {
        $postal=(string)wp_unslash($_GET['postal_code']??'');$coverage=null;
        echo '<form method="get"><input type="hidden" name="krev_agent" value="booking"><label>Check your ZIP code<input name="postal_code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" required value="'.esc_attr($postal).'"></label><button>Check service coverage</button></form>';
        if($postal){try{$coverage=BookingCoverage::check($postal);echo '<p role="status">'.esc_html($coverage['message']).'</p>';if($coverage['coverage_state']==='outside_bay_area')return;}catch(Fault $e){echo '<p>'.esc_html($e->getMessage()).'</p>';}}
        echo '<form method="post"><input type="hidden" name="csrf" value="'.esc_attr(wp_create_nonce('krev_booking_new')).'"><input type="hidden" name="request_key" value="'.esc_attr(Domain::id()).'">';
        echo '<fieldset><legend>Your knives</legend>';
        foreach($options['services'] as $p){echo '<label>'.esc_html($p['title'].' — $'.Domain::decimal($p['unit_price_minor'])).'<input type="number" name="quantities['.esc_attr((string)$p['product_id']).']" min="0" max="30" value="0"></label><p>'.esc_html($p['definition']).'</p>';}
        echo '</fieldset><label>Booking option<select name="mode" required><option value="pay_later_dropoff">Drop off — pay at service, no online payment</option><option value="prepaid_dropoff"'.(($coverage && !$coverage['prepayment_eligible'])?' disabled':'').'>Prepay — customer drops off</option><option value="prepaid_pickup"'.(($coverage && !$coverage['prepayment_eligible'])?' disabled':'').'>Prepay — KnifeRevive picks up (+$'.esc_html(Domain::decimal($options['merchant_trip_fee_minor'])).')</option></select></label><label>Return option<select name="return_mode"><option value="customer_collection">Customer collects — no merchant trip fee</option><option value="courier_delivery"'.(($coverage && !$coverage['pickup_eligible'])?' disabled':'').'>KnifeRevive returns knives (+$'.esc_html(Domain::decimal($options['merchant_trip_fee_minor'])).'; prepaid requests)</option></select></label><label>Requested service day<select name="preferred_date" required><option value="">Choose an open day</option>';
        foreach(Booking::availability()['days'] as $day)if($day['available_jobs']!==0)echo '<option value="'.esc_attr($day['date']).'">'.esc_html((new \DateTimeImmutable($day['start_at']))->format('l, M j').' · '.(new \DateTimeImmutable($day['start_at']))->format('g:i A').'–'.(new \DateTimeImmutable($day['end_at']))->format('g:i A')).'</option>';
        echo '</select></label><label>Notes (optional)<input name="notes" maxlength="500"></label>';self::contact(['postal_code'=>$postal]);self::submitButton();echo '</form>';
        self::paymentNotice();
    }
    private static function contact(array $input): void {
        echo '<label>Your service ZIP code<input name="postcode" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" required value="'.esc_attr($input['postal_code']??'').'" data-coverage="'.esc_url(rest_url(Api::NS.'/booking-coverage')).'"></label><p id="coverage-status" role="status">Pickup and prepayment: Contra Costa and Santa Clara counties. Other SF Bay Area counties: customer drop-off with payment at service.</p>';
        foreach(['name'=>'Your name','email'=>'Contact email','phone'=>'Phone (optional)'] as $field=>$label)echo '<label>'.esc_html($label).'<input name="'.esc_attr($field).'" type="'.($field==='email'?'email':'text').'" maxlength="'.($field==='email'?254:100).'" '.($field==='phone'?'':'required').' value="'.esc_attr($input['customer'][$field]??'').'"></label>';
        echo '<fieldset><legend>Address for merchant trips or county review</legend><p>Required for pickup, return delivery, or a ZIP code crossing county boundaries. Otherwise, leave blank for customer drop-off and collection.</p>';
        foreach(['address_1'=>'Street address','address_2'=>'Apartment (optional)','city'=>'City'] as $field=>$label)echo '<label>'.esc_html($label).'<input name="'.esc_attr($field).'" maxlength="150" value="'.esc_attr($input['pickup_address'][$field]??'').'"></label>';
        echo '</fieldset>';
    }
    private static function submitButton(): void {echo '<label><input type="checkbox" name="accept" value="yes" required> Send this booking request to KnifeRevive. No online payment now; the appointment awaits merchant confirmation.</label><button name="action" value="submit">Request booking — no payment now</button>';}
    private static function paymentNotice(): void {echo '<p>Choosing prepayment records your preference. It does not charge you. Prepayment opens only after booking confirmation and checkout verification. Final fees and taxes must be reviewed before you authorize payment.</p>';}
    private static function privateView(array $row,string $owner): void {
        $s=Booking::response($row);$i=$row['data']['input'];
        echo '<p>Reference: '.esc_html($row['id']).'</p><p><strong>Booking: '.esc_html($s['booking_state']).'</strong></p><p>Payment: '.esc_html($s['payment_state']).'</p><p>Requested service day: '.esc_html($i['preferred_date']).'</p><p>'.esc_html(match($i['mode']){'pay_later_dropoff'=>'Customer drop-off; pay at service.','prepaid_dropoff'=>'Customer drop-off; prepayment requested.','prepaid_pickup'=>'KnifeRevive pickup; prepayment requested.'}).'</p><ul>';
        foreach($s['items'] as $p)echo '<li>'.esc_html($p['title'].' × '.$p['quantity'].' — $'.Domain::decimal($p['unit_price_minor']*$p['quantity'])).'</li>';
        echo '</ul><p>Merchant trips: $'.esc_html(Domain::decimal($s['merchant_trip_fee_minor'])).'</p><p>Estimated subtotal before taxes and other disclosed fees: $'.esc_html(Domain::decimal($s['estimated_subtotal_minor'])).' USD</p>';
        if($s['policy_url'])echo '<p><a href="'.esc_url($s['policy_url']).'">Service and cancellation/refund terms</a></p>';
        if($s['booking_state']==='draft'){echo '<form method="post" action="'.esc_url(add_query_arg(['krev_agent'=>'booking','booking'=>$row['id']],home_url('/'))).'">';self::hidden($owner,$row['id']);self::contact($i);self::submitButton();echo '</form>';}
        else {
            echo '<p>'.esc_html($s['appointment_confirmed']?'KnifeRevive confirmed this service day.':'This request does not reserve a service day; wait for KnifeRevive confirmation.').'</p>';
            if($s['booking_state']==='confirmed' && $i['mode']!=='pay_later_dropoff' && $s['prepayment_enabled']){echo '<form method="post">';self::hidden($owner,$row['id']);echo '<label><input type="checkbox" name="accept" value="yes" required> Prepare my secure checkout. I will review the final total and authorize payment there.</label><button name="action" value="checkout">Prepare prepaid checkout</button></form>';}
            elseif($i['mode']!=='pay_later_dropoff')self::paymentNotice();
            if(in_array($s['booking_state'],['requested','confirmed'],true)){echo '<form method="post">';self::hidden($owner,$row['id']);echo '<button name="action" value="cancel">Cancel booking (refund requires merchant review)</button></form>';}
        }
    }
    private static function hidden(string $owner,string $id): void {echo '<input type="hidden" name="csrf" value="'.esc_attr(ListingFrontend::csrf($owner,$id,intdiv(time(),600))).'">';}
}
