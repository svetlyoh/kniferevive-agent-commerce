<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;
final class Settings {
    public static function defaults(): array {
        return ['enabled' => false, 'pricing_verified' => false, 'stripe_enabled' => false, 'stripe_live' => false, 'live_verified' => false, 'stripe_use_woocommerce_keys' => false,
            'lightning_enabled' => false, 'pending_scheduling' => false, 'technology_category' => 'technology',
            'merchant_ids' => [], 'services' => [], 'postal_codes' => [], 'location' => '', 'policy_url' => '', 'return_policy_url' => '',
            'policy_version' => '', 'slots' => [], 'transport' => [], 'transport_round_trip_minor' => null, 'max_minor' => 10000];
    }
    public static function get(): array { return array_replace(self::defaults(), (array)get_option('krev_agent_settings', [])); }
    public static function validate(array $s): array {
        Domain::fields($s, array_keys(self::defaults()));
        $s = array_replace(self::defaults(), $s);
        foreach (['enabled', 'pricing_verified', 'stripe_enabled', 'stripe_live', 'live_verified', 'stripe_use_woocommerce_keys', 'lightning_enabled', 'pending_scheduling'] as $key) {
            if (!is_bool($s[$key])) Domain::fail('INVALID_SETTINGS', 'Settings flags must be booleans.');
        }
        foreach (['location', 'policy_version', 'technology_category'] as $key) $s[$key] = Domain::text($s[$key], 300);
        if (!preg_match('/^[a-z0-9-]+$/D', $s['technology_category'])) Domain::fail('INVALID_SETTINGS', 'Use a category slug.');
        foreach (['policy_url', 'return_policy_url'] as $key) {
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
    public static function page(): void {
        if (!current_user_can('manage_woocommerce')) return;
        $notice = '';
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
        echo '<div class="wrap"><h1>KnifeRevive Agent Commerce</h1><p>' . esc_html($notice) . '</p><p>New payments default to disabled. Configure service definitions, approved merchant user IDs, exact postal codes, the location, policies, and UTC or explicit-offset appointment windows. Transport requires a separately installed address verifier (documented PHP filter). Capacity counts jobs, not knives.</p><p>transport_round_trip_minor sets the combined pickup and return delivery price in cents; null keeps separate trip pricing. For a $7.99 combined price, set 799 and each individual trip to 400 ($4.00). Both trips together total exactly $7.99 before applicable tax; customer drop-off and collection have no courier fee.</p><form method="post">';
        wp_nonce_field('krev_agent_settings');
        echo '<textarea name="settings" rows="28" style="width:100%;font-family:monospace">' . esc_textarea(wp_json_encode(self::get(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</textarea><p><button class="button button-primary" name="krev_agent_save">Save validated settings</button></p></form><p>Stripe secrets use server constants, never this form or the public skill. Google Pay availability is decided by Stripe checkout and the customer device. Review retained attempts and change requests below; process refunds through existing merchant workflows.</p><table class="widefat"><tr><th>Type</th><th>Reference</th><th>State</th><th>WooCommerce order</th></tr>';
        foreach (Store::backlog(100) as $row) {
            $d = $row['data']; $order = !empty($d['order_id']) ? wc_get_order($d['order_id']) : null;
            echo '<tr><td>' . esc_html($row['kind']) . '</td><td>' . esc_html($row['id']) . '</td><td>' . esc_html($d['payment_state'] ?? $d['state'] ?? 'pending') . '</td><td>' . ($order ? '<a href="' . esc_url($order->get_edit_order_url()) . '">' . esc_html($order->get_order_number()) . '</a>' : '—') . '</td></tr>';
        }
        echo '</table>';
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
