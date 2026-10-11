<?php
defined( 'ABSPATH' ) || exit;

final class KREV_ListLab_Video_Handler {
	const META_KEY = '_krev_listlab_video_id';
	const URL_META_KEY = '_krev_listlab_video_url';
	public static function normalize_url( $value ) {
		if ( ! is_string( $value ) ) return false;
		$url = trim( $value ); if ( '' === $url ) return '';
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || 'https' !== strtolower( $parts['scheme'] ?? '' ) || isset( $parts['user'], $parts['pass'] ) || isset( $parts['user'] ) || isset( $parts['port'] ) ) return false;
		$host = strtolower( $parts['host'] ?? '' ); $path = $parts['path'] ?? ''; $id = '';
		if ( 'youtu.be' === $host && preg_match( '~^/([A-Za-z0-9_-]{11})/?$~D', $path, $match ) ) $id = $match[1];
		if ( in_array( $host, array( 'youtube.com', 'www.youtube.com', 'm.youtube.com' ), true ) ) {
			if ( '/watch' === $path ) { parse_str( $parts['query'] ?? '', $query ); $id = is_string( $query['v'] ?? null ) ? $query['v'] : ''; }
			elseif ( preg_match( '~^/(?:shorts|embed)/([A-Za-z0-9_-]{11})/?$~D', $path, $match ) ) $id = $match[1];
		}
		if ( preg_match( '/^[A-Za-z0-9_-]{11}$/D', $id ) ) return 'https://www.youtube.com/watch?v=' . $id;
		if ( in_array( $host, array( 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com' ), true ) && preg_match( '~^/(?:video/)?([0-9]+)/?$~D', $path, $match ) ) return 'https://vimeo.com/' . $match[1];
		return false;
	}
	public static function embed_url( $url ) {
		$url = self::normalize_url( $url ); if ( ! $url ) return '';
		if ( str_starts_with( $url, 'https://vimeo.com/' ) ) return 'https://player.vimeo.com/video/' . substr( $url, strlen( 'https://vimeo.com/' ) );
		return 'https://www.youtube-nocookie.com/embed/' . substr( $url, strlen( 'https://www.youtube.com/watch?v=' ) );
	}
	public static function mimes() { return array( 'mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime' ); }
	public static function limit() { return min( 100 * MB_IN_BYTES, wp_max_upload_size() ); }
	public static function hosting_available() {
		if ( function_exists( 'wpcom_site_can_upload_videos' ) && ! wpcom_site_can_upload_videos() ) return false;
		return (bool) array_intersect( self::mimes(), get_allowed_mime_types() );
	}
	public static function authorized_video( $id ) {
		$post = get_post( $id );
		if ( ! $post || 'attachment' !== $post->post_type || ! in_array( $post->post_mime_type, self::mimes(), true ) ) return false;
		$allowed = current_user_can( 'manage_woocommerce' ) || (int) $post->post_author === get_current_user_id();
		return (bool) apply_filters( 'kniferevive_listlab_video_authorized', $allowed, (int) $id, get_current_user_id() );
	}
	public static function attachment( $id ) {
		$id = absint( $id ); $post = $id ? get_post( $id ) : null;
		if ( ! $post || 'attachment' !== $post->post_type || ! in_array( $post->post_mime_type, self::mimes(), true ) ) return null;
		$url = wp_get_attachment_url( $id ); if ( ! $url ) return null;
		$meta = wp_get_attachment_metadata( $id );
		return array( 'id' => $id, 'url' => $url, 'title' => get_the_title( $id ), 'mime' => $post->post_mime_type, 'width' => absint( $meta['width'] ?? 0 ), 'height' => absint( $meta['height'] ?? 0 ), 'duration' => (float) ( $meta['length'] ?? 0 ) );
	}
	public static function validate_file( array $file ) {
		if ( ! isset( $file['error'], $file['size'], $file['tmp_name'], $file['name'] ) || ! is_scalar( $file['name'] ) || ! is_scalar( $file['tmp_name'] ) || UPLOAD_ERR_OK !== (int) $file['error'] || (int) $file['size'] <= 0 || (int) $file['size'] > self::limit() ) return new WP_Error( 'listlab_video_upload', 'Video upload failed or exceeds the ' . size_format( self::limit() ) . ' limit.', array( 'status' => 400 ) );
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], self::mimes() );
		if ( empty( $check['type'] ) || ! in_array( $check['type'], self::mimes(), true ) ) return new WP_Error( 'listlab_video_type', 'Use an MP4, WebM, or MOV video.', array( 'status' => 400 ) );
		$meta = wp_read_video_metadata( $file['tmp_name'] );
		if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) return new WP_Error( 'listlab_video_invalid', 'This file does not contain a readable video. Try exporting it as an MP4 video.', array( 'status' => 400 ) );
		return true;
	}
	public static function upload( $field = 'file' ) {
		if ( ! KREV_ListLab_Dokan_Adapter::can_sell() || empty( $_FILES[ $field ] ) || ! is_array( $_FILES[ $field ] ) ) return new WP_Error( 'listlab_video_upload', 'A valid video upload is required.', array( 'status' => 400 ) );
		if ( ! self::hosting_available() ) return new WP_Error( 'listlab_video_hosting', 'Video uploads are not enabled by this site\'s WordPress.com hosting plan. KnifeRevive must enable video hosting before sellers can upload videos.', array( 'status' => 400 ) );
		require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
		$valid = self::validate_file( $_FILES[ $field ] ); if ( is_wp_error( $valid ) ) return $valid;
		$id = media_handle_upload( $field, 0, array(), array( 'test_form' => false, 'mimes' => self::mimes() ) );
		return is_wp_error( $id ) ? $id : self::attachment( $id );
	}
	public static function assets() {
		if ( is_product() ) wp_enqueue_style( 'krev-listing-video', KREV_LISTLAB_URL . 'assets/css/listing-video.css', array(), KREV_LISTLAB_VERSION );
	}
	private static $classic_video = '';
	public static function gallery_block( $content, $parsed_block, $block ) {
		if ( ! is_product() || empty( $content ) ) return $content;
		$id = (int) ( $block->context['postId'] ?? get_queried_object_id() );
		if ( $id !== (int) get_queried_object_id() ) return $content;
		$video = self::player( wc_get_product( $id ) );
		return $video ? '<div class="krev-product-media">' . $content . $video . '</div>' : $content;
	}
	public static function classic_gallery_start() {
		global $product;
		// Block templates dispatch legacy hooks separately from the gallery block.
		if ( wp_is_block_theme() ) return;
		self::$classic_video = is_product() ? self::player( $product ) : '';
		if ( self::$classic_video ) echo '<div class="krev-product-media krev-product-media--classic">';
	}
	public static function classic_gallery_end() {
		if ( ! self::$classic_video ) return;
		echo self::$classic_video . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by player().
		self::$classic_video = '';
	}
	public static function player( $product ) {
		if ( ! $product instanceof WC_Product ) return '';
		$video = self::attachment( $product->get_meta( self::META_KEY, true, 'edit' ) );
		$url = self::normalize_url( $product->get_meta( self::URL_META_KEY, true, 'edit' ) ); $embed = self::embed_url( $url );
		if ( ! $video && ! $embed ) return '';
		$html = '<section class="krev-listing-video" aria-label="Listing video"><h2>Listing video</h2>';
		if ( $video ) $html .= '<video controls playsinline preload="metadata" aria-label="Play listing video" src="' . esc_url( $video['url'] ) . '"></video>';
		else $html .= '<iframe src="' . esc_url( $embed ) . '" title="Listing video" loading="lazy" allow="fullscreen; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>';
		return $html . '<p><a href="' . esc_url( $video ? $video['url'] : $url ) . '" target="_blank" rel="noopener">Open video</a></p></section>';
	}
}
