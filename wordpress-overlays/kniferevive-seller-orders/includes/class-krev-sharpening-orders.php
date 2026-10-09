<?php
defined( 'ABSPATH' ) || exit;

final class KREV_Sharpening_Orders {
	const CATEGORY = 'knife-sharpening';

	public static function is_sharpening_product( $product_or_id ) {
		$product = is_object( $product_or_id ) ? $product_or_id : wc_get_product( absint( $product_or_id ) );
		if ( ! $product ) {
			return false;
		}
		$product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		return has_term( self::CATEGORY, 'product_cat', $product_id );
	}

	public static function is_sharpening_order( $order_or_id ) {
		$order = $order_or_id instanceof WC_Order ? $order_or_id : wc_get_order( $order_or_id );
		if ( ! $order instanceof WC_Order ) {
			return false;
		}
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( self::is_sharpening_product( $item->get_product() ) ) {
				return true;
			}
		}
		return false;
	}

	public static function initialize( $order_or_id ) {
		$order = $order_or_id instanceof WC_Order ? $order_or_id : wc_get_order( $order_or_id );
		if ( ! $order || ! self::is_sharpening_order( $order ) || $order->get_meta( '_krev_sharpening_stage', true, 'edit' ) ) {
			return;
		}
		$order->update_meta_data( '_krev_sharpening_stage', 'handoff' );
		$order->update_meta_data( '_krev_sharpening_started_at', current_time( 'mysql', true ) );
		$order->save();
		KREV_Sharpening_Workflow::audit( $order->get_id(), 'service', 'handoff', 0, 'Created from sharpening checkout.' );
	}

	public static function orders_for_current_user( $page = 1, $per_page = 20 ) {
		$args = array( 'type' => 'shop_order', 'limit' => 50, 'page' => 1, 'paginate' => true, 'orderby' => 'date', 'order' => 'DESC' );
		if ( ! KREV_Orders_Permissions::is_operator() ) {
			$args['customer_id'] = get_current_user_id();
		}
		$matches = array(); $query = null;
		do {
			$query = wc_get_orders( $args );
			$matches = array_merge( $matches, array_filter( $query->orders, array( __CLASS__, 'is_sharpening_order' ) ) );
			$args['page']++;
		} while ( count( $matches ) < absint( $per_page ) * max( 1, absint( $page ) ) && $args['page'] <= min( 10, (int) $query->max_num_pages ) );
		$offset = ( max( 1, absint( $page ) ) - 1 ) * absint( $per_page );
		return (object) array( 'orders' => array_slice( array_values( $matches ), $offset, absint( $per_page ) ), 'total' => count( $matches ), 'max_num_pages' => max( 1, (int) ceil( count( $matches ) / max( 1, absint( $per_page ) ) ) ) );
	}
}
