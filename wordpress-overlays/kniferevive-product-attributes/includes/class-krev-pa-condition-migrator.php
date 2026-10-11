<?php

defined( 'ABSPATH' ) || exit;

/** Bounded, resumable condition inventory/backfill with conflict-aware rollback. */
final class KREV_PA_Condition_Migrator {
	const OPTION_PROGRESS = 'krev_condition_migration_progress';
	const OPTION_RUNS     = 'krev_condition_migration_runs';
	const BATCH_SIZE      = 25;

	public static function run_batch( $mode = 'preview', $run_id = '', $offset = 0 ) {
		$mode   = in_array( $mode, array( 'preview', 'apply' ), true ) ? $mode : 'preview';
		$run_id = $run_id ? sanitize_key( $run_id ) : gmdate( 'YmdHis' ) . '-' . wp_generate_password( 6, false, false );
		$query  = new WP_Query( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'posts_per_page' => self::BATCH_SIZE, 'offset' => absint( $offset ), 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => false ) );
		$rows = array(); $changed = 0; $errors = array(); $snapshots = array();
		$runs = (array) get_option( self::OPTION_RUNS, array() );
		if ( isset( $runs[ $run_id ]['snapshots'] ) ) { $snapshots = (array) $runs[ $run_id ]['snapshots']; }

		foreach ( $query->posts as $product_id ) {
			try {
				$product = wc_get_product( $product_id );
				if ( ! $product ) { continue; }
				$before = self::snapshot( $product );
				$resolved = KREV_PA_Condition_Resolver::resolve( $product );
				$row = self::row( $product, $resolved, $before );
				if ( 'apply' === $mode && $resolved['is_knife'] ) {
					$state = KREV_PA_Condition_Resolver::sharpened_state( $product_id );
					if ( ! $state['conflict'] && ! metadata_exists( 'post', $product_id, KREV_PA_Condition_Resolver::META_SHARPENED ) ) {
						$result = KREV_PA_Condition_Resolver::set_sharpened( $product, $state['value'] );
						if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
					}
					KREV_PA_Condition_Resolver::persist_resolution( $product );
					$product->save();
					$after = self::snapshot( wc_get_product( $product_id ) );
					if ( $before !== $after ) {
						$snapshots[ $product_id ] = array( 'before' => $before, 'after' => $after, 'saved_at' => gmdate( 'c' ) );
						$changed++;
						if ( class_exists( 'KREV_Sync_Queue' ) ) { KREV_Sync_Queue::enqueue( $product_id ); }
					}
				}
				$rows[] = $row;
			} catch ( Throwable $error ) { $errors[ $product_id ] = $error->getMessage(); }
		}

		$next = absint( $offset ) + count( $query->posts );
		$done = $next >= (int) $query->found_posts;
		$report = array( 'run_id' => $run_id, 'mode' => $mode, 'offset' => absint( $offset ), 'next_offset' => $next, 'total' => (int) $query->found_posts, 'done' => $done, 'changed' => $changed, 'rows' => $rows, 'errors' => $errors, 'updated_at' => gmdate( 'c' ) );
		$runs[ $run_id ] = array( 'mode' => $mode, 'snapshots' => $snapshots, 'updated_at' => gmdate( 'c' ) );
		$runs = array_slice( $runs, -5, null, true );
		update_option( self::OPTION_RUNS, $runs, false );
		update_option( self::OPTION_PROGRESS, $report, false );
		return $report;
	}

	public static function rollback_batch( $run_id, $offset = 0 ) {
		$runs = (array) get_option( self::OPTION_RUNS, array() );
		$snapshots = array_slice( (array) ( $runs[ $run_id ]['snapshots'] ?? array() ), absint( $offset ), self::BATCH_SIZE, true );
		$restored = 0; $conflicts = array();
		foreach ( $snapshots as $product_id => $snapshot ) {
			$product = wc_get_product( $product_id );
			if ( ! $product || self::snapshot( $product ) !== $snapshot['after'] ) { $conflicts[] = (int) $product_id; continue; }
			self::restore_snapshot( $product, $snapshot['before'] );
			$restored++;
			if ( class_exists( 'KREV_Sync_Queue' ) ) { KREV_Sync_Queue::enqueue( $product_id ); }
		}
		return array( 'run_id' => $run_id, 'restored' => $restored, 'conflicts' => $conflicts, 'done' => count( $snapshots ) < self::BATCH_SIZE, 'next_offset' => absint( $offset ) + count( $snapshots ) );
	}

	private static function row( WC_Product $product, array $resolved, array $before ) {
		$categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
		return array( 'product_id' => $product->get_id(), 'title' => $product->get_name(), 'categories' => is_wp_error( $categories ) ? array() : $categories, 'sharp_tag' => (bool) $before['sharp_tag'], 'current_classification' => $before['meta'][ KREV_PA_Condition_Resolver::META_CLASSIFICATION ] ?? '', 'cosmetic_grade' => $resolved['cosmetic_grade'], 'existing_feed_condition' => $product->get_meta( '_krev_google_condition', true, 'edit' ), 'proposed_classification' => $resolved['classification_label'], 'proposed_condition' => $resolved['merchant_condition'] ?: 'unresolved', 'reason' => $resolved['reason'], 'review_required' => $resolved['review_required'] );
	}

	private static function snapshot( WC_Product $product ) {
		$keys = array( KREV_PA_Condition_Resolver::META_SHARPENED, KREV_PA_Condition_Resolver::META_CLASSIFICATION, KREV_PA_Condition_Resolver::META_DERIVED, KREV_PA_Condition_Resolver::META_REVIEW_REASON );
		$meta = array();
		foreach ( $keys as $key ) { $meta[ $key ] = metadata_exists( 'post', $product->get_id(), $key ) ? get_post_meta( $product->get_id(), $key, true ) : null; }
		return array( 'meta' => $meta, 'sharp_tag' => has_term( KREV_PA_Condition_Resolver::SHARP_TAG_SLUG, 'product_tag', $product->get_id() ), 'classification_term' => has_term( 'refurbished', KREV_PA_Condition_Resolver::CLASSIFICATION_TAXONOMY, $product->get_id() ) );
	}

	private static function restore_snapshot( WC_Product $product, array $snapshot ) {
		foreach ( $snapshot['meta'] as $key => $value ) { if ( null === $value ) { $product->delete_meta_data( $key ); } else { $product->update_meta_data( $key, $value ); } }
		KREV_PA_Condition_Resolver::set_sharpened( $product, (bool) $snapshot['sharp_tag'] );
		foreach ( $snapshot['meta'] as $key => $value ) { if ( null === $value ) { $product->delete_meta_data( $key ); } else { $product->update_meta_data( $key, $value ); } }
		if ( ! empty( $snapshot['classification_term'] ) ) { wp_set_object_terms( $product->get_id(), 'refurbished', KREV_PA_Condition_Resolver::CLASSIFICATION_TAXONOMY, true ); } else { wp_remove_object_terms( $product->get_id(), 'refurbished', KREV_PA_Condition_Resolver::CLASSIFICATION_TAXONOMY ); }
		$product->save_meta_data();
	}
}
