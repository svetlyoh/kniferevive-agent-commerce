<?php
defined( 'ABSPATH' ) || exit;

/** Parent/child selection shared by the reader, writer and editor schema. */
final class KREV_ListLab_Categories {
	public static function root_id() { return class_exists( 'KREV_PA_Tech_Categories' ) ? KREV_PA_Tech_Categories::root_id() : 0; }
	public static function is_child( $id ) { return class_exists( 'KREV_PA_Tech_Categories' ) && KREV_PA_Tech_Categories::is_child( $id ); }
	public static function selection( array $ids ) {
		$children = array_values( array_filter( array_map( 'intval', $ids ), array( __CLASS__, 'is_child' ) ) );
		if ( $children ) {
			usort( $children, static function( $a, $b ) { return count( get_ancestors( $b, 'product_cat', 'taxonomy' ) ) <=> count( get_ancestors( $a, 'product_cat', 'taxonomy' ) ); } );
			return array( 'category_id' => self::root_id(), 'subcategory_id' => $children[0] );
		}
		return array( 'category_id' => $ids ? (int) reset( $ids ) : 0, 'subcategory_id' => 0 );
	}

	public static function validate( $parent, $child ) {
		if ( $child && ( (int) $parent !== self::root_id() || ! self::is_child( $child ) ) ) return new WP_Error( 'listlab_subcategory', 'Choose a Tech subcategory belonging to Tech.', array( 'status' => 422, 'errors' => array( 'subcategory_id' => 'Choose a valid Tech subcategory.' ) ) );
		return true;
	}

	public static function schema( $category ) {
		$selection = self::selection( array( (int) $category ) );
		$tech = self::root_id() && (int) $selection['category_id'] === self::root_id();
		$subcategories = $tech ? array_values( array_filter( KREV_ListLab_Product_Reader::categories(), static function( $row ) { return ! empty( $row['is_tech_child'] ); } ) ) : array();
		return array( 'tech_category_id' => self::root_id(), 'tech_subcategories' => $subcategories );
	}
}
