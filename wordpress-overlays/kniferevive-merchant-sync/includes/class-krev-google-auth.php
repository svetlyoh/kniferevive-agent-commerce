<?php

defined( 'ABSPATH' ) || exit;

use Google\Auth\Credentials\ServiceAccountCredentials;

final class KREV_Google_Auth {
	const SCOPE         = 'https://www.googleapis.com/auth/content';
	const TRANSIENT_KEY = 'krev_merchant_access_token';

	public static function get_access_token( $force_refresh = false ) {
		if ( ! class_exists( ServiceAccountCredentials::class ) ) {
			return new WP_Error( 'krev_auth_library_missing', 'The Google authentication library is not installed.' );
		}

		if ( ! $force_refresh ) {
			$cached = get_transient( self::TRANSIENT_KEY );
			if ( is_string( $cached ) && '' !== $cached ) {
				return $cached;
			}
		}

		$credentials_config = KREV_Merchant_Config::validate();
		if ( is_wp_error( $credentials_config ) ) {
			return $credentials_config;
		}

		try {
			$credentials = new ServiceAccountCredentials( array( self::SCOPE ), $credentials_config );
			$token_data  = $credentials->fetchAuthToken();
		} catch ( Throwable $error ) {
			KREV_Logger::error( 'Merchant authentication failed.', array( 'exception' => get_class( $error ), 'message' => $error->getMessage() ) );
			return new WP_Error( 'krev_auth_failed', 'Merchant authentication failed: ' . KREV_Logger::sanitize( $error->getMessage() ) );
		}

		$token = isset( $token_data['access_token'] ) ? (string) $token_data['access_token'] : '';
		if ( '' === $token ) {
			return new WP_Error( 'krev_auth_no_token', 'Google did not return an access token.' );
		}

		$expires_in = max( 60, (int) ( $token_data['expires_in'] ?? 3600 ) - 300 );
		set_transient( self::TRANSIENT_KEY, $token, $expires_in );
		KREV_Logger::info( 'Merchant authentication succeeded.' );

		return $token;
	}

	public static function test() {
		$token = self::get_access_token( true );

		return is_wp_error( $token )
			? array( 'success' => false, 'message' => $token->get_error_message() )
			: array( 'success' => true, 'message' => 'Service-account OAuth authentication succeeded.' );
	}
}
