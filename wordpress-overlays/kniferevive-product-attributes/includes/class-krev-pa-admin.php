<?php

defined( 'ABSPATH' ) || exit;

final class KREV_PA_Admin {
	const PAGE_SLUG = 'krev-product-attributes';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_post_krev_pa_backfill', array( __CLASS__, 'handle_backfill' ) );
		add_action( 'admin_post_krev_pa_condition_migration', array( __CLASS__, 'handle_condition_migration' ) );
	}

	public static function handle_condition_migration() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'You are not allowed to perform this action.', 'kniferevive-product-attributes' ) ); }
		check_admin_referer( 'krev_pa_condition_migration' );
		$mode = sanitize_key( wp_unslash( $_POST['krev_condition_mode'] ?? 'preview' ) );
		$run  = sanitize_key( wp_unslash( $_POST['krev_condition_run_id'] ?? '' ) );
		$offset = absint( $_POST['krev_condition_offset'] ?? 0 );
		$report = 'rollback' === $mode ? KREV_PA_Condition_Migrator::rollback_batch( $run, $offset ) : KREV_PA_Condition_Migrator::run_batch( $mode, $run, $offset );
		update_user_meta( get_current_user_id(), '_krev_pa_condition_report', $report );
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE_SLUG, 'krev_condition_complete' => $mode ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function add_page() {
		add_submenu_page(
			'woocommerce',
			__( 'KnifeRevive Product Attributes', 'kniferevive-product-attributes' ),
			__( 'Product Attributes Setup', 'kniferevive-product-attributes' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	public static function handle_backfill() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'kniferevive-product-attributes' ) );
		}
		check_admin_referer( 'krev_pa_backfill' );

		$mode = isset( $_POST['krev_pa_mode'] ) ? sanitize_key( wp_unslash( $_POST['krev_pa_mode'] ) ) : 'dry-run';
		$apply = 'apply' === $mode;
		$schema = KREV_PA_Setup::ensure_schema();
		$report = ( new KREV_PA_Migrator( $apply ) )->run();
		$report['schema_errors'] = $schema['errors'];
		update_user_meta( get_current_user_id(), '_krev_pa_last_report', $report );

		$url = add_query_arg(
			array(
				'page'            => self::PAGE_SLUG,
				'krev_pa_complete'=> $apply ? 'apply' : 'dry-run',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$report = get_user_meta( get_current_user_id(), '_krev_pa_last_report', true );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'KnifeRevive Product Attributes', 'kniferevive-product-attributes' ); ?></h1>
			<p><?php esc_html_e( 'Populate only missing category-specific attributes. Existing product attribute values are preserved.', 'kniferevive-product-attributes' ); ?></p>

			<?php if ( isset( $_GET['krev_pa_complete'] ) && is_array( $report ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p>
					<?php
					echo esc_html(
						sprintf(
							'%s complete: %d products scanned; %d products changed; %d attribute decisions recorded.',
							'apply' === $report['mode'] ? 'Backfill' : 'Dry run',
							(int) $report['products_scanned'],
							(int) $report['products_changed'],
							count( $report['rows'] )
						)
					);
					?>
				</p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( '1. Preview', 'kniferevive-product-attributes' ); ?></h2>
			<p><?php esc_html_e( 'Run a dry test first. It does not change product data.', 'kniferevive-product-attributes' ); ?></p>
			<?php self::render_form( 'dry-run', __( 'Run dry test', 'kniferevive-product-attributes' ), false ); ?>

			<h2><?php esc_html_e( '2. Apply hosted backfill', 'kniferevive-product-attributes' ); ?></h2>
			<p><?php esc_html_e( 'Adds inferred values—or “Not specified” when no safe value can be inferred—to empty applicable attributes.', 'kniferevive-product-attributes' ); ?></p>
			<?php self::render_form( 'apply', __( 'Apply backfill', 'kniferevive-product-attributes' ), true ); ?>

			<?php self::render_report( $report ); ?>

			<hr><h2><?php esc_html_e( 'Condition & Sharpened migration', 'kniferevive-product-attributes' ); ?></h2>
			<p><?php esc_html_e( 'Inventory and reconcile condition data in batches of 25. Preview first. Apply snapshots only changed fields; rollback refuses products changed afterward.', 'kniferevive-product-attributes' ); ?></p>
			<?php self::render_condition_tools(); ?>
		</div>
		<?php
	}

	private static function render_condition_tools() {
		$report = get_user_meta( get_current_user_id(), '_krev_pa_condition_report', true );
		$progress = (array) get_option( KREV_PA_Condition_Migrator::OPTION_PROGRESS, array() );
		foreach ( array( 'preview' => 'Preview next batch', 'apply' => 'Apply next batch' ) as $mode => $label ) :
			$continue = isset( $report['mode'] ) && $report['mode'] === $mode && empty( $report['done'] ); ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px">
				<input type="hidden" name="action" value="krev_pa_condition_migration"><input type="hidden" name="krev_condition_mode" value="<?php echo esc_attr( $mode ); ?>">
				<input type="hidden" name="krev_condition_run_id" value="<?php echo esc_attr( $continue ? $report['run_id'] : '' ); ?>"><input type="hidden" name="krev_condition_offset" value="<?php echo esc_attr( $continue ? $report['next_offset'] : 0 ); ?>">
				<?php wp_nonce_field( 'krev_pa_condition_migration' ); submit_button( $continue ? $label : str_replace( 'next ', '', $label ), 'apply' === $mode ? 'primary' : 'secondary', 'submit', false ); ?>
			</form>
		<?php endforeach;
		if ( ! empty( $progress['run_id'] ) && 'apply' === ( $progress['mode'] ?? '' ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block">
				<input type="hidden" name="action" value="krev_pa_condition_migration"><input type="hidden" name="krev_condition_mode" value="rollback"><input type="hidden" name="krev_condition_run_id" value="<?php echo esc_attr( $progress['run_id'] ); ?>"><input type="hidden" name="krev_condition_offset" value="0">
				<?php wp_nonce_field( 'krev_pa_condition_migration' ); submit_button( __( 'Rollback apply run', 'kniferevive-product-attributes' ), 'secondary', 'submit', false ); ?>
			</form>
		<?php endif;
		if ( ! is_array( $report ) || empty( $report ) ) { return; }
		echo '<p><strong>' . esc_html( sprintf( 'Run %s: %d changed/restored; next offset %d%s.', $report['run_id'] ?? '', (int) ( $report['changed'] ?? $report['restored'] ?? 0 ), (int) ( $report['next_offset'] ?? 0 ), ! empty( $report['done'] ) ? ' (complete)' : '' ) ) . '</strong></p>';
		if ( ! empty( $report['conflicts'] ) ) { echo '<p class="notice notice-warning inline">' . esc_html( 'Rollback conflicts (newer edits preserved): ' . implode( ', ', array_map( 'intval', $report['conflicts'] ) ) ) . '</p>'; }
		if ( empty( $report['rows'] ) ) { return; } ?>
		<table class="widefat striped"><thead><tr><th>ID</th><th>Product / categories</th><th>Sharp</th><th>Current classification</th><th>Cosmetic grade</th><th>Existing feed</th><th>Proposed classification</th><th>Proposed condition</th><th>Reason</th></tr></thead><tbody>
		<?php foreach ( $report['rows'] as $row ) : ?><tr><td><?php echo esc_html( (string) $row['product_id'] ); ?></td><td><?php echo esc_html( $row['title'] . ' — ' . implode( ', ', $row['categories'] ) ); ?></td><td><?php echo $row['sharp_tag'] ? 'Yes' : 'No'; ?></td><td><?php echo esc_html( $row['current_classification'] ); ?></td><td><?php echo esc_html( $row['cosmetic_grade'] ); ?></td><td><?php echo esc_html( $row['existing_feed_condition'] ); ?></td><td><?php echo esc_html( $row['proposed_classification'] ); ?></td><td><?php echo esc_html( $row['proposed_condition'] ); ?></td><td><?php echo esc_html( $row['reason'] ); ?></td></tr><?php endforeach; ?>
		</tbody></table><?php
	}

	private static function render_form( $mode, $label, $primary ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="krev_pa_backfill">
			<input type="hidden" name="krev_pa_mode" value="<?php echo esc_attr( $mode ); ?>">
			<?php wp_nonce_field( 'krev_pa_backfill' ); ?>
			<?php submit_button( $label, $primary ? 'primary' : 'secondary', 'submit', false ); ?>
		</form>
		<?php
	}

	private static function render_report( $report ) {
		if ( ! is_array( $report ) || empty( $report['generated_at'] ) ) {
			return;
		}
		$manual = array_values( array_filter( $report['manual_review'] ?? array() ) );
		?>
		<h2><?php esc_html_e( 'Last result', 'kniferevive-product-attributes' ); ?></h2>
		<table class="widefat striped" style="max-width:760px">
			<tbody>
				<tr><th><?php esc_html_e( 'Mode', 'kniferevive-product-attributes' ); ?></th><td><?php echo esc_html( $report['mode'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Products scanned', 'kniferevive-product-attributes' ); ?></th><td><?php echo esc_html( (string) $report['products_scanned'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Products changed', 'kniferevive-product-attributes' ); ?></th><td><?php echo esc_html( (string) $report['products_changed'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Manual review product IDs', 'kniferevive-product-attributes' ); ?></th><td><?php echo esc_html( $manual ? implode( ', ', array_map( 'intval', $manual ) ) : 'None' ); ?></td></tr>
				<?php if ( ! empty( $report['schema_errors'] ) ) : ?>
					<tr><th><?php esc_html_e( 'Schema warnings', 'kniferevive-product-attributes' ); ?></th><td><?php echo esc_html( implode( '; ', $report['schema_errors'] ) ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}
}
