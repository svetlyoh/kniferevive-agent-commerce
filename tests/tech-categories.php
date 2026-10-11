<?php
/** Actual WooCommerce category, editor and feed behavior in the fenced test DB. */
ob_start();
set_exception_handler( static function( Throwable $e ) { fwrite( STDERR, 'FAIL: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine() . "\n" ); exit( 1 ); } );
require __DIR__ . '/sandbox-bootstrap.php';
set_exception_handler( static function( Throwable $e ) { fwrite( STDERR, 'FAIL: ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine() . "\n" ); exit( 1 ); } );
if ( DB_NAME !== 'krev_agent_sandbox' || DB_HOST !== KREV_TEST_DB_HOST || $wpdb->prefix !== 'krev_sandbox_' ) throw new RuntimeException( 'Sandbox fence failed.' );
require_once ABSPATH . 'wp-content/plugins/kniferevive-product-attributes/kniferevive-product-attributes.php';
require_once ABSPATH . 'wp-content/plugins/kniferevive-listlab/kniferevive-listlab.php';
require_once ABSPATH . 'wp-content/plugins/kniferevive-merchant-sync/includes/class-krev-google-category-mapper.php';
require_once ABSPATH . 'wp-content/plugins/kniferevive-merchant-sync/includes/class-krev-product-mapper.php';
require_once ABSPATH . 'wp-content/plugins/kniferevive-merchant-sync/includes/class-krev-merchant-config.php';
require_once ABSPATH . 'wp-content/plugins/kniferevive-merchant-sync/includes/class-krev-logger.php';
wp_set_current_user( 1 );
$passed = 0;
function techCheck( $ok, $label ) { global $passed; if ( ! $ok ) throw new RuntimeException( $label ); $passed++; echo "PASS: $label\n"; }
KREV_PA_Setup::ensure_schema();
if ( ! taxonomy_exists( 'kr_return_policy' ) ) register_taxonomy( 'kr_return_policy', 'product' );
if ( ! taxonomy_exists( 'pa_condition' ) ) register_taxonomy( 'pa_condition', 'product' );
// Only synthetic terms in the isolated database are used or created.
$root = get_term_by( 'slug', 'technology', 'product_cat' );
if ( ! $root ) $root = get_term( wp_insert_term( 'Synthetic Tech', 'product_cat', array( 'slug' => 'technology' ) )['term_id'], 'product_cat' );
$root_id = (int) $root->term_id;
update_option( KREV_PA_Tech_Categories::PROGRESS, 0, false );
$total_before = wp_count_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
do { $report = KREV_PA_Tech_Categories::import_batch(); techCheck( ! is_wp_error( $report ), 'bounded native taxonomy import batch succeeds' ); } while ( $report['completed'] < $report['total'] );
techCheck( 421 === $report['total'], '417 official Electronics categories plus four merchant aliases are imported' );
$stream = get_term_by( 'slug', 'tech-google-5276', 'product_cat' );
$desktop = get_term_by( 'slug', 'tech-google-325', 'product_cat' );
techCheck( (int) $stream->parent === $root_id && (int) $desktop->parent === $root_id, 'streaming devices and desktops are distinct native Tech children' );
$count = wp_count_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
update_option( KREV_PA_Tech_Categories::PROGRESS, 0, false );
do { $report = KREV_PA_Tech_Categories::import_batch(); if ( is_wp_error( $report ) ) throw new RuntimeException( $report->get_error_message() ); } while ( $report['completed'] < $report['total'] );
techCheck( $count === wp_count_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ), 'repeated import creates no duplicate categories' );
$selection = KREV_ListLab_Categories::selection( array( $stream->term_id ) );
techCheck( $selection === array( 'category_id' => $root_id, 'subcategory_id' => (int) $stream->term_id ), 'legacy child-only categories reopen as parent and child' );
techCheck( ! is_wp_error( KREV_ListLab_Categories::validate( $root_id, $stream->term_id ) ), 'correct parent and child are accepted' );
techCheck( is_wp_error( KREV_ListLab_Categories::validate( $desktop->term_id, $stream->term_id ) ), 'mismatched parent is rejected' );
techCheck( is_wp_error( KREV_ListLab_Categories::validate( $root_id, 999999999 ) ), 'nonexistent subcategory is rejected' );
$schema = ( new KREV_ListLab_REST() )->schema( new WP_REST_Request() );
techCheck( empty( $schema['tech_subcategories'] ), 'Tech dropdown is absent for unselected/non-Tech category' );
$r = new WP_REST_Request(); $r->set_param( 'category_id', $stream->term_id ); $schema = ( new KREV_ListLab_REST() )->schema( $r );
techCheck( $schema['is_tech'] && count( $schema['tech_subcategories'] ) >= 417, 'child category inherits Tech specifications and subcategory schema' );
$policy = term_exists( 'Synthetic Tech Returns', 'kr_return_policy' );
if ( ! $policy ) $policy = wp_insert_term( 'Synthetic Tech Returns', 'kr_return_policy' );
update_term_meta( $policy['term_id'], '_kr_allowed_categories', array( $root_id ) );
techCheck( KREV_ListLab_Policies::valid_return_policy( $policy['term_id'], $stream->term_id ), 'parent-scoped return policy remains valid for child' );
$image = wp_insert_post( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'image/jpeg', 'post_title' => 'Synthetic Tech test image', 'post_author' => 1 ) );
$shipping = wp_insert_term( 'Synthetic Tech Shipping ' . wp_generate_uuid4(), 'product_shipping_class' )['term_id'];
$condition = get_term_by( 'name', 'Like New', 'pa_condition' );
if ( ! $condition ) $condition = get_term( wp_insert_term( 'Like New', 'pa_condition' )['term_id'], 'pa_condition' );
$input = array( 'title' => 'Synthetic streaming device', 'description' => 'Synthetic native category test, not a real listing.', 'category_id' => $root_id, 'subcategory_id' => $stream->term_id, 'quantity' => 1, 'regular_price' => '20', 'shipping_policy_id' => $shipping, 'return_policy_id' => $policy['term_id'], 'featured_image_id' => $image, 'attributes' => array( 'pa_condition' => array( $condition->term_id ) ), 'publish' => true );
$result = KREV_ListLab_Product_Writer::save( $input );
if ( is_wp_error( $result ) ) throw new RuntimeException( $result->get_error_message() . wp_json_encode( $result->get_error_data() ) );
techCheck( $result['category_id'] === $root_id && $result['subcategory_id'] === (int) $stream->term_id, 'native product writer and reader retain both selections' );
$p = wc_get_product( $result['id'] );
techCheck( in_array( $root_id, $p->get_category_ids(), true ) && in_array( (int) $stream->term_id, $p->get_category_ids(), true ), 'product is assigned both actual native terms' );
techCheck( 'used' === KREV_PA_Condition_Resolver::resolve( $p )['merchant_condition'], 'Like New no longer blocks feed condition' );
update_option( KREV_Google_Category_Mapper::OPTION, array( array( 'enabled' => true, 'product_cat_term_id' => $root_id, 'google_category_id' => '325', 'google_category_path' => 'Electronics > Computers > Desktop Computers', 'include_descendants' => true ) ), false );
KREV_Google_Category_Mapper::init();
techCheck( '222' === KREV_Google_Category_Mapper::mappings()[0]['google_category_id'], 'old bundled parent desktop mapping is corrected to broad Electronics' );
$mapping = KREV_Google_Category_Mapper::resolve_for_product( $p );
techCheck( '5276' === $mapping['id'] && str_ends_with( $mapping['path'], 'Streaming & Home Media Players' ), 'Chromecast feed resolves exact official streaming-media category' );
$updated = KREV_ListLab_Product_Writer::save( $input + array(), $p->get_id() );
techCheck( ! is_wp_error( $updated ) && $updated['subcategory_id'] === (int) $stream->term_id, 'reopening and saving preserves the child' );
$bad = $input; $bad['subcategory_id'] = 999999999;
techCheck( is_wp_error( KREV_ListLab_Product_Writer::save( $bad, $p->get_id() ) ), 'forged child is rejected by the actual writer before product mutation' );
$legacy = $input; $legacy['category_id'] = $desktop->term_id; unset( $legacy['subcategory_id'] );
$updated = KREV_ListLab_Product_Writer::save( $legacy, $p->get_id() );
techCheck( ! is_wp_error( $updated ) && $updated['category_id'] === $root_id && $updated['subcategory_id'] === (int) $desktop->term_id, 'older importer sending only child category remains compatible' );
techCheck( '325' === KREV_Google_Category_Mapper::resolve_for_product( wc_get_product( $p->get_id() ) )['id'], 'actual desktop selection sends Desktop Computers, not streaming-media category' );
$cleared = $input; $cleared['subcategory_id'] = 0;
$updated = KREV_ListLab_Product_Writer::save( $cleared, $p->get_id() );
techCheck( ! is_wp_error( $updated ) && 0 === $updated['subcategory_id'], 'clearing child removes stale category assignment' );
techCheck( '222' === KREV_Google_Category_Mapper::resolve_for_product( wc_get_product( $p->get_id() ) )['id'], 'unclassified Tech uses Electronics fallback' );

// Four-group expansion: actual term creation, schemas, native persistence and feed IDs.
if ( ! get_term_by( 'slug', 'art', 'product_cat' ) ) wp_insert_term( 'Synthetic Art', 'product_cat', array( 'slug' => 'art' ) );
foreach ( array( 'world-coins' => 3, 'art' => 4, 'home-garden' => 1034 ) as $group => $expected ) {
    update_option( KREV_PA_Tech_Categories::progress_key( $group ), 0, false );
    do { $group_report = KREV_PA_Tech_Categories::import_batch( 100, $group ); if ( is_wp_error( $group_report ) ) throw new RuntimeException( $group_report->get_error_message() ); } while ( $group_report['completed'] < $group_report['total'] );
    techCheck( $group_report['total'] === $expected, 'complete official branch imported: ' . $group );
    $group_root = KREV_PA_Tech_Categories::root_id( $group );
    $req = new WP_REST_Request(); $req->set_param( 'category_id', $group_root ); $group_schema = ( new KREV_ListLab_REST() )->schema( $req );
    techCheck( count( $group_schema['subcategories'] ) === $expected && empty( $group_schema['tech_subcategories'] ), 'parent shows its own children only: ' . $group );
}
foreach ( array( 'tech-iphones' => '267', 'tech-mobile-devices' => '267', 'tech-smart-watches' => '201', 'tech-apple-watches' => '201' ) as $slug => $google_id ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    techCheck( $term && KREV_PA_Tech_Categories::mapping( $term->term_id )['id'] === $google_id && (int) $term->parent === $root_id, 'Tech alias uses official Google mapping: ' . $slug );
}
foreach ( array( 'world-coins-google-543607' => '543607', 'art-google-11' => '11', 'home-garden-google-6792' => '6792' ) as $slug => $google_id ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' ); $parent = (int) $term->parent;
    $group_input = $input; $group_input['category_id'] = $parent; $group_input['subcategory_id'] = (int) $term->term_id;
    $group_input['attributes'] = array(); $group_input['return_policy_id'] = 0; $group_input['publish'] = false;
    $saved = KREV_ListLab_Product_Writer::save( $group_input );
    techCheck( ! is_wp_error( $saved ) && $saved['category_id'] === $parent && $saved['subcategory_id'] === (int) $term->term_id, 'native parent/child persistence: ' . $slug );
    techCheck( KREV_Google_Category_Mapper::resolve_for_product( wc_get_product( $saved['id'] ) )['id'] === $google_id, 'exact Google feed category overrides broad parent: ' . $slug );
    $legacy_group = $group_input; $legacy_group['category_id'] = (int) $term->term_id; unset( $legacy_group['subcategory_id'] );
    $legacy_saved = KREV_ListLab_Product_Writer::save( $legacy_group, $saved['id'] );
    techCheck( ! is_wp_error( $legacy_saved ) && $legacy_saved['category_id'] === $parent, 'child-only imports reopen in the correct group: ' . $slug );
    $bad_group = $group_input; $bad_group['category_id'] = $root_id;
    techCheck( is_wp_error( KREV_ListLab_Product_Writer::save( $bad_group, $saved['id'] ) ), 'cross-group child rejected before mutation: ' . $slug );
}
$home = KREV_PA_Tech_Categories::root_id( 'home-garden' );
techCheck( KREV_PA_Tech_Categories::mapping( $home ) === array( 'id' => '536', 'path' => 'Home & Garden' ), 'new Home & Garden parent has a valid broad fallback' );
$before_retry = wp_count_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
update_option( KREV_PA_Tech_Categories::progress_key( 'art' ), 0, false ); KREV_PA_Tech_Categories::import_batch( 100, 'art' );
techCheck( $before_retry === wp_count_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) ), 'retrying another category group creates no duplicate terms' );
echo "$passed native Tech taxonomy assertions passed.\n";
