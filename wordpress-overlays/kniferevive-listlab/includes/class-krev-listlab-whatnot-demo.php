<?php
defined( 'ABSPATH' ) || exit;

/** One listing's seller-entered Whatnot demonstration schedule. */
final class KREV_ListLab_Whatnot_Demo {
	public const ENABLED = '_krev_whatnot_demo_enabled';
	public const URL = '_krev_whatnot_demo_url';
	public const START = '_krev_whatnot_demo_starts_at_utc';
	public const TIMEZONE = '_krev_whatnot_demo_timezone';
	private static array $product_rendered = array();

	public static function init(): void {
		add_action( 'woocommerce_after_shop_loop_item_title', array( __CLASS__, 'loop_card' ), 15 );
		add_action( 'kniferevive_knife_card_after_price', array( __CLASS__, 'knife_card' ) );
		add_filter( 'render_block', array( __CLASS__, 'block_price_card' ), 40, 3 );
		add_action( 'woocommerce_product_meta_end', array( __CLASS__, 'single_product' ), 20 );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'single_product' ), 41 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function assets(): void {
		if ( ! is_admin() ) {
			wp_enqueue_style( 'krev-whatnot-demo', KREV_LISTLAB_URL . 'assets/css/whatnot-demo.css', array(), KREV_LISTLAB_VERSION );
			wp_enqueue_script( 'krev-whatnot-demo', KREV_LISTLAB_URL . 'assets/js/whatnot-demo.js', array(), KREV_LISTLAB_VERSION, true );
		}
	}

	public static function validate_url( $input ) {
		if ( ! is_string( $input ) ) { return new WP_Error( 'invalid_whatnot_demo', 'Enter a direct Whatnot show link.' ); }
		$raw = trim( wp_unslash( $input ) );
		if ( ! $raw || preg_match( '/[\x00-\x20\x7f]/', $raw ) || ! preg_match( '#^https://#i', $raw ) ) { return new WP_Error( 'invalid_whatnot_demo', 'Enter a direct HTTPS Whatnot show link.' ); }
		$parts = wp_parse_url( $raw );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || isset( $parts['user'], $parts['pass'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) || isset( $parts['fragment'] ) || isset( $parts['query'] ) ) { return new WP_Error( 'invalid_whatnot_demo', 'This Whatnot URL format is not allowed.' ); }
		$host = strtolower( rtrim( $parts['host'], '.' ) );
		if ( ! preg_match( '/^[a-z0-9.-]+$/', $host ) || ! in_array( $host, array( 'whatnot.com', 'www.whatnot.com' ), true ) ) { return new WP_Error( 'invalid_whatnot_demo', 'Use a direct link on whatnot.com.' ); }
		$path = rawurldecode( $parts['path'] ?? '' );
		if ( str_contains( $path, '%' ) || str_contains( $path, '\\' ) || ! preg_match( '#^/[A-Za-z0-9._/-]+$#', $path ) ) { return new WP_Error( 'invalid_whatnot_demo', 'This Whatnot show path is not allowed.' ); }
		$segments = array_values( array_filter( explode( '/', strtolower( $path ) ), 'strlen' ) );
		if ( count( $segments ) < 2 || in_array( $segments[0], array( 'user', 'invite', 'referral', 's', 'shop', 'store', 'listing', 'category' ), true ) ) { return new WP_Error( 'invalid_whatnot_demo', 'Paste the direct show URL, not a profile, invite, referral, or share link.' ); }
		return esc_url_raw( 'https://' . $host . $path, array( 'https' ) );
	}

	/** Resolve a show-share link only during a seller save, never during buyer rendering. */
	private static function normalize_seller_url( $input ) {
		$direct = self::validate_url( $input );
		if ( ! is_wp_error( $direct ) ) { return $direct; }
		if ( ! is_string( $input ) ) { return $direct; }
		$raw = trim( wp_unslash( $input ) );
		if ( preg_match( '/[\x00-\x20\x7f]/', $raw ) ) { return $direct; }
		$parts = wp_parse_url( $raw );
		if ( ! is_array( $parts ) || 'https' !== strtolower( $parts['scheme'] ?? '' ) || ! in_array( strtolower( $parts['host'] ?? '' ), array( 'whatnot.com', 'www.whatnot.com' ), true ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['port'] ) || isset( $parts['query'] ) || isset( $parts['fragment'] ) || ! preg_match( '#^/s/([A-Za-z0-9_-]+)/?$#', $parts['path'] ?? '', $match ) ) { return $direct; }
		$share_url = 'https://www.whatnot.com/s/' . $match[1];
		$response = wp_safe_remote_head( $share_url, array( 'timeout' => 5, 'redirection' => 0 ) );
		if ( is_wp_error( $response ) || ! in_array( wp_remote_retrieve_response_code( $response ), array( 301, 302, 303, 307, 308 ), true ) ) { return new WP_Error( 'whatnot_demo_share_unavailable', 'The Whatnot show-share link could not be checked. Try the direct show URL.' ); }
		$location = wp_remote_retrieve_header( $response, 'location' );
		$target = is_string( $location ) ? wp_parse_url( $location ) : false;
		if ( ! is_array( $target ) || 'https' !== strtolower( $target['scheme'] ?? '' ) || ! in_array( strtolower( $target['host'] ?? '' ), array( 'whatnot.com', 'www.whatnot.com' ), true ) || isset( $target['user'] ) || isset( $target['pass'] ) || isset( $target['port'] ) || isset( $target['fragment'] ) || ! preg_match( '#^/live/[A-Za-z0-9_-]+/?$#', $target['path'] ?? '' ) ) { return new WP_Error( 'whatnot_demo_share_not_show', 'That share link does not lead to a Whatnot show.' ); }
		// Whatnot adds referral/tracking query parameters; keep only its direct show path.
		return self::validate_url( 'https://www.whatnot.com' . $target['path'] );
	}

	public static function apply( WC_Product $product, $input ) {
		$data = is_array( $input ) ? $input : array();
		$enabled = ! empty( $data['enabled'] );
		if ( ! $enabled ) {
			if ( $product->get_meta( self::ENABLED, true, 'edit' ) ) { $product->update_meta_data( self::ENABLED, 'no' ); }
			return true;
		}
		$errors = array();
		$url = self::normalize_seller_url( $data['url'] ?? '' );
		if ( is_wp_error( $url ) ) { $errors['whatnot_demo.url'] = $url->get_error_message(); }
		$date = trim( (string) ( $data['date'] ?? '' ) );
		$time = trim( (string) ( $data['time'] ?? '' ) );
		$zone = trim( (string) ( $data['timezone'] ?? '' ) );
		if ( ! preg_match( '/^\d{4}-\d\d-\d\d$/', $date ) ) { $errors['whatnot_demo.date'] = 'Enter the show date.'; }
		if ( ! preg_match( '/^\d\d:\d\d$/', $time ) ) { $errors['whatnot_demo.time'] = 'Enter the show start time.'; }
		if ( ! in_array( $zone, timezone_identifiers_list(), true ) ) { $errors['whatnot_demo.timezone'] = 'Choose a valid time zone.'; }
		if ( $errors ) { return self::error( $errors ); }
		$timezone = new DateTimeZone( $zone );
		$local = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $date . ' ' . $time, $timezone );
		$parse_errors = DateTimeImmutable::getLastErrors();
		if ( ! $local || ( is_array( $parse_errors ) && ( $parse_errors['warning_count'] || $parse_errors['error_count'] ) ) || $local->format( 'Y-m-d H:i' ) !== $date . ' ' . $time ) { return self::error( array( 'whatnot_demo.date' => 'Enter a valid date and time in the selected time zone.' ) ); }
		if ( $local->getTimestamp() <= time() ) { return self::error( array( 'whatnot_demo.date' => 'Choose a future date and time for the demo.' ) ); }
		$product->update_meta_data( self::ENABLED, 'yes' );
		$product->update_meta_data( self::URL, $url );
		$product->update_meta_data( self::START, $local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' ) );
		$product->update_meta_data( self::TIMEZONE, $zone );
		return true;
	}

	private static function error( array $errors ): WP_Error {
		return new WP_Error( 'invalid_whatnot_demo', 'Please correct the Whatnot demo fields.', array( 'status' => 422, 'field' => array_key_first( $errors ), 'errors' => $errors ) );
	}

	public static function state( $product_or_id ): array {
		$product = $product_or_id instanceof WC_Product ? $product_or_id : wc_get_product( absint( $product_or_id ) );
		$empty = array( 'enabled' => false, 'url' => '', 'starts_at_utc' => '', 'timezone' => '', 'date' => '', 'time' => '', 'state' => 'none', 'expired' => false );
		if ( ! $product instanceof WC_Product ) { return $empty; }
		$enabled = 'yes' === $product->get_meta( self::ENABLED, true, 'edit' );
		$url = (string) $product->get_meta( self::URL, true, 'edit' );
		$start = (string) $product->get_meta( self::START, true, 'edit' );
		$zone = (string) $product->get_meta( self::TIMEZONE, true, 'edit' );
		$out = $empty;
		$out['enabled'] = $enabled;
		$out['url'] = $url;
		$out['starts_at_utc'] = $start;
		$out['timezone'] = $zone;
		$timestamp = strtotime( $start );
		if ( $timestamp && in_array( $zone, timezone_identifiers_list(), true ) ) {
			$tz = new DateTimeZone( $zone );
			$out['date'] = wp_date( 'Y-m-d', $timestamp, $tz );
			$out['time'] = wp_date( 'H:i', $timestamp, $tz );
			$out['display_card'] = wp_date( 'M j · g:i A T', $timestamp, $tz );
			$out['display_product'] = wp_date( 'M j, Y · g:i A T', $timestamp, $tz );
		}
		if ( ! $enabled ) { return $out; }
		if ( is_wp_error( self::validate_url( $url ) ) || ! $timestamp || ! in_array( $zone, timezone_identifiers_list(), true ) ) { $out['state'] = 'invalid'; return $out; }
		// Only future shows may be promoted. A start time alone cannot prove a show is still live.
		$out['state'] = time() < $timestamp ? 'upcoming' : 'expired';
		$out['expired'] = 'expired' === $out['state'];
		return $out;
	}

	private static function public_demo( $product_or_id ): array {
		$product = $product_or_id instanceof WC_Product ? $product_or_id : wc_get_product( absint( $product_or_id ) );
		if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() || ! $product->is_visible() || 'hidden' === $product->get_catalog_visibility() || 'yes' === $product->get_meta( '_krev_listlab_archived', true, 'edit' ) ) { return array(); }
		$demo = self::state( $product );
		return 'upcoming' === $demo['state'] ? $demo : array();
	}

	public static function render_card( $product_or_id ): string {
		$product = $product_or_id instanceof WC_Product ? $product_or_id : wc_get_product( absint( $product_or_id ) );
		if ( ! $product instanceof WC_Product ) { return ''; }
		$demo = self::public_demo( $product );
		if ( ! $demo ) { return ''; }
		return '<a class="kr-whatnot-demo kr-whatnot-demo--card" data-whatnot-start="' . esc_attr( $demo['starts_at_utc'] ) . '" href="' . esc_url( $product->get_permalink() ) . '" aria-label="' . esc_attr( sprintf( 'View %s and its upcoming Whatnot demo, scheduled %s', $product->get_name(), $demo['display_card'] ) ) . '">' . self::icon() . '<span class="kr-whatnot-demo__copy"><span class="kr-whatnot-demo__eyebrow">Upcoming Whatnot demo</span><span class="kr-whatnot-demo__time">' . esc_html( $demo['display_card'] ) . '</span></span></a>';
	}

	public static function render_product( $product_or_id ): string {
		$product = $product_or_id instanceof WC_Product ? $product_or_id : wc_get_product( absint( $product_or_id ) );
		if ( ! $product instanceof WC_Product ) { return ''; }
		$demo = self::public_demo( $product );
		if ( ! $demo ) { return ''; }
		return '<aside class="kr-whatnot-demo kr-whatnot-demo--product" data-whatnot-start="' . esc_attr( $demo['starts_at_utc'] ) . '" aria-label="Upcoming Whatnot demonstration"><div class="kr-whatnot-demo__identity">' . self::icon() . '<div><p class="kr-whatnot-demo__heading">Upcoming live demo</p><p class="kr-whatnot-demo__message">Watch this item live on Whatnot</p></div></div><p class="kr-whatnot-demo__schedule">Starts ' . esc_html( $demo['display_product'] ) . '</p><a class="kr-whatnot-demo__cta" href="' . esc_url( $demo['url'] ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( sprintf( 'Watch the live demonstration of %s on Whatnot (opens in a new tab)', $product->get_name() ) ) . '">' . self::icon() . 'Watch live demo on Whatnot ↗</a></aside>';
	}

	private static function icon(): string {
		return '<img class="kr-whatnot-demo__icon" src="' . esc_url( KREV_LISTLAB_URL . 'assets/images/whatnot-mark.svg' ) . '" width="24" height="24" alt="" aria-hidden="true" decoding="async">';
	}

	public static function loop_card(): void {
		global $product;
		if ( $product instanceof WC_Product ) { echo self::render_card( $product ); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper escapes dynamic output.
	}

	public static function knife_card( $product ): void {
		if ( $product instanceof WC_Product ) { echo self::render_card( $product ); } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper escapes dynamic output.
	}

	public static function block_price_card( string $content, array $block, $instance = null ): string {
		if ( is_admin() || is_product() || 'woocommerce/product-price' !== ( $block['blockName'] ?? '' ) ) { return $content; }
		$id = $instance instanceof WP_Block ? absint( $instance->context['postId'] ?? 0 ) : absint( $block['context']['postId'] ?? 0 );
		if ( ! $id ) { return $content; }
		return $content . self::render_card( $id );
	}

	public static function single_product(): void {
		if ( ! is_product() ) { return; }
		global $product;
		if ( ! $product instanceof WC_Product || isset( self::$product_rendered[ $product->get_id() ] ) ) { return; }
		$markup = self::render_product( $product );
		if ( ! $markup ) { return; }
		self::$product_rendered[ $product->get_id() ] = true;
		echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- helper escapes dynamic output.
	}
}

function kniferevive_get_whatnot_demo( $product_id ): array { return KREV_ListLab_Whatnot_Demo::state( $product_id ); }
function kniferevive_render_whatnot_demo_card( $product_id ): string { return KREV_ListLab_Whatnot_Demo::render_card( $product_id ); }
function kniferevive_render_whatnot_demo_product( $product_id ): string { return KREV_ListLab_Whatnot_Demo::render_product( $product_id ); }
