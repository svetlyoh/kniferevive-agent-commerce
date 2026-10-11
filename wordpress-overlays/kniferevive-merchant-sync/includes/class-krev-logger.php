<?php

defined( 'ABSPATH' ) || exit;

final class KREV_Logger {
	const SOURCE = 'kniferevive-merchant-sync';

	public static function info( $message, array $context = array() ) {
		self::log( 'info', $message, $context );
	}

	public static function error( $message, array $context = array() ) {
		self::log( 'error', $message, $context );
	}

	public static function sanitize( $value ) {
		if ( is_array( $value ) ) {
			$clean = array();
			foreach ( $value as $key => $item ) {
				if ( preg_match( '/authorization|access.?token|private.?key|client.?secret/i', (string) $key ) ) {
					$clean[ $key ] = '[REDACTED]';
				} else {
					$clean[ $key ] = self::sanitize( $item );
				}
			}
			return $clean;
		}

		if ( ! is_scalar( $value ) && null !== $value ) {
			return gettype( $value );
		}

		$text = (string) $value;
		$text = preg_replace( '/Bearer\s+[A-Za-z0-9._~+\/-]+/i', 'Bearer [REDACTED]', $text );
		$text = preg_replace( '/("?(?:access_token|private_key|private_key_id|client_secret)"?\s*[:=]\s*)[^,}\s]+/i', '$1[REDACTED]', $text );

		return $text;
	}

	private static function log( $level, $message, array $context ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$context            = self::sanitize( $context );
		$context['source']  = self::SOURCE;
		wc_get_logger()->log( $level, self::sanitize( $message ), $context );
	}
}
