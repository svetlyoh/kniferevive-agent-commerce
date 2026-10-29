# Seller-to-buyer Quick Note / Order Messenger

Audit date: October 29, 2026 (America/Los_Angeles).


Active Order Messenger 1.0.4. Manual transactional messages from the seller; the message itself is free text and cannot be exhaustively listed as generic wording. The following are **source defaults**, overridable by saved owner settings.

**Default subject:** `[KnifeRevive] Order #{order_id} — Message from your seller`

Supported subject substitutions: `{order_id}`, `{order_number}`, `{store_name}`. The default uses internal order ID, which can differ from a displayed/custom order number.

**Heading:** A message about your order

**Intro:** Your seller has sent you an update about your KnifeRevive order.

**Standard sections/labels:** Order details; Order; Placed; Seller; {item_name} — Qty {quantity}; Message from your seller; {seller_written_message}.

**Attachment section:** Screenshots attached. “The images below are attached to this email as separate files.” Screenshot {index} ({extension}).

**Buttons/footer:** View Your Order; “Checked out as a guest? Use your original order confirmation or contact your seller by replying to this email for help accessing your order.”; {optional_owner_footer_note}; Visit {site_name}. The default footer note is empty. Summary-first/message-first is configurable.

The recipient is the registered purchaser's account email, or guest billing email. Seller/support Reply-To is configurable. Sharpening scope uses “KnifeRevive Sharpening” as store name.

Administrative-only diagnostic subject: `[KnifeRevive] Order Messenger diagnostic test`.

Administrative support-mailbox verification subject: `Verify KnifeRevive Order Messenger support Reply-To`. Body: “Confirm access to this support mailbox by opening the following link while signed in as a KnifeRevive administrator:” {verification_link}. These two are not routine buyer/seller order mail.


## Source and exact wording reference


### Source: [class-kr-order-message-admin.php](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/plugins/kniferevive-order-messenger/includes/class-kr-order-message-admin.php)

SHA-256: `5b271f8ab20df2cbcdceff01133a5a838e6b9c78fd8ba4321d639760b42b0ee9`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- A message about your order
- Your seller has sent you an update about your KnifeRevive order.
- Message from your seller
- [KnifeRevive] Order #{order_id} — Message from your seller

<details><summary>Exact template/renderer source, beginning at line 4</summary>

```php
    public static function defaults(): array {
        return ['enabled'=>1,'seller_access'=>1,'manager_access'=>0,'reply_to_mode'=>'seller','support_email'=>'','screenshots'=>3,'file_mb'=>4,'total_mb'=>8,'order_hour'=>5,'seller_day'=>20,'history'=>1,'retention_days'=>90,'theme'=>'inherit','layout'=>'summary-first','heading'=>'A message about your order','intro'=>'Your seller has sent you an update about your KnifeRevive order.','message_heading'=>'Message from your seller','subject'=>'[KnifeRevive] Order #{order_id} — Message from your seller','footer_note'=>'','accent'=>'#a7552a','background'=>'#f6f6f4','surface'=>'#ffffff','text'=>'#111111','font_size'=>16,'width'=>620,'logo_url'=>''];
    }
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Sign in as a marketplace administrator to verify this mailbox.
- Verification link expired or changed. Request a new one.
- Verify support Reply-To <input type="hidden" name="token" value="
- Confirm this mailbox

<details><summary>Exact template/renderer source, beginning at line 50</summary>

```php
    public static function verify_support(): void {
        if (!current_user_can('manage_options')) wp_die('Sign in as a marketplace administrator to verify this mailbox.',403);
        $token=sanitize_text_field(wp_unslash($_REQUEST['token']??'')); $pending=get_transient('kr_om_support_verification');
        if (!$pending || !hash_equals($pending['hash'],hash('sha256',$token)) || strtolower(self::settings()['support_email'])!==$pending['email']) wp_die('Verification link expired or changed. Request a new one.',400);
        if ($_SERVER['REQUEST_METHOD']!=='POST') {
            echo '<h1>Verify support Reply-To</h1><form method="post"><input type="hidden" name="action" value="kr_om_verify_support"><input type="hidden" name="token" value="'.esc_attr($token).'">'; wp_nonce_field('kr_om_verify_support'); echo '<button>Confirm this mailbox</button></form>'; return;
        }
        self::guard('kr_om_verify_support'); update_option('kr_om_verified_support',$pending['email'],false); delete_transient('kr_om_support_verification'); self::redirect('verified');
    }
```

</details>


### Source: [class-kr-order-context.php](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/plugins/kniferevive-order-messenger/includes/class-kr-order-context.php)

SHA-256: `0cdfc7d864fa82bfdb28339d58a601deb4801f7068546f7eb52f71fe7caed0e0`. This is a local source reference, not a byte-for-byte live-server export.

<details><summary>Exact template/renderer source, beginning at line 13</summary>

```php
    public static function subject(array $scope): string {
        $order=$scope['requested'];
        $template=KR_Order_Messenger_Admin::settings()['subject'];
        $text=strtr($template,['{order_id}'=>(string)$order->get_id(),'{order_number}'=>(string)$order->get_order_number(),'{store_name}'=>self::scope_store($scope)]);
        return mb_substr(str_replace(["\r","\n"],' ',wp_strip_all_tags($text)),0,240);
    }
```

</details>


### Source: [order-message.php](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/plugins/kniferevive-order-messenger/templates/emails/order-message.php)

SHA-256: `e7535b4a26d2adddd0718292086151eeda7a10ba57057b3aa54aee70e301cbdb`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- button kr-om-email-button

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
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

```

</details>


### Source: [order-message.php](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/plugins/kniferevive-order-messenger/templates/emails/plain/order-message.php)

SHA-256: `fc613bd2af5bd0acaf1dbaffd3ea8e6a8c344a889b55ece6fdfc27d8f1ae9b50`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Screenshots attached as separate image files:
- View Your Order:
- Guest checkout: use your original confirmation or reply to your seller for order-access help.

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
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

```

</details>
