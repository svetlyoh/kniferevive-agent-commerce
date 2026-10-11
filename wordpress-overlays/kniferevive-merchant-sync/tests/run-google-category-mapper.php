<?php
/** Focused regression checks for Google category defaults and precedence. */
define( 'ABSPATH', __DIR__ . '/' );
function get_term_by( $field, $value, $taxonomy ) { return false; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( (string) $value ); }
function term_exists( $term, $taxonomy ) { return true; }
class WP_Error {
	private $code;
	public function __construct( $code = '', $message = '' ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}
require dirname( __DIR__ ) . '/includes/class-krev-google-category-mapper.php';

$assert = static function( $condition, $message ) { if ( ! $condition ) { fwrite( STDERR, $message . PHP_EOL ); exit( 1 ); } };
$defaults = KREV_Google_Category_Mapper::default_mappings();
$ids = array_column( $defaults, 'google_category_id' );
$assert( 1 === count( array_keys( $ids, '665', true ) ) && in_array( '1529', $ids, true ) && in_array( '543606', $ids, true ) && in_array( '222', $ids, true ) && ! in_array( '325', $ids, true ) && in_array( '500044', $ids, true ), 'Tech uses broad Electronics while desktop specificity belongs to the child.' );

$parent = array( 'google_category_id' => '325', 'exact' => 0, 'depth' => 1, 'priority' => 1 );
$child = array( 'google_category_id' => '342', 'exact' => 1, 'depth' => 2, 'priority' => 2 );
$assert( '342' === KREV_Google_Category_Mapper::choose_candidate( array( $parent, $child ) )['google_category_id'], 'Exact child mapping wins over inherited parent mapping.' );
$first = array( 'google_category_id' => '100', 'exact' => 1, 'depth' => 2, 'priority' => 1 );
$second = array( 'google_category_id' => '200', 'exact' => 1, 'depth' => 2, 'priority' => 2 );
$assert( '100' === KREV_Google_Category_Mapper::choose_candidate( array( $second, $first ) )['google_category_id'], 'First row wins equal-specificity conflicts.' );
$assert( null === KREV_Google_Category_Mapper::choose_candidate( array() ), 'Unmapped products resolve to no Google category.' );
$saved = KREV_Google_Category_Mapper::save( array(
	array( 'enabled' => '', 'product_cat_term_id' => 17, 'google_category_id' => '', 'google_category_path' => '' ),
	array( 'enabled' => '1', 'product_cat_term_id' => 18, 'google_category_id' => '1529', 'google_category_path' => 'Food > Spices' ),
) );
$assert( ! ( $saved instanceof WP_Error ) && 1 === count( $saved ) && 18 === $saved[0]['product_cat_term_id'], 'Blank unchecked category-grid rows are ignored while mapped rows are preserved.' );
echo "Google category mapping checks passed.\n";
