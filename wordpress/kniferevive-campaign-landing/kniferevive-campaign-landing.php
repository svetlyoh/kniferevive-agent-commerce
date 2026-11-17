<?php
/**
 * Plugin Name: KnifeRevive Campaign Landing
 * Description: Read-only Next Find campaign page using approved photography and live WooCommerce listing status.
 * Version: 1.0.0
 * Requires PHP: 8.2
 * Requires at least: 6.7
 * Text Domain: kniferevive-campaign-landing
 */
defined('ABSPATH') || exit;
define('KRCL_FILE',__FILE__);
define('KRCL_VERSION','1.0.0');
foreach(['Campaign','Admin'] as $file)require_once __DIR__.'/includes/'.$file.'.php';
add_action('plugins_loaded',[\KnifeRevive\CampaignLanding\Campaign::class,'boot'],90);
add_action('admin_menu',[\KnifeRevive\CampaignLanding\Admin::class,'menu']);
add_action('admin_post_krcl_save',[\KnifeRevive\CampaignLanding\Admin::class,'save']);
// Installation does not create/publish pages, alter seller settings or enable measurement.
