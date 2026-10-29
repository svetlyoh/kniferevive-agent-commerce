# Native WooCommerce email subjects and wording

Local source version: 11.2.1. Live plugin version: 11.2.1. Native email enable switches, subject/heading overrides, additional content, locale and feature variants are saved settings; their current live values were not exported. The following are source defaults, not a claim that each is enabled or sent. `{site_title}` normally resolves to KnifeRevive but the configured live value must remain authoritative. Admin-only emails are labelled by their class, not assumed seller mail. Dokan/KnifeRevive filters and theme additions can augment these bodies.

Placeholder values such as %s, %1$s, {order_number}, {site_title}, {site_url}, {store_email} are dynamic. HTML and plain alternatives are retained in exact source appendices. UI/error text in reference fragments is not necessarily delivered content.


## Admin Payment Gateway Enabled


### Source: [class-wc-email-admin-payment-gateway-enabled.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-admin-payment-gateway-enabled.php)

SHA-256: `11b1a5922ec0d0acd8f63e422d219207e7178003786b45ac741f895237cdee4f`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- [{site_title}] Payment gateway "{gateway_title}" enabled

<details><summary>Exact template/renderer source, beginning at line 96</summary>

```php
		public function get_default_subject() {
			return __( '[{site_title}] Payment gateway "{gateway_title}" enabled', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Payment gateway "{gateway_title}" enabled

<details><summary>Exact template/renderer source, beginning at line 106</summary>

```php
		public function get_default_heading() {
			return __( 'Payment gateway "{gateway_title}" enabled', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- If this was intentional, you can safely ignore and delete this email.

<details><summary>Exact template/renderer source, beginning at line 318</summary>

```php
		public function get_default_additional_content() {
			return __( 'If this was intentional, you can safely ignore and delete this email.', 'woocommerce' );
		}
```

</details>


### Source: [admin-payment-gateway-enabled.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/admin-payment-gateway-enabled.php)

SHA-256: `371ad96dcecbdf8e9c76145ee0b83f027a321e35b90a9eef32ce5d5284f4e191`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- The payment gateway "%1$s" was just enabled on this site: %2$s
- If you did not enable this payment gateway, please log in to your site and consider disabling it here:
- This email has been sent to %s
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /** * Output the email footer. * * @hooked WC_Emails::email_footer() Output the email footer. * @since 10.6.0 */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Admin payment gateway enabled email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-payment-gateway-enabled.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\HTML
 * @version 10.7.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Output the email header.
 *
 * @hooked WC_Emails::email_header() Output the email header.
 * @since 10.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
	/* translators: %s: Username */
	printf( esc_html__( 'Howdy %s,', 'woocommerce' ), esc_html( $username ) );
?>
</p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<p>
<?php
	printf(
		/* translators: 1: gateway title, 2: site URL */
		esc_html__( 'The payment gateway "%1$s" was just enabled on this site: %2$s', 'woocommerce' ),
		esc_html( $gateway_title ),
		esc_html( home_url() )
	);
	?>
	</p>

<p><?php esc_html_e( 'If you did not enable this payment gateway, please log in to your site and consider disabling it here:', 'woocommerce' ); ?></p>
<p><a href="<?php echo esc_url( $gateway_settings_url ); ?>"><?php echo esc_url( $gateway_settings_url ); ?></a></p>

<p>
<?php
	/* translators: %s: admin email address */
	printf( esc_html__( 'This email has been sent to %s', 'woocommerce' ), esc_html( $admin_email ) );
?>
</p>

<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Output the email footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer.
 * @since 10.6.0
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [admin-payment-gateway-enabled.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/admin-payment-gateway-enabled.php)

SHA-256: `6a3051c4a6aed6bf790070602e44e07121708fa57cefcb64cda02c19ad96d57d`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- The payment gateway "%1$s" was just enabled on this site: %2$s
- If you did not enable this payment gateway, please log in to your site and consider disabling it here:
- This email has been sent to %s
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } /** * Filter the email footer text. * * @since 3.7.0 */ echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Admin payment gateway enabled email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/admin-payment-gateway-enabled.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.7.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Username */
echo sprintf( esc_html__( 'Howdy %s,', 'woocommerce' ), esc_html( $username ) ) . "\n\n";

echo sprintf(
	/* translators: 1: gateway title, 2: site URL */
	esc_html__( 'The payment gateway "%1$s" was just enabled on this site: %2$s', 'woocommerce' ),
	esc_html( $gateway_title ),
	esc_html( home_url() )
) . "\n\n";

echo esc_html__( 'If you did not enable this payment gateway, please log in to your site and consider disabling it here:', 'woocommerce' ) . "\n";
echo esc_url( $gateway_settings_url ) . "\n\n";

/* translators: %s: admin email address */
echo sprintf( esc_html__( 'This email has been sent to %s', 'woocommerce' ), esc_html( $admin_email ) ) . "\n\n";

echo "\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Filter the email footer text.
 *
 * @since 3.7.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Cancelled Order


### Source: [class-wc-email-cancelled-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-cancelled-order.php)

SHA-256: `45c822584e27f196647a4f21de28c787db1fb3fda0a0ae5a69f8fc74b8c75503`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- [{site_title}]: Order #{order_number} has been cancelled

<details><summary>Exact template/renderer source, beginning at line 71</summary>

```php
		public function get_default_subject() {
			return __( '[{site_title}]: Order #{order_number} has been cancelled', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Order cancelled: #{order_number}
- Order Cancelled: #{order_number}

<details><summary>Exact template/renderer source, beginning at line 81</summary>

```php
		public function get_default_heading() {
			return $this->email_improvements_enabled
				? __( 'Order cancelled: #{order_number}', 'woocommerce' )
				: __( 'Order Cancelled: #{order_number}', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Thanks for reading.

<details><summary>Exact template/renderer source, beginning at line 173</summary>

```php
		public function get_default_additional_content() {
			return __( 'Thanks for reading.', 'woocommerce' );
		}
```

</details>


### Source: [admin-cancelled-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/admin-cancelled-order.php)

SHA-256: `4aa61ea55382adaee325dce4cf1f22f574379538e049583415d6019b950852ae`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Notification to let you know — order #%1$s belonging to %2$s has been cancelled:
- We’re getting in touch to let you know that order #%1$s from %2$s has been cancelled.
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /* * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Admin cancelled order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-cancelled-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 9.8.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/*
 * @hooked WC_Emails::email_header() Output the email header
*/
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
echo $email_improvements_enabled ? '<div class="email-introduction">' : '';
/* translators: %1$s: Order number. %2$s: Customer full name */
$text = __( 'Notification to let you know &mdash; order #%1$s belonging to %2$s has been cancelled:', 'woocommerce' );
if ( $email_improvements_enabled ) {
	/* translators: %1$s: Order number. %2$s: Customer full name */
	$text = __( 'We’re getting in touch to let you know that order #%1$s from %2$s has been cancelled.', 'woocommerce' );
}
?>
<p><?php printf( esc_html( $text ), esc_html( $order->get_order_number() ), esc_html( $order->get_formatted_billing_full_name() ) ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [admin-cancelled-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/admin-cancelled-order.php)

SHA-256: `ee34e18c5c495a16a3977db2101dc58007aa88b16258e2964760e94fa3437e0e`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Notification to let you know — order #%1$s belonging to %2$s has been cancelled:
- We’re getting in touch to let you know that order #%1$s from %2$s has been cancelled.
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Admin cancelled order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/admin-cancelled-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.8.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %1$s: Order number. %2$s: Customer full name */
$text = __( 'Notification to let you know &mdash; order #%1$s belonging to %2$s has been cancelled:', 'woocommerce' );
if ( $email_improvements_enabled ) {
	/* translators: %1$s: Order number. %2$s: Customer full name */
	$text = __( 'We’re getting in touch to let you know that order #%1$s from %2$s has been cancelled.', 'woocommerce' );
}
echo sprintf( esc_html( $text ), esc_html( $order->get_order_number() ), esc_html( $order->get_formatted_billing_full_name() ) ) . "\n\n";

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer Cancelled Order


### Source: [class-wc-email-customer-cancelled-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-cancelled-order.php)

SHA-256: `f1a44afbcff0f60bd217b7c948d0a7eefbdc08526212a5af55ffcccd25266180`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- [{site_title}]: Your order #{order_number} has been cancelled

<details><summary>Exact template/renderer source, beginning at line 68</summary>

```php
		public function get_default_subject() {
			return __( '[{site_title}]: Your order #{order_number} has been cancelled', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Order cancelled: #{order_number}
- Order Cancelled: #{order_number}

<details><summary>Exact template/renderer source, beginning at line 78</summary>

```php
		public function get_default_heading() {
			return $this->email_improvements_enabled
				? __( 'Order cancelled: #{order_number}', 'woocommerce' )
				: __( 'Order Cancelled: #{order_number}', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We hope to see you again soon.

<details><summary>Exact template/renderer source, beginning at line 171</summary>

```php
		public function get_default_additional_content() {
			return __( 'We hope to see you again soon.', 'woocommerce' );
		}
```

</details>


### Source: [customer-cancelled-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-cancelled-order.php)

SHA-256: `f178591678d69d5f7b276e53abab93c00518649dcfd6e7a3babe70e72a401990`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We’re sorry to let you know that your order #%1$s has been cancelled.
- We’re getting in touch to let you know that your order #%1$s has been cancelled.
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /** * Hook: woocommerce_email_footer. * * @hooked WC_Emails::email_footer() Output the email footer * @since 2.5.0 */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer cancelled order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-cancelled-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Hook: woocommerce_email_header.
 *
 * @hooked WC_Emails::email_header() Output the email header
 * @since 2.5.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
echo $email_improvements_enabled ? '<div class="email-introduction">' : '';
/* translators: %1$s: Order number */
$text = __( 'We’re sorry to let you know that your order #%1$s has been cancelled.', 'woocommerce' );
if ( $email_improvements_enabled ) {
	/* translators: %1$s: Order number */
	$text = __( 'We’re getting in touch to let you know that your order #%1$s has been cancelled.', 'woocommerce' );
}
?>
<p><?php printf( esc_html( $text ), esc_html( $order->get_order_number() ) ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
/**
 * Hook: woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook: woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook: woocommerce_email_customer_details.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 * @since 2.5.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Hook: woocommerce_email_footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 * @since 2.5.0
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-cancelled-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-cancelled-order.php)

SHA-256: `261ead9754c68a8b39b02d65e3c36c99ae2d5c71e965a0fb2668ff21213c8ab7`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We’re sorry to let you know that your order #%1$s has been cancelled.
- We’re getting in touch to let you know that your order #%1$s has been cancelled.
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } /** * Hook: woocommerce_email_footer. * * @hooked WC_Emails::email_footer() Output the email footer * @since 2.5.0 */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer cancelled order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-cancelled-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.0.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %1$s: Order number */
$text = __( 'We’re sorry to let you know that your order #%1$s has been cancelled.', 'woocommerce' );
if ( $email_improvements_enabled ) {
	/* translators: %1$s: Order number */
	$text = __( 'We’re getting in touch to let you know that your order #%1$s has been cancelled.', 'woocommerce' );
}
echo sprintf( esc_html( $text ), esc_html( $order->get_order_number() ) ) . "\n\n";

/**
 * Hook: woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Hook: woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook: woocommerce_email_customer_details.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 * @since 2.5.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Hook: woocommerce_email_footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 * @since 2.5.0
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


## Customer Completed Order


### Source: [class-wc-email-customer-completed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-completed-order.php)

SHA-256: `def57602f28ddf135da6406e9039d82f28da7ff8b48d53090ddcaa46812e8769`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your order from {site_title} is on its way!
- Your {site_title} order is now complete

<details><summary>Exact template/renderer source, beginning at line 89</summary>

```php
		public function get_default_subject() {
			return $this->email_improvements_enabled
				? __( 'Your order from {site_title} is on its way!', 'woocommerce' )
				: __( 'Your {site_title} order is now complete', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Good things are heading your way!
- Thanks for shopping with us

<details><summary>Exact template/renderer source, beginning at line 101</summary>

```php
		public function get_default_heading() {
			return $this->email_improvements_enabled
				? __( 'Good things are heading your way!', 'woocommerce' )
				: __( 'Thanks for shopping with us', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Thanks again! If you need any help with your order, please contact us at {store_email}.
- Thanks for shopping with us.

<details><summary>Exact template/renderer source, beginning at line 151</summary>

```php
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'Thanks again! If you need any help with your order, please contact us at {store_email}.', 'woocommerce' )
				: __( 'Thanks for shopping with us.', 'woocommerce' );
		}
```

</details>


### Source: [customer-completed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-completed-order.php)

SHA-256: `5936eb2a37724e1ff74d2f18df8ecdcf0d74ded6690f2fa6da960aa0ffadf458`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We have finished processing your order.
- Here’s a reminder of what you’ve ordered:
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /* * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer completed order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi,', 'woocommerce' ) );
}
?>
</p>
<p><?php esc_html_e( 'We have finished processing your order.', 'woocommerce' ); ?></p>
<?php if ( $email_improvements_enabled ) : ?>
	<p><?php esc_html_e( 'Here’s a reminder of what you’ve ordered:', 'woocommerce' ); ?></p>
<?php endif; ?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-completed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-completed-order.php)

SHA-256: `99e20c283e5757afdabd604f6fe2a7b7856ef3ad4cdb8c64b89c462bc37c95e3`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We have finished processing your order.
- Here’s a reminder of what you’ve ordered:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer completed order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.9.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
echo esc_html__( 'We have finished processing your order.', 'woocommerce' ) . "\n\n";
if ( $email_improvements_enabled ) {
	echo esc_html__( 'Here’s a reminder of what you’ve ordered:', 'woocommerce' ) . "\n\n";
}

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer Failed Order


### Source: [class-wc-email-customer-failed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-failed-order.php)

SHA-256: `14faff5e92839ccb9cbf309bf881d98cfde28971b762677aa1c18816e885d510`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your order at {site_title} was unsuccessful

<details><summary>Exact template/renderer source, beginning at line 88</summary>

```php
		public function get_default_subject() {
			return __( 'Your order at {site_title} was unsuccessful', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Sorry, your order was unsuccessful

<details><summary>Exact template/renderer source, beginning at line 98</summary>

```php
		public function get_default_heading() {
			return __( 'Sorry, your order was unsuccessful', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- If you need any help with your order, please contact us at {store_email}.

<details><summary>Exact template/renderer source, beginning at line 148</summary>

```php
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'If you need any help with your order, please contact us at {store_email}.', 'woocommerce' )
				: '';
		}
```

</details>


### Source: [customer-failed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-failed-order.php)

SHA-256: `8b8a742afaa215a69ba3821eb1b023446adda15420f1dfb44a0a549f611842e5`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Unfortunately, we couldn't complete your order due to an issue with your payment method.
- If you'd like to continue with your purchase, please return to %s and try a different method of payment.
- Your order details are as follows:
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /** * Hook for the woocommerce_email_footer. * * @hooked WC_Emails::email_footer() Output the email footer * @since 3.7.0 */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer failed order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-failed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Hook for the woocommerce_email_header.
 *
 * @hooked WC_Emails::email_header() Output the email header
 * @since 3.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi,', 'woocommerce' ) );
}
?>
</p>
<p><?php esc_html_e( "Unfortunately, we couldn't complete your order due to an issue with your payment method.", 'woocommerce' ); ?></p>
<?php /* translators: %s: Site title */ ?>
<p><?php printf( esc_html__( "If you'd like to continue with your purchase, please return to %s and try a different method of payment.", 'woocommerce' ), esc_html( $blogname ) ); ?></p>
<p><?php esc_html_e( 'Your order details are as follows:', 'woocommerce' ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
/**
 * Hook for the woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 1.0.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 * @since 1.0.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Hook for the woocommerce_email_footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 * @since 3.7.0
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-failed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-failed-order.php)

SHA-256: `c605d1e44a3b48e79472c551756932f42c589f6465f8e2b630d68d4cb17f2500`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Unfortunately, we couldn't complete your order due to an issue with your payment method.
- If you'd like to continue with your purchase, please return to %s and try a different method of payment.
- Your order details are as follows:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } /** * Filters the email footer text. * * @since 3.7.0 */ echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer failed order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-failed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.8.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
echo esc_html__( "Unfortunately, we couldn't complete your order due to an issue with your payment method.", 'woocommerce' ) . "\n\n";
/* translators: %s: Site title */
echo sprintf( esc_html__( "If you'd like to continue with your purchase, please return to %s and try a different method of payment.", 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
echo esc_html__( 'Your order details are as follows:', 'woocommerce' ) . "\n\n";

/**
 * Hook for the woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Hook for the woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 1.0.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 * @since 1.0.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Filters the email footer text.
 *
 * @since 3.7.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer Fulfillment Created


### Source: [class-wc-email-customer-fulfillment-created.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-fulfillment-created.php)

SHA-256: `b783d53b85feca7c803eb21dbb0d8b19e1d78c38cb9b9b32fced677c798ac459`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Items from {site_title} order {order_number} have been fulfilled!
- An item from {site_title} order {order_number} has been fulfilled!

<details><summary>Exact template/renderer source, beginning at line 113</summary>

```php
		public function get_default_subject() {
			if ( $this->get_fulfillment_item_count() > 1 ) {
				return __( 'Items from {site_title} order {order_number} have been fulfilled!', 'woocommerce' );
			}
			return __( 'An item from {site_title} order {order_number} has been fulfilled!', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your items are on the way!
- Your item is on the way!

<details><summary>Exact template/renderer source, beginning at line 127</summary>

```php
		public function get_default_heading() {
			if ( $this->get_fulfillment_item_count() > 1 ) {
				return __( 'Your items are on the way!', 'woocommerce' );
			}
			return __( 'Your item is on the way!', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Please note that couriers may need some time to provide the latest shipping information.

<details><summary>Exact template/renderer source, beginning at line 201</summary>

```php
		public function get_default_additional_content() {
			return __( 'Please note that couriers may need some time to provide the latest shipping information.', 'woocommerce' );
		}
```

</details>


### Source: [customer-fulfillment-created.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-fulfillment-created.php)

SHA-256: `1366534d693c6ff9ca7505c243aa3b52f27f21c194ea98aecb193f3f8298d6dd`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Woo! Some items you purchased are being fulfilled. You can use the below information to track your shipment:
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo
- ; } /** * Hook for the woocommerce_email_footer. * * @param WC_Email $email The email object. * @since 2.5.0 * * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer fulfillment created email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-fulfillment-created.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\HTML
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook for the woocommerce_email_header.
 *
 * @param string $email_heading The email heading.
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<div class="email-introduction">
	<p><?php echo esc_html__( 'Woo! Some items you purchased are being fulfilled. You can use the below information to track your shipment:', 'woocommerce' ); ?></p>
</div>

<?php

/**
 * Hook for the woocommerce_email_fulfillment_details.
 *
 * @since 10.1.0
 * @param WC_Order $order The order object.
 * @param Fulfillment $fulfillment The fulfillment object.
 * @param bool $sent_to_admin Whether the email is sent to admin.
 * @param bool $plain_text Whether the email is plain text.
 * @param WC_Email $email The email object.
 *
 * @hooked WC_Emails::fulfillment_details() Shows the fulfillment details.
 */
do_action( 'woocommerce_email_fulfillment_details', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_fulfillment_meta.
 *
 * @param WC_Order $order The order object.
 * @param Fulfillment $fulfillment The fulfillment object.
 * @param bool $sent_to_admin Whether the email is sent to admin.
 * @param bool $plain_text Whether the email is plain text.
 * @param WC_Email $email The email object.
 * @since 10.1.0
 *
 * @hooked WC_Emails::order_meta() Shows fulfillment meta data.
 */
do_action( 'woocommerce_email_fulfillment_meta', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details.
 *
 * @param WC_Order $order The order object.
 * @param bool $sent_to_admin Whether the email is sent to admin.
 * @param bool $plain_text Whether the email is plain text.
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo '</td></tr></table>';
}

/**
 * Hook for the woocommerce_email_footer.
 *
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-fulfillment-created.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-fulfillment-created.php)

SHA-256: `20b0b542068f3f53ac04b1b7823cc62e1f18d065b269490b9290a86dd40a2f5b`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Woo! Some items you purchased are being fulfilled. You can use the below information to track your shipment:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } /** * Display the email footer text. * * @since 2.5.0 */ echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer fulfillment created email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-fulfillment-created.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__( 'Woo! Some items you purchased are being fulfilled. You can use the below information to track your shipment:', 'woocommerce' ) . "\n\n";

/**
 * Display fulfillment details.
 *
 * @hooked WC_Emails::fulfillment_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 *
 * @since 10.1.0
 */
do_action( 'woocommerce_email_fulfillment_details', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Display fulfillment meta data.
 *
 * @hooked WC_Emails::fulfillment_meta() Shows order meta data.
 *
 * @since 10.1.0
 */
do_action( 'woocommerce_email_fulfillment_meta', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

/**
 * Display customer details and email address.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 *
 * @since 2.5.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Display the email footer text.
 *
 * @since 2.5.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer Fulfillment Deleted


### Source: [class-wc-email-customer-fulfillment-deleted.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-fulfillment-deleted.php)

SHA-256: `d944f379e1bd79bda89c702d93b219211b94dbda5976208036e88bcb7ef00b57`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- A shipment from {site_title} order {order_number} has been cancelled

<details><summary>Exact template/renderer source, beginning at line 93</summary>

```php
		public function get_default_subject() {
			return __( 'A shipment from {site_title} order {order_number} has been cancelled', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- One of your shipments has been removed

<details><summary>Exact template/renderer source, beginning at line 103</summary>

```php
		public function get_default_heading() {
			return __( 'One of your shipments has been removed', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- If you have any questions or notice anything unexpected, feel free to reach out to our support team through your account or reply to this email.

<details><summary>Exact template/renderer source, beginning at line 174</summary>

```php
		public function get_default_additional_content() {
			return __( 'If you have any questions or notice anything unexpected, feel free to reach out to our support team through your account or reply to this email.', 'woocommerce' );
		}
```

</details>


### Source: [customer-fulfillment-deleted.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-fulfillment-deleted.php)

SHA-256: `e3f3feadb9b2a8d5669d304c9e9f1cf456ca8e5ce33f969a2cc1e682c7737327`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We wanted to let you know that one of the previously fulfilled shipments from your order has been removed from our system. This may have been due to a correction or an update in our fulfillment records. Don’t worry — this won’t affect any items you’ve already received.
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo
- ; } /** * Hook for the woocommerce_email_footer. * * @param WC_Email $email The email object. * @since 2.5.0 * * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer fulfillment deleted email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-fulfillment-deleted.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\HTML
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook for the woocommerce_email_header.
 *
 * @param string $email_heading The email heading.
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<div class="email-introduction">
	<p><?php echo esc_html__( 'We wanted to let you know that one of the previously fulfilled shipments from your order has been removed from our system. This may have been due to a correction or an update in our fulfillment records. Don’t worry — this won’t affect any items you’ve already received.', 'woocommerce' ); ?></p>
</div>

<?php

/**
 * Hook for the woocommerce_email_fulfillment_details.
 *
 * @since 10.1.0
 * @param WC_Order $order The order object.
 * @param Fulfillment $fulfillment The fulfillment object.
 * @param bool $sent_to_admin Whether the email is sent to admin.
 * @param bool $plain_text Whether the email is plain text.
 * @param WC_Email $email The email object.
 *
 * @hooked WC_Emails::fulfillment_details() Shows the fulfillment details.
 */
do_action( 'woocommerce_email_fulfillment_details', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_fulfillment_meta.
 *
 * @param WC_Order $order The order object.
 * @param Fulfillment $fulfillment The fulfillment object.
 * @param bool $sent_to_admin Whether the email is sent to admin.
 * @param bool $plain_text Whether the email is plain text.
 * @param WC_Email $email The email object.
 * @since 10.1.0
 *
 * @hooked WC_Emails::order_meta() Shows fulfillment meta data.
 */
do_action( 'woocommerce_email_fulfillment_meta', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details.
 *
 * @param WC_Order $order The order object.
 * @param bool $sent_to_admin Whether the email is sent to admin.
 * @param bool $plain_text Whether the email is plain text.
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo '</td></tr></table>';
}

/**
 * Hook for the woocommerce_email_footer.
 *
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-fulfillment-deleted.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-fulfillment-deleted.php)

SHA-256: `f3688253ab9e2e38dbc326da423a58223a9b96fb4971929e9ba5dd26ac80892f`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We wanted to let you know that one of the previously fulfilled shipments from your order has been removed from our system. This may have been due to a correction or an update in our fulfillment records. Don’t worry — this won’t affect any items you’ve already received.
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } /** * Display the email footer text. * * @since 2.5.0 */ echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer fulfillment deleted email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-fulfillment-deleted.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__( 'We wanted to let you know that one of the previously fulfilled shipments from your order has been removed from our system. This may have been due to a correction or an update in our fulfillment records. Don’t worry — this won’t affect any items you’ve already received.', 'woocommerce' ) . "\n\n";

/**
 * Display fulfillment details.
 *
 * @hooked WC_Emails::fulfillment_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 *
 * @since 10.1.0
 */
do_action( 'woocommerce_email_fulfillment_details', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Display fulfillment meta data.
 *
 * @hooked WC_Emails::fulfillment_meta() Shows order meta data.
 *
 * @since 10.1.0
 */
do_action( 'woocommerce_email_fulfillment_meta', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

/**
 * Display customer details and email address.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 *
 * @since 2.5.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Display the email footer text.
 *
 * @since 2.5.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer Fulfillment Updated


### Source: [class-wc-email-customer-fulfillment-updated.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-fulfillment-updated.php)

SHA-256: `a4d3757f6ad4a131af2b894c6bd2450861be1a58930ee3f275bfdeee97e39a2a`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- A shipment from {site_title} order {order_number} has been updated

<details><summary>Exact template/renderer source, beginning at line 102</summary>

```php
		public function get_default_subject() {
			return __( 'A shipment from {site_title} order {order_number} has been updated', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your shipment has been updated

<details><summary>Exact template/renderer source, beginning at line 112</summary>

```php
		public function get_default_heading() {
			return __( 'Your shipment has been updated', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- If anything looks off or you have questions, feel free to contact our support team.

<details><summary>Exact template/renderer source, beginning at line 186</summary>

```php
		public function get_default_additional_content() {
			return __( 'If anything looks off or you have questions, feel free to contact our support team.', 'woocommerce' );
		}
```

</details>


### Source: [customer-fulfillment-updated.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-fulfillment-updated.php)

SHA-256: `92d90d09a3531337e64727c1d9c0cd1d42a35fc3131a2bdd56447a79c9af0fa8`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Some details of your shipment have recently been updated. This may include tracking information, item contents, or delivery status.
- Here’s the latest info we have:
- Note from the store:
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo
- ; } /** * Hook for the woocommerce_email_footer. * * @param WC_Email $email The email object. * @since 2.5.0 * * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer fulfillment updated email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-fulfillment-updated.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\HTML
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hook for the woocommerce_email_header.
 *
 * @param string $email_heading The email heading.
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<div class="email-introduction">
	<p><?php echo esc_html__( 'Some details of your shipment have recently been updated. This may include tracking information, item contents, or delivery status.', 'woocommerce' ); ?></p>
	<p><?php echo esc_html__( 'Here’s the latest info we have:', 'woocommerce' ); ?></p>
</div>

<?php
$customer_note_text = is_scalar( $customer_note ?? null ) ? trim( (string) $customer_note ) : '';
if ( '' !== $customer_note_text ) :
	?>
<p><strong><?php echo esc_html__( 'Note from the store:', 'woocommerce' ); ?></strong></p>
<blockquote><?php echo wp_kses_post( wpautop( wptexturize( $customer_note_text ) ) ); ?></blockquote>
<?php endif; ?>

<?php

/**
 * Hook for the woocommerce_email_fulfillment_details.
 *
 * @since 10.1.0
 * @param WC_Order $order The order object.
 * @param Fulfillment $fulfillment The fulfillment object.
 * @param bool $sent_to_admin Whether the email is sent to admin.
 * @param bool $plain_text Whether the email is plain text.
 * @param WC_Email $email The email object.
 *
 * @hooked WC_Emails::fulfillment_details() Shows the fulfillment details.
 */
do_action( 'woocommerce_email_fulfillment_details', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_fulfillment_meta.
 *
 * @param WC_Order $order The order object.
 * @param Fulfillment $fulfillment The fulfillment object.
 * @param bool $sent_to_admin Whether the email is sent to admin.
 * @param bool $plain_text Whether the email is plain text.
 * @param WC_Email $email The email object.
 * @since 10.1.0
 *
 * @hooked WC_Emails::order_meta() Shows fulfillment meta data.
 */
do_action( 'woocommerce_email_fulfillment_meta', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details.
 *
 * @param WC_Order $order The order object.
 * @param bool $sent_to_admin Whether the email is sent to admin.
 * @param bool $plain_text Whether the email is plain text.
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo '</td></tr></table>';
}

/**
 * Hook for the woocommerce_email_footer.
 *
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-fulfillment-updated.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-fulfillment-updated.php)

SHA-256: `f03a5e4a3c86123690d5c1ed976e0e2dc43699824fefa26fca93f2defe5a26e9`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Some details of your shipment have recently been updated. This may include tracking information, item contents, or delivery status.
- Here’s the latest info we have:
- Note from the store:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } /** * Display the email footer text. * * @since 2.5.0 */ echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer fulfillment updated email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-fulfillment-updated.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo esc_html__( 'Some details of your shipment have recently been updated. This may include tracking information, item contents, or delivery status.', 'woocommerce' ) . "\n\n";
echo esc_html__( 'Here’s the latest info we have:', 'woocommerce' ) . "\n\n";

$customer_note_text = is_scalar( $customer_note ?? null ) ? trim( (string) $customer_note ) : '';
if ( '' !== $customer_note_text ) {
	echo esc_html__( 'Note from the store:', 'woocommerce' ) . "\n";
	echo esc_html( wp_strip_all_tags( wptexturize( $customer_note_text ) ) ) . "\n\n";
}

/**
 * Display fulfillment details.
 *
 * @hooked WC_Emails::fulfillment_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 *
 * @since 10.1.0
 */
do_action( 'woocommerce_email_fulfillment_details', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Display fulfillment meta data.
 *
 * @hooked WC_Emails::fulfillment_meta() Shows order meta data.
 *
 * @since 10.1.0
 */
do_action( 'woocommerce_email_fulfillment_meta', $order, $fulfillment, $sent_to_admin, $plain_text, $email );

/**
 * Display customer details and email address.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 *
 * @since 2.5.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Display the email footer text.
 *
 * @since 2.5.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer Invoice


### Source: [class-wc-email-customer-invoice.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-invoice.php)

SHA-256: `bdd1082db985d591f1c4224deaff0b09513bb28e04f168225399a533dd07dc92`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Details for order #{order_number} on {site_title}

<details><summary>Exact template/renderer source, beginning at line 70</summary>

```php
		public function get_default_subject( $paid = false ) {
			return __( 'Details for order #{order_number} on {site_title}', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Details for order #{order_number}

<details><summary>Exact template/renderer source, beginning at line 81</summary>

```php
		public function get_default_heading( $paid = false ) {
			return __( 'Details for order #{order_number}', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Thanks again! If you need any help with your order, please contact us at {store_email}.
- Thanks for using {site_url}!

<details><summary>Exact template/renderer source, beginning at line 131</summary>

```php
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'Thanks again! If you need any help with your order, please contact us at {store_email}.', 'woocommerce' )
				: __( 'Thanks for using {site_url}!', 'woocommerce' );
		}
```

</details>


### Source: [customer-invoice.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-invoice.php)

SHA-256: `8aef58ad80f4529aa80231428562c99d57ef7d237f2b9ba673c4d6fe0f2dd295`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Sorry, your order on %1$s was unsuccessful. Your order details are below, with a link to try your payment again: %2$s
- Pay for this order
- An order has been created for you on %1$s. Your order details are below, with a link to make payment when you’re ready: %2$s
- Here are the details of your order placed on %s:
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /** * Executes the email footer. * * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer order details email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-invoice.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 11.1.0
 */

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Executes the e-mail header.
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi,', 'woocommerce' ) );
}
?>
</p>
<?php if ( $order->needs_payment() ) { ?>
	<p>
	<?php
	if ( $order->has_status( OrderStatus::FAILED ) ) {
		printf(
			wp_kses(
			/* translators: %1$s Site title, %2$s Order pay link */
				__( 'Sorry, your order on %1$s was unsuccessful. Your order details are below, with a link to try your payment again: %2$s', 'woocommerce' ),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			),
			esc_html( get_bloginfo( 'name', 'display' ) ),
			'<a href="' . esc_url( $order->get_checkout_payment_url() ) . '" class="email-payment-link">' . esc_html__( 'Pay for this order', 'woocommerce' ) . '</a>'
		);
	} else {
		printf(
			wp_kses(
			/* translators: %1$s Site title, %2$s Order pay link */
				__( 'An order has been created for you on %1$s. Your order details are below, with a link to make payment when you’re ready: %2$s', 'woocommerce' ),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			),
			esc_html( get_bloginfo( 'name', 'display' ) ),
			'<a href="' . esc_url( $order->get_checkout_payment_url() ) . '" class="email-payment-link">' . esc_html__( 'Pay for this order', 'woocommerce' ) . '</a>'
		);
	}
	?>
	</p>

<?php } else { ?>
	<p>
	<?php
	/* translators: %s Order date */
	printf( esc_html__( 'Here are the details of your order placed on %s:', 'woocommerce' ), esc_html( wc_format_datetime( $order->get_date_created() ) ) );
	?>
	</p>
	<?php
}
?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php

/**
 * Hook for the woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Executes the email footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-invoice.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-invoice.php)

SHA-256: `0082b81106d537510e5880e22c15d597a458154a49829f220e70c8bce2830cdc`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Sorry, your order on %1$s was unsuccessful. Your order details are below, with a link to try your payment again: %2$s
- An order has been created for you on %1$s. Your order details are below, with a link to make payment when you’re ready: %2$s
- Here are the details of your order placed on %s:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer invoice email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-invoice.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.7.0
 */

// phpcs:disable Universal.WhiteSpace.PrecisionAlignment.Found, Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed -- Plain text output needs specific spacing without tabs

use Automattic\WooCommerce\Enums\OrderStatus;

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";

if ( $order->needs_payment() ) {
	if ( $order->has_status( OrderStatus::FAILED ) ) {
		echo wp_kses_post(
			sprintf(
				/* translators: %1$s: Site title, %2$s: Order pay link */
				__( 'Sorry, your order on %1$s was unsuccessful. Your order details are below, with a link to try your payment again: %2$s', 'woocommerce' ),
				esc_html( get_bloginfo( 'name', 'display' ) ),
				esc_url( $order->get_checkout_payment_url() )
			)
	    ) . "\n\n";
	} else {
		echo wp_kses_post(
			sprintf(
				/* translators: %1$s: Site title, %2$s: Order pay link */
				__( 'An order has been created for you on %1$s. Your order details are below, with a link to make payment when you’re ready: %2$s', 'woocommerce' ),
				esc_html( get_bloginfo( 'name', 'display' ) ),
				esc_url( $order->get_checkout_payment_url() )
			)
	    ) . "\n\n";
	}
} else {
	/* translators: %s: Order date */
	echo sprintf( esc_html__( 'Here are the details of your order placed on %s:', 'woocommerce' ), esc_html( wc_format_datetime( $order->get_date_created() ) ) ) . "\n\n";
}

/**
 * Hook for the woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Hook for the woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

// phpcs:enable Universal.WhiteSpace.PrecisionAlignment.Found, Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed

```

</details>


## Customer New Account


### Source: [class-wc-email-customer-new-account.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-new-account.php)

SHA-256: `f2f9d592a962e5d1570da4b9e05d2a1c5575f21d9e3aca32419471dd613319d8`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your {site_title} account has been created!

<details><summary>Exact template/renderer source, beginning at line 95</summary>

```php
		public function get_default_subject() {
			return __( 'Your {site_title} account has been created!', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Welcome to {site_title}

<details><summary>Exact template/renderer source, beginning at line 105</summary>

```php
		public function get_default_heading() {
			return __( 'Welcome to {site_title}', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We look forward to seeing you soon.

<details><summary>Exact template/renderer source, beginning at line 212</summary>

```php
		public function get_default_additional_content() {
			return __( 'We look forward to seeing you soon.', 'woocommerce' );
		}
```

</details>


### Source: [customer-new-account.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-new-account.php)

SHA-256: `9fb39e212b9cf50406fe6f247718d915daa6a0150e3f239dd62d42060ea985c8`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Thanks for creating an account on %s. Here’s a copy of your user details.
- hr hr-top
- Set your new password.
- hr hr-bottom
- You can access your account area to view orders, change your password, and more via the link below:
- My account
- Thanks for creating an account on %1$s. Your username is %2$s. You can access your account area to view orders, change your password, and more at: %3$s
- Click here to set your new password.
- email-additional-content email-additional-content-aligned
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /** * Fires to output the email footer. * * @hooked WC_Emails::email_footer() * * @since 3.7.0 */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer new account email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-new-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.9.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Fires to output the email header.
 *
 * @hooked WC_Emails::email_header()
 *
 * @since 3.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<?php /* translators: %s: Customer first name, or username if name is not available */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_display_name ) ); ?></p>
<?php if ( $email_improvements_enabled ) : ?>
	<?php /* translators: %s: Site title */ ?>
	<p><?php printf( esc_html__( 'Thanks for creating an account on %s. Here’s a copy of your user details.', 'woocommerce' ), esc_html( $blogname ) ); ?></p>
	<div class="hr hr-top"></div>
	<?php /* translators: %s: Username */ ?>
	<p><?php echo wp_kses( sprintf( __( 'Username: <b>%s</b>', 'woocommerce' ), esc_html( $user_login ) ), array( 'b' => array() ) ); ?></p>
	<?php if ( $password_generated && $set_password_url ) : ?>
		<?php // If the password has not been set by the user during the sign up process, send them a link to set a new password. ?>
		<p><a href="<?php echo esc_attr( $set_password_url ); ?>"><?php printf( esc_html__( 'Set your new password.', 'woocommerce' ) ); ?></a></p>
	<?php endif; ?>
	<div class="hr hr-bottom"></div>
	<p><?php echo esc_html__( 'You can access your account area to view orders, change your password, and more via the link below:', 'woocommerce' ); ?></p>
	<p><a href="<?php echo esc_attr( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php printf( esc_html__( 'My account', 'woocommerce' ) ); ?></a></p>
<?php else : ?>
	<?php /* translators: %1$s: Site title, %2$s: Username, %3$s: My account link */ ?>
	<p><?php printf( esc_html__( 'Thanks for creating an account on %1$s. Your username is %2$s. You can access your account area to view orders, change your password, and more at: %3$s', 'woocommerce' ), esc_html( $blogname ), '<strong>' . esc_html( $user_login ) . '</strong>', make_clickable( esc_url( wc_get_page_permalink( 'myaccount' ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<?php if ( $password_generated && $set_password_url ) : ?>
		<?php // If the password has not been set by the user during the sign up process, send them a link to set a new password. ?>
		<p><a href="<?php echo esc_attr( $set_password_url ); ?>"><?php printf( esc_html__( 'Click here to set your new password.', 'woocommerce' ) ); ?></a></p>
	<?php endif; ?>
<?php endif; ?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content email-additional-content-aligned">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Fires to output the email footer.
 *
 * @hooked WC_Emails::email_footer()
 *
 * @since 3.7.0
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-new-account.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-new-account.php)

SHA-256: `cd26ece922582fab0272399af0bf70d10b75dd885350d3d08649d27be3ec1fc6`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Thanks for creating an account on %s. Here’s a copy of your user details.
- You can access your account area to view orders, change your password, and more via the link below:
- Thanks for creating an account on %1$s. Your username is %2$s. You can access your account area to view orders, change your password, and more at: %3$s
- t set their password during sign-up. if ( $password_generated && $set_password_url ) { /* translators: URL follows */ echo esc_html__(
- ) . " "; echo esc_html( $set_password_url ) . " "; } echo " ---------------------------------------- "; /** * Show user-defined additional content - this is set in each email

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer new account email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-new-account.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.9.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name, or username if name is not available */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_display_name ) ) . "\n\n";
if ( $email_improvements_enabled ) {
	/* translators: %s: Site title */
	echo sprintf( esc_html__( 'Thanks for creating an account on %s. Here’s a copy of your user details.', 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
	echo "----------------------------------------\n\n";
	/* translators: %s: Username */
	echo sprintf( esc_html__( 'Username: %s.', 'woocommerce' ), esc_html( $user_login ) ) . "\n\n";
	echo "----------------------------------------\n\n";
	echo esc_html__( 'You can access your account area to view orders, change your password, and more via the link below:', 'woocommerce' ) . "\n\n";
	echo esc_html( wc_get_page_permalink( 'myaccount' ) ) . "\n\n";
} else {
	/* translators: %1$s: Site title, %2$s: Username, %3$s: My account link */
	echo sprintf( esc_html__( 'Thanks for creating an account on %1$s. Your username is %2$s. You can access your account area to view orders, change your password, and more at: %3$s', 'woocommerce' ), esc_html( $blogname ), esc_html( $user_login ), esc_html( wc_get_page_permalink( 'myaccount' ) ) ) . "\n\n";
}

// Only send the set new password link if the user hasn't set their password during sign-up.
if ( $password_generated && $set_password_url ) {
	/* translators: URL follows */
	echo esc_html__( 'To set your password, visit the following address: ', 'woocommerce' ) . "\n\n";
	echo esc_html( $set_password_url ) . "\n\n";
}

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer Note


### Source: [class-wc-email-customer-note.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-note.php)

SHA-256: `579f6b5d7fd97b34d4791e3c7f2a9229c3b0e81777d0e4f23094e56d0b7dcddd`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- A note has been added to your order from {site_title}
- Note added to your {site_title} order from {order_date}

<details><summary>Exact template/renderer source, beginning at line 71</summary>

```php
		public function get_default_subject() {
			return $this->email_improvements_enabled
				? __( 'A note has been added to your order from {site_title}', 'woocommerce' )
				: __( 'Note added to your {site_title} order from {order_date}', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- A note has been added to your order

<details><summary>Exact template/renderer source, beginning at line 83</summary>

```php
		public function get_default_heading() {
			return __( 'A note has been added to your order', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Thanks again! If you need any help with your order, please contact us at {store_email}.
- Thanks for reading.

<details><summary>Exact template/renderer source, beginning at line 169</summary>

```php
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'Thanks again! If you need any help with your order, please contact us at {store_email}.', 'woocommerce' )
				: __( 'Thanks for reading.', 'woocommerce' );
		}
```

</details>


### Source: [customer-note.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-note.php)

SHA-256: `cca7be8d2153ccef7d7bacdca053d234b9e052129730854d8211200bc2be4c34`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- The following note has been added to your order:
- As a reminder, here are your order details:
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /* * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer note email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-note.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi,', 'woocommerce' ) );
}
?>
</p>
<p><?php esc_html_e( 'The following note has been added to your order:', 'woocommerce' ); ?></p>

<blockquote>
<?php
$safe_note = wc_wptexturize_order_note( $customer_note );
echo wpautop( make_clickable( $safe_note ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
</blockquote>

<p><?php esc_html_e( 'As a reminder, here are your order details:', 'woocommerce' ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-note.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-note.php)

SHA-256: `068f2d15f83ee74329db37f78916285507e095f26fe6ccd25173099ef2b2e4cc`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- The following note has been added to your order:
- As a reminder, here are your order details:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer note email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-note.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
echo esc_html__( 'The following note has been added to your order:', 'woocommerce' ) . "\n\n";

echo "----------\n\n";

echo wc_wptexturize_order_note( $customer_note ) . "\n\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

echo "----------\n\n";

echo esc_html__( 'As a reminder, here are your order details:', 'woocommerce' ) . "\n\n";

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer On Hold Order


### Source: [class-wc-email-customer-on-hold-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-on-hold-order.php)

SHA-256: `fed8ab80eb8f40f4cab58e59fbf7dbd9ed2fe4159bb64611aac0a5705de4aab1`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your order from {site_title} is on hold
- Your {site_title} order has been received!

<details><summary>Exact template/renderer source, beginning at line 66</summary>

```php
		public function get_default_subject() {
			if ( $this->block_email_editor_enabled ) {
				return __( 'Your order from {site_title} is on hold', 'woocommerce' );
			}
			return __( 'Your {site_title} order has been received!', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your order is on hold
- Thank you for your order

<details><summary>Exact template/renderer source, beginning at line 79</summary>

```php
		public function get_default_heading() {
			if ( $this->block_email_editor_enabled ) {
				return __( 'Your order is on hold', 'woocommerce' );
			}
			return __( 'Thank you for your order', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Thanks again! If you need any help with your order, please contact us at {store_email}.
- We look forward to fulfilling your order soon.

<details><summary>Exact template/renderer source, beginning at line 155</summary>

```php
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'Thanks again! If you need any help with your order, please contact us at {store_email}.', 'woocommerce' )
				: __( 'We look forward to fulfilling your order soon.', 'woocommerce' );
		}
```

</details>


### Source: [customer-on-hold-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-on-hold-order.php)

SHA-256: `681c067965920b256d84a4b4bac11e0aa0e1f635b64cd891991d43103ec2d922`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We’ve received your order and it’s currently on hold until we can confirm your payment has been processed.
- Here’s a reminder of what you’ve ordered:
- Thanks for your order. It’s on-hold until we confirm that payment has been received.
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /* * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer on-hold order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-on-hold-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi,', 'woocommerce' ) );
}
?>
</p>
<?php if ( $email_improvements_enabled ) : ?>
	<p><?php esc_html_e( 'We’ve received your order and it’s currently on hold until we can confirm your payment has been processed.', 'woocommerce' ); ?></p>
	<p><?php esc_html_e( 'Here’s a reminder of what you’ve ordered:', 'woocommerce' ); ?></p>
<?php else : ?>
	<p><?php esc_html_e( 'Thanks for your order. It’s on-hold until we confirm that payment has been received.', 'woocommerce' ); ?></p>
<?php endif; ?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-on-hold-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-on-hold-order.php)

SHA-256: `d04db3da634d55abdff1f4690243efdf74c22ee05046b85b9dac82fede12480f`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We’ve received your order and it’s currently on hold until we can confirm your payment has been processed.
- Here’s a reminder of what you’ve ordered:
- Thanks for your order. It’s on-hold until we confirm that payment has been received.
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer on-hold order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-on-hold-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.8.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
if ( $email_improvements_enabled ) {
	echo esc_html__( 'We’ve received your order and it’s currently on hold until we can confirm your payment has been processed.', 'woocommerce' ) . "\n\n";
	echo esc_html__( 'Here’s a reminder of what you’ve ordered:', 'woocommerce' ) . "\n\n";
} else {
	echo esc_html__( 'Thanks for your order. It’s on-hold until we confirm that payment has been received.', 'woocommerce' ) . "\n\n";
}

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer Order Withdrawal Requested


### Source: [class-wc-email-customer-order-withdrawal-requested.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-order-withdrawal-requested.php)

SHA-256: `7c9dd56f15a7f105d90487e7c83141cf89f99f782764d7b19be51ebc2e4709d5`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We received your withdrawal request

<details><summary>Exact template/renderer source, beginning at line 69</summary>

```php
		public function get_default_subject() {
			return __( 'We received your withdrawal request', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We received your withdrawal request

<details><summary>Exact template/renderer source, beginning at line 78</summary>

```php
		public function get_default_heading() {
			return __( 'We received your withdrawal request', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We will review your request and contact you about next steps, including any refund due.

<details><summary>Exact template/renderer source, beginning at line 87</summary>

```php
		public function get_default_additional_content() {
			return __( 'We will review your request and contact you about next steps, including any refund due.', 'woocommerce' );
		}
```

</details>


### Source: [customer-order-withdrawal-requested.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-order-withdrawal-requested.php)

SHA-256: `0a27c359230952bc4e496129224e9041286419a3c67c55bc9c079e6f40ee95dd`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We have received your request to withdraw from the order below.

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer order withdrawal request email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-order-withdrawal-requested.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 11.2.0
 *
 * @var string              $email_heading      Email heading.
 * @var string              $additional_content Additional content below the body.
 * @var array<string,string> $withdrawal_data    Withdrawal request data.
 * @var array<string,string> $detail_rows        Withdrawal request detail rows.
 * @var bool                $sent_to_admin      Whether sent to admin.
 * @var bool                $plain_text         Whether plain-text variant.
 * @var \WC_Email           $email              Email object.
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Fires to output the email header.
 *
 * @hooked WC_Emails::email_header()
 * @since 3.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p><?php esc_html_e( 'We have received your request to withdraw from the order below.', 'woocommerce' ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<ul>
	<?php foreach ( $detail_rows as $label => $value ) : ?>
		<li><strong><?php echo esc_html( $label ); ?>:</strong> <?php echo nl2br( esc_html( $value ) ); ?></li>
	<?php endforeach; ?>
</ul>

<?php
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content email-additional-content-aligned">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Fires to output the email footer.
 *
 * @hooked WC_Emails::email_footer()
 * @since 3.7.0
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-order-withdrawal-requested.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-order-withdrawal-requested.php)

SHA-256: `0e4e71af9c5c8b9acdf560d208816111325503c4cf5c7cc3304e8e2bf9e4dde6`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We have received your request to withdraw from the order below.

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer order withdrawal request email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-order-withdrawal-requested.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 11.2.0
 *
 * @var string              $email_heading      Email heading.
 * @var string              $additional_content Additional content below the body.
 * @var array<string,string> $withdrawal_data    Withdrawal request data.
 * @var array<string,string> $detail_rows        Withdrawal request detail rows.
 * @var bool                $sent_to_admin      Whether sent to admin.
 * @var bool                $plain_text         Whether plain-text variant.
 * @var \WC_Email           $email              Email object.
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__( 'We have received your request to withdraw from the order below.', 'woocommerce' ) . "\n\n";

foreach ( $detail_rows as $label => $value ) {
	echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
}

echo "\n----------------------------------------\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Filter the email footer text.
 *
 * @param string $footer_text The footer text.
 * @since 2.3.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer Pos Completed Order


### Source: [class-wc-email-customer-pos-completed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-pos-completed-order.php)

SHA-256: `13fb55b279b332be6fb7b20187b89969c5ed7ebf47e3a55f4197c6d58be0f320`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your in-store purchase #%1$s at %2$s

<details><summary>Exact template/renderer source, beginning at line 102</summary>

```php
		public function get_default_subject() {
			$store_name = $this->get_pos_store_name();
			/* translators: %1$s: Order number, %2$s: Store name */
			return sprintf( __( 'Your in-store purchase #%1$s at %2$s', 'woocommerce' ), '{order_number}', esc_html( $store_name ) );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Thank you for your in-store purchase

<details><summary>Exact template/renderer source, beginning at line 114</summary>

```php
		public function get_default_heading() {
			return __( 'Thank you for your in-store purchase', 'woocommerce' );
		}
```

</details>


### Source: [customer-pos-completed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-pos-completed-order.php)

SHA-256: `4d777757532c2f0a10d2a156873865aa7985b1465b87b8fddaee8af631c1d13a`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Hi there,
- Here’s a reminder of what you’ve bought:
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /** * Show store information - store details are set in the Point of Sale settings. */ if ( ! empty( $pos_store_email ) \|\| ! empty( $pos_store_phone_number ) \|\| ! empty( $pos_store_address ) ) { echo
- ; } if ( ! empty( $pos_store_address ) ) { echo wp_kses_post( wpautop( wptexturize( $pos_store_address ) ) ); } echo
- ; } /** * Show refund & returns policy - this is set in the Point of Sale settings. */ if ( ! empty( $pos_refund_returns_policy ) ) { echo
- ; echo wp_kses_post( wpautop( wptexturize( $pos_refund_returns_policy ) ) ); echo
- ; } /** * Output the email footer * * @hooked WC_Email_Customer_POS_*::email_footer() Output the email footer * @since 10.0.0 */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer POS completed order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-pos-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Hook for the woocommerce_email_header.
 *
 * @hooked WC_Email_Customer_POS_*::email_header() Output the email header
 * @since 10.0.0
 */
do_action( 'woocommerce_pos_email_header', $email_heading, $email ); ?>

<div class="email-introduction">
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi there,', 'woocommerce' ) );
}
?>
</p>
<p><?php esc_html_e( 'Here’s a reminder of what you’ve bought:', 'woocommerce' ); ?></p>
</div>

<?php

/**
 * Show order details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show order meta data.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 1.0.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show customer details and email address.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 * @since 1.0.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Show store information - store details are set in the Point of Sale settings.
 */
if ( ! empty( $pos_store_email ) || ! empty( $pos_store_phone_number ) || ! empty( $pos_store_address ) ) {
	echo '<div class="pos-store-information">';
	if ( ! empty( $pos_store_name ) ) {
		echo '<h2>' . esc_html( $pos_store_name ) . '</h2>';
	}
	if ( ! empty( $pos_store_email ) ) {
		echo '<p>' . esc_html( $pos_store_email ) . '</p>';
	}
	if ( ! empty( $pos_store_phone_number ) ) {
		echo '<p>' . esc_html( $pos_store_phone_number ) . '</p>';
	}
	if ( ! empty( $pos_store_address ) ) {
		echo wp_kses_post( wpautop( wptexturize( $pos_store_address ) ) );
	}
	echo '</div>';
}

/**
 * Show refund & returns policy - this is set in the Point of Sale settings.
 */
if ( ! empty( $pos_refund_returns_policy ) ) {
	echo '<div class="refund-returns-policy">';
	echo '<h2>' . esc_html__( 'Refund & Returns Policy', 'woocommerce' ) . '</h2>';
	echo wp_kses_post( wpautop( wptexturize( $pos_refund_returns_policy ) ) );
	echo '</div>';
}

/**
 * Output the email footer
 *
 * @hooked WC_Email_Customer_POS_*::email_footer() Output the email footer
 * @since 10.0.0
 */
do_action( 'woocommerce_pos_email_footer', $email );

```

</details>


### Source: [customer-pos-completed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-pos-completed-order.php)

SHA-256: `ed6eef3fa3802c9d4ff3833963f7afded85c75f2267adfd81dc20baf1cbfc815`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Hi there,
- Here’s a reminder of what you’ve ordered:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } /** * Show store information - store details are set in the Point of Sale settings. */ if ( ! empty( $pos_store_email ) \|\| ! empty( $pos_store_phone_number ) \|\| ! empty( $pos_store_address ) ) { if ( ! empty( $pos_store_name ) ) { echo " " . esc_html( $pos_store_name ) . " "; } if ( ! empty( $pos_store_email ) ) { echo esc_html( $pos_store_email ) . " "; } if ( ! empty( $pos_store_phone_number ) ) { echo esc_html( $pos_store_phone_number ) . " "; } if ( ! empty( $pos_store_address ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $pos_store_address ) ) ) . " "; } echo " ---------------------------------------- "; } /** * Show refund & returns policy - this is set in the Point of Sale settings. */ if ( ! empty( $pos_refund_returns_policy ) ) { echo " " . esc_html__(
- ) . " "; echo esc_html( wp_strip_all_tags( wptexturize( $pos_refund_returns_policy ) ) ) . " "; echo " ---------------------------------------- "; } /** * Filter the email footer text. * * @since 4.0.0 */ echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer POS completed order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-pos-completed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.0.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$email                      = $email ?? null;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
} else {
	echo esc_html__( 'Hi there,', 'woocommerce' ) . "\n\n";
}
echo esc_html__( 'Here’s a reminder of what you’ve ordered:', 'woocommerce' ) . "\n\n";

/**
 * Show the order details table, generate and output structured data.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Show order meta data.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 1.0.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show customer details and email address.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 * @since 1.0.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Show store information - store details are set in the Point of Sale settings.
 */
if ( ! empty( $pos_store_email ) || ! empty( $pos_store_phone_number ) || ! empty( $pos_store_address ) ) {
	if ( ! empty( $pos_store_name ) ) {
		echo "\n" . esc_html( $pos_store_name ) . "\n\n";
	}
	if ( ! empty( $pos_store_email ) ) {
		echo esc_html( $pos_store_email ) . "\n";
	}
	if ( ! empty( $pos_store_phone_number ) ) {
		echo esc_html( $pos_store_phone_number ) . "\n";
	}
	if ( ! empty( $pos_store_address ) ) {
		echo esc_html( wp_strip_all_tags( wptexturize( $pos_store_address ) ) ) . "\n";
	}
	echo "\n----------------------------------------\n\n";
}

/**
 * Show refund & returns policy - this is set in the Point of Sale settings.
 */
if ( ! empty( $pos_refund_returns_policy ) ) {
	echo "\n" . esc_html__( 'Refund & Returns Policy', 'woocommerce' ) . "\n\n";
	echo esc_html( wp_strip_all_tags( wptexturize( $pos_refund_returns_policy ) ) ) . "\n";
	echo "\n----------------------------------------\n\n";
}

/**
 * Filter the email footer text.
 *
 * @since 4.0.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ), $email ) );

```

</details>


## Customer Pos Refunded Order


### Source: [class-wc-email-customer-pos-refunded-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-pos-refunded-order.php)

SHA-256: `5690fc519f53a3474da1ef0a9ccfa039d2a4b05d857c6f28fc0d26464bd722b2`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your %1$s order #%2$s has been partially refunded
- Your %1$s order #%2$s has been refunded

<details><summary>Exact template/renderer source, beginning at line 86</summary>

```php
		public function get_default_subject( $partial = false ) {
			$store_name = $this->get_pos_store_name();
			if ( $partial ) {
				/* translators: %1$s: Store name, %2$s: Order number */
				return sprintf( __( 'Your %1$s order #%2$s has been partially refunded', 'woocommerce' ), esc_html( $store_name ), '{order_number}' );
			} else {
				/* translators: %1$s: Store name, %2$s: Order number */
				return sprintf( __( 'Your %1$s order #%2$s has been refunded', 'woocommerce' ), esc_html( $store_name ), '{order_number}' );
			}
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Partial refund: Order {order_number}
- Partial Refund: Order {order_number}
- Order refunded: {order_number}
- Order Refunded: {order_number}

<details><summary>Exact template/renderer source, beginning at line 104</summary>

```php
		public function get_default_heading( $partial = false ) {
			if ( $partial ) {
				return $this->email_improvements_enabled
					? __( 'Partial refund: Order {order_number}', 'woocommerce' )
					: __( 'Partial Refund: Order {order_number}', 'woocommerce' );
			} else {
				return $this->email_improvements_enabled
					? __( 'Order refunded: {order_number}', 'woocommerce' )
					: __( 'Order Refunded: {order_number}', 'woocommerce' );
			}
		}
```

</details>


### Source: [customer-pos-refunded-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-pos-refunded-order.php)

SHA-256: `5b8d67bbfbcd4c5cf08eba4123073c4bb44a8bebcd68ec31a042090c39a851e5`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Hi there,
- Your order from %s has been partially refunded.
- Your order from %s has been refunded.
- Here’s a reminder of what you’ve bought:
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /** * Show store information - store details are set in the Point of Sale settings. */ if ( ! empty( $pos_store_email ) \|\| ! empty( $pos_store_phone_number ) \|\| ! empty( $pos_store_address ) ) { echo
- ; } if ( ! empty( $pos_store_address ) ) { echo wp_kses_post( wpautop( wptexturize( $pos_store_address ) ) ); } echo
- ; } /** * Show refund & returns policy - this is set in the Point of Sale settings. */ if ( ! empty( $pos_refund_returns_policy ) ) { echo
- ; echo wp_kses_post( wpautop( wptexturize( $pos_refund_returns_policy ) ) ); echo
- ; } /** * Hook for the woocommerce_email_footer. * * @hooked WC_Email_Customer_POS_*::email_footer() Output the email footer * @since 10.0.0 */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer POS refunded order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-pos-refunded-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Hook for the woocommerce_email_header.
 *
 * @hooked WC_Email_Customer_POS_*::email_header() Output the email header
 * @since 10.0.0
 */
do_action( 'woocommerce_pos_email_header', $email_heading, $email ); ?>

<div class="email-introduction">
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi there,', 'woocommerce' ) );
}
?>
</p>

<p>
<?php
if ( $partial_refund ) {
	/* translators: %s: Site title */
	echo sprintf( esc_html__( 'Your order from %s has been partially refunded.', 'woocommerce' ), esc_html( $pos_store_name ) ) . "\n\n";
} else {
	/* translators: %s: Site title */
	echo sprintf( esc_html__( 'Your order from %s has been refunded.', 'woocommerce' ), esc_html( $pos_store_name ) ) . "\n\n";
}
echo '</p><p>';
echo esc_html__( 'Here’s a reminder of what you’ve bought:', 'woocommerce' ) . "\n\n";
?>
</p>
</div>

<?php

/**
 * Show order details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show order meta data.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 1.0.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show customer details and email address.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 * @since 1.0.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Show store information - store details are set in the Point of Sale settings.
 */
if ( ! empty( $pos_store_email ) || ! empty( $pos_store_phone_number ) || ! empty( $pos_store_address ) ) {
	echo '<div class="pos-store-information">';
	if ( ! empty( $pos_store_name ) ) {
		echo '<h2>' . esc_html( $pos_store_name ) . '</h2>';
	}
	if ( ! empty( $pos_store_email ) ) {
		echo '<p>' . esc_html( $pos_store_email ) . '</p>';
	}
	if ( ! empty( $pos_store_phone_number ) ) {
		echo '<p>' . esc_html( $pos_store_phone_number ) . '</p>';
	}
	if ( ! empty( $pos_store_address ) ) {
		echo wp_kses_post( wpautop( wptexturize( $pos_store_address ) ) );
	}
	echo '</div>';
}

/**
 * Show refund & returns policy - this is set in the Point of Sale settings.
 */
if ( ! empty( $pos_refund_returns_policy ) ) {
	echo '<div class="refund-returns-policy">';
	echo '<h2>' . esc_html__( 'Refund & Returns Policy', 'woocommerce' ) . '</h2>';
	echo wp_kses_post( wpautop( wptexturize( $pos_refund_returns_policy ) ) );
	echo '</div>';
}

/**
 * Hook for the woocommerce_email_footer.
 *
 * @hooked WC_Email_Customer_POS_*::email_footer() Output the email footer
 * @since 10.0.0
*/
do_action( 'woocommerce_pos_email_footer', $email );

```

</details>


### Source: [customer-pos-refunded-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-pos-refunded-order.php)

SHA-256: `72165daca22ad2e064d06685affa2b609d13ef0d9736fcc0d18d0d185732117d`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Hi there,
- Your order from %s has been partially refunded.
- Your order from %s has been refunded.
- Here’s a reminder of what you’ve bought:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } /** * Show store information - store details are set in the Point of Sale settings. */ if ( ! empty( $pos_store_email ) \|\| ! empty( $pos_store_phone_number ) \|\| ! empty( $pos_store_address ) ) { if ( ! empty( $pos_store_name ) ) { echo " " . esc_html( $pos_store_name ) . " "; } if ( ! empty( $pos_store_email ) ) { echo esc_html( $pos_store_email ) . " "; } if ( ! empty( $pos_store_phone_number ) ) { echo esc_html( $pos_store_phone_number ) . " "; } if ( ! empty( $pos_store_address ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $pos_store_address ) ) ) . " "; } echo " ---------------------------------------- "; } /** * Show refund & returns policy - this is set in the Point of Sale settings. */ if ( ! empty( $pos_refund_returns_policy ) ) { echo " " . esc_html__(
- ) . " "; echo esc_html( wp_strip_all_tags( wptexturize( $pos_refund_returns_policy ) ) ) . " "; echo " ---------------------------------------- "; } /** * Filter the email footer text. * * @since 4.0.0 */ echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer POS refunded order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-pos-refunded-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.0.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$email                      = $email ?? null;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
} else {
	echo esc_html__( 'Hi there,', 'woocommerce' ) . "\n\n";
}
if ( $partial_refund ) {
	/* translators: %s: Site title */
	echo sprintf( esc_html__( 'Your order from %s has been partially refunded.', 'woocommerce' ), esc_html( $pos_store_name ) ) . "\n\n";
} else {
	/* translators: %s: Site title */
	echo sprintf( esc_html__( 'Your order from %s has been refunded.', 'woocommerce' ), esc_html( $pos_store_name ) ) . "\n\n";
}
echo esc_html__( 'Here’s a reminder of what you’ve bought:', 'woocommerce' ) . "\n\n";

/**
 * Show order details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/**
 * Show order meta data.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 1.0.0
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show customer details and email address.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 * @since 1.0.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Show store information - store details are set in the Point of Sale settings.
 */
if ( ! empty( $pos_store_email ) || ! empty( $pos_store_phone_number ) || ! empty( $pos_store_address ) ) {
	if ( ! empty( $pos_store_name ) ) {
		echo "\n" . esc_html( $pos_store_name ) . "\n\n";
	}
	if ( ! empty( $pos_store_email ) ) {
		echo esc_html( $pos_store_email ) . "\n";
	}
	if ( ! empty( $pos_store_phone_number ) ) {
		echo esc_html( $pos_store_phone_number ) . "\n";
	}
	if ( ! empty( $pos_store_address ) ) {
		echo esc_html( wp_strip_all_tags( wptexturize( $pos_store_address ) ) ) . "\n";
	}
	echo "\n----------------------------------------\n\n";
}

/**
 * Show refund & returns policy - this is set in the Point of Sale settings.
 */
if ( ! empty( $pos_refund_returns_policy ) ) {
	echo "\n" . esc_html__( 'Refund & Returns Policy', 'woocommerce' ) . "\n\n";
	echo esc_html( wp_strip_all_tags( wptexturize( $pos_refund_returns_policy ) ) ) . "\n";
	echo "\n----------------------------------------\n\n";
}

/**
 * Filter the email footer text.
 *
 * @since 4.0.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ), $email ) );

```

</details>


## Customer Processing Order


### Source: [class-wc-email-customer-processing-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-processing-order.php)

SHA-256: `0ca84ab9339e2a551ff76d272d196bfa78ed33262b1efc33f1c809f13c226eb1`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your {site_title} order has been received!

<details><summary>Exact template/renderer source, beginning at line 68</summary>

```php
		public function get_default_subject() {
			return __( 'Your {site_title} order has been received!', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Thank you for your order

<details><summary>Exact template/renderer source, beginning at line 78</summary>

```php
		public function get_default_heading() {
			return __( 'Thank you for your order', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Thanks again! If you need any help with your order, please contact us at {store_email}.
- Thanks for using {site_url}!

<details><summary>Exact template/renderer source, beginning at line 151</summary>

```php
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'Thanks again! If you need any help with your order, please contact us at {store_email}.', 'woocommerce' )
				: __( 'Thanks for using {site_url}!', 'woocommerce' );
		}
```

</details>


### Source: [customer-processing-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-processing-order.php)

SHA-256: `b02ea43e74ce4b88cbc65262d3637e98679f928a2c28b1f21caa857936bb284c`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Just to let you know — we’ve received your order, and it is now being processed.
- Here’s a reminder of what you’ve ordered:
- Just to let you know — we've received your order #%s, and it is now being processed:
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /* * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer processing order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-processing-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi,', 'woocommerce' ) );
}
?>
</p>
<?php if ( $email_improvements_enabled ) : ?>
	<p><?php esc_html_e( 'Just to let you know &mdash; we’ve received your order, and it is now being processed.', 'woocommerce' ); ?></p>
	<p><?php esc_html_e( 'Here’s a reminder of what you’ve ordered:', 'woocommerce' ); ?></p>
<?php else : ?>
	<?php /* translators: %s: Order number */ ?>
	<p><?php printf( esc_html__( 'Just to let you know &mdash; we\'ve received your order #%s, and it is now being processed:', 'woocommerce' ), esc_html( $order->get_order_number() ) ); ?></p>
<?php endif; ?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-processing-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-processing-order.php)

SHA-256: `de4c059bb03fa5e82c32d29a78eada746821f5edb3a18cd0c6bad6249e389ecc`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Just to let you know — we’ve received your order, and it is now being processed.
- Here’s a reminder of what you’ve ordered:
- Just to let you know — we've received your order #%s, and it is now being processed:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer processing order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-processing-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.9.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
if ( $email_improvements_enabled ) {
	echo esc_html__( 'Just to let you know &mdash; we’ve received your order, and it is now being processed.', 'woocommerce' ) . "\n\n";
	echo esc_html__( 'Here’s a reminder of what you’ve ordered:', 'woocommerce' ) . "\n\n";
} else {
	/* translators: %s: Order number */
	echo sprintf( esc_html__( 'Just to let you know &mdash; we\'ve received your order #%s, and it is now being processed:', 'woocommerce' ), esc_html( $order->get_order_number() ) ) . "\n\n";
}

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer Refunded Order


### Source: [class-wc-email-customer-refunded-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-refunded-order.php)

SHA-256: `19c3b367b3df42c1730cce901eb7cf34bd2a34333025538868d2c698d1035c93`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your {site_title} order #{order_number} has been partially refunded
- Your {site_title} order #{order_number} has been refunded

<details><summary>Exact template/renderer source, beginning at line 83</summary>

```php
		public function get_default_subject( $partial = false ) {
			if ( $partial ) {
				return __( 'Your {site_title} order #{order_number} has been partially refunded', 'woocommerce' );
			} else {
				return __( 'Your {site_title} order #{order_number} has been refunded', 'woocommerce' );
			}
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Partial refund: Order {order_number}
- Partial Refund: Order {order_number}
- Order refunded: {order_number}
- Order Refunded: {order_number}

<details><summary>Exact template/renderer source, beginning at line 98</summary>

```php
		public function get_default_heading( $partial = false ) {
			if ( $partial ) {
				return $this->email_improvements_enabled
					? __( 'Partial refund: Order {order_number}', 'woocommerce' )
					: __( 'Partial Refund: Order {order_number}', 'woocommerce' );
			} else {
				return $this->email_improvements_enabled
					? __( 'Order refunded: {order_number}', 'woocommerce' )
					: __( 'Order Refunded: {order_number}', 'woocommerce' );
			}
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- If you need any help with your order, please contact us at {store_email}.
- We hope to see you again soon.

<details><summary>Exact template/renderer source, beginning at line 285</summary>

```php
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'If you need any help with your order, please contact us at {store_email}.', 'woocommerce' )
				: __( 'We hope to see you again soon.', 'woocommerce' );
		}
```

</details>


### Source: [customer-refunded-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-refunded-order.php)

SHA-256: `effa6e4cb39b5030d6d62ad90cb82d4d813630de823e3901368d3811fe5f4b07`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your order from %s has been partially refunded.
- Your order from %s has been refunded.
- Here’s a reminder of what you’ve ordered:
- Your order on %s has been partially refunded. There are more details below for your reference:
- Your order on %s has been refunded. There are more details below for your reference:
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /* * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer refunded order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-refunded-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi,', 'woocommerce' ) );
}
?>
</p>

<p>
<?php
if ( $email_improvements_enabled ) {
	if ( $partial_refund ) {
		/* translators: %s: Site title */
		echo sprintf( esc_html__( 'Your order from %s has been partially refunded.', 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
	} else {
		/* translators: %s: Site title */
		echo sprintf( esc_html__( 'Your order from %s has been refunded.', 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
	}
	echo '</p><p>';
	echo esc_html__( 'Here’s a reminder of what you’ve ordered:', 'woocommerce' ) . "\n\n";

} elseif ( $partial_refund ) {
	/* translators: %s: Site title */
	printf( esc_html__( 'Your order on %s has been partially refunded. There are more details below for your reference:', 'woocommerce' ), esc_html( $blogname ) );
} else {
	/* translators: %s: Site title */
	printf( esc_html__( 'Your order on %s has been refunded. There are more details below for your reference:', 'woocommerce' ), esc_html( $blogname ) );
}
?>
</p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-refunded-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-refunded-order.php)

SHA-256: `0a777c0229c6cbf94898313b6a5cc597813539b74b57a7ed583b230be0509f7d`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your order from %s has been partially refunded.
- Your order from %s has been refunded.
- Here’s a reminder of what you’ve ordered:
- Your order on %s has been partially refunded. There are more details below for your reference:
- Your order on %s has been refunded. There are more details below for your reference:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer refunded order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-refunded-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.8.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
if ( $email_improvements_enabled ) {
	if ( $partial_refund ) {
		/* translators: %s: Site title */
		echo sprintf( esc_html__( 'Your order from %s has been partially refunded.', 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
	} else {
		/* translators: %s: Site title */
		echo sprintf( esc_html__( 'Your order from %s has been refunded.', 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
	}
	echo esc_html__( 'Here’s a reminder of what you’ve ordered:', 'woocommerce' ) . "\n\n";
} elseif ( $partial_refund ) {
	/* translators: %s: Site title */
	echo sprintf( esc_html__( 'Your order on %s has been partially refunded. There are more details below for your reference:', 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
} else {
	/* translators: %s: Site title */
	echo sprintf( esc_html__( 'Your order on %s has been refunded. There are more details below for your reference:', 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
}

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer Reset Password


### Source: [class-wc-email-customer-reset-password.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-reset-password.php)

SHA-256: `c419f21eeb19e8bc6583a71325f8ff0dd3788634d275ff8223584b28d2c525f0`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Reset your password for {site_title}
- Password Reset Request for {site_title}

<details><summary>Exact template/renderer source, beginning at line 97</summary>

```php
		public function get_default_subject() {
			return $this->email_improvements_enabled
				? __( 'Reset your password for {site_title}', 'woocommerce' )
				: __( 'Password Reset Request for {site_title}', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Reset your password
- Password Reset Request

<details><summary>Exact template/renderer source, beginning at line 109</summary>

```php
		public function get_default_heading() {
			return $this->email_improvements_enabled
				? __( 'Reset your password', 'woocommerce' )
				: __( 'Password Reset Request', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Thanks for reading.

<details><summary>Exact template/renderer source, beginning at line 219</summary>

```php
		public function get_default_additional_content() {
			return __( 'Thanks for reading.', 'woocommerce' );
		}
```

</details>


### Source: [customer-reset-password.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-reset-password.php)

SHA-256: `7084168e12a5bcf41fae8a7ca3a845cab04272fd9d8a6285a4a47b978940de5c`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Someone has requested a new password for the following account on %s:
- hr hr-top
- hr hr-bottom
- If you didn’t make this request, just ignore this email. If you’d like to proceed, reset your password via the link below:
- If you didn't make this request, just ignore this email. If you'd like to proceed:
- Reset your password
- Click here to reset your password
- email-additional-content email-additional-content-aligned
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer Reset Password email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-reset-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.9.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<?php /* translators: %s: Customer first name, or username if name is not available */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_display_name ) ); ?></p>
<?php /* translators: %s: Store name */ ?>
<p><?php printf( esc_html__( 'Someone has requested a new password for the following account on %s:', 'woocommerce' ), esc_html( $blogname ) ); ?></p>
<?php if ( $email_improvements_enabled ) : ?>
	<div class="hr hr-top"></div>
	<?php /* translators: %s: Username */ ?>
	<p><?php echo wp_kses( sprintf( __( 'Username: <b>%s</b>', 'woocommerce' ), esc_html( $user_login ) ), array( 'b' => array() ) ); ?></p>
	<div class="hr hr-bottom"></div>
	<p><?php esc_html_e( 'If you didn’t make this request, just ignore this email. If you’d like to proceed, reset your password via the link below:', 'woocommerce' ); ?></p>
<?php else : ?>
	<?php /* translators: %s: Customer username */ ?>
	<p><?php printf( esc_html__( 'Username: %s', 'woocommerce' ), esc_html( $user_login ) ); ?></p>
	<p><?php esc_html_e( 'If you didn\'t make this request, just ignore this email. If you\'d like to proceed:', 'woocommerce' ); ?></p>
<?php endif; ?>
<p>
	<a class="link" href="<?php echo esc_url( add_query_arg( array( 'key' => $reset_key, 'id' => $user_id, 'login' => rawurlencode( $user_login ) ), wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) ) ) ); ?>"><?php // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound ?>
		<?php
		if ( $email_improvements_enabled ) {
			esc_html_e( 'Reset your password', 'woocommerce' );
		} else {
			esc_html_e( 'Click here to reset your password', 'woocommerce' );
		}
		?>
	</a>
</p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content email-additional-content-aligned">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-reset-password.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-reset-password.php)

SHA-256: `e8540baffaa5e83fdc8db2d76b6fc51e6fe4c84fece4a3fd022ac0fac36e0ccf`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Someone has requested a new password for the following account on %s:
- If you didn’t make this request, just ignore this email. If you’d like to proceed, reset your password via the link below:
- If you didn't make this request, just ignore this email. If you'd like to proceed:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer Reset Password email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-reset-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.9.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer first name, or username if name is not available */
echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_display_name ) ) . "\n\n";
/* translators: %s: Store name */
echo sprintf( esc_html__( 'Someone has requested a new password for the following account on %s:', 'woocommerce' ), esc_html( $blogname ) ) . "\n\n";
if ( $email_improvements_enabled ) {
	echo "----------------------------------------\n\n";
}
/* translators: %s: Customer username */
echo sprintf( esc_html__( 'Username: %s', 'woocommerce' ), esc_html( $user_login ) ) . "\n\n";
if ( $email_improvements_enabled ) {
	echo "----------------------------------------\n\n";
	echo esc_html__( 'If you didn’t make this request, just ignore this email. If you’d like to proceed, reset your password via the link below:', 'woocommerce' ) . "\n\n";
} else {
	echo esc_html__( 'If you didn\'t make this request, just ignore this email. If you\'d like to proceed:', 'woocommerce' ) . "\n\n";
}
echo esc_url( add_query_arg( array( 'key' => $reset_key, 'id' => $user_id, 'login' => rawurlencode( $user_login ) ), wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) ) ) ) . "\n\n"; // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Customer Review Request


### Source: [class-wc-email-customer-review-request.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-customer-review-request.php)

SHA-256: `b3d22e9a5e83cd9dd2b332bf2d7bf4b545973655c6fcc3b1642611c3b234bb67`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- How was your order from {site_title}?

<details><summary>Exact template/renderer source, beginning at line 163</summary>

```php
		public function get_default_subject() {
			return __( 'How was your order from {site_title}?', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Rate your recent purchases

<details><summary>Exact template/renderer source, beginning at line 173</summary>

```php
		public function get_default_heading() {
			return __( 'Rate your recent purchases', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Thanks again for shopping with us. If you have any questions, reply to this email and we'll help out.

<details><summary>Exact template/renderer source, beginning at line 183</summary>

```php
		public function get_default_additional_content() {
			return __( 'Thanks again for shopping with us. If you have any questions, reply to this email and we\'ll help out.', 'woocommerce' );
		}
```

</details>


### Source: [customer-review-request.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/customer-review-request.php)

SHA-256: `5e529752180876c20a5affb759859b5737fe8b51083016a2d4e802636879fc59`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We’d love to know what you thought of the products you ordered. Your review helps other shoppers make better decisions and helps us improve.
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /** * Hook for the woocommerce_email_footer. * * @param WC_Email $email The email object. * @since 2.5.0 * * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer review request email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-review-request.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.8.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Hook for the woocommerce_email_header.
 *
 * @param string   $email_heading The email heading.
 * @param WC_Email $email         The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi,', 'woocommerce' ) );
}
?>
</p>

<p><?php esc_html_e( 'We’d love to know what you thought of the products you ordered. Your review helps other shoppers make better decisions and helps us improve.', 'woocommerce' ); ?></p>

<?php if ( ! empty( $review_order_url ) ) : ?>
<p>
	<a href="<?php echo esc_url( $review_order_url ); ?>"><?php esc_html_e( 'Leave a review', 'woocommerce' ); ?></a>
</p>
<?php endif; ?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php if ( $order instanceof WC_Order ) : ?>
<p style="font-size: 12px; line-height: 16px; color: #4d4d4d; margin-top: 16px;">
	<?php
	$date_created = $order->get_date_created();
	printf(
	/* translators: 1: order number, 2: order date */
		esc_html__( 'Order #%1$s (%2$s)', 'woocommerce' ),
		esc_html( $order->get_order_number() ),
		esc_html( $date_created ? wc_format_datetime( $date_created ) : '' )
	);
	?>
</p>
<?php endif; ?>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Hook for the woocommerce_email_footer.
 *
 * @param WC_Email $email The email object.
 * @since 2.5.0
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [customer-review-request.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/customer-review-request.php)

SHA-256: `70bb47350cb9849d4876ed9a2f3d0cdb96e04d79eb4af752d4e1736cf445d656`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We’d love to know what you thought of the products you ordered. Your review helps other shoppers make better decisions and helps us improve.
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } /** * Filter the email footer text. * * @param string $footer_text The footer text. * @since 2.3.0 */ echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Customer review request email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-review-request.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.8.0
 */

// phpcs:disable Universal.WhiteSpace.PrecisionAlignment.Found, Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed -- Plain text output needs specific spacing without tabs

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	echo sprintf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
} else {
	echo esc_html__( 'Hi,', 'woocommerce' ) . "\n\n";
}

echo esc_html__( 'We’d love to know what you thought of the products you ordered. Your review helps other shoppers make better decisions and helps us improve.', 'woocommerce' ) . "\n\n";

if ( ! empty( $review_order_url ) ) {
	echo esc_html__( 'Leave a review:', 'woocommerce' ) . "\n";
	echo esc_url( $review_order_url ) . "\n\n";
}

if ( $order instanceof WC_Order ) {
	$date_created = $order->get_date_created();
	printf(
		/* translators: 1: order number, 2: order date */
		esc_html__( 'Order #%1$s (%2$s)', 'woocommerce' ),
		esc_html( $order->get_order_number() ),
		esc_html( $date_created ? wc_format_datetime( $date_created ) : '' )
	);
	echo "\n\n";
}

echo "----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Filter the email footer text.
 *
 * @param string $footer_text The footer text.
 * @since 2.3.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

// phpcs:enable Universal.WhiteSpace.PrecisionAlignment.Found, Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed

```

</details>


## Failed Order


### Source: [class-wc-email-failed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-failed-order.php)

SHA-256: `39582ed9f70d7c1a171304a5f2d7788d79166e313d8af106f347cbbcd7f663aa`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- [{site_title}]: Order #{order_number} has failed

<details><summary>Exact template/renderer source, beginning at line 68</summary>

```php
		public function get_default_subject() {
			return __( '[{site_title}]: Order #{order_number} has failed', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Order failed: #{order_number}
- Order Failed: #{order_number}

<details><summary>Exact template/renderer source, beginning at line 78</summary>

```php
		public function get_default_heading() {
			return $this->email_improvements_enabled
				? __( 'Order failed: #{order_number}', 'woocommerce' )
				: __( 'Order Failed: #{order_number}', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- We hope they’ll be back soon! Read more about troubleshooting failed payments .
- Hopefully they’ll be back. Read more about troubleshooting failed payments .

<details><summary>Exact template/renderer source, beginning at line 170</summary>

```php
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'We hope they’ll be back soon! Read more about <a href="https://woocommerce.com/document/managing-orders/">troubleshooting failed payments</a>.', 'woocommerce' )
				: __( 'Hopefully they’ll be back. Read more about <a href="https://woocommerce.com/document/managing-orders/">troubleshooting failed payments</a>.', 'woocommerce' );
		}
```

</details>


### Source: [admin-failed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/admin-failed-order.php)

SHA-256: `6d7ad30ab38ce5069fb480214b0500ccbaf9d60fa3730fb848fe7a5e1c31cc06`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Payment for order #%1$s from %2$s has failed. The order was as follows:
- Unfortunately, the payment for order #%1$s from %2$s has failed. The order was as follows:
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /* * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Admin failed order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-failed-order.php
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
echo $email_improvements_enabled ? '<div class="email-introduction">' : '';
/* translators: %1$s: Order number. %2$s: Customer full name. */
$text = __( 'Payment for order #%1$s from %2$s has failed. The order was as follows:', 'woocommerce' );
if ( $email_improvements_enabled ) {
	/* translators: %1$s: Order number. %2$s: Customer full name. */
	$text = __( 'Unfortunately, the payment for order #%1$s from %2$s has failed. The order was as follows:', 'woocommerce' );
}
?>
<p><?php printf( esc_html( $text ), esc_html( $order->get_order_number() ), esc_html( $order->get_formatted_billing_full_name() ) ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
*/
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [admin-failed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/admin-failed-order.php)

SHA-256: `d8a981ba2dd363c6230149013cf5a0019cc59ccfb38b5065be526376d594f29b`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Payment for order #%1$s from %2$s has failed. The order was as follows:
- Unfortunately, the payment for order #%1$s from %2$s has failed. The order was as follows:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Admin failed order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/admin-failed-order.php
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 9.8.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %1$s: Order number. %2$s: Customer full name. */
$text = __( 'Payment for order #%1$s from %2$s has failed. The order was as follows:', 'woocommerce' );
if ( $email_improvements_enabled ) {
	/* translators: %1$s: Order number. %2$s: Customer full name. */
	$text = __( 'Unfortunately, the payment for order #%1$s from %2$s has failed. The order was as follows:', 'woocommerce' );
}
echo sprintf( esc_html( $text ), esc_html( $order->get_order_number() ), esc_html( $order->get_formatted_billing_full_name() ) ) . "\n\n";

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## New Order


### Source: [class-wc-email-new-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-new-order.php)

SHA-256: `1b5e8f18cf7669b2c6d7ce4bd0dce8e13976500996c8c62aab2d66abd26485ea`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- [{site_title}]: You've got a new order: #{order_number}
- [{site_title}]: New order #{order_number}

<details><summary>Exact template/renderer source, beginning at line 76</summary>

```php
		public function get_default_subject() {
			return $this->email_improvements_enabled
				? __( '[{site_title}]: You\'ve got a new order: #{order_number}', 'woocommerce' )
				: __( '[{site_title}]: New order #{order_number}', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- New order: #{order_number}
- New Order: #{order_number}

<details><summary>Exact template/renderer source, beginning at line 88</summary>

```php
		public function get_default_heading() {
			return $this->email_improvements_enabled
				? __( 'New order: #{order_number}', 'woocommerce' )
				: __( 'New Order: #{order_number}', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Congratulations on the sale!
- Congratulations on the sale.

<details><summary>Exact template/renderer source, beginning at line 199</summary>

```php
		public function get_default_additional_content() {
			return $this->email_improvements_enabled
				? __( 'Congratulations on the sale!', 'woocommerce' )
				: __( 'Congratulations on the sale.', 'woocommerce' );
		}
```

</details>


### Source: [admin-new-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/admin-new-order.php)

SHA-256: `2f7bb40a545c0b8ee90e352986a02320c20602e87e027c03ec2be29ad7fbbafc`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- You’ve received the following order from %s:
- You’ve received a new order from %s:
- ; echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); echo $email_improvements_enabled ?
- ; } /* * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Admin new order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-new-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\HTML
 * @version 10.4.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
echo $email_improvements_enabled ? '<div class="email-introduction">' : '';
/* translators: %s: Customer billing full name */
$text = __( 'You’ve received the following order from %s:', 'woocommerce' );
if ( $email_improvements_enabled ) {
	/* translators: %s: Customer billing full name */
	$text = __( 'You’ve received a new order from %s:', 'woocommerce' );
}
?>
<p><?php printf( esc_html( $text ), esc_html( $order->get_formatted_billing_full_name() ) ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [admin-new-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/admin-new-order.php)

SHA-256: `a485a633aa484afdef37a53f1e2f3ec050cc4d40b05842ed52c5b6335f4d4e62`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- You’ve received the following order from %s:
- You’ve received a new order from %s:
- s settings. */ if ( $additional_content ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Admin new order email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/admin-new-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 10.0.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/* translators: %s: Customer billing full name */
$text = __( 'You’ve received the following order from %s:', 'woocommerce' );
if ( $email_improvements_enabled ) {
	/* translators: %s: Customer billing full name */
	$text = __( 'You’ve received a new order from %s:', 'woocommerce' );
}
echo sprintf( esc_html( $text ), esc_html( $order->get_formatted_billing_full_name() ) ) . "\n\n";

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


## Order Withdrawal Requested


### Source: [class-wc-email-order-withdrawal-requested.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/includes/emails/class-wc-email-order-withdrawal-requested.php)

SHA-256: `03ac4166270c5523f6bb443ebccd3c1f98dbd8617b7e0d8351016da7d189ce2f`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- [{site_title}]: Order withdrawal request for order {order_number}

<details><summary>Exact template/renderer source, beginning at line 92</summary>

```php
		public function get_default_subject() {
			return __( '[{site_title}]: Order withdrawal request for order {order_number}', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Order withdrawal request received

<details><summary>Exact template/renderer source, beginning at line 101</summary>

```php
		public function get_default_heading() {
			return __( 'Order withdrawal request received', 'woocommerce' );
		}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Review the request details and contact the customer about next steps.

<details><summary>Exact template/renderer source, beginning at line 110</summary>

```php
		public function get_default_additional_content() {
			return __( 'Review the request details and contact the customer about next steps.', 'woocommerce' );
		}
```

</details>


### Source: [admin-order-withdrawal-requested.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/admin-order-withdrawal-requested.php)

SHA-256: `e16494f6929938b3ded4fe81461d113f62414d8668fe37cfb033ec9aa3ba8af7`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- A customer submitted an order withdrawal request.
- WooCommerce matched this request to an order and added an order note.
- WooCommerce could not match this request to an order automatically, so no order note was added.
- Matched order ID: %d
- View matched order

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Admin order withdrawal request email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/admin-order-withdrawal-requested.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 11.2.0
 *
 * @var string               $email_heading             Email heading.
 * @var string               $additional_content        Additional content below the body.
 * @var array<string,string> $withdrawal_data           Withdrawal request data.
 * @var array<string,string> $detail_rows               Withdrawal request detail rows.
 * @var \WC_Order|null       $matched_order             Matched order, if found.
 * @var bool                 $outside_withdrawal_window Whether the matched order is outside the withdrawal window.
 * @var string               $withdrawal_window_warning Withdrawal window warning message.
 * @var bool                 $sent_to_admin             Whether sent to admin.
 * @var bool                 $plain_text                Whether plain-text variant.
 * @var \WC_Email            $email                     Email object.
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Fires to output the email header.
 *
 * @hooked WC_Emails::email_header()
 * @since 3.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p><?php esc_html_e( 'A customer submitted an order withdrawal request.', 'woocommerce' ); ?></p>
<?php if ( $matched_order instanceof WC_Order ) : ?>
	<p><?php esc_html_e( 'WooCommerce matched this request to an order and added an order note.', 'woocommerce' ); ?></p>
<?php else : ?>
	<p><?php esc_html_e( 'WooCommerce could not match this request to an order automatically, so no order note was added.', 'woocommerce' ); ?></p>
<?php endif; ?>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<ul>
	<?php foreach ( $detail_rows as $label => $value ) : ?>
		<li><strong><?php echo esc_html( $label ); ?>:</strong> <?php echo nl2br( esc_html( $value ) ); ?></li>
	<?php endforeach; ?>
</ul>

<?php if ( $matched_order instanceof WC_Order ) : ?>
	<?php if ( $outside_withdrawal_window ) : ?>
		<p><?php echo esc_html( $withdrawal_window_warning ); ?></p>
	<?php endif; ?>
	<p>
		<?php
		printf(
			/* translators: %d: order ID. */
			esc_html__( 'Matched order ID: %d', 'woocommerce' ),
			absint( $matched_order->get_id() )
		);
		?>
	</p>
	<?php if ( '' !== $matched_order->get_edit_order_url() ) : ?>
		<p><a href="<?php echo esc_url( $matched_order->get_edit_order_url() ); ?>"><?php esc_html_e( 'View matched order', 'woocommerce' ); ?></a></p>
	<?php endif; ?>
<?php endif; ?>

<?php
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}

/**
 * Fires to output the email footer.
 *
 * @hooked WC_Emails::email_footer()
 * @since 3.7.0
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [admin-order-withdrawal-requested.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/tests/vendor-current/woocommerce/templates/emails/plain/admin-order-withdrawal-requested.php)

SHA-256: `68c06b4cbaf45ed49096dd76d892d0df1778d8d19fc3fd6635ebbb6ef87665ac`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- A customer submitted an order withdrawal request.
- WooCommerce matched this request to an order and added an order note.
- WooCommerce could not match this request to an order automatically, so no order note was added.
- Matched order ID: %d

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Admin order withdrawal request email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/admin-order-withdrawal-requested.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails\Plain
 * @version 11.2.0
 *
 * @var string               $email_heading             Email heading.
 * @var string               $additional_content        Additional content below the body.
 * @var array<string,string> $withdrawal_data           Withdrawal request data.
 * @var array<string,string> $detail_rows               Withdrawal request detail rows.
 * @var \WC_Order|null       $matched_order             Matched order, if found.
 * @var bool                 $outside_withdrawal_window Whether the matched order is outside the withdrawal window.
 * @var string               $withdrawal_window_warning Withdrawal window warning message.
 * @var bool                 $sent_to_admin             Whether sent to admin.
 * @var bool                 $plain_text                Whether plain-text variant.
 * @var \WC_Email            $email                     Email object.
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html__( 'A customer submitted an order withdrawal request.', 'woocommerce' ) . "\n\n";

if ( $matched_order instanceof WC_Order ) {
	echo esc_html__( 'WooCommerce matched this request to an order and added an order note.', 'woocommerce' ) . "\n\n";
} else {
	echo esc_html__( 'WooCommerce could not match this request to an order automatically, so no order note was added.', 'woocommerce' ) . "\n\n";
}

foreach ( $detail_rows as $label => $value ) {
	echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
}

if ( $matched_order instanceof WC_Order ) {
	echo "\n";

	if ( $outside_withdrawal_window ) {
		echo esc_html( $withdrawal_window_warning ) . "\n\n";
	}

	printf(
		/* translators: %d: order ID. */
		esc_html__( 'Matched order ID: %d', 'woocommerce' ),
		absint( $matched_order->get_id() )
	);
	echo "\n";

	if ( '' !== $matched_order->get_edit_order_url() ) {
		echo esc_url( $matched_order->get_edit_order_url() ) . "\n";
	}
}

echo "\n----------------------------------------\n\n";

if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
	echo "\n\n----------------------------------------\n\n";
}

/**
 * Filter the email footer text.
 *
 * @param string $footer_text The footer text.
 * @since 2.3.0
 */
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>
