<?php
/** CLI only. Uses its own loopback MySQL database; never loads the site's wp-config.php. */
if (!in_array(PHP_SAPI,['cli','cli-server'],true)) exit(1);
$root=$argv[1]??'';
if (!is_file($root.'/wp-settings.php')) throw new RuntimeException('Supply the WordPress core directory as the first argument.');
$testPort=(int)(getenv('KREV_TEST_DB_PORT')?:11019);
if(!in_array($testPort,[11019,11029],true))throw new RuntimeException('Use an approved loopback-only sandbox port.');
define('KREV_TEST_DB_HOST','127.0.0.1:'.$testPort);
$db=new mysqli('127.0.0.1','root','','',$testPort);
$db->query('CREATE DATABASE IF NOT EXISTS krev_agent_sandbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$db->close();
define('ABSPATH',rtrim(str_replace('\\','/',$root),'/').'/');
define('DB_NAME','krev_agent_sandbox'); define('DB_USER','root'); define('DB_PASSWORD',''); define('DB_HOST',KREV_TEST_DB_HOST);
define('DB_CHARSET','utf8mb4'); define('DB_COLLATE','');
define('WP_ENVIRONMENT_TYPE','local'); define('DISABLE_WP_CRON',true); define('WP_DEBUG',true); define('WP_DEBUG_DISPLAY',false);
define('WP_DISABLE_FATAL_ERROR_HANDLER',true);
define('WP_DEBUG_LOG',dirname(__DIR__).'/.runtime/wp-debug.log');
$setupTest=($argv[2]??'')==='setup';
define('WP_HOME',$setupTest?'https://kniferevive.com':'http://localhost:11080'); define('WP_SITEURL',WP_HOME);
define('WP_HTTP_BLOCK_EXTERNAL',true); define('WP_ACCESSIBLE_HOSTS','localhost,127.0.0.1');
define('WPMU_PLUGIN_DIR',dirname(__DIR__).'/.runtime/mu-plugins');
define('WPMU_PLUGIN_URL','http://localhost:11080/mu-plugins');
foreach (['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $key) define($key,'Synthetic test configuration only: '.$key);
if (!$setupTest) {
    define('KREV_AGENT_STRIPE_TEST_SECRET_KEY','sk_test_synthetic_fixture');
    define('KREV_AGENT_STRIPE_TEST_WEBHOOK_SECRET','synthetic-webhook-fixture-only');
}
if (PHP_SAPI==='cli') { $_SERVER['HTTP_HOST']='localhost:11080'; $_SERVER['REQUEST_URI']='/'; $_SERVER['HTTP_ORIGIN']=WP_HOME; }
$table_prefix='krev_sandbox_';
require_once ABSPATH.'wp-includes/plugin.php';
if(getenv('KREV_BOOKING_UI')==='1')add_filter('upload_dir',static function($u){$u['basedir']=dirname(__DIR__).'/.runtime/ui-media';$u['baseurl']=WP_HOME.'/fixture-media';$u['path']=$u['basedir'];$u['url']=$u['baseurl'];$u['subdir']='';return $u;});
add_filter('pre_wp_mail',static fn()=>true,PHP_INT_MAX);
if (getenv('KREV_LISTING_TEST_STACK')==='1') {
    add_filter('option_active_plugins',static function(){
        $plugins=['woocommerce/woocommerce.php','woocommerce-gateway-stripe/woocommerce-gateway-stripe.php','dokan-lite/dokan.php','dokan-pro/dokan-pro.php','kniferevive-stripe-connect/kniferevive-stripe-connect.php','kniferevive-seller-orders/kniferevive-seller-orders.php','kniferevive-seller-commissions/kniferevive-seller-commissions.php','kniferevive-return-policies/kniferevive-return-policies.php','conditional-extra-fees-for-woocommerce/conditional-fees-rule-woocommerce.php'];
        return getenv('KREV_SELLER_ORDERS_CANDIDATE')==='1'?array_values(array_diff($plugins,['kniferevive-seller-orders/kniferevive-seller-orders.php'])):$plugins;
    });
    if(getenv('KREV_SELLER_ORDERS_CANDIDATE')==='1')add_action('muplugins_loaded',static function(){
        if(DB_NAME!=='krev_agent_sandbox' || DB_HOST!==KREV_TEST_DB_HOST)throw new RuntimeException('Candidate plugin sandbox fence failed.');
        require dirname(__DIR__).'/.runtime/seller-booking-visibility-20261008/candidate/kniferevive-seller-orders/kniferevive-seller-orders.php';
    });
    add_filter('pre_option_woocommerce_stripe_settings',static fn()=>['enabled'=>'no','testmode'=>'yes']);
}
if (($argv[2]??'')==='install') define('WP_INSTALLING',true);
if(getenv('KREV_BOOKING_UI')==='1')add_action('plugins_loaded',static function(){require_once dirname(__DIR__).'/wordpress/kniferevive-agent-commerce/kniferevive-agent-commerce.php';},29);
require ABSPATH.'wp-settings.php';
add_filter('pre_wp_mail',static fn() => true);
add_filter('pre_http_request',static function ($pre) { return $pre!==false ? $pre : new WP_Error('test_outbound_blocked','External requests are disabled in the sandbox.'); },PHP_INT_MAX);
if (($argv[2]??'')==='install') {
    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    if (!is_blog_installed()) wp_install('Synthetic KnifeRevive test site','sandbox-admin','sandbox@example.invalid',false,'','Synthetic-test-password-only');
    update_option('active_plugins',['woocommerce/woocommerce.php']);
    update_option('woocommerce_currency','USD'); update_option('woocommerce_default_country','US:CA'); update_option('woocommerce_price_num_decimals',2);
    echo "Isolated WordPress database installed.\n"; exit;
}
if (!class_exists('WooCommerce')) throw new RuntimeException('Run sandbox-bootstrap.php with install first.');
if (!get_option('krev_agent_sandbox_woo_installed')) { WC_Install::install(); WC_Install::create_roles(); update_option('krev_agent_sandbox_woo_installed',true); }
if (getenv('KREV_LISTING_TEST_STACK')==='1') {
    if(DB_NAME!=='krev_agent_sandbox' || $wpdb->prefix!=='krev_sandbox_') throw new RuntimeException('Sandbox plugin-install fence failed.');
    $liteInstaller=new \WeDevs\Dokan\Install\Installer();$liteInstaller->create_tables();$liteInstaller->user_roles();
    // Use the installed schema builders, without opt-in, module activation or network calls.
    $proInstaller=new \WeDevs\DokanPro\Install\Installer();$proInstaller->create_shipping_tables();$proInstaller->create_shipping_tracking_table();
}
get_role('administrator')->add_cap('manage_woocommerce');
require_once dirname(__DIR__).'/wordpress/kniferevive-agent-commerce/kniferevive-agent-commerce.php';
if(getenv('KREV_BOOKING_UI')!=='1')\KnifeRevive\AgentCommerce\Plugin::boot();
\KnifeRevive\AgentCommerce\Store::install();
