<?php
defined( 'ABSPATH' ) || exit;

final class KREV_Orders_REST {
	const NS = 'kniferevive/v1';

	public function register() {
		register_rest_route( self::NS, '/seller-orders', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'seller_orders' ), 'permission_callback' => array( $this, 'seller_permission' ) ) );
		register_rest_route( self::NS, '/seller-orders/(?P<id>\d+)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'seller_order' ), 'permission_callback' => array( $this, 'logged_in' ) ) );
		register_rest_route( self::NS, '/seller-orders/(?P<id>\d+)/tracking', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'tracking' ), 'permission_callback' => array( $this, 'logged_in' ) ) );
		register_rest_route( self::NS, '/seller-orders/(?P<id>\d+)/fulfill', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'fulfill' ), 'permission_callback' => array( $this, 'logged_in' ) ) );
		register_rest_route( self::NS, '/seller-orders/(?P<id>\d+)/fulfillment-status', array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( $this, 'fulfillment_status' ), 'permission_callback' => array( $this, 'logged_in' ) ) );
		register_rest_route( self::NS, '/shipping-policies', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'shipping_policies' ), 'permission_callback' => array( $this, 'seller_permission' ) ) );
		register_rest_route( self::NS, '/orders/(?P<id>\d+)/returns', array(
			array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'order_returns' ), 'permission_callback' => array( $this, 'logged_in' ) ),
			array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'create_return' ), 'permission_callback' => array( $this, 'logged_in' ) ),
		) );
		register_rest_route( self::NS, '/returns/(?P<return_id>\d+)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'return_detail' ), 'permission_callback' => array( $this, 'logged_in' ) ) );
		foreach ( array( 'approve', 'reject', 'tracking', 'received', 'escalate' ) as $action ) {
			register_rest_route( self::NS, '/returns/(?P<return_id>\d+)/' . $action, array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'return_' . $action ), 'permission_callback' => array( $this, 'logged_in' ) ) );
		}
		register_rest_route( self::NS, '/returns/(?P<return_id>\d+)/refund', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'refund' ), 'permission_callback' => static function () { return current_user_can( 'manage_woocommerce' ); } ) );
		register_rest_route( self::NS, '/sharpening-orders', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'sharpening_orders' ), 'permission_callback' => array( $this, 'logged_in' ) ) );
		register_rest_route( self::NS, '/sharpening-orders/(?P<id>\d+)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'sharpening_order' ), 'permission_callback' => array( $this, 'logged_in' ) ) );
		register_rest_route( self::NS, '/sharpening-orders/(?P<id>\d+)/stage', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'sharpening_stage' ), 'permission_callback' => static function () { return KREV_Orders_Permissions::is_operator(); } ) );
	}

	public function logged_in() { return is_user_logged_in(); }
	public function seller_permission() { return is_user_logged_in() && KREV_Orders_Permissions::current_user_is_seller(); }

	private function order( $id ) {
		$order = wc_get_order( absint( $id ) );
		return $order ?: new WP_Error( 'krev_order_missing', __( 'Order not found.', 'kniferevive-seller-orders' ), array( 'status' => 404 ) );
	}

	private function return_record( $id ) {
		$return = KREV_Returns::get( $id );
		return $return ?: new WP_Error( 'krev_return_missing', __( 'Return request not found.', 'kniferevive-seller-orders' ), array( 'status' => 404 ) );
	}

	public function seller_orders( WP_REST_Request $request ) {
		$result = KREV_Orders_Query::orders_for_user( sanitize_key( $request->get_param( 'tab' ) ?: 'all' ), absint( $request->get_param( 'page' ) ?: 1 ), min( 50, absint( $request->get_param( 'per_page' ) ?: 20 ) ) );
		$data = array();
		foreach ( $result->orders as $order ) { $dto = KREV_Orders_Reader::dto( $order ); if ( ! is_wp_error( $dto ) ) { $data[] = $dto; } }
		return rest_ensure_response( array( 'orders' => $data, 'total' => $result->total, 'pages' => $result->max_num_pages ) );
	}

	public function seller_order( WP_REST_Request $request ) {
		$order = $this->order( $request['id'] ); if ( is_wp_error( $order ) ) { return $order; }
		return rest_ensure_response( KREV_Orders_Reader::dto( $order ) );
	}

	public function tracking( WP_REST_Request $request ) {
		$order = $this->order( $request['id'] ); if ( is_wp_error( $order ) ) { return $order; }
		$result = KREV_Orders_Actions::add_tracking( $order, $request->get_json_params() ?: $request->get_params() );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function fulfill( WP_REST_Request $request ) {
		$order = $this->order( $request['id'] ); if ( is_wp_error( $order ) ) { return $order; }
		$result = KREV_Orders_Actions::fulfill( $order ); return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}
	public function fulfillment_status( WP_REST_Request $request ) {
		$order = $this->order( $request['id'] ); if ( is_wp_error( $order ) ) { return $order; }
		$result = KREV_Orders_Actions::fulfillment_status( $order, $request->get_param( 'status' ) );
		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function shipping_policies() { return rest_ensure_response( array_values( KREV_Shipping_Policies::all( true ) ) ); }

	public function order_returns( WP_REST_Request $request ) {
		$order = $this->order( $request['id'] ); if ( is_wp_error( $order ) ) { return $order; }
		if ( ! KREV_Orders_Permissions::customer_can_view_order( $order ) && ! KREV_Orders_Permissions::seller_can_view_order( $order ) ) { return new WP_Error( 'krev_returns_forbidden', 'You cannot view returns for this order.', array( 'status' => 403 ) ); }
		$rows = KREV_Returns::for_order( $order->get_id(), KREV_Orders_Permissions::is_manager() || KREV_Orders_Permissions::customer_can_view_order( $order ) ? 0 : get_current_user_id() );
		$rows = array_values( array_filter( $rows, array( 'KREV_Return_Permissions', 'can_view' ) ) );
		return rest_ensure_response( $rows );
	}

	private function evidence_uploads( $order_id ) {
		if ( empty( $_FILES['evidence'] ) ) { return array(); }
		$files = $_FILES['evidence']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$names = is_array( $files['name'] ) ? $files['name'] : array( $files['name'] );
		$ids = array();
		require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
		foreach ( array_slice( array_keys( $names ), 0, 5 ) as $index ) {
			$file = array(); foreach ( array( 'name', 'type', 'tmp_name', 'error', 'size' ) as $key ) { $file[ $key ] = is_array( $files[ $key ] ) ? $files[ $key ][ $index ] : $files[ $key ]; }
			if ( UPLOAD_ERR_OK !== (int) $file['error'] || (int) $file['size'] > 5 * MB_IN_BYTES ) { continue; }
			$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) );
			if ( empty( $check['type'] ) || ! wp_getimagesize( $file['tmp_name'] ) ) { continue; }
			$_FILES['krev_single_evidence'] = $file; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$id = media_handle_upload( 'krev_single_evidence', 0, array( 'post_title' => sprintf( 'Return evidence for order %d', $order_id ) ) );
			if ( ! is_wp_error( $id ) ) { $ids[] = $id; update_post_meta( $id, '_krev_return_evidence_owner', get_current_user_id() ); }
		}
		unset( $_FILES['krev_single_evidence'] );
		return $ids;
	}

	public function create_return( WP_REST_Request $request ) {
		$order = $this->order( $request['id'] ); if ( is_wp_error( $order ) ) { return $order; }
		if ( ! KREV_Orders_Permissions::customer_can_view_order( $order ) || KREV_Orders_Permissions::is_manager() ) { return new WP_Error( 'krev_return_customer', 'Only the customer who placed this order may request a return.', array( 'status' => 403 ) ); }
		$params = $request->get_params(); $item = $order->get_item( absint( $params['order_item_id'] ?? 0 ) );
		if ( ! $item instanceof WC_Order_Item_Product ) { return new WP_Error( 'krev_return_item', 'Order item not found.', array( 'status' => 404 ) ); }
		$result = KREV_Returns::create( $order, $item, $params, $this->evidence_uploads( $order->get_id() ) );
		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 201 );
	}

	public function return_detail( WP_REST_Request $request ) {
		$return = $this->return_record( $request['return_id'] ); if ( is_wp_error( $return ) ) { return $return; }
		return KREV_Return_Permissions::can_view( $return ) ? rest_ensure_response( $return ) : new WP_Error( 'krev_return_forbidden', 'You cannot view this return.', array( 'status' => 403 ) );
	}

	private function seller_transition( WP_REST_Request $request, $status, array $fields = array() ) {
		$return = $this->return_record( $request['return_id'] ); if ( is_wp_error( $return ) ) { return $return; }
		if ( ! KREV_Return_Permissions::seller_can_act( $return ) ) { return new WP_Error( 'krev_return_forbidden', 'You cannot update this return.', array( 'status' => 403 ) ); }
		$result = KREV_Returns::transition( $return['id'], $status, $fields ); return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function return_approve( WP_REST_Request $request ) { return $this->seller_transition( $request, 'approved' ); }
	public function return_reject( WP_REST_Request $request ) { $reason = sanitize_textarea_field( $request->get_param( 'reason' ) ); if ( '' === $reason ) { return new WP_Error( 'krev_rejection_reason', 'A rejection reason is required.', array( 'status' => 422 ) ); } return $this->seller_transition( $request, 'rejected', array( 'rejection_reason' => $reason ) ); }
	public function return_received( WP_REST_Request $request ) { $return = $this->seller_transition( $request, 'received', array( 'received_date' => $request->get_param( 'received_date' ) ?: gmdate( 'Y-m-d' ), 'received_quantity' => $request->get_param( 'received_quantity' ), 'condition_note' => $request->get_param( 'condition_note' ) ) ); if ( is_wp_error( $return ) ) { return $return; } KREV_Returns::transition( $request['return_id'], 'refund-pending' ); return rest_ensure_response( KREV_Returns::get( $request['return_id'] ) ); }
	public function return_escalate( WP_REST_Request $request ) { $return = $this->return_record( $request['return_id'] ); if ( is_wp_error( $return ) ) { return $return; } if ( ! KREV_Return_Permissions::can_view( $return ) ) { return new WP_Error( 'krev_return_forbidden', 'You cannot escalate this return.', array( 'status' => 403 ) ); } $result = KREV_Returns::transition( $return['id'], 'escalated' ); return is_wp_error( $result ) ? $result : rest_ensure_response( $result ); }
	public function return_tracking( WP_REST_Request $request ) { $return = $this->return_record( $request['return_id'] ); if ( is_wp_error( $return ) ) { return $return; } if ( ! KREV_Return_Permissions::customer_can_track( $return ) ) { return new WP_Error( 'krev_return_forbidden', 'Only the customer may add return tracking.', array( 'status' => 403 ) ); } $tracking = sanitize_text_field( $request->get_param( 'return_tracking' ) ); if ( '' === $tracking ) { return new WP_Error( 'krev_return_tracking', 'A return tracking number is required.', array( 'status' => 422 ) ); } $result = KREV_Returns::transition( $return['id'], 'return-in-transit', array( 'return_carrier' => $request->get_param( 'return_carrier' ), 'return_tracking' => $tracking, 'date_shipped' => $request->get_param( 'date_shipped' ) ?: gmdate( 'Y-m-d' ) ) ); return is_wp_error( $result ) ? $result : rest_ensure_response( $result ); }

	public function refund( WP_REST_Request $request ) {
		$return = $this->return_record( $request['return_id'] ); if ( is_wp_error( $return ) ) { return $return; }
		$params = $request->get_json_params() ?: $request->get_params();
		if ( empty( $params['confirm'] ) ) { $preview = KREV_Refunds::preview( $return, ! empty( $params['include_shipping'] ) ); return is_wp_error( $preview ) ? $preview : rest_ensure_response( array( 'confirmation_required' => true, 'preview' => $preview ) ); }
		$result = KREV_Refunds::execute( $return, $params ); return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	public function sharpening_orders() { $result = KREV_Sharpening_Orders::orders_for_current_user(); return rest_ensure_response( array( 'orders' => array_map( array( 'KREV_Sharpening_Workflow', 'dto' ), $result->orders ), 'total' => $result->total ) ); }
	public function sharpening_order( WP_REST_Request $request ) { $order = $this->order( $request['id'] ); if ( is_wp_error( $order ) ) { return $order; } if ( ! KREV_Sharpening_Orders::can_view_order( $order ) ) { return new WP_Error( 'krev_sharpening_forbidden', 'You cannot view this sharpening order.', array( 'status' => 403 ) ); } return rest_ensure_response( KREV_Sharpening_Workflow::dto( $order ) ); }
	public function sharpening_stage( WP_REST_Request $request ) { $order = $this->order( $request['id'] ); if ( is_wp_error( $order ) ) { return $order; } if ( ! KREV_Sharpening_Orders::is_sharpening_order( $order ) ) { return new WP_Error( 'krev_sharpening_missing', 'This is not a sharpening order.', array( 'status' => 404 ) ); } $params = $request->get_json_params() ?: $request->get_params(); $result = KREV_Sharpening_Workflow::change_stage( $order, $params['stage'] ?? '', $params['internal_note'] ?? '', ! empty( $params['complete'] ), $params ); return is_wp_error( $result ) ? $result : rest_ensure_response( $result ); }
}
