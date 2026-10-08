<?php
namespace KnifeRevive\AgentCommerce;
defined('ABSPATH') || exit;
/** Read-only, administrator-only observations; configuration never proves settlement. */
final class GatewayDiagnostics {
    public static function snapshot(): array {
        if(!current_user_can('manage_woocommerce'))Domain::fail('FORBIDDEN','Merchant diagnostics require administrator access.',403);
        require_once ABSPATH.'wp-admin/includes/plugin.php';$installed=get_plugins();$plugins=[];
        foreach($installed as $file=>$p)if(preg_match('/woocommerce|dokan|kniferevive.*(?:connect|orders|commission|lightning)|conditional.*fee/',$file))$plugins[]=['file'=>$file,'version'=>$p['Version'],'active'=>is_plugin_active($file)];
        $stripe=(array)get_option('woocommerce_stripe_settings',[]);$rows=[];
        foreach(WC()->payment_gateways()->payment_gateways() as $gateway)foreach(['test','live'] as $environment){
            $test=$environment==='test';$enabled=$gateway->enabled==='yes';$selected=$gateway->get_option('testmode','unknown');
            $configured=$gateway->id==='stripe' && !empty($stripe[$test?'test_secret_key':'secret_key']) && !empty($stripe[$test?'test_webhook_secret':'webhook_secret']);
            $state=!$enabled?'disabled':($configured && $test?'configured_test':'needs_operator_review');$evidence=null;
            foreach(Settings::get()['gateway_evidence'] as $entry)if($entry['gateway_id']===$gateway->id && $entry['environment']===$environment)$evidence=$entry;
            $rows[]=['gateway_id'=>$gateway->id,'class'=>get_class($gateway),'environment'=>$environment,'selected_mode'=>$selected==='unknown'?'unknown':($selected==='yes'?'test':'live'),
                'observed_state'=>$state,'operator_reported_evidence'=>$evidence,'webhook_secret_present'=>$gateway->id==='stripe'?!empty($stripe[$test?'test_webhook_secret':'webhook_secret']):null,
                'last_success_at'=>$gateway->id==='stripe'?(int)get_option($test?'wc_stripe_wh_test_last_success_at':'wc_stripe_wh_last_success_at',0):null,
                'last_failure_at'=>$gateway->id==='stripe'?(int)get_option($test?'wc_stripe_wh_test_last_failure_at':'wc_stripe_wh_last_failure_at',0):null];
        }
        $account=(string)($stripe['account_id']??'');$connect=(array)get_option('kr_connect_settings',[]);
        $modules=(array)get_option('dokan_pro_active_modules',[]);
        return ['plugins'=>$plugins,'gateways'=>$rows,'stripe_account_masked'=>$account?'…'.substr($account,-4):null,
            'dokan_stripe_modules'=>['stripe_connect_active'=>in_array('stripe',$modules,true),'stripe_express_active'=>in_array('stripe_express',$modules,true)],
            'webhook_owners'=>[
                ['owner'=>'WooCommerce Stripe','destination'=>add_query_arg('wc-api','wc_stripe',home_url('/')),'strategy'=>'reuse_gateway_managed','events'=>'inspect managed endpoint in Stripe Workbench'],
                ['owner'=>'Dokan Connect module','destination'=>null,'strategy'=>'inspect installed active module; do not assume it is used','events'=>'module dependent'],
                ['owner'=>'KnifeRevive Stripe Connect','destination'=>rest_url('kniferevive-connect/v1/stripe/webhook'),'strategy'=>'separate seller distribution/reconciliation','configured_environment'=>$connect['environment']??'unknown','transfers_enabled'=>($connect['transfers_enabled']??'no')==='yes'],
                ['owner'=>'Operator-service adapter','destination'=>rest_url(Api::NS.'/stripe/webhook'),'strategy'=>'only adapter-owned service attempts','test_secret_present'=>(bool)Settings::webhookSecret(false),'live_secret_present'=>(bool)Settings::webhookSecret(true),
                    'events'=>['checkout.session.completed','checkout.session.expired','checkout.session.async_payment_succeeded','checkout.session.async_payment_failed','charge.refunded']],
                ['owner'=>'Native Lightning','destination'=>null,'strategy'=>'existing coordinator only; inspect enabled native plugin']
            ],'listing_handoff'=>ListingCheckout::enabled()?'handoff_enabled':'unavailable','listing_direct_payment'=>false,
            'listing_intents'=>self::listingBacklog(),
            'note'=>'Key presence and reported evidence do not independently verify a real payment, webhook delivery, vendor payout or refund.'];
    }
    private static function listingBacklog(): array {
        global $wpdb;$rows=$wpdb->get_results("SELECT id,data,updated FROM ".Store::table('records')." WHERE kind='listing' ORDER BY updated DESC LIMIT 30",ARRAY_A);$out=[];
        foreach($rows as $row){$d=json_decode($row['data'],true);$out[]=['intent_id'=>$row['id'],'handoff_state'=>$d['handoff_state']??'unknown','native_order_linked'=>!empty($d['order_id']),'creation_started'=>!empty($d['creation_started']),'updated_at'=>gmdate('c',(int)$row['updated'])];}return $out;
    }
    public static function render(): void {
        if(!current_user_can('manage_woocommerce'))return;$s=self::snapshot();
        echo '<h2>Native listing checkout and payment diagnostics</h2><p>Listing handoff: '.esc_html($s['listing_handoff']).'. Direct marketplace provider sessions are disabled. Native checkout and its gateway own charges and seller payouts. Diagnostic observations are separate from operator-reported proof.</p><p>Configure listing_handoff_enabled, listing_pricing_verified, listing_live_verified, listing_gateway_ids, listing_policy_url, listing_policy_version, listing_max_minor and reviewed listing_services in the validated JSON above. The normal service payment flags do not enable native listings. Never enter credentials in this JSON.</p><table class="widefat"><tr><th>Gateway</th><th>Environment</th><th>Observed state</th><th>Reported evidence</th><th>Last managed webhook success</th></tr>';
        foreach($s['gateways'] as $g)echo '<tr><td>'.esc_html($g['gateway_id']).'</td><td>'.esc_html($g['environment'].'; selected '.$g['selected_mode']).'</td><td>'.esc_html($g['observed_state']).'</td><td>'.esc_html($g['operator_reported_evidence']?($g['operator_reported_evidence']['state'].'; '.$g['operator_reported_evidence']['reference'].'; '.gmdate('c',$g['operator_reported_evidence']['checked_at'])):'Not verified').'</td><td>'.esc_html($g['last_success_at']?gmdate('c',$g['last_success_at']):'Not observed').'</td></tr>';
        echo '</table><h3>Webhook ownership</h3><ul>';foreach($s['webhook_owners'] as $entry)echo '<li>'.esc_html($entry['owner'].': '.($entry['destination']??'Inspect existing configuration').' — '.$entry['strategy']).'</li>';echo '</ul><h3>Recent native listing intents</h3><ul>';foreach($s['listing_intents'] as $intent)echo '<li>'.esc_html($intent['intent_id'].' — '.$intent['handoff_state'].'; order '.($intent['native_order_linked']?'linked':'not linked').'; '.$intent['updated_at']).'</li>';echo '</ul><p>'.esc_html($s['note']).'</p>';
    }
}
