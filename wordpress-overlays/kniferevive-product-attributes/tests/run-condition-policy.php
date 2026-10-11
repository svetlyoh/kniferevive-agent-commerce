<?php

define( 'ABSPATH', __DIR__ );
$GLOBALS['test_meta'] = array(); $GLOBALS['test_tags'] = array(); $GLOBALS['test_knife'] = true; $GLOBALS['test_groups'] = array();
class WP_Error { public function __construct( public $code = '', public $message = '' ) {} }
class KREV_PA_Config { public static function groups_for_product( $id ) { return $GLOBALS['test_groups'][ $id ] ?? ( $GLOBALS['test_knife'] ? array( 'knives' ) : array( 'art' ) ); } }
class WC_Product {
	public function __construct( private $id, private $grade = '' ) {}
	public function get_id() { return $this->id; }
	public function get_attribute( $name ) { return 'pa_condition' === $name ? $this->grade : ''; }
	public function get_meta( $key ) { return $GLOBALS['test_meta'][ $this->id ][ $key ] ?? ''; }
	public function update_meta_data( $key, $value ) { $GLOBALS['test_meta'][ $this->id ][ $key ] = $value; }
	public function delete_meta_data( $key ) { unset( $GLOBALS['test_meta'][ $this->id ][ $key ] ); }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function metadata_exists( $type, $id, $key ) { return array_key_exists( $key, $GLOBALS['test_meta'][ $id ] ?? array() ); }
function get_post_meta( $id, $key ) { return $GLOBALS['test_meta'][ $id ][ $key ] ?? ''; }
function has_term( $slug, $taxonomy, $id ) { return ! empty( $GLOBALS['test_tags'][ $id ][ $taxonomy ][ $slug ] ); }
function wp_set_object_terms( $id, $slug, $taxonomy ) { $GLOBALS['test_tags'][ $id ][ $taxonomy ][ $slug ] = true; }
function wp_remove_object_terms( $id, $slug, $taxonomy ) { unset( $GLOBALS['test_tags'][ $id ][ $taxonomy ][ $slug ] ); }
function sanitize_textarea_field( $value ) { return trim( (string) $value ); }

require dirname( __DIR__ ) . '/includes/class-krev-pa-condition-resolver.php';
$passed = 0; $failed = 0;
$assert = static function ( $condition, $message ) use ( &$passed, &$failed ) { if ( $condition ) { echo "PASS: $message\n"; $passed++; } else { echo "FAIL: $message\n"; $failed++; } };

$assert( false === KREV_PA_Condition_Resolver::normalize_boolean( 'false' ), 'string false normalizes to false' );
$assert( is_wp_error( KREV_PA_Condition_Resolver::normalize_boolean( 'yes' ) ), 'malformed boolean is rejected' );

$GLOBALS['test_tags'][1]['product_tag']['sharpened'] = true;
$used = KREV_PA_Condition_Resolver::resolve( new WC_Product( 1, 'Excellent' ) );
$assert( $used['sharpened'] && 'refurbished' === $used['merchant_condition'], 'Sharp-tagged knife with non-New condition resolves to refurbished' );
$assert( 'Refurbished' === $used['classification_label'] && str_ends_with( $used['schema_condition'], '/RefurbishedCondition' ), 'sharpened knife aligns classification and schema as refurbished' );

$GLOBALS['test_tags'][7]['product_tag']['sharpened'] = true;
$sharp_new = KREV_PA_Condition_Resolver::resolve( new WC_Product( 7, 'New' ) );
$assert( 'new' === $sharp_new['merchant_condition'], 'sharpened knife with explicit New condition remains new' );
$GLOBALS['test_tags'][8]['product_tag']['sharpened'] = true;
$sharp_missing = KREV_PA_Condition_Resolver::resolve( new WC_Product( 8, '' ) );
$assert( null === $sharp_missing['merchant_condition'] && $sharp_missing['review_required'], 'sharpened knife without an Item Condition remains unresolved' );

$GLOBALS['test_meta'][1][KREV_PA_Condition_Resolver::META_RESTORED] = 'yes';
$GLOBALS['test_meta'][1][KREV_PA_Condition_Resolver::META_LIKE_NEW] = 'yes';
$GLOBALS['test_meta'][1][KREV_PA_Condition_Resolver::META_WARRANTY] = '90-day edge and workmanship warranty';
$refurb = KREV_PA_Condition_Resolver::resolve( new WC_Product( 1, 'Excellent' ) );
$assert( 'refurbished' === $refurb['merchant_condition'] && str_ends_with( $refurb['schema_condition'], '/RefurbishedCondition' ), 'complete evidence aligns feed and schema as refurbished' );

$GLOBALS['test_meta'][2][KREV_PA_Condition_Resolver::META_SHARPENED] = 'yes';
$conflict = KREV_PA_Condition_Resolver::resolve( new WC_Product( 2, 'Good' ) );
$assert( $conflict['review_required'] && null === $conflict['merchant_condition'], 'unreliable tag/meta conflict is not guessed' );

$ordinary = KREV_PA_Condition_Resolver::resolve( new WC_Product( 3, 'Good' ) );
$assert( 'used' === $ordinary['merchant_condition'], 'ordinary used cosmetic grade maps to used' );
$new = KREV_PA_Condition_Resolver::resolve( new WC_Product( 4, 'New' ) );
$assert( 'new' === $new['merchant_condition'], 'explicit New maps to new' );

$GLOBALS['test_groups'][6] = array( 'tech' );
$tech_used = KREV_PA_Condition_Resolver::resolve( new WC_Product( 6, 'Used' ) );
$assert( 'used' === $tech_used['merchant_condition'], 'used tech without refurbishment evidence stays used' );
$assert( 'Pre-owned' === $tech_used['public_condition'], 'used tech uses Pre-owned customer-facing wording' );
$GLOBALS['test_meta'][6][KREV_PA_Condition_Resolver::META_RESTORED] = 'yes';
$GLOBALS['test_meta'][6][KREV_PA_Condition_Resolver::META_LIKE_NEW] = 'yes';
$GLOBALS['test_meta'][6][KREV_PA_Condition_Resolver::META_WARRANTY] = '90-day parts and labor warranty';
$tech_refurb = KREV_PA_Condition_Resolver::resolve( new WC_Product( 6, 'Used' ) );
$assert( 'refurbished' === $tech_refurb['merchant_condition'] && 'Refurbished' === $tech_refurb['public_condition'], 'used tech with complete evidence maps to refurbished' );

$GLOBALS['test_knife'] = false; $GLOBALS['test_meta'][5][KREV_PA_Condition_Resolver::META_SHARPENED] = 'yes'; $GLOBALS['test_tags'][5]['product_tag']['sharpened'] = true;
$inactive = KREV_PA_Condition_Resolver::resolve( new WC_Product( 5, 'Good' ) );
$assert( ! $inactive['sharpened'] && 'used' === $inactive['merchant_condition'], 'knife-only sharpening deactivates after category removal' );

$GLOBALS['test_groups'][7] = array( 'tech' );
$like_new = KREV_PA_Condition_Resolver::resolve( new WC_Product( 7, 'Like New' ) );
$assert( 'used' === $like_new['merchant_condition'] && ! $like_new['review_required'], 'Like New cosmetic tech grade resolves to Used without claiming New or refurbishment' );
echo "RESULT: $passed passed, $failed failed\n";
exit( $failed ? 1 : 0 );
