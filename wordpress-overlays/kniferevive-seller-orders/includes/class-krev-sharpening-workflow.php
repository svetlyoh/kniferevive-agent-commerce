<?php
defined( 'ABSPATH' ) || exit;

final class KREV_Sharpening_Workflow {
	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'krev_sharpening_audit';
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$table = self::table();
		dbDelta( "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint(20) unsigned NOT NULL,
			old_stage varchar(40) NOT NULL,
			new_stage varchar(40) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			note text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY order_id (order_id)
		) {$charset};" );
	}

	public static function audit( $order_id, $old_stage, $new_stage, $user_id, $note = '' ) {
		global $wpdb;
		$wpdb->insert( self::table(), array( 'order_id' => absint( $order_id ), 'old_stage' => sanitize_key( $old_stage ), 'new_stage' => sanitize_key( $new_stage ), 'user_id' => absint( $user_id ), 'note' => sanitize_textarea_field( $note ), 'created_at' => current_time( 'mysql', true ) ) );
	}

	public static function history( $order_id, $include_notes = false ) {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE order_id=%d ORDER BY id ASC', absint( $order_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $include_notes ) {
			foreach ( $rows as &$row ) {
				unset( $row['note'], $row['user_id'] );
			}
		}
		return $rows;
	}

	public static function dto( WC_Order $order ) {
		$current = sanitize_key( $order->get_meta( '_krev_sharpening_stage', true, 'edit' ) ?: 'handoff' );
		$keys = array_keys( KREV_Sharpening_Assets::stages() );
		$current_index = array_search( $current, $keys, true );
		$current_index = false === $current_index ? 1 : $current_index;
		$completed = 'yes' === $order->get_meta( '_krev_sharpening_completed', true, 'edit' );
		$stages = array();
		foreach ( KREV_Sharpening_Assets::stages() as $key => $step ) {
			$index = array_search( $key, $keys, true );
			if ( 'pay' === $key && $order->is_paid() ) {
				$state = 'completed';
			} elseif ( $completed || $index < $current_index ) {
				$state = 'completed';
			} elseif ( $index === $current_index ) {
				$state = 'current';
			} else {
				$state = 'upcoming';
			}
			$stages[] = array( 'key' => $key, 'number' => $step['number'], 'label' => $step['label'], 'state' => $state, 'icon' => KREV_Sharpening_Assets::url( $step['file'] ) );
		}
		$items = array();
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( KREV_Sharpening_Orders::is_sharpening_product( $item->get_product() ) ) {
				$items[] = array( 'item_id' => $item->get_id(), 'name' => $item->get_name(), 'quantity' => $item->get_quantity() );
			}
		}
		return array(
			'order_id' => $order->get_id(), 'order_number' => $order->get_order_number(), 'woocommerce_status' => $order->get_status(), 'payment_state' => $order->is_paid() ? 'paid' : 'payment-due',
			'current_stage' => $current, 'workflow_completed' => $completed, 'stages' => $stages, 'services' => $items,
			'handoff' => (string) $order->get_meta( '_krev_sharpening_handoff', true, 'edit' ), 'expected_ready_date' => (string) $order->get_meta( '_krev_sharpening_expected_ready', true, 'edit' ),
			'customer_notes' => $order->get_customer_note(), 'completion_information' => (string) $order->get_meta( '_krev_sharpening_completion_info', true, 'edit' ),
			'history' => self::history( $order->get_id(), KREV_Orders_Permissions::is_operator() ),
		);
	}

	public static function change_stage( WC_Order $order, $new_stage, $note = '', $complete = false, array $fields = array() ) {
		if ( ! KREV_Orders_Permissions::is_operator() ) {
			return new WP_Error( 'krev_sharpening_forbidden', __( 'Only authorized sharpening operators can change stages.', 'kniferevive-seller-orders' ), array( 'status' => 403 ) );
		}
		if ( $order->has_status( array( 'cancelled', 'refunded', 'failed' ) ) ) {
			return new WP_Error( 'krev_sharpening_order_state', __( 'Cancelled, refunded, or failed orders cannot advance through sharpening.', 'kniferevive-seller-orders' ), array( 'status' => 409 ) );
		}
		$new_stage = sanitize_key( $new_stage );
		$stages = KREV_Sharpening_Assets::stages();
		if ( ! isset( $stages[ $new_stage ] ) ) {
			return new WP_Error( 'krev_sharpening_stage', __( 'Unknown sharpening stage.', 'kniferevive-seller-orders' ), array( 'status' => 422 ) );
		}
		if ( 'return' === $new_stage && ! $order->is_paid() ) {
			return new WP_Error( 'krev_sharpening_payment', __( 'Payment must be recorded by WooCommerce before moving to Get them back.', 'kniferevive-seller-orders' ), array( 'status' => 409 ) );
		}
		if ( $complete && 'yes' === $order->get_meta( '_krev_sharpening_completed', true, 'edit' ) ) { return self::dto( $order ); }
		$old = sanitize_key( $order->get_meta( '_krev_sharpening_stage', true, 'edit' ) ?: 'handoff' );
		$order->update_meta_data( '_krev_sharpening_stage', $new_stage );
		// Returning to an earlier work step reopens the sharpening workflow only.
		// Payment and the WooCommerce order status are independent facts.
		if ( 'return' !== $new_stage && 'yes' === $order->get_meta( '_krev_sharpening_completed', true, 'edit' ) ) {
			$order->delete_meta_data( '_krev_sharpening_completed' );
			$order->delete_meta_data( '_krev_sharpening_completed_at' );
		}
		$mapping = array( 'handoff' => '_krev_sharpening_handoff', 'expected_ready_date' => '_krev_sharpening_expected_ready', 'completion_information' => '_krev_sharpening_completion_info', 'loaner_chef' => '_krev_loaner_chef', 'loaner_paring' => '_krev_loaner_paring' );
		foreach ( $mapping as $input => $meta ) {
			if ( array_key_exists( $input, $fields ) ) {
				$order->update_meta_data( $meta, sanitize_text_field( $fields[ $input ] ) );
			}
		}
		if ( $complete && 'return' === $new_stage ) {
			$order->update_meta_data( '_krev_sharpening_completed', 'yes' );
			$order->update_meta_data( '_krev_sharpening_completed_at', current_time( 'mysql', true ) );
			if ( $order->is_paid() && KREV_Orders_Query::all_fulfilled( $order ) && ! $order->has_status( array( 'cancelled', 'refunded', 'failed', 'completed' ) ) ) {
				$order->update_status( 'completed', __( 'KnifeRevive sharpening workflow completed.', 'kniferevive-seller-orders' ), true );
			}
		}
		$order->save();
		self::audit( $order->get_id(), $old, $new_stage, get_current_user_id(), $note );
		self::notify_customer( $order, $new_stage );
		return self::dto( $order );
	}

	private static function notify_customer( WC_Order $order, $stage ) {
		$messages = array( 'blade-check' => 'We received your knives / Blade check', 'sharpen-shape' => 'Sharpening has started', 'hone-test' => 'Hone & test', 'pay' => 'Your knives are ready', 'return' => 'Get them back / pickup-return information' );
		if ( empty( $messages[ $stage ] ) || ! $order->get_billing_email() ) {
			return;
		}
		wp_mail( $order->get_billing_email(), sprintf( '[KnifeRevive] %s — Order #%s', $messages[ $stage ], $order->get_order_number() ), sprintf( 'Your KnifeRevive sharpening order is now at: %s.', $messages[ $stage ] ) );
	}
}
