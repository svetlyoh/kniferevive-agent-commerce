<?php
defined( 'ABSPATH' ) || exit;

final class KREV_ListLab_Dokan_Adapter {
	public static function seller_id() {
		return get_current_user_id();
	}

	public static function can_sell() {
		$user_id = self::seller_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ) ) {
			return true;
		}
		if ( function_exists( 'dokan_is_user_seller' ) && ! dokan_is_user_seller( $user_id ) ) {
			return false;
		}
		return ! function_exists( 'dokan_is_seller_enabled' ) || dokan_is_seller_enabled( $user_id );
	}

	public static function owns_product( $product_id ) {
		if ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ) ) {
			return true;
		}
		return self::seller_id() > 0 && self::seller_id() === (int) get_post_field( 'post_author', $product_id );
	}

	public static function publish_status() {
		if ( function_exists( 'dokan_get_default_product_status' ) ) {
			$status = sanitize_key( dokan_get_default_product_status( self::seller_id() ) );
			return in_array( $status, array( 'publish', 'pending', 'draft' ), true ) ? $status : 'pending';
		}
		return current_user_can( 'publish_products' ) ? 'publish' : 'pending';
	}
}
