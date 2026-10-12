<?php
defined('ABSPATH') || exit;
do_action('woocommerce_email_header',$settings['heading'],$email);
?>
<p><?php echo esc_html($settings['intro']); ?></p>
<?php
$summary=static function () use ($context) { ?>
<div class="kr-om-email-order">
<h2>Order details</h2>
<table class="kr-om-email-summary" role="presentation"><tr><td><strong>Order</strong></td><td>#<?php echo esc_html((string)$context['order_id']); ?><?php if ((string)$context['order_id']!==$context['order_number']) echo ' · '.esc_html($context['order_number']); ?></td></tr>
<tr><td><strong>Placed</strong></td><td><?php echo esc_html($context['placed']); ?> (<?php echo esc_html($context['timezone']); ?>)</td></tr>
<tr><td><strong>Seller</strong></td><td><?php echo esc_html($context['store_name']); ?></td></tr></table>
<ul><?php foreach ($context['items'] as $item) { ?><li><?php echo esc_html($item['name']); ?> — Qty <?php echo esc_html((string)$item['quantity']); ?></li><?php } ?></ul>
</div>
<?php };
$body=static function () use ($message,$settings) { ?>
<h2><?php echo esc_html($settings['message_heading']); ?></h2>
<div class="kr-om-email-message"><?php echo nl2br(esc_html($message),false); ?></div>
<?php };
if ($settings['layout']==='message-first') { $body(); $summary(); } else { $summary(); $body(); }
if ($screenshots) { ?>
<h2>Screenshots attached</h2><p>The images below are attached to this email as separate files.</p><ul><?php foreach ($screenshots as $i=>$shot) { ?><li><?php echo esc_html('Screenshot '.($i+1).' ('.strtoupper($shot['extension']).')'); ?></li><?php } ?></ul>
<?php } ?>
<p><a class="button kr-om-email-button" href="<?php echo esc_url($context['buyer_url']); ?>">View Your Order</a></p>
<?php if ($context['guest']) { ?><p>Checked out as a guest? Use your original order confirmation or contact your seller by replying to this email for help accessing your order.</p><?php } ?>
<?php if ($settings['footer_note']) { ?><p><?php echo esc_html($settings['footer_note']); ?></p><?php } ?>
<p><a href="<?php echo esc_url(home_url('/')); ?>">Visit <?php echo esc_html(get_bloginfo('name')); ?></a></p>
<?php do_action('woocommerce_email_footer',$email); ?>
