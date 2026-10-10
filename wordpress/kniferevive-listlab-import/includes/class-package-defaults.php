<?php
defined( 'ABSPATH' ) || exit;

final class KREV_Import_Package_Defaults {
    private static $requests = array();

    public static function boot() {
        add_filter( 'rest_request_before_callbacks', array( __CLASS__, 'before_request' ), 10, 3 );
        add_filter( 'rest_request_after_callbacks', array( __CLASS__, 'after_request' ), 10, 3 );
        add_action( 'woocommerce_before_product_object_save', array( __CLASS__, 'save' ), 10 );
    }

    public static function before_request( $response, $handler, $request ) {
        if ( in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) && preg_match( '#^/kniferevive/v1/listings(?:/\d+(?:/(?:publish|draft|quick-edit|duplicate|restore))?)?/?$#D', $request->get_route() ) ) self::$requests[ spl_object_id( $request ) ] = true;
        return $response;
    }

    public static function after_request( $response, $handler, $request ) {
        unset( self::$requests[ spl_object_id( $request ) ] );
        return $response;
    }

    public static function values() {
        $weight_unit = get_option( 'woocommerce_weight_unit', 'kg' );
        $dimension_unit = get_option( 'woocommerce_dimension_unit', 'cm' );
        return array(
            'weight' => wc_format_decimal( wc_get_weight( 15, $weight_unit, 'oz' ), 4, true ),
            'length' => wc_format_decimal( wc_get_dimension( 1, $dimension_unit, 'in' ), 4, true ),
            'width' => wc_format_decimal( wc_get_dimension( 6, $dimension_unit, 'in' ), 4, true ),
            'height' => wc_format_decimal( wc_get_dimension( 4, $dimension_unit, 'in' ), 4, true ),
        );
    }

    public static function schema() {
        return array( 'weight_oz' => 15, 'length_in' => 1, 'width_in' => 6, 'height_in' => 4, 'weight_unit' => get_option( 'woocommerce_weight_unit', 'kg' ), 'dimension_unit' => get_option( 'woocommerce_dimension_unit', 'cm' ), 'store_values' => self::values(), 'applies' => 'Blank shipping fields only; seller-entered values take priority. Merchant defaults, not measured Facebook facts.' );
    }

    public static function fill( $listing ) {
        foreach ( self::values() as $field => $value ) if ( ! array_key_exists( $field, $listing ) || '' === trim( (string) $listing[ $field ] ) ) $listing[ $field ] = $value;
        return $listing;
    }

    public static function save( $product ) {
        if ( ! $product instanceof WC_Product || $product->is_virtual() || has_term( 'knife-sharpening', 'product_cat', $product->get_id() ) ) return;
        if ( ! self::$requests && ! $product->get_meta( '_krev_import_source_url', true, 'edit' ) ) return;
        foreach ( self::values() as $field => $value ) {
            $getter = 'get_' . $field; $setter = 'set_' . $field;
            if ( '' === trim( (string) $product->$getter( 'edit' ) ) ) $product->$setter( $value );
        }
    }
}
