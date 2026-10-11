<?php
defined( 'ABSPATH' ) || exit;

final class KREV_ListLab_Image_Handler {
	public static function authorized_image( $id ) {
		$post = get_post( $id ); if ( ! $post || 'attachment' !== $post->post_type || 0 !== strpos( (string) $post->post_mime_type, 'image/' ) ) { return false; }
		return current_user_can( 'manage_woocommerce' ) || (int) $post->post_author === get_current_user_id();
	}

	public static function upload( $field = 'file' ) {
		if ( ! KREV_ListLab_Dokan_Adapter::can_sell() || empty( $_FILES[ $field ] ) ) { return new WP_Error( 'listlab_upload', 'A valid image upload is required.', array( 'status' => 400 ) ); }
		$file = $_FILES[ $field ]; if ( UPLOAD_ERR_OK !== (int) $file['error'] || (int) $file['size'] > wp_max_upload_size() ) { return new WP_Error( 'listlab_upload', 'Image upload failed or exceeds the site limit.', array( 'status' => 400 ) ); }
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) ); if ( empty( $check['type'] ) || ! wp_getimagesize( $file['tmp_name'] ) ) { return new WP_Error( 'listlab_image', 'Use a readable JPEG, PNG, or WebP image.', array( 'status' => 400 ) ); }
		require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php'; $id = media_handle_upload( $field, 0, array(), array( 'test_form' => false ) ); if ( is_wp_error( $id ) ) { return $id; }
		return KREV_ListLab_Product_Reader::attachment( $id );
	}
}
