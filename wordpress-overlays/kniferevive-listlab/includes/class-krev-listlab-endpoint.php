<?php
defined( 'ABSPATH' ) || exit;

final class KREV_ListLab_Endpoint {
	const ENDPOINT = 'listlab';

	public function register() {
		add_action( 'init', array( __CLASS__, 'register_rewrite_endpoint' ) );
		add_action( 'template_redirect', array( $this, 'require_login' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 100 );
		add_action( 'wp_footer', array( $this, 'replace_vendor_dashboard_cta' ), 99 );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'menu' ), 30 );
		add_filter( 'woocommerce_get_endpoint_url', array( $this, 'dashboard_url' ), 10, 4 );
		add_filter( 'woocommerce_login_redirect', array( $this, 'login_redirect' ), 99, 2 );
		add_filter( 'login_redirect', array( $this, 'wp_login_redirect' ), 99, 3 );
		add_filter( 'woocommerce_registration_redirect', array( $this, 'registration_redirect' ), 99 );
		add_filter( 'secure_passkeys_login_redirect_url', array( $this, 'passkey_redirect' ), 99, 3 );
		// wc_get_template runs after WooCommerce's located-template object cache,
		// so the dashboard override cannot be bypassed by a cached theme path.
		add_filter( 'wc_get_template', array( $this, 'dashboard_template' ), 99, 5 );
	}

	public function dashboard_template( $template, $template_name, $args = array(), $template_path = '', $default_path = '' ) {
		if ( 'myaccount/dashboard.php' !== $template_name || ! is_user_logged_in() || ! KREV_ListLab_Dokan_Adapter::can_sell() ) { return $template; }
		$override = KREV_LISTLAB_PATH . 'templates/myaccount-dashboard.php';
		return is_readable( $override ) ? $override : $template;
	}

	public static function register_rewrite_endpoint() { add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES ); }

	public function require_login() {
		if ( $this->is_endpoint() && ! is_user_logged_in() ) {
			wp_safe_redirect( add_query_arg( 'redirect_to', wc_get_account_endpoint_url( self::ENDPOINT ), wc_get_page_permalink( 'myaccount' ) ) );
			exit;
		}
	}

	public function menu( $items ) {
		$out = array();
		foreach ( $items as $key => $label ) {
			if ( 'dashboard' === $key ) {
				$out[ $key ] = __( 'Seller Settings', 'kniferevive-listlab' );
				$out[ self::ENDPOINT ] = __( 'My Listings', 'kniferevive-listlab' );
			} else {
				$out[ $key ] = $label;
			}
		}
		return $out;
	}

	public function dashboard_url( $url, $endpoint ) { return 'dashboard' === $endpoint ? wc_get_page_permalink( 'myaccount' ) : $url; }

	public function login_redirect( $redirect, $user ) { return $this->frontend_account_url( $user, $redirect ); }
	public function wp_login_redirect( $redirect, $requested, $user ) { return $this->frontend_account_url( $user, $redirect ); }
	public function registration_redirect( $redirect ) {
		if ( function_exists( 'dokan_get_option' ) && 'off' === dokan_get_option( 'disable_welcome_wizard', 'dokan_selling', 'off' ) ) { return $redirect; }
		return wc_get_page_permalink( 'myaccount' );
	}
	public function passkey_redirect( $redirect, $user = null, $requested = '' ) { return $this->frontend_account_url( $user, $redirect ); }

	private function frontend_account_url( $user, $fallback ) {
		if ( ! $user instanceof WP_User ) { return $fallback; }
		if ( user_can( $user, 'manage_options' ) && false !== strpos( (string) $fallback, 'wp-admin' ) ) { return $fallback; }
		return wc_get_page_permalink( 'myaccount' );
	}

	public function replace_vendor_dashboard_cta() {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || $this->is_endpoint() || ! KREV_ListLab_Dokan_Adapter::can_sell() ) { return; }
		echo '<script>(function(){var links=document.querySelectorAll("a");for(var i=0;i<links.length;i++){var a=links[i],text=(a.textContent||"").replace(/\s+/g," ").trim();if(/^(go to )?(vendor|seller) dashboard$/i.test(text)){var wrap=a.closest("p,div");(wrap||a).remove();}}}());</script>';
	}

	public function render() {
		if ( ! KREV_ListLab_Dokan_Adapter::can_sell() ) { echo '<div class="woocommerce-info">' . esc_html__( 'ListLab is available to enabled sellers.', 'kniferevive-listlab' ) . '</div>'; return; }
		include KREV_LISTLAB_PATH . 'templates/listlab.php';
	}

	public function assets() {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) { return; }
		wp_enqueue_style( 'krev-listlab-account', KREV_LISTLAB_URL . 'assets/css/account-profile.css', array(), KREV_LISTLAB_VERSION );
		if ( ! $this->is_endpoint() ) { return; }
		wp_enqueue_style( 'krev-listlab', KREV_LISTLAB_URL . 'assets/css/listlab.css', array(), KREV_LISTLAB_VERSION );
		wp_enqueue_script( 'krev-listlab-zxing', KREV_LISTLAB_URL . 'assets/vendor/zxing-browser-0.2.1/zxing-browser.min.js', array(), '0.2.1', true );
		wp_enqueue_script( 'krev-listlab', KREV_LISTLAB_URL . 'assets/js/listlab.js', array( 'krev-listlab-zxing' ), KREV_LISTLAB_VERSION, true );
		wp_localize_script( 'krev-listlab', 'KREVListLab', array(
			'root'              => esc_url_raw( rest_url( 'kniferevive/v1/' ) ),
			'nonce'             => wp_create_nonce( 'wp_rest' ),
			'accountUrl'        => wc_get_account_endpoint_url( self::ENDPOINT ),
			'myAccountUrl'      => wc_get_page_permalink( 'myaccount' ),
			'sellerId'          => get_current_user_id(),
			'sellerName'        => wp_get_current_user()->display_name,
			'siteTimezone'      => wp_timezone_string(),
			'timezones'         => timezone_identifiers_list(),
			'maxVideoUploadBytes' => KREV_ListLab_Video_Handler::limit(),
			'maxVideoUploadLabel' => size_format( KREV_ListLab_Video_Handler::limit() ),
			'videoHostingAvailable' => KREV_ListLab_Video_Handler::hosting_available(),
			'sharedVideosAvailable' => class_exists( 'KREV_Video_Links' ) && KREV_Video_Links::seller_has_access( get_current_user_id() ),
		) );
	}

	private function is_endpoint() { return ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( self::ENDPOINT ) ) || null !== get_query_var( self::ENDPOINT, null ); }
}
