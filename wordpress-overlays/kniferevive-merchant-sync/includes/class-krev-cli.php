<?php

defined( 'ABSPATH' ) || exit;

final class KREV_CLI {
	public static function init() {
		if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
			WP_CLI::add_command( 'krev-merchant', __CLASS__ );
		}
	}

	public function status() {
		$this->print_json(
			array(
				'config'             => KREV_Merchant_Config::safe_status(),
				'test_gate_approved' => (bool) get_option( 'krev_merchant_bulk_approved', false ),
				'connection'         => (array) get_option( 'krev_merchant_connection_status', array() ),
				'reconciliation'     => (array) get_option( 'krev_merchant_reconciliation_progress', array() ),
			)
		);
	}

	public function auth() {
		$result = KREV_Google_Auth::test();
		$this->finish( $result['success'], $result );
	}

	public function register() {
		$result = ( new KREV_Merchant_API() )->register_gcp();
		$this->api_result( $result, array( 'name', 'gcpIds' ) );
	}

	public function verify_datasource() {
		$result = ( new KREV_Merchant_API() )->get_datasource();
		$this->api_result( $result, array( 'name', 'dataSourceId', 'displayName', 'input', 'primaryProductDataSource' ) );
	}

	public function inventory() {
		$api      = new KREV_Merchant_API();
		$sources  = $api->list_datasources();
		$products = $api->list_products();
		if ( is_wp_error( $sources ) || is_wp_error( $products ) ) {
			$this->finish( false, array( 'sources_error' => is_wp_error( $sources ) ? $sources->get_error_message() : '', 'products_error' => is_wp_error( $products ) ? $products->get_error_message() : '' ) );
		}
		$counts = array();
		foreach ( $products['products'] ?? array() as $product ) {
			$source = (string) ( $product['dataSource'] ?? 'unknown' );
			$counts[ $source ] = (int) ( $counts[ $source ] ?? 0 ) + 1;
		}
		$safe_sources = array_map(
			static function ( $source ) {
				$type = 'unknown';
				foreach ( array( 'primaryProductDataSource', 'supplementalProductDataSource', 'localInventoryDataSource', 'regionalInventoryDataSource', 'promotionDataSource' ) as $candidate ) {
					if ( isset( $source[ $candidate ] ) ) {
						$type = $candidate;
						break;
					}
				}
				return array( 'name' => $source['name'] ?? '', 'displayName' => $source['displayName'] ?? '', 'input' => $source['input'] ?? '', 'type' => $type );
			},
			$sources['dataSources'] ?? array()
		);
		$this->print_json( array( 'data_sources' => $safe_sources, 'processed_products_by_source' => $counts ) );
	}

	public function preview( $args, $assoc_args ) {
		$product_id = absint( $assoc_args['product'] ?? 0 );
		$result     = ( new KREV_Product_Sync() )->preview( $product_id, false );
		$this->finish( ! is_wp_error( $result ), is_wp_error( $result ) ? array( 'error' => $result->get_error_message() ) : $result );
	}

	/**
	 * Save redacted, unsent payload samples for populated logical groups.
	 *
	 * ## OPTIONS
	 *
	 * --output-dir=<path>
	 * : Existing directory that will receive one JSON file per populated group.
	 */
	public function samples( $args, $assoc_args ) {
		$output_dir = isset( $assoc_args['output-dir'] ) ? wp_normalize_path( $assoc_args['output-dir'] ) : '';
		if ( '' === $output_dir || ! is_dir( $output_dir ) ) {
			WP_CLI::error( 'Use --output-dir=<existing directory>.' );
		}
		if ( ! class_exists( 'KREV_PA_Config' ) ) {
			WP_CLI::error( 'KnifeRevive Product Attributes must be active.' );
		}

		$wanted = array( 'knives', 'tech', 'art', 'world-coins' );
		$samples = array();
		$product_ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);
		$sync = new KREV_Product_Sync();
		foreach ( $product_ids as $product_id ) {
			$groups = KREV_PA_Config::groups_for_product( $product_id );
			foreach ( array_intersect( $wanted, $groups ) as $group ) {
				if ( isset( $samples[ $group ] ) ) {
					continue;
				}
				$payload = $sync->preview( $product_id, false );
				if ( is_wp_error( $payload ) || empty( $payload['productAttributes']['productDetails'] ) ) {
					continue;
				}
				$safe = KREV_Logger::sanitize( $payload );
				$path = trailingslashit( $output_dir ) . 'KnifeRevive_Merchant_Payload_' . sanitize_file_name( $group ) . '.json';
				$json = wp_json_encode( $safe, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				if ( false === file_put_contents( $path, $json ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
					WP_CLI::error( 'Could not write sample: ' . $path );
				}
				$samples[ $group ] = array( 'product_id' => $product_id, 'path' => $path );
			}
		}

		$this->print_json(
			array(
				'generated_without_sending' => true,
				'samples'                   => $samples,
				'groups_without_products'   => array_values( array_diff( $wanted, array_keys( $samples ) ) ),
			)
		);
	}

	public function sync( $args, $assoc_args ) {
		$product_id = absint( $assoc_args['product'] ?? 0 );
		if ( ! $product_id ) {
			WP_CLI::error( 'Use --product=<WooCommerce product ID>.' );
		}
		$result = ( new KREV_Product_Sync() )->sync( $product_id, true );
		$safe   = is_wp_error( $result ) ? array( 'error' => $result->get_error_message() ) : array( 'status' => $result['status'] ?? '', 'input_name' => $result['input_name'] ?? '', 'product_name' => $result['product_name'] ?? '', 'payload_hash' => $result['payload_hash'] ?? '' );
		$this->finish( ! is_wp_error( $result ), $safe );
	}

	public function processed( $args, $assoc_args ) {
		$product_id = absint( $assoc_args['product'] ?? 0 );
		$result     = ( new KREV_Product_Sync() )->refresh_processed_status( $product_id );
		$this->finish( ! is_wp_error( $result ), is_wp_error( $result ) ? array( 'error' => $result->get_error_message() ) : $result['summary'] );
	}

	public function reconcile() {
		$result = KREV_Sync_Queue::queue_reconciliation();
		$this->finish( ! is_wp_error( $result ), is_wp_error( $result ) ? array( 'error' => $result->get_error_message() ) : $result );
	}

	private function api_result( $result, array $allowed_keys ) {
		if ( is_wp_error( $result ) ) {
			$this->finish( false, array( 'error' => $result->get_error_message(), 'data' => $result->get_error_data() ) );
		}
		$safe = array();
		foreach ( $allowed_keys as $key ) {
			$safe[ $key ] = $result[ $key ] ?? null;
		}
		$this->finish( true, $safe );
	}

	private function finish( $success, array $data ) {
		$this->print_json( array_merge( array( 'success' => (bool) $success ), $data ) );
		if ( ! $success ) {
			WP_CLI::halt( 1 );
		}
	}

	private function print_json( array $data ) {
		WP_CLI::line( (string) wp_json_encode( KREV_Logger::sanitize( $data ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}
}
