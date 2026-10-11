<?php
defined( 'ABSPATH' ) || exit;

/** Google Electronics taxonomy snapshot, stored as native children of Tech. */
final class KREV_PA_Tech_Categories {
	const PAGE = 'krev-tech-categories';
	const ID_META = '_krev_google_taxonomy_id';
	const PATH_META = '_krev_google_taxonomy_path';
	const PROGRESS = 'krev_tech_taxonomy_20261010';

	public static function init() {
		add_action( 'admin_menu', static function() { add_submenu_page( 'woocommerce', 'Tech Categories', 'Tech Categories', 'manage_woocommerce', self::PAGE, array( __CLASS__, 'page' ) ); } );
		add_action( 'admin_post_krev_tech_categories', array( __CLASS__, 'handle' ) );
	}

	public static function catalog() {
		static $rows;
		if ( null === $rows ) $rows = json_decode( file_get_contents( KREV_PA_DIR . 'data/google-electronics-2026-10-10.json' ), true );
		return is_array( $rows ) ? $rows : array();
	}

	public static function root_id() {
		$term = get_term_by( 'slug', 'technology', 'product_cat' );
		return $term && ! is_wp_error( $term ) ? (int) $term->term_id : 0;
	}

	public static function is_child( $id ) {
		$root = self::root_id(); $term = get_term( (int) $id, 'product_cat' );
		return $root && $term && ! is_wp_error( $term ) && (int) $term->term_id !== $root && in_array( $root, array_map( 'intval', get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) ), true );
	}

	public static function mapping( $id ) {
		if ( ! self::is_child( $id ) ) return null;
		$google_id = (string) get_term_meta( $id, self::ID_META, true );
		foreach ( self::catalog() as $row ) if ( (string) $row['id'] === $google_id ) return array( 'id' => $google_id, 'path' => $row['path'] );
		return null;
	}

	/** No existing products are recategorized; each retry reuses stable slugs. */
	public static function import_batch( $limit = 50 ) {
		$root = self::root_id();
		if ( ! $root ) return new WP_Error( 'krev_tech_root', 'The existing Tech category (technology) is missing.' );
		$rows = self::catalog();
		if ( ! $rows ) return new WP_Error( 'krev_tech_catalog', 'The bundled Google Electronics taxonomy could not be read.' );
		$offset = min( count( $rows ), absint( get_option( self::PROGRESS, 0 ) ) );
		foreach ( array_slice( $rows, $offset, min( 50, max( 1, (int) $limit ) ) ) as $row ) {
			$slug = 'tech-google-' . $row['id'];
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $term && (int) $term->parent !== $root ) return new WP_Error( 'krev_tech_conflict', 'A reserved Google Tech slug already belongs to another category. No existing category was moved.' );
			if ( ! $term ) {
				$created = wp_insert_term( $row['name'], 'product_cat', array( 'slug' => $slug, 'parent' => $root ) );
				if ( is_wp_error( $created ) ) return $created;
				$term = get_term( $created['term_id'], 'product_cat' );
			}
			$id_result = update_term_meta( $term->term_id, self::ID_META, (string) $row['id'] );
			$path_result = update_term_meta( $term->term_id, self::PATH_META, $row['path'] );
			if ( is_wp_error( $id_result ) || is_wp_error( $path_result ) ) return new WP_Error( 'krev_tech_meta', 'The Google mapping could not be saved. Retry this batch.' );
			update_option( self::PROGRESS, ++$offset, false );
		}
		return array( 'completed' => $offset, 'total' => count( $rows ) );
	}

	public static function handle() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) wp_die( 'You cannot manage Tech categories.' );
		check_admin_referer( 'krev_tech_categories' );
		$result = self::import_batch();
		update_user_meta( get_current_user_id(), '_krev_tech_import_error', is_wp_error( $result ) ? $result->get_error_message() : '' );
		wp_safe_redirect( add_query_arg( 'page', self::PAGE, admin_url( 'admin.php' ) ) ); exit;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) return;
		$total = count( self::catalog() ); $done = absint( get_option( self::PROGRESS, 0 ) );
		echo '<div class="wrap"><h1>Tech Categories</h1><p>Google Electronics product taxonomy, retrieved October 10, 2026. Each category becomes a native subcategory of Tech with its matching Google ID. Existing products and custom categories are preserved.</p>';
		echo '<p><a href="https://www.google.com/basepages/producttype/taxonomy-with-ids.en-US.txt" target="_blank" rel="noopener">Official Google product taxonomy</a></p>';
		echo '<p><strong>' . esc_html( "$done of $total Tech subcategories imported" ) . '</strong></p>';
		$error = get_user_meta( get_current_user_id(), '_krev_tech_import_error', true );
		if ( $error ) echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
		if ( $done < $total ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="krev_tech_categories">';
			wp_nonce_field( 'krev_tech_categories' ); submit_button( 'Import next 50 Tech subcategories' ); echo '</form>';
		} else echo '<p>Import complete. ListLab’s Tech subcategory dropdown and automatic Google feed mapping are ready.</p>';
		echo '</div>';
	}
}
