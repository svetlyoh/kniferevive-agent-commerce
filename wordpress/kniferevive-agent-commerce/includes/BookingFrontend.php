<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

final class BookingFrontend {
    private static array $errors=[];
    private static array $values=[];
    public static function render(): never {
        PrivateBrand::headers();$message='';$failed=false;$row=null;$owner='';$id=(string)wp_unslash($_GET['booking']??'');
        try{
            if(!Booking::enabled() && !$id)Domain::fail('BOOKING_DISABLED','Booking requests are temporarily unavailable. Contact support.',503);
            if($id){$owner=Api::bookingOwner($id);$row=Booking::get($id,$owner);}
            elseif(isset($_GET['referral'])){$row=Booking::referral((string)wp_unslash($_GET['referral']));$id=$row['id'];$owner=$row['owner'];}
            if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
                $post=wp_unslash($_POST);self::$values=$post;if(isset($post['mode'])){$post['return_mode']=$post['mode']==='prepaid_pickup_delivery'?'courier_delivery':'customer_collection';if($post['mode']==='prepaid_pickup_delivery')$post['mode']='prepaid_pickup';}
                $action=$post['action']??'submit';
                if($id && in_array($action,['quote','continue','pay'],true)){
                    $intent=Store::get($row['data']['listing_intent']??'','listing');
                    ListingFrontend::authorizeForm($intent['owner'],$intent['id'],$post);
                    if($action==='pay'){
                        ListingFrontend::bookingPayment($intent['id'],$intent['owner'],$post);
                        wp_safe_redirect(add_query_arg(['krev_agent'=>'booking','booking'=>$id,'payment'=>'1'],home_url('/')),303);exit;
                    }
                    if($action==='continue'){
                        if(($post['accept']??'')!=='yes')Domain::fail('AUTHORIZATION_REQUIRED','Review the final quote and policies.',403);
                        ListingCheckout::handoff($intent['id'],$intent['owner'],(string)($post['quote_hash']??''));
                        wp_safe_redirect(add_query_arg(['krev_agent'=>'booking','booking'=>$id,'payment'=>'1'],home_url('/')),303);exit;
                    }
                    $context=['email'=>$post['email']??'','payment_method'=>$post['payment_method']??'','shipping_methods'=>array_values((array)($post['shipping_methods']??[]))];
                    foreach(['billing','shipping'] as $kind)foreach(['address_1','address_2','city','state','postcode','country'] as $field)$context[$kind][$field]=(string)($post[$kind.'_'.$field]??'');
                    ListingCheckout::quote($intent['id'],$context,$intent['owner'],'booking-quote-'.Domain::id());
                    $message='Review your final total below. No payment has been taken.';
                }
                elseif($id)ListingFrontend::authorizeForm($owner,$id,$post);
                elseif(!Api::firstPartyForm() || !wp_verify_nonce($post['csrf']??'','krev_booking_new'))Domain::fail('FORBIDDEN','The form expired. Reload before submitting.',403);
                $action=$post['action']??'submit';
                if($action==='checkout'){
                    if(($post['accept']??'')!=='yes')Domain::fail('AUTHORIZATION_REQUIRED','Choose to prepare your prepaid checkout.',403);
                    $intent=Booking::checkout($id,$owner);$row=Booking::get($id,$owner);$message='Review the final total below before paying.';
                }
                if(in_array($action,['checkout','quote','continue','pay'],true)){}
                elseif($action==='coverage'){if(($post['mode']??'pay_later_dropoff')!=='pay_later_dropoff')BookingCoverage::check((string)($post['postcode']??''));}
                elseif($action==='cancel'){$row=Booking::cancel($id,$owner);$message='Booking cancelled. An unpaid pay-at-service order is cancelled too. Any online payment needs separate merchant refund review.';}
                elseif($action==='revoke'){Booking::revokeConsent($id,$owner);$row=Booking::get($id,$owner);$message='Sharing consent revoked. Existing service/order records are retained for reconciliation. Contact support to cancel the service.';}
                elseif($action==='share'){Booking::shareConsent($id,$owner,($post['contact_share']??'')==='yes');$row=Booking::get($id,$owner);$message='Sharing consent renewed for this booking only.';}
                elseif($action==='submit'){
                    self::validate($post);if(self::$errors)Domain::fail('INVALID_REQUEST','Check the highlighted fields. No booking was submitted.');
                    if(!$row){
                        $key=Domain::key($post['request_key']??'');$request=new \WP_REST_Request('POST');$request->set_header('Content-Type','application/json');$request->set_header('Idempotency-Key',$key);$request->set_body('{}');
                        $session=Api::session($request);$owner=$session['session_id'];setcookie('krev_agent_session',$session['session_token'],['expires'=>strtotime($session['expires_at']),'path'=>'/','secure'=>is_ssl(),'httponly'=>true,'samesite'=>'Strict']);
                        $items=[];foreach((array)($post['quantities']??[]) as $product=>$quantity){if(!ctype_digit((string)$product) || !ctype_digit((string)$quantity))Domain::fail('INVALID_REQUEST','Choose whole knife quantities.');if((int)$quantity>0)$items[]=['product_id'=>(int)$product,'quantity'=>(int)$quantity];}
                        $row=Booking::create(['items'=>$items,'mode'=>$post['mode']??'','preferred_date'=>$post['preferred_date']??'','postal_code'=>$post['postcode']??'','return_mode'=>$post['return_mode']??'customer_collection','notes'=>$post['notes']??''],$owner,$key);$id=$row['id'];
                    }
                    $contact=['postal_code'=>$post['postcode']??'','notes'=>$post['notes']??'','customer'=>['name'=>$post['name']??'','email'=>$post['email']??'','phone'=>$post['phone']??'']];
                    if($row['data']['input']['mode']==='prepaid_pickup' || $row['data']['input']['return_mode']==='courier_delivery' || !empty($post['address_1']))$contact['pickup_address']=['address_1'=>$post['address_1']??'','address_2'=>$post['address_2']??'','city'=>$post['city']??'','state'=>'CA','country'=>'US','postcode'=>$post['postcode']??''];
                    $row=Booking::submit($id,$owner,$contact,true);Booking::accessCookie($row);if($row['data']['input']['mode']!=='pay_later_dropoff'){Booking::checkout($id,$owner);$row=Booking::get($id,$owner);}wp_safe_redirect(add_query_arg(['krev_agent'=>'booking','booking'=>$id],home_url('/')),303);exit;
                }else Domain::fail('INVALID_REQUEST','Unknown booking action.');
            }
        }catch(\Throwable $e){$failed=true;$message=$e instanceof Fault?$e->getMessage():'Your original request needs review. Check its status before trying again.';}
        $options=Booking::options();
        PrivateBrand::start('Book knife sharpening','data-booking-attach="'.esc_url(rest_url(Api::NS.'/bookings/'.$id.'/attach')).'" data-attach="'.esc_url(rest_url(Api::NS.'/sessions/attach')).'"');
        echo '<p class="krev-eyebrow">SF Bay Area · sharpening services</p><h1>Book knife sharpening</h1><p>'.esc_html($options['location']).'</p><p>';
        $hours=[];foreach($options['weekly_hours'] as $window)$hours[]=(['','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'][$window['weekday']]).' '.(new \DateTimeImmutable($window['open']))->format('g:i A').'–'.(new \DateTimeImmutable($window['close']))->format('g:i A');
        echo esc_html(implode(' · ',$hours).' · Pacific time').'</p><p>Choose an intake day. KnifeRevive confirms availability; return timing is arranged separately.</p>';
        if($message){echo '<div id="'.($failed?'form-errors':'form-message').'"'.($failed?' class="krev-alert" role="alert" tabindex="-1" autofocus':' role="status"').'><p>'.esc_html($message).'</p>';foreach(self::$errors as $field=>$error)echo '<p><a href="#field-'.esc_attr($field).'">'.esc_html($error).'</a></p>';echo '</div>';}
        if($row)self::privateView($row,$owner,$options);elseif(!$id && Booking::enabled())self::newForm($options);
        if($options['phone'])echo '<p><a href="'.esc_attr('tel:'.preg_replace('/[^+0-9]/','',$options['phone'])).'">Call '.esc_html($options['phone']).'</a></p>';
        PrivateBrand::end(true);exit;
    }
    private static function validate(array $post): void {
        if(!trim((string)($post['name']??'')))self::$errors['name']='Enter your name.';
        if(!is_email((string)($post['email']??'')))self::$errors['email']='Enter a valid contact email.';
        if(($post['mode']??'pay_later_dropoff')!=='pay_later_dropoff' && !preg_match('/^[0-9]{5}$/D',(string)($post['postcode']??'')))self::$errors['postcode']='Enter a five-digit service ZIP code.';
        if(($post['contact_share']??'')!=='yes')self::$errors['contact_share']='Approve contact/address sharing for this booking.';
        if(($post['accept']??'')!=='yes')self::$errors['accept']='Approve your booking details before continuing.';
        if(isset($post['quantities']) && array_sum(array_map('intval',(array)$post['quantities']))<1)self::$errors['quantities']='Choose at least one knife.';
    }
    private static function field(string $name,string $label,string $type='text',bool $required=false,string $value=''): void {
        $value=(string)(self::$values[$name]??$value);$error=self::$errors[$name]??null;
        echo '<label for="field-'.esc_attr($name).'">'.esc_html($label).'</label><input id="field-'.esc_attr($name).'" name="'.esc_attr($name).'" type="'.esc_attr($type).'" maxlength="'.($name==='email'?254:150).'" value="'.esc_attr($value).'"'.($required?' required':'').($error?' aria-invalid="true" aria-describedby="error-'.esc_attr($name).'"':'').($name==='postcode'?' inputmode="numeric" pattern="[0-9]{5}" data-coverage="'.esc_url(rest_url(Api::NS.'/booking-coverage')).'"':'').'>';
        if($error)echo '<p class="krev-field-error" id="error-'.esc_attr($name).'">'.esc_html($error).'</p>';
    }
    private static function newForm(array $options): void {
        $postal=(string)(self::$values['postcode']??wp_unslash($_GET['postal_code']??''));$coverage=null;
        if($postal && (self::$values['mode']??'pay_later_dropoff')!=='pay_later_dropoff'){try{$coverage=BookingCoverage::check($postal);}catch(Fault $e){}}
        echo '<ol class="krev-steps" aria-label="Booking steps"><li>Choose your handoff</li><li>Knives &amp; coverage</li><li>Contact/address</li><li>Review &amp; payment</li></ol>';
        echo '<form method="post" data-booking-form><input type="hidden" name="csrf" value="'.esc_attr(wp_create_nonce('krev_booking_new')).'"><input type="hidden" name="request_key" value="'.esc_attr(self::$values['request_key']??Domain::id()).'">';
        echo '<fieldset><legend>1. Choose your knife journey</legend><label for="mode">Booking option</label><select id="mode" name="mode" required>';
        foreach(['pay_later_dropoff'=>'1. Drop off · pay when you collect','prepaid_dropoff'=>'2. Drop off · prepay online','prepaid_pickup'=>'3. We pick up · you collect — $'.Domain::decimal($options['merchant_trip_fee_minor']).' pickup fee','prepaid_pickup_delivery'=>'4. We pick up + deliver · comeback combo — $'.Domain::decimal($options['merchant_round_trip_fee_minor']).' total transport'] as $value=>$label)echo '<option value="'.esc_attr($value).'"'.((self::$values['mode']??'pay_later_dropoff')===$value?' selected':'').'>'.esc_html($label).'</option>';
        echo '</select><p>Options 2–4 need eligible ZIP coverage and completed online payment before the booking reaches KnifeRevive. Transport fees are separate from sharpening.</p></fieldset>';
        self::coverage($postal,$coverage);
        echo '<fieldset id="field-quantities"><legend>2. Your knives</legend>';
        foreach($options['services'] as $p){$value=(string)(self::$values['quantities'][$p['product_id']]??'0');echo '<div class="krev-knife"><label for="knife-'.esc_attr((string)$p['product_id']).'">'.esc_html($p['title'].' — $'.Domain::decimal($p['unit_price_minor'])).'</label><input id="knife-'.esc_attr((string)$p['product_id']).'" type="number" name="quantities['.esc_attr((string)$p['product_id']).']" min="0" max="30" value="'.esc_attr($value).'" data-unit-minor="'.esc_attr((string)$p['unit_price_minor']).'"><p>'.esc_html($p['definition']).'</p></div>';}
        if(isset(self::$errors['quantities']))echo '<p class="krev-field-error">'.esc_html(self::$errors['quantities']).'</p>';echo '</fieldset>';
        echo '<fieldset><legend>Requested service day</legend><label for="preferred-date">Requested intake day</label><select id="preferred-date" name="preferred_date" required><option value="">Choose an open day</option>';
        foreach(Booking::availability()['days'] as $day)if($day['available_jobs']!==0)echo '<option value="'.esc_attr($day['date']).'"'.((self::$values['preferred_date']??'')===$day['date']?' selected':'').'>'.esc_html((new \DateTimeImmutable($day['start_at']))->format('l, M j').' · '.(new \DateTimeImmutable($day['start_at']))->format('g:i A').'–'.(new \DateTimeImmutable($day['end_at']))->format('g:i A')).'</option>';
        echo '</select><p>A requested day is not a confirmed appointment. Intake and return are separate.</p></fieldset>';
        self::contact(['postal_code'=>$postal],$coverage);self::review($options);self::consent();echo '</form>';
    }
    private static function coverage(string $postal,?array $coverage): void {
        echo '<fieldset class="krev-coverage" data-coverage-fields'.((self::$values['mode']??'pay_later_dropoff')==='pay_later_dropoff'?' hidden':'').'><legend>Check your prepaid service ZIP</legend>';self::field('postcode','Service ZIP code','text',false,$postal);
        $tone=!$coverage?'pending':($coverage['address_review_required']?'pending':($coverage['service_available']?'available':'unavailable'));
        echo '<div id="coverage-status" class="krev-coverage-result" role="status" aria-live="polite" tabindex="-1" data-tone="'.esc_attr($tone).'" data-verified-postal="'.esc_attr($coverage?$postal:'').'"'.((self::$values['action']??'')==='coverage'?' data-coverage-confirmed="true"':'').'>'.esc_html($coverage['message']??'Let’s check your zone. Enter your ZIP to see pickup, drop-off and payment options.').'</div><button name="action" value="coverage" formnovalidate>Check service coverage</button></fieldset>';
    }
    private static function contact(array $input,?array $coverage=null): void {
        echo '<fieldset><legend>3. Contact &amp; address</legend>';
        if(!empty($input['mode']) && $input['mode']!=='pay_later_dropoff'){echo '<input type="hidden" name="mode" value="'.esc_attr($input['mode']).'"><input type="hidden" name="return_mode" value="'.esc_attr($input['return_mode']).'">';self::$values['mode']=$input['mode'];self::coverage($input['postal_code']??'',$coverage);}
        foreach(['name'=>'Your name','email'=>'Contact email','phone'=>'Phone (optional)'] as $field=>$label)self::field($field,$label,$field==='email'?'email':($field==='phone'?'tel':'text'),$field!=='phone',$input['customer'][$field]??'');
        echo '<details data-address-fields data-address-review="'.(!empty($coverage['address_review_required'])?'true':'false').'"'.((($input['mode']??self::$values['mode']??'')==='prepaid_pickup' || (self::$values['mode']??'')==='prepaid_pickup_delivery' || ($input['return_mode']??self::$values['return_mode']??'')==='courier_delivery' || !empty($coverage['address_review_required']))?' open':'').'><summary>Pickup/delivery address or county review</summary><p>Complete this only for merchant trips or a ZIP requiring county review. For ordinary customer drop-off and collection, leave it blank.</p>';
        foreach(['address_1'=>'Street address','address_2'=>'Apartment (optional)','city'=>'City'] as $field=>$label)self::field($field,$label,'text',false,$input['pickup_address'][$field]??'');
        echo '</details>';self::field('notes','Notes (optional)','text',false,$input['notes']??'');echo '</fieldset>';
    }
    private static function review(array $options,?array $selection=null): void {
        $quantities=self::$values['quantities']??array_column($selection['items']??[],'quantity','product_id');$subtotal=0;
        foreach($options['services'] as $service)$subtotal+=$service['unit_price_minor']*max(0,min(30,(int)($quantities[$service['product_id']]??0)));
        $trips=((self::$values['mode']??$selection['mode']??'')==='prepaid_pickup'?1:0)+((self::$values['return_mode']??$selection['return_mode']??'')==='courier_delivery'?1:0);$trips=(self::$values['mode']??'')==='prepaid_pickup_delivery'?2:$trips;$fee=$trips===2?$options['merchant_round_trip_fee_minor']:$trips*$options['merchant_trip_fee_minor'];
        echo '<fieldset class="krev-review" data-trip-minor="'.esc_attr((string)$options['merchant_trip_fee_minor']).'" data-round-trip-minor="'.esc_attr((string)$options['merchant_round_trip_fee_minor']).'"><legend>4. Review &amp; request</legend><p id="booking-estimate" role="status">'.esc_html('Services: $'.Domain::decimal($subtotal).' · Merchant trips: $'.Domain::decimal($fee).' · Estimated subtotal: $'.Domain::decimal($subtotal+$fee).' USD, before taxes and other disclosed fees. No payment now.').'</p><p>Pickup: $'.esc_html(Domain::decimal($options['merchant_trip_fee_minor'])).'. Pickup plus delivery (comeback combo): $'.esc_html(Domain::decimal($options['merchant_round_trip_fee_minor'])).' total. Drop off and collect yourself: no transport fee.</p><p>This is an estimate before actual WooCommerce taxes and other disclosed fees. No online payment is taken by this form.</p>';
        echo '<p class="krev-payment-notice">'.esc_html(Booking::prepaymentEnabled()?((Settings::get()['booking_require_payment_submission'] || Settings::get()['booking_pay_before_confirmation'])?'Choose a prepaid option to continue to payment on this booking screen. The merchant confirms the day and trip address afterward.':'Prepayment follows merchant confirmation and review of the final native checkout total.'):'Online payment is currently unavailable. Choose drop-off with payment at collection; prepaid bookings cannot be submitted without payment.').'</p>';
        if(BookingOrderBridge::enabled())echo '<p>'.esc_html(Settings::get()['booking_order_timing']==='on_submit'?'For customer drop-off with payment at collection, an unpaid seller order is prepared when you request the booking. The service day still needs confirmation.':'For customer drop-off with payment at collection, an unpaid seller order is prepared after merchant confirmation.').'</p>';
        else echo '<p>This request does not create a WooCommerce order. The seller reviews it in the sharpening request inbox.</p>';
        if($options['policy_url'])echo '<p><a href="'.esc_url($options['policy_url']).'">Read service cancellation and refund terms</a></p>';else echo '<p>Contact support to clarify service/cancellation terms before requesting. A cancellation does not automatically issue a refund.</p>';echo '</fieldset>';
    }
    private static function consent(): void {
        $upfront=Booking::prepaymentEnabled();
        $paid=(self::$values['mode']??'pay_later_dropoff')!=='pay_later_dropoff';
        echo '<label class="krev-check" id="field-contact_share"><input type="checkbox" name="contact_share" value="yes" required> I approve sharing the contact details and any trip address entered here with KnifeRevive and this service seller to fulfill this booking only.</label><label class="krev-check" id="field-accept"><input type="checkbox" name="accept" value="yes" required> '.esc_html($upfront?'Approve these details. For options 2–4, I must complete payment to send this booking to KnifeRevive; the merchant confirms the day afterward.':'Send my booking request. No online payment now; the merchant must confirm the service day.').'</label><button name="action" value="submit" data-submit-booking data-pay-upfront="'.($upfront?'true':'false').'">'.esc_html($upfront && $paid?'Continue to Payment':'Book my drop-off').'</button>';
    }
    private static function privateView(array $row,string $owner,array $options): void {
        $s=Booking::response($row);$i=$row['data']['input'];echo '<p>Booking reference: '.esc_html($row['id']).'</p><div class="krev-state"><p><strong>Booking: '.esc_html($s['booking_state']).'</strong></p><p>Payment: '.esc_html($s['payment_state']).'</p><p>WooCommerce order: '.esc_html($s['order_reference']??'not created yet').($s['order_state']?' · '.esc_html($s['order_state']):'').'</p></div><p>Requested day: '.esc_html($i['preferred_date']).' · Pacific time</p><p><strong>Your knife journey:</strong> '.esc_html(BookingLifecycle::handoffLabel($i)).'</p><ul>';
        foreach($s['items'] as $p)echo '<li>'.esc_html($p['title'].' × '.$p['quantity'].' — $'.Domain::decimal($p['unit_price_minor']*$p['quantity'])).'</li>';
        echo '</ul><p>Merchant trips: $'.esc_html(Domain::decimal($s['merchant_trip_fee_minor'])).'</p><p>Estimated subtotal before taxes and fees: $'.esc_html(Domain::decimal($s['estimated_subtotal_minor'])).' USD</p>';
        $order=BookingLifecycle::order($row);if($order){echo '<p>Native order total: '.wp_kses_post($order->get_formatted_order_total()).'</p><p>Refund: '.esc_html($s['refund_state']).' · Recorded $'.esc_html(Domain::decimal($s['refund_summary']['refund_recorded_minor'])).' · Gateway accepted $'.esc_html(Domain::decimal($s['refund_summary']['refund_gateway_accepted_minor'])).'. Arrival in your account is not verified.</p>';}
        if($s['booking_state']==='draft'){
            echo '<p><a href="'.esc_url(add_query_arg('krev_agent','booking',home_url('/'))).'">Edit knives, requested day or handoff in a new draft</a>. This draft creates no order.</p><form method="post" action="'.esc_url(strtok($s['review_url'],'#')).'" data-booking-form>';self::hidden($owner,$row['id']);self::contact($i,$s['coverage']);self::review($options,$i);self::consent();echo '</form>';
        }else{
            echo '<p>'.esc_html($s['booking_state']==='cancelled'?'This booking was cancelled. Contact support about any linked order or payment.':($s['appointment_confirmed']?'KnifeRevive confirmed the service day. Return timing is arranged separately.':($i['mode']!=='pay_later_dropoff' && !Booking::merchantVisible($row)?'One more step: complete payment to send this booking to KnifeRevive. Your details are saved; no appointment has been submitted yet.':'KnifeRevive received this request; wait for merchant confirmation before travelling.'))).'</p>';
            if(($s['booking_state']==='confirmed' || ($s['booking_state']==='awaiting_payment' || ($s['booking_state']==='requested' && Settings::get()['booking_pay_before_confirmation']))) && $i['mode']!=='pay_later_dropoff' && $s['prepayment_enabled'] && empty($row['data']['listing_intent'])){echo '<form method="post">';self::hidden($owner,$row['id']);echo '<label><input type="checkbox" name="accept" value="yes" required> Prepare secure checkout; I will review the final total and approve payment there.</label><button name="action" value="checkout">Review final native checkout total</button></form>';}
            elseif($i['mode']!=='pay_later_dropoff' && empty($row['data']['listing_intent']) && !in_array($s['payment_state'],['paid','refund_recorded','paid_cancelled_review_required'],true))echo '<p>'.esc_html($s['prepayment_enabled']?'Payment is available after the merchant confirms this day and any trip address.':'Prepayment is currently unavailable.').'</p>';
            if(in_array($s['booking_state'],['awaiting_payment','requested','confirmed'],true)){
                echo '<details class="krev-manage-request"><summary>Need to change plans?</summary><div class="krev-request-actions"><form method="post"><p>Cancel this booking request. A payment already made needs a separate merchant refund review.</p>';self::hidden($owner,$row['id']);echo '<button class="krev-secondary-action" name="action" value="cancel">Cancel my booking request</button></form>';
                echo '<form method="post">';self::hidden($owner,$row['id']);if(in_array($s['address_authorization'],['unknown','needs_user','revoked','expired'],true))echo '<label class="krev-check"><input type="checkbox" name="contact_share" value="yes" required> Approve sharing the existing contact/address details with KnifeRevive and this service seller for this booking only.</label><button class="krev-secondary-action" name="action" value="share">Allow booking details to be shared</button>';else echo '<p>Stop future contact/address sharing for this request. Existing order records stay; this does not cancel the booking.</p><button class="krev-secondary-action" name="action" value="revoke">Stop sharing my contact details</button>';echo '</form></div></details>';
            }
            if(!empty($row['data']['listing_intent']) && in_array($s['booking_state'],['awaiting_payment','requested','confirmed'],true) && $s['payment_state']!=='paid'){
                $intent=Store::get($row['data']['listing_intent'],'listing');ListingFrontend::bookingReview($intent,$intent['owner'],self::$values);
            }
        }
    }
    private static function hidden(string $owner,string $id): void {echo '<input type="hidden" name="csrf" value="'.esc_attr(ListingFrontend::csrf($owner,$id,intdiv(time(),600))).'">';}
}
