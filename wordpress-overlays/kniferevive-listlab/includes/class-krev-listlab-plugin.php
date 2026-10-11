<?php
defined( 'ABSPATH' ) || exit;

final class KREV_ListLab_Plugin {
	public static function boot() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'woocommerce_notice' ) );
			return;
		}
		add_action( 'admin_init', array( 'KREV_ListLab_Views', 'maybe_upgrade' ) );
		add_action( 'template_redirect', array( 'KREV_ListLab_Views', 'track_current_product' ), 20 );
		KREV_ListLab_Whatnot_Demo::init();
		add_filter( 'render_block_woocommerce/product-image-gallery', array( 'KREV_ListLab_Video_Handler', 'gallery_block' ), 10, 3 );
		add_action( 'woocommerce_before_single_product_summary', array( 'KREV_ListLab_Video_Handler', 'classic_gallery_start' ), 19 );
		add_action( 'woocommerce_before_single_product_summary', array( 'KREV_ListLab_Video_Handler', 'classic_gallery_end' ), 21 );
		add_action( 'wp_enqueue_scripts', array( 'KREV_ListLab_Video_Handler', 'assets' ) );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'single_product_timed_offer' ), 24 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'timed_offer_assets' ) );
		( new KREV_ListLab_Endpoint() )->register();
		add_action( 'save_post_product', array( 'KREV_ListLab_Account_Dashboard', 'invalidate_product' ) );
		add_action( 'woocommerce_order_status_changed', array( 'KREV_ListLab_Account_Dashboard', 'invalidate_order' ), 10, 4 );

		// Register routes only after WordPress has initialized its REST server.
		// Calling register_rest_route() during plugins_loaded forces early REST
		// initialization and fatals on WordPress 7.1 / Jetpack Import.
		$rest = new KREV_ListLab_REST();
		add_action( 'rest_api_init', array( $rest, 'register' ) );
	}
	public static function timed_offer_assets() {
		if ( ! ( is_product() || is_page( 'technology-trade-desk' ) ) ) return;
		wp_enqueue_style( 'krev-timed-offers', KREV_LISTLAB_URL . 'assets/css/timed-offers.css', array(), KREV_LISTLAB_VERSION );
		wp_enqueue_script( 'krev-timed-offers', KREV_LISTLAB_URL . 'assets/js/timed-offers.js', array(), KREV_LISTLAB_VERSION, true );
	}

	/** Display WooCommerce scheduled-sale information on every product type/category. */
	public static function single_product_timed_offer(): void {
		if ( ! is_product() ) return;
		global $product;
		if ( ! $product instanceof WC_Product || $product->get_id() !== (int) get_queried_object_id() ) return;
		echo KREV_ListLab_Timed_Offers::render( $product, 'full' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by shared renderer.
	}

	public static function activate() {
		if ( class_exists( 'WooCommerce' ) ) {
			KREV_ListLab_Views::maybe_upgrade();
			KREV_ListLab_Endpoint::register_rewrite_endpoint();
			flush_rewrite_rules();
		}
	}

	public static function woocommerce_notice() {
		if ( current_user_can( 'activate_plugins' ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'KnifeRevive ListLab requires WooCommerce. It did not initialize.', 'kniferevive-listlab' ) . '</p></div>';
		}
	}
}
