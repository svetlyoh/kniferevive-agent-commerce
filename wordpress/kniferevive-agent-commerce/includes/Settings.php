<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;
final class Settings {
    public static function defaults(): array {
        return ['enabled' => false, 'pricing_verified' => false, 'stripe_enabled' => false, 'stripe_live' => false, 'live_verified' => false, 'stripe_use_woocommerce_keys' => false,
            'lightning_enabled' => false, 'pending_scheduling' => false, 'technology_category' => 'technology',
            'merchant_ids' => [], 'services' => [], 'postal_codes' => [], 'location' => '', 'policy_url' => '', 'return_policy_url' => '',
            'policy_version' => '', 'slots' => [], 'transport' => [], 'transport_round_trip_minor' => null, 'max_minor' => 10000,
            'listing_handoff_enabled'=>false,'listing_pricing_verified'=>false,'listing_live_verified'=>false,'listing_gateway_ids'=>[],
            'listing_policy_url'=>'','listing_policy_version'=>'','listing_services'=>[],'listing_max_minor'=>100000,
            'gateway_evidence'=>[],
            'booking_enabled'=>false,'booking_location'=>'','booking_phone'=>'','booking_services'=>[],
            'booking_weekly_hours'=>[],'booking_daily_capacity'=>null,'booking_pickup_postal_codes'=>[],
            'booking_trip_fee_minor'=>600,'booking_round_trip_fee_minor'=>1100,'booking_require_payment_submission'=>true,'booking_prepaid_enabled'=>false,'booking_policy_url'=>'','booking_policy_version'=>'',
            'booking_transport_taxable'=>false,'booking_transport_tax_class'=>'','booking_wallet_enabled'=>false,'booking_wallet_verified'=>false,
            'booking_order_timing'=>'disabled','booking_order_verified'=>false,'booking_offline_gateway_id'=>'',
            'booking_launch_approved'=>false,'booking_pay_before_confirmation'=>false,'booking_pickup_limit_enabled'=>false];
    }
    public static function get(): array { return array_replace(self::defaults(), (array)get_option('krev_agent_settings', [])); }
    public static function validate(array $s): array {
        Domain::fields($s, array_keys(self::defaults()));
        $s = array_replace(self::defaults(), $s);
        foreach (['enabled', 'pricing_verified', 'stripe_enabled', 'stripe_live', 'live_verified', 'stripe_use_woocommerce_keys', 'lightning_enabled', 'pending_scheduling','listing_handoff_enabled','listing_pricing_verified','listing_live_verified'] as $key) {
            if (!is_bool($s[$key])) Domain::fail('INVALID_SETTINGS', 'Settings flags must be booleans.');
        }
        foreach (['location', 'policy_version', 'technology_category','listing_policy_version'] as $key) $s[$key] = Domain::text($s[$key], 300);
        if (!preg_match('/^[a-z0-9-]+$/D', $s['technology_category'])) Domain::fail('INVALID_SETTINGS', 'Use a category slug.');
        foreach (['policy_url', 'return_policy_url','listing_policy_url'] as $key) {
            $s[$key] = Domain::text($s[$key], 500);
            if ($s[$key] && !Domain::httpsHost($s[$key], 'kniferevive.com')) Domain::fail('INVALID_SETTINGS', 'Policy links must use kniferevive.com HTTPS.');
        }
        foreach (['merchant_ids','services','postal_codes','slots','transport'] as $key) if (!is_array($s[$key]) || !array_is_list($s[$key]) || count($s[$key]) > 200) Domain::fail('INVALID_SETTINGS', 'Invalid settings collection.');
        foreach ($s['merchant_ids'] as $id) Domain::integer($id, 1, PHP_INT_MAX);
        foreach ($s['postal_codes'] as $zip) Domain::postal($zip);
        $products=[];
        foreach ($s['services'] as $service) {
            Domain::fields($service, ['product_id','definition'], ['product_id','definition']);
            Domain::integer($service['product_id'], 1, PHP_INT_MAX); Domain::text($service['definition'], 500);
            if (!$service['definition']) Domain::fail('INVALID_SETTINGS', 'Every service needs its size/scope definition.');
            if (isset($products[$service['product_id']])) Domain::fail('INVALID_SETTINGS', 'Service product IDs must be unique.');
            $products[$service['product_id']]=true;
        }
        $ids = [];
        foreach ($s['slots'] as $slot) {
            Domain::fields($slot, ['id','start','end','capacity','kind'], ['id','start','end','capacity','kind']);
            if (!preg_match('/^[a-z0-9-]{1,64}$/D', $slot['id']) || isset($ids[$slot['id']])) Domain::fail('INVALID_SETTINGS', 'Slot IDs must be unique.');
            if(str_starts_with($slot['id'],'booking-'))Domain::fail('INVALID_SETTINGS','The booking- slot prefix is reserved for confirmed booking allocations.');
            $ids[$slot['id']] = true;
            if (!in_array($slot['kind'], ['customer_dropoff','courier_pickup','customer_collection','courier_delivery'], true)) Domain::fail('INVALID_SETTINGS', 'Unknown slot kind.');
            if (Domain::slotTime($slot['start']) >= Domain::slotTime($slot['end'])) Domain::fail('INVALID_SETTINGS', 'Slot end must follow its start.');
            Domain::integer($slot['capacity'], 1, 200);
        }
        $kinds=[];
        foreach ($s['transport'] as $leg) {
            Domain::fields($leg, ['kind','fee_minor','taxable','tax_class'], ['kind','fee_minor','taxable','tax_class']);
            if (!in_array($leg['kind'], ['courier_pickup','courier_delivery'], true) || !is_bool($leg['taxable'])) Domain::fail('INVALID_SETTINGS', 'Invalid transport leg.');
            if (isset($kinds[$leg['kind']])) Domain::fail('INVALID_SETTINGS', 'Configure each transport kind only once.');
            $kinds[$leg['kind']]=$leg;
            Domain::integer($leg['fee_minor'], 0, 100000); Domain::text($leg['tax_class'], 100);
        }
        if ($s['transport_round_trip_minor'] !== null) {
            Domain::integer($s['transport_round_trip_minor'], 0, 200000);
            // The total can be staged while courier transport is disabled.
            if ($kinds) {
                if (!isset($kinds['courier_pickup'],$kinds['courier_delivery'])) Domain::fail('INVALID_SETTINGS', 'A combined transport price requires both courier legs.');
                $pickup=$kinds['courier_pickup']; $delivery=$kinds['courier_delivery'];
                if ($pickup['taxable'] !== $delivery['taxable'] || $pickup['tax_class'] !== $delivery['tax_class']) Domain::fail('INVALID_SETTINGS', 'Combined transport legs must use the same tax treatment.');
                if ($s['transport_round_trip_minor'] > $pickup['fee_minor'] + $delivery['fee_minor']) Domain::fail('INVALID_SETTINGS', 'Combined transport price cannot exceed the separate trip fees.');
            }
        }
        Domain::integer($s['max_minor'], 1, 1000000);
        Domain::integer($s['listing_max_minor'],1,1000000);
        foreach(['listing_gateway_ids','listing_services','gateway_evidence'] as $key)if(!is_array($s[$key]) || !array_is_list($s[$key]) || count($s[$key])>100)Domain::fail('INVALID_SETTINGS','Invalid listing settings collection.');
        foreach($s['listing_gateway_ids'] as $id)if(!is_string($id) || !preg_match('/^[a-z0-9_-]{1,80}$/D',$id) || $id==='krev_agent_checkout')Domain::fail('INVALID_SETTINGS','Use native gateway IDs; the service adapter cannot pay marketplace listings.');
        $seen=[];foreach($s['listing_services'] as $terms){
            Domain::fields($terms,['product_id','terms_url','fulfillment_note','policy_version','native_fulfillment_verified'],['product_id','terms_url','fulfillment_note','policy_version','native_fulfillment_verified']);
            Domain::integer($terms['product_id'],1,PHP_INT_MAX);
            if(isset($seen[$terms['product_id']]))Domain::fail('INVALID_SETTINGS','Listing service approvals must be unique.');$seen[$terms['product_id']]=true;
            if(!is_bool($terms['native_fulfillment_verified']) || !Domain::httpsHost(Domain::text($terms['terms_url'],500),'kniferevive.com') || !Domain::text($terms['fulfillment_note'],1000) || !Domain::text($terms['policy_version'],100))Domain::fail('INVALID_SETTINGS','Each approved native service needs reviewed public terms, fulfillment instructions and a version.');
        }
        foreach($s['gateway_evidence'] as $e){
            Domain::fields($e,['gateway_id','environment','state','checked_at','reference'],['gateway_id','environment','state','checked_at','reference']);
            if(!preg_match('/^[a-z0-9_-]{1,80}$/D',(string)$e['gateway_id']) || !in_array($e['environment'],['test','live'],true) || !in_array($e['state'],['disabled','configured_test','test_payment_verified','live_webhook_configured','live_payment_verified','needs_operator_review'],true))Domain::fail('INVALID_SETTINGS','Invalid gateway evidence state.');
            Domain::integer($e['checked_at'],0,time());
            // Retain only a redacted operator reference, never provider IDs or credentials.
            if(!preg_match('/^[a-zA-Z0-9 ._-]{1,80}$/D',(string)$e['reference']) || preg_match('/(?:sk_|whsec_|pi_|ch_|cs_|acct_)/i',$e['reference']))Domain::fail('INVALID_SETTINGS','Use a redacted internal evidence label, never provider IDs or secrets.');
        }
        foreach(['booking_require_payment_submission','booking_enabled','booking_prepaid_enabled','booking_transport_taxable','booking_wallet_enabled','booking_wallet_verified','booking_launch_approved','booking_pay_before_confirmation','booking_pickup_limit_enabled'] as $flag)if(!is_bool($s[$flag]))Domain::fail('INVALID_SETTINGS','Booking flags must be booleans.');
        if(!is_bool($s['booking_order_verified']) || !in_array($s['booking_order_timing'],['disabled','on_submit','on_confirm'],true) || !in_array($s['booking_offline_gateway_id'],['','cod','bacs','cheque'],true))Domain::fail('INVALID_SETTINGS','Use an approved and tested unpaid-order timing and native offline method.');
        foreach(['booking_location'=>300,'booking_phone'=>30,'booking_policy_version'=>100,'booking_transport_tax_class'=>100] as $field=>$limit)$s[$field]=Domain::text($s[$field],$limit);
        $s['booking_policy_url']=Domain::text($s['booking_policy_url'],500);
        if($s['booking_policy_url'] && !Domain::httpsHost($s['booking_policy_url'],'kniferevive.com'))Domain::fail('INVALID_SETTINGS','Booking policies must use KnifeRevive HTTPS.');
        Domain::integer($s['booking_trip_fee_minor'],0,100000);Domain::integer($s['booking_round_trip_fee_minor'],0,200000);
        if($s['booking_daily_capacity']!==null)Domain::integer($s['booking_daily_capacity'],1,200);
        foreach(['booking_services','booking_weekly_hours','booking_pickup_postal_codes'] as $field)if(!is_array($s[$field]) || !array_is_list($s[$field]) || count($s[$field])>200)Domain::fail('INVALID_SETTINGS','Invalid booking collection.');
        $seen=[];foreach($s['booking_services'] as $service){Domain::fields($service,['product_id','definition'],['product_id','definition']);Domain::integer($service['product_id'],1,PHP_INT_MAX);if(!Domain::text($service['definition'],500) || isset($seen[$service['product_id']]))Domain::fail('INVALID_SETTINGS','Use unique booking services with scope definitions.');$seen[$service['product_id']]=true;}
        $seen=[];foreach($s['booking_weekly_hours'] as $hours){Domain::fields($hours,['weekday','open','close'],['weekday','open','close']);Domain::integer($hours['weekday'],1,7);foreach(['open','close'] as $field)if(!is_string($hours[$field]) || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$hours[$field]))Domain::fail('INVALID_SETTINGS','Use 24-hour booking times.');if($hours['open']>=$hours['close'] || isset($seen[$hours['weekday']]))Domain::fail('INVALID_SETTINGS','Use one nonempty window per open weekday.');$seen[$hours['weekday']]=true;}
        foreach($s['booking_pickup_postal_codes'] as &$zip){$zip=Domain::postal($zip);$c=BookingCoverage::check($zip,false);if(!$c['prepayment_eligible'] || $c['address_review_required'])Domain::fail('INVALID_SETTINGS','Pickup ZIPs must be unambiguous Contra Costa or Santa Clara service ZIPs.');}unset($zip);$s['booking_pickup_postal_codes']=array_values(array_unique($s['booking_pickup_postal_codes']));
        return $s;
    }
    public static function operational(): bool {
        $s = self::get();
        return $s['enabled'] && $s['pricing_verified'] && $s['services'] && $s['merchant_ids'] && $s['postal_codes'] && $s['location'] && $s['policy_url'] && $s['policy_version'];
    }
    public static function stripeKey(bool $live): string {
        $key = $live ? 'KREV_AGENT_STRIPE_LIVE_SECRET_KEY' : 'KREV_AGENT_STRIPE_TEST_SECRET_KEY';
        if (defined($key)) return (string)constant($key);
        if (!self::get()['stripe_use_woocommerce_keys']) return '';
        $s=(array)get_option('woocommerce_stripe_settings',[]);
        $secret=(string)($s[$live?'secret_key':'test_secret_key']??'');
        return preg_match($live?'/^sk_live_[A-Za-z0-9]+$/D':'/^sk_test_[A-Za-z0-9]+$/D',$secret) ? $secret : '';
    }
    public static function webhookSecret(bool $live): string {
        $key = $live ? 'KREV_AGENT_STRIPE_LIVE_WEBHOOK_SECRET' : 'KREV_AGENT_STRIPE_TEST_WEBHOOK_SECRET';
        return defined($key) ? (string)constant($key) : StripeSetup::secret($live);
    }
    public static function rails(): array {
        $s = self::get(); $rails = [];
        if (!self::operational()) return $rails;
        if ($s['stripe_enabled'] && (!$s['stripe_live'] || $s['live_verified']) && self::stripeKey($s['stripe_live']) && self::webhookSecret($s['stripe_live'])) $rails[] = 'stripe_checkout';
        if ($s['lightning_enabled'] && $s['live_verified'] && class_exists('KnifeRevive\\Lightning\\Settings') && \KnifeRevive\Lightning\Settings::get('accept', 'no') === 'yes') $rails[] = 'lightning';
        return $rails;
    }
    public static function saveDailyCapacity(int $capacity): void {
        if(!current_user_can('manage_woocommerce'))Domain::fail('FORBIDDEN','Only a WooCommerce administrator can change booking capacity.',403);
        Domain::integer($capacity,1,200);$s=self::get();$s['booking_daily_capacity']=$capacity;
        update_option('krev_agent_settings',self::validate($s),false);
    }
    public static function savePickupCoverage(bool $limited,string $text): void {
        if(!current_user_can('manage_woocommerce'))Domain::fail('FORBIDDEN','Only a WooCommerce administrator can change pickup coverage.',403);
        $zips=preg_split('/[\s,;]+/',trim($text),-1,PREG_SPLIT_NO_EMPTY);$s=self::get();$s['booking_pickup_limit_enabled']=$limited;$s['booking_pickup_postal_codes']=$zips;
        update_option('krev_agent_settings',self::validate($s),false);
    }
    public static function saveTripFees(string $single,string $combined): void {
        if(!current_user_can('manage_woocommerce'))Domain::fail('FORBIDDEN','Only a WooCommerce administrator can change trip fees.',403);
        $s=self::get();
        foreach(['booking_trip_fee_minor'=>[$single,100000],'booking_round_trip_fee_minor'=>[$combined,200000]] as $key=>[$amount,$maximum]){
            $amount=trim($amount);
            if(!preg_match('/^([0-9]{1,4})(?:\.([0-9]{1,2}))?$/D',$amount,$parts))Domain::fail('INVALID_SETTINGS','Enter a nonnegative dollar amount with at most two decimal places.');
            $minor=(int)$parts[1]*100+(int)str_pad($parts[2]??'',2,'0');
            if($minor>$maximum)Domain::fail('INVALID_SETTINGS','One-trip fees cannot exceed $1,000; combined fees cannot exceed $2,000.');$s[$key]=$minor;
        }
        update_option('krev_agent_settings',self::validate($s),false);
    }
    private static function tripFeesForm(): void {
        $s=self::get();
        echo '<section id="sharpening-trip-fees"><h2>Sharpening trip fees</h2><p>Set the fee per order in dollars. One trip means they pick up from the customer or deliver back. The comeback combo includes both pickup and return delivery. Sharpening is charged separately; configured tax is added at payment.</p><form method="post">';wp_nonce_field('krev_booking_trip_fees');
        echo '<p><label for="booking-single-trip">One pickup or delivery trip ($ per order)</label><br><input id="booking-single-trip" name="booking_single_trip_dollars" type="number" min="0" max="1000" step="0.01" required value="'.esc_attr(Domain::decimal($s['booking_trip_fee_minor'])).'"></p>';
        echo '<p><label for="booking-combined-trip">Pickup + delivery · comeback combo ($ total per order)</label><br><input id="booking-combined-trip" name="booking_combined_trip_dollars" type="number" min="0" max="2000" step="0.01" required value="'.esc_attr(Domain::decimal($s['booking_round_trip_fee_minor'])).'"></p>';
        echo '<p><button class="button button-primary" name="krev_booking_trip_fees_save">Save trip fees</button></p><p>Applies to new booking quotes on the website and bot API. Existing bookings and orders keep their quoted fees. Customer drop-off and collection have a $0 trip fee. These are booking fees, separate from parcel shipping rates.</p></form></section>';
    }
    private static function pickupCoverageForm(): void {
        $s=self::get();echo '<section id="pickup-zip-coverage"><h2>Prepaid pickup ZIP codes</h2><p>Leave custom coverage off for all supported Contra Costa and Santa Clara ZIPs. Turn it on to serve only the ZIPs below; an empty custom list disables pickup. Customer drop-off prepayment keeps its county check. Existing orders are retained for merchant review.</p><form method="post">';wp_nonce_field('krev_booking_pickup');
        echo '<label><input type="checkbox" name="booking_pickup_limit_enabled" value="yes"'.checked($s['booking_pickup_limit_enabled'],true,false).'> Use a custom pickup ZIP list</label><p><label for="pickup-zip-list">Available prepaid pickup ZIP codes (one per line or separated by commas)</label></p><textarea id="pickup-zip-list" name="booking_pickup_postal_codes" rows="8" cols="40">'.esc_textarea(implode("\n",$s['booking_pickup_postal_codes'])).'</textarea><p><button class="button button-primary" name="krev_booking_pickup_save">Save pickup ZIP codes</button></p></form></section>';
    }
    public static function page(): void {
        if (!current_user_can('manage_woocommerce')) return;
        $notice = '';
        if(isset($_POST['krev_booking_trip_fees_save'])){
            check_admin_referer('krev_booking_trip_fees');
            try{self::saveTripFees((string)wp_unslash($_POST['booking_single_trip_dollars']??''),(string)wp_unslash($_POST['booking_combined_trip_dollars']??''));$notice='Trip fees saved for new booking quotes. Existing bookings and orders keep their quoted fees.';}
            catch(\Throwable $e){$notice=$e instanceof Fault?$e->getMessage():'Trip fees could not be saved.';}
        }
        if(isset($_POST['krev_booking_pickup_save'])){
            check_admin_referer('krev_booking_pickup');
            try{self::savePickupCoverage(($_POST['booking_pickup_limit_enabled']??'')==='yes',(string)wp_unslash($_POST['booking_pickup_postal_codes']??''));$notice='Pickup ZIP coverage saved. Existing orders remain available for merchant review.';}
            catch(\Throwable $e){$notice=$e instanceof Fault?$e->getMessage():'Pickup coverage could not be saved.';}
        }

        if(isset($_POST['krev_booking_capacity_save'])){
            check_admin_referer('krev_booking_capacity');
            try{$capacity=(string)wp_unslash($_POST['booking_daily_capacity']??'');
                if(!preg_match('/^[0-9]+$/D',$capacity))Domain::fail('INVALID_SETTINGS','Daily capacity must be a whole number from 1 to 200.');
                self::saveDailyCapacity((int)$capacity);$notice='Daily sharpening capacity saved. This limit counts jobs per open day, not knives. Existing confirmed bookings are retained.';
            }catch(\Throwable $e){$notice=$e instanceof Fault?$e->getMessage():'Daily capacity could not be saved.';}
        }
        if (isset($_POST['krev_agent_test_webhook']) && current_user_can('manage_options')) {
            check_admin_referer('krev_agent_test_webhook');
            try { StripeSetup::testWebhook(); $notice='Dedicated Stripe test webhook configured. New payments remain controlled by the settings below.'; }
            catch (\Throwable $e) { $notice=$e instanceof Fault ? $e->getMessage() : 'Webhook setup could not be verified. No payment was created.'; }
        }
        if (isset($_POST['krev_agent_reconcile'])) {
            check_admin_referer('krev_agent_reconcile');
            try {
                $id=(string)wp_unslash($_POST['attempt_id']??'');
                if (!Domain::validId($id)) Domain::fail('INVALID_REQUEST','Invalid attempt reference.');
                Store::lock('attempt:'.$id,static function () use ($id) {
                    $a=Store::get($id,'attempt')['data']; $a['checked_at']=0; $a['failures']=0; $a['auto_paused']=false; $a['next_check_at']=0; Store::update($id,$a);
                });
                Payments::start($id); Payments::reconcile($id); $notice='Original attempt reconciled. Review its retained state.';
            } catch (\Throwable $e) { $notice='The original attempt still needs reconciliation. No new payment reference was issued.'; }
        }
        if (isset($_POST['krev_agent_save'])) {
            check_admin_referer('krev_agent_settings');
            try {
                $input = json_decode(wp_unslash($_POST['settings'] ?? ''), true, 32, JSON_THROW_ON_ERROR);
                if (!is_array($input)) Domain::fail('INVALID_SETTINGS', 'Use a JSON object.');
                $s = self::validate($input);
                Store::syncSlots($s['slots']);
                update_option('krev_agent_settings', $s, false);
                $notice = 'Settings saved. Capacity is jobs per window. Existing payment attempts are retained.';
            } catch (\Throwable $e) { $notice = $e instanceof Fault ? $e->getMessage() : 'Settings could not be saved. Check JSON and database readiness.'; }
        }
        echo '<div class="wrap"><h1>KnifeRevive Agent Commerce</h1><p>' . esc_html($notice) . '</p><h2 id="daily-sharpening-capacity">Daily sharpening capacity</h2><p>Maximum confirmed jobs per open day in America/Los_Angeles. A job may contain several knives. Requested days still need merchant confirmation. Lowering the limit does not cancel existing confirmed jobs.</p><form method="post">';
        wp_nonce_field('krev_booking_capacity');
        echo '<label for="booking_daily_capacity">Jobs per open day</label> <input id="booking_daily_capacity" name="booking_daily_capacity" type="number" min="1" max="200" step="1" required value="'.esc_attr((string)(self::get()['booking_daily_capacity']??'')).'"> <button class="button button-primary" name="krev_booking_capacity_save">Save daily capacity</button></form>';self::tripFeesForm();self::pickupCoverageForm();echo '<h2>Advanced settings</h2><p>New payments default to disabled. Configure service definitions, approved merchant user IDs, exact postal codes, the location, policies, and UTC or explicit-offset appointment windows. Transport requires a separately installed address verifier (documented PHP filter). Capacity counts jobs, not knives.</p><p>The trip fee fields above update booking_trip_fee_minor and booking_round_trip_fee_minor (integer cents). Customer drop-off and collection have no transport fee. New bookings retain their quoted transport price; existing orders keep their original financial records. The legacy transport adapter has separate settings.</p><form method="post">';
        wp_nonce_field('krev_agent_settings');
        echo '<textarea name="settings" rows="28" style="width:100%;font-family:monospace">' . esc_textarea(wp_json_encode(self::get(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</textarea><p><button class="button button-primary" name="krev_agent_save">Save validated settings</button></p></form><p>Stripe secrets use server constants, never this form or the public skill. Google Pay availability is decided by Stripe checkout and the customer device. Review retained attempts and change requests below; process refunds through existing merchant workflows.</p><table class="widefat"><tr><th>Type</th><th>Reference</th><th>State</th><th>WooCommerce order</th></tr>';
        foreach (Store::backlog(100) as $row) {
            $d = $row['data']; $order = !empty($d['order_id']) ? wc_get_order($d['order_id']) : null;
            echo '<tr><td>' . esc_html($row['kind']) . '</td><td>' . esc_html($row['id']) . '</td><td>' . esc_html($d['payment_state'] ?? $d['state'] ?? 'pending') . '</td><td>' . ($order ? '<a href="' . esc_url($order->get_edit_order_url()) . '">' . esc_html($order->get_order_number()) . '</a>' : '—') . '</td></tr>';
        }
        echo '</table>';
        Booking::admin();
        GatewayDiagnostics::render();
        if (current_user_can('manage_options')) {
            echo '<h2>Stripe test connection</h2><p>Test key: '.(self::stripeKey(false)?'present':'missing').'. Dedicated test webhook signing secret: '.(self::webhookSecret(false)?'present':'missing').'. Enable stripe_use_woocommerce_keys above to reuse the existing official WooCommerce Stripe test key. The button registers only test events with Stripe, stores the signing secret encrypted, and never enables live payments. Existing gateway webhooks are retained.</p><form method="post">';
            wp_nonce_field('krev_agent_test_webhook');
            echo '<button class="button" name="krev_agent_test_webhook">Connect dedicated Stripe test webhook</button></form>';
        }
        echo '<h2>Reconcile an original attempt</h2><form method="post">';
        wp_nonce_field('krev_agent_reconcile');
        echo '<label>Attempt reference <input name="attempt_id" maxlength="32" pattern="[a-f0-9]{32}" required></label> <button class="button" name="krev_agent_reconcile">Reconcile original attempt</button></form><p>Automatic transient retries use exponential backoff and pause after eight failures. Manual review is retained; do not issue another payment to resolve an uncertain attempt.</p></div>';
    }
}
