# Account/security, Dokan, newsletters and external providers

## WordPress account emails (buyers and sellers)

Core account/password mail can apply to either role. Common source subject patterns include `[{site_title}] Login Details`, `[{site_title}] Password Reset`, `[{site_title}] Password Changed`, and `[{site_title}] Email Changed`. Exact installed-source functions and conditionals are reproduced below. These are not all guaranteed enabled. WooCommerce can own the customer new-account/reset-password path instead; WordPress.com sign-in can use its own hosted mail. Do not interpret both sources as a duplicate for every registration.

The Ads Controller's dedicated operator creation calls `wp_new_user_notification(..., 'user')`; it uses WordPress's login/password setup copy, rather than a custom Google Ads password email. Creating an operator does not send a Google password.

## Dokan Lite

Live Lite 5.3.0 remains active; Dokan Pro is not installed. Local Dokan notification definitions/templates are inventoried below where present. Their live enable settings and replacement/suppression by KnifeRevive's native seller-order sender are not established here. No Pro email is represented as a currently active dependency.

## Passkeys

The onboarding emails explicitly encourage adding a passkey. A separate successful-passkey-registration notification was not established by this audit. Account recovery/security mail can still originate in WordPress/WooCommerce/WordPress.com. Never document a template as sent just because a security plugin is active.

## MailPoet and other campaigns — content audit gap

MailPoet 5.44.0 is active. Newsletter subjects, automations, subscriber confirmations, welcome-email customizations and scheduled campaigns can be stored in its database or hosted service. Their current authored copy/subscription state was not read in this source audit. This catalogue does not claim to contain every newsletter ever sent. Historical messages, saved code snippets and remotely configured transactional mail need a separate read-only configuration/mail-history export to close that gap. No customer mailbox, subscriber list or database with buyer data was exported.

## External providers

Google Customer Reviews survey emails are sent by Google after its own opt-in process. Stripe Express identity/payout/account emails, PayPal receipts and WordPress.com authentication messages are controlled by those providers. KnifeRevive's integration does not own their exact subjects/bodies. They are not included as invented KnifeRevive templates. WP Mail SMTP transports messages; it is not the author of every email. Its administrative delivery/security alerts are outside buyer/seller transactional copy.

## Additional active third-party senders

Native gateway/payment-plugin buyer emails and Dokan's source-defined WC_Email extensions are catalogued below when found. Source defaults can differ from saved production settings. A live plugin version match is not a file-hash verification.


### Source: [pluggable.php](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-includes/pluggable.php)

SHA-256: `3a2482a65b50d62ebae75a728be98b78985d3a3f5a3e007cb2d0c1338bf05777`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- New user registration on your site %s:
- [%s] New User Registration
- t sent a user notification. if (
- ) . "\r \r "; /* * Since some user login names end in a period, this could produce ambiguous URLs that * end in a period. To avoid the ambiguity, ensure that the login is not the last query * arg in the URL. If moving it to the end, a trailing period will need to be escaped. * * @see https://core.trac.wordpress.org/tickets/42957 */ $message .= network_site_url(
- => $user->user_email, /* translators: Login details notification email subject. %s: Site title. */
- , ); /** * Filters the contents of the new user notification email sent to the new user. * * @since 4.9.0 * * @param array $wp_new_user_notification_email { * Used to build wp_mail(). * * @type string $to The intended recipient - New user email address. * @type string $subject The subject of the email. * @type string $message The body of the email. * @type string $headers The headers of the email. * } * @param WP_User $user User object for new user. * @param string $blogname The site title. */ $wp_new_user_notification_email = apply_filters(

<details><summary>Exact template/renderer source, beginning at line 2276</summary>

```php
	function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
		if ( null !== $deprecated ) {
			_deprecated_argument( __FUNCTION__, '4.3.1' );
		}

		// Accepts only 'user', 'admin' , 'both' or default '' as $notify.
		if ( ! in_array( $notify, array( 'user', 'admin', 'both', '' ), true ) ) {
			return;
		}

		$user = get_userdata( $user_id );

		/*
		 * The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
		 * We want to reverse this for the plain text arena of emails.
		 */
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		/**
		 * Filters whether the admin is notified of a new user registration.
		 *
		 * @since 6.1.0
		 *
		 * @param bool    $send Whether to send the email. Default true.
		 * @param WP_User $user User object for new user.
		 */
		$send_notification_to_admin = apply_filters( 'wp_send_new_user_notification_to_admin', true, $user );

		if ( 'user' !== $notify && true === $send_notification_to_admin ) {

			$admin_user = get_user_by( 'email', get_option( 'admin_email' ) );

			if ( $admin_user ) {
				$switched_locale = switch_to_user_locale( $admin_user->ID );
			} else {
				$switched_locale = switch_to_locale( get_locale() );
			}

			/* translators: %s: Site title. */
			$message = sprintf( __( 'New user registration on your site %s:' ), $blogname ) . "\r\n\r\n";
			/* translators: %s: User login. */
			$message .= sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
			/* translators: %s: User email address. */
			$message .= sprintf( __( 'Email: %s' ), $user->user_email ) . "\r\n";

			$wp_new_user_notification_email_admin = array(
				'to'      => get_option( 'admin_email' ),
				/* translators: New user registration notification email subject. %s: Site title. */
				'subject' => __( '[%s] New User Registration' ),
				'message' => $message,
				'headers' => '',
			);

			/**
			 * Filters the contents of the new user notification email sent to the site admin.
			 *
			 * @since 4.9.0
			 *
			 * @param array   $wp_new_user_notification_email_admin {
			 *     Used to build wp_mail().
			 *
			 *     @type string $to      The intended recipient - site admin email address.
			 *     @type string $subject The subject of the email.
			 *     @type string $message The body of the email.
			 *     @type string $headers The headers of the email.
			 * }
			 * @param WP_User $user     User object for new user.
			 * @param string  $blogname The site title.
			 */
			$wp_new_user_notification_email_admin = apply_filters( 'wp_new_user_notification_email_admin', $wp_new_user_notification_email_admin, $user, $blogname );

			wp_mail(
				$wp_new_user_notification_email_admin['to'],
				wp_specialchars_decode( sprintf( $wp_new_user_notification_email_admin['subject'], $blogname ) ),
				$wp_new_user_notification_email_admin['message'],
				$wp_new_user_notification_email_admin['headers']
			);

			if ( $switched_locale ) {
				restore_previous_locale();
			}
		}

		/**
		 * Filters whether the user is notified of their new user registration.
		 *
		 * @since 6.1.0
		 *
		 * @param bool    $send Whether to send the email. Default true.
		 * @param WP_User $user User object for new user.
		 */
		$send_notification_to_user = apply_filters( 'wp_send_new_user_notification_to_user', true, $user );

		// `$deprecated` was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notification.
		if ( 'admin' === $notify || true !== $send_notification_to_user || ( empty( $deprecated ) && empty( $notify ) ) ) {
			return;
		}

		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			return;
		}

		$switched_locale = switch_to_user_locale( $user_id );

		$message = __( 'To set your password, visit the following address:' ) . "\r\n\r\n";

		/*
		 * Since some user login names end in a period, this could produce ambiguous URLs that
		 * end in a period. To avoid the ambiguity, ensure that the login is not the last query
		 * arg in the URL. If moving it to the end, a trailing period will need to be escaped.
		 *
		 * @see https://core.trac.wordpress.org/tickets/42957
		 */
		$message .= network_site_url( 'wp-login.php?login=' . rawurlencode( $user->user_login ) . "&key=$key&action=rp", 'login' ) . "\r\n";

		$wp_new_user_notification_email = array(
			'to'      => $user->user_email,
			/* translators: Login details notification email subject. %s: Site title. */
			'subject' => __( '[%s] Login Details' ),
			'message' => $message,
			'headers' => '',
		);

		/**
		 * Filters the contents of the new user notification email sent to the new user.
		 *
		 * @since 4.9.0
		 *
		 * @param array   $wp_new_user_notification_email {
		 *     Used to build wp_mail().
		 *
		 *     @type string $to      The intended recipient - New user email address.
		 *     @type string $subject The subject of the email.
		 *     @type string $message The body of the email.
		 *     @type string $headers The headers of the email.
		 * }
		 * @param WP_User $user     User object for new user.
		 * @param string  $blogname The site title.
		 */
		$wp_new_user_notification_email = apply_filters( 'wp_new_user_notification_email', $wp_new_user_notification_email, $user, $blogname );

		wp_mail(
			$wp_new_user_notification_email['to'],
			wp_specialchars_decode( sprintf( $wp_new_user_notification_email['subject'], $blogname ) ),
			$wp_new_user_notification_email['message'],
			$wp_new_user_notification_email['headers']
		);

		if ( $switched_locale ) {
			restore_previous_locale();
		}
	}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- s the admin whose password we
- Password changed for user: %s
- [%s] Password Changed

<details><summary>Exact template/renderer source, beginning at line 2197</summary>

```php
	function wp_password_change_notification( $user ) {
		/*
		 * Send a copy of password change notification to the admin,
		 * but check to see if it's the admin whose password we're changing, and skip this.
		 */
		if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) ) {

			$admin_user = get_user_by( 'email', get_option( 'admin_email' ) );

			if ( $admin_user ) {
				$switched_locale = switch_to_user_locale( $admin_user->ID );
			} else {
				$switched_locale = switch_to_locale( get_locale() );
			}

			/* translators: %s: User name. */
			$message = sprintf( __( 'Password changed for user: %s' ), $user->user_login ) . "\r\n";
			/*
			 * The blogname option is escaped with esc_html() on the way into the database in sanitize_option().
			 * We want to reverse this for the plain text arena of emails.
			 */
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

			$wp_password_change_notification_email = array(
				'to'      => get_option( 'admin_email' ),
				/* translators: Password change notification email subject. %s: Site title. */
				'subject' => __( '[%s] Password Changed' ),
				'message' => $message,
				'headers' => '',
			);

			/**
			 * Filters the contents of the password change notification email sent to the site admin.
			 *
			 * @since 4.9.0
			 *
			 * @param array   $wp_password_change_notification_email {
			 *     Used to build wp_mail().
			 *
			 *     @type string $to      The intended recipient - site admin email address.
			 *     @type string $subject The subject of the email.
			 *     @type string $message The body of the email.
			 *     @type string $headers The headers of the email.
			 * }
			 * @param WP_User $user     User object for user whose password was changed.
			 * @param string  $blogname The site title.
			 */
			$wp_password_change_notification_email = apply_filters( 'wp_password_change_notification_email', $wp_password_change_notification_email, $user, $blogname );

			wp_mail(
				$wp_password_change_notification_email['to'],
				wp_specialchars_decode( sprintf( $wp_password_change_notification_email['subject'], $blogname ) ),
				$wp_password_change_notification_email['message'],
				$wp_password_change_notification_email['headers']
			);

			if ( $switched_locale ) {
				restore_previous_locale();
			}
		}
	}
```

</details>


### Source: [user.php](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-includes/user.php)

SHA-256: `c837321fbf722f89a080324f3c742b0c44e5853c9bd6d305cb894b5f664fc6cc`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Error: Please enter a username or email address.
- Error: There is no account with that username or email address.
- Someone has requested a password reset for the following account:
- Site Name: %s
- If this was a mistake, ignore this email and nothing will happen.
- To reset your password, visit the following address:
- This password reset request originated from the IP address %s.
- [%s] Password Reset
- Error: The email could not be sent. Your site may not be correctly configured to send emails. Get support for resetting your password .

<details><summary>Exact template/renderer source, beginning at line 3262</summary>

```php
function retrieve_password( $user_login = '' ) {
	$errors    = new WP_Error();
	$user_data = false;

	// Use the passed $user_login if available, otherwise use $_POST['user_login'].
	if ( ! $user_login && ! empty( $_POST['user_login'] ) && is_string( $_POST['user_login'] ) ) {
		$user_login = $_POST['user_login'];
	}

	$user_login = trim( wp_unslash( $user_login ) );

	if ( empty( $user_login ) ) {
		$errors->add( 'empty_username', __( '<strong>Error:</strong> Please enter a username or email address.' ) );
	} elseif ( strpos( $user_login, '@' ) ) {
		$user_data = get_user_by( 'email', $user_login );

		if ( empty( $user_data ) ) {
			$user_data = get_user_by( 'login', $user_login );
		}

		if ( empty( $user_data ) ) {
			$errors->add( 'invalid_email', __( '<strong>Error:</strong> There is no account with that username or email address.' ) );
		}
	} else {
		$user_data = get_user_by( 'login', $user_login );
	}

	/**
	 * Filters the user data during a password reset request.
	 *
	 * Allows, for example, custom validation using data other than username or email address.
	 *
	 * @since 5.7.0
	 *
	 * @param WP_User|false $user_data WP_User object if found, false if the user does not exist.
	 * @param WP_Error      $errors    A WP_Error object containing any errors generated
	 *                                 by using invalid credentials.
	 */
	$user_data = apply_filters( 'lostpassword_user_data', $user_data, $errors );

	/**
	 * Fires before errors are returned from a password reset request.
	 *
	 * @since 2.1.0
	 * @since 4.4.0 Added the `$errors` parameter.
	 * @since 5.4.0 Added the `$user_data` parameter.
	 *
	 * @param WP_Error      $errors    A WP_Error object containing any errors generated
	 *                                 by using invalid credentials.
	 * @param WP_User|false $user_data WP_User object if found, false if the user does not exist.
	 */
	do_action( 'lostpassword_post', $errors, $user_data );

	/**
	 * Filters the errors encountered on a password reset request.
	 *
	 * The filtered WP_Error object may, for example, contain errors for an invalid
	 * username or email address. A WP_Error object should always be returned,
	 * but may or may not contain errors.
	 *
	 * If any errors are present in $errors, this will abort the password reset request.
	 *
	 * @since 5.5.0
	 *
	 * @param WP_Error      $errors    A WP_Error object containing any errors generated
	 *                                 by using invalid credentials.
	 * @param WP_User|false $user_data WP_User object if found, false if the user does not exist.
	 */
	$errors = apply_filters( 'lostpassword_errors', $errors, $user_data );

	if ( $errors->has_errors() ) {
		return $errors;
	}

	if ( ! $user_data ) {
		$errors->add( 'invalidcombo', __( '<strong>Error:</strong> There is no account with that username or email address.' ) );
		return $errors;
	}

	/**
	 * Filters whether to send the retrieve password email.
	 *
	 * Return false to disable sending the email.
	 *
	 * @since 6.0.0
	 *
	 * @param bool    $send       Whether to send the email.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 */
	if ( ! apply_filters( 'send_retrieve_password_email', true, $user_login, $user_data ) ) {
		return true;
	}

	// Redefining user_login ensures we return the right case in the email.
	$user_login = $user_data->user_login;
	$user_email = $user_data->user_email;
	$key        = get_password_reset_key( $user_data );

	if ( is_wp_error( $key ) ) {
		return $key;
	}

	// Localize password reset message content for user.
	$locale = get_user_locale( $user_data );

	$switched_locale = switch_to_user_locale( $user_data->ID );

	if ( is_multisite() ) {
		$site_name = get_network()->site_name;
	} else {
		/*
		 * The blogname option is escaped with esc_html on the way into the database
		 * in sanitize_option. We want to reverse this for the plain text arena of emails.
		 */
		$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	$message = __( 'Someone has requested a password reset for the following account:' ) . "\r\n\r\n";
	/* translators: %s: Site name. */
	$message .= sprintf( __( 'Site Name: %s' ), $site_name ) . "\r\n\r\n";
	/* translators: %s: User login. */
	$message .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
	$message .= __( 'If this was a mistake, ignore this email and nothing will happen.' ) . "\r\n\r\n";
	$message .= __( 'To reset your password, visit the following address:' ) . "\r\n\r\n";

	/*
	 * Since some user login names end in a period, this could produce ambiguous URLs that
	 * end in a period. To avoid the ambiguity, ensure that the login is not the last query
	 * arg in the URL. If moving it to the end, a trailing period will need to be escaped.
	 *
	 * @see https://core.trac.wordpress.org/tickets/42957
	 */
	$message .= network_site_url( 'wp-login.php?login=' . rawurlencode( $user_login ) . "&key=$key&action=rp", 'login' ) . '&wp_lang=' . $locale . "\r\n\r\n";

	if ( ! is_user_logged_in() ) {
		$requester_ip = $_SERVER['REMOTE_ADDR'];
		if ( $requester_ip ) {
			$message .= sprintf(
				/* translators: %s: IP address of password reset requester. */
				__( 'This password reset request originated from the IP address %s.' ),
				$requester_ip
			) . "\r\n";
		}
	}

	/* translators: Password reset notification email subject. %s: Site title. */
	$title = sprintf( __( '[%s] Password Reset' ), $site_name );

	/**
	 * Filters the subject of the password reset email.
	 *
	 * @since 2.8.0
	 * @since 4.4.0 Added the `$user_login` and `$user_data` parameters.
	 *
	 * @param string  $title      Email subject.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 */
	$title = apply_filters( 'retrieve_password_title', $title, $user_login, $user_data );

	/**
	 * Filters the message body of the password reset mail.
	 *
	 * If the filtered message is empty, the password reset email will not be sent.
	 *
	 * @since 2.8.0
	 * @since 4.1.0 Added `$user_login` and `$user_data` parameters.
	 *
	 * @param string  $message    Email message.
	 * @param string  $key        The activation key.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 */
	$message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user_data );

	// Short-circuit on falsey $message value for backwards compatibility.
	if ( ! $message ) {
		return true;
	}

	/*
	 * Wrap the single notification email arguments in an array
	 * to pass them to the retrieve_password_notification_email filter.
	 */
	$defaults = array(
		'to'      => $user_email,
		'subject' => $title,
		'message' => $message,
		'headers' => '',
	);

	/**
	 * Filters the contents of the reset password notification email sent to the user.
	 *
	 * @since 6.0.0
	 *
	 * @param array $defaults {
	 *     The default notification email arguments. Used to build wp_mail().
	 *
	 *     @type string $to      The intended recipient - user email address.
	 *     @type string $subject The subject of the email.
	 *     @type string $message The body of the email.
	 *     @type string $headers The headers of the email.
	 * }
	 * @param string  $key        The activation key.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 */
	$notification_email = apply_filters( 'retrieve_password_notification_email', $defaults, $key, $user_login, $user_data );

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	if ( is_array( $notification_email ) ) {
		// Force key order and merge defaults in case any value is missing in the filtered array.
		$notification_email = array_merge( $defaults, $notification_email );
	} else {
		$notification_email = $defaults;
	}

	list( $to, $subject, $message, $headers ) = array_values( $notification_email );

	$subject = wp_specialchars_decode( $subject );

	if ( ! wp_mail( $to, $subject, $message, $headers ) ) {
		$errors->add(
			'retrieve_password_email_failure',
			sprintf(
				/* translators: %s: Documentation URL. */
				__( '<strong>Error:</strong> The email could not be sent. Your site may not be correctly configured to send emails. <a href="%s">Get support for resetting your password</a>.' ),
				esc_url( __( 'https://wordpress.org/documentation/article/reset-your-password/' ) )
			)
		);
		return $errors;
	}

	return true;
}
```

</details>

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Invalid user ID.
- Hi ###USERNAME###, This notice confirms that your password was changed on ###SITENAME###. If you did not change your password, please contact the Site Administrator at ###ADMIN_EMAIL### This email has been sent to ###EMAIL### Regards, All at ###SITENAME### ###SITEURL###
- [%s] Password Changed
- s password is changed. * * @since 4.3.0 * * @param array $pass_change_email { * Used to build wp_mail(). * * @type string $to The intended recipients. Add emails in a comma separated string. * @type string $subject The subject of the email. * @type string $message The content of the email. * The following strings have a special meaning and will get replaced dynamically: * - `###USERNAME###` The current user
- s email address. * - `###SITENAME###` The name of the site. * - `###SITEURL###` The URL to the site. * @type string $headers Headers. Add headers in a newline (\r ) separated string. * } * @param array $user The original user array. * @param array $userdata The updated user array. */ $pass_change_email = apply_filters(
- ] ); } if ( ! empty( $send_email_change_email ) ) { /* translators: Do not translate USERNAME, ADMIN_EMAIL, NEW_EMAIL, EMAIL, SITENAME, SITEURL: those are placeholders. */ $email_change_text = __(
- ], /* translators: Email change notification email subject. %s: Site title. */
- , ); /** * Filters the contents of the email sent when the user
- s username. * - `###ADMIN_EMAIL###` The admin email in case this was unexpected. * - `###NEW_EMAIL###` The new email address. * - `###EMAIL###` The old email address. * - `###SITENAME###` The name of the site. * - `###SITEURL###` The URL to the site. * @type string $headers Headers. * } * @param array $user The original user array. * @param array $userdata The updated user array. */ $email_change_email = apply_filters(
- ] ); } if ( $switched_locale ) { restore_previous_locale(); } // Update the cookies if the password changed. $current_user = wp_get_current_user(); if ( $current_user->ID === $user_id ) { if ( isset( $plaintext_pass ) ) { /* * Here we calculate the expiration length of the current auth cookie and compare it to the default expiration. * If it
- Remember Me

<details><summary>Exact template/renderer source, beginning at line 2733</summary>

```php
function wp_update_user( $userdata ) {
	if ( $userdata instanceof stdClass ) {
		$userdata = get_object_vars( $userdata );
	} elseif ( $userdata instanceof WP_User ) {
		$userdata = $userdata->to_array();
	}

	$userdata_raw = $userdata;

	$user_id = (int) ( $userdata['ID'] ?? 0 );
	if ( ! $user_id ) {
		return new WP_Error( 'invalid_user_id', __( 'Invalid user ID.' ) );
	}

	// First, get all of the original fields.
	$user_obj = get_userdata( $user_id );
	if ( ! $user_obj ) {
		return new WP_Error( 'invalid_user_id', __( 'Invalid user ID.' ) );
	}

	$user = $user_obj->to_array();

	// Add additional custom fields.
	foreach ( _get_additional_user_keys( $user_obj ) as $key ) {
		$user[ $key ] = get_user_meta( $user_id, $key, true );
	}

	// Escape data pulled from DB.
	$user = add_magic_quotes( $user );

	if ( ! empty( $userdata['user_pass'] ) && $userdata['user_pass'] !== $user_obj->user_pass ) {
		// If password is changing, hash it now.
		$plaintext_pass        = $userdata['user_pass'];
		$userdata['user_pass'] = wp_hash_password( $userdata['user_pass'] );

		/** This action is documented in wp-includes/pluggable.php */
		do_action( 'wp_set_password', $plaintext_pass, $user_id, $user_obj );

		/**
		 * Filters whether to send the password change email.
		 *
		 * @since 4.3.0
		 *
		 * @see wp_insert_user() For `$user` and `$userdata` fields.
		 *
		 * @param bool  $send     Whether to send the email.
		 * @param array $user     The original user array.
		 * @param array $userdata The updated user array.
		 */
		$send_password_change_email = apply_filters( 'send_password_change_email', true, $user, $userdata );
	}

	if ( isset( $userdata['user_email'] ) && $user['user_email'] !== $userdata['user_email'] ) {
		/**
		 * Filters whether to send the email change email.
		 *
		 * @since 4.3.0
		 *
		 * @see wp_insert_user() For `$user` and `$userdata` fields.
		 *
		 * @param bool  $send     Whether to send the email.
		 * @param array $user     The original user array.
		 * @param array $userdata The updated user array.
		 */
		$send_email_change_email = apply_filters( 'send_email_change_email', true, $user, $userdata );
	}

	clean_user_cache( $user_obj );

	// Merge old and new fields with new fields overwriting old ones.
	$userdata = array_merge( $user, $userdata );
	$user_id  = wp_insert_user( $userdata );

	if ( is_wp_error( $user_id ) ) {
		return $user_id;
	}

	$blog_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

	$switched_locale = false;
	if ( ! empty( $send_password_change_email ) || ! empty( $send_email_change_email ) ) {
		$switched_locale = switch_to_user_locale( $user_id );
	}

	if ( ! empty( $send_password_change_email ) ) {
		/* translators: Do not translate USERNAME, ADMIN_EMAIL, EMAIL, SITENAME, SITEURL: those are placeholders. */
		$pass_change_text = __(
			'Hi ###USERNAME###,

This notice confirms that your password was changed on ###SITENAME###.

If you did not change your password, please contact the Site Administrator at
###ADMIN_EMAIL###

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###'
		);

		$pass_change_email = array(
			'to'      => $user['user_email'],
			/* translators: Password change notification email subject. %s: Site title. */
			'subject' => __( '[%s] Password Changed' ),
			'message' => $pass_change_text,
			'headers' => '',
		);

		/**
		 * Filters the contents of the email sent when the user's password is changed.
		 *
		 * @since 4.3.0
		 *
		 * @param array $pass_change_email {
		 *     Used to build wp_mail().
		 *
		 *     @type string $to      The intended recipients. Add emails in a comma separated string.
		 *     @type string $subject The subject of the email.
		 *     @type string $message The content of the email.
		 *         The following strings have a special meaning and will get replaced dynamically:
		 *          - `###USERNAME###`    The current user's username.
		 *          - `###ADMIN_EMAIL###` The admin email in case this was unexpected.
		 *          - `###EMAIL###`       The user's email address.
		 *          - `###SITENAME###`    The name of the site.
		 *          - `###SITEURL###`     The URL to the site.
		 *     @type string $headers Headers. Add headers in a newline (\r\n) separated string.
		 * }
		 * @param array $user     The original user array.
		 * @param array $userdata The updated user array.
		 */
		$pass_change_email = apply_filters( 'password_change_email', $pass_change_email, $user, $userdata );

		$pass_change_email['message'] = str_replace( '###USERNAME###', $user['user_login'], $pass_change_email['message'] );
		$pass_change_email['message'] = str_replace( '###ADMIN_EMAIL###', get_option( 'admin_email' ), $pass_change_email['message'] );
		$pass_change_email['message'] = str_replace( '###EMAIL###', $user['user_email'], $pass_change_email['message'] );
		$pass_change_email['message'] = str_replace( '###SITENAME###', $blog_name, $pass_change_email['message'] );
		$pass_change_email['message'] = str_replace( '###SITEURL###', home_url(), $pass_change_email['message'] );

		wp_mail( $pass_change_email['to'], sprintf( $pass_change_email['subject'], $blog_name ), $pass_change_email['message'], $pass_change_email['headers'] );
	}

	if ( ! empty( $send_email_change_email ) ) {
		/* translators: Do not translate USERNAME, ADMIN_EMAIL, NEW_EMAIL, EMAIL, SITENAME, SITEURL: those are placeholders. */
		$email_change_text = __(
			'Hi ###USERNAME###,

This notice confirms that your email address on ###SITENAME### was changed to ###NEW_EMAIL###.

If you did not change your email, please contact the Site Administrator at
###ADMIN_EMAIL###

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###'
		);

		$email_change_email = array(
			'to'      => $user['user_email'],
			/* translators: Email change notification email subject. %s: Site title. */
			'subject' => __( '[%s] Email Changed' ),
			'message' => $email_change_text,
			'headers' => '',
		);

		/**
		 * Filters the contents of the email sent when the user's email is changed.
		 *
		 * @since 4.3.0
		 *
		 * @param array $email_change_email {
		 *     Used to build wp_mail().
		 *
		 *     @type string $to      The intended recipients.
		 *     @type string $subject The subject of the email.
		 *     @type string $message The content of the email.
		 *         The following strings have a special meaning and will get replaced dynamically:
		 *          - `###USERNAME###`    The current user's username.
		 *          - `###ADMIN_EMAIL###` The admin email in case this was unexpected.
		 *          - `###NEW_EMAIL###`   The new email address.
		 *          - `###EMAIL###`       The old email address.
		 *          - `###SITENAME###`    The name of the site.
		 *          - `###SITEURL###`     The URL to the site.
		 *     @type string $headers Headers.
		 * }
		 * @param array $user     The original user array.
		 * @param array $userdata The updated user array.
		 */
		$email_change_email = apply_filters( 'email_change_email', $email_change_email, $user, $userdata );

		$email_change_email['message'] = str_replace( '###USERNAME###', $user['user_login'], $email_change_email['message'] );
		$email_change_email['message'] = str_replace( '###ADMIN_EMAIL###', get_option( 'admin_email' ), $email_change_email['message'] );
		$email_change_email['message'] = str_replace( '###NEW_EMAIL###', $userdata['user_email'], $email_change_email['message'] );
		$email_change_email['message'] = str_replace( '###EMAIL###', $user['user_email'], $email_change_email['message'] );
		$email_change_email['message'] = str_replace( '###SITENAME###', $blog_name, $email_change_email['message'] );
		$email_change_email['message'] = str_replace( '###SITEURL###', home_url(), $email_change_email['message'] );

		wp_mail( $email_change_email['to'], sprintf( $email_change_email['subject'], $blog_name ), $email_change_email['message'], $email_change_email['headers'] );
	}

	if ( $switched_locale ) {
		restore_previous_locale();
	}

	// Update the cookies if the password changed.
	$current_user = wp_get_current_user();
	if ( $current_user->ID === $user_id ) {
		if ( isset( $plaintext_pass ) ) {
			/*
			 * Here we calculate the expiration length of the current auth cookie and compare it to the default expiration.
			 * If it's greater than this, then we know the user checked 'Remember Me' when they logged in.
			 */
			$logged_in_cookie = wp_parse_auth_cookie( '', 'logged_in' );
			/** This filter is documented in wp-includes/pluggable.php */
			$default_cookie_life = apply_filters( 'auth_cookie_expiration', ( 2 * DAY_IN_SECONDS ), $user_id, false );

			wp_clear_auth_cookie();

			$remember = false;
			$token    = '';

			if ( false !== $logged_in_cookie ) {
				$token = $logged_in_cookie['token'];
			}

			if ( false !== $logged_in_cookie && ( (int) $logged_in_cookie['expiration'] - time() ) > $default_cookie_life ) {
				$remember = true;
			}

			wp_set_auth_cookie( $user_id, $remember, '', $token );
		}
	}

	/**
	 * Fires after the user has been updated and emails have been sent.
	 *
	 * @since 6.3.0
	 *
	 * @param int   $user_id      The ID of the user that was just updated.
	 * @param array $userdata     The array of user data that was updated.
	 * @param array $userdata_raw The unedited array of user data that was updated.
	 */
	do_action( 'wp_update_user', $user_id, $userdata, $userdata_raw );

	return $user_id;
}
```

</details>

<details><summary>Exact template/renderer source, beginning at line 3679</summary>

```php
function wp_send_new_user_notifications( $user_id, $notify = 'both' ) {
	wp_new_user_notification( $user_id, null, $notify );
}
```

</details>


### dokan-lite / includes\Emails\ContactSeller.php


### Source: [ContactSeller.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/includes/Emails/ContactSeller.php)

SHA-256: `66d1a19dab2697d994062546cc1f337e039a2fa3c045741895ba483facde9cdd`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dokan Contact Vendor
- These emails are sent to a vendor who is contacted by customer via contact form widget
- [{customer_name}] sent you a message from your store at - {site_title}
- {customer_name} - Sent a message from {site_title}
- Available placeholders: %s
- Enable this email notification
- Email heading
- Additional content
- Text to appear below the main email content.
- Email type
- Choose which format of email to send.
- email_type wc-enhanced-select

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php

namespace WeDevs\Dokan\Emails;

use WC_Email;
use WeDevs\Dokan\Vendor\Vendor;

/**
 * Customer Email to vendor from contact form widget.
 *
 * @class       Dokan_Email_Contact_Seller
 * @version     2.6.8
 * @author      weDevs
 * @extends     WC_Email
 */
class ContactSeller extends WC_Email {

    /**
     * Reply email
     *
     * @since 3.2.15
     *
     * @var string
     */
    private $from_email;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_contact_seller';
        $this->title          = __( 'Dokan Contact Vendor', 'dokan-lite' );
        $this->description    = __( 'These emails are sent to a vendor who is contacted by customer via contact form widget ', 'dokan-lite' );
        $this->template_html  = 'emails/contact-seller.php';
        $this->template_plain = 'emails/plain/contact-seller.php';
        $this->template_base  = DOKAN_DIR . '/templates/';
        $this->placeholders   = [
            '{store_name}'     => '',
            '{customer_name}'  => '',
            '{customer_email}' => '',
            '{message}'        => '',
            // only for backward compatibility
            '{site_name}'      => $this->get_from_name(),
            '{seller_name}'    => '',
        ];

        // Triggers for this email
        add_action( 'dokan_trigger_contact_seller_mail', array( $this, 'trigger' ), 30, 4 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = 'vendor@ofthe.product';
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject() {
        return __( '[{customer_name}] sent you a message from your store at - {site_title}', 'dokan-lite' );
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading() {
        return __( '{customer_name} - Sent a message from {site_title}', 'dokan-lite' );
    }

    /**
     * Trigger this email.
     */
    public function trigger( $seller_email, $contact_name, $contact_email, $contact_message ) {
        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }
        $this->setup_locale();

        $this->from_email = $contact_email;

        $seller = get_user_by( 'email', $seller_email );
        $seller = new Vendor( $seller );

        $this->placeholders['{store_name}']     = $seller->get_shop_name();
        $this->placeholders['{customer_name}']  = $contact_name;
        $this->placeholders['{customer_email}'] = $contact_email;
        $this->placeholders['{message}']        = $contact_message;
        $this->placeholders['{seller_name}']    = $seller->get_shop_name(); // only for backward compatibility.
        $this->send( $seller_email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

    /**
     * Get the from address for outgoing emails.
     *
     * @return string
     */
    public function get_from_address( $from_email = '' ) {
        return $this->from_email;
    }

    /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html, array(
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
                'data'               => $this->placeholders,
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain, array(
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
                'data'               => $this->placeholders,
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Initialize settings form fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        unset( $placeholders['{site_name}'], $placeholders['{seller_name}'] );
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan-lite' ), '<code>' . implode( '</code>, <code>', array_keys( $placeholders ) ) . '</code>' );
        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enable/Disable', 'dokan-lite' ),
                'type'          => 'checkbox',
                'label'         => __( 'Enable this email notification', 'dokan-lite' ),
                'default'       => 'yes',
            ),

            'subject' => array(
                'title'         => __( 'Subject', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_subject(),
                'default'       => '',
            ),
            'heading' => array(
                'title'         => __( 'Email heading', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_heading(),
                'default'       => '',
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'dokan-lite' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan-lite' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan-lite' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'         => __( 'Email type', 'dokan-lite' ),
                'type'          => 'select',
                'description'   => __( 'Choose which format of email to send.', 'dokan-lite' ),
                'default'       => 'html',
                'class'         => 'email_type wc-enhanced-select',
                'options'       => $this->get_email_type_options(),
                'desc_tip'      => true,
            ),
        );
    }
}

```

</details>


### Source: [contact-seller.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/contact-seller.php)

SHA-256: `7b2ab3cd53b0c901f369002b68c275e354b7ef2d54fc685358f2c5eeb167f6e4`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- s settings. */ if ( $additional_content ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); } ?> <?php do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Contact Seller Email.
 *
 * An email sent to the vendor when a vendor is contacted via customer.
 *
 * @class       Dokan_Email_Contact_Seller
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
    <?php
    echo wp_kses_post(
        // translators: 1) customer name 2) customer email
        sprintf( __( 'From : %1$s (%2$s)', 'dokan-lite' ), esc_html( $data['{customer_name}'] ), esc_html( $data['{customer_email}'] ) )
    );
    ?>
    <br>
</p>
<hr>
<p>
    <?php echo esc_html( $data['{message}'] ); ?>
</p>
<hr>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
?>

<?php
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [contact-seller.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/plain/contact-seller.php)

SHA-256: `472032f5cff58fba40cafef4e4379de85993831346107b7f9940b1acaa07a5f1`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- s settings. */ if ( ! empty( $additional_content ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Contact Seller Email. (plain text)
 *
 * An email sent to the vendor when a vendor is contacted via customer.
 *
 * @class       Dokan_Email_Contact_Seller
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: 1) customer name 2) customer email
echo sprintf( esc_html__( 'From : %1$s (%2$s)', 'dokan-lite' ), esc_html( $data['{customer_name}'] ), esc_html( $data['{customer_email}'] ) );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( wp_strip_all_tags( wptexturize( $data['{message}'] ) ) );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


### dokan-lite / includes\Emails\NewProduct.php


### Source: [NewProduct.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/includes/Emails/NewProduct.php)

SHA-256: `e597891cb856578d4c7434aecaddd5897e47d70386ae687b88d7a99e4f78287e`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dokan New Product
- New Product emails are sent to chosen recipient(s) when a new product is created by vendors.
- [{site_title}] A New product is added by ({store_name}) - {product_title}
- New product added by Vendor {store_name}
- ve added _dokan_new_product_email_sent from version 3.8.2 // so, we are assuming if the meta doesn
- Available placeholders: %s
- Enable this email notification
- Enter recipients (comma separated) for this email. Defaults to %s.
- Email heading
- Additional content
- Text to appear below the main email content.
- Email type
- Choose which format of email to send.
- email_type wc-enhanced-select

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php

namespace WeDevs\Dokan\Emails;

use WC_Email;
use WeDevs\Dokan\Vendor\Vendor;

/**
 * New Product Email.
 *
 * An email sent to the admin when a new Product is created by vendor.
 *
 * @class       Dokan_Email_New_Product
 * @version     2.6.8
 * @author      weDevs
 * @extends     WC_Email
 */
class NewProduct extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'new_product';
        $this->title          = __( 'Dokan New Product', 'dokan-lite' );
        $this->description    = __( 'New Product emails are sent to chosen recipient(s) when a new product is created by vendors.', 'dokan-lite' );
        $this->template_html  = 'emails/new-product.php';
        $this->template_plain = 'emails/plain/new-product.php';
        $this->template_base  = DOKAN_DIR . '/templates/';
        $this->placeholders = [
            '{product_title}' => '',
            '{price}'         => '',
            '{store_name}'    => '',
            '{seller_url}'    => '',
            '{category}'      => '',
            '{product_link}'  => '',
            // only for backward compatibility
            '{site_name}'     => $this->get_from_name(),
            '{seller_name}'   => '',
        ];

        // Triggers for this email
        add_action( 'dokan_new_product_added', array( $this, 'trigger' ), 30, 1 );
        add_action( 'dokan_product_updated', array( $this, 'trigger' ), 30, 1 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject() {
            return __( '[{site_title}] A New product is added by ({store_name}) - {product_title}', 'dokan-lite' );
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading() {
            return __( 'New product added by Vendor {store_name}', 'dokan-lite' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param int $product_id The product ID.
     */
    public function trigger( $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return;
        }

        // we've added _dokan_new_product_email_sent from version 3.8.2
        // so, we are assuming if the meta doesn't exist, email was already sent to the client
        $email_sent = $product->get_meta( '_dokan_new_product_email_sent' );
        if ( empty( $email_sent ) || true === wc_string_to_bool( $email_sent ) ) {
            return;
        }

        if ( 'publish' !== $product->get_status() ) {
            return;
        }

        $product->update_meta_data( '_dokan_new_product_email_sent', 'yes' );
        $product->save();

        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }

        $this->setup_locale();

        $seller_id     = get_post_field( 'post_author', $product_id );
        $seller        = new Vendor( $seller_id );
        $category      = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
        $category_name = $category ? implode( ', ', $category ) : 'N/A';
        $this->object  = $product;

        $this->placeholders['{product_title}'] = $product->get_title();
        $this->placeholders['{price}']         = dokan()->email->currency_symbol( $product->get_price() );
        $this->placeholders['{store_name}']    = $seller->get_shop_name();
        $this->placeholders['{seller_url}']    = $seller->get_shop_url();
        $this->placeholders['{category}']      = $category_name;
        $this->placeholders['{product_link}']  = admin_url( 'post.php?action=edit&post=' . $product_id );
        $this->placeholders['{seller_name}']   = $seller->get_shop_name(); //for backward compatibility.

        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'product'            => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
                'data'               => $this->placeholders,
            ),
            'dokan/',
            $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'product'            => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
                'data'               => $this->placeholders,
            ),
            'dokan/',
            $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        unset( $placeholders['{site_name}'], $placeholders['{seller_name}'] );
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan-lite' ), '<code>' . implode( '</code>, <code>', array_keys( $placeholders ) ) . '</code>' );
        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enable/Disable', 'dokan-lite' ),
                'type'          => 'checkbox',
                'label'         => __( 'Enable this email notification', 'dokan-lite' ),
                'default'       => 'yes',
            ),
            'recipient' => array(
                'title'         => __( 'Recipient(s)', 'dokan-lite' ),
                'type'          => 'text',
                // translators: 1) Email recipients
                'description'   => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'dokan-lite' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
                'placeholder'   => '',
                'default'       => '',
                'desc_tip'      => true,
            ),
            'subject' => array(
                'title'         => __( 'Subject', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_subject(),
                'default'       => '',
            ),
            'heading' => array(
                'title'         => __( 'Email heading', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_heading(),
                'default'       => '',
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'dokan-lite' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan-lite' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan-lite' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'         => __( 'Email type', 'dokan-lite' ),
                'type'          => 'select',
                'description'   => __( 'Choose which format of email to send.', 'dokan-lite' ),
                'default'       => 'html',
                'class'         => 'email_type wc-enhanced-select',
                'options'       => $this->get_email_type_options(),
                'desc_tip'      => true,
            ),
        );
    }
}

```

</details>


### Source: [new-product.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/new-product.php)

SHA-256: `92bad95cc37b7ffa261a4bc9ad747543295ad5483db532fbeb5c2463c352e81f`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- A new product is submitted to your site
- Summary of the product:
- The product is currently in "publish" status. So, everyone can view the product.
- s settings. */ if ( $additional_content ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); } do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Product Email.
 *
 * An email sent to the admin when a new Product is created by vendor.
 *
 * @class       Dokan_Email_New_Product
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php esc_attr_e( 'Hello,', 'dokan-lite' ); ?></p>

<p><?php esc_attr_e( 'A new product is submitted to your site', 'dokan-lite' ); ?> <a href="<?php echo esc_url( $data['{site_url}'] ); ?>" ><?php echo esc_attr( $data['{site_title}'] ); ?></a> </p>
<p><?php esc_attr_e( 'Summary of the product:', 'dokan-lite' ); ?></p>
<hr>
<ul>
    <li>
        <strong>
            <?php esc_attr_e( 'Title :', 'dokan-lite' ); ?>
        </strong>
        <?php printf( '<a href="%s">%s</a>', esc_url( $data['{product_link}'] ), esc_attr( $data['{product_title}'] ) ); ?>
    </li>
    <li>
        <strong>
            <?php esc_attr_e( 'Price :', 'dokan-lite' ); ?>
        </strong>
        <?php echo esc_html( $data['{price}'] ); ?>
    </li>
    <li>
        <strong>
            <?php esc_attr_e( 'Vendor :', 'dokan-lite' ); ?>
        </strong>
        <?php printf( '<a href="%s">%s</a>', esc_url( $data['{seller_url}'] ), esc_attr( $data['{store_name}'] ) ); ?>
    </li>
    <li>
        <strong>
            <?php esc_attr_e( 'Category :', 'dokan-lite' ); ?>
        </strong>
        <?php echo esc_html( $data['{category}'] ); ?>
    </li>

</ul>
<p><?php esc_attr_e( 'The product is currently in "publish" status. So, everyone can view the product.', 'dokan-lite' ); ?></p>

<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [new-product.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/plain/new-product.php)

SHA-256: `c698381f72d6dcb8059da3cbb1c2fad5dd6990b5ccd06de5b286f909950d9a22`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Hello there,
- A new product is submitted to your site.
- Summary of the product:
- The product is currently in "publish" state. So everyone can view the product.
- In case it needs to be moderated, please follow the URL below.
- s settings. */ if ( ! empty( $additional_content ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Product Email ( plain text )
 *
 * An email sent to the admin when a new Product is created by vendor.
 *
 * @class       Dokan_Email_New_Product
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_html_e( 'Hello there,', 'dokan-lite' );
echo " \n\n";

esc_html_e( 'A new product is submitted to your site.', 'dokan-lite' );
echo " \n\n";

esc_html_e( 'Summary of the product:', 'dokan-lite' );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: 1) product title
echo sprintf( esc_html__( 'Title: %1$s', 'dokan-lite' ), esc_html( $data['{product_title}'] ) );
echo " \n";

// translators: 1) product price
echo sprintf( esc_html__( 'Price: %1$s', 'dokan-lite' ), esc_html( $data['{price}'] ) );
echo " \n";

// translators: 1) product seller name
echo sprintf( esc_html__( 'Vendor: %1$s', 'dokan-lite' ), esc_html( $data['{store_name}'] ) );
echo " \n";

// translators: 1) product category
echo sprintf( esc_html__( 'Category: %1$s', 'dokan-lite' ), esc_html( $data['{category}'] ) );
echo " \n";

echo esc_html__( 'The product is currently in "publish" state. So everyone can view the product.', 'dokan-lite' );
echo " \n";

esc_html_e( 'In case it needs to be moderated, please follow the URL below.', 'dokan-lite' );
echo esc_url( $data['{product_link}'] );
echo " \n";

echo "\n\n----------------------------------------\n\n";
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


### dokan-lite / includes\Emails\NewProductPending.php


### Source: [NewProductPending.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/includes/Emails/NewProductPending.php)

SHA-256: `08273e3243b0fea9aef5b9b2ceb8c192569ad973a2c884e9e7f0acd6218354fb`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dokan New Pending Product
- New Pending Product emails are sent to chosen recipient(s) when a new product is created by vendors.
- [{site_title}] A New product is pending from ({store_name}) - {product_title}
- New pending product added by Vendor {store_name}
- ve added _dokan_new_product_email_sent from version 3.8.2 // so, we are assuming if the meta doesn
- Available placeholders: %s
- Enable this email notification
- Enter recipients (comma separated) for this email. Defaults to %1$s.
- Email heading
- Additional content
- Text to appear below the main email content.
- Email type
- Choose which format of email to send.
- email_type wc-enhanced-select

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php

namespace WeDevs\Dokan\Emails;

use WC_Email;
use WeDevs\Dokan\Vendor\Vendor;

/**
 * New Product Email.
 *
 * An email sent to the admin when a new Product is created by vendor.
 *
 * @class       Dokan_Email_New_Product_Pending
 * @version     2.6.8
 * @package     Dokan/Classes/Emails
 * @author      weDevs
 * @extends     WC_Email
 */
class NewProductPending extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'new_product_pending';
        $this->title          = __( 'Dokan New Pending Product', 'dokan-lite' );
        $this->description    = __( 'New Pending Product emails are sent to chosen recipient(s) when a new product is created by vendors.', 'dokan-lite' );
        $this->template_html  = 'emails/new-product-pending.php';
        $this->template_plain = 'emails/plain/new-product-pending.php';
        $this->template_base  = DOKAN_DIR . '/templates/';
        $this->placeholders   = [
            '{product_title}' => '',
            '{price}'         => '',
            '{store_name}'    => '',
            '{seller_url}'    => '',
            '{category}'      => '',
            '{product_link}'  => '',
            // only for backward compatibility
            '{site_name}'     => $this->get_from_name(),
            '{seller_name}'   => '',
        ];

        // Triggers for this email
        add_action( 'dokan_new_product_added', array( $this, 'trigger' ), 30, 1 );
        add_action( 'dokan_product_updated', array( $this, 'trigger' ), 30, 1 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject() {
            return __( '[{site_title}] A New product is pending from ({store_name}) - {product_title}', 'dokan-lite' );
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading() {
            return __( 'New pending product added by Vendor {store_name}', 'dokan-lite' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param int $product_id The product ID.
     */
    public function trigger( $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return;
        }

        // we've added _dokan_new_product_email_sent from version 3.8.2
        // so, we are assuming if the meta doesn't exist, email was already sent to the client
        $email_sent = $product->get_meta( '_dokan_new_product_email_sent' );
        if ( empty( $email_sent ) || true === wc_string_to_bool( $email_sent ) ) {
            return;
        }

        if ( 'pending' !== $product->get_status() ) {
            return;
        }

        $product->update_meta_data( '_dokan_new_product_email_sent', 'yes' );
        $product->save();

        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }

        $this->setup_locale();
        $seller_id     = get_post_field( 'post_author', $product_id );
        $seller        = new Vendor( $seller_id );
        $category      = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
        $category_name = $category ? implode( ', ', $category ) : 'N/A';
        $this->object  = $product;

        $this->placeholders['{product_title}'] = $product->get_title();
        $this->placeholders['{price}']         = dokan()->email->currency_symbol( $product->get_price() );
        $this->placeholders['{store_name}']    = $seller->get_shop_name();
        $this->placeholders['{seller_url}']    = $seller->get_shop_url();
        $this->placeholders['{category}']      = $category_name;
        $this->placeholders['{product_link}']  = admin_url( 'post.php?action=edit&post=' . $product_id );
        $this->placeholders['{seller_name}']   = $seller->get_shop_name(); // only for backward compatibility.

        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

        /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
            ob_start();
                wc_get_template(
                    $this->template_html, array(
                        'product'            => $this->object,
                        'email_heading'      => $this->get_heading(),
                        'additional_content' => $this->get_additional_content(),
                        'sent_to_admin'      => true,
                        'plain_text'         => false,
                        'email'              => $this,
                        'data'               => $this->placeholders,
                    ), 'dokan/', $this->template_base
                );
            return ob_get_clean();
    }

    /**
     * Get content plain.
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
            ob_start();
                wc_get_template(
                    $this->template_plain, array(
                        'product'            => $this->object,
                        'email_heading'      => $this->get_heading(),
                        'additional_content' => $this->get_additional_content(),
                        'sent_to_admin'      => true,
                        'plain_text'         => true,
                        'email'              => $this,
                        'data'               => $this->placeholders,
                    ), 'dokan/', $this->template_base
                );
            return ob_get_clean();
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        unset( $placeholders['{site_name}'], $placeholders['{seller_name}'] );
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan-lite' ), '<code>' . implode( '</code>, <code>', array_keys( $placeholders ) ) . '</code>' );
        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enable/Disable', 'dokan-lite' ),
                'type'          => 'checkbox',
                'label'         => __( 'Enable this email notification', 'dokan-lite' ),
                'default'       => 'yes',
            ),
            'recipient' => array(
                'title'         => __( 'Recipient(s)', 'dokan-lite' ),
                'type'          => 'text',
                // translators: 1) admin email address
                'description'   => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %1$s.', 'dokan-lite' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
                'placeholder'   => '',
                'default'       => '',
                'desc_tip'      => true,
            ),
            'subject' => array(
                'title'         => __( 'Subject', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_subject(),
                'default'       => '',
            ),
            'heading' => array(
                'title'         => __( 'Email heading', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_heading(),
                'default'       => '',
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'dokan-lite' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan-lite' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan-lite' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'         => __( 'Email type', 'dokan-lite' ),
                'type'          => 'select',
                'description'   => __( 'Choose which format of email to send.', 'dokan-lite' ),
                'default'       => 'html',
                'class'         => 'email_type wc-enhanced-select',
                'options'       => $this->get_email_type_options(),
                'desc_tip'      => true,
            ),
        );
    }
}

```

</details>


### Source: [new-product-pending.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/new-product-pending.php)

SHA-256: `c6fb1564efdc8cd3af994eff645215f2b2163ee67e0cb12d7ef3c6da356c8e8c`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- A new product is submitted to your site and is pending review
- Summary of the product:
- The product is currently in "pending" status.
- s settings. */ if ( $additional_content ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); } do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Product Email.
 *
 * An email sent to the admin when a new Product is created by vendor.
 *
 * @class       Dokan_Email_New_Product_Pending
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php esc_html_e( 'Hello,', 'dokan-lite' ); ?></p>

<p><?php esc_html_e( 'A new product is submitted to your site and is pending review', 'dokan-lite' ); ?> <a href="<?php echo esc_url( $data['{site_url}'] ); ?>" ><?php echo esc_html( $data['{site_title}'] ); ?></a> </p>
<p><?php esc_html_e( 'Summary of the product:', 'dokan-lite' ); ?></p>
<hr>
<ul>
    <li>
        <strong>
            <?php esc_html_e( 'Title :', 'dokan-lite' ); ?>
        </strong>
        <?php printf( '<a href="%s">%s</a>', esc_url( $data['{product_link}'] ), esc_html( $data['{product_title}'] ) ); ?>
    </li>
    <li>
        <strong>
            <?php esc_html_e( 'Price :', 'dokan-lite' ); ?>
        </strong>
        <?php echo esc_html( $data['{price}'] ); ?>
    </li>
    <li>
        <strong>
            <?php esc_html_e( 'Vendor :', 'dokan-lite' ); ?>
        </strong>
        <?php printf( '<a href="%s">%s</a>', esc_url( $data['{seller_url}'] ), esc_html( $data['{store_name}'] ) ); ?>
    </li>
    <li>
        <strong>
            <?php esc_html_e( 'Category :', 'dokan-lite' ); ?>
        </strong>
        <?php echo esc_html( $data['{category}'] ); ?>
    </li>

</ul>
<p><?php esc_html_e( 'The product is currently in "pending" status.', 'dokan-lite' ); ?></p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [new-product-pending.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/plain/new-product-pending.php)

SHA-256: `69baa89d93ce3fd93f9fc45d84223ed0adfa67ae1de8437f8618b1bd4789198c`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Hello there,
- A new product is submitted to your site and pending review
- Summary of the product:
- The product is currently in "pending" state.
- In case it needs to be moderated, please the follow URL below.
- s settings. */ if ( ! empty( $additional_content ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Product Email ( plain text )
 *
 * An email sent to the admin when a new Product is created by vendor.
 *
 * @class       Dokan_Email_New_Product
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_html_e( 'Hello there,', 'dokan-lite' );
echo " \n\n";

esc_html_e( 'A new product is submitted to your site and pending review', 'dokan-lite' );
echo " \n\n";

esc_html_e( 'Summary of the product:', 'dokan-lite' );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";

// translators: 1) product title
echo sprintf( esc_html__( 'Title: %s', 'dokan-lite' ), esc_html( $data['{product_title}'] ) );
echo " \n";

// translators: 1) product price
echo sprintf( esc_html__( 'Price: %1$s', 'dokan-lite' ), esc_html( $data['{price}'] ) );
echo " \n";

// translators: 1) product seller name
echo sprintf( esc_html__( 'Vendor: %1$s', 'dokan-lite' ), esc_html( $data['{store_name}'] ) );
echo " \n";

// translators: 1) product category
echo sprintf( esc_html__( 'Category: %1$s', 'dokan-lite' ), esc_html( $data['{category}'] ) );
echo " \n";

esc_html_e( 'The product is currently in "pending" state.', 'dokan-lite' );
echo " \n";

esc_html_e( 'In case it needs to be moderated, please the follow URL below.', 'dokan-lite' );
echo esc_url( $data['{product_link}'] );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


### dokan-lite / includes\Emails\NewSeller.php


### Source: [NewSeller.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/includes/Emails/NewSeller.php)

SHA-256: `06d42c73d40ea1f2d6cccb917935144ae946f733aa4ab78b3e44d3373303047d`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dokan New Seller Registered
- These emails are sent to chosen recipient(s) when a new vendor registers in marketplace
- [{site_title}] A New vendor has registered
- New Vendor Registered - {seller_name}
- Available placeholders: %s
- Enable this email notification
- Enter recipients (comma separated) for this email. Defaults to %s.
- Email heading
- Additional content
- Text to appear below the main email content.
- Email type
- Choose which format of email to send.
- email_type wc-enhanced-select

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php

namespace WeDevs\Dokan\Emails;

use WC_Email;
use WeDevs\Dokan\Vendor\Vendor;

/**
 * New Seller Email.
 *
 * @version     2.6.6
 * @package     Dokan/Classes/Emails
 * @author      weDevs
 * @extends     WC_Email
 */
class NewSeller extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_new_seller';
        $this->title          = __( 'Dokan New Seller Registered', 'dokan-lite' );
        $this->description    = __( 'These emails are sent to chosen recipient(s) when a new vendor registers in marketplace', 'dokan-lite' );
        $this->template_html  = 'emails/new-seller-registered.php';
        $this->template_plain = 'emails/plain/new-seller-registered.php';
        $this->template_base  = DOKAN_DIR . '/templates/';
        $this->placeholders   = [
            '{seller_name}' => '',
            '{store_url}'   => '',
            '{store_name}'  => '',
            '{seller_edit}' => '',
            // only for backward compatibility.
            '{site_name}'   => $this->get_from_name(),
        ];

        // Triggers for this email
        add_action( 'dokan_new_seller_created', array( $this, 'trigger' ), 30, 2 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject() {
            return __( '[{site_title}] A New vendor has registered', 'dokan-lite' );
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading() {
            return __( 'New Vendor Registered - {seller_name}', 'dokan-lite' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param int $user_id The user ID.
     * @param array $shop_info Store info.
     */
    public function trigger( $user_id, $shop_info ) {
		if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
			return;
		}
        $this->setup_locale();

        $seller       = new Vendor( $user_id );
        $this->object = $seller;

        $this->placeholders['{seller_name}'] = $seller->get_name();
        $this->placeholders['{store_url}']   = $seller->get_shop_url();
        $this->placeholders['{store_name}']  = $seller->get_shop_name();
        $this->placeholders['{seller_edit}'] = $seller->get_profile_url();

        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html, array(
                'seller'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
                'data'               => $this->placeholders,
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain, array(
                'seller'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
                'data'               => $this->placeholders,
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        unset( $placeholders['{site_name}'] );
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan-lite' ), '<code>' . implode( '</code>, <code>', array_keys( $placeholders ) ) . '</code>' );
        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enable/Disable', 'dokan-lite' ),
                'type'          => 'checkbox',
                'label'         => __( 'Enable this email notification', 'dokan-lite' ),
                'default'       => 'yes',
            ),
            'recipient' => array(
                'title'         => __( 'Recipient(s)', 'dokan-lite' ),
                'type'          => 'text',
                // translators: 1) Email recipients
                'description'   => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'dokan-lite' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
                'placeholder'   => '',
                'default'       => '',
                'desc_tip'      => true,
            ),
            'subject' => array(
                'title'         => __( 'Subject', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_subject(),
                'default'       => '',
            ),
            'heading' => array(
                'title'         => __( 'Email heading', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_heading(),
                'default'       => '',
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'dokan-lite' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan-lite' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan-lite' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'         => __( 'Email type', 'dokan-lite' ),
                'type'          => 'select',
                'description'   => __( 'Choose which format of email to send.', 'dokan-lite' ),
                'default'       => 'html',
                'class'         => 'email_type wc-enhanced-select',
                'options'       => $this->get_email_type_options(),
                'desc_tip'      => true,
            ),
        );
    }
}

```

</details>


### Source: [new-seller-registered.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/new-seller-registered.php)

SHA-256: `c21ba4812f04904a0c7185b58835c6eeca40b1903e09f342a170422c1573b1b3`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Hello there,
- A new vendor has registered in your marketplace
- Vendor Details:
- Vendor Store :
- To edit vendor access and details Click Here
- s settings. */ if ( $additional_content ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); } do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Seller Email.
 *
 * An email sent to the admin when a new vendor is registered.
 *
 * @class       Dokan_Email_New_Seller
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p>
    <?php esc_html_e( 'Hello there,', 'dokan-lite' ); ?>
    <br>
    <?php esc_html_e( 'A new vendor has registered in your marketplace ', 'dokan-lite' ); ?>
    <?php echo esc_html( $data['{site_title}'] ); ?>
</p>
<p>
    <?php esc_html_e( 'Vendor Details:', 'dokan-lite' ); ?>
    <hr>
</p>
<ul>
    <li>
        <strong>
            <?php esc_html_e( 'Vendor :', 'dokan-lite' ); ?>
        </strong>
        <?php printf( '<a href="%s">%s</a>', esc_url( $data['{seller_edit}'] ), esc_html( $data['{seller_name}'] ) ); ?>
    </li>
    <li>
        <strong>
            <?php esc_html_e( 'Vendor Store :', 'dokan-lite' ); ?>
        </strong>
        <?php printf( '<a href="%s">%s</a>', esc_url( $data['{store_url}'] ), esc_html( $data['{store_name}'] ) ); ?>
    </li>
</ul>
<p>
    <?php
    // translators: 1) seller edit url
    echo wp_kses_post( sprintf( __( 'To edit vendor access and details <a href="%s">Click Here</a>', 'dokan-lite' ), esc_url( $data['{seller_edit}'] ) ) );
    ?>
</p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [new-seller-registered.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/plain/new-seller-registered.php)

SHA-256: `7467e38f9c1cbda662d4aefb7583c116867f543b83a1e4baa321ef3b1797405f`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Hello there,
- A new vendor has registered in your marketplace
- Vendor Details:
- Vendor Store: %s
- To edit vendor access and details visit : %s
- s settings. */ if ( ! empty( $additional_content ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Seller Email ( plain text )
 *
 * An email sent to the admin when a new vendor is registered.
 *
 * @class       Dokan_Email_New_Seller
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_html_e( 'Hello there,', 'dokan-lite' );
echo " \n";

esc_html_e( 'A new vendor has registered in your marketplace  ', 'dokan-lite' );
echo " \n";

esc_html_e( 'Vendor Details:', 'dokan-lite' );
echo " \n";

echo "\n\n----------------------------------------\n\n";

// translators: 1) seller name
printf( esc_html__( 'Vendor: %s', 'dokan-lite' ), esc_html( $data['{seller_name}'] ) );
echo " \n";

// translators: 1) store name
printf( esc_html__( 'Vendor Store: %s', 'dokan-lite' ), esc_html( $data['{store_name}'] ) );
echo " \n";

// translators: 1) seller edit url
printf( esc_html__( 'To edit vendor access and details visit : %s', 'dokan-lite' ), esc_url( $data['{seller_edit}'] ) );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


### dokan-lite / includes\Emails\ProductPublished.php


### Source: [ProductPublished.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/includes/Emails/ProductPublished.php)

SHA-256: `7e408357bff7f15c5dc478d445601d5ac5a48d2de704c1bdeeccc74c42b1dfe4`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dokan Pending Product Published
- These emails are sent to vendor of the product when a pending product is published.
- [{site_title}] Your product - {product_title} - is now published
- {product_title} - is published
- Available placeholders: %s
- Enable this email notification
- Email heading
- Additional content
- Text to appear below the main email content.
- Email type
- Choose which format of email to send.
- email_type wc-enhanced-select

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php

namespace WeDevs\Dokan\Emails;

use WC_Email;
use WeDevs\Dokan\Vendor\Vendor;

/**
 * New Product Published Email to vendor.
 *
 * An email sent to the vendor when a pending Product is published by admin.
 *
 * @class       Dokan_Email_Product_Published
 * @version     2.6.8
 * @author      weDevs
 * @extends     WC_Email
 */
class ProductPublished extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'pending_product_published';
        $this->title          = __( 'Dokan Pending Product Published', 'dokan-lite' );
        $this->description    = __( 'These emails are sent to vendor of the product when a pending product is published.', 'dokan-lite' );
        $this->template_html  = 'emails/product-published.php';
        $this->template_plain = 'emails/plain/product-published.php';
        $this->template_base  = DOKAN_DIR . '/templates/';
        $this->placeholders   = [
            '{product_title}'     => '',
            '{price}'             => '',
            '{store_name}'        => '',
            '{product_url}'       => '',
            '{product_edit_link}' => '',
            // only for backward compatibility.
            '{site_name}'         => $this->get_from_name(),
            '{seller_name}'       => '',
        ];

        // Triggers for this email
        add_action( 'dokan_pending_product_published_notification', array( $this, 'trigger' ), 30, 2 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = 'vendor@ofthe.product';
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject() {
            return __( '[{site_title}] Your product - {product_title} - is now published', 'dokan-lite' );
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading() {
            return __( '{product_title} - is published', 'dokan-lite' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param \WP_Post $post The product as post.
     * @param \WP_User $seller.
     */
    public function trigger( $post, $seller ) {
		if ( ! $this->is_enabled() || ! $this->get_recipient() || ! $seller ) {
			return;
		}

        $product = wc_get_product( $post->ID );
        if ( ! $product ) {
            return;
        }
        $this->setup_locale();

        $product_edit_url = remove_query_arg( '_dokan_edit_product_nonce', dokan_edit_product_url( $post->ID ) );
        $product_edit_url = add_query_arg( '_view_mode', 'product_email', $product_edit_url );

        $this->object = $product;
        $seller = new Vendor( $seller );

        $this->placeholders['{product_title}']     = $product->get_title();
        $this->placeholders['{price}']             = dokan()->email->currency_symbol( $product->get_price() );
        $this->placeholders['{store_name}']        = $seller->get_shop_name();
        $this->placeholders['{product_url}']       = get_permalink( $post->ID );
        $this->placeholders['{product_edit_link}'] = $product_edit_url;
        $this->placeholders['{seller_name}']       = $seller->get_shop_name(); // Only for backward compatibility.

        $this->send( $seller->get_email(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html, array(
                'product'            => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
                'data'               => $this->placeholders,
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain, array(
                'product'            => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
                'data'               => $this->placeholders,
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        unset( $placeholders['{site_name}'], $placeholders['{seller_name}'] );
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan-lite' ), '<code>' . implode( '</code>, <code>', array_keys( $placeholders ) ) . '</code>' );
        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enable/Disable', 'dokan-lite' ),
                'type'          => 'checkbox',
                'label'         => __( 'Enable this email notification', 'dokan-lite' ),
                'default'       => 'yes',
            ),

            'subject' => array(
                'title'         => __( 'Subject', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_subject(),
                'default'       => '',
            ),
            'heading' => array(
                'title'         => __( 'Email heading', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_heading(),
                'default'       => '',
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'dokan-lite' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan-lite' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan-lite' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'         => __( 'Email type', 'dokan-lite' ),
                'type'          => 'select',
                'description'   => __( 'Choose which format of email to send.', 'dokan-lite' ),
                'default'       => 'html',
                'class'         => 'email_type wc-enhanced-select',
                'options'       => $this->get_email_type_options(),
                'desc_tip'      => true,
            ),
        );
    }
}

```

</details>


### Source: [product-published.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/product-published.php)

SHA-256: `32339789cf5a5a69ae92e1189e64e16d7f32873512109fd9cab07f938a1b3a3a`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your product : %2$s is approved by the admin, congrats!
- To Edit product click : here
- s settings. */ if ( $additional_content ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); } do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Product published Email.
 *
 * An email sent to the vendor when a new Product is published from pending.
 *
 * @class       Dokan_Email_Product_Published
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p>
    <?php
    // translators: 1) seller name
    printf( esc_html__( 'Hello %s', 'dokan-lite' ), esc_html( $data['{store_name}'] ) );
    ?>
</p>
<p>
    <?php
    // translators: 1) Product url 2) Product title
    echo wp_kses_post( sprintf( __( 'Your product : <a href="%1$s">%2$s</a> is approved by the admin, congrats!', 'dokan-lite' ), esc_html( $data['{product_url}'] ), esc_html( $data['{product_title}'] ) ) );
    ?>
</p>
<p>
    <?php
    // translators: 1) product edit link
    echo wp_kses_post( sprintf( __( 'To Edit product click : <a href="%s">here</a>', 'dokan-lite' ), esc_html( $data['{product_edit_link}'] ) ) );
    ?>
</p>
<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [product-published.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/plain/product-published.php)

SHA-256: `ec8d7e027d790c9bfd321cc80fd2967570cf3afc25f0a8419e8c2308221b39bd`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your product %s
- has been approved by one of our admin, congrats!
- View product : %s
- s settings. */ if ( ! empty( $additional_content ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Product published Email. ( plain text )
 *
 * An email sent to the vendor when a new Product is published from pending.
 *
 * @class       Dokan_Email_Product_Published
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: 1) Seller name
echo sprintf( esc_html__( 'Hello %s', 'dokan-lite' ), esc_html( $data['{store_name}'] ) );
echo " \n\n";

// translators: 1) product title
echo sprintf( esc_html__( 'Your product %s', 'dokan-lite' ), esc_html( $data['{product_title}'] ) );
echo esc_html__( ' has been approved by one of our admin, congrats!', 'dokan-lite' );
echo " \n\n";

// translators: 1) product url
echo sprintf( esc_html__( 'View product : %s', 'dokan-lite' ), esc_url( $data['{product_url}'] ) );
echo " \n\n";

// translators: 1) product edit url
echo sprintf( esc_html__( 'Update : %s', 'dokan-lite' ), esc_url( $data['{product_edit_link}'] ) );
echo " \n\n";

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


### dokan-lite / includes\Emails\ReverseWithdrawalInvoice.php


### Source: [ReverseWithdrawalInvoice.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/includes/Emails/ReverseWithdrawalInvoice.php)

SHA-256: `84aa68080f7d7e1cdf304fa8f7870e5ccc1d0ded1d265c824934e86b4f812b0f`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dokan Reverse Withdrawal Invoice
- These emails are sent to the vendor(s) who has a due reverse withdrawal payment based on settings.
- [{site_title}]: Your {month} invoice for store: {store_name} is available
- Your latest {site_title} invoice
- Thanks for using {site_url}!
- first day of last month
- Available placeholders: %s
- Enable this email notification
- Email heading
- Additional content
- Text to appear below the main email content.
- Email type
- Choose which format of email to send.
- email_type wc-enhanced-select

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php

namespace WeDevs\Dokan\Emails;

use WC_Email;
use WeDevs\Dokan\ReverseWithdrawal\Helper;
use WeDevs\Dokan\Vendor\Vendor;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Reverse Withdrawal Invoice Email.
 *
 * @since 3.5.1
 *
 * @extends WC_Email
 *
 * @package WeDevs\Dokan\Emails
 */
class ReverseWithdrawalInvoice extends WC_Email {

    /**
     * @var \WeDevs\Dokan\Vendor\Vendor|null
     *
     * @since 3.5.1
     */
    protected $seller_info;

    /**
     * @var array|null
     *
     * @since 3.5.1
     *
     * @see Helper::get_vendor_due_status()
     */
    protected $due_status;

    /**
     * Constructor.
     *
     * @since 3.5.1
     */
    public function __construct() {
        $this->id             = 'reverse_withdrawal_invoice';
        $this->title          = __( 'Dokan Reverse Withdrawal Invoice', 'dokan-lite' );
        $this->description    = __( 'These emails are sent to the vendor(s) who has a due reverse withdrawal payment based on settings.', 'dokan-lite' );
        $this->template_html  = 'emails/reverse-withdrawal-invoice.php';
        $this->template_plain = 'emails/plain/reverse-withdrawal-invoice.php';
        $this->template_base  = DOKAN_DIR . '/templates/';
        $this->placeholders   = [
            '{reverse_withdrawal_url}' => dokan_get_navigation_url( 'reverse-withdrawal' ),
            '{month}'                  => '',
            '{year}'                   => '',
            '{store_name}'             => '',
        ];

        add_action( 'dokan_reverse_withdrawal_invoice_email_sent', [ $this, 'trigger' ], 30, 2 );

        // Call parent constructor.
        parent::__construct();
    }

    /**
     * Get email subject.
     *
     * @since 3.5.1
     *
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}]: Your {month} invoice for store: {store_name} is available', 'dokan-lite' );
    }

    /**
     * Get email heading.
     *
     * @since 3.5.1
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'Your latest {site_title} invoice', 'dokan-lite' );
    }

    /**
     * Default content to show below main email content.
     *
     * @since 3.5.1
     *
     * @return string
     */
    public function get_default_additional_content() {
        return __( 'Thanks for using {site_url}!', 'dokan-lite' );
    }

    /**
     * Trigger this email.
     *
     * @since 3.5.1
     *
     * @param int $vendor_id
     * @param array $due_status
     *
     * @return void
     */
    public function trigger( $vendor_id, $due_status = null ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $this->seller_info = new Vendor( $vendor_id );

        if ( ! $this->seller_info->get_id() ) {
            return;
        }

        // get vendor due status
        if ( null === $due_status ) {
            $due_status = Helper::get_vendor_due_status( $vendor_id );
        }
        $this->setup_locale();

        $this->due_status = $due_status;

        // add placeholders
        $last_month = dokan_current_datetime()->modify( 'first day of last month' );

        $this->placeholders['{month}']      = $last_month->format( 'F' );
        $this->placeholders['{year}']       = $last_month->format( 'Y' );
        $this->placeholders['{store_name}'] = $this->seller_info->get_shop_name();

        if ( $this->get_recipient() !== 'unpaid_vendor@ofthe.site' ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        $this->restore_locale();
    }

    /**
     * Get vendor email address
     *
     * @since 3.5.1
     *
     * @return string|null
     */
    public function get_recipient() {
        return $this->seller_info && $this->seller_info->get_id() ? $this->seller_info->get_email() : 'unpaid_vendor@ofthe.site';
    }

    /**
     * Get content html.
     *
     * @since 3.5.1
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            [
                'due_status'         => $this->due_status,
                'seller_info'        => $this->seller_info,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'email'              => $this,
                'data'               => $this->placeholders,
            ],
            'dokan/', $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @since 3.5.1
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            [
                'due_status'         => $this->due_status,
                'seller_info'        => $this->seller_info,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => true,
                'email'              => $this,
                'data'               => $this->placeholders,
            ],
            'dokan/', $this->template_base
        );
    }

    /**
     * Initialize settings form fields.
     *
     * @since 3.5.1
     *
     * @return void
     */
    public function init_form_fields() {
        /* translators: %s: list of placeholders */
        $placeholder_text = sprintf( __( 'Available placeholders: %s', 'dokan-lite' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );

        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enable/Disable', 'dokan-lite' ),
                'type'          => 'checkbox',
                'label'         => __( 'Enable this email notification', 'dokan-lite' ),
                'default'       => 'yes',
            ),
            'subject' => array(
                'title'         => __( 'Subject', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_subject(),
                'default'       => '',
            ),
            'heading' => array(
                'title'         => __( 'Email heading', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_heading(),
                'default'       => '',
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'dokan-lite' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan-lite' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan-lite' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'         => __( 'Email type', 'dokan-lite' ),
                'type'          => 'select',
                'description'   => __( 'Choose which format of email to send.', 'dokan-lite' ),
                'default'       => 'html',
                'class'         => 'email_type wc-enhanced-select',
                'options'       => $this->get_email_type_options(),
                'desc_tip'      => true,
            ),
        );
    }
}

```

</details>


### Source: [reverse-withdrawal-invoice.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/reverse-withdrawal-invoice.php)

SHA-256: `e9ca570c765994c93af983bcd7e5d6b380d7958c6aab41afc8a776ac60fc8c51`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your %1$s %2$s invoice is now available for store: %3$s.
- Summary for %1$s:
- Reverse withdrawal charges for %1$s %2$s:
- Due Date:
- Pay Now
- s settings. */ if ( ! empty( $additional_content ) ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); } do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Monthly Reverse Withdrawal Invoice Template.
 *
 * Vendors Will get this email once in a month.
 *
 * @class ReverseWithdrawalInvoice
 *
 * @package \WeDevs\Dokan\Emails\ReverseWithdrawalInvoice
 *
 * @since 3.5.1
 *
 * @var $seller_info \WeDevs\Dokan\Vendor\Vendor
 * @var $due_status array
 * @var $data array
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
    <?php
    // translators: 1) store name
    printf( esc_html__( 'Hi %s,', 'dokan-lite' ), esc_html( $seller_info->get_shop_name() ) );
    ?>
</p>
<p>
    <?php
    printf(
        // translators: 1) invoice month 2) invoice year 3) store name
        esc_html__( 'Your %1$s %2$s invoice is now available for store: %3$s.', 'dokan-lite' ),
        esc_html( $data['{month}'] ), esc_html( $data['{year}'] ), esc_html( $seller_info->get_shop_name() )
    );
    ?>
</p>
<hr>
<ul>
    <li>
        <strong>
            <?php
            // translators: 1) store name
            printf( esc_html__( 'Summary for %1$s: ', 'dokan-lite' ), esc_html( $seller_info->get_shop_name() ) );
            ?>
        </strong>
    </li>
    <li>
        <strong>
            <?php
            printf(
                // translators: 1) invoice month 2) invoice year
                esc_html__( 'Reverse withdrawal charges for %1$s %2$s:', 'dokan-lite' ),
                esc_html( $data['{month}'] ), esc_html( $data['{year}'] )
            );
            ?>
        </strong>
        <?php echo wp_kses_post( wc_price( $due_status['balance']['payable_amount'] ) ); ?>
    </li>
    <li>
        <strong>
            <?php esc_html_e( 'Due Date: ', 'dokan-lite' ); ?>
        </strong>
        <?php echo 'immediate' === $due_status['due_date'] ? esc_html( ucfirst( $due_status['due_date'] ) ) : esc_html( dokan_format_date( $due_status['due_date'] ) ); ?>
    </li>
</ul>

<p>
    <?php
    printf(
        wp_kses(
            '<a href="' . $data['{reverse_withdrawal_url}'] . '">' . esc_html__( 'Pay Now', 'dokan-lite' ) . '</a>',
            array(
                'a' => array(
                    'href' => array(),
                ),
            )
        )
    );
    ?>
</p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [reverse-withdrawal-invoice.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/plain/reverse-withdrawal-invoice.php)

SHA-256: `d1cfffd84453693c26d81dec3c3d910b470df983abbb78f0c2a9c80a7f000164`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your %1$s %2$s invoice is now available for store: %3$s.
- Summary for %1$s:
- Reverse withdrawal charges for %1$s %2$s: %3$s
- Due Date %1$s:
- Pay Now
- s settings. */ if ( ! empty( $additional_content ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Monthly Reverse Withdrawal Invoice Template.
 *
 * Vendors Will get this email once in a month.
 *
 * @class   ReverseWithdrawalInvoice
 *
 * @since   3.5.1
 *
 * @package \WeDevs\Dokan\Emails\ReverseWithdrawalInvoice
 *
 * @var $seller_info \WeDevs\Dokan\Vendor\Vendor
 * @var $due_status  array
 * @var $data        array
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: 1) store name
printf( esc_html__( 'Hi %s,\n\n', 'dokan-lite' ), esc_html( $seller_info->get_shop_name() ) );

// translators: 1) invoice month 2) invoice year 3) store name
printf( esc_html__( 'Your %1$s %2$s invoice is now available for store: %3$s.\n\n', 'dokan-lite' ), esc_html( $data['{month}'] ), esc_html( $data['{year}'] ), esc_html( $seller_info->get_shop_name() ) );

// translators: 1) store name
printf( esc_html__( 'Summary for %1$s: \n\n', 'dokan-lite' ), esc_html( $seller_info->get_shop_name() ) );

// translators: 1) invoice month 2) invoice year 3) due balance amount
printf( esc_html__( 'Reverse withdrawal charges for %1$s %2$s: %3$s \n\n', 'dokan-lite' ), esc_html( $data['{month}'] ), esc_html( $data['{year}'] ), esc_html( wc_price( $due_status['balance']['payable_amount'] ) ) );

// translators: 1) invoice due date
printf( esc_html__( 'Due Date %1$s: \n\n', 'dokan-lite' ), 'immediate' === $due_status['due_date'] ? esc_html( ucfirst( $due_status['due_date'] ) ) : esc_html( dokan_format_date( $due_status['due_date'] ) ) );

printf(
    wp_kses(
        '<a href="' . $data['{reverse_withdrawal_url}'] . '">' . esc_html__( 'Pay Now', 'dokan-lite' ) . '</a>',
        [
            'a' => [
                'href' => [],
            ],
        ]
    )
);

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


### dokan-lite / includes\Emails\VendorCompletedOrder.php


### Source: [VendorCompletedOrder.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/includes/Emails/VendorCompletedOrder.php)

SHA-256: `065e80736596a204048ee426721ff33df487ca9c5f517a09ca02157d46a32c58`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dokan Vendor Completed Order
- Completed order emails are sent to the vendor when a order is completed.
- [{site_title}] Your customer order is now complete ({order_number}) - {order_date}
- Complete Customer Order: #{order_number}
- t need to process this from here. return; } $seller_info = new Vendor( $seller_id ); if ( ! $seller_info->get_id() ) { return; } $seller_email = $seller_info->get_email(); $this->order_info = dokan_get_vendor_order_details( $order_id ); $this->send( $seller_email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() ); $this->restore_locale(); } /** * Get content html. * * @access public * @return string */ public function get_content_html() { return wc_get_template_html( $this->template_html, array(
- , $this->template_base ); } /** * Get content plain. * * @access public * @return string */ public function get_content_plain() { return wc_get_template_html( $this->template_plain, array(
- , $this->template_base ); } /** * Initialise settings form fields. */ public function init_form_fields() { /* translators: %s: list of placeholders */ $placeholder_text = sprintf( __(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php

namespace WeDevs\Dokan\Emails;

use WC_Email;
use WeDevs\Dokan\Utilities\OrderUtil;
use WeDevs\Dokan\Vendor\Vendor;

/**
 * Completed Order Email.
 *
 * An email sent to the admin when a order is completed for.
 *
 * @class       VendorCompletedOrder
 * @version     3.2.2
 * @package     Dokan/Classes/Emails
 * @author      weDevs
 * @extends     WC_Email
 */
class VendorCompletedOrder extends WC_Email {
    public $order_info;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_vendor_completed_order';
        $this->title          = __( 'Dokan Vendor Completed Order', 'dokan-lite' );
        $this->description    = __( 'Completed order emails are sent to the vendor when a order is completed.', 'dokan-lite' );
        $this->template_html  = 'emails/vendor-completed-order.php';
        $this->template_plain = 'emails/plain/vendor-completed-order.php';
        $this->template_base  = DOKAN_DIR . '/templates/';
        $this->placeholders   = array(
            '{order_date}'   => '',
            '{order_number}' => '',
        );

        // Triggers for this email.
        add_action( 'woocommerce_order_status_completed_notification', array( $this, 'trigger' ), 10, 2 );
	    //Prevent admin email for sub-order
        add_filter( 'woocommerce_email_enabled_new_order', [ $this, 'prevent_sub_order_admin_email' ], 10, 2 );
        // Call parent constructor.
        parent::__construct();

        // Other settings.
        $this->recipient = 'vendor@ofthe.product';
    }

    /**
     * Get email subject.
     *
     * @since  3.2.2
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] Your customer order is now complete ({order_number}) - {order_date}', 'dokan-lite' );
    }

    /**
     * Get email heading.
     *
     * @since  3.2.2
     * @return string
     */
    public function get_default_heading() {
        return __( 'Complete Customer Order: #{order_number}', 'dokan-lite' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param int $order_id The Order ID.
     * @param array $order.
     */
    public function trigger( $order_id, $order = false ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
            $order = wc_get_order( $order_id );
        }

        if ( ! is_a( $order, 'WC_Order' ) ) {
            return;
        }

        $this->setup_locale();
        $this->object                         = $order;
        $this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
        $this->placeholders['{order_number}'] = $this->object->get_order_number();

        $seller_id = dokan_get_seller_id_by_order( $order_id );
        if ( empty( $seller_id ) ) {
            return;
        }

        // check has sub order
        if ( $order->get_meta( 'has_sub_order' ) ) {
            // same hook will be called again for sub-orders, so we don't need to process this from here.
            return;
        }
        $seller_info = new Vendor( $seller_id );
        if ( ! $seller_info->get_id() ) {
            return;
        }
        $seller_email     = $seller_info->get_email();
        $this->order_info = dokan_get_vendor_order_details( $order_id );
        $this->send( $seller_email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html, array(
                'order'                 => $this->object,
                'email_heading'         => $this->get_heading(),
                'additional_content'    => $this->get_additional_content(),
                'sent_to_admin'         => true,
                'plain_text'            => false,
                'email'                 => $this,
                'order_info'            => $this->order_info,
                'show_customer_details' => OrderUtil::should_show_email_customer_details( $this->object ),
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain, array(
                'order'                 => $this->object,
                'email_heading'         => $this->get_heading(),
                'additional_content'    => $this->get_additional_content(),
                'sent_to_admin'         => true,
                'plain_text'            => true,
                'email'                 => $this,
                'order_info'            => $this->order_info,
                'show_customer_details' => OrderUtil::should_show_email_customer_details( $this->object ),
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        /* translators: %s: list of placeholders */
        $placeholder_text = sprintf( __( 'Available placeholders: %s', 'dokan-lite' ), '<code>' . esc_html( implode( '</code>, <code>', array_keys( $this->placeholders ) ) ) . '</code>' );
        $this->form_fields = array(
            'enabled'    => array(
                'title'   => __( 'Enable/Disable', 'dokan-lite' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'dokan-lite' ),
                'default' => 'yes',
            ),
            'subject'    => array(
                'title'       => __( 'Subject', 'dokan-lite' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ),
            'heading'    => array(
                'title'       => __( 'Email heading', 'dokan-lite' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'dokan-lite' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan-lite' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan-lite' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'       => __( 'Email type', 'dokan-lite' ),
                'type'        => 'select',
                'description' => __( 'Choose which format of email to send.', 'dokan-lite' ),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Prevent sub-order email for admin
     *
     * @param $bool
     * @param $order
     *
     * @return bool
     */
    public function prevent_sub_order_admin_email( $bool, $order ) {
        if ( ! $order ) {
            return $bool;
        }

        if ( $order->get_parent_id() ) {
            return false;
        }

        return $bool;
    }
}

```

</details>


### Source: [vendor-completed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/vendor-completed-order.php)

SHA-256: `7331b7a9a095ae1b1a9a5cb6493e08d03ead7e2cdfa7678fb6d250e899db2567`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- You have received complete order from %s.
- width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;
- s settings. */ if ( $additional_content ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); } /** * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Completed Order Email.
 *
 * An email sent to the vendor when a order is completed.
 *
 * @class       VendorCompletedOrder
 * @version     3.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$text_align = is_rtl() ? 'right' : 'left';

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );

$order_url = esc_url(
    add_query_arg(
        [
            'order_id'   => $order->get_order_number(),
            '_view_mode' => 'email',
            'permission' => '1',
        ], dokan_get_navigation_url( 'orders' )
    )
);

?>

    <p>
        <?php
        // translators: 1) order billing full name
        printf( esc_html__( 'You have received complete order from %s.', 'dokan-lite' ), esc_html( $order->get_formatted_billing_full_name() ) );
        ?>
    </p>

    <h2>
        <?php
        $before = '<a class="link" href="' . esc_url( $order_url ) . '">';
        $after  = '</a>';
        /* translators: %s: Order ID. */
        echo wp_kses_post( $before . sprintf( __( '[Order #%s]', 'dokan-lite' ) . $after . ' (<time datetime="%s">%s</time>)', $order->get_order_number(), $order->get_date_created()->format( 'c' ), wc_format_datetime( $order->get_date_created() ) ) );
        ?>
    </h2>

    <div style="margin-bottom: 40px;">
        <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
            <thead>
            <tr>
                <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Product', 'dokan-lite' ); ?></th>
                <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Quantity', 'dokan-lite' ); ?></th>
                <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Price', 'dokan-lite' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            echo wc_get_email_order_items( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                $order,
                [
                    'show_sku'      => $sent_to_admin,
                    'show_image'    => false,
                    'image_size'    => [ 32, 32 ],
                    'plain_text'    => $plain_text,
                    'sent_to_admin' => $sent_to_admin,
                ]
            );
            ?>
            </tbody>
            <tfoot>
            <?php
            $item_totals = $order->get_order_item_totals();

            if ( $item_totals ) {
                $i = 0;
                foreach ( $item_totals as $total ) {
                    ++$i;
                    ?>
                    <tr>
                        <th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $total['label'] ); ?></th>
                        <td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $total['value'] ); ?></td>
                    </tr>
                    <?php
                }
            }
            if ( $order->get_customer_note() ) {
                ?>
                <tr>
                    <th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Note:', 'dokan-lite' ); ?></th>
                    <td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
                </tr>
                <?php
            }
            ?>
            </tfoot>
        </table>
    </div>

<?php
do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

if ( $show_customer_details ) {
    /**
     * @hooked WC_Emails::customer_details() Shows customer details
     * @hooked WC_Emails::email_address() Shows email address
     */
    do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
}

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [vendor-completed-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/plain/vendor-completed-order.php)

SHA-256: `02cbfccb0f9a7c9425c551cd78f3591612c74d0ce9d8c7b68c6ad7c98adba665`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- You have received complete order from %s.
- s settings. */ if ( ! empty( $additional_content ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Completed Order Email ( plain text )
 *
 * An email sent to the vendor when a order is completed.
 *
 * @class       VendorCompletedOrder
 * @version     3.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: 1) order billing full name
echo sprintf( esc_html__( 'You have received complete order from %s.', 'dokan-lite' ), esc_html( $order->get_formatted_billing_full_name() ) ) . "\n\n";
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_html_e( 'Product            | Quantity        | Price', 'dokan-lite' );
echo "\n\n";
echo wp_kses_post(
    wc_get_email_order_items(
        $order,
        [
            'show_sku'      => $sent_to_admin,
            'show_image'    => false,
            'image_size'    => [ 32, 32 ],
            'plain_text'    => $plain_text,
            'sent_to_admin' => $sent_to_admin,
        ]
    )
);
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
$item_totals = $order->get_order_item_totals();

if ( $item_totals ) {
    foreach ( $item_totals as $total ) {
        printf( '%s %s %s', esc_html( wp_strip_all_tags( wptexturize( $total['label'] ) ) ), esc_html( wp_strip_all_tags( wptexturize( $total['value'] ) ) ), "\n" );
    }
    echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}
if ( $order->get_customer_note() ) {
    esc_html_e( 'Note:', 'dokan-lite' );
    echo "\n\n";
    echo wp_kses_post( wp_strip_all_tags( wptexturize( $order->get_customer_note() ) ) );
    echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}
do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

if ( $show_customer_details ) {
    /**
     * @hooked WC_Emails::customer_details() Shows customer details
     * @hooked WC_Emails::email_address() Shows email address
     */
    do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
}

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


### dokan-lite / includes\Emails\VendorNewOrder.php


### Source: [VendorNewOrder.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/includes/Emails/VendorNewOrder.php)

SHA-256: `70f9ad8aadb87bba876655a63d1c9e472c3f5a35e8ebdab6fc748915c932e109`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dokan Vendor New Order
- New order emails are sent to the vendor when a new order is received.
- [{site_title}] New customer order ({order_number}) - {order_date}
- New Customer Order: #{order_number}
- t need to process this from here. return; } else { $seller_info = get_userdata( $seller_id ); if ( ! $seller_info ) { return; } $seller_email = $seller_info->user_email; $this->order_info = dokan_get_vendor_order_details( $order_id ); $this->send( $seller_email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() ); } $this->restore_locale(); } /** * Get content html. * * @access public * @return string */ public function get_content_html() { return wc_get_template_html( $this->template_html, array(
- , $this->template_base ); } /** * Get content plain. * * @access public * @return string */ public function get_content_plain() { return wc_get_template_html( $this->template_plain, array(
- , $this->template_base ); } /** * Initialise settings form fields. */ public function init_form_fields() { /* translators: %s: list of placeholders */ $placeholder_text = sprintf( __(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php

namespace WeDevs\Dokan\Emails;

use WC_Email;
use WeDevs\Dokan\Utilities\OrderUtil;

/**
 * New Order Email.
 *
 * An email sent to the admin when a new order is received/paid for.
 *
 * @class       VendorNewOrder
 * @version     2.6.8
 * @package     Dokan/Classes/Emails
 * @author      weDevs
 * @extends     WC_Email
 */
class VendorNewOrder extends WC_Email {
    public $order_info;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_vendor_new_order';
        $this->title          = __( 'Dokan Vendor New Order', 'dokan-lite' );
        $this->description    = __( 'New order emails are sent to the vendor when a new order is received.', 'dokan-lite' );
        $this->template_html  = 'emails/vendor-new-order.php';
        $this->template_plain = 'emails/plain/vendor-new-order.php';
        $this->template_base  = DOKAN_DIR . '/templates/';
        $this->placeholders   = array(
            '{order_date}'   => '',
            '{order_number}' => '',
        );

        // Triggers for this email.
        add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ), 10, 2 );
        add_action( 'woocommerce_order_status_pending_to_completed_notification', array( $this, 'trigger' ), 10, 2 );
        add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
        add_action( 'woocommerce_order_status_failed_to_processing_notification', array( $this, 'trigger' ), 10, 2 );
        add_action( 'woocommerce_order_status_failed_to_completed_notification', array( $this, 'trigger' ), 10, 2 );
        add_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
        add_action( 'woocommerce_order_status_cancelled_to_processing_notification', array( $this, 'trigger' ), 10, 2 );
        add_action( 'woocommerce_order_status_cancelled_to_completed_notification', array( $this, 'trigger' ), 10, 2 );
        add_action( 'woocommerce_order_status_cancelled_to_on-hold_notification', array( $this, 'trigger' ), 10, 2 );
		//Prevent admin email for sub-order
        add_filter( 'woocommerce_email_enabled_new_order', [ $this, 'prevent_sub_order_admin_email' ], 10, 2 );
        // Call parent constructor.
        parent::__construct();

        // Other settings.
        $this->recipient = 'vendor@ofthe.product';
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] New customer order ({order_number}) - {order_date}', 'dokan-lite' );
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading() {
        return __( 'New Customer Order: #{order_number}', 'dokan-lite' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param int $order_id The Order ID.
     * @param array $order.
     */
    public function trigger( $order_id, $order = false ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $this->setup_locale();
        if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
            $order = wc_get_order( $order_id );
        }

        if ( ! is_a( $order, 'WC_Order' ) ) {
            return;
        }

        $this->object                         = $order;
        $this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
        $this->placeholders['{order_number}'] = $this->object->get_order_number();

        $seller_id = dokan_get_seller_id_by_order( $order_id );
        if ( empty( $seller_id ) ) {
            return;
        }

        // check has sub order
        if ( $order->get_meta( 'has_sub_order' ) ) {
            // same hook will be called again for sub-orders, so we don't need to process this from here.
            return;
        } else {
            $seller_info = get_userdata( $seller_id );
            if ( ! $seller_info ) {
                return;
            }
            $seller_email     = $seller_info->user_email;
            $this->order_info = dokan_get_vendor_order_details( $order_id );
            $this->send( $seller_email, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }
        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html, array(
                'order'                 => $this->object,
                'email_heading'         => $this->get_heading(),
                'additional_content'    => $this->get_additional_content(),
                'sent_to_admin'         => true,
                'plain_text'            => false,
                'email'                 => $this,
                'order_info'            => $this->order_info,
                'show_customer_details' => OrderUtil::should_show_email_customer_details( $this->object ),
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain, array(
                'order'                 => $this->object,
                'email_heading'         => $this->get_heading(),
                'additional_content'    => $this->get_additional_content(),
                'sent_to_admin'         => true,
                'plain_text'            => true,
                'email'                 => $this,
                'order_info'            => $this->order_info,
                'show_customer_details' => OrderUtil::should_show_email_customer_details( $this->object ),
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan-lite' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );
        $this->form_fields = array(
            'enabled'    => array(
                'title'   => __( 'Enable/Disable', 'dokan-lite' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'dokan-lite' ),
                'default' => 'yes',
            ),
            'subject'    => array(
                'title'       => __( 'Subject', 'dokan-lite' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ),
            'heading'    => array(
                'title'       => __( 'Email heading', 'dokan-lite' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'dokan-lite' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan-lite' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan-lite' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'       => __( 'Email type', 'dokan-lite' ),
                'type'        => 'select',
                'description' => __( 'Choose which format of email to send.', 'dokan-lite' ),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Prevent sub-order email for admin
     *
     * @param $bool
     * @param $order
     *
     * @return bool
     */
    public function prevent_sub_order_admin_email( $bool, $order ) {
        if ( ! $order ) {
            return $bool;
        }

        if ( $order->get_parent_id() ) {
            return false;
        }

        return $bool;
    }
}

```

</details>


### Source: [vendor-new-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/vendor-new-order.php)

SHA-256: `7d462c58b8294ee8b19239f910c9ddf1f8f816a446cd60002e4bf3dfebe74f2d`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- You have received an order from %s.
- width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;
- s settings. */ if ( $additional_content ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); } /** * @hooked WC_Emails::email_footer() Output the email footer */ do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Order Email.
 *
 * An email sent to the vendor when a new order is created by customer.
 *
 * @class       VendorNewOrder
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$text_align = is_rtl() ? 'right' : 'left';

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email );

$order_url = esc_url(
    add_query_arg(
        [
            'order_id'   => $order->get_order_number(),
            '_view_mode' => 'email',
            'permission' => '1',
        ], dokan_get_navigation_url( 'orders' )
    )
);

?>
    <p>
        <?php
        // translators: 1) order formatted billing full name
        printf( esc_html__( 'You have received an order from %s.', 'dokan-lite' ), esc_html( $order->get_formatted_billing_full_name() ) );
        ?>
    </p>

    <h2>
        <?php
        $before = '<a class="link" href="' . esc_url( $order_url ) . '">';
        $after  = '</a>';
        /* translators: %s: Order ID. */
        echo wp_kses_post( $before . sprintf( __( '[Order #%s]', 'dokan-lite' ) . $after . ' (<time datetime="%s">%s</time>)', $order->get_order_number(), $order->get_date_created()->format( 'c' ), wc_format_datetime( $order->get_date_created() ) ) );
        ?>
    </h2>

    <div style="margin-bottom: 40px;">
        <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
            <thead>
            <tr>
                <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Product', 'dokan-lite' ); ?></th>
                <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Quantity', 'dokan-lite' ); ?></th>
                <th class="td" scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Price', 'dokan-lite' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            echo wp_kses_post(
                wc_get_email_order_items(
                    $order,
                    [
                        'show_sku'      => $sent_to_admin,
                        'show_image'    => false,
                        'image_size'    => [ 32, 32 ],
                        'plain_text'    => $plain_text,
                        'sent_to_admin' => $sent_to_admin,
                    ]
                )
            );
            ?>
            </tbody>
            <tfoot>
            <?php
            $item_totals = $order->get_order_item_totals();

            if ( $item_totals ) {
                $i = 0;
                foreach ( $item_totals as $total ) {
                    ++$i;
                    ?>
                    <tr>
                        <th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $total['label'] ); ?></th>
                        <td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo ( 1 === $i ) ? 'border-top-width: 4px;' : ''; ?>"><?php echo wp_kses_post( $total['value'] ); ?></td>
                    </tr>
                    <?php
                }
            }
            if ( $order->get_customer_note() ) {
                ?>
                <tr>
                    <th class="td" scope="row" colspan="2" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php esc_html_e( 'Note:', 'dokan-lite' ); ?></th>
                    <td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>;"><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
                </tr>
                <?php
            }
            ?>
            </tfoot>
        </table>
    </div>

<?php
do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

if ( $show_customer_details ) {
    /**
     * @hooked WC_Emails::customer_details() Shows customer details
     * @hooked WC_Emails::email_address() Shows email address
     */
    do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
}

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [vendor-new-order.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/plain/vendor-new-order.php)

SHA-256: `1014833c0022aa68ca09641a92e9a972d348d73f285d18af720ea437a1918ab6`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- You have received an order from %s.
- s settings. */ if ( ! empty( $additional_content ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Order Email ( plain text )
 *
 * An email sent to the vendor when a new order is created by customer.
 *
 * @class       VendorNewOrder
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: 1) order billing full name
echo sprintf( esc_html__( 'You have received an order from %s.', 'dokan-lite' ), esc_html( $order->get_formatted_billing_full_name() ) ) . "\n\n";
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_html_e( 'Product            | Quantity        | Price', 'dokan-lite' );
echo "\n\n";
echo wp_kses_post(
    wc_get_email_order_items(
        $order,
        [
            'show_sku'      => $sent_to_admin,
            'show_image'    => false,
            'image_size'    => [ 32, 32 ],
            'plain_text'    => $plain_text,
            'sent_to_admin' => $sent_to_admin,
        ]
    )
);
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
$item_totals = $order->get_order_item_totals();

if ( $item_totals ) {
    foreach ( $item_totals as $total ) {
        printf( '%s %s %s', esc_html( wp_strip_all_tags( wptexturize( $total['label'] ) ) ), esc_html( wp_strip_all_tags( wptexturize( $total['value'] ) ) ), "\n" );
    }
    echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}
if ( $order->get_customer_note() ) {
    esc_html_e( 'Note:', 'dokan-lite' );
    echo "\n\n";
    echo wp_kses_post( wp_strip_all_tags( wptexturize( $order->get_customer_note() ) ) );
    echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
}
do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

if ( $show_customer_details ) {
    /**
     * @hooked WC_Emails::customer_details() Shows customer details
     * @hooked WC_Emails::email_address() Shows email address
     */
    do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
}

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


### dokan-lite / includes\Emails\VendorProductReview.php


### Source: [VendorProductReview.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/includes/Emails/VendorProductReview.php)

SHA-256: `271fc5682a82b8f6e74fb09d5bc70526ca4e507043232ce73164fafd88a2cfdb`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dokan Vendor Product Review
- After a product has been reviewed, an email is sent to the vendor containing information about the review. The email may include details such as the reviewer’s name, the product’s name and description, the review rating, and the review text. The email may also contain a link to the review page where the vendor can view the review and respond to it if necessary.
- New Product Review Alert from {site_title}
- New Product Review Alert From Your Store: {store_name} at - {site_title}
- Available placeholders: %s
- Enable this email notification
- Email heading
- Additional content
- Text to appear below the main email content.
- Email type
- Choose which format of email to send.
- email_type wc-enhanced-select

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php

namespace WeDevs\Dokan\Emails;

use WC_Email;

/**
 * Send email to vendor when a product is reviewed
 *
 * @since       3.9.2
 *
 * @class       Dokan_Email_Vendor_Product_Review
 *
 * @author      weDevs
 *
 * @extends     WC_Email
 */
class VendorProductReview extends WC_Email {

    /**
     * Reply email
     *
     * @since 3.9.2
     *
     * @var string
     */
    private $from_email;

    /**
     * Constructor.
     *
     * @since 3.9.2
     */
    public function __construct() {
        $this->id             = 'dokan_product_review';
        $this->title          = __( 'Dokan Vendor Product Review', 'dokan-lite' );
        $this->description    = __( 'After a product has been reviewed, an email is sent to the vendor containing information about the review. The email may include details such as the reviewer’s name, the product’s name and description, the review rating, and the review text. The email may also contain a link to the review page where the vendor can view the review and respond to it if necessary.', 'dokan-lite' );
        $this->template_html  = 'emails/vendor-product-review.php';
        $this->template_plain = 'emails/plain/vendor-product-review.php';
        $this->template_base  = DOKAN_DIR . '/templates/';
        $this->placeholders   = [
            '{store_name}'     => '',
            '{product_name}'   => '',
            '{customer_name}'  => '',
            '{rating}'         => '',
            '{review_text}'    => '',
            '{review_link}'    => '',
        ];

        // Triggers for this email
        add_action( 'wp_set_comment_status', [ $this, 'trigger' ], 35, 1 );
        add_action( 'comment_post', [ $this, 'trigger' ], 35, 1 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = 'vendor@ofthe.product';
    }

    /**
     * Get the email subject.
     *
     * @since  3.9.2
     *
     * @return string
     */
    public function get_default_subject() {
        return __( 'New Product Review Alert from {site_title}', 'dokan-lite' );
    }

    /**
     * Get email heading.
     *
     * @since  3.9.2
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'New Product Review Alert From Your Store: {store_name} at - {site_title}', 'dokan-lite' );
    }

    /**
     * Trigger this email.
     *
     * @since 3.9.2
     *
     * @param int $comment_id
     *
     * @return void
     */
    public function trigger( $comment_id ) {
        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }

        $comment = get_comment( $comment_id );
        if ( ! $comment ) {
            // the comment does not exist
            return;
        }

        $product = wc_get_product( $comment->comment_post_ID );
        if ( ! $product ) {
            // the review is not for a product
            return;
        }

        $this->setup_locale();

        $this->from_email = get_option( 'admin_email' );

        // get the vendor
        $seller = dokan_get_vendor_by_product( $product->get_id() );

        $this->placeholders['{store_name}']     = $seller->get_shop_name();
        $this->placeholders['{product_name}']   = $product->get_title();
        $this->placeholders['{customer_name}']  = $comment->comment_author;
        $this->placeholders['{rating}']         = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
        $this->placeholders['{review_text}']    = wp_specialchars_decode( $comment->comment_content );
        $this->placeholders['{review_link}']    = get_comment_link( $comment );
        $this->send( $seller->get_email(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

    /**
     * Get the from address for outgoing emails.
     *
     * @since 3.9.2
     *
     * @return string
     */
    public function get_from_address( $from_email = '' ) {
        return $this->from_email;
    }

    /**
     * Get content html.
     *
     * @since 3.9.2
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            [
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => false,
                'email'              => $this,
                'data'               => $this->placeholders,
            ],
            'dokan/', $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @since 3.9.2
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            [
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => false,
                'plain_text'         => true,
                'email'              => $this,
                'data'               => $this->placeholders,
            ],
            'dokan/', $this->template_base
        );
    }

    /**
     * Initialize settings form fields.
     *
     * @since 3.9.2
     */
    public function init_form_fields() {
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan-lite' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );
        $this->form_fields = [
            'enabled' => [
                'title'   => __( 'Enable/Disable', 'dokan-lite' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'dokan-lite' ),
                'default' => 'yes',
            ],

            'subject'            => [
                'title'       => __( 'Subject', 'dokan-lite' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => $this->get_default_subject(),
            ],
            'heading'            => [
                'title'       => __( 'Email heading', 'dokan-lite' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_heading(),
                'default'     => $this->get_default_heading(),
            ],
            'additional_content' => [
                'title'       => __( 'Additional content', 'dokan-lite' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan-lite' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan-lite' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ],
            'email_type'         => [
                'title'       => __( 'Email type', 'dokan-lite' ),
                'type'        => 'select',
                'description' => __( 'Choose which format of email to send.', 'dokan-lite' ),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
                'desc_tip'    => true,
            ],
        ];
    }
}

```

</details>


### Source: [vendor-product-review.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/vendor-product-review.php)

SHA-256: `48553db523d9ad8d98406349ed2ba362da250768dddc4041f2cf4d41a7352251`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Hello there,
- We are happy to inform you that your product %1$s has received a new review on our website. The review was written by %2$s and has a rating of %3$s out of 5 stars.
- The review text is as follows:
- You can view the review by visiting the following link:
- We appreciate your participation in our platform and hope that you will continue to offer quality products and services to our customers.
- Thank you for your attention.
- s settings. */ if ( $additional_content ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); } do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Product Review Email
 *
 * After a product has been reviewed, an email is sent to the vendor containing information about the review.
 * The email may include details such as the reviewer’s name, the product’s name and description, the review rating, and the review text.
 * The email may also contain a link to the review page where the vendor can view the review and respond to it if necessary.
 *
 * @class     \WeDevs\Dokan\Emails\VendorProductReview
 * @since     3.9.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

    <p><?php esc_attr_e( 'Hello there,', 'dokan-lite' ); ?></p>

    <p>
        <?php
        printf(
            wp_kses_post(
            // translators: 1) product name, 2) customer name, 3) rating
                __( 'We are happy to inform you that your product <strong>%1$s</strong> has received a new review on our website. The review was written by <strong>%2$s</strong> and has a rating of <strong>%3$s</strong> out of 5 stars.', 'dokan-lite' )
            ),
            esc_html( $data['{product_name}'] ),
            esc_html( $data['{customer_name}'] ),
            esc_html( $data['{rating}'] )
        );
        ?>
    </p>

    <p><?php esc_html_e( 'The review text is as follows:', 'dokan-lite' ); ?></p>
    <hr>
    <p><?php echo wp_kses_post( $data['{review_text}'] ); ?></p>
    <hr>
    <p><?php esc_html_e( 'You can view the review by visiting the following link:', 'dokan-lite' ); ?></p>
    <p>
        <?php
        printf(
            '<a href="%1$s">%2$s</a>',
            esc_url( $data['{review_link}'] ),
            esc_html( $data['{product_name}'] )
        );
        ?>
    </p>
    <hr>
    <p><?php esc_html_e( 'We appreciate your participation in our platform and hope that you will continue to offer quality products and services to our customers.', 'dokan-lite' ); ?></p>
    <p><?php esc_html_e( 'Thank you for your attention.', 'dokan-lite' ); ?></p>
<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [vendor-product-review.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/plain/vendor-product-review.php)

SHA-256: `7e830675602543cb6068fd03ba3dcf5c1fb2ca07f7af728008774f2132adcbaf`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Hello there,
- We are happy to inform you that your product %1$s has received a new review on our website. The review was written by %2$s and has a rating of %3$s out of 5 stars.
- The review text is as follows:
- You can view the review by visiting the following link:
- We appreciate your participation in our platform and hope that you will continue to offer quality products and services to our customers.
- Thank you for your attention.
- s settings. */ if ( ! empty( $additional_content ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Product Review Email (plain text)
 *
 * After a product has been reviewed, an email is sent to the vendor containing information about the review.
 * The email may include details such as the reviewer’s name, the product’s name and description, the review rating, and the review text.
 * The email may also contain a link to the review page where the vendor can view the review and respond to it if necessary.
 *
 * @class    \WeDevs\Dokan\Emails\VendorProductReview
 *
 * @since    3.9.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_html_e( 'Hello there,', 'dokan-lite' );
echo " \n\n";

printf(
// translators: 1) product name, 2) customer name, 3) rating
    esc_html__( 'We are happy to inform you that your product %1$s has received a new review on our website. The review was written by %2$s and has a rating of %3$s out of 5 stars.', 'dokan-lite' ),
    esc_html( $data['{product_name}'] ),
    esc_html( $data['{customer_name}'] ),
    esc_html( $data['{rating}'] )
);
echo " \n\n";

esc_html_e( 'The review text is as follows:', 'dokan-lite' );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html( wp_strip_all_tags( wptexturize( $data['{review_text}'] ) ) );

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_html_e( 'You can view the review by visiting the following link:', 'dokan-lite' );

printf(
    ' %s',
    esc_url( $data['{review_link}'] )
);

echo "\n\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_html_e( 'We appreciate your participation in our platform and hope that you will continue to offer quality products and services to our customers.', 'dokan-lite' );

echo "\n\n";

esc_html_e( 'Thank you for your attention.', 'dokan-lite' );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


### dokan-lite / includes\Emails\VendorWithdrawRequest.php


### Source: [VendorWithdrawRequest.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/includes/Emails/VendorWithdrawRequest.php)

SHA-256: `aca63f1d6cf149513c6f97a007a4e2fb034dae483b1ec287a05567cb8ddcd4b7`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dokan New Withdrawal Request
- These emails are sent to chosen recipient(s) when a vendor send request to withdraw
- [{site_title}] A New withdrawal request is made by {store_name}
- New Withdraw Request from - {store_name}
- Available placeholders: %s
- Enable this email notification
- Enter recipients (comma separated) for this email. Defaults to %s.
- Email heading
- Additional content
- Text to appear below the main email content.
- Email type
- Choose which format of email to send.
- email_type wc-enhanced-select

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php

namespace WeDevs\Dokan\Emails;

use WC_Email;
use WeDevs\Dokan\Vendor\Vendor;

/**
 * New Product Email.
 *
 * An email sent to the admin when a new Product is created by vendor.
 *
 * @class       Dokan_Vendor_Withdraw_Request
 * @version     2.6.8
 * @author      weDevs
 * @extends     WC_Email
 */
class VendorWithdrawRequest extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_vendor_withdraw_request';
        $this->title          = __( 'Dokan New Withdrawal Request', 'dokan-lite' );
        $this->description    = __( 'These emails are sent to chosen recipient(s) when a vendor send request to withdraw', 'dokan-lite' );
        $this->template_html  = 'emails/withdraw-new.php';
        $this->template_plain = 'emails/plain/withdraw-new.php';
        $this->template_base  = DOKAN_DIR . '/templates/';
        $this->placeholders   = [
            '{store_name}'    => '',
            '{amount}'        => '',
            '{charge}'        => '',
            '{method}'        => '',
            '{profile_url}'   => '',
            '{withdraw_page}' => '',
            // Only for backward compatibility.
            '{user_name}'     => '',
            '{site_name}'     => $this->get_from_name(),
        ];

        // Triggers for this email
        add_action( 'dokan_withdraw_created', array( $this, 'trigger' ), 30, 1 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] A New withdrawal request is made by {store_name}', 'dokan-lite' );
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading() {
        return __( 'New Withdraw Request from - {store_name}', 'dokan-lite' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param int    $user_id User ID.
     * @param mixed  $amount  Withdrawal amount.
     * @param string $method  Withdrawal method.
     * @param int    $id      Withdrawal id,
     */
    public function trigger( $withdraw ) {
        $user_id = $withdraw->get_user_id();
        $amount  = $withdraw->get_amount();
        $method  = $withdraw->get_method();
        $id      = $withdraw->get_id();
        $seller = new Vendor( $user_id );

        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }

        $withdraw = dokan()->withdraw->get( $id );

        $this->setup_locale();
        $this->object                          = $seller;
        $this->placeholders['{store_name}']    = $seller->get_shop_name();
        $this->placeholders['{amount}']        = dokan()->email->currency_symbol( $amount );
        $this->placeholders['{charge}']        = dokan()->email->currency_symbol( $withdraw->get_charge() );
        $this->placeholders['{method}']        = dokan_withdraw_get_method_title( $method );
        $this->placeholders['{profile_url}']   = $seller->get_profile_url();
        $this->placeholders['{withdraw_page}'] = admin_url( 'admin.php?page=dokan#/withdraw?status=pending' );
        $this->placeholders['{user_name}']     = $seller->get_shop_name(); // for backward compatibility.

        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

        /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html, array(
                'seller'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
                'data'               => $this->placeholders,
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain, array(
                'seller'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
                'data'               => $this->placeholders,
            ), 'dokan/', $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        unset( $placeholders['{site_name}'], $placeholders['{user_name}'] );
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan-lite' ), '<code>' . implode( '</code>, <code>', array_keys( $placeholders ) ) . '</code>' );
        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enable/Disable', 'dokan-lite' ),
                'type'          => 'checkbox',
                'label'         => __( 'Enable this email notification', 'dokan-lite' ),
                'default'       => 'yes',
            ),
            'recipient' => array(
                'title'         => __( 'Recipient(s)', 'dokan-lite' ),
                'type'          => 'text',
                // translators: 1) Email recipients
                'description'   => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'dokan-lite' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
                'placeholder'   => '',
                'default'       => '',
                'desc_tip'      => true,
            ),
            'subject' => array(
                'title'         => __( 'Subject', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_subject(),
                'default'       => '',
            ),
            'heading' => array(
                'title'         => __( 'Email heading', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_heading(),
                'default'       => '',
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'dokan-lite' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan-lite' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan-lite' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'         => __( 'Email type', 'dokan-lite' ),
                'type'          => 'select',
                'description'   => __( 'Choose which format of email to send.', 'dokan-lite' ),
                'default'       => 'html',
                'class'         => 'email_type wc-enhanced-select',
                'options'       => $this->get_email_type_options(),
                'desc_tip'      => true,
            ),
        );
    }
}

```

</details>


### Source: [withdraw-new.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/withdraw-new.php)

SHA-256: `3be19ed399d7207566ed9b7a838d14ff99ecf455f7a97e41e8ff76b2436059d4`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- A new withdraw request has been made by
- Store Name :
- Request Amount:
- Payment Method:
- You can approve or deny it by going here
- s settings. */ if ( $additional_content ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); } do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Withdraw request Email.
 *
 * An email sent to the admin when a new withdraw request is created by vendor.
 *
 * @class       Dokan_Vendor_Withdraw_Request
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p>
    <?php esc_html_e( 'Hi,', 'dokan-lite' ); ?>
</p>
<p>
    <?php esc_html_e( 'A new withdraw request has been made by', 'dokan-lite' ); ?> <?php echo esc_html( $data ['{store_name}'] ); ?>.
</p>
<hr>
<ul>
    <li>
        <strong>
            <?php esc_html_e( 'Store Name : ', 'dokan-lite' ); ?>
        </strong>
        <?php
        printf( '<a href="%s">%s</a>', esc_url( $data['{profile_url}'] ), esc_html( $data['{store_name}'] ) );
        ?>
    </li>
    <li>
        <strong>
            <?php esc_html_e( 'Request Amount:', 'dokan-lite' ); ?>
        </strong>
        <?php echo wp_kses_post( $data['{amount}'] ); ?>
    </li>
    <li>
        <strong>
            <?php esc_html_e( 'Charge:', 'dokan-lite' ); ?>
        </strong>
        <?php echo wp_kses_post( $data['{charge}'] ); ?>
    </li>
    <li>
        <strong>
            <?php esc_html_e( 'Payment Method: ', 'dokan-lite' ); ?>
        </strong>
        <?php echo esc_html( $data['{method}'] ); ?>
    </li>
</ul>

<?php
echo wp_kses_post(
    sprintf(
        // translators: 1) withdraw page url
        __( 'You can approve or deny it by going <a href="%s"> here </a>', 'dokan-lite' ), esc_url( $data['{withdraw_page}'] )
    )
);

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [withdraw-new.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/plain/withdraw-new.php)

SHA-256: `a9318413f6dd979a737ff16dc0f275014cde23a7333c4d88d96d0ae551fe39ee`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- A new withdraw request has been made by - %s
- Request Amount : %s
- Payment Method : %s
- Store Name : %s
- You can approve or deny it by going here : %s
- s settings. */ if ( ! empty( $additional_content ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Withdraw request Email.
 *
 * An email sent to the admin when a new withdraw request is created by vendor.
 *
 * @class       Dokan_Vendor_Withdraw_Request
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

esc_attr_e( 'Hi,', 'dokan-lite' );
echo " \n";

// translators: 1) user name
echo sprintf( esc_html__( 'A new withdraw request has been made by - %s', 'dokan-lite' ), esc_html( $data['{store_name}'] ) );
echo " \n";

// translators: 1) withdraw request amount
echo sprintf( esc_html__( 'Request Amount : %s', 'dokan-lite' ), esc_html( $data['{amount}'] ) );
echo " \n";

// translators: 1) withdraw payment method
echo sprintf( esc_html__( 'Payment Method : %s', 'dokan-lite' ), esc_html( $data['{method}'] ) );
echo " \n";

// translators: 1) user name
echo sprintf( esc_html__( 'Store Name : %s', 'dokan-lite' ), esc_html( $data['{store_name}'] ) );
echo " \n";

// translators: 1) vendor profile url
echo sprintf( esc_html__( 'Profile : %s', 'dokan-lite' ), esc_url( $data['{profile_url}'] ) );
echo " \n";

// translators: 1) with menu url
echo sprintf( esc_html__( 'You can approve or deny it by going here : %s', 'dokan-lite' ), esc_html( $data['{withdraw_page}'] ) );
echo " \n";

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


### dokan-lite / includes\Emails\WithdrawApproved.php


### Source: [WithdrawApproved.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/includes/Emails/WithdrawApproved.php)

SHA-256: `5b141c875b768874b62329a1e3e740f7d49db4d812b81b7cb26d22dde00384f5`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dokan Withdraw Approved
- These emails are sent to vendor when a vendor withdraw request is approved
- [{site_title}] Your withdrawal request was approved
- Withdrawal request for {amount} is approved
- Available placeholders: %s
- Enable this email notification
- Email heading
- Additional content
- Text to appear below the main email content.
- Email type
- Choose which format of email to send.
- email_type wc-enhanced-select

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php

namespace WeDevs\Dokan\Emails;

use WC_Email;
use WeDevs\Dokan\Vendor\Vendor;

/**
 * New Product Email.
 *
 * An email sent to the admin when a new Product is created by vendor.
 *
 * @class       Dokan_Email_Withdraw_Approved
 * @version     2.6.8
 * @author      weDevs
 * @extends     WC_Email
 */
class WithdrawApproved extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'dokan_vendor_withdraw_approved';
        $this->title          = __( 'Dokan Withdraw Approved', 'dokan-lite' );
        $this->description    = __( 'These emails are sent to vendor when a vendor withdraw request is approved', 'dokan-lite' );
        $this->template_html  = 'emails/withdraw-approve.php';
        $this->template_plain = 'emails/plain/withdraw-approve.php';
        $this->template_base  = DOKAN_DIR . '/templates/';
        $this->placeholders   = [
            '{store_name}'        => '',
            '{amount}'            => '',
            '{receivable_amount}' => '',
            '{method}'            => '',
            '{profile_url}'       => '',
            '{withdraw_page}'     => '',
            // Only for backward compatibility.
            '{user_name}'         => '',
            '{site_name}'         => $this->get_from_name(),
        ];

        // Triggers for this email
        add_action( 'dokan_withdraw_request_approved', [ $this, 'trigger' ], 30 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = 'vendor@ofthe.product';
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] Your withdrawal request was approved', 'dokan-lite' );
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading() {
        return __( 'Withdrawal request for {amount} is approved', 'dokan-lite' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param \WeDevs\Dokan\Withdraw\Withdraw $withdraw .
     */
    public function trigger( $withdraw ) {
        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }

        $this->setup_locale();
        $seller       = new Vendor( $withdraw->get_user_id() );
        $this->object = $seller;
        $amount = wc_format_decimal( $withdraw->get_amount(), false, true );

        $this->placeholders['{store_name}']        = $seller->get_shop_name();
        $this->placeholders['{amount}']            = dokan()->email->currency_symbol( $amount );
        $this->placeholders['{receivable_amount}'] = dokan()->email->currency_symbol( $withdraw->get_receivable_amount() );
        $this->placeholders['{method}']            = dokan_withdraw_get_method_title( $withdraw->get_method() );
        $this->placeholders['{profile_url}']       = esc_url( dokan_get_navigation_url( 'edit-account' ) );
        $this->placeholders['{withdraw_page}']     = esc_url( dokan_get_navigation_url( 'withdraw-requests' ) );
        $this->placeholders['{user_name}']         = $seller->get_shop_name(); // for backward compatibility.

        $this->send( $seller->get_email(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html, [
                'seller'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
                'data'               => $this->placeholders,
            ], 'dokan/', $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain, [
                'seller'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
                'data'               => $this->placeholders,
            ], 'dokan/', $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        unset( $placeholders['{site_name}'], $placeholders['{user_name}'] );
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan-lite' ), '<code>' . implode( '</code>, <code>', array_keys( $placeholders ) ) . '</code>' );
        $this->form_fields = [
            'enabled'    => [
                'title'   => __( 'Enable/Disable', 'dokan-lite' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'dokan-lite' ),
                'default' => 'yes',
            ],
            'subject'    => [
                'title'       => __( 'Subject', 'dokan-lite' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ],
            'heading'    => [
                'title'       => __( 'Email heading', 'dokan-lite' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ],
            'additional_content' => array(
                'title'       => __( 'Additional content', 'dokan-lite' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan-lite' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan-lite' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => [
                'title'       => __( 'Email type', 'dokan-lite' ),
                'type'        => 'select',
                'description' => __( 'Choose which format of email to send.', 'dokan-lite' ),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
                'desc_tip'    => true,
            ],
        ];
    }
}

```

</details>


### Source: [withdraw-approve.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/withdraw-approve.php)

SHA-256: `4b0794003baaf096b2d0d0cfde13d9d6abecf31e066f0f7fecda340690b73e40`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your withdraw request has been approved, congrats!
- You sent a withdraw request of:
- Receivable amount : %s
- We'll transfer this amount to your preferred withdrawal method shortly.
- Thanks for being with us.
- s settings. */ if ( $additional_content ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); } do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Withdraw request Email.
 *
 * An email sent to the admin when a new withdraw request is created by vendor.
 *
 * @class       Dokan_Email_Withdraw_Approved
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p>
    <?php
    // translators: user name
    echo sprintf( esc_html__( 'Hi %s', 'dokan-lite' ), esc_html( $data['{store_name}'] ) );
    ?>
</p>
<p>
    <?php esc_html_e( 'Your withdraw request has been approved, congrats!', 'dokan-lite' ); ?>
</p>
<p>
    <?php esc_html_e( 'You sent a withdraw request of:', 'dokan-lite' ); ?>
    <br>
    <?php esc_html_e( 'Amount : ', 'dokan-lite' ); ?>
    <?php echo wp_kses_post( $data['{amount}'] ); ?>
    <br>
    <?php
    // translators: 1) withdraw receivable amount
    printf( esc_html__( 'Receivable amount : %s', 'dokan-lite' ), esc_html( $data['{receivable_amount}'] ) );
    ?>
    <br>
    <?php
    // translators: 1) withdraw method title
    echo sprintf( esc_html__( 'Method : %s', 'dokan-lite' ), esc_html( $data['{method}'] ) );
    ?>
</p>
<p>
    <?php esc_html_e( 'We\'ll transfer this amount to your preferred withdrawal method shortly.', 'dokan-lite' ); ?>

    <?php esc_html_e( 'Thanks for being with us.', 'dokan-lite' ); ?>
</p>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [withdraw-approve.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/plain/withdraw-approve.php)

SHA-256: `c6b25dab0239f4263c23e89bfa68bf7b492cb88e51f2a93aa4dff0ba15318d21`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your withdraw request has been approved, congrats!
- You sent a withdraw request of:
- We'll transfer this amount to your preferred withdrawal method shortly.
- Thanks for being with us.
- s settings. */ if ( ! empty( $additional_content ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Withdraw request Email.
 *
 * An email sent to the admin when a new withdraw request is created by vendor.
 *
 * @class       Dokan_Email_Withdraw_Approved
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: 1) user name
echo sprintf( esc_html__( 'Hi %s', 'dokan-lite' ), esc_html( $data['{store_name}'] ) );
echo " \n";

esc_html_e( 'Your withdraw request has been approved, congrats!', 'dokan-lite' );
echo " \n";

esc_html_e( 'You sent a withdraw request of:', 'dokan-lite' );
echo " \n";

// translators: 1) withdraw amount
echo sprintf( esc_html__( 'Amount : %s', 'dokan-lite' ), esc_html( $data['{amount}'] ) );
echo " \n";

// translators: 1) withdraw method
echo sprintf( esc_html__( 'Method : %s', 'dokan-lite' ), esc_html( $data['{method}'] ) );
echo " \n";

esc_html_e( 'We\'ll transfer this amount to your preferred withdrawal method shortly.', 'dokan-lite' );
echo " \n";

esc_html_e( 'Thanks for being with us.', 'dokan-lite' );
echo " \n";

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


### dokan-lite / includes\Emails\WithdrawCancelled.php


### Source: [WithdrawCancelled.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/includes/Emails/WithdrawCancelled.php)

SHA-256: `418ca7d800839373a4d9ec1dee4ff90ac54bc9badef2b93e18366d916cd3f0be`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Dokan Withdraw cancelled
- These emails are sent to vendor when a vendor withdraw request is cancelled
- [{site_title}] Your withdrawal request was cancelled
- Withdrawal request for {amount} is cancelled
- Available placeholders: %s
- Enable this email notification
- Email heading
- Additional content
- Text to appear below the main email content.
- Email type
- Choose which format of email to send.
- email_type wc-enhanced-select

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php

namespace WeDevs\Dokan\Emails;

use WC_Email;
use WeDevs\Dokan\Vendor\Vendor;

/**
 * Withdraw Request Cancelled
 *
 * An email sent to the vendor when a withdrawal request is cancelled by admin.
 *
 * @class       Dokan_Email_Withdraw_Cancelled
 * @version     2.6.8
 * @author      weDevs
 * @extends     WC_Email
 */
class WithdrawCancelled extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id               = 'dokan_vendor_withdraw_cancelled';
        $this->title            = __( 'Dokan Withdraw cancelled', 'dokan-lite' );
        $this->description      = __( 'These emails are sent to vendor when a vendor withdraw request is cancelled', 'dokan-lite' );
        $this->template_html    = 'emails/withdraw-cancel.php';
        $this->template_plain   = 'emails/plain/withdraw-cancel.php';
        $this->template_base    = DOKAN_DIR . '/templates/';
        $this->placeholders = [
            '{store_name}'        => '',
            '{amount}'            => '',
            '{receivable_amount}' => '',
            '{method}'            => '',
            '{profile_url}'       => '',
            '{withdraw_page}'     => dokan_get_navigation_url( 'withdraw' ),
            '{note}'              => '',
            // Only for backward compatibility.
            '{user_name}'         => '',
            '{site_name}'         => $this->get_from_name(),
        ];

        // Triggers for this email
        add_action( 'dokan_withdraw_request_cancelled', array( $this, 'trigger' ), 30, 2 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->recipient = 'vendor@ofthe.product';
    }

    /**
     * Get email subject.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_subject() {
        return __( '[{site_title}] Your withdrawal request was cancelled', 'dokan-lite' );
    }

    /**
     * Get email heading.
     *
     * @since  3.1.0
     * @return string
     */
    public function get_default_heading() {
        return __( 'Withdrawal request for {amount} is cancelled', 'dokan-lite' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param \WeDevs\Dokan\Withdraw\Withdraw $withdraw.
     */
    public function trigger( $withdraw ) {
        if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
            return;
        }

        $this->setup_locale();
        $seller                      = new Vendor( $withdraw->get_user_id() );
        $this->object                = $seller;

        $this->placeholders['{store_name}']        = $seller->get_shop_name();
        $this->placeholders['{amount}']            = dokan()->email->currency_symbol( $withdraw->get_amount() );
        $this->placeholders['{receivable_amount}'] = dokan()->email->currency_symbol( $withdraw->get_receivable_amount() );
        $this->placeholders['{method}']            = dokan_withdraw_get_method_title( $withdraw->get_method() );
        $this->placeholders['{profile_url}']       = dokan_get_navigation_url( 'edit-account' );
        $this->placeholders['{note}']              = $withdraw->get_note();
        $this->placeholders['{user_name}']         = $seller->get_shop_name(); // Only for  backward compatibility.

        $this->send( $seller->get_email(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @access public
     * @return string
     */
    public function get_content_html() {
        ob_start();
        wc_get_template(
            $this->template_html, array(
                'seller'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
                'data'               => $this->placeholders,
            ), 'dokan/', $this->template_base
        );
        return ob_get_clean();
    }

    /**
     * Get content plain.
     *
     * @access public
     * @return string
     */
    public function get_content_plain() {
        ob_start();
        wc_get_template(
            $this->template_plain, array(
                'seller'             => $this->object,
                'email_heading'      => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
                'data'               => $this->placeholders,
            ), 'dokan/', $this->template_base
        );
        return ob_get_clean();
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        $placeholders = $this->placeholders;
        unset( $placeholders['{site_name}'], $placeholders['{user_name}'] );
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'dokan-lite' ), '<code>' . implode( '</code>, <code>', array_keys( $placeholders ) ) . '</code>' );
        $this->form_fields = array(
            'enabled' => array(
                'title'         => __( 'Enable/Disable', 'dokan-lite' ),
                'type'          => 'checkbox',
                'label'         => __( 'Enable this email notification', 'dokan-lite' ),
                'default'       => 'yes',
            ),
            'subject' => array(
                'title'         => __( 'Subject', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_subject(),
                'default'       => '',
            ),
            'heading' => array(
                'title'         => __( 'Email heading', 'dokan-lite' ),
                'type'          => 'text',
                'desc_tip'      => true,
                'description'   => $placeholder_text,
                'placeholder'   => $this->get_default_heading(),
                'default'       => '',
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'dokan-lite' ),
                'description' => __( 'Text to appear below the main email content.', 'dokan-lite' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'dokan-lite' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'         => __( 'Email type', 'dokan-lite' ),
                'type'          => 'select',
                'description'   => __( 'Choose which format of email to send.', 'dokan-lite' ),
                'default'       => 'html',
                'class'         => 'email_type wc-enhanced-select',
                'options'       => $this->get_email_type_options(),
                'desc_tip'      => true,
            ),
        );
    }
}

```

</details>


### Source: [withdraw-cancel.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/withdraw-cancel.php)

SHA-256: `a0416554806607a58a73ad59d617992939826b367e73330b8f5672b191b5a46f`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your withdraw request was cancelled!
- You sent a withdraw request of:
- Receivable amount : %s
- Here's the reason, why :
- s settings. */ if ( $additional_content ) { echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); } do_action(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * Withdraw request cancelled Email.
 *
 * An email sent to the vendor when a new withdraw request is cancelled by admin.
 *
 * @class       Dokan_Email_Withdraw_Cancelled
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p>
    <?php
    // translators: user name
    echo sprintf( esc_html__( 'Hi %s', 'dokan-lite' ), esc_html( $data['{store_name}'] ) );
    ?>
</p>
<p>
    <?php esc_html_e( 'Your withdraw request was cancelled!', 'dokan-lite' ); ?>
</p>
<p>
    <?php esc_html_e( 'You sent a withdraw request of:', 'dokan-lite' ); ?>
    <br>
    <?php
    // translators: 1) withdraw amount
    echo sprintf( esc_html__( 'Amount : %s', 'dokan-lite' ), esc_html( $data['{amount}'] ) );
    ?>
    <br>
    <?php
    // translators: 1) withdraw receivable amount
    printf( esc_html__( 'Receivable amount : %s', 'dokan-lite' ), esc_html( $data['{receivable_amount}'] ) );
    ?>
    <br>
    <?php
    // translators: 1) withdraw method title
    echo sprintf( esc_html__( 'Method : %s', 'dokan-lite' ), esc_html( $data['{method}'] ) );
    ?>
</p>
<p>
    <?php esc_html_e( 'Here\'s the reason, why : ', 'dokan-lite' ); ?>
    <br>
    <i><?php echo esc_html( $data['{note}'] ); ?></i>
</p>

<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}
do_action( 'woocommerce_email_footer', $email );

```

</details>


### Source: [withdraw-cancel.php](C:/Users/Svet/Documents/KnifeRevive_Documentation/dokan-pro-exit-2026-10-25/production-cutover-2026-10-26/private-production-copy/plugins/dokan-lite/templates/emails/plain/withdraw-cancel.php)

SHA-256: `04959a4895a336ce79190a6c21ad2fb5cdf28e7adb37f91481cbc07f95c29e09`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- Your withdraw request was cancelled
- You sent a withdraw request of:
- Here's the reason, why :
- s settings. */ if ( ! empty( $additional_content ) ) { echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ); echo " ---------------------------------------- "; } echo wp_kses_post( apply_filters(

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
/**
 * New Withdraw request Email. ( plain text )
 *
 * An email sent to the admin when a new withdraw request is created by vendor.
 *
 * @class       Dokan_Email_Withdraw_Cancelled
 * @version     2.6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// translators: 1) user name
echo sprintf( esc_html__( 'Hi %s', 'dokan-lite' ), esc_html( $data['{store_name}'] ) );
echo " \n";

esc_attr_e( 'Your withdraw request was cancelled', 'dokan-lite' );
echo " \n";

esc_attr_e( 'You sent a withdraw request of:', 'dokan-lite' );
echo " \n";

// translators: 1) withdraw amount
echo sprintf( esc_html__( 'Amount : %s', 'dokan-lite' ), esc_html( $data['{amount}'] ) );
echo " \n";

// translators: 1) withdraw method
echo sprintf( esc_html__( 'Method : %s', 'dokan-lite' ), esc_html( $data['{method}'] ) );
echo " \n";

esc_attr_e( 'Here\'s the reason, why : ', 'dokan-lite' );
echo " \n";

echo esc_html( $data['{note}'] );
echo " \n";

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( ! empty( $additional_content ) ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

```

</details>


### woocommerce-gateway-stripe / includes\compat\class-wc-stripe-email-failed-authentication.php


### Source: [class-wc-stripe-email-failed-authentication.php](C:/Users/Svet/Local Sites/kniferevive/app/public/wp-content/plugins/woocommerce-gateway-stripe/includes/compat/class-wc-stripe-email-failed-authentication.php)

SHA-256: `eb23c1446ad2f64908c641a4632dccc16f03e4609be3d21bffc49a449bba6a9f`. This is a local source reference, not a byte-for-byte live-server export.

**Literal wording reference** (fragments retain dynamic boundaries; surrounding source is authoritative):

- an email notification
- Enable this email notification

<details><summary>Exact template/renderer source, beginning at line 1</summary>

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Base for Failed Renewal/Pre-Order Authentication Notifications.
 */
abstract class WC_Stripe_Email_Failed_Authentication extends WC_Email {
	/**
	 * An instance of the email, which would normally be sent after a failed payment.
	 *
	 * @var WC_Email
	 */
	public $original_email;

	/**
	 * Generates the HTML for the email while keeping the `template_base` in mind.
	 *
	 * @return string
	 */
	public function get_content_html() {
		ob_start();
		wc_get_template(
			$this->template_html,
			[
				'order'             => $this->object,
				'email_heading'     => $this->get_heading(),
				'sent_to_admin'     => false,
				'plain_text'        => false,
				'authorization_url' => $this->get_authorization_url( $this->object ),
				'email'             => $this,
			],
			'',
			$this->template_base
		);
		return ob_get_clean();
	}

	/**
	 * Generates the plain text for the email while keeping the `template_base` in mind.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();
		wc_get_template(
			$this->template_plain,
			[
				'order'             => $this->object,
				'email_heading'     => $this->get_heading(),
				'sent_to_admin'     => false,
				'plain_text'        => true,
				'authorization_url' => $this->get_authorization_url( $this->object ),
				'email'             => $this,
			],
			'',
			$this->template_base
		);
		return ob_get_clean();
	}

	/**
	 * Generates the URL, which will be used to authenticate the payment.
	 *
	 * @param WC_Order $order The order whose payment needs authentication.
	 * @return string
	 */
	public function get_authorization_url( $order ) {
		return esc_url( add_query_arg( 'wc-stripe-confirmation', 1, $order->get_checkout_payment_url( false ) ) );
	}

	/**
	 * Uses specific fields from `WC_Email_Customer_Invoice` for this email.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		parent::init_form_fields();
		$base_fields = $this->form_fields;

		$this->form_fields = [
			'enabled'    => [
				'title'   => _x( 'Enable/Disable', 'an email notification', 'woocommerce-gateway-stripe' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woocommerce-gateway-stripe' ),
				'default' => 'yes',
			],

			'subject'    => $base_fields['subject'],
			'heading'    => $base_fields['heading'],
			'email_type' => $base_fields['email_type'],
		];
	}

	/**
	 * Triggers the email.
	 *
	 * @param WC_Order $order The renewal order whose payment failed.
	 * @return void
	 */
	public function trigger( $order ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$this->object = $order;

		if ( method_exists( $order, 'get_billing_email' ) ) {
			$this->recipient = $order->get_billing_email();
		} else {
			$this->recipient = $order->billing_email;
		}

		$this->find['order_date'] = '{order_date}';
		if ( function_exists( 'wc_format_datetime' ) ) { // WC 3.0+
			$this->replace['order_date'] = wc_format_datetime( $order->get_date_created() );
		} else { // WC < 3.0
			$this->replace['order_date'] = $order->date_created->date_i18n( wc_date_format() );
		}

		$this->find['order_number']    = '{order_number}';
		$this->replace['order_number'] = $order->get_order_number();

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}
}

```

</details>
