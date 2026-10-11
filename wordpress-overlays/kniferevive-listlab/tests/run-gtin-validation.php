<?php
/** Lightweight no-WordPress regression check for ListLab's GTIN normalization. */
define( 'ABSPATH', __DIR__ . '/' );
class WP_Error {
	public function __construct( $code = '', $message = '', $data = null ) {}
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
require dirname( __DIR__ ) . '/includes/class-krev-listlab-product-writer.php';

$method = new ReflectionMethod( 'KREV_ListLab_Product_Writer', 'normalize_gtin' );
$method->setAccessible( true );
$cases = array(
	'96385074'        => '96385074',
	'036000291452'    => '036000291452',
	'4 006381-333931' => '4006381333931',
	'00012345600012'  => '00012345600012',
	''                => '',
);
foreach ( $cases as $input => $expected ) {
	$result = $method->invoke( null, $input );
	if ( is_wp_error( $result ) || $result !== $expected ) { fwrite( STDERR, "Expected valid GTIN: {$input}\n" ); exit( 1 ); }
}
foreach ( array( '96385075', '4006381333932', '0123abc8905', '1234567890' ) as $input ) {
	if ( ! is_wp_error( $method->invoke( null, $input ) ) ) { fwrite( STDERR, "Expected invalid GTIN: {$input}\n" ); exit( 1 ); }
}
echo "GTIN validation checks passed.\n";
