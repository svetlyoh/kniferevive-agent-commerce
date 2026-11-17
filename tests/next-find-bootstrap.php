<?php
// Isolated database, no wp-config, no active production plugins, no external HTTP or customer mail.
$core='C:/Users/Svet/Local Sites/kniferevive/app/public';$qa=$core.'/.codex-tmp/next-find-qa';
mysqli_report(MYSQLI_REPORT_OFF);$db=new mysqli('127.0.0.1','root','','',10089);if($db->connect_error)throw new RuntimeException('Isolated MySQL10089 unavailable');$db->query('CREATE DATABASE IF NOT EXISTS krev_campaign_qa');$db->close();
define('ABSPATH',$core.'/');define('DB_NAME','krev_campaign_qa');define('DB_USER','root');define('DB_PASSWORD','');define('DB_HOST','127.0.0.1:10089');define('DB_CHARSET','utf8mb4');define('DB_COLLATE','');
define('WP_CONTENT_DIR',$qa.'/runtime');define('WP_CONTENT_URL','http://127.0.0.1:8799/content');define('WP_PLUGIN_DIR',$core.'/.codex-tmp/gcr-qa/vendor');define('WP_PLUGIN_URL','http://127.0.0.1:8799/plugins');define('WP_HOME','https://kniferevive.com');define('WP_SITEURL',WP_HOME);
define('WP_INSTALLING',getenv('KRCL_INSTALL')==='yes');define('WP_ENVIRONMENT_TYPE','local');define('DISABLE_WP_CRON',true);define('WP_DEBUG',true);define('WP_DEBUG_DISPLAY',false);define('WP_DEBUG_LOG',$qa.'/debug.log');define('AUTH_KEY','next-find-isolated-qa');define('AUTH_SALT','next-find-isolated-qa-salt');
@mkdir(WP_CONTENT_DIR,0777,true);$_SERVER['HTTP_HOST']='kniferevive.com';$_SERVER['REQUEST_METHOD']='GET';$_SERVER['REQUEST_URI']='/your-next-find/';$table_prefix='krcl_';
$GLOBALS['wp_filter']['pre_http_request'][1][]=['function'=>static fn()=>new WP_Error('blocked','QA blocks outbound HTTP.'),'accepted_args'=>0];
$GLOBALS['wp_filter']['pre_wp_mail'][1][]=['function'=>static fn()=>false,'accepted_args'=>0];
$GLOBALS['wp_filter']['pre_option_active_plugins'][1][]=['function'=>static fn()=>[],'accepted_args'=>0];
$GLOBALS['wp_filter']['pre_option_stylesheet'][1][]=['function'=>static fn()=>'twentytwentyfour','accepted_args'=>0];
$GLOBALS['wp_filter']['pre_option_template'][1][]=['function'=>static fn()=>'twentytwentyfour','accepted_args'=>0];
$GLOBALS['wp_filter']['muplugins_loaded'][1][]=['function'=>static function()use($core){register_theme_directory($core.'/wp-content/themes');if(getenv('KRCL_INSTALL')!=='yes'){require WP_PLUGIN_DIR.'/woocommerce/woocommerce.php';require dirname(__DIR__).'/wordpress/kniferevive-campaign-landing/kniferevive-campaign-landing.php';}},'accepted_args'=>0];
require ABSPATH.'wp-settings.php';require_once ABSPATH.'wp-admin/includes/upgrade.php';
if(DB_NAME!=='krev_campaign_qa'||$table_prefix!=='krcl_')throw new RuntimeException('Unsafe QA target');
if(getenv('KRCL_INSTALL')==='yes'){if(!is_blog_installed())wp_install('Next Find isolated QA','campaign-qa','qa@example.invalid',false,'',wp_generate_password(40));echo "Isolated WordPress installed.\n";return;}
if(!get_option('krcl_wc_installed')){WC_Install::install();update_option('krcl_wc_installed',true);}
update_option('timezone_string','America/Los_Angeles');update_option('woocommerce_currency','USD');
add_filter('plugins_url',static fn($url,$path,$plugin)=>wp_normalize_path($plugin)===wp_normalize_path(KRCL_FILE)?'http://127.0.0.1:8799/campaign-assets/'.ltrim($path,'/'):$url,10,3);
