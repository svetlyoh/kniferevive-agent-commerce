<?php
/** Lightweight no-WordPress checks for the WCAG-aware product-page countdown. */
define( 'ABSPATH', __DIR__ . '/' );
class WC_DateTime {
	private int $timestamp;
	public function __construct( int $timestamp ) { $this->timestamp = $timestamp; }
	public function getTimestamp(): int { return $this->timestamp; }
}
class WC_Product {
	public ?WC_DateTime $from = null;
	public ?WC_DateTime $to = null;
	public function get_date_on_sale_from( $context = '' ) { return $this->from; }
	public function get_date_on_sale_to( $context = '' ) { return $this->to; }
	public function get_regular_price( $context = '' ) { return '55'; }
	public function get_sale_price( $context = '' ) { return '49'; }
	public function is_on_sale(): bool { return $this->to && $this->to->getTimestamp() > time() && ( ! $this->from || $this->from->getTimestamp() <= time() ); }
}
function wp_timezone(): DateTimeZone { return new DateTimeZone( 'America/Los_Angeles' ); }
function wp_date( $format, $timestamp, $timezone ): string { return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( $timezone )->format( $format ); }
function esc_attr( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES ); }
function esc_html( $value ): string { return htmlspecialchars( (string) $value, ENT_QUOTES ); }
function esc_html__( $value, $domain ): string { return $value; }
require dirname( __DIR__ ) . '/includes/class-krev-listlab-timed-offers.php';
$assert = static function ( bool $condition, string $message ): void { if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); } };
$product = new WC_Product();
$product->to = new WC_DateTime( time() + 3600 );
$html = KREV_ListLab_Timed_Offers::render( $product, 'full' );
$assert( str_contains( $html, 'Hide countdown' ) && str_contains( $html, 'data-krev-timed-offer-toggle' ), 'keyboard-operable hide control rendered' );
$assert( str_contains( $html, 'aria-hidden="true"' ) && ! str_contains( $html, 'aria-live' ), 'seconds omitted from screen-reader announcements' );
$assert( str_contains( $html, 'krev-timed-offer__deadline' ) && str_contains( $html, 'Ends ' ), 'absolute deadline remains visible' );
$product->to = new WC_DateTime( time() - 1 );
$assert( '' === KREV_ListLab_Timed_Offers::render( $product, 'full' ), 'expired offer has no countdown' );
echo "Timed-offer rendering checks passed.\n";
