<?php
defined( 'ABSPATH' ) || exit;

final class KREV_Orders_Plugin {
	public static function boot() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'woocommerce_notice' ) );
			return;
		}
		if ( get_option( 'krev_orders_db_version' ) !== KREV_ORDERS_SCHEMA_VERSION ) {
			self::install_schema();
		}
		( new KREV_Orders_Endpoints() )->register();
		KREV_Shipping_Policies::register_admin();
		$rest = new KREV_Orders_REST();
		add_action( 'rest_api_init', array( $rest, 'register' ) );
		add_action( 'woocommerce_checkout_create_order_line_item', array( 'KREV_Shipping_Policies', 'snapshot_to_item' ), 20, 4 );
		add_action( 'woocommerce_checkout_order_created', array( 'KREV_Orders_Query', 'index_order' ), 20 );
		add_action( 'woocommerce_checkout_order_created', array( 'KREV_Sharpening_Orders', 'initialize' ), 30 );
		add_action( 'woocommerce_new_order', array( 'KREV_Orders_Query', 'index_order' ), 20 );
		add_action( 'woocommerce_order_status_changed', array( 'KREV_Sharpening_Orders', 'initialize' ), 20 );
		add_action( 'krev_orders_backfill', array( 'KREV_Orders_Query', 'backfill_batch' ) );
		add_action( 'krev_orders_refresh_recent', array( 'KREV_Orders_Query', 'refresh_recent_index' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( 'KREV_Returns', 'render_buyer_actions' ), 20 );
	}

	private static function install_schema() {
		KREV_Orders_Query::install();
		KREV_Shipping_Policies::install();
		KREV_Returns::install();
		KREV_Sharpening_Workflow::install();
		update_option( 'krev_orders_db_version', KREV_ORDERS_SCHEMA_VERSION, false );
		update_option( 'krev_orders_backfill_page', 1, false );
		if ( ! wp_next_scheduled( 'krev_orders_backfill' ) ) { wp_schedule_single_event( time() + 5, 'krev_orders_backfill' ); }
	}

	public static function activate() {
		self::install_schema();
		KREV_Orders_Endpoints::rewrite();
		foreach ( array( 'administrator', 'shop_manager' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) { $role->add_cap( 'krev_manage_sharpening' ); }
		}
		flush_rewrite_rules();
	}

	public static function deactivate() { flush_rewrite_rules(); }

	public static function woocommerce_notice() {
		if ( current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'KnifeRevive Seller Orders requires WooCommerce. It did not initialize.', 'kniferevive-seller-orders' ) . '</p></div>';
		}
	}
}
