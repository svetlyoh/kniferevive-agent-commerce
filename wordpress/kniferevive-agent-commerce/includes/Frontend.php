<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;
final class Frontend {
    public static function statusUrl(string $id): string { return add_query_arg(['krev_agent'=>'status','attempt'=>$id],home_url('/')); }
    private static function localTime(string $value): string { return (new \DateTimeImmutable($value))->setTimezone(new \DateTimeZone('America/Los_Angeles'))->format('M j, Y g:i A T'); }
    private static function csrf(string $owner,string $quote,int $bucket): string { return hash_hmac('sha256',Domain::canonical([$owner,$quote,$bucket]),wp_salt('nonce')); }
    public static function render(): void {
        if(($_GET['krev_agent']??'')==='booking'){if(($_GET['payment']??'')==='1')BookingCheckoutFrontend::render();BookingFrontend::render();}
        if(in_array($_GET['krev_agent']??'',['listing-review','listing-status'],true))ListingFrontend::render();
        if (parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)==='/AI.md') {
            header('Content-Type: text/markdown; charset=utf-8'); header('X-Content-Type-Options: nosniff');
            readfile(dirname(__DIR__).'/assets/AI.md'); exit;
        }
        if (!isset($_GET['krev_agent'])) return;
        PrivateBrand::headers();
        $base=plugin_dir_url(FILE).'assets/';
        $heading=($_GET['krev_agent']??'')==='status'?'Payment and appointment status':'Review your service';
        PrivateBrand::start($heading,'data-attach="'.esc_url(rest_url(Api::NS.'/sessions/attach')).'"');echo '<h1>'.esc_html($heading).'</h1>';
        try {
            $owner=Api::owner();
            $view=Domain::text((string)wp_unslash($_GET['krev_agent']),20);
            if ($view==='review') self::review($owner);
            elseif ($view==='status') self::status($owner);
            else Domain::fail('NOT_FOUND','Page unavailable.',404);
        } catch (\Throwable $e) {
            $message=$e instanceof Fault?$e->getMessage():'This request could not be completed. Check the original attempt before trying another payment.';
            echo '<p>'.esc_html($message).'</p><p>Use your private review link or contact KnifeRevive through the site.</p>';
        }
        echo '<p><a href="'.esc_url(home_url('/#knife-sharpening')).'">KnifeRevive sharpening</a></p>';PrivateBrand::end();exit;
    }
    private static function review(string $owner): void {
        $id=(string)wp_unslash($_GET['quote']??'');
        if (!Domain::validId($id)) Domain::fail('NOT_FOUND','Quote unavailable.',404);
        $row=Store::get($id,'quote',$owner);
        if ((int)$row['expires']<time()) Domain::fail('QUOTE_EXPIRED','Obtain a fresh quote.',409);
        if (($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
            if (!Api::firstPartyForm()) Domain::fail('FORBIDDEN','First-party approval required.',403);
            $csrf=(string)wp_unslash($_POST['csrf']??''); $bucket=intdiv(time(),600);
            if (!hash_equals(self::csrf($owner,$id,$bucket),$csrf) && !hash_equals(self::csrf($owner,$id,$bucket-1),$csrf)) Domain::fail('FORBIDDEN','Review form expired.',403);
            if ($row['data']['requires_customer']) {
                $input=$row['data']['input'];
                $input['customer']=['name'=>wp_unslash($_POST['name']??''),'email'=>wp_unslash($_POST['email']??''),'phone'=>wp_unslash($_POST['phone']??''),
                    'billing'=>['address_1'=>wp_unslash($_POST['address_1']??''),'city'=>wp_unslash($_POST['city']??''),'state'=>'CA','country'=>'US','postcode'=>$input['postal_code']]];
                $new=Commerce::quote($input,$owner,'private-contact-'.Domain::digest($input));
                echo '<p>Your contact details are saved for this private quote. Review the final total before approving.</p><p><a href="'.esc_url(add_query_arg(['krev_agent'=>'review','quote'=>$new['id']],home_url('/'))).'">Review final quote</a></p>'; return;
            }
            if (($_POST['accept']??'')!=='yes') Domain::fail('AUTHORIZATION_REQUIRED','Review and accept the quote and policies.',403);
            $consent=Commerce::grant($id,$owner);
            $a=Commerce::attempt(['quote_id'=>$id,'quote_hash'=>$row['data']['quote_hash'],'consent_id'=>$consent],$owner,'first-party-review-'.$id);
            echo '<p>Approval recorded. Payment completion still requires your checkout or wallet authorization.</p><p><a href="'.esc_url(self::statusUrl($a['id'])).'">Open payment and booking status</a></p>'; return;
        }
        $q=Commerce::publicQuote($row);
        echo '<ul>';
        foreach ($q['items'] as $line) echo '<li>'.esc_html($line['name'].' × '.$line['quantity'].' — $'.Domain::decimal($line['total_minor'])).'</li>';
        foreach ($q['fees'] as $fee) echo '<li>'.esc_html($fee['name'].' — $'.Domain::decimal($fee['total_minor'])).'</li>';
        echo '</ul><p>Taxes: $'.esc_html(Domain::decimal($q['tax_minor'])).'</p><p><strong>Total: $'.esc_html(Domain::decimal($q['total_minor'])).' USD</strong></p>';
        echo '<p>Payment method: '.esc_html($q['rail']==='stripe_checkout'?'Stripe checkout':'Bitcoin Lightning').'. '.($q['rail']==='stripe_checkout'?(Settings::get()['stripe_live']?'Live payment.':'TEST checkout: no real payment.'):'Live Bitcoin Lightning payment.').'</p>';
        echo '<p>Location: '.esc_html($q['location']).'</p>';
        foreach (['intake','return'] as $leg) {
            $label=match($q[$leg]['kind']) { 'customer_dropoff'=>'Drop off your knives', 'customer_collection'=>'Collect your knives', 'courier_pickup'=>'Courier pickup', 'courier_delivery'=>'Courier delivery', default=>'Contact the merchant' };
            echo '<p>'.esc_html(ucfirst($leg).': '.$label).'</p>';
            foreach (Store::slots() as $slot) if (($q[$leg]['slot_id']??'')===$slot['slot_id']) {
                $start=(new \DateTimeImmutable($slot['start_at']))->setTimezone(new \DateTimeZone('America/Los_Angeles'))->format('M j, Y g:i A T');
                $end=(new \DateTimeImmutable($slot['end_at']))->setTimezone(new \DateTimeZone('America/Los_Angeles'))->format('g:i A T');
                echo '<p>'.esc_html($start.' – '.$end.'; appointment will be confirmed after verified payment.').'</p>';
            }
        }
        if ($q['booking_mode']==='pending_scheduling') echo '<p><strong>This prepays the service. Your appointment remains pending until the merchant arranges it.</strong></p>';
        echo '<p>Quote expires '.esc_html(self::localTime($q['expires_at'])).'. <a href="'.esc_url($q['policy_url']).'" target="_blank" rel="noopener noreferrer">Service, cancellation and refund policies</a></p><form method="post"><input type="hidden" name="csrf" value="'.esc_attr(self::csrf($owner,$id,intdiv(time(),600))).'">';
        if ($row['data']['requires_customer']) {
            foreach (['name'=>'Full name','email'=>'Receipt email','phone'=>'Phone (optional)','address_1'=>'Billing street address','city'=>'Billing city'] as $field=>$label) {
                echo '<label>'.esc_html($label).'<input name="'.esc_attr($field).'" type="'.($field==='email'?'email':'text').'" maxlength="'.($field==='email'?254:150).'" '.($field==='phone'?'':'required').'></label>';
            }
            echo '<p>California, '.esc_html($row['data']['input']['postal_code']).'. These details are used for tax calculation and service fulfillment.</p><button>Prepare final review</button>';
        } else {
            echo '<label><input type="checkbox" name="accept" value="yes" required> I approve this quoted purchase and its handoff, scheduling and policy terms.</label><button>Approve and create payment request</button>';
        }
        echo '</form>';
    }
    private static function status(string $owner): void {
        $id=(string)wp_unslash($_GET['attempt']??'');
        if (!Domain::validId($id)) Domain::fail('NOT_FOUND','Attempt unavailable.',404);
        Store::get($id,'attempt',$owner); Payments::reconcile($id); $s=Commerce::status(Store::get($id,'attempt',$owner));
        $payment=match($s['payment_state']) { 'creating'=>'Preparing payment request', 'pending'=>'Awaiting payment', 'paid'=>'Verified', 'expired'=>'Payment request expired', 'refunded'=>'Refund verified', 'unknown'=>'Verification unavailable', default=>'Merchant review required' };
        $booking=match($s['booking_state']) { 'held'=>'Reserved temporarily while awaiting verified payment', 'confirmed'=>'Confirmed', 'pending_scheduling'=>'Contact the merchant to arrange your appointment', 'expired'=>'Reservation expired', 'cancelled'=>'Cancelled', default=>'Merchant review required' };
        echo '<p>Reference: '.esc_html($id).'</p><p><strong>Payment: '.esc_html($payment).'</strong></p><p>Appointment: '.esc_html($booking).'</p>';
        if ($s['payment_state']==='paid') echo '<p>Payment verified. '.esc_html($s['booking_state']==='confirmed'?'Your handoff windows are confirmed.':'Contact the merchant to arrange or confirm your appointment.').'</p>';
        elseif ($s['payment_state']==='pending') {
            if ($s['checkout_url']) echo '<p><a href="'.esc_url($s['checkout_url']).'" rel="noreferrer">Continue to secure Stripe checkout</a></p><p>Google Pay appears only when Stripe and your device support it.</p>';
            if ($s['invoice']) echo '<label>Lightning invoice<textarea readonly rows="5">'.esc_textarea($s['invoice']['bolt11']).'</textarea></label><p>'.esc_html((string)$s['invoice']['amount_sat']).' sats. Use your authorized wallet; validate amount, payee, network and expiry before paying.</p>';
        } else echo '<p>Wait or contact the merchant before starting another payment.</p>';
        echo '<p><a href="'.esc_url(self::statusUrl($id)).'">Check status again</a></p>';
    }
}
