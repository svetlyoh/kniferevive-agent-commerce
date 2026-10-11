<?php
defined( 'ABSPATH' ) || exit;

final class KREV_ListLab_Product_Reader {
	public static function attachment( $id ) {
		$id = absint( $id ); if ( ! $id ) { return null; }
		return array( 'id' => $id, 'url' => wp_get_attachment_image_url( $id, 'medium' ) ?: wp_get_attachment_url( $id ), 'thumbnail_url' => wp_get_attachment_image_url( $id, 'thumbnail' ) ?: wp_get_attachment_url( $id ), 'alt' => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
	}

	public static function dto( WC_Product $product, $views_30d = 0 ) {
		$id = $product->get_id(); $gallery = array_values( array_filter( array_map( array( __CLASS__, 'attachment' ), $product->get_gallery_image_ids() ) ) );
		$categories = wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'ids' ) ); $return = wp_get_post_terms( $id, 'kr_return_policy', array( 'fields' => 'ids' ) ); $attributes = array();
		foreach ( $product->get_attributes() as $attribute ) { if ( $attribute->is_taxonomy() ) { $attributes[ $attribute->get_name() ] = wp_get_post_terms( $id, $attribute->get_name(), array( 'fields' => 'ids' ) ); } }
		$brand_terms = taxonomy_exists( 'product_brand' ) ? wp_get_post_terms( $id, 'product_brand' ) : array();
		$brand = ! is_wp_error( $brand_terms ) && $brand_terms ? reset( $brand_terms ) : null;
		if ( $brand ) { $attributes['product_brand'] = array( (int) $brand->term_id ); }
		$selection = KREV_ListLab_Categories::selection( is_wp_error( $categories ) ? array() : $categories );
		$created = $product->get_date_created(); $modified = $product->get_date_modified();
		$condition = class_exists( 'KREV_PA_Condition_Resolver' ) ? KREV_PA_Condition_Resolver::resolve( $product ) : null;
		$timed_offer = class_exists( 'KREV_ListLab_Timed_Offers' ) ? KREV_ListLab_Timed_Offers::state( $product ) : array( 'enabled' => false, 'state' => 'none' );
		$whatnot_demo = KREV_ListLab_Whatnot_Demo::state( $product );
		return array(
			'video' => KREV_ListLab_Video_Handler::attachment( $product->get_meta( KREV_ListLab_Video_Handler::META_KEY, true, 'edit' ) ),
			'video_url' => KREV_ListLab_Video_Handler::normalize_url( $product->get_meta( KREV_ListLab_Video_Handler::URL_META_KEY, true, 'edit' ) ) ?: '',
			'id' => $id, 'status' => $product->get_status(), 'archived' => 'yes' === $product->get_meta( '_krev_listlab_archived', true, 'edit' ), 'hidden_from_status' => sanitize_key( $product->get_meta( '_krev_listlab_hidden_from_status', true, 'edit' ) ), 'type' => $product->get_type(), 'title' => $product->get_name(), 'subtitle' => (string) $product->get_meta( '_kniferevive_listing_subtitle', true, 'edit' ), 'is_knife' => self::is_knife( $id ), 'sharpened' => $condition ? (bool) $condition['sharpened'] : has_term( 'sharpened', 'product_tag', $id ), 'condition_resolution' => $condition, 'refurbishment_evidence' => $condition ? $condition['evidence'] : array(), 'slug' => $product->get_slug(), 'description' => $product->get_description(), 'short_description' => $product->get_short_description(), 'global_unique_id' => $product->get_global_unique_id( 'edit' ), 'regular_price' => $product->get_regular_price(), 'sale_price' => $product->get_sale_price(), 'is_on_sale' => $product->is_on_sale(), 'price_html' => wp_kses_post( $product->get_price_html() ), 'timed_offer' => $timed_offer, 'whatnot_demo' => $whatnot_demo, 'quantity' => $product->get_stock_quantity(), 'sku' => $product->get_sku(), 'manage_stock' => $product->get_manage_stock(), 'stock_status' => $product->get_stock_status(), 'category_id' => $selection['category_id'], 'subcategory_id' => $selection['subcategory_id'], 'categories' => is_wp_error( $categories ) ? array() : array_map( 'intval', $categories ), 'attributes' => $attributes, 'brand' => $brand ? array( 'term_id' => (int) $brand->term_id, 'name' => $brand->name ) : null, 'featured_image' => self::attachment( $product->get_image_id() ), 'gallery' => $gallery, 'shipping_policy' => (int) $product->get_shipping_class_id(), 'return_policy' => is_wp_error( $return ) || ! $return ? 0 : (int) reset( $return ), 'weight' => $product->get_weight(), 'dimensions' => array( 'length' => $product->get_length(), 'width' => $product->get_width(), 'height' => $product->get_height() ), 'upsell_ids' => array_map( 'intval', $product->get_upsell_ids() ), 'cross_sell_ids' => array_map( 'intval', $product->get_cross_sell_ids() ), 'view_url' => 'publish' === $product->get_status() && 'yes' !== $product->get_meta( '_krev_listlab_archived', true, 'edit' ) ? get_permalink( $id ) : '', 'edit_url' => add_query_arg( array( 'action' => 'edit', 'product_id' => $id ), wc_get_account_endpoint_url( 'listlab' ) ), 'created_date' => $created ? wp_date( 'c', $created->getTimestamp() ) : '', 'modified_date' => $modified ? wp_date( 'c', $modified->getTimestamp() ) : '', 'views_30d' => absint( $views_30d ),
		);
	}

	private static function is_knife( $product_id ) {
		try {
			if ( function_exists( 'kniferevive_is_knife_product' ) ) { return kniferevive_is_knife_product( $product_id ); }
			$terms = wp_get_post_terms( $product_id, 'product_cat' ); if ( is_wp_error( $terms ) ) { return false; }
			foreach ( $terms as $term ) { if ( self::is_knife_term( $term ) ) { return true; } foreach ( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) { $ancestor = get_term( $ancestor_id, 'product_cat' ); if ( $ancestor && ! is_wp_error( $ancestor ) && self::is_knife_term( $ancestor ) ) { return true; } } }
			return false;
		} catch ( Throwable $error ) {
			return false;
		}
	}

	private static function is_knife_term( $term ) {
		if ( class_exists( 'KREV_Product_Mapper' ) && method_exists( 'KREV_Product_Mapper', 'is_knife_category_term' ) && KREV_Product_Mapper::is_knife_category_term( $term ) ) { return true; }
		return isset( $term->slug ) && in_array( $term->slug, array( 'boning-knife', 'bread-knife', 'butcher-knife', 'chefs-knife', 'cleaver', 'fillet-knife', 'nakiri-knife', 'oyster-knife', 'paring-knife', 'santoku-knife', 'serated-utility-knife', 'carving-knife', 'steak-knife', 'tomato-knife', 'utility-knife' ), true );
	}

	public static function categories() { $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ); return is_wp_error( $terms ) ? array() : array_map( static function( $term ) { return array( 'id' => (int) $term->term_id, 'parent' => (int) $term->parent, 'name' => html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' ), 'is_tech_child' => KREV_ListLab_Categories::is_child( $term->term_id ), 'google_category' => class_exists( 'KREV_PA_Tech_Categories' ) ? KREV_PA_Tech_Categories::mapping( $term->term_id ) : null ); }, $terms ); }
}
