<?php

defined( 'ABSPATH' ) || exit;

final class KREV_Merchant_API {
	const ACCOUNTS_BASE    = 'https://merchantapi.googleapis.com/accounts/v1/';
	const DATASOURCES_BASE = 'https://merchantapi.googleapis.com/datasources/v1/';
	const PRODUCTS_BASE    = 'https://merchantapi.googleapis.com/products/v1/';

	public function get_account() {
		return $this->request( 'GET', self::ACCOUNTS_BASE . 'accounts/' . rawurlencode( KREV_Merchant_Config::account_id() ) );
	}

	public function get_developer_registration() {
		$name = sprintf( 'accounts/%s/developerRegistration', KREV_Merchant_Config::account_id() );

		return $this->request( 'GET', self::ACCOUNTS_BASE . $name );
	}

	public function register_gcp() {
		$name = sprintf( 'accounts/%s/developerRegistration', KREV_Merchant_Config::account_id() );

		return $this->request(
			'POST',
			self::ACCOUNTS_BASE . $name . ':registerGcp',
			array( 'developerEmail' => KREV_Merchant_Config::developer_email() )
		);
	}

	public function get_datasource() {
		return $this->request( 'GET', self::DATASOURCES_BASE . KREV_Merchant_Config::datasource_name() );
	}

	public function list_datasources() {
		$url = add_query_arg( 'pageSize', 1000, self::DATASOURCES_BASE . 'accounts/' . rawurlencode( KREV_Merchant_Config::account_id() ) . '/dataSources' );

		return $this->request( 'GET', $url );
	}

	public function list_products() {
		$url = add_query_arg( 'pageSize', 1000, self::PRODUCTS_BASE . 'accounts/' . rawurlencode( KREV_Merchant_Config::account_id() ) . '/products' );

		return $this->request( 'GET', $url );
	}

	public function insert_product( array $payload ) {
		$url = self::PRODUCTS_BASE . 'accounts/' . rawurlencode( KREV_Merchant_Config::account_id() ) . '/productInputs:insert';
		$url = add_query_arg( 'dataSource', KREV_Merchant_Config::datasource_name(), $url );

		return $this->request( 'POST', $url, $payload );
	}

	public function get_product( $resource_name ) {
		$resource_name = ltrim( (string) $resource_name, '/' );
		if ( ! preg_match( '#^accounts/\d+/products/[A-Za-z0-9_~\-]+$#', $resource_name ) ) {
			return new WP_Error( 'krev_invalid_product_name', 'Google returned an invalid processed-product resource name.' );
		}

		return $this->request( 'GET', self::PRODUCTS_BASE . $resource_name );
	}

	public function delete_product_input( $resource_name ) {
		$resource_name = ltrim( (string) $resource_name, '/' );
		if ( ! preg_match( '#^accounts/\d+/productInputs/[A-Za-z0-9_~\-]+$#', $resource_name ) ) {
			return new WP_Error( 'krev_invalid_product_input_name', 'Stored Google product-input resource name is invalid.' );
		}

		$url = add_query_arg( 'dataSource', KREV_Merchant_Config::datasource_name(), self::PRODUCTS_BASE . $resource_name );

		return $this->request( 'DELETE', $url );
	}

	public function request( $method, $url, array $body = null ) {
		$token = KREV_Google_Auth::get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$args = array(
			'method'      => strtoupper( (string) $method ),
			'timeout'     => 30,
			'redirection' => 0,
			'headers'     => array(
				'Authorization' => 'Bearer ' . $token,
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json; charset=utf-8',
			),
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body, JSON_UNESCAPED_SLASHES );
		}

		$response = null;
		for ( $attempt = 0; $attempt < 3; $attempt++ ) {
			$response = wp_remote_request( $url, $args );
			$code     = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );

			if ( ! is_wp_error( $response ) && 429 !== $code && $code < 500 ) {
				break;
			}

			if ( $attempt < 2 ) {
				usleep( ( 250000 * ( 2 ** $attempt ) ) + random_int( 0, 150000 ) );
			}
		}

		if ( is_wp_error( $response ) ) {
			KREV_Logger::error( 'Merchant API network failure.', array( 'code' => $response->get_error_code(), 'message' => $response->get_error_message() ) );
			return new WP_Error( 'krev_network_error', 'Merchant API network failure: ' . KREV_Logger::sanitize( $response->get_error_message() ) );
		}

		$code    = (int) wp_remote_retrieve_response_code( $response );
		$raw     = (string) wp_remote_retrieve_body( $response );
		$decoded = '' === $raw ? array() : json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = is_array( $decoded ) ? (string) ( $decoded['error']['message'] ?? 'Merchant API request failed.' ) : 'Merchant API request failed.';
			$status  = is_array( $decoded ) ? (string) ( $decoded['error']['status'] ?? '' ) : '';
			KREV_Logger::error( 'Merchant API request failed.', array( 'http_code' => $code, 'status' => $status, 'message' => $message ) );

			return new WP_Error(
				'krev_merchant_api_error',
				KREV_Logger::sanitize( $message ),
				array( 'http_code' => $code, 'status' => $status )
			);
		}

		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'krev_invalid_api_response', 'Merchant API returned invalid JSON.' );
		}

		return $decoded;
	}
}
