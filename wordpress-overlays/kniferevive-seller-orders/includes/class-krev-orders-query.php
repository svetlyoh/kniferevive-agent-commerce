<?php
defined( 'ABSPATH' ) || exit;

final class KREV_Orders_Query {
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'krev_order_sellers';
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$table = self::table();
			dbDelta( "CREATE TABLE {$table} (
			order_id bigint(20) unsigned NOT NULL,
			seller_id bigint(20) unsigned NOT NULL,
			fulfilled_at datetime NULL,
			is_local_pickup tinyint(1) unsigned NOT NULL DEFAULT 0,
			fulfillment_status varchar(32) NOT NULL DEFAULT 'processing',
			updated_at datetime NOT NULL,
			PRIMARY KEY  (order_id,seller_id),
			KEY seller_id (seller_id),
			KEY local_pickup (is_local_pickup),
			KEY fulfilled_at (fulfilled_at)
		) {$charset};" );
	}

	public static function index_order( $order_or_id ) {
		global $wpdb;
		$order = $order_or_id instanceof WC_Order ? $order_or_id : wc_get_order( $order_or_id );
		if ( ! $order ) {
			return;
		}
		$table = self::table();
		$sellers = KREV_Orders_Permissions::seller_ids_for_order( $order );
		$is_local_pickup = KREV_Shipping_Reader::is_local_pickup( $order ) ? 1 : 0;
		$existing = $wpdb->get_col( $wpdb->prepare( "SELECT seller_id FROM {$table} WHERE order_id = %d", $order->get_id() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( $sellers as $seller_id ) {
			$wpdb->query( $wpdb->prepare( "INSERT INTO {$table} (order_id,seller_id,fulfilled_at,is_local_pickup,updated_at) VALUES (%d,%d,NULL,%d,%s) ON DUPLICATE KEY UPDATE is_local_pickup=VALUES(is_local_pickup),updated_at=VALUES(updated_at)", $order->get_id(), $seller_id, $is_local_pickup, current_time( 'mysql', true ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		foreach ( array_diff( array_map( 'intval', $existing ), $sellers ) as $removed ) {
			$wpdb->delete( $table, array( 'order_id' => $order->get_id(), 'seller_id' => $removed ), array( '%d', '%d' ) );
		}
	}

	public static function seller_order_ids( $seller_id = 0, $pickup_filter = 'all' ) {
		global $wpdb;
		$table = self::table();
		$where = array();
		$params = array();
		if ( $seller_id ) { $where[] = 'seller_id=%d'; $params[] = absint( $seller_id ); }
		if ( 'pickup' === $pickup_filter ) { $where[] = 'is_local_pickup=1'; }
		if ( 'shipping' === $pickup_filter ) { $where[] = 'is_local_pickup=0'; }
		$sql = "SELECT DISTINCT order_id FROM {$table}" . ( $where ? ' WHERE ' . implode( ' AND ', $where ) : '' ) . ' ORDER BY order_id DESC';
		if ( $params ) { $sql = $wpdb->prepare( $sql, $params ); } // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}

	public static function backfill_batch() {
		$page = max( 1, absint( get_option( 'krev_orders_backfill_page', 1 ) ) );
		$result = wc_get_orders( array( 'type' => 'shop_order', 'limit' => 50, 'page' => $page, 'paginate' => true, 'orderby' => 'ID', 'order' => 'ASC' ) );
		foreach ( $result->orders as $order ) { self::index_order( $order ); KREV_Sharpening_Orders::initialize( $order ); }
		if ( $page < (int) $result->max_num_pages ) {
			update_option( 'krev_orders_backfill_page', $page + 1, false );
			wp_schedule_single_event( time() + 10, 'krev_orders_backfill' );
		} else {
			delete_option( 'krev_orders_backfill_page' );
		}
	}

	public static function refresh_recent_index() {
		$cache_key = 'krev_pickup_refresh_recent';
		if ( get_transient( $cache_key ) ) {
			return;
		}
		try {
			$orders = wc_get_orders( array( 'type' => 'shop_order', 'limit' => 100, 'orderby' => 'date', 'order' => 'DESC' ) );
		} catch ( Throwable $error ) {
			if ( function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->error( $error->getMessage(), array( 'source' => 'kniferevive-seller-orders-pickup-refresh' ) );
			}
			return;
		}
		foreach ( $orders as $order ) {
			try {
				self::index_order( $order );
			} catch ( Throwable $error ) {
				if ( function_exists( 'wc_get_logger' ) ) {
					wc_get_logger()->error( $error->getMessage(), array( 'source' => 'kniferevive-seller-orders-pickup-refresh', 'order_id' => $order instanceof WC_Order ? $order->get_id() : 0 ) );
				}
			}
		}
		set_transient( $cache_key, KREV_ORDERS_VERSION, 5 * MINUTE_IN_SECONDS );
	}

	public static function queue_recent_refresh() {
		if ( ! get_transient( 'krev_pickup_refresh_recent' ) && ! wp_next_scheduled( 'krev_orders_refresh_recent' ) ) {
			wp_schedule_single_event( time() + 1, 'krev_orders_refresh_recent' );
		}
	}

	public static function orders_for_user( $tab = 'all', $page = 1, $per_page = 20 ) {
		$allowed_tabs = array( 'needs-fulfillment', 'local-pickup', 'returns', 'completed', 'cancelled-refunded', 'all' );
		$tab = in_array( $tab, $allowed_tabs, true ) ? $tab : 'needs-fulfillment';
		$args = array( 'type' => 'shop_order', 'limit' => absint( $per_page ), 'page' => max( 1, absint( $page ) ), 'paginate' => true, 'orderby' => 'date', 'order' => 'DESC' );
		if ( 'local-pickup' === $tab ) {
			// Never scan/reindex WooCommerce orders inside the account request. That
			// was the source of the Local Pickup timeout/fatal. Queue the bounded
			// repair and answer from the seller index; no rows is a normal empty state.
			self::queue_recent_refresh();
		}
		$pickup_filter = 'local-pickup' === $tab ? 'pickup' : ( 'needs-fulfillment' === $tab ? 'shipping' : 'all' );
		if ( ! KREV_Orders_Permissions::is_manager() ) {
			$ids = self::seller_order_ids( get_current_user_id(), $pickup_filter );
			if ( empty( $ids ) ) {
				return (object) array( 'orders' => array(), 'total' => 0, 'max_num_pages' => 0 );
			}
			$args['include'] = $ids;
		} elseif ( 'all' !== $pickup_filter ) {
			$ids = self::seller_order_ids( 0, $pickup_filter );
			if ( empty( $ids ) ) {
				return (object) array( 'orders' => array(), 'total' => 0, 'max_num_pages' => 0 );
			}
			$args['include'] = $ids;
		}
		if ( 'needs-fulfillment' === $tab ) {
			$args['status'] = array( 'wc-processing', 'wc-on-hold' );
		} elseif ( 'completed' === $tab ) {
			$args['status'] = array( 'wc-completed' );
		} elseif ( 'cancelled-refunded' === $tab ) {
			$args['status'] = array( 'wc-cancelled', 'wc-refunded', 'wc-failed' );
		}
		try {
			$result = wc_get_orders( $args );
		} catch ( Throwable $error ) {
			if ( function_exists( 'wc_get_logger' ) ) { wc_get_logger()->error( $error->getMessage(), array( 'source' => 'kniferevive-seller-orders-query', 'tab' => $tab ) ); }
			return (object) array( 'orders' => array(), 'total' => 0, 'max_num_pages' => 0 );
		}
		if ( 'local-pickup' === $tab ) {
			// Existing index rows can contain classifications made by older plugin
			// versions. Re-check only the already-paginated results through the
			// canonical reader so shipping orders disappear immediately.
			$result->orders = array_values( array_filter( (array) $result->orders, static function( $order ) { return $order instanceof WC_Order && KREV_Shipping_Reader::is_local_pickup( $order ); } ) );
			$result->total = count( $result->orders );
			$result->max_num_pages = $result->orders ? 1 : 0;
		}
		if ( 'returns' === $tab ) {
			$result->orders = array_values( array_filter( $result->orders, static function ( $order ) { return KREV_Returns::order_has_active_return_for_user( $order->get_id() ); } ) );
			$result->total = count( $result->orders );
			$result->max_num_pages = 1;
		}
		return $result;
	}

	public static function mark_fulfilled( $order_id, $seller_id ) {
		global $wpdb;
		self::index_order( $order_id );
		return false !== $wpdb->update( self::table(), array( 'fulfilled_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ), array( 'order_id' => absint( $order_id ), 'seller_id' => absint( $seller_id ) ), array( '%s', '%s' ), array( '%d', '%d' ) );
	}

	public static function fulfilled_at( $order_id, $seller_id ) {
		global $wpdb;
		return (string) $wpdb->get_var( $wpdb->prepare( 'SELECT fulfilled_at FROM ' . self::table() . ' WHERE order_id=%d AND seller_id=%d', $order_id, $seller_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function fulfillment_status( $order_id, $seller_id ) {
		global $wpdb;
		$status = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT fulfillment_status FROM ' . self::table() . ' WHERE order_id=%d AND seller_id=%d', $order_id, $seller_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $status ?: 'processing';
	}

	public static function fulfillment_options( WC_Order $order ) {
		return KREV_Shipping_Reader::is_local_pickup( $order )
			? array( 'processing' => '● Processing', 'ready-for-pickup' => '◆ Ready for Pickup', 'picked-up' => '✓ Picked Up' )
			: array( 'processing' => '● Processing', 'ready-to-ship' => '▣ Ready to Ship', 'shipped' => '➜ Shipped', 'delivered' => '✓ Delivered' );
	}

	public static function set_fulfillment_status( WC_Order $order, $seller_id, $status ) {
		global $wpdb;
		self::index_order( $order );
		$status = sanitize_key( $status );
		if ( ! isset( self::fulfillment_options( $order )[ $status ] ) ) { return new WP_Error( 'krev_fulfillment_status', __( 'Choose a valid fulfillment status.', 'kniferevive-seller-orders' ), array( 'status' => 422 ) ); }
		$updated = $wpdb->update( self::table(), array( 'fulfillment_status' => $status, 'updated_at' => current_time( 'mysql', true ) ), array( 'order_id' => $order->get_id(), 'seller_id' => absint( $seller_id ) ), array( '%s', '%s' ), array( '%d', '%d' ) );
		return false === $updated ? new WP_Error( 'krev_fulfillment_save', __( 'Fulfillment status could not be saved.', 'kniferevive-seller-orders' ), array( 'status' => 500 ) ) : $status;
	}

	public static function all_fulfilled( WC_Order $order ) {
		$seller_ids = KREV_Orders_Permissions::seller_ids_for_order( $order );
		$has_sharpening = KREV_Sharpening_Orders::is_sharpening_order( $order );
		if ( $has_sharpening && 'yes' !== $order->get_meta( '_krev_sharpening_completed', true, 'edit' ) ) { return false; }
		foreach ( $seller_ids as $seller_id ) {
			if ( ! self::fulfilled_at( $order->get_id(), $seller_id ) ) {
				return false;
			}
		}
		return $has_sharpening || ! empty( $seller_ids );
	}
}
