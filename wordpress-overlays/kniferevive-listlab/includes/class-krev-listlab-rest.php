<?php
defined( 'ABSPATH' ) || exit;

final class KREV_ListLab_REST {
	public function register() {
		$auth = array( 'permission_callback' => array( $this, 'permission' ) );
		register_rest_route( 'kniferevive/v1', '/listings', $auth + array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'listings' ) ) );
		register_rest_route( 'kniferevive/v1', '/listings/(?P<id>\d+)', $auth + array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'listing' ) ) );
		register_rest_route( 'kniferevive/v1', '/listings', $auth + array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'create' ) ) );
		register_rest_route( 'kniferevive/v1', '/listings/(?P<id>\d+)', $auth + array( 'methods' => 'PUT', 'callback' => array( $this, 'update' ) ) );
		register_rest_route( 'kniferevive/v1', '/listings/(?P<id>\d+)/quick-edit', $auth + array( 'methods' => 'PATCH', 'callback' => array( $this, 'quick_edit' ) ) );
		foreach ( array( 'duplicate', 'archive', 'restore', 'publish', 'draft' ) as $action ) { register_rest_route( 'kniferevive/v1', '/listings/(?P<id>\d+)/' . $action, $auth + array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, $action ) ) ); }
		register_rest_route( 'kniferevive/v1', '/listings/(?P<id>\d+)', $auth + array( 'methods' => WP_REST_Server::DELETABLE, 'callback' => array( $this, 'delete' ) ) );
		register_rest_route( 'kniferevive/v1', '/media', $auth + array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'media' ) ) );
		register_rest_route( 'kniferevive/v1', '/media/video', $auth + array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'video' ) ) );
		register_rest_route( 'kniferevive/v1', '/listings/schema', $auth + array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'schema' ) ) );
	}
	public function permission() { return is_user_logged_in() && KREV_ListLab_Dokan_Adapter::can_sell(); }
	public function listings( WP_REST_Request $r ) {
		$tab = sanitize_key( $r->get_param( 'tab' ) ?: 'active' );
		$search = sanitize_text_field( $r->get_param( 'search' ) );
		$meta = array( 'relation' => 'OR', array( 'key' => '_krev_listlab_archived', 'compare' => 'NOT EXISTS' ), array( 'key' => '_krev_listlab_archived', 'value' => 'no' ) );
		$args = array( 'post_type' => 'product', 'author' => get_current_user_id(), 'posts_per_page' => -1, 'meta_query' => $meta );
		if ( 'hidden' === $tab ) { $args['meta_query'] = array( 'relation' => 'AND', array( 'key' => '_krev_listlab_archived', 'value' => 'yes' ), array( 'key' => '_krev_listlab_hidden_from_status', 'value' => 'publish' ) ); $args['post_status'] = 'any'; } elseif ( 'drafts' === $tab ) { $args['post_status'] = array( 'draft', 'pending' ); } else { $args['post_status'] = 'publish'; }
		$sort = sanitize_key( $r->get_param( 'sort' ) ?: 'newest' );
		if ( in_array( $sort, array( 'price_high', 'price_low' ), true ) ) { $args['meta_key'] = '_price'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'price_high' === $sort ? 'DESC' : 'ASC'; }
		elseif ( 'alpha' === $sort ) { $args['orderby'] = 'title'; $args['order'] = 'ASC'; }
		elseif ( 'oldest' === $sort ) { $args['orderby'] = 'date'; $args['order'] = 'ASC'; }
		else { $args['orderby'] = 'date'; $args['order'] = 'DESC'; }
		$q           = new WP_Query( $args );
		$product_ids = wp_list_pluck( $q->posts, 'ID' );
		// View counts are enrichment only. A corrupted product or an unavailable
		// optional analytics table must not make every seller listing disappear.
		$views = KREV_ListLab_Views::totals( $product_ids );
		$items = array();
		foreach ( $product_ids as $id ) {
			try {
				$product = wc_get_product( $id );
				if ( $product instanceof WC_Product ) {
					$items[] = KREV_ListLab_Product_Reader::dto( $product, $views[ $id ] ?? 0 );
				}
			} catch ( Throwable $error ) {
				// Keep the remaining valid listings available to the seller.
				continue;
			}
		}
		if ( $search ) {
			$needle        = strtolower( $search );
			$title_matches = array_values( array_filter( $items, static function( $item ) use ( $needle ) { return false !== strpos( strtolower( $item['title'] ), $needle ); } ) );
			$items         = $title_matches ? $title_matches : array_values( array_filter( $items, static function( $item ) use ( $needle ) { return false !== strpos( strtolower( $item['subtitle'] ), $needle ); } ) );
		}
		$total = count( $items ); $per_page = min( 50, max( 1, absint( $r->get_param( 'per_page' ) ?: 20 ) ) ); $page = max( 1, absint( $r->get_param( 'page' ) ?: 1 ) );
		return array( 'items' => array_slice( $items, ( $page - 1 ) * $per_page, $per_page ), 'total' => $total );
	}
	public function listing( WP_REST_Request $r ) { $product = $this->owned( $r['id'] ); return is_wp_error( $product ) ? $product : KREV_ListLab_Product_Reader::dto( $product ); }
	public function create( WP_REST_Request $r ) { return $this->result( KREV_ListLab_Product_Writer::save( (array) $r->get_json_params() ) ); }
	public function update( WP_REST_Request $r ) { return $this->result( KREV_ListLab_Product_Writer::save( (array) $r->get_json_params(), absint( $r['id'] ) ) ); }
	public function quick_edit( WP_REST_Request $r ) {
		$product = $this->owned( $r['id'] );
		if ( is_wp_error( $product ) ) { return $product; }
		if ( ! $product->is_type( 'simple' ) ) { return new WP_Error( 'listlab_quick_type', 'Only Simple products support quick editing.', array( 'status' => 400 ) ); }
		$data = (array) $r->get_json_params();
		$allowed = array( 'quantity', 'regular_price', 'sale_price' );
		$unknown = array_diff( array_keys( $data ), $allowed );
		if ( $unknown ) { return new WP_Error( 'listlab_quick_fields', 'Unsupported quick-edit field.', array( 'status' => 400 ) ); }
		if ( array_key_exists( 'quantity', $data ) ) {
			$raw = trim( (string) $data['quantity'] );
			if ( ! preg_match( '/^\d+$/', $raw ) ) { return new WP_Error( 'listlab_quantity', 'Quantity must be a whole number of 0 or more.', array( 'status' => 422 ) ); }
			$quantity = absint( $raw );
			$product->set_manage_stock( true );
			$product->set_stock_quantity( $quantity );
			$product->set_stock_status( $quantity > 0 ? 'instock' : 'outofstock' );
		}
		$regular = array_key_exists( 'regular_price', $data ) ? trim( (string) $data['regular_price'] ) : $product->get_regular_price( 'edit' );
		$sale = array_key_exists( 'sale_price', $data ) ? trim( (string) $data['sale_price'] ) : $product->get_sale_price( 'edit' );
		foreach ( array( 'regular_price' => $regular, 'sale_price' => $sale ) as $field => $value ) {
			if ( '' !== $value && ( ! is_numeric( $value ) || (float) $value < 0 ) ) { return new WP_Error( 'listlab_price', 'Prices must be valid non-negative amounts.', array( 'status' => 422, 'field' => $field ) ); }
		}
		$regular = '' === $regular ? '' : wc_format_decimal( $regular );
		$sale = '' === $sale ? '' : wc_format_decimal( $sale );
		if ( '' !== $sale && ( '' === $regular || (float) $sale > (float) $regular ) ) { return new WP_Error( 'listlab_sale_price', 'Sale price cannot be greater than the regular price.', array( 'status' => 422 ) ); }
		if ( array_key_exists( 'regular_price', $data ) ) { $product->set_regular_price( $regular ); }
		if ( array_key_exists( 'sale_price', $data ) ) { $product->set_sale_price( $sale ); }
		$product->save();
		return KREV_ListLab_Product_Reader::dto( wc_get_product( $product->get_id() ) );
	}
	public function archive( WP_REST_Request $r ) { $p = $this->owned( $r['id'] ); if ( is_wp_error( $p ) ) { return $p; } if ( 'publish' !== $p->get_status() ) { return new WP_Error( 'listlab_hide_status', 'Only active listings can be made not visible.', array( 'status' => 400 ) ); } $p->update_meta_data( '_krev_listlab_hidden_from_status', 'publish' ); $p->update_meta_data( '_krev_listlab_archived', 'yes' ); $p->set_status( 'draft' ); $p->save(); return KREV_ListLab_Product_Reader::dto( $p ); }
	public function restore( WP_REST_Request $r ) { $p = $this->owned( $r['id'] ); if ( is_wp_error( $p ) ) { return $p; } $p->update_meta_data( '_krev_listlab_archived', 'no' ); $p->delete_meta_data( '_krev_listlab_hidden_from_status' ); $p->set_status( 'publish' ); $p->set_catalog_visibility( 'visible' ); $p->save(); return KREV_ListLab_Product_Reader::dto( $p ); }
	public function publish( WP_REST_Request $r ) { $p = $this->owned( $r['id'] ); if ( is_wp_error( $p ) ) { return $p; } $attribute_validation = KREV_ListLab_Attributes::validate_product_required_attributes( $p ); if ( is_wp_error( $attribute_validation ) ) { return $attribute_validation; } if ( class_exists( 'KREV_PA_Condition_Resolver' ) ) { $resolved = KREV_PA_Condition_Resolver::resolve( $p ); if ( empty( $resolved['merchant_condition'] ) ) { return new WP_Error( 'listlab_condition', 'Resolve the listing condition before publishing.', array( 'status' => 422, 'errors' => array( 'pa_condition' => $resolved['reason'] ) ) ); } } $p->update_meta_data( '_krev_listlab_archived', 'no' ); $p->delete_meta_data( '_krev_listlab_hidden_from_status' ); $p->set_status( KREV_ListLab_Dokan_Adapter::publish_status() ); $p->set_catalog_visibility( 'visible' ); $p->save(); return KREV_ListLab_Product_Reader::dto( $p ); }
	public function draft( WP_REST_Request $r ) { $p = $this->owned( $r['id'] ); if ( is_wp_error( $p ) ) { return $p; } $p->update_meta_data( '_krev_listlab_archived', 'no' ); $p->delete_meta_data( '_krev_listlab_hidden_from_status' ); $p->set_status( 'draft' ); $p->save(); return KREV_ListLab_Product_Reader::dto( $p ); }
	public function delete( WP_REST_Request $r ) { $p = $this->owned( $r['id'] ); if ( is_wp_error( $p ) ) { return $p; } $id = $p->get_id(); return wp_delete_post( $id, true ) ? array( 'deleted' => true, 'id' => $id ) : new WP_Error( 'listlab_delete', 'Unable to delete this listing.', array( 'status' => 500 ) ); }
	public function duplicate( WP_REST_Request $r ) {
		$product = $this->owned( $r['id'] );
		if ( is_wp_error( $product ) ) {
			return $product;
		}

		// Keep seller-entered listing data (images, taxonomy, attributes, descriptions),
		// but do not let this new listing inherit integration processing history.
		$copy = clone $product;
		$copy->set_id( 0 );
		$copy->set_name( $product->get_name() . ' — Copy' );
		$copy->set_slug( '' );
		$copy->set_status( 'draft' );
		$copy->set_sku( '' );
		$copy->set_global_unique_id( '' );
		$copy->set_date_on_sale_from( null );
		$copy->set_date_on_sale_to( null );

		foreach ( $this->duplicate_runtime_meta_keys() as $meta_key ) {
			$copy->delete_meta_data( $meta_key );
		}
		foreach ( array( KREV_ListLab_Whatnot_Demo::ENABLED, KREV_ListLab_Whatnot_Demo::URL, KREV_ListLab_Whatnot_Demo::START, KREV_ListLab_Whatnot_Demo::TIMEZONE ) as $meta_key ) { $copy->delete_meta_data( $meta_key ); }

		$copy->update_meta_data( '_krev_listlab_archived', 'no' );
		if ( class_exists( 'KREV_PA_Condition_Resolver' ) ) { $copy->update_meta_data( KREV_PA_Condition_Resolver::META_EVIDENCE_REVIEW, 'yes' ); }
		$copy->delete_meta_data( '_krev_listlab_client_uuid' );
		$id = $copy->save();

		wp_update_post( array( 'ID' => $id, 'post_author' => get_current_user_id() ) );

		return KREV_ListLab_Product_Reader::dto( wc_get_product( $id ) );
	}

	/**
	 * Return metadata that records an integration's processing state rather than
	 * seller-entered catalog data. Integrations can extend this list without
	 * ListLab assuming that every protected meta key is unsafe to copy.
	 *
	 * @return string[]
	 */
	private function duplicate_runtime_meta_keys() {
		$keys = array(
			'_wc_gla_synced_at',
			'_wc_gla_google_ids',
			'_wc_gla_errors',
			'_wc_gla_failed_delete_attempts',
			'_wc_gla_failed_sync_attempts',
			'_wc_gla_sync_failed_at',
			'_wc_gla_sync_status',
			'_wc_gla_mc_status',
			'_wc_gla_sync_hash',
		);

		/**
		 * Filters integration runtime metadata removed from a ListLab duplicate.
		 *
		 * Keep catalog configuration such as `_wc_gla_visibility` and explicit
		 * Google product attributes out of this list unless an integration owns it
		 * as runtime state.
		 *
		 * @param string[] $keys Meta keys to remove from the duplicate.
		 */
		$keys = apply_filters( 'kniferevive_listlab_duplicate_runtime_meta_keys', $keys );

		return array_values( array_unique( array_filter( (array) $keys, 'is_string' ) ) );
	}
	public function media() { return $this->result( KREV_ListLab_Image_Handler::upload() ); }
	public function video() { return $this->result( KREV_ListLab_Video_Handler::upload() ); }
	public function schema( WP_REST_Request $r ) { $category = absint( $r->get_param( 'category_id' ) ); $groups = array(); if ( $category && class_exists( 'KREV_PA_Config' ) ) { $term = get_term( $category, 'product_cat' ); $slugs = array(); if ( $term && ! is_wp_error( $term ) ) { $slugs[] = $term->slug; foreach ( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) { $ancestor = get_term( $ancestor_id, 'product_cat' ); if ( $ancestor && ! is_wp_error( $ancestor ) ) { $slugs[] = $ancestor->slug; } } } $groups = KREV_PA_Config::groups_for_category_slugs( $slugs ); } return KREV_ListLab_Categories::schema( $category ) + array( 'categories' => KREV_ListLab_Product_Reader::categories(), 'attributes' => KREV_ListLab_Attributes::schema( $category ), 'is_knife' => in_array( 'knives', $groups, true ), 'is_tech' => in_array( 'tech', $groups, true ), 'is_world_spices' => in_array( 'world-spices', $groups, true ), 'shipping_policies' => KREV_ListLab_Policies::shipping_classes(), 'return_policies' => KREV_ListLab_Policies::return_policies( $category ) ); }
	private function owned( $id ) { if ( ! KREV_ListLab_Dokan_Adapter::owns_product( $id ) ) { return new WP_Error( 'listlab_owner', 'You do not own this listing.', array( 'status' => 403 ) ); } $p = wc_get_product( $id ); return $p ? $p : new WP_Error( 'listlab_missing', 'Listing not found.', array( 'status' => 404 ) ); }
	private function result( $value ) { return is_wp_error( $value ) ? $value : $value; }
}
