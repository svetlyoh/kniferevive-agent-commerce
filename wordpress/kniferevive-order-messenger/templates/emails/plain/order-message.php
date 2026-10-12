<?php
defined('ABSPATH') || exit;
echo wp_strip_all_tags(get_bloginfo('name'))."\n".$settings['heading']."\n\n".$settings['intro']."\n\n";
$summary=static function () use ($context) {
    echo 'Order: #'.$context['order_id'].((string)$context['order_id']!==$context['order_number']?' · '.$context['order_number']:'')."\nPlaced: ".$context['placed'].' ('.$context['timezone'].")\nSeller: ".$context['store_name']."\nItems:\n";
    foreach ($context['items'] as $item) echo '- '.wp_strip_all_tags($item['name']).' — Qty '.$item['quantity']."\n";
    echo "\n";
};
$body=static function () use ($settings,$message) { echo $settings['message_heading']."\n".$message."\n\n"; };
if ($settings['layout']==='message-first') { $body(); $summary(); } else { $summary(); $body(); }
if ($screenshots) { echo "Screenshots attached as separate image files:\n"; foreach ($screenshots as $i=>$shot) echo '- Screenshot '.($i+1).' ('.strtoupper($shot['extension']).")\n"; echo "\n"; }
echo 'View Your Order: '.$context['buyer_url']."\n";
if ($context['guest']) echo "Guest checkout: use your original confirmation or reply to your seller for order-access help.\n";
if ($settings['footer_note']) echo $settings['footer_note']."\n";
echo "\nVisit ".get_bloginfo('name').': '.home_url('/')."\n\n";
echo wp_strip_all_tags((string)apply_filters('woocommerce_email_footer_text',get_option('woocommerce_email_footer_text')))."\n";
