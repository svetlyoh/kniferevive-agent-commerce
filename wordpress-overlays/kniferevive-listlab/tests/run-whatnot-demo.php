<?php
/** Lightweight no-WordPress regression tests for Whatnot demo validation and expiry. */
define( 'ABSPATH', __DIR__ . '/' );
define( 'KREV_LISTLAB_URL', 'https://kniferevive.com/wp-content/plugins/kniferevive-listlab/' );
class WP_Error {
	private string $message;
	public function __construct( $code = '', $message = '', $data = null ) { $this->message = $message; }
	public function get_error_message(): string { return $this->message; }
}
class WC_Product {
	private array $meta = array();
	public function update_meta_data( $key, $value ): void { $this->meta[ $key ] = $value; }
	public function get_meta( $key, $single = true, $context = 'view' ) { return $this->meta[ $key ] ?? ''; }
	public function get_id(): int { return 1; }
	public function get_status(): string { return 'publish'; }
	public function is_visible(): bool { return true; }
	public function get_catalog_visibility(): string { return 'visible'; }
	public function get_permalink(): string { return 'https://kniferevive.com/product/test/'; }
	public function get_name(): string { return 'Test item'; }
}
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function wp_unslash( $value ) { return $value; }
function wp_parse_url( $url ) { return parse_url( $url ); }
function esc_url_raw( $url, $protocols = null ) { return $url; }
function esc_url( $url ) { return htmlspecialchars( $url, ENT_QUOTES ); }
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function esc_attr( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
function wp_date( $format, $timestamp, $timezone ) { return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone )->format( $format ); }
function apply_filters( $tag, $value ) { return $value; }
function wp_safe_remote_head( $url, $args ) {
	$target = str_contains( $url, 'Q6GxcHu1' ) ? 'https://www.whatnot.com/user/svetlyoh' : 'https://www.whatnot.com/live/9590de6c-8f58-46c1-b495-3e222b464c7b?app=web';
	return array( 'response' => array( 'code' => 307 ), 'headers' => array( 'location' => $target ) );
}
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code']; }
function wp_remote_retrieve_header( $response, $header ) { return $response['headers'][ $header ] ?? ''; }
require dirname( __DIR__ ) . '/includes/class-krev-listlab-whatnot-demo.php';
$assert = static function ( $truth, string $message ): void { if ( ! $truth ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$direct = 'https://www.whatnot.com/live/9590de6c-8f58-46c1-b495-3e222b464c7b';
$assert( ! is_wp_error( KREV_ListLab_Whatnot_Demo::validate_url( $direct ) ), 'direct show URL accepted' );
foreach ( array( 'http://www.whatnot.com/live/123', 'https://whatnot.com.evil.example/live/123', 'https://whatnot.com@evil.example/live/123', 'https://www.whatnot.com/invite/seller', 'https://www.whatnot.com/user/svetlyoh', 'https://www.whatnot.com/s/Q6GxcHu1' ) as $bad ) {
	$assert( is_wp_error( KREV_ListLab_Whatnot_Demo::validate_url( $bad ) ), 'invalid/non-direct URL rejected' );
}
$product = new WC_Product();
$future = ( new DateTimeImmutable( '+1 day', new DateTimeZone( 'America/Los_Angeles' ) ) );
$saved = KREV_ListLab_Whatnot_Demo::apply( $product, array( 'enabled' => true, 'url' => $direct, 'date' => $future->format( 'Y-m-d' ), 'time' => $future->format( 'H:i' ), 'timezone' => 'America/Los_Angeles' ) );
$assert( ! is_wp_error( $saved ), 'future event saved' );
$shared = KREV_ListLab_Whatnot_Demo::apply( $product, array( 'enabled' => true, 'url' => 'https://www.whatnot.com/s/AfDNmSXy', 'date' => $future->format( 'Y-m-d' ), 'time' => $future->format( 'H:i' ), 'timezone' => 'America/Los_Angeles' ) );
$assert( ! is_wp_error( $shared ) && $direct === $product->get_meta( KREV_ListLab_Whatnot_Demo::URL ), 'show-share link resolves to direct show URL' );
$profile_share = KREV_ListLab_Whatnot_Demo::apply( $product, array( 'enabled' => true, 'url' => 'https://www.whatnot.com/s/Q6GxcHu1', 'date' => $future->format( 'Y-m-d' ), 'time' => $future->format( 'H:i' ), 'timezone' => 'America/Los_Angeles' ) );
$assert( is_wp_error( $profile_share ), 'profile-share link is rejected' );
$assert( 'upcoming' === KREV_ListLab_Whatnot_Demo::state( $product )['state'], 'future event upcoming' );
$assert( str_contains( KREV_ListLab_Whatnot_Demo::render_card( $product ), 'Upcoming Whatnot demo' ) && str_contains( KREV_ListLab_Whatnot_Demo::render_card( $product ), 'whatnot-mark.svg' ), 'card includes local official icon and date' );
$assert( str_contains( KREV_ListLab_Whatnot_Demo::render_product( $product ), $direct ), 'product callout links to show' );
$product->update_meta_data( KREV_ListLab_Whatnot_Demo::START, gmdate( 'Y-m-d\TH:i:s\Z', time() - 60 ) );
$assert( 'expired' === KREV_ListLab_Whatnot_Demo::state( $product )['state'], 'started event expired' );
$assert( '' === KREV_ListLab_Whatnot_Demo::render_card( $product ), 'no past card' );
$assert( '' === KREV_ListLab_Whatnot_Demo::render_product( $product ), 'no past product callout' );
KREV_ListLab_Whatnot_Demo::apply( $product, array( 'enabled' => false ) );
$assert( '' === KREV_ListLab_Whatnot_Demo::render_card( $product ), 'disabled event hidden' );
echo "Whatnot demo validation and expiry checks passed.\n";
