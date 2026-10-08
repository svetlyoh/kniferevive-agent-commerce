<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;

/** Requests never charge. An independently approved bridge may create one unpaid drop-off order. */
final class Booking {
    public const MODES = ['pay_later_dropoff','prepaid_dropoff','prepaid_pickup'];
    public static function accessToken(array $row): string {
        $body=$row['id'].'.'.$row['data']['access_expires'];return $body.'.'.hash_hmac('sha256','booking-access:'.$body,wp_salt('auth'));
    }
    public static function accessOwner(string $id,string $token): string {
        if(!Domain::validId($id))Domain::fail('NOT_FOUND','Booking unavailable.',404);
        $row=Store::get($id,'booking');
        if(empty($row['data']['access_expires']) || $row['data']['access_expires']<time() || !hash_equals(self::accessToken($row),$token))Domain::fail('AUTHORIZATION_REQUIRED','A valid private booking link is required.',401);
        Api::rate('booking-access:'.$id,60);return $row['owner'];
    }
    public static function accessCookie(array $row): void {
        setcookie('krev_booking_access',self::accessToken($row),['expires'=>$row['data']['access_expires'],'path'=>'/','secure'=>is_ssl(),'httponly'=>true,'samesite'=>'Strict']);
    }
    public static function enabled(): bool {
        $s=Settings::get();
        return $s['booking_enabled'] && $s['booking_location'] && $s['booking_services'] && $s['booking_weekly_hours'];
    }
    public static function options(): array {
        $s=Settings::get();$items=[];
        foreach($s['booking_services'] as $service){
            try{$p=ListingCheckout::product($service['product_id']);}catch(Fault $e){continue;}
            if($p['fulfillment_type']==='service')$items[]=['product_id'=>$p['product_id'],'title'=>$p['title'],'definition'=>$service['definition'],'unit_price_minor'=>$p['unit_price_minor'],'currency'=>$p['currency'],'canonical_url'=>$p['canonical_url']];
        }
        return ['enabled'=>self::enabled(),'booking_url'=>add_query_arg('krev_agent','booking',home_url('/')),
            'timezone'=>'America/Los_Angeles','location'=>$s['booking_location'],'phone'=>$s['booking_phone'],
            'modes'=>self::MODES,'weekly_hours'=>$s['booking_weekly_hours'],'services'=>$items,
            'merchant_trip_fee_minor'=>$s['booking_trip_fee_minor'],'daily_capacity'=>$s['booking_daily_capacity'],
            'confirmation'=>'merchant_confirmation_required','prepayment_enabled'=>self::prepaymentEnabled(),
            'pickup_postal_codes'=>BookingCoverage::pickupPostcodes(),'pickup_address_review_required'=>true,
            'coverage_url'=>rest_url(Api::NS.'/booking-coverage'),'pickup_counties'=>['Contra Costa','Santa Clara'],
            'prepayment_counties'=>['Contra Costa','Santa Clara'],'direct_wallet_enabled'=>false,
            'authorized_wallet_payment_enabled'=>self::walletEnabled(),
            'wallet_payment_state'=>'native_invoice_handoff_only','wallet_authorization'=>'explicit_host_wallet_spending_authorization',
            'policy_url'=>$s['booking_policy_url']?:null,'policy_version'=>$s['booking_policy_version']?:null];
    }
    public static function prepaymentEnabled(): bool {
        $s=Settings::get();
        return self::enabled() && $s['booking_prepaid_enabled'] && $s['booking_policy_url'] && $s['booking_policy_version'] && ListingCheckout::enabled();
    }
    public static function walletEnabled(): bool {
        $s=Settings::get();
        return self::prepaymentEnabled() && $s['booking_wallet_enabled'] && $s['booking_wallet_verified']
            && in_array('krev_lightning',$s['listing_gateway_ids'],true)
            && class_exists('KnifeRevive\\Lightning\\Repository') && class_exists('KnifeRevive\\Lightning\\Client')
            && \KnifeRevive\Lightning\Settings::get('accept','no')==='yes';
    }
    /** Read an existing native invoice only; GET never creates an order or invoice. */
    public static function walletInvoice(string $id,string $owner): array {
        $booking=self::get($id,$owner);$d=$booking['data'];
        if(!self::walletEnabled())Domain::fail('WALLET_PAYMENT_DISABLED','Authorized wallet payments require verified native Lightning checkout.',503);
        self::paymentSelection($id);
        if(!$d['listing_intent'])Domain::fail('NATIVE_INVOICE_REQUIRED','Prepare the original native checkout and select Bitcoin Lightning before requesting its invoice.',409);
        $checkoutOwner=$d['checkout_owner']??$booking['owner'];$intent=ListingCheckout::get($d['listing_intent'],$checkoutOwner,true);$order=wc_get_order($intent['data']['order_id']??0);
        if(!$order || $order->get_meta('_krev_listing_intent')!==$intent['id'] || $order->get_meta('_krev_service_booking')!==$id || $order->get_payment_method()!=='krev_lightning' || ($intent['data']['context']['payment_method']??'')!=='krev_lightning')Domain::fail('NATIVE_INVOICE_REQUIRED','A bound native Bitcoin Lightning order is required.',409);
        if(empty($intent['data']['quote']) || Domain::cents(wc_format_decimal($order->get_total(),2))!==$intent['data']['quote']['total_minor'])Domain::fail('PAYMENT_UNRESOLVED','The native order total differs from the reviewed quote.',409);
        $row=\KnifeRevive\Lightning\Repository::for_order($order->get_id());
        if(!$row)Domain::fail('NATIVE_INVOICE_REQUIRED','The original native invoice is not ready.',409);
        $status=ListingCheckout::status($intent['id'],$checkoutOwner);
        if($status['payment_state']==='paid')return ['booking_id'=>$id,'state'=>'settled','payable'=>false,'bolt11'=>null];
        if(!$order->has_status('pending') || $order->get_total_refunded()>0 || !\KnifeRevive\Lightning\Settings::eligible_order($order) || !hash_equals($row->fingerprint,\KnifeRevive\Lightning\Settings::fingerprint($order)))Domain::fail('PAYMENT_UNRESOLVED','The original order needs merchant reconciliation.',409);
        try{$payload=\KnifeRevive\Lightning\Client::validate($row,json_decode($row->payload,true,32,JSON_THROW_ON_ERROR));}
        catch(\Throwable $e){Domain::fail('PAYMENT_UNRESOLVED','The saved native invoice binding could not be verified.',409);}
        if(($payload['fiat_minor']??null)!==Domain::cents(wc_format_decimal($order->get_total(),2)))Domain::fail('PAYMENT_UNRESOLVED','The native invoice fiat amount differs from the order.',409);
        if($row->state!=='awaiting-payment' || ($payload['state']??'')!=='awaiting-payment' || ($payload['expires_at']??0)<=time() || !\KnifeRevive\Lightning\Coordinator::reservation_valid($order))Domain::fail('PAYMENT_UNRESOLVED','The original invoice is not currently payable. Do not retry or switch rails.',409);
        return ['booking_id'=>$id,'state'=>'awaiting-payment','payable'=>true,'bolt11'=>$payload['bolt11'],'amount_sat'=>$payload['amount_sat'],
            'amount_msat'=>$payload['amount_msat'],'payment_hash'=>$payload['payment_hash'],'network'=>$payload['network'],
            'fiat_minor'=>Domain::cents(wc_format_decimal($order->get_total(),2)),'currency'=>$order->get_currency(),'expires_at'=>$payload['expires_at'],
            'wallet_authorization_required'=>true,'status_url'=>rest_url(Api::NS.'/bookings/'.$id)];
    }
    private static function day(string $date): array {
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date))Domain::fail('INVALID_REQUEST','Choose a calendar date.');
        $tz=new \DateTimeZone('America/Los_Angeles');$d=\DateTimeImmutable::createFromFormat('!Y-m-d',$date,$tz);
        if(!$d || $d->format('Y-m-d')!==$date)Domain::fail('INVALID_REQUEST','Invalid calendar date.');
        $today=new \DateTimeImmutable('today',$tz);
        if($d<$today || $d>$today->modify('+30 days'))Domain::fail('SLOT_UNAVAILABLE','Choose a service day within the next 30 days.',409);
        foreach(Settings::get()['booking_weekly_hours'] as $hours)if($hours['weekday']===(int)$d->format('N')){
            $start=new \DateTimeImmutable($date.' '.$hours['open'],$tz);$end=new \DateTimeImmutable($date.' '.$hours['close'],$tz);
            if($end->getTimestamp()<=time())Domain::fail('SLOT_UNAVAILABLE','This service window has ended.',409);
            return ['date'=>$date,'start_at'=>$start->format('c'),'end_at'=>$end->format('c'),'timezone'=>'America/Los_Angeles'];
        }
        Domain::fail('SLOT_UNAVAILABLE','KnifeRevive is closed on this day. Choose an open service day.',409);
    }
    public static function availability(): array {
        $days=[];$today=new \DateTimeImmutable('today',new \DateTimeZone('America/Los_Angeles'));
        for($i=0;$i<30;$i++){
            try{$day=self::day($today->modify('+'.$i.' days')->format('Y-m-d'));}catch(Fault $e){continue;}
            $day['available_jobs']=self::remaining($day['date']);$day['reservation_state']='request_only';$days[]=$day;
        }
        return ['enabled'=>self::enabled(),'days'=>$days,'confirmation'=>'merchant_confirmation_required'];
    }
    public static function remaining(string $date,bool $locked=false): ?int {
        $capacity=Settings::get()['booking_daily_capacity'];if($capacity===null)return null;
        global $wpdb;
        $sql=$wpdb->prepare('SELECT attempt_id FROM '.Store::table('holds')." WHERE slot_id=%s AND (state='confirmed' OR (state='held' AND expires>%d))",'booking-'.$date,time());
        $used=count($wpdb->get_col($sql.($locked?' FOR UPDATE':'')));
        return max(0,$capacity-$used);
    }
    public static function normalize(array $input,bool $buyerContext=true): array {
        Domain::fields($input,['items','mode','preferred_date','return_mode','customer','pickup_address','postal_code','notes'],['items','mode','preferred_date','postal_code']);
        if(!self::enabled())Domain::fail('BOOKING_DISABLED','Service booking requests are unavailable.',503);
        if(!in_array($input['mode'],self::MODES,true))Domain::fail('INVALID_REQUEST','Choose an offered booking option.');
        $input['preferred_date']=Domain::text($input['preferred_date'],10);self::day($input['preferred_date']);
        if(!is_array($input['items']) || !array_is_list($input['items']) || !$input['items'] || count($input['items'])>10)Domain::fail('INVALID_REQUEST','Choose service items.');
        $allowed=array_column(Settings::get()['booking_services'],'product_id');$seen=[];$count=0;$sellers=[];
        foreach($input['items'] as $item){
            if(!is_array($item))Domain::fail('INVALID_REQUEST','Invalid service item.');
            Domain::fields($item,['product_id','quantity'],['product_id','quantity']);$id=Domain::integer($item['product_id'],1,PHP_INT_MAX);$count+=Domain::integer($item['quantity'],1,30);
            $p=wc_get_product($id);
            if(isset($seen[$id]) || $count>50 || !in_array($id,$allowed,true) || !$p || $p->get_price()==='' || get_woocommerce_currency()!=='USD' || wc_get_price_decimals()!==2 || $p->get_status()!=='publish' || $p->get_catalog_visibility()==='hidden' || !$p->is_type('simple') || !Commerce::isService($p) || ($buyerContext && !$p->is_purchasable()) || !$p->is_in_stock() || !$p->has_enough_stock($item['quantity']))Domain::fail('SERVICE_UNAVAILABLE','A requested sharpening service is unavailable.');
            $seller=(int)get_post_field('post_author',$id);
            $sellers[$seller]=true;if(count($sellers)>1)Domain::fail('MULTIPLE_SELLERS','Request each service seller separately.');
            if(!get_userdata($seller) || (function_exists('dokan_is_user_seller') && (dokan_is_user_seller($seller)?!dokan_is_seller_enabled($seller):!user_can($seller,'manage_woocommerce'))))Domain::fail('SERVICE_UNAVAILABLE','The service seller is unavailable.');
            $seen[$id]=true;
        }
        usort($input['items'],static fn($a,$b)=>$a['product_id']<=>$b['product_id']);
        $input['return_mode']=$input['return_mode']??'customer_collection';
        if(!in_array($input['return_mode'],['customer_collection','courier_delivery'],true) || ($input['mode']==='pay_later_dropoff' && $input['return_mode']!=='customer_collection'))Domain::fail('INVALID_REQUEST','Choose customer collection or paid return delivery.');
        $input['postal_code']=Domain::postal($input['postal_code']);BookingCoverage::requireService($input);
        $input['notes']=Domain::text($input['notes']??'',500);
        if(isset($input['customer'])){
            if(!is_array($input['customer']))Domain::fail('INVALID_REQUEST','Invalid contact details.');
            Domain::fields($input['customer'],['name','email','phone'],['name','email']);
            $input['customer']['name']=Domain::text($input['customer']['name'],100);$input['customer']['email']=Domain::text($input['customer']['email'],254);$input['customer']['phone']=Domain::text($input['customer']['phone']??'',30);
            if(!$input['customer']['name'] || !is_email($input['customer']['email']))Domain::fail('INVALID_REQUEST','Enter a name and valid contact email.');
        }
        if(isset($input['pickup_address'])){
            $a=$input['pickup_address'];if(!is_array($a))Domain::fail('INVALID_REQUEST','Invalid pickup address.');
            Domain::fields($a,['address_1','address_2','city','state','country','postcode'],['address_1','city','state','country','postcode']);
            foreach($a as &$v)$v=Domain::text($v,150);unset($v);
            if(!$a['address_1'] || !$a['city'] || $a['country']!=='US' || $a['state']!=='CA')Domain::fail('INVALID_REQUEST','Use a complete California pickup address.');
            $a['postcode']=Domain::postal($a['postcode']);$a['address_2']=$a['address_2']??'';$input['pickup_address']=$a;
            if($a['postcode']!==$input['postal_code'])Domain::fail('INVALID_REQUEST','Service ZIP code and trip address ZIP code must match.');
        }
        return $input;
    }
    public static function create(array $input,string $owner,string $key): array {
        $input=self::normalize($input);
        return Store::idempotent($owner,'booking-create',$key,$input,static function()use($input,$owner){
            if(self::remaining($input['preferred_date'])===0)Domain::fail('SLOT_UNAVAILABLE','This service day is full.',409);
            $snapshot=[];foreach($input['items'] as $item){$p=ListingCheckout::product($item['product_id']);$snapshot[]=['product_id'=>$p['product_id'],'title'=>$p['title'],'quantity'=>$item['quantity'],'unit_price_minor'=>$p['unit_price_minor']];}
            $id=Domain::id();$referral=Domain::id();Store::put($referral,'booking_referral',$id,time()+1800,['session_owner'=>$owner]);
            Store::put($id,'booking',$owner,time()+1800,['input'=>$input,'referral_id'=>$referral,'seller_id'=>(int)get_post_field('post_author',$input['items'][0]['product_id']),'access_expires'=>min(time()+31*86400,strtotime(self::day($input['preferred_date'])['end_at'])+86400),'catalog_snapshot'=>$snapshot,'preferred_window'=>self::day($input['preferred_date']),'booking_state'=>'draft','submitted_at'=>null,'confirmed_at'=>null,'pickup_verified'=>false,'listing_intent'=>null,'payment_state'=>'not_started']);return Store::get($id);
        });
    }
    /** Referral exposes only a new service-choice draft. It is not later status authorization. */
    public static function referral(string $id): array {
        if(!Domain::validId($id))Domain::fail('NOT_FOUND','Booking referral unavailable.',404);
        $ref=Store::get($id,'booking_referral');if((int)$ref['expires']<time())Domain::fail('BOOKING_EXPIRED','This booking referral expired. Prepare a fresh draft.',410);
        $row=Store::get($ref['owner'],'booking');
        if(($row['data']['referral_id']??'')!==$id || $ref['data']['session_owner']!==$row['owner'])Domain::fail('NOT_FOUND','Booking referral unavailable.',404);
        if($row['data']['booking_state']!=='draft'){
            // A used referral cannot read any persisted customer request without its independent cookie/capability.
            if(Api::bookingOwner($row['id'])!==$row['owner'])Domain::fail('AUTHORIZATION_REQUIRED','Use your private booking status page.',401);
        }elseif(isset($row['data']['input']['customer']) || isset($row['data']['input']['pickup_address']))Domain::fail('AUTHORIZATION_REQUIRED','Use the original private review link for this contact-bearing draft.',401);
        return $row;
    }
    public static function get(string $id,string $owner): array {
        if(!Domain::validId($id))Domain::fail('NOT_FOUND','Booking unavailable.',404);
        $row=Store::get($id,'booking');
        if(!hash_equals($row['owner'],$owner) && !hash_equals($row['data']['checkout_owner']??'',$owner))Domain::fail('NOT_FOUND','Booking unavailable.',404);
        if($row['data']['booking_state']==='draft' && (int)$row['expires']<time())Domain::fail('BOOKING_EXPIRED','The booking draft expired.',410);
        return $row;
    }
    public static function submit(string $id,string $owner,array $contact,bool $firstPartyConsent=false): array {
        if(!$firstPartyConsent)Domain::fail('ADDRESS_AUTHORIZATION_REQUIRED','Approve sharing these contact and fulfillment details for this booking.',403);
        $row=Store::lock('booking:'.$id,static function()use($id,$owner,$contact){
            $row=self::get($id,$owner);$d=$row['data'];if($d['booking_state']!=='draft')return $row;
            Domain::fields($contact,['customer','pickup_address','postal_code','notes']);$input=self::normalize(array_replace($d['input'],$contact));$coverage=BookingCoverage::requireService($input);
            if(self::remaining($input['preferred_date'])===0)Domain::fail('SLOT_UNAVAILABLE','This service day is full.',409);
            if(empty($input['customer']))Domain::fail('INVALID_REQUEST','Contact details are required to request a booking.');
            if(($input['mode']==='prepaid_pickup' || $input['return_mode']==='courier_delivery') && empty($input['pickup_address']))Domain::fail('INVALID_REQUEST','Enter the address for the requested merchant trip.');
            if($coverage['address_review_required'] && empty($input['pickup_address']))Domain::fail('ADDRESS_REVIEW_REQUIRED','Enter your street address to confirm the county for this ZIP code.');
            Store::transaction(static function()use($id,$d,$input,$row){$d['input']=$input;$d['booking_state']='requested';$d['submitted_at']=time();$proofRow=$row;$proofRow['data']=$d;$d['address_consent']=BookingAuthorization::grant($proofRow);Store::update($id,$d);
                global $wpdb;if($wpdb->update(Store::table('records'),['expires'=>time()+90*86400],['id'=>$id])===false)Domain::fail('DATABASE_UNAVAILABLE','Booking persistence failed.',503);
                $saved=Store::get($id,'booking');BookingEvents::record($saved,'booking.request_received');BookingOutbox::enqueue($saved,'requested');
            });
            return self::get($id,$owner);
        });
        BookingOrderBridge::transition($id,'requested');self::notify($id,'requested');return self::get($id,$owner);
    }
    public static function confirm(string $id,bool $addressApproved=false): void {
        if(!BookingSeller::can(Store::get($id,'booking')))Domain::fail('FORBIDDEN','The service seller must confirm this request.',403);
        Store::lock('booking:'.$id,static function()use($id,$addressApproved){Store::transaction(static function()use($id,$addressApproved){
            $row=Store::get($id,'booking');$d=$row['data'];if($d['booking_state']==='confirmed')return;
            if($d['booking_state']!=='requested')Domain::fail('INVALID_REQUEST','Only submitted requests can be confirmed.');
            if(!in_array(BookingAuthorization::address($row),['granted_for_order','merchant_review_required'],true))Domain::fail('ADDRESS_AUTHORIZATION_REQUIRED','The customer must renew contact/fulfillment sharing consent.');
            // Confirming a request is not the merchant buying their own SKU.
            $input=self::normalize($d['input'],false);$day=self::day($input['preferred_date']);$s=Settings::get();
            if($s['booking_daily_capacity']===null)Domain::fail('CAPACITY_UNCONFIGURED','Set daily booking capacity before confirming reservations.');
            $courier=$input['mode']==='prepaid_pickup' || $input['return_mode']==='courier_delivery';
            $coverage=BookingCoverage::requireService($input);
            if(($courier || $coverage['address_review_required']) && (!$addressApproved || empty($input['pickup_address'])))Domain::fail('ADDRESS_REVIEW_REQUIRED','Approve this exact address and its county before confirming.');
            if($courier && !$coverage['pickup_eligible'])Domain::fail('PICKUP_UNAVAILABLE',$coverage['message'],422);
            global $wpdb;$slot='booking-'.$day['date'];$table=Store::table('slots');
            $sql=$wpdb->prepare("INSERT INTO $table (id,capacity,start_at,end_at,kind,enabled) VALUES(%s,%d,%d,%d,'customer_dropoff',1) ON DUPLICATE KEY UPDATE capacity=VALUES(capacity),enabled=1",$slot,$s['booking_daily_capacity'],strtotime($day['start_at']),strtotime($day['end_at']));
            if($wpdb->query($sql)===false)Domain::fail('DATABASE_UNAVAILABLE','Booking window could not be saved.',503);
            $wpdb->get_row($wpdb->prepare("SELECT id FROM $table WHERE id=%s FOR UPDATE",$slot));
            if(self::remaining($day['date'],true)===0)Domain::fail('SLOT_UNAVAILABLE','This service day is full.',409);
            if($wpdb->insert(Store::table('holds'),['slot_id'=>$slot,'attempt_id'=>$id,'expires'=>strtotime($day['end_at']),'state'=>'confirmed'])!==1)Domain::fail('DATABASE_UNAVAILABLE','The booking could not be reserved.',503);
            $d['booking_state']='confirmed';$d['confirmed_at']=time();$d['pickup_verified']=$courier;$d['coverage_verified']=$addressApproved || !$coverage['address_review_required'];$d['window']=$day;Store::update($id,$d);
            $saved=Store::get($id,'booking');BookingEvents::record($saved,'booking.confirmed');BookingOutbox::enqueue($saved,'confirmed');
        });});
        BookingOrderBridge::transition($id,'confirmed');
        $linked=BookingOrderBridge::linked(Store::get($id,'booking'));if($linked){$linked->update_meta_data('_krev_booking_confirmation','confirmed');$linked->save();}
        self::notify($id,'confirmed');
    }
    public static function cancel(string $id,string $owner): array {
        return Store::lock('booking:'.$id,static function()use($id,$owner){return Store::transaction(static function()use($id,$owner){$row=self::get($id,$owner);$d=$row['data'];Store::release($id);$d['booking_state']='cancelled';if(isset($d['address_consent']))$d['address_consent']['revoked_at']=time();Store::update($id,$d);$saved=self::get($id,$owner);BookingEvents::record($saved,'booking.cancelled');return $saved;});});
    }
    public static function shareConsent(string $id,string $owner,bool $approved): void {
        if(!$approved)Domain::fail('ADDRESS_AUTHORIZATION_REQUIRED','Approve sharing contact/fulfillment details.',403);
        Store::lock('booking:'.$id,static function()use($id,$owner){$r=self::get($id,$owner);if($r['data']['booking_state']==='cancelled')Domain::fail('INVALID_REQUEST','A cancelled request cannot renew consent.');$d=$r['data'];$d['address_consent']=BookingAuthorization::grant($r);Store::update($id,$d);});
    }
    public static function revokeConsent(string $id,string $owner): void {
        Store::lock('booking:'.$id,static function()use($id,$owner){$r=self::get($id,$owner);$d=$r['data'];if(isset($d['address_consent']))$d['address_consent']['revoked_at']=time();else $d['address_consent']=['revoked_at'=>time()];Store::update($id,$d);});
    }
    public static function paymentSelection(string $id): array {
        $row=Store::get($id,'booking');$d=$row['data'];$input=$d['input'];
        if(!self::prepaymentEnabled() || $d['booking_state']!=='confirmed' || $input['mode']==='pay_later_dropoff')Domain::fail('BOOKING_PREPAYMENT_DISABLED','Prepayment requires a confirmed booking and verified native checkout.',503);
        if(BookingAuthorization::address($row)!=='granted_for_order')Domain::fail('ADDRESS_AUTHORIZATION_REQUIRED','Fulfillment sharing authorization must be current.');
        $coverage=BookingCoverage::requireService($input);
        if(!$coverage['prepayment_eligible'] || empty($d['coverage_verified']))Domain::fail('ADDRESS_REVIEW_REQUIRED','Prepayment requires verified Contra Costa or Santa Clara county coverage.');
        if(($input['mode']==='prepaid_pickup' || $input['return_mode']==='courier_delivery') && !$d['pickup_verified'])Domain::fail('ADDRESS_REVIEW_REQUIRED','Pickup address needs merchant approval.');
        self::day($input['preferred_date']);
        return ['items'=>$input['items'],'fee_minor'=>Settings::get()['booking_trip_fee_minor']*(($input['mode']==='prepaid_pickup'?1:0)+($input['return_mode']==='courier_delivery'?1:0)),
            'fingerprint'=>Domain::digest([$input,$d['window'],Settings::get()['booking_trip_fee_minor'],Settings::get()['booking_policy_version']])];
    }
    public static function checkout(string $id,string $owner): array {
        return Store::lock('booking:'.$id,static function()use($id,$owner){
            $row=self::get($id,$owner);$d=$row['data'];
            self::paymentSelection($id);
            if($d['listing_intent'])return ListingCheckout::get($d['listing_intent'],$d['checkout_owner']??$row['owner'],true);
            $checkoutOwner=$d['checkout_owner']??$row['owner'];
            try{$session=Store::get($checkoutOwner,'session');}catch(Fault $e){if($e->codeName!=='NOT_FOUND')throw $e;$session=['expires'=>0];}
            if(empty($d['checkout_owner']) && (int)$session['expires']<time()){
                // A booking-scoped link cannot revive an expired general shopper token.
                $checkoutOwner=Domain::id();Store::put($checkoutOwner,'session','booking:'.$id,time()+7200,['token_hash'=>hash('sha256',Domain::token($checkoutOwner))]);
            }
            $d['checkout_owner']=$checkoutOwner;Store::update($id,$d);
            $intent=ListingCheckout::create(['items'=>$d['input']['items'],'booking_id'=>$id],$checkoutOwner,'booking-checkout-'.$id);
            $d['listing_intent']=$intent['id'];Store::update($id,$d);return $intent;
        });
    }
    public static function nativeFees($cart): void {
        if(!WC()->session)return;$id=WC()->session->get('krev_booking_id');if(!$id)return;
        // A cancelled/disabled booking must not crash the customer's cart. Native
        // order validation still blocks payment without a current confirmed booking.
        try{$row=Store::get($id,'booking');}catch(Fault $e){return;}
        $input=$row['data']['input'];$selection=['items'=>$input['items'],'fee_minor'=>Settings::get()['booking_trip_fee_minor']*(($input['mode']==='prepaid_pickup'?1:0)+($input['return_mode']==='courier_delivery'?1:0))];$items=[];
        foreach($cart->get_cart() as $line)$items[]=['product_id'=>(int)$line['product_id'],'quantity'=>(int)$line['quantity']];
        usort($items,static fn($a,$b)=>$a['product_id']<=>$b['product_id']);
        if($items!==$selection['items']){wc_add_notice('Booking items changed. Review your booking before paying.','error');return;}
        if($selection['fee_minor'])$cart->add_fee('KnifeRevive merchant transport',Domain::decimal($selection['fee_minor']),Settings::get()['booking_transport_taxable'],Settings::get()['booking_transport_tax_class']);
    }
    private static function notify(string $id,string $stage): void {
        BookingOutbox::schedule($id);
    }
    public static function response(array $row): array {
        $d=$row['data'];$input=$d['input'];$items=$d['catalog_snapshot'];$subtotal=0;
        foreach($items as $item)$subtotal+=$item['unit_price_minor']*$item['quantity'];
        $fee=Settings::get()['booking_trip_fee_minor']*(($input['mode']==='prepaid_pickup'?1:0)+($input['return_mode']==='courier_delivery'?1:0));
        $payment='not_started';if($d['listing_intent'])$payment=ListingCheckout::status($d['listing_intent'],$d['checkout_owner']??$row['owner'])['payment_state'];
        $url=add_query_arg(['krev_agent'=>'booking','booking'=>$row['id']],home_url('/'));
        $order=BookingOrderBridge::linked($row)??BookingEvents::nativeOrder($row);
        BookingEvents::observe($row);
        return ['booking_id'=>$row['id'],'booking_state'=>$d['booking_state'],'payment_state'=>$payment,'mode'=>$input['mode'],'items'=>$items,'preferred_window'=>$d['window']??$d['preferred_window'],
            'return_mode'=>$input['return_mode'],'location'=>Settings::get()['booking_location'],'currency'=>get_woocommerce_currency(),'service_subtotal_minor'=>$subtotal,'merchant_trip_fee_minor'=>$fee,
            'estimated_subtotal_minor'=>$subtotal+$fee,'total_minor'=>null,'estimate_only'=>true,'prepayment_enabled'=>self::prepaymentEnabled() && BookingCoverage::check($input['postal_code'])['prepayment_eligible'],
            'coverage'=>BookingCoverage::check($input['postal_code']),'direct_wallet_enabled'=>false,
            'policy_url'=>Settings::get()['booking_policy_url']?:null,'booking_access_token'=>self::accessToken($row),'review_url'=>$d['booking_state']==='draft' && !empty($d['referral_id']) && empty($input['customer']) && empty($input['pickup_address'])?add_query_arg(['krev_agent'=>'booking','referral'=>$d['referral_id']],home_url('/')):$url.'#booking_access='.rawurlencode(self::accessToken($row)),'status_url'=>$url,
            'appointment_confirmed'=>$d['booking_state']==='confirmed','refund_state'=>'not_issued','expires_at'=>gmdate('c',(int)$row['expires']),
            'order_reference'=>$order?(string)$order->get_order_number():null,'order_state'=>$order?$order->get_status():null,
            'order_bridge_state'=>$d['order_bridge_state']??'not_created','events'=>BookingEvents::recent($row['id']),
            'address_authorization'=>BookingAuthorization::address($row),'payment_authorization'=>BookingAuthorization::payment($row),
            'delegated_card_authorization'=>'unsupported','host_wallet_authorization'=>'unknown'];
    }
    public static function admin(): void {
        if(!current_user_can('manage_woocommerce'))return;
        echo '<h2>Sharpening booking requests</h2><p>Requests require merchant confirmation. Configure daily capacity before reserving a day. Confirm pickup coverage and the exact address separately. Confirmation does not charge the customer.</p>';
        if(isset($_POST['krev_booking_confirm'])){
            check_admin_referer('krev_booking_confirm');
            try{self::confirm((string)wp_unslash($_POST['booking_id']??''),($_POST['pickup_verified']??'')==='yes');echo '<p>Booking confirmed. No payment was initiated.</p>';}catch(\Throwable $e){echo '<p>'.esc_html($e instanceof Fault?$e->getMessage():'Booking confirmation needs review.').'</p>';}
        }
        if(isset($_POST['krev_booking_order_reconcile'])){
            check_admin_referer('krev_booking_order_reconcile');
            try{BookingOrderBridge::reconcile((string)wp_unslash($_POST['booking_id']??''),($_POST['order_confirm']??'')==='yes');echo '<p>Original unpaid native order reconciled. No payment was taken.</p>';}
            catch(\Throwable $e){echo '<p>'.esc_html($e instanceof Fault?$e->getMessage():'The original order needs review.').'</p>';}
        }
        global $wpdb;$rows=$wpdb->get_results('SELECT * FROM '.Store::table('records')." WHERE kind='booking' AND JSON_UNQUOTE(JSON_EXTRACT(data,'$.booking_state')) IN ('requested','confirmed') ORDER BY updated DESC LIMIT 50",ARRAY_A);
        foreach($rows as $row){$d=json_decode($row['data'],true);$row['data']=$d;$i=$d['input'];echo '<section><h3>'.esc_html($i['preferred_date'].' · '.$i['mode'].' · '.$d['booking_state']).'</h3><p>Reference: '.esc_html($row['id']).'</p>';
            foreach($i['items'] as $item){$p=wc_get_product($item['product_id']);echo '<p>'.esc_html(($p?$p->get_name():'Unavailable service').' × '.$item['quantity']).'</p>';}
            echo '<p>'.esc_html(implode(' · ',$i['customer']??[])).'</p><p>'.esc_html($i['notes']).'</p>';
            echo '<p>Service ZIP: '.esc_html($i['postal_code']).'</p>';
            $linked=BookingOrderBridge::linked($row);echo '<p>Native order: '.($linked?'<a href="'.esc_url($linked->get_edit_order_url()).'">'.esc_html($linked->get_order_number()).'</a>':'none').'. Bridge: '.esc_html($d['order_bridge_state']??'not_created').'. '.esc_html($d['order_bridge_error']??'').'</p>';
            echo '<p>Address/contact authorization: '.esc_html(BookingAuthorization::address($row)).'</p>';
            if($i['mode']==='pay_later_dropoff' && BookingOrderBridge::enabled()){echo '<form method="post">';wp_nonce_field('krev_booking_order_reconcile');echo '<input type="hidden" name="booking_id" value="'.esc_attr($row['id']).'"><label><input type="checkbox" name="order_confirm" value="yes" required> Reconcile this original booking into one unpaid seller order. No charge; check existing native orders first.</label><button name="krev_booking_order_reconcile" class="button">Reconcile original unpaid order</button></form>';}
            if(isset($i['pickup_address']))echo '<p>Address for county review or merchant trip: '.esc_html(implode(', ',$i['pickup_address'])).'</p>';
            if($d['booking_state']==='requested'){echo '<form method="post">';wp_nonce_field('krev_booking_confirm');echo '<input type="hidden" name="booking_id" value="'.esc_attr($row['id']).'"><label><input type="checkbox" name="pickup_verified" value="yes"> I verified this street address is in the approved county (Contra Costa or Santa Clara for prepayment or merchant trips; SF Bay Area for unpaid drop-off)</label><button name="krev_booking_confirm" class="button">Confirm requested service day</button></form>';}
            echo '</section>';
        }
        BookingOutbox::admin();
    }
}
