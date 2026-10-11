<?php
defined( 'ABSPATH' ) || exit;

final class KREV_ListLab_Product_Writer {
	public static function save( array $data, $product_id = 0 ) {
		$seller = KREV_ListLab_Dokan_Adapter::seller_id();
		if ( ! KREV_ListLab_Dokan_Adapter::can_sell() ) return new WP_Error( 'listlab_seller', 'Your seller account is not enabled.', array( 'status' => 403 ) );
		if ( $product_id ) {
			if ( ! KREV_ListLab_Dokan_Adapter::owns_product( $product_id ) ) return new WP_Error( 'listlab_owner', 'You do not own this listing.', array( 'status' => 403 ) );
			$product = wc_get_product( $product_id );
			if ( ! $product || ! $product->is_type( 'simple' ) ) return new WP_Error( 'listlab_product', 'Only Simple products are supported by ListLab.', array( 'status' => 400 ) );
		} else {
			$existing = self::idempotent( $data['client_uuid'] ?? '', $seller );
			if ( $existing ) return KREV_ListLab_Product_Reader::dto( wc_get_product( $existing ) );
			$product = new WC_Product_Simple();
		}

		$clean = self::clean( $data );
		$errors = self::validate( $clean, ! empty( $data['publish'] ) );
		if ( $errors ) return new WP_Error( 'listlab_validation', 'Please correct the highlighted fields.', array( 'status' => 422, 'errors' => $errors ) );
		$gtin = self::normalize_gtin( $clean['global_unique_id'] );
		if ( is_wp_error( $gtin ) ) return $gtin;
		if ( $clean['global_unique_id_provided'] && '' !== $gtin && function_exists( 'wc_get_product_id_by_global_unique_id' ) ) {
			$matching_id = (int) wc_get_product_id_by_global_unique_id( $gtin );
			if ( $matching_id && $matching_id !== (int) $product_id ) return self::duplicate_gtin_error();
		}

		$product->set_name( $clean['title'] );
		$product->set_description( $clean['description'] );
		$product->set_short_description( $clean['short_description'] );
		$product->set_regular_price( $clean['regular_price'] );
		$product->set_sale_price( $clean['sale_price'] );
		if ( array_key_exists( 'timed_offer_enabled', $data ) ) {
			$timed_offer = KREV_ListLab_Timed_Offers::apply( $product, $data );
			if ( is_wp_error( $timed_offer ) ) return $timed_offer;
		}
		$product->set_sku( $clean['sku'] );
		$product->set_category_ids( $clean['category_ids'] );
		$product->set_image_id( $clean['featured_image_id'] );
		$product->set_gallery_image_ids( $clean['gallery_ids'] );
		if ( $clean['video_id_provided'] ) {
			if ( $clean['video_id'] ) $product->update_meta_data( KREV_ListLab_Video_Handler::META_KEY, $clean['video_id'] );
			else $product->delete_meta_data( KREV_ListLab_Video_Handler::META_KEY );
		}
		if ( $clean['video_url_provided'] ) {
			if ( $clean['video_url'] ) $product->update_meta_data( KREV_ListLab_Video_Handler::URL_META_KEY, $clean['video_url'] );
			else $product->delete_meta_data( KREV_ListLab_Video_Handler::URL_META_KEY );
		}
		if ( $clean['video_id_provided'] && $clean['video_id'] ) $product->delete_meta_data( KREV_ListLab_Video_Handler::URL_META_KEY );
		if ( $clean['video_url_provided'] && $clean['video_url'] ) $product->delete_meta_data( KREV_ListLab_Video_Handler::META_KEY );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( $clean['quantity'] );
		$product->set_stock_status( $clean['quantity'] > 0 ? 'instock' : 'outofstock' );
		$product->set_shipping_class_id( $clean['shipping_policy_id'] );
		$product->set_weight( $clean['weight'] );
		$product->set_length( $clean['length'] );
		$product->set_width( $clean['width'] );
		$product->set_height( $clean['height'] );
		$product->set_upsell_ids( $clean['upsell_ids'] );
		$product->set_cross_sell_ids( $clean['cross_sell_ids'] );
		if ( $clean['global_unique_id_provided'] ) {
			try { $product->set_global_unique_id( $gtin ); } catch ( Exception $exception ) { return self::duplicate_gtin_error(); }
		}
		$product->update_meta_data( '_kniferevive_listing_subtitle', $clean['subtitle'] );
		$product->update_meta_data( '_krev_listlab_archived', 'no' );
		if ( array_key_exists( 'whatnot_demo', $data ) ) {
			$demo_save = KREV_ListLab_Whatnot_Demo::apply( $product, $data['whatnot_demo'] );
			if ( is_wp_error( $demo_save ) ) return $demo_save;
		}
		if ( ! $product_id ) $product->update_meta_data( '_krev_listlab_client_uuid', $clean['client_uuid'] );
		$complete = ! self::required_errors( $clean );
		$product->set_status( $complete && ! empty( $data['publish'] ) ? KREV_ListLab_Dokan_Adapter::publish_status() : 'draft' );
		// Publishing must also clear inherited WooCommerce catalog exclusions.
		if ( $complete && ! empty( $data['publish'] ) ) $product->set_catalog_visibility( 'visible' );
		try { $id = $product->save(); } catch ( Exception $exception ) { return self::duplicate_gtin_error(); }
		if ( ! $product_id ) {
			wp_update_post( array( 'ID' => $id, 'post_author' => $seller ) );
			if ( $seller !== (int) get_post_field( 'post_author', $id ) ) { wp_delete_post( $id, true ); return new WP_Error( 'listlab_author', 'Listing ownership could not be verified.', array( 'status' => 500 ) ); }
		}

		$product = wc_get_product( $id );
		$attribute_save = KREV_ListLab_Attributes::save( $product, $clean['effective_category_id'], $clean['attributes'] );
		if ( is_wp_error( $attribute_save ) ) return $attribute_save;
		if ( taxonomy_exists( 'kr_return_policy' ) ) wp_set_object_terms( $id, $clean['return_policy_id'] ? array( $clean['return_policy_id'] ) : array(), 'kr_return_policy', false );
		if ( class_exists( 'KREV_PA_Condition_Resolver' ) ) {
			if ( $clean['sharpened_provided'] && KREV_PA_Condition_Resolver::is_knife( $id ) ) {
				$changed = KREV_PA_Condition_Resolver::set_sharpened( $product, $clean['sharpened'] );
				if ( is_wp_error( $changed ) ) return $changed;
			}
			if ( $clean['refurbishment_evidence_provided'] ) {
				$evidence = KREV_PA_Condition_Resolver::save_evidence( $product, $clean['refurbishment_evidence'] );
				if ( is_wp_error( $evidence ) ) return $evidence;
			}
			KREV_PA_Condition_Resolver::persist_resolution( $product );
		}
		$product->save();
		wc_delete_product_transients( $id );
		return KREV_ListLab_Product_Reader::dto( wc_get_product( $id ) );
	}

	private static function clean( $d ) {
		$category = absint( $d['category_id'] ?? 0 );
		$selection = KREV_ListLab_Categories::selection( array( $category ) );
		$child = array_key_exists( 'subcategory_id', $d ) ? absint( is_scalar( $d['subcategory_id'] ) ? $d['subcategory_id'] : 0 ) : $selection['subcategory_id'];
		$parent = $selection['category_id'];
		$gallery = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $d['gallery_ids'] ?? array() ) ) ) ) );
		$evidence = is_array( $d['refurbishment_evidence'] ?? null ) ? $d['refurbishment_evidence'] : array();
		return array(
			'video_id_provided' => array_key_exists( 'video_id', $d ),
			'video_url_provided' => array_key_exists( 'video_url', $d ),
			'video_url' => KREV_ListLab_Video_Handler::normalize_url( $d['video_url'] ?? '' ),
			'video_id_valid' => ! array_key_exists( 'video_id', $d ) || ( ( is_int( $d['video_id'] ) || is_string( $d['video_id'] ) ) && preg_match( '/^\d+$/D', (string) $d['video_id'] ) ),
			'video_id' => is_scalar( $d['video_id'] ?? 0 ) ? absint( $d['video_id'] ?? 0 ) : 0,
			'title' => sanitize_text_field( $d['title'] ?? '' ), 'subtitle' => sanitize_text_field( $d['subtitle'] ?? '' ), 'description' => wp_kses_post( $d['description'] ?? '' ), 'short_description' => wp_kses_post( $d['short_description'] ?? '' ), 'category_id' => $parent, 'subcategory_id' => $child, 'subcategory_valid' => ! array_key_exists( 'subcategory_id', $d ) || ( ( is_int( $d['subcategory_id'] ) || is_string( $d['subcategory_id'] ) ) && preg_match( '/^\d+$/D', (string) $d['subcategory_id'] ) ), 'effective_category_id' => $child ?: $parent, 'category_ids' => array_values( array_filter( array_unique( array( $parent, $child ) ) ) ), 'featured_image_id' => absint( $d['featured_image_id'] ?? 0 ), 'gallery_ids' => $gallery, 'regular_price' => wc_format_decimal( $d['regular_price'] ?? '' ), 'sale_price' => wc_format_decimal( $d['sale_price'] ?? '' ), 'quantity' => wc_stock_amount( $d['quantity'] ?? 0 ), 'quantity_provided' => isset( $d['quantity'] ) && '' !== trim( (string) $d['quantity'] ), 'sku' => wc_clean( $d['sku'] ?? '' ), 'global_unique_id_provided' => array_key_exists( 'global_unique_id', $d ), 'global_unique_id' => (string) ( $d['global_unique_id'] ?? '' ), 'shipping_policy_id' => absint( $d['shipping_policy_id'] ?? 0 ), 'return_policy_id' => absint( $d['return_policy_id'] ?? 0 ), 'weight' => wc_format_decimal( $d['weight'] ?? '' ), 'length' => wc_format_decimal( $d['length'] ?? '' ), 'width' => wc_format_decimal( $d['width'] ?? '' ), 'height' => wc_format_decimal( $d['height'] ?? '' ), 'attributes' => is_array( $d['attributes'] ?? null ) ? $d['attributes'] : array(), 'upsell_ids' => array_map( 'absint', (array) ( $d['upsell_ids'] ?? array() ) ), 'cross_sell_ids' => array_map( 'absint', (array) ( $d['cross_sell_ids'] ?? array() ) ), 'client_uuid' => sanitize_text_field( $d['client_uuid'] ?? '' ), 'sharpened_provided' => array_key_exists( 'sharpened', $d ), 'sharpened' => ! empty( $d['sharpened'] ), 'refurbishment_evidence_provided' => array_key_exists( 'refurbishment_evidence', $d ), 'refurbishment_evidence' => array( 'restoration_confirmed' => ! empty( $evidence['restoration_confirmed'] ), 'like_new_confirmed' => ! empty( $evidence['like_new_confirmed'] ), 'warranty_terms' => sanitize_textarea_field( $evidence['warranty_terms'] ?? '' ) ),
		);
	}

	private static function validate( $d, $publishing ) {
		$e = $publishing ? self::required_errors( $d ) : array();
		$category_validation = KREV_ListLab_Categories::validate( $d['category_id'], $d['subcategory_id'] );
		if ( ! $d['subcategory_valid'] || is_wp_error( $category_validation ) ) $e['subcategory_id'] = 'Choose a valid Tech subcategory.';
		if ( false === $d['video_url'] ) $e['video_url'] = 'Enter a public HTTPS YouTube or Vimeo video link.';
		if ( $d['video_id'] && $d['video_url'] ) $e['video_url'] = 'Use one uploaded video or a video link.';
		if ( ! $d['video_id_valid'] || ( $d['video_id_provided'] && $d['video_id'] && ! KREV_ListLab_Video_Handler::authorized_video( $d['video_id'] ) ) ) $e['video_id'] = 'Choose a video uploaded by your account or from an authorized shared folder.';
		if ( $d['category_id'] && ! get_term( $d['category_id'], 'product_cat' ) ) $e['category_id'] = 'Choose a valid product category.';
		if ( $d['shipping_policy_id'] && ! get_term( $d['shipping_policy_id'], 'product_shipping_class' ) ) $e['shipping_policy_id'] = 'Choose a valid Shipping Policy.';
		if ( ! KREV_ListLab_Policies::valid_return_policy( $d['return_policy_id'], $d['effective_category_id'] ) ) $e['return_policy_id'] = 'That Return Policy is not allowed for this category.';
		foreach ( array_merge( array( $d['featured_image_id'] ), $d['gallery_ids'] ) as $id ) if ( $id && ! KREV_ListLab_Image_Handler::authorized_image( $id ) ) $e['images'] = 'An image is invalid or not authorized for your account.';
		if ( count( array_unique( array_filter( array_merge( array( $d['featured_image_id'] ), $d['gallery_ids'] ) ) ) ) > 10 ) $e['images'] = 'A maximum of 10 images is allowed.';
		if ( is_wp_error( self::normalize_gtin( $d['global_unique_id'] ) ) ) $e['global_unique_id'] = 'Enter a valid 8, 12, 13, or 14 digit GTIN, or leave this optional field blank.';
		if ( $publishing ) {
			$attribute_validation = KREV_ListLab_Attributes::validate( $d['effective_category_id'], $d['attributes'], true );
			if ( is_wp_error( $attribute_validation ) ) $e = array_merge( $e, (array) ( $attribute_validation->get_error_data()['errors'] ?? array() ) );
		}
		return $e;
	}

	private static function normalize_gtin( $value ) {
		$gtin = preg_replace( '/[\s-]+/', '', trim( (string) $value ) );
		if ( '' === $gtin ) return '';
		if ( ! preg_match( '/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/', $gtin ) || ! self::valid_gtin( $gtin ) ) return new WP_Error( 'listlab_invalid_gtin', 'Enter a valid 8, 12, 13, or 14 digit GTIN, or leave this optional field blank.', array( 'status' => 422 ) );
		return $gtin;
	}
	private static function valid_gtin( $gtin ) { $sum = 0; for ( $position = 0, $index = strlen( $gtin ) - 2; $index >= 0; $index--, $position++ ) $sum += (int) $gtin[ $index ] * ( 0 === $position % 2 ? 3 : 1 ); return ( 10 - ( $sum % 10 ) ) % 10 === (int) substr( $gtin, -1 ); }
	private static function duplicate_gtin_error() { return new WP_Error( 'listlab_duplicate_gtin', 'This barcode is already being used by another KnifeRevive listing. Check that you scanned or entered the correct product barcode.', array( 'status' => 422, 'errors' => array( 'global_unique_id' => 'This barcode is already being used by another KnifeRevive listing. Check that you scanned or entered the correct product barcode.' ) ) ); }
	private static function required_errors( $d ) { $e = array(); foreach ( array( 'title' => 'Listing title is required.', 'description' => 'Description is required.', 'category_id' => 'Product Category is required.', 'featured_image_id' => 'Featured Image is required.', 'shipping_policy_id' => 'Shipping Policy is required.' ) as $k => $m ) if ( empty( $d[ $k ] ) ) $e[ $k ] = $m; if ( empty( $d['quantity_provided'] ) || $d['quantity'] < 0 ) $e['quantity'] = 'Quantity is required.'; return $e; }
	private static function idempotent( $uuid, $seller ) { if ( '' === $uuid ) return 0; $q = new WP_Query( array( 'post_type' => 'product', 'author' => $seller, 'meta_key' => '_krev_listlab_client_uuid', 'meta_value' => $uuid, 'fields' => 'ids', 'posts_per_page' => 1 ) ); return $q->posts ? (int) $q->posts[0] : 0; }
}
