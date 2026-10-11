<?php
defined( 'ABSPATH' ) || exit;

final class KREV_ListLab_Account_Dashboard {
	public static function invalidate_product( $product_id ) {
		$seller_id = (int) get_post_field( 'post_author', $product_id );
		if ( $seller_id ) { delete_transient( 'krev_seller_overview_' . $seller_id ); }
	}

	public static function invalidate_order( $order_id ) {
		if ( ! class_exists( 'KREV_Orders_Permissions' ) ) { return; }
		$order = wc_get_order( $order_id );
		if ( ! $order ) { return; }
		foreach ( KREV_Orders_Permissions::seller_ids_for_order( $order ) as $seller_id ) { delete_transient( 'krev_seller_overview_' . $seller_id ); }
	}

	public static function metrics( $seller_id ) {
		$seller_id = absint( $seller_id );
		$key = 'krev_seller_overview_' . $seller_id;
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) { return $cached; }
		$data = array( 'active_listings' => 0, 'needs_fulfillment' => 0, 'gross_30d' => 0.0, 'sold_items' => 0 );

		try {
			$listings = new WP_Query( array( 'post_type' => 'product', 'post_status' => 'publish', 'author' => $seller_id, 'fields' => 'ids', 'posts_per_page' => 1, 'meta_query' => array( 'relation' => 'OR', array( 'key' => '_krev_listlab_archived', 'compare' => 'NOT EXISTS' ), array( 'key' => '_krev_listlab_archived', 'value' => 'no' ) ) ) );
			$data['active_listings'] = (int) $listings->found_posts;
		} catch ( Throwable $error ) { self::log_error( $error, 'active-listings' ); }

		if ( class_exists( 'KREV_Orders_Query' ) ) {
			try { $ids = (array) KREV_Orders_Query::seller_order_ids( $seller_id, 'all' ); } catch ( Throwable $error ) { self::log_error( $error, 'order-ids' ); $ids = array(); }
			$cutoff = time() - 30 * DAY_IN_SECONDS;
			foreach ( array_chunk( $ids, 100 ) as $chunk ) {
				try { $orders = wc_get_orders( array( 'include' => $chunk, 'limit' => 100, 'status' => array( 'wc-on-hold', 'wc-processing', 'wc-completed' ) ) ); } catch ( Throwable $error ) { self::log_error( $error, 'order-query' ); $orders = array(); }
				foreach ( $orders as $order ) {
					try {
						if ( in_array( $order->get_status(), array( 'on-hold', 'processing' ), true ) && ! KREV_Orders_Query::fulfilled_at( $order->get_id(), $seller_id ) ) { $data['needs_fulfillment']++; }
						$created = $order->get_date_created();
						foreach ( $order->get_items( 'line_item' ) as $item ) {
							$product = $item->get_product();
							if ( ! $product ) { continue; }
							$product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
							if ( $seller_id !== (int) get_post_field( 'post_author', $product_id ) ) { continue; }
							$data['sold_items'] += absint( $item->get_quantity() );
							if ( $created && $created->getTimestamp() >= $cutoff ) { $data['gross_30d'] += (float) $item->get_total(); }
						}
					} catch ( Throwable $error ) { self::log_error( $error, 'order-' . $order->get_id() ); }
				}
			}
		}

		set_transient( $key, $data, 5 * MINUTE_IN_SECONDS );
		return $data;
	}

	private static function log_error( Throwable $error, $context ) {
		if ( function_exists( 'wc_get_logger' ) ) { wc_get_logger()->error( $error->getMessage(), array( 'source' => 'kniferevive-seller-overview', 'context' => sanitize_key( $context ) ) ); }
	}

	public static function render() {
		$user = wp_get_current_user();
		try { $metrics = self::metrics( $user->ID ); } catch ( Throwable $error ) { self::log_error( $error, 'dashboard' ); $metrics = array( 'active_listings' => 0, 'needs_fulfillment' => 0, 'gross_30d' => 0.0, 'sold_items' => 0 ); }
		echo '<section class="krev-seller-overview"><h2>' . esc_html__( 'Seller Overview', 'kniferevive-listlab' ) . '</h2><div class="krev-seller-metrics">';
		$metric_cards = array(
			array( 'Active Listings', number_format_i18n( $metrics['active_listings'] ), wc_get_account_endpoint_url( 'listlab' ) ),
			array( 'Needs Fulfillment', number_format_i18n( $metrics['needs_fulfillment'] ), add_query_arg( 'tab', 'needs-fulfillment', wc_get_account_endpoint_url( 'seller-orders' ) ) ),
			array( '30-Day Gross Sales', wc_price( $metrics['gross_30d'] ), '' ),
			array( 'Sold Items', number_format_i18n( $metrics['sold_items'] ), '' ),
		);
		$metric_cards = apply_filters( 'krev_seller_overview_cards', $metric_cards, $metrics, $user->ID );
		foreach ( $metric_cards as $metric ) {
			echo '<article' . ( ! empty( $metric[3] ) ? ' title="' . esc_attr( $metric[3] ) . '"' : '' ) . '><span>' . esc_html( $metric[0] ) . '</span><strong>';
			if ( $metric[2] ) { echo '<a href="' . esc_url( $metric[2] ) . '">' . esc_html( $metric[1] ) . '</a>'; }
			else { echo wp_kses_post( $metric[1] ); }
			echo '</strong></article>';
		}
		echo '</div>';
		do_action( 'krev_seller_overview_after_cards' );
		echo '</section><section class="krev-account-help"><h3>' . esc_html__( 'Account', 'kniferevive-listlab' ) . '</h3><p>' . sprintf( esc_html__( 'Signed in as %1$s · %2$s', 'kniferevive-listlab' ), esc_html( $user->display_name ), '<a href="' . esc_url( wc_logout_url() ) . '">' . esc_html__( 'Log out', 'kniferevive-listlab' ) . '</a>' ) . '</p><p>' . esc_html__( 'Manage purchases, addresses, payment methods, and account details from the account links.', 'kniferevive-listlab' ) . '</p></section>';
	}
}
