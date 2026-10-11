<?php

defined( 'ABSPATH' ) || exit;

final class KREV_Product_Sync {
	const META_INPUT_NAME      = '_krev_google_product_input_name';
	const META_PRODUCT_NAME    = '_krev_google_product_name';
	const META_DATASOURCE_NAME = '_krev_google_datasource_name';
	const META_LAST_SYNC_AT    = '_krev_google_last_sync_at';
	const META_LAST_SYNC_HASH  = '_krev_google_last_sync_hash';
	const META_LAST_ERROR      = '_krev_google_last_error';
	const META_API_STATUS      = '_krev_google_api_status';
	const META_GOOGLE_STATUS   = '_krev_google_processing_status';
	const META_GOOGLE_CATEGORY = '_krev_google_reported_category';

	private $mapper;
	private $api;

	public function __construct( KREV_Product_Mapper $mapper = null, KREV_Merchant_API $api = null ) {
		$this->mapper = $mapper ?: new KREV_Product_Mapper();
		$this->api    = $api ?: new KREV_Merchant_API();
	}

	public function preview( $product_id, $persist_offer_id = false ) {
		$product = wc_get_product( (int) $product_id );
		if ( ! $product ) {
			return new WP_Error( 'krev_product_not_found', 'WooCommerce product was not found.' );
		}

		return $this->mapper->map( $product, $persist_offer_id );
	}

	public function sync( $product_id, $force = false ) {
		$product = wc_get_product( (int) $product_id );
		if ( ! $product ) {
			return new WP_Error( 'krev_product_not_found', 'WooCommerce product was not found.' );
		}

		if ( $this->mapper->is_excluded_from_merchant( $product ) ) {
			$result = $this->delete( $product );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			update_post_meta( $product->get_id(), self::META_API_STATUS, 'excluded' );
			delete_post_meta( $product->get_id(), self::META_LAST_ERROR );
			KREV_Logger::info( sprintf( 'Product %d excluded from Merchant sync.', $product->get_id() ) );

			return array( 'status' => 'excluded' );
		}

		if ( 'publish' !== $product->get_status() ) {
			return $this->delete( $product );
		}

		$payload = $this->mapper->map( $product, true );
		if ( is_wp_error( $payload ) ) {
			$this->record_error( $product->get_id(), $payload->get_error_message() );
			return $payload;
		}

		$hash = self::payload_hash( $payload );
		if ( ! $force && hash_equals( (string) get_post_meta( $product->get_id(), self::META_LAST_SYNC_HASH, true ), $hash ) ) {
			return array( 'status' => 'unchanged', 'payload_hash' => $hash );
		}

		$result = $this->api->insert_product( $payload );
		if ( is_wp_error( $result ) ) {
			$this->record_error( $product->get_id(), $result->get_error_message() );
			return $result;
		}

		$input_name   = (string) ( $result['base64EncodedName'] ?? $result['name'] ?? '' );
		$product_name = (string) ( $result['base64EncodedProduct'] ?? $result['product'] ?? '' );
		update_post_meta( $product->get_id(), self::META_INPUT_NAME, $input_name );
		update_post_meta( $product->get_id(), self::META_PRODUCT_NAME, $product_name );
		update_post_meta( $product->get_id(), self::META_DATASOURCE_NAME, KREV_Merchant_Config::datasource_name() );
		update_post_meta( $product->get_id(), self::META_LAST_SYNC_AT, gmdate( 'c' ) );
		update_post_meta( $product->get_id(), self::META_LAST_SYNC_HASH, $hash );
		update_post_meta( $product->get_id(), self::META_API_STATUS, 'successful' );
		delete_post_meta( $product->get_id(), self::META_LAST_ERROR );
		KREV_Logger::info( sprintf( 'Product %d synced successfully.', $product->get_id() ) );

		return array(
			'status'        => 'inserted',
			'input_name'    => $input_name,
			'product_name'  => $product_name,
			'payload_hash'  => $hash,
			'api_response'  => $result,
		);
	}

	public function refresh_processed_status( $product_id ) {
		$resource_name = (string) get_post_meta( (int) $product_id, self::META_PRODUCT_NAME, true );
		if ( '' === $resource_name ) {
			return new WP_Error( 'krev_product_resource_missing', 'No processed Google product resource is stored.' );
		}

		$result = $this->api->get_product( $resource_name );
		if ( is_wp_error( $result ) ) {
			$this->record_error( (int) $product_id, $result->get_error_message() );
			return $result;
		}

		$summary = self::summarize_google_status( $result );
		$google_category = sanitize_text_field( (string) ( $result['productAttributes']['googleProductCategory'] ?? '' ) );
		update_post_meta( (int) $product_id, self::META_GOOGLE_STATUS, wp_json_encode( $summary, JSON_UNESCAPED_SLASHES ) );
		if ( '' !== $google_category ) {
			update_post_meta( (int) $product_id, self::META_GOOGLE_CATEGORY, $google_category );
		} else {
			delete_post_meta( (int) $product_id, self::META_GOOGLE_CATEGORY );
		}
		delete_post_meta( (int) $product_id, self::META_LAST_ERROR );

		return array( 'product' => $result, 'summary' => $summary, 'google_category' => $google_category );
	}

	public function delete( WC_Product $product ) {
		$input_name = (string) $product->get_meta( self::META_INPUT_NAME, true, 'edit' );
		$data_source = (string) $product->get_meta( self::META_DATASOURCE_NAME, true, 'edit' );
		if ( '' === $input_name || KREV_Merchant_Config::datasource_name() !== $data_source ) {
			return array( 'status' => 'not-owned' );
		}

		$result = $this->api->delete_product_input( $input_name );
		if ( is_wp_error( $result ) ) {
			$this->record_error( $product->get_id(), $result->get_error_message() );
			return $result;
		}

		delete_post_meta( $product->get_id(), self::META_INPUT_NAME );
		delete_post_meta( $product->get_id(), self::META_PRODUCT_NAME );
		delete_post_meta( $product->get_id(), self::META_LAST_SYNC_HASH );
		update_post_meta( $product->get_id(), self::META_API_STATUS, 'deleted' );

		return array( 'status' => 'deleted' );
	}

	public function delete_resource( $input_name, $data_source ) {
		if ( KREV_Merchant_Config::datasource_name() !== (string) $data_source ) {
			return array( 'status' => 'not-owned' );
		}

		$result = $this->api->delete_product_input( (string) $input_name );

		return is_wp_error( $result ) ? $result : array( 'status' => 'deleted' );
	}

	public static function payload_hash( array $payload ) {
		self::recursive_ksort( $payload );

		return hash( 'sha256', (string) wp_json_encode( $payload, JSON_UNESCAPED_SLASHES ) );
	}

	public static function summarize_google_status( array $product ) {
		$status       = $product['productStatus'] ?? array();
		$destinations = $status['destinationStatuses'] ?? array();
		$issues        = array();
		$eligibility   = 'unknown';
		$has_approved  = false;
		$has_pending   = false;
		$has_disapproved = false;

		foreach ( $destinations as $destination ) {
			$has_disapproved = $has_disapproved || ! empty( $destination['disapprovedCountries'] );
			if ( ! empty( $destination['pendingCountries'] ) ) {
				$has_pending = true;
			}
			if ( ! empty( $destination['approvedCountries'] ) ) {
				$has_approved = true;
			}
		}

		if ( $has_approved && ( $has_disapproved || $has_pending ) ) {
			$eligibility = 'limited';
		} elseif ( $has_disapproved ) {
			$eligibility = 'disapproved';
		} elseif ( $has_pending ) {
			$eligibility = 'pending';
		} elseif ( $has_approved ) {
			$eligibility = 'approved';
		}

		foreach ( $status['itemLevelIssues'] ?? array() as $issue ) {
			$issues[] = array(
				'code'        => (string) ( $issue['code'] ?? '' ),
				'severity'    => (string) ( $issue['severity'] ?? '' ),
				'description' => (string) ( $issue['description'] ?? '' ),
				'countries'   => $issue['applicableCountries'] ?? array(),
			);
		}

		return array(
			'processing'  => isset( $product['productStatus'] ) ? 'processed' : 'pending',
			'eligibility' => $eligibility,
			'data_source' => (string) ( $product['dataSource'] ?? '' ),
			'issues'      => $issues,
		);
	}

	private static function recursive_ksort( array &$value ) {
		foreach ( $value as &$item ) {
			if ( is_array( $item ) ) {
				self::recursive_ksort( $item );
			}
		}
		ksort( $value );
	}

	private function record_error( $product_id, $message ) {
		update_post_meta( (int) $product_id, self::META_API_STATUS, 'error' );
		update_post_meta( (int) $product_id, self::META_LAST_ERROR, KREV_Logger::sanitize( $message ) );
		KREV_Logger::error( sprintf( 'Product %d Merchant sync failed.', (int) $product_id ), array( 'message' => $message ) );
	}
}
