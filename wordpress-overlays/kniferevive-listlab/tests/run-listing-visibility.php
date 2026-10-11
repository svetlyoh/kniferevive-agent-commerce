<?php
/** Isolated regression tests: execute the actual REST and editor publish handlers. */
define( 'ABSPATH', __DIR__ . '/' );
class WP_Error {}
class WP_REST_Request extends ArrayObject {}
class KREV_ListLab_Dokan_Adapter {
	public static $publish_status = 'publish';
	public static function owns_product( $id ) { return true; }
	public static function can_sell() { return true; }
	public static function seller_id() { return 1; }
	public static function publish_status() { return self::$publish_status; }
}
class KREV_ListLab_Attributes {
	public static $valid = true;
	public static function validate_product_required_attributes( $p ) { return self::$valid ? true : new WP_Error(); }
	public static function validate( ...$args ) { return true; }
	public static function save( ...$args ) { return true; }
}
class KREV_ListLab_Product_Reader { public static function dto( $p ) { return $p; } }
class KREV_ListLab_Policies { public static function valid_return_policy( ...$args ) { return true; } }
class KREV_ListLab_Image_Handler { public static function authorized_image( $id ) { return true; } }
class KREV_ListLab_Video_Handler { public static function normalize_url( $url ) { return $url; } }
class VisibilityFixture {
	public $status = 'publish';
	public $visibility = 'hidden';
	public $meta = array();
	public $saved_visibility;
	public function get_status() { return $this->status; }
	public function set_status( $status ) { $this->status = $status; }
	public function set_catalog_visibility( $visibility ) { $this->visibility = $visibility; }
	public function update_meta_data( $key, $value ) { $this->meta[$key] = $value; }
	public function delete_meta_data( $key ) { unset( $this->meta[$key] ); }
	public function is_type( $type ) { return 'simple' === $type; }
	public function save() { $this->saved_visibility = $this->visibility; return 1; }
	public function __call( $method, $args ) { if ( str_starts_with( $method, 'set_' ) ) return; throw new Exception( $method ); }
}
function wc_get_product( $id ) { return $GLOBALS['fixture']; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_text_field( $value ) { return (string) $value; }
function sanitize_textarea_field( $value ) { return (string) $value; }
function wp_kses_post( $value ) { return $value; }
function wc_format_decimal( $value ) { return $value; }
function wc_stock_amount( $value ) { return (int) $value; }
function wc_clean( $value ) { return $value; }
function get_term( ...$args ) { return (object) array( 'term_id' => 1 ); }
function taxonomy_exists( $taxonomy ) { return false; }
function wc_delete_product_transients( $id ) {}
require dirname( __DIR__ ) . '/includes/class-krev-listlab-rest.php';
require dirname( __DIR__ ) . '/includes/class-krev-listlab-categories.php';
require dirname( __DIR__ ) . '/includes/class-krev-listlab-product-writer.php';
$checks = 0;
function check( $condition, $label ) {
	if ( ! $condition ) { fwrite( STDERR, "FAIL: $label\n" ); exit( 1 ); }
	$GLOBALS['checks']++;
}
$rest = new KREV_ListLab_REST();
$request = new WP_REST_Request( array( 'id' => 1 ) );
$data = array( 'publish' => true, 'title' => 'Knife', 'description' => 'Knife description', 'category_id' => 1, 'featured_image_id' => 1, 'shipping_policy_id' => 1, 'quantity' => 1 );
foreach ( array( 'hidden', 'search', 'catalog', 'visible' ) as $visibility ) {
	foreach ( array( 'restore', 'publish', 'editor' ) as $action ) {
		$fixture = new VisibilityFixture();
		$fixture->visibility = $visibility;
		$rest->archive( $request );
		check( 'draft' === $fixture->status && 'yes' === $fixture->meta['_krev_listlab_archived'], 'hide keeps listing unpublished' );
		if ( 'editor' === $action ) $result = KREV_ListLab_Product_Writer::save( $data, 1 );
		else $result = $rest->$action( $request );
		check( ! is_wp_error( $result ) && 'publish' === $fixture->status, "$action publishes $visibility" );
		check( 'visible' === $fixture->saved_visibility && 'no' === $fixture->meta['_krev_listlab_archived'], "$action saves shop and search visibility from $visibility" );
	}
}
$fixture = new VisibilityFixture();
KREV_ListLab_Product_Writer::save( array_merge( $data, array( 'publish' => false ) ), 1 );
check( 'draft' === $fixture->status && 'hidden' === $fixture->saved_visibility, 'save draft preserves visibility' );
$fixture = new VisibilityFixture();
$fixture->status = 'draft';
KREV_ListLab_Attributes::$valid = false;
check( is_wp_error( $rest->publish( $request ) ) && 'hidden' === $fixture->visibility && 'draft' === $fixture->status, 'failed validation does not unhide' );
KREV_ListLab_Attributes::$valid = true;
KREV_ListLab_Dokan_Adapter::$publish_status = 'pending';
foreach ( array( 'publish', 'editor' ) as $action ) {
	$fixture = new VisibilityFixture();
	if ( 'editor' === $action ) KREV_ListLab_Product_Writer::save( $data, 1 );
	else $rest->publish( $request );
	check( 'pending' === $fixture->status && 'visible' === $fixture->saved_visibility, "$action retains moderation status" );
}
echo "PASS: $checks listing visibility checks\n";
