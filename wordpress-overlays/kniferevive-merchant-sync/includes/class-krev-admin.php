<?php

defined( 'ABSPATH' ) || exit;

final class KREV_Admin {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_post_krev_merchant_action', array( __CLASS__, 'handle_action' ) );
		add_action( 'add_meta_boxes_product', array( __CLASS__, 'add_product_meta_box' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product_condition' ) );
	}

	public static function add_menu() {
		add_submenu_page( 'woocommerce', 'Merchant Sync', 'Merchant Sync', 'manage_woocommerce', 'krev-merchant-sync', array( __CLASS__, 'render_page' ) );
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$config     = KREV_Merchant_Config::safe_status();
		$notice     = get_transient( 'krev_merchant_admin_notice_' . get_current_user_id() );
		$gate_open  = (bool) get_option( 'krev_merchant_bulk_approved', false );
		$progress   = (array) get_option( 'krev_merchant_reconciliation_progress', array() );
		$connection = (array) get_option( 'krev_merchant_connection_status', array() );
		$counts     = self::overview_counts();
		?>
		<div class="wrap">
			<h1>KnifeRevive Merchant Sync</h1>
			<?php if ( is_array( $notice ) ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ?? 'info' ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ?? '' ); ?></p></div>
			<?php endif; ?>

			<h2>Connection</h2>
			<table class="widefat striped" style="max-width:900px"><tbody>
				<tr><th>Merchant account</th><td><?php echo esc_html( $config['account_id'] ); ?></td></tr>
				<tr><th>Data source</th><td><?php echo esc_html( $config['datasource_name'] ); ?></td></tr>
				<tr><th>Service account</th><td><?php echo esc_html( $config['service_account'] ); ?></td></tr>
				<tr><th>Credential storage</th><td><?php echo esc_html( $config['credential_storage'] ); ?></td></tr>
				<tr><th>Credential status</th><td><?php echo $config['credentials_configured'] ? 'Configured' : esc_html( $config['credentials_error'] ); ?></td></tr>
				<tr><th>Authentication</th><td><?php echo esc_html( $connection['authentication'] ?? 'Not tested in admin' ); ?></td></tr>
				<tr><th>Developer registration</th><td><?php echo esc_html( $connection['registration'] ?? 'Registered during setup' ); ?></td></tr>
				<tr><th>Verified data source</th><td><?php echo esc_html( $connection['datasource'] ?? 'Not tested in admin' ); ?></td></tr>
			</tbody></table>

			<?php if ( '' === KREV_Merchant_Config::credential_path() ) : ?>
				<h3>Secure credential upload</h3>
				<p>For hosting without private file access. The JSON is validated, encrypted with this site's WordPress security keys, and stored as a non-autoloaded option. The temporary upload is not retained.</p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="krev_merchant_action">
					<input type="hidden" name="krev_action" value="upload-credentials">
					<?php wp_nonce_field( 'krev_merchant_action' ); ?>
					<label>Google service-account JSON <input type="file" name="credential_file" accept="application/json,.json" required></label>
					<?php submit_button( 'Encrypt and Save Credential', 'secondary', 'submit', false ); ?>
				</form>
			<?php endif; ?>

			<?php if ( ! empty( $connection['competing_primary_sources'] ) ) : ?>
				<div class="notice notice-warning inline"><p><strong>WARNING:</strong> Another primary source appears to contain KnifeRevive product IDs. Merchant API source 10717392032 is intended to be authoritative. Review legacy connector/source configuration.</p></div>
			<?php endif; ?>

			<h2>Actions</h2>
			<div style="display:flex;gap:8px;flex-wrap:wrap">
				<?php self::action_form( 'test-auth', 'Test Authentication' ); ?>
				<?php self::action_form( 'verify-datasource', 'Verify Data Source' ); ?>
				<?php self::action_form( 'reconcile', 'Reconcile Now', ! $gate_open ); ?>
				<?php self::action_form( 'sync-all', 'Sync All Eligible Products', ! $gate_open ); ?>
			</div>

			<?php self::render_google_category_mappings(); ?>

			<h3>One-product test</h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="krev_merchant_action">
				<input type="hidden" name="krev_action" value="one-product-test">
				<?php wp_nonce_field( 'krev_merchant_action' ); ?>
				<label>WooCommerce product ID <input type="number" min="1" name="product_id" required></label>
				<?php submit_button( 'Run One-Product Test', 'secondary', 'submit', false ); ?>
			</form>

			<h3>Verify a product's Google category</h3>
			<p>Reads the category on Google’s processed product; it does not upload or change the listing.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="krev_merchant_action">
				<input type="hidden" name="krev_action" value="verify-google-category">
				<?php wp_nonce_field( 'krev_merchant_action' ); ?>
				<label>WooCommerce product ID <input type="number" min="1" name="product_id" required></label>
				<?php submit_button( 'Verify Google Category', 'secondary', 'submit', false ); ?>
			</form>

			<h3>Test gate</h3>
			<p><?php echo $gate_open ? 'Verified by an administrator; controlled bulk actions are enabled.' : 'Locked. Verify the one-product result in Merchant Center before approval.'; ?></p>
			<?php if ( ! $gate_open ) { self::action_form( 'approve-gate', 'I verified the test in Merchant Center' ); } ?>

			<h2>Sync overview</h2>
			<table class="widefat striped" style="max-width:900px"><thead><tr><th>Published eligible candidates</th><th>Synced</th><th>Errors</th><th>Out of stock</th><th>Queued in last reconciliation</th></tr></thead><tbody><tr>
				<td><?php echo esc_html( $counts['eligible'] ); ?></td><td><?php echo esc_html( $counts['synced'] ); ?></td><td><?php echo esc_html( $counts['errors'] ); ?></td><td><?php echo esc_html( $counts['out_of_stock'] ); ?></td><td><?php echo esc_html( $progress['queued'] ?? 0 ); ?></td>
			</tr></tbody></table>

			<h2>Recent errors</h2>
			<ul><?php foreach ( self::recent_errors() as $error ) : ?><li><a href="<?php echo esc_url( get_edit_post_link( $error->ID ) ); ?>">Product <?php echo esc_html( $error->ID ); ?></a>: <?php echo esc_html( $error->error ); ?></li><?php endforeach; ?></ul>
		</div>
		<?php
	}

	public static function handle_action() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Insufficient permissions.' );
		}
		check_admin_referer( 'krev_merchant_action' );
		$action  = sanitize_key( wp_unslash( $_POST['krev_action'] ?? '' ) );
		$type    = 'success';
		$message = '';

		switch ( $action ) {
			case 'upload-credentials':
				$result = self::store_uploaded_credentials();
				if ( is_wp_error( $result ) ) {
					$message = $result->get_error_message();
					$type    = 'error';
				} else {
					update_option(
						'krev_merchant_connection_status',
						array(
							'authentication' => 'Not tested after credential update',
							'registration'   => 'Registered during setup',
							'datasource'     => 'Not tested after credential update',
						),
						false
					);
					$message = 'Credential validated, encrypted, and saved. The temporary upload was not retained.';
				}
				break;
			case 'test-auth':
				$result  = KREV_Google_Auth::test();
				$message = $result['message'];
				$type    = $result['success'] ? 'success' : 'error';
				self::merge_connection( array( 'authentication' => $result['success'] ? 'Connected' : 'Error' ) );
				break;
			case 'verify-datasource':
				$api      = new KREV_Merchant_API();
				$result   = $api->get_datasource();
				$sources  = $api->list_datasources();
				$valid    = ! is_wp_error( $result ) && KREV_Merchant_Config::datasource_name() === ( $result['name'] ?? '' ) && 'API' === ( $result['input'] ?? '' ) && isset( $result['primaryProductDataSource'] );
				$competing = array();
				if ( ! is_wp_error( $sources ) ) {
					foreach ( $sources['dataSources'] ?? array() as $source ) {
						if ( isset( $source['primaryProductDataSource'] ) && KREV_Merchant_Config::datasource_name() !== ( $source['name'] ?? '' ) ) {
							$competing[] = array( 'name' => $source['name'] ?? '', 'displayName' => $source['displayName'] ?? '', 'input' => $source['input'] ?? '' );
						}
					}
				}
				self::merge_connection( array( 'datasource' => $valid ? 'Verified' : 'Error', 'competing_primary_sources' => $competing ) );
				$message = $valid ? 'Data source 10717392032 verified.' : ( is_wp_error( $result ) ? $result->get_error_message() : 'Data source mismatch.' );
				$type    = $valid ? 'success' : 'error';
				break;
			case 'one-product-test':
				$product_id = absint( $_POST['product_id'] ?? 0 );
				$result     = ( new KREV_Product_Sync() )->sync( $product_id, true );
				$category   = self::local_google_category( $product_id );
				$message    = is_wp_error( $result ) ? $result->get_error_message() : sprintf( 'Product %d uploaded%s. Use “Verify Google Category” after Google finishes processing.', $product_id, $category ? sprintf( ' with Google category %s', $category['id'] ) : '' );
				$type       = is_wp_error( $result ) ? 'error' : 'success';
				break;
			case 'verify-google-category':
				$product_id = absint( $_POST['product_id'] ?? 0 );
				$local      = self::local_google_category( $product_id );
				$result     = ( new KREV_Product_Sync() )->refresh_processed_status( $product_id );
				if ( is_wp_error( $result ) ) {
					$message = $result->get_error_message();
					$type    = 'error';
					break;
				}
				$reported = (string) ( $result['google_category'] ?? '' );
				$expected = $local['id'] ?? '';
				if ( '' !== $expected && $expected === $reported ) {
					$message = sprintf( 'Verified: product %d is mapped locally and in Google as category %s.', $product_id, $reported );
				} elseif ( '' === $reported ) {
					$message = sprintf( 'Product %d is mapped locally as %s, but Google has not reported a category yet. Run the one-product test or reconciliation, then verify again after processing.', $product_id, $expected ?: 'automatic categorization' );
					$type    = 'warning';
				} else {
					$message = sprintf( 'Product %d local category is %s; Google currently reports %s. Re-upload this product, then verify again after processing.', $product_id, $expected ?: 'automatic categorization', $reported );
					$type    = 'warning';
				}
				break;
			case 'approve-gate':
				update_option( 'krev_merchant_bulk_approved', true, false );
				$message = 'One-product test gate approved. Controlled bulk actions are now enabled.';
				break;
			case 'save-google-category-mappings':
				$old = KREV_Google_Category_Mapper::mappings();
				$saved = KREV_Google_Category_Mapper::save( wp_unslash( $_POST['mappings'] ?? array() ) );
				if ( is_wp_error( $saved ) ) {
					$message = $saved->get_error_message();
					$type = 'error';
					break;
				}
				update_option( KREV_Google_Category_Mapper::OPTION, $saved, false );
				update_option( KREV_Google_Category_Mapper::INITIALIZED_OPTION, true, false );
				$affected = wp_json_encode( $old ) === wp_json_encode( $saved ) ? array() : KREV_Google_Category_Mapper::affected_published_product_ids( $old, $saved );
				$queued = KREV_Sync_Queue::enqueue_many( $affected );
				$message = $affected ? sprintf( 'Google category mappings saved. %d affected published products have been queued for Merchant resynchronization.', $queued ) : 'Google category mappings saved. No published products were affected.';
				if ( $affected && ! get_option( 'krev_merchant_bulk_approved', false ) ) $message .= ' The test gate is locked, so affected products were not scheduled yet.';
				break;
			case 'sync-all':
			case 'reconcile':
				$result  = KREV_Sync_Queue::queue_reconciliation();
				$message = is_wp_error( $result ) ? $result->get_error_message() : sprintf( 'Reconciliation queued for %d published products.', $result['eligible'] );
				$type    = is_wp_error( $result ) ? 'error' : 'success';
				break;
			default:
				$message = 'Unknown Merchant Sync action.';
				$type    = 'error';
		}

		set_transient( 'krev_merchant_admin_notice_' . get_current_user_id(), array( 'type' => $type, 'message' => KREV_Logger::sanitize( $message ) ), 60 );
		wp_safe_redirect( admin_url( 'admin.php?page=krev-merchant-sync' ) );
		exit;
	}

	private static function store_uploaded_credentials() {
		if ( '' !== KREV_Merchant_Config::credential_path() ) {
			return new WP_Error( 'krev_credentials_managed_by_file', 'Credentials are managed by the external file configured in wp-config.php.' );
		}

		$file = $_FILES['credential_file'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- checked by the action handler.
		if ( ! is_array( $file ) || UPLOAD_ERR_OK !== (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
			return new WP_Error( 'krev_credentials_upload_failed', 'The credential upload failed. Select the JSON file and try again.' );
		}

		$size = (int) ( $file['size'] ?? 0 );
		if ( $size < 1 || $size > KREV_Merchant_Config::MAX_CREDENTIAL_BYTES ) {
			return new WP_Error( 'krev_credentials_upload_size', 'The credential file is empty or unexpectedly large.' );
		}

		$tmp_name = (string) ( $file['tmp_name'] ?? '' );
		if ( '' === $tmp_name || ! is_uploaded_file( $tmp_name ) || ! is_readable( $tmp_name ) ) {
			return new WP_Error( 'krev_credentials_upload_invalid', 'WordPress could not read the temporary credential upload.' );
		}

		$json = file_get_contents( $tmp_name );
		if ( false === $json ) {
			return new WP_Error( 'krev_credentials_upload_unreadable', 'WordPress could not read the uploaded credential file.' );
		}

		return KREV_Merchant_Config::store_credentials_json( $json );
	}

	public static function add_product_meta_box() {
		add_meta_box( 'krev-merchant-status', 'Merchant Sync', array( __CLASS__, 'render_product_meta_box' ), 'product', 'side', 'default' );
	}

	public static function render_product_meta_box( $post ) {
		wp_nonce_field( 'krev_product_condition', 'krev_product_condition_nonce' );
		$condition = (string) get_post_meta( $post->ID, KREV_Product_Mapper::CONDITION_META, true );
		$status    = json_decode( (string) get_post_meta( $post->ID, KREV_Product_Sync::META_GOOGLE_STATUS, true ), true );
		$local_category = self::local_google_category( $post->ID );
		$fields    = array(
			'Offer ID'          => get_post_meta( $post->ID, KREV_Product_Mapper::OFFER_META, true ),
			'Last synced'       => get_post_meta( $post->ID, KREV_Product_Sync::META_LAST_SYNC_AT, true ),
			'API status'        => get_post_meta( $post->ID, KREV_Product_Sync::META_API_STATUS, true ),
			'Google processing' => $status['processing'] ?? '',
			'Google eligibility'=> $status['eligibility'] ?? '',
			'Google category (next sync)' => $local_category ? $local_category['id'] . ( empty( $local_category['path'] ) ? '' : ' — ' . $local_category['path'] ) : 'Automatic',
			'Google category (reported)' => get_post_meta( $post->ID, KREV_Product_Sync::META_GOOGLE_CATEGORY, true ),
			'Last error'        => get_post_meta( $post->ID, KREV_Product_Sync::META_LAST_ERROR, true ),
		);
		foreach ( $fields as $label => $value ) {
			echo '<p><strong>' . esc_html( $label ) . ':</strong><br>' . esc_html( $value ?: '—' ) . '</p>';
		}
		echo '<p><label for="krev_google_condition"><strong>Google condition</strong></label><select name="krev_google_condition" id="krev_google_condition" style="width:100%">';
		foreach ( array( '' => 'Default', 'NEW' => 'New', 'USED' => 'Used', 'REFURBISHED' => 'Refurbished' ) as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $condition, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></p>';
	}

	public static function save_product_condition( $product_id ) {
		if ( ! isset( $_POST['krev_product_condition_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['krev_product_condition_nonce'] ) ), 'krev_product_condition' ) ) {
			return;
		}
		$value = strtoupper( sanitize_text_field( wp_unslash( $_POST['krev_google_condition'] ?? '' ) ) );
		if ( in_array( $value, array( 'NEW', 'USED', 'REFURBISHED' ), true ) ) {
			update_post_meta( $product_id, KREV_Product_Mapper::CONDITION_META, $value );
		} else {
			delete_post_meta( $product_id, KREV_Product_Mapper::CONDITION_META );
		}
		KREV_Sync_Queue::enqueue( $product_id );
	}

	private static function render_google_category_mappings() {
		$mappings = KREV_Google_Category_Mapper::mappings();
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC' ) );
		$terms = is_wp_error( $terms ) ? array() : $terms;
		$existing = self::mapping_index( $mappings );
		$automatic = array();
		if ( class_exists( 'KREV_PA_Tech_Categories' ) ) {
			foreach ( $terms as $term ) {
				$mapping = KREV_PA_Tech_Categories::mapping( $term->term_id );
				if ( $mapping && ! isset( $existing['terms'][$term->term_id] ) ) $automatic[$term->term_id] = array( 'term' => $term, 'mapping' => $mapping );
			}
			// Managed rows have no form inputs: a large taxonomy must not exceed
			// PHP max_input_vars and silently truncate the manual mapping form.
			$terms = array_values( array_filter( $terms, static function( $term ) use ( $automatic ) { return ! isset( $automatic[$term->term_id] ); } ) );
		}
		$ordered_terms = self::ordered_category_terms( $terms );
		$mapped_count = count( array_filter( $existing['terms'], static function ( $mapping ) { return ! empty( $mapping['enabled'] ); } ) ) + ( ! empty( $existing['all_knives']['enabled'] ) ? 1 : 0 );
		?>
		<hr style="margin:28px 0 22px">
		<h2>Google Product Category Mapping</h2>
		<?php if ( $automatic ) : ?>
		<details><summary><?php echo esc_html( count( $automatic ) . ' Tech subcategories mapped automatically to Google Electronics' ); ?></summary>
		<p>These mappings come from the official Google taxonomy snapshot. Select the matching Tech subcategory in ListLab.</p>
		<table class="widefat striped"><thead><tr><th>Tech subcategory</th><th>Google ID</th><th>Google taxonomy path</th></tr></thead><tbody>
		<?php foreach ( $automatic as $row ) echo '<tr><td>' . esc_html( $row['term']->name ) . '</td><td>' . esc_html( $row['mapping']['id'] ) . '</td><td>' . esc_html( $row['mapping']['path'] ) . '</td></tr>'; ?>
		</tbody></table></details>
		<?php endif; ?>
		<p>Each KnifeRevive category appears once below, so it cannot be mapped twice. Enter a Google ID and taxonomy path only for categories you want to set explicitly; unchecked categories use Google’s automatic categorization.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="krev-google-category-mappings">
			<input type="hidden" name="action" value="krev_merchant_action"><input type="hidden" name="krev_action" value="save-google-category-mappings">
			<?php wp_nonce_field( 'krev_merchant_action' ); ?>
			<p><strong><?php echo esc_html( sprintf( '%d of %d rows are explicitly mapped.', $mapped_count, count( $ordered_terms ) + 1 ) ); ?></strong> The saved values remain visible after refresh. <strong>All Knives</strong> covers every configured kitchen-knife category; an individual knife row can override it.</p>
			<div style="overflow:auto;max-width:1200px"><table class="widefat striped"><thead><tr><th style="width:70px">Map</th><th>KnifeRevive Category</th><th style="width:110px">Google ID</th><th>Google Taxonomy Path</th><th style="width:90px">Include Children</th></tr></thead><tbody>
			<?php self::render_mapping_grid_row( 0, null, 0, $existing['all_knives'] ?? KREV_Google_Category_Mapper::default_all_knives_mapping() ); ?>
			<?php foreach ( $ordered_terms as $index => $item ) self::render_mapping_grid_row( $index + 1, $item['term'], $item['depth'], $existing['terms'][ $item['term']->term_id ] ?? array() ); ?>
			</tbody></table></div>
			<p><em>To stop sending a category, uncheck Map and clear its ID and path. The category itself stays in the grid.</em></p>
			<?php submit_button( 'Save Mappings', 'primary', 'submit', false ); ?>
		</form>
		<?php
	}

	private static function render_mapping_grid_row( $index, $term, $depth, array $mapping ) {
		$is_legacy = ! $term;
		$term_id = $is_legacy ? 0 : (int) $term->term_id;
		$group = $is_legacy ? (string) ( $mapping['special_group'] ?? '' ) : '';
		$name = 'mappings[' . $index . ']';
		$label = $is_legacy ? 'All Knives' : str_repeat( '— ', max( 0, (int) $depth ) ) . $term->name;
		$description = $is_legacy ? 'Applies to all configured kitchen-knife categories.' : ( $term->count ? sprintf( '%d products', $term->count ) : 'No products yet' );
		echo '<tr><td><label class="screen-reader-text" for="krev-map-' . esc_attr( $index ) . '">Map ' . esc_html( $label ) . '</label><input id="krev-map-' . esc_attr( $index ) . '" type="checkbox" name="' . esc_attr( $name ) . '[enabled]" value="1" ' . checked( ! empty( $mapping['enabled'] ), true, false ) . '></td><td><strong>' . esc_html( $label ) . '</strong><br><span class="description">' . esc_html( $description ) . '</span><input type="hidden" name="' . esc_attr( $name ) . '[product_cat_term_id]" value="' . esc_attr( $term_id ) . '"><input type="hidden" name="' . esc_attr( $name ) . '[special_group]" value="' . esc_attr( $group ) . '"></td><td><input name="' . esc_attr( $name ) . '[google_category_id]" inputmode="numeric" pattern="[0-9]+" value="' . esc_attr( $mapping['google_category_id'] ?? '' ) . '" style="width:90px"></td><td><input name="' . esc_attr( $name ) . '[google_category_path]" value="' . esc_attr( $mapping['google_category_path'] ?? '' ) . '" style="min-width:360px;width:100%"></td><td><label><input type="checkbox" name="' . esc_attr( $name ) . '[include_descendants]" value="1" ' . checked( ! empty( $mapping['include_descendants'] ), true, false ) . '> Yes</label></td></tr>';
	}

	private static function mapping_index( array $mappings ) {
		$result = array( 'terms' => array(), 'all_knives' => null );
		foreach ( $mappings as $mapping ) {
			$term_id = absint( $mapping['product_cat_term_id'] ?? 0 );
			if ( $term_id ) {
				if ( ! isset( $result['terms'][ $term_id ] ) ) $result['terms'][ $term_id ] = $mapping;
			} elseif ( 'knives' === ( $mapping['special_group'] ?? '' ) && null === $result['all_knives'] ) {
				$result['all_knives'] = $mapping;
			}
		}
		return $result;
	}

	private static function ordered_category_terms( array $terms ) {
		$by_id = array();
		$children = array();
		foreach ( $terms as $term ) $by_id[ (int) $term->term_id ] = $term;
		foreach ( $terms as $term ) {
			$parent = (int) $term->parent;
			$children[ isset( $by_id[ $parent ] ) ? $parent : 0 ][] = $term;
		}
		foreach ( $children as &$siblings ) usort( $siblings, static function ( $a, $b ) { return strcasecmp( $a->name, $b->name ); } );
		unset( $siblings );
		$ordered = array();
		$walk = static function ( $parent, $depth ) use ( &$walk, &$ordered, $children ) {
			foreach ( $children[ $parent ] ?? array() as $term ) {
				$ordered[] = array( 'term' => $term, 'depth' => $depth );
				$walk( (int) $term->term_id, $depth + 1 );
			}
		};
		$walk( 0, 0 );
		return $ordered;
	}

	private static function action_form( $action, $label, $disabled = false ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="krev_merchant_action"><input type="hidden" name="krev_action" value="' . esc_attr( $action ) . '">';
		wp_nonce_field( 'krev_merchant_action' );
		submit_button( $label, 'secondary', 'submit', false, $disabled ? array( 'disabled' => 'disabled' ) : array() );
		echo '</form>';
	}

	private static function merge_connection( array $values ) {
		update_option( 'krev_merchant_connection_status', array_merge( (array) get_option( 'krev_merchant_connection_status', array() ), $values ), false );
	}

	private static function local_google_category( $product_id ) {
		$product = wc_get_product( (int) $product_id );

		return $product ? KREV_Google_Category_Mapper::resolve_for_product( $product ) : null;
	}

	private static function overview_counts() {
		global $wpdb;
		return array(
			'eligible'     => KREV_Sync_Queue::published_eligible_count(),
			'synced'       => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s", KREV_Product_Sync::META_API_STATUS, 'successful' ) ),
			'errors'       => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s", KREV_Product_Sync::META_API_STATUS, 'error' ) ),
			'out_of_stock' => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key='_stock_status' AND meta_value='outofstock'" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}

	private static function recent_errors() {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT p.ID,pm.meta_value AS error FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID WHERE p.post_type='product' AND pm.meta_key=%s AND pm.meta_value<>'' ORDER BY p.post_modified_gmt DESC LIMIT 10", KREV_Product_Sync::META_LAST_ERROR ) );
	}
}
