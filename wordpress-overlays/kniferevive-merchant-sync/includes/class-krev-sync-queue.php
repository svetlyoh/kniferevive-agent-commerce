<?php

defined( 'ABSPATH' ) || exit;

final class KREV_Sync_Queue {
	const GROUP            = 'kniferevive-merchant-sync';
	const SYNC_ACTION      = 'krev_merchant_sync_product';
	const DELETE_ACTION    = 'krev_merchant_delete_input';
	const RECONCILE_ACTION = 'krev_merchant_reconcile_batch';
	const DAILY_ACTION     = 'krev_merchant_daily_reconciliation';

	public static function init() {
		add_action( 'woocommerce_new_product', array( __CLASS__, 'enqueue' ), 20 );
		add_action( 'woocommerce_update_product', array( __CLASS__, 'enqueue' ), 20 );
		add_action( 'woocommerce_product_set_stock', array( __CLASS__, 'enqueue_from_product' ), 20 );
		add_action( 'woocommerce_variation_set_stock', array( __CLASS__, 'enqueue_from_product' ), 20 );
		add_action( 'woocommerce_product_set_stock_status', array( __CLASS__, 'enqueue' ), 20 );
		add_action( 'transition_post_status', array( __CLASS__, 'on_status_transition' ), 20, 3 );
		add_action( 'trashed_post', array( __CLASS__, 'enqueue' ), 20 );
		add_action( 'untrashed_post', array( __CLASS__, 'enqueue' ), 20 );
		add_action( 'before_delete_post', array( __CLASS__, 'on_before_delete' ), 20, 2 );

		add_action( self::SYNC_ACTION, array( __CLASS__, 'run_sync' ), 10, 2 );
		add_action( self::DELETE_ACTION, array( __CLASS__, 'run_delete' ), 10, 2 );
		add_action( self::RECONCILE_ACTION, array( __CLASS__, 'run_reconciliation_batch' ) );
		add_action( self::DAILY_ACTION, array( __CLASS__, 'start_daily_reconciliation' ) );

		if ( ! wp_next_scheduled( self::DAILY_ACTION ) && ! function_exists( 'as_next_scheduled_action' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::DAILY_ACTION );
		}
	}

	public static function activate() {
		if ( function_exists( 'as_schedule_recurring_action' ) && ! as_next_scheduled_action( self::DAILY_ACTION, array(), self::GROUP ) ) {
			as_schedule_recurring_action( time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, self::DAILY_ACTION, array(), self::GROUP, true );
		} elseif ( ! wp_next_scheduled( self::DAILY_ACTION ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::DAILY_ACTION );
		}
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( self::DAILY_ACTION );
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::DAILY_ACTION, array(), self::GROUP );
		}
	}

	public static function enqueue_from_product( $product ) {
		if ( $product instanceof WC_Product ) {
			self::enqueue( $product->get_id() );
		}
	}

	public static function on_status_transition( $new_status, $old_status, $post ) {
		if ( $post instanceof WP_Post && 'product' === $post->post_type && $new_status !== $old_status ) {
			self::enqueue( $post->ID );
		}
	}

	public static function on_before_delete( $post_id, $post ) {
		if ( ! $post instanceof WP_Post || 'product' !== $post->post_type ) {
			return;
		}
		$input_name = (string) get_post_meta( $post_id, KREV_Product_Sync::META_INPUT_NAME, true );
		$data_source = (string) get_post_meta( $post_id, KREV_Product_Sync::META_DATASOURCE_NAME, true );
		if ( '' !== $input_name && KREV_Merchant_Config::datasource_name() === $data_source ) {
			self::schedule( self::DELETE_ACTION, array( $input_name, $data_source ) );
		}
	}

	public static function enqueue( $product_id ) {
		if ( ! get_option( 'krev_merchant_bulk_approved', false ) ) {
			return;
		}
		$product_id = (int) $product_id;
		if ( $product_id <= 0 || 'product' !== get_post_type( $product_id ) ) {
			return;
		}
		self::schedule( self::SYNC_ACTION, array( $product_id, 0 ) );
		KREV_Logger::info( sprintf( 'Product %d queued for Merchant sync.', $product_id ) );
	}

	/** Queue a bounded, already-filtered set of changed category-mapping products. */
	public static function enqueue_many( array $product_ids ) {
		if ( ! get_option( 'krev_merchant_bulk_approved', false ) ) return 0;
		$count = 0;
		foreach ( array_slice( array_values( array_unique( array_map( 'absint', $product_ids ) ) ), 0, 500 ) as $product_id ) {
			if ( $product_id > 0 && 'publish' === get_post_status( $product_id ) ) { self::enqueue( $product_id ); $count++; }
		}
		return $count;
	}

	public static function run_sync( $product_id, $attempt = 0 ) {
		$result = ( new KREV_Product_Sync() )->sync( (int) $product_id );
		if ( ! is_wp_error( $result ) ) {
			return;
		}

		$data      = (array) $result->get_error_data();
		$http_code = (int) ( $data['http_code'] ?? 0 );
		$transient = 0 === $http_code || 429 === $http_code || $http_code >= 500;
		if ( $transient && (int) $attempt < 3 ) {
			$delay = ( 2 ** (int) $attempt ) * MINUTE_IN_SECONDS + random_int( 1, 30 );
			self::schedule( self::SYNC_ACTION, array( (int) $product_id, (int) $attempt + 1 ), time() + $delay );
		}
	}

	public static function run_delete( $input_name, $data_source ) {
		( new KREV_Product_Sync() )->delete_resource( $input_name, $data_source );
	}

	public static function queue_reconciliation() {
		if ( ! get_option( 'krev_merchant_bulk_approved', false ) ) {
			return new WP_Error( 'krev_test_gate_closed', 'Bulk synchronization is locked until the one-product test is verified.' );
		}
		$count = self::published_eligible_count();
		update_option( 'krev_merchant_reconciliation_progress', array( 'eligible' => $count, 'queued' => 0, 'page' => 0, 'started_at' => gmdate( 'c' ) ), false );
		self::queue_excluded_cleanup();
		self::schedule( self::RECONCILE_ACTION, array( 1 ) );

		return array( 'eligible' => $count, 'status' => 'queued' );
	}

	public static function run_reconciliation_batch( $page = 1 ) {
		if ( ! get_option( 'krev_merchant_bulk_approved', false ) ) {
			return;
		}
		$query = new WP_Query(
			array_merge(
				self::eligible_query_args(),
				array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'paged'          => max( 1, (int) $page ),
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				)
			)
		);
		foreach ( $query->posts as $product_id ) {
			self::enqueue( $product_id );
		}
		$progress           = (array) get_option( 'krev_merchant_reconciliation_progress', array() );
		$progress['queued']  = (int) ( $progress['queued'] ?? 0 ) + count( $query->posts );
		$progress['page']    = (int) $page;
		$progress['updated_at'] = gmdate( 'c' );
		update_option( 'krev_merchant_reconciliation_progress', $progress, false );

		if ( (int) $page < (int) $query->max_num_pages ) {
			self::schedule( self::RECONCILE_ACTION, array( (int) $page + 1 ), time() + 10 );
		} else {
			$progress['completed_at'] = gmdate( 'c' );
			update_option( 'krev_merchant_reconciliation_progress', $progress, false );
		}
	}

	public static function start_daily_reconciliation() {
		if ( get_option( 'krev_merchant_bulk_approved', false ) ) {
			self::queue_reconciliation();
		}
	}

	public static function published_eligible_count() {
		$query = new WP_Query(
			array_merge(
				self::eligible_query_args(),
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => 1,
					'fields'                 => 'ids',
					'no_found_rows'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			)
		);

		return (int) $query->found_posts;
	}

	private static function eligible_query_args() {
		$term = get_term_by( 'slug', 'knife-sharpening', 'product_cat' );
		if ( ! $term instanceof WP_Term ) {
			return array();
		}

		return array(
			'tax_query' => array(
				array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => array( $term->term_id ),
					'operator'         => 'NOT IN',
					'include_children' => true,
				),
			),
		);
	}

	private static function queue_excluded_cleanup() {
		$term = get_term_by( 'slug', 'knife-sharpening', 'product_cat' );
		if ( ! $term instanceof WP_Term ) {
			return;
		}

		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => KREV_Product_Sync::META_INPUT_NAME,
				'tax_query'      => array(
					array(
						'taxonomy'         => 'product_cat',
						'field'            => 'term_id',
						'terms'            => array( $term->term_id ),
						'include_children' => true,
					),
				),
			)
		);

		foreach ( $ids as $product_id ) {
			self::enqueue( $product_id );
		}
	}

	private static function schedule( $hook, array $args, $timestamp = null ) {
		if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_enqueue_async_action' ) ) {
			if ( ! as_has_scheduled_action( $hook, $args, self::GROUP ) ) {
				if ( $timestamp && function_exists( 'as_schedule_single_action' ) ) {
					as_schedule_single_action( $timestamp, $hook, $args, self::GROUP, true );
				} else {
					as_enqueue_async_action( $hook, $args, self::GROUP, true );
				}
			}
			return;
		}

		if ( ! wp_next_scheduled( $hook, $args ) ) {
			wp_schedule_single_event( $timestamp ?: time() + 5, $hook, $args );
		}
	}
}
