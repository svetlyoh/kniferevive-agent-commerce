<?php
// Isolated loopback database. Never reads wp-config, installed site options or live credentials.
$core='C:/Users/Svet/Local Sites/kniferevive/app/public';$qa=$core.'/.codex-tmp/seller-contact-qa';
mysqli_report(MYSQLI_REPORT_OFF);$db=new mysqli('127.0.0.1','root','','',10089);if($db->connect_error)throw new RuntimeException('Start isolated MySQL on 10089.');$db->query('CREATE DATABASE IF NOT EXISTS krev_dispatch_qa');$db->close();
define('ABSPATH',$core.'/');define('DB_NAME','krev_dispatch_qa');define('DB_USER','root');define('DB_PASSWORD','');define('DB_HOST','127.0.0.1:10089');define('DB_CHARSET','utf8mb4');define('DB_COLLATE','');
define('WP_CONTENT_DIR',$qa.'/runtime');define('WP_CONTENT_URL','http://127.0.0.1:8803/content');define('WP_PLUGIN_DIR',$core.'/.codex-tmp/gcr-qa/vendor');define('WP_PLUGIN_URL','http://127.0.0.1:8803/plugins');define('WP_HOME','http://127.0.0.1:8803');define('WP_SITEURL',WP_HOME);
define('WP_INSTALLING',getenv('KRD_INSTALL')==='yes');define('WP_ENVIRONMENT_TYPE','local');define('KRD_TEST_MUTATIONS_ALLOWED',true);if(getenv('KRD_FAIL_STOCK')==='yes')define('KRD_TEST_FAIL_STOCK',true);
define('DISABLE_WP_CRON',true);define('WP_DEBUG',true);define('WP_DEBUG_DISPLAY',false);define('WP_DEBUG_LOG',$qa.'/debug.log');define('AUTH_KEY','dispatch-isolated-test');define('AUTH_SALT','dispatch-test-salt');define('LOGGED_IN_KEY','dispatch-login-test');define('LOGGED_IN_SALT','dispatch-login-salt');
@mkdir(WP_CONTENT_DIR,0777,true);$_SERVER['HTTP_HOST']='127.0.0.1:8803';$_SERVER['REQUEST_METHOD']=$_SERVER['REQUEST_METHOD']??'GET';$_SERVER['REQUEST_URI']=$_SERVER['REQUEST_URI']??'/';$table_prefix='krchat_';
$GLOBALS['krd_http_blocked']=0;$GLOBALS['krd_mail_blocked']=0;
$GLOBALS['wp_filter']['pre_http_request'][1][]=['function'=>static function(){$GLOBALS['krd_http_blocked']++;return new WP_Error('blocked','Isolated Dispatch QA blocks HTTP.');},'accepted_args'=>0];
$GLOBALS['wp_filter']['pre_wp_mail'][1][]=['function'=>static function(){$GLOBALS['krd_mail_blocked']++;return false;},'accepted_args'=>0];
$GLOBALS['wp_filter']['pre_option_active_plugins'][1][]=['function'=>static fn()=>[],'accepted_args'=>0];
if(getenv('KRD_THEME_QA')==='yes'){$GLOBALS['wp_filter']['pre_option_stylesheet'][1][]=['function'=>static fn()=>'kniferevive','accepted_args'=>0];$GLOBALS['wp_filter']['pre_option_template'][1][]=['function'=>static fn()=>'twentytwentyfour','accepted_args'=>0];$GLOBALS['wp_filter']['theme_root'][1][]=['function'=>static fn()=>$core.'/wp-content/themes','accepted_args'=>0];}
$GLOBALS['wp_filter']['pre_option_woocommerce_custom_orders_table_enabled'][1][]=['function'=>static fn()=>getenv('KRD_HPOS')==='yes'?'yes':'no','accepted_args'=>0];
$GLOBALS['wp_filter']['pre_option_woocommerce_custom_orders_table_data_sync_enabled'][1][]=['function'=>static fn()=>'no','accepted_args'=>0];
$GLOBALS['wp_filter']['muplugins_loaded'][1][]=['function'=>static function()use($core){register_theme_directory($core.'/wp-content/themes');if(getenv('KRD_INSTALL')!=='yes'){require WP_PLUGIN_DIR.'/woocommerce/woocommerce.php';require 'C:/Users/Svet/Documents/KnifeRevive_Documentation/my-money-2026-10-22/vendor-audit/dokan-lite/dokan.php';require dirname(__DIR__).'/wordpress/kniferevive-seller-contact/kniferevive-seller-contact.php';}},'accepted_args'=>0];
require ABSPATH.'wp-settings.php';require_once ABSPATH.'wp-admin/includes/upgrade.php';
if(DB_NAME!=='krev_dispatch_qa'||DB_HOST!=='127.0.0.1:10089')throw new RuntimeException('Unsafe QA database');
if(getenv('KRD_INSTALL')==='yes'){if(!is_blog_installed())wp_install('Dispatch isolated QA','dispatch-owner','owner@example.invalid',false,'',wp_generate_password(40));echo "Isolated WordPress installed.\n";return;}
if(!get_option('dispatch_wc_installed')){WC_Install::install();WC_Install::create_pages();update_option('dispatch_wc_installed',true);}
$installer=new WeDevs\Dokan\Install\Installer();$installer->create_tables();$installer->user_roles();\KnifeRevive\SellerContact\Analytics::install();
update_option('timezone_string','America/Los_Angeles');update_option('woocommerce_currency','USD');update_option('woocommerce_price_num_decimals',2);
