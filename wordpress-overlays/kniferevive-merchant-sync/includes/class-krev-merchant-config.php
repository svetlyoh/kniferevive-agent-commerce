<?php

defined( 'ABSPATH' ) || exit;

final class KREV_Merchant_Config {
	const DEFAULT_ACCOUNT_ID       = '5489888687';
	const DEFAULT_DATASOURCE_ID    = '10717392032';
	const DEFAULT_DEVELOPER_EMAIL  = 'knifereviveofficial@gmail.com';
	const EXPECTED_SERVICE_ACCOUNT = 'kniferevive-merchant-sync@kniferevive.iam.gserviceaccount.com';
	const DEFAULT_LANGUAGE         = 'en';
	const DEFAULT_FEED_LABEL       = 'US';
	const CREDENTIALS_OPTION       = 'krev_merchant_credentials_encrypted';
	const MAX_CREDENTIAL_BYTES     = 131072;

	public static function account_id() {
		return (string) ( defined( 'KNIFEREVIVE_MERCHANT_ACCOUNT_ID' ) ? KNIFEREVIVE_MERCHANT_ACCOUNT_ID : get_option( 'krev_merchant_account_id', self::DEFAULT_ACCOUNT_ID ) );
	}

	public static function datasource_id() {
		return (string) ( defined( 'KNIFEREVIVE_MERCHANT_DATASOURCE_ID' ) ? KNIFEREVIVE_MERCHANT_DATASOURCE_ID : get_option( 'krev_merchant_datasource_id', self::DEFAULT_DATASOURCE_ID ) );
	}

	public static function datasource_name() {
		return sprintf( 'accounts/%s/dataSources/%s', self::account_id(), self::datasource_id() );
	}

	public static function developer_email() {
		return (string) ( defined( 'KNIFEREVIVE_MERCHANT_DEVELOPER_EMAIL' ) ? KNIFEREVIVE_MERCHANT_DEVELOPER_EMAIL : get_option( 'krev_merchant_developer_email', self::DEFAULT_DEVELOPER_EMAIL ) );
	}

	public static function credential_path() {
		return defined( 'KNIFEREVIVE_MERCHANT_CREDENTIALS' ) ? (string) KNIFEREVIVE_MERCHANT_CREDENTIALS : '';
	}

	public static function credential_storage() {
		if ( '' !== self::credential_path() ) {
			return 'External file';
		}

		return get_option( self::CREDENTIALS_OPTION, false ) ? 'Encrypted WordPress storage' : 'Not configured';
	}

	public static function production_origin() {
		return untrailingslashit( (string) apply_filters( 'krev_merchant_production_origin', 'https://kniferevive.com' ) );
	}

	public static function validate() {
		$path = self::credential_path();

		if ( '' !== $path ) {
			if ( ! is_file( $path ) || ! is_readable( $path ) ) {
				return new WP_Error( 'krev_credentials_missing', 'The configured Merchant credential file is missing or unreadable.' );
			}

			if ( filesize( $path ) > self::MAX_CREDENTIAL_BYTES ) {
				return new WP_Error( 'krev_credentials_too_large', 'The Merchant credential file is unexpectedly large.' );
			}

			return self::decode_and_validate( (string) file_get_contents( $path ) );
		}

		if ( '' === self::account_id() || '' === self::datasource_id() ) {
			return new WP_Error( 'krev_configuration_missing', 'Merchant account or data-source configuration is missing.' );
		}

		$encrypted = get_option( self::CREDENTIALS_OPTION, false );
		if ( ! is_array( $encrypted ) ) {
			return new WP_Error( 'krev_credentials_missing', 'Merchant credentials are not configured. Upload the service-account JSON below.' );
		}

		$json = self::decrypt( $encrypted );
		if ( is_wp_error( $json ) ) {
			return $json;
		}

		return self::decode_and_validate( $json );
	}

	public static function store_credentials_json( $json ) {
		if ( ! is_string( $json ) || '' === trim( $json ) ) {
			return new WP_Error( 'krev_credentials_empty', 'The uploaded credential file is empty.' );
		}

		if ( strlen( $json ) > self::MAX_CREDENTIAL_BYTES ) {
			return new WP_Error( 'krev_credentials_too_large', 'The uploaded credential file is unexpectedly large.' );
		}

		$credentials = self::decode_and_validate( $json );
		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}

		$canonical = wp_json_encode( $credentials, JSON_UNESCAPED_SLASHES );
		if ( false === $canonical ) {
			return new WP_Error( 'krev_credentials_encode_failed', 'The credential file could not be prepared for secure storage.' );
		}

		$encrypted = self::encrypt( $canonical );
		if ( is_wp_error( $encrypted ) ) {
			return $encrypted;
		}

		if ( ! update_option( self::CREDENTIALS_OPTION, $encrypted, false ) && get_option( self::CREDENTIALS_OPTION, false ) !== $encrypted ) {
			return new WP_Error( 'krev_credentials_store_failed', 'WordPress could not save the encrypted credentials.' );
		}

		return true;
	}

	private static function decode_and_validate( $json ) {
		$credentials = json_decode( $json, true );
		if ( ! is_array( $credentials ) || JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'krev_credentials_invalid_json', 'Merchant credentials are not valid JSON.' );
		}

		if ( 'service_account' !== ( $credentials['type'] ?? '' ) ) {
			return new WP_Error( 'krev_credentials_wrong_type', 'Merchant credentials are not a service-account key.' );
		}

		if ( self::EXPECTED_SERVICE_ACCOUNT !== ( $credentials['client_email'] ?? '' ) ) {
			return new WP_Error( 'krev_credentials_wrong_account', 'Merchant credentials use an unexpected service account.' );
		}

		$private_key = (string) ( $credentials['private_key'] ?? '' );
		if ( ! str_contains( $private_key, '-----BEGIN PRIVATE KEY-----' ) || ! str_contains( $private_key, '-----END PRIVATE KEY-----' ) ) {
			return new WP_Error( 'krev_credentials_invalid_key', 'Merchant credentials do not contain a valid private-key field.' );
		}

		return $credentials;
	}

	private static function encryption_key() {
		if ( ! function_exists( 'wp_salt' ) ) {
			return new WP_Error( 'krev_credentials_crypto_unavailable', 'WordPress security keys are unavailable.' );
		}

		return hash( 'sha256', wp_salt( 'auth' ) . '|kniferevive-merchant-sync|v1', true );
	}

	private static function encrypt( $plaintext ) {
		$key = self::encryption_key();
		if ( is_wp_error( $key ) ) {
			return $key;
		}

		try {
			if ( function_exists( 'sodium_crypto_secretbox' ) ) {
				$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
				return array(
					'version'    => 1,
					'method'     => 'sodium_secretbox',
					'nonce'      => base64_encode( $nonce ),
					'ciphertext' => base64_encode( sodium_crypto_secretbox( $plaintext, $nonce, $key ) ),
				);
			}

			if ( function_exists( 'openssl_encrypt' ) ) {
				$iv         = random_bytes( 12 );
				$tag        = '';
				$ciphertext = openssl_encrypt( $plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
				if ( false === $ciphertext ) {
					throw new RuntimeException( 'OpenSSL encryption failed.' );
				}

				return array(
					'version'    => 1,
					'method'     => 'aes-256-gcm',
					'nonce'      => base64_encode( $iv ),
					'tag'        => base64_encode( $tag ),
					'ciphertext' => base64_encode( $ciphertext ),
				);
			}
		} catch ( Throwable $error ) {
			return new WP_Error( 'krev_credentials_encrypt_failed', 'Merchant credentials could not be encrypted.' );
		}

		return new WP_Error( 'krev_credentials_crypto_unavailable', 'The server does not provide a supported encryption library.' );
	}

	private static function decrypt( array $payload ) {
		$key = self::encryption_key();
		if ( is_wp_error( $key ) ) {
			return $key;
		}

		if ( 1 !== (int) ( $payload['version'] ?? 0 ) || empty( $payload['method'] ) || empty( $payload['nonce'] ) || empty( $payload['ciphertext'] ) ) {
			return new WP_Error( 'krev_credentials_corrupt', 'The encrypted Merchant credential record is invalid. Upload the JSON again.' );
		}

		$nonce      = base64_decode( (string) $payload['nonce'], true );
		$ciphertext = base64_decode( (string) $payload['ciphertext'], true );
		if ( false === $nonce || false === $ciphertext ) {
			return new WP_Error( 'krev_credentials_corrupt', 'The encrypted Merchant credential record is invalid. Upload the JSON again.' );
		}

		try {
			if ( 'sodium_secretbox' === $payload['method'] && function_exists( 'sodium_crypto_secretbox_open' ) ) {
				$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );
			} elseif ( 'aes-256-gcm' === $payload['method'] && function_exists( 'openssl_decrypt' ) ) {
				$tag       = base64_decode( (string) ( $payload['tag'] ?? '' ), true );
				$plaintext = false === $tag ? false : openssl_decrypt( $ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag );
			} else {
				$plaintext = false;
			}
		} catch ( Throwable $error ) {
			$plaintext = false;
		}

		if ( false === $plaintext ) {
			return new WP_Error( 'krev_credentials_decrypt_failed', 'The encrypted Merchant credentials cannot be decrypted. Upload the JSON again.' );
		}

		return $plaintext;
	}

	public static function safe_status() {
		$validation = self::validate();

		return array(
			'account_id'            => self::account_id(),
			'datasource_name'        => self::datasource_name(),
			'developer_email'        => self::developer_email(),
			'service_account'        => self::EXPECTED_SERVICE_ACCOUNT,
			'credential_storage'     => self::credential_storage(),
			'credentials_configured' => ! is_wp_error( $validation ),
			'credentials_error'      => is_wp_error( $validation ) ? $validation->get_error_message() : '',
		);
	}
}
