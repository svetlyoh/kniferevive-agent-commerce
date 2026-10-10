<?php
defined( 'ABSPATH' ) || exit;

final class KREV_Orders_Endpoints {
	const ENDPOINTS = array( 'seller-orders', 'shipping-policies', 'sharpening-orders' );

	public function register() {
		add_action( 'init', array( __CLASS__, 'rewrite' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'menu' ), 90 );
		foreach ( self::ENDPOINTS as $endpoint ) {
			add_action( 'woocommerce_account_' . $endpoint . '_endpoint', array( $this, str_replace( '-', '_', $endpoint ) ) );
		}
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 100 );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	public function body_class( $classes ) {
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'seller-orders' ) ) { $classes[] = 'krev-seller-orders-focus'; }
		return $classes;
	}

	public static function rewrite() {
		foreach ( self::ENDPOINTS as $endpoint ) {
			add_rewrite_endpoint( $endpoint, EP_ROOT | EP_PAGES );
		}
	}

	public function menu( $items ) {
		$seller = KREV_Orders_Permissions::current_user_is_seller();
		$sharpening = KREV_Orders_Permissions::is_operator() || $this->customer_has_sharpening_orders();
		if ( ! $seller && ! $sharpening ) {
			if ( isset( $items['orders'] ) ) { $items['orders'] = __( 'Purchases', 'kniferevive-seller-orders' ); }
			return $items;
		}
		$out = array();
		if ( $seller && isset( $items['listlab'] ) ) { $out['listlab'] = __( 'My Listings', 'kniferevive-seller-orders' ); }
		if ( $seller ) {
			$out['seller-orders'] = __( 'Seller Orders', 'kniferevive-seller-orders' );
			$out['shipping-policies'] = __( 'Shipping Policies', 'kniferevive-seller-orders' );
		}
		if ( $sharpening ) { $out['sharpening-orders'] = __( 'Sharpening Orders', 'kniferevive-seller-orders' ); }
		$out['orders'] = __( 'Purchases', 'kniferevive-seller-orders' );
		foreach ( array( 'edit-address' => 'Addresses', 'payment-methods' => 'Payment Methods', 'edit-account' => 'Account Details', 'customer-logout' => '' ) as $key => $fallback ) {
			if ( isset( $items[ $key ] ) ) { $out[ $key ] = $fallback ? __( $fallback, 'kniferevive-seller-orders' ) : $items[ $key ]; }
		}
		return $out;
	}

	private function customer_has_sharpening_orders() {
		if ( ! get_current_user_id() ) { return false; }
		$result = wc_get_orders( array( 'customer_id' => get_current_user_id(), 'limit' => 20, 'orderby' => 'date', 'order' => 'DESC' ) );
		return (bool) array_filter( $result, array( 'KREV_Sharpening_Orders', 'is_sharpening_order' ) );
	}

	public function seller_orders( $value = '' ) {
		if ( ! KREV_Orders_Permissions::current_user_is_seller() ) { wc_print_notice( __( 'Seller Orders is available to marketplace sellers.', 'kniferevive-seller-orders' ), 'notice' ); return; }
		$order_id = absint( $value );
		if ( $order_id ) { $order = wc_get_order( $order_id ); $dto = $order ? KREV_Orders_Reader::dto( $order ) : new WP_Error( 'missing', 'Order not found.' ); include KREV_ORDERS_PATH . 'templates/seller-order-detail.php'; return; }
		$tab = sanitize_key( wp_unslash( $_GET['tab'] ?? 'needs-fulfillment' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $tab, array( 'needs-fulfillment', 'local-pickup', 'returns', 'completed', 'cancelled-refunded', 'all' ), true ) ) { $tab = 'needs-fulfillment'; }
		$page = absint( $_GET['orders-page'] ?? 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		try { $result = KREV_Orders_Query::orders_for_user( $tab, $page ); } catch ( Throwable $error ) { self::log_endpoint_error( $error, $tab ); $result = (object) array( 'orders' => array(), 'total' => 0, 'max_num_pages' => 0 ); }
		$sharpening_result = null;
		if ( 'local-pickup' === $tab && KREV_Orders_Permissions::is_operator() ) {
			try { $sharpening_result = KREV_Sharpening_Orders::orders_for_current_user(); } catch ( Throwable $error ) { self::log_endpoint_error( $error, 'local-pickup-sharpening' ); }
		}
		// The commerce plugin supplies authorized, read-only appointment cards.
		$appointment_cards = apply_filters( 'krev_seller_orders_appointments', array(), $tab, $page );
		$appointment_ids = array_map( 'absint', array_column( $appointment_cards, 'order_id' ) );
		$result->orders = array_values( array_filter( $result->orders, static function( $order ) use ( $appointment_ids ) { return ! in_array( $order->get_id(), $appointment_ids, true ); } ) );
		if ( $sharpening_result ) { $sharpening_result->orders = array_values( array_filter( $sharpening_result->orders, static function( $order ) use ( $appointment_ids ) { return ! in_array( $order->get_id(), $appointment_ids, true ); } ) ); }
		include KREV_ORDERS_PATH . 'templates/seller-orders.php';
	}

	private static function log_endpoint_error( Throwable $error, $context ) {
		if ( function_exists( 'wc_get_logger' ) ) { wc_get_logger()->error( $error->getMessage(), array( 'source' => 'kniferevive-seller-orders-endpoint', 'context' => sanitize_key( $context ) ) ); }
	}

	public function shipping_policies() {
		if ( ! KREV_Orders_Permissions::current_user_is_seller() ) { wc_print_notice( __( 'Shipping Policies is available to marketplace sellers.', 'kniferevive-seller-orders' ), 'notice' ); return; }
		$policies = KREV_Shipping_Policies::all( true ); include KREV_ORDERS_PATH . 'templates/shipping-policies.php';
	}

	public function sharpening_orders( $value = '' ) {
		$order_id = absint( $value );
		if ( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! KREV_Sharpening_Orders::can_view_order( $order ) ) { wc_print_notice( __( 'Sharpening order not found.', 'kniferevive-seller-orders' ), 'error' ); return; }
			$sharpening = KREV_Sharpening_Workflow::dto( $order ); include KREV_ORDERS_PATH . 'templates/sharpening-order-detail.php'; return;
		}
		$result = KREV_Sharpening_Orders::orders_for_current_user(); include KREV_ORDERS_PATH . 'templates/sharpening-orders.php';
	}

	public function assets() {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) { return; }
		wp_enqueue_style( 'krev-seller-orders', KREV_ORDERS_URL . 'assets/css/seller-orders.css', array(), KREV_ORDERS_VERSION );
		wp_enqueue_script( 'krev-seller-orders', KREV_ORDERS_URL . 'assets/js/seller-orders.js', array(), KREV_ORDERS_VERSION, true );
		wp_localize_script( 'krev-seller-orders', 'KREVOrders', array( 'root' => esc_url_raw( rest_url( 'kniferevive/v1/' ) ), 'nonce' => wp_create_nonce( 'wp_rest' ), 'sellerOrdersUrl' => wc_get_account_endpoint_url( 'seller-orders' ), 'sharpeningOrdersUrl' => wc_get_account_endpoint_url( 'sharpening-orders' ) ) );
	}
}
