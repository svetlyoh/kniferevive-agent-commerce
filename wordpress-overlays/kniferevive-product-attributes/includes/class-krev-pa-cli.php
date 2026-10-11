<?php

defined( 'ABSPATH' ) || exit;

final class KREV_PA_CLI {
	public static function init() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'krev attributes', __CLASS__ );
		}
	}

	/** Set up the World Coins category and global attributes. */
	public function setup() {
		$result = KREV_PA_Setup::ensure_schema();
		WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
		if ( $result['errors'] ) {
			WP_CLI::warning( 'Setup completed with errors.' );
			return;
		}
		WP_CLI::success( 'World Coins and product attribute schema are ready.' );
	}

	/**
	 * Audit or apply the local product backfill.
	 *
	 * ## OPTIONS
	 *
	 * [--apply]
	 * : Write the proposed taxonomy-backed attribute values.
	 *
	 * [--output=<path>]
	 * : Save the JSON report to this path.
	 */
	public function backfill( $args, $assoc_args ) {
		$apply = isset( $assoc_args['apply'] );
		$migrator = new KREV_PA_Migrator( $apply );
		$report = $migrator->run();
		$json = wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( ! empty( $assoc_args['output'] ) ) {
			$path = wp_normalize_path( $assoc_args['output'] );
			$written = file_put_contents( $path, $json ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			if ( false === $written ) {
				WP_CLI::error( 'Could not write report: ' . $path );
			}
			WP_CLI::line( 'Report: ' . $path );
		}
		WP_CLI::line( sprintf( 'Mode: %s', $report['mode'] ) );
		WP_CLI::line( sprintf( 'Products scanned: %d', $report['products_scanned'] ) );
		WP_CLI::line( sprintf( 'Products changed: %d', $report['products_changed'] ) );
		WP_CLI::line( sprintf( 'Audit rows: %d', count( $report['rows'] ) ) );
		WP_CLI::line( 'Source counts: ' . wp_json_encode( $report['source_counts'] ) );
		WP_CLI::line( 'Manual review: ' . wp_json_encode( array_values( array_filter( $report['manual_review'] ) ) ) );
		WP_CLI::success( $apply ? 'Local backfill applied.' : 'Dry run complete; no product data changed.' );
	}

	/** Verify categories, attribute definitions, and preservation requirements. */
	public function verify() {
		$category = get_term_by( 'slug', 'world-coins', 'product_cat' );
		$attributes = array();
		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$attributes[ $attribute->attribute_name ] = array(
				'id'   => (int) $attribute->attribute_id,
				'name' => $attribute->attribute_label,
			);
		}
		$required = array_keys( KREV_PA_Config::all_definitions() );
		$result = array(
			'world_coins' => $category ? array(
				'id'          => (int) $category->term_id,
				'name'        => $category->name,
				'slug'        => $category->slug,
				'parent'      => (int) $category->parent,
				'description' => $category->description,
				'archive'     => get_term_link( $category ),
			) : null,
			'attribute_count' => count( $attributes ),
			'missing'         => array_values( array_diff( $required, array_keys( $attributes ) ) ),
			'preserved'       => array_intersect_key( $attributes, array_flip( array( 'model-number', 'condition', 'service' ) ) ),
			'duplicates'      => array(),
		);
		WP_CLI::line( wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
		if ( ! $category || $result['missing'] ) {
			WP_CLI::error( 'Verification failed.' );
		}
		WP_CLI::success( 'Category and global attributes verified.' );
	}
}
