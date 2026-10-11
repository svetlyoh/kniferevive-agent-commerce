<?php
define( 'ABSPATH', __DIR__ );
function get_terms( $args ) { return array( (object) array( 'term_id' => 123, 'parent' => 12, 'name' => 'Video &gt; Players &amp; Recorders' ) ); }
function is_wp_error( $value ) { return false; }
class KREV_ListLab_Categories { public static function is_child( $id ) { return true; } }
require dirname( __DIR__ ) . '/includes/class-krev-listlab-product-reader.php';
$result = KREV_ListLab_Product_Reader::categories();
if ( $result[0]['name'] !== 'Video > Players & Recorders' || $result[0]['id'] !== 123 || ! $result[0]['is_tech_child'] ) throw new Exception( 'Category label decoding failed or changed category identity.' );
echo "Category label decoding and category identity assertions passed\n";
