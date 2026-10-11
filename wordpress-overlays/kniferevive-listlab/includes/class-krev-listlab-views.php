<?php
defined( 'ABSPATH' ) || exit;

/**
 * Optional, best-effort product-view analytics.
 *
 * This class must never make the seller listing experience depend on its
 * custom table. A missing table, restricted database account, or failed
 * migration simply results in zero views being returned.
 */
final class KREV_ListLab_Views {
	const VERSION = '1';
	const OPTION  = 'krev_listlab_views_schema_version';

	public static function maybe_upgrade() {
		try {
			if ( self::VERSION === get_option( self::OPTION ) ) {
				return;
			}

			global $wpdb;
			if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
				return;
			}

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$table           = self::table();
			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE {$table} (
				product_id bigint(20) unsigned NOT NULL,
				view_date date NOT NULL,
				views bigint(20) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (product_id, view_date),
				KEY view_date (view_date)
			) {$charset_collate};";

			dbDelta( $sql );
			if ( self::table_exists() ) {
				update_option( self::OPTION, self::VERSION, false );
			}
		} catch ( Throwable $error ) {
			// Analytics is optional. Do not interrupt activation, admin, or storefront requests.
			return;
		}
	}

	public static function track_current_product() {
		try {
			if ( is_admin() || wp_doing_ajax() || ! function_exists( 'is_product' ) || ! is_product() || ! self::table_exists() ) {
				return;
			}

			$product_id = absint( get_queried_object_id() );
			if ( ! $product_id ) {
				return;
			}

			global $wpdb;
			$wpdb->query(
				$wpdb->prepare(
					'INSERT INTO ' . self::table() . ' (product_id, view_date, views) VALUES (%d, %s, 1) ON DUPLICATE KEY UPDATE views = views + 1',
					$product_id,
					wp_date( 'Y-m-d' )
				)
			);
		} catch ( Throwable $error ) {
			return;
		}
	}

	/**
	 * @param int[] $product_ids Product IDs.
	 * @return array<int,int> Product ID => rolling 30-day view count.
	 */
	public static function totals( array $product_ids ) {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );
		if ( ! $ids ) {
			return array();
		}

		try {
			if ( ! self::table_exists() ) {
				return array();
			}

			global $wpdb;
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			$query        = 'SELECT product_id, SUM(views) AS total FROM ' . self::table() . " WHERE view_date >= %s AND product_id IN ({$placeholders}) GROUP BY product_id";
			$args         = array_merge( array( wp_date( 'Y-m-d', strtotime( '-29 days' ) ) ), $ids );
			$sql          = $wpdb->prepare( $query, $args );
			$rows         = is_string( $sql ) ? $wpdb->get_results( $sql ) : array();
			$totals       = array();

			foreach ( (array) $rows as $row ) {
				$totals[ absint( $row->product_id ) ] = absint( $row->total );
			}
			return $totals;
		} catch ( Throwable $error ) {
			return array();
		}
	}

	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'krev_listlab_product_views';
	}

	private static function table_exists() {
		try {
			global $wpdb;
			if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
				return false;
			}

			$table    = self::table();
			$previous = $wpdb->suppress_errors( true );
			$found    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			$wpdb->suppress_errors( $previous );

			return $table === $found;
		} catch ( Throwable $error ) {
			if ( isset( $previous ) && isset( $wpdb ) && is_object( $wpdb ) ) {
				$wpdb->suppress_errors( $previous );
			}
			return false;
		}
	}
}
