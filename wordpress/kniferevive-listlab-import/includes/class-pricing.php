<?php
defined( 'ABSPATH' ) || exit;

final class KREV_Import_Pricing {
    const OPTION = 'krev_marketplace_import_pricing';

    public static function defaults() {
        return array( 'percent' => '20', 'fixed' => '0', 'minimum' => '0', 'rounding' => '0.01', 'categories' => array() );
    }

    public static function settings() { return array_merge( self::defaults(), (array) get_option( self::OPTION, array() ) ); }

    public static function clean_rule( $input ) {
        if ( ! is_array( $input ) ) return new WP_Error( 'import_pricing', 'Invalid pricing rule.' );
        $rule = array();
        foreach ( array( 'percent' => 1000, 'fixed' => 100000, 'minimum' => 100000, 'rounding' => 100 ) as $key => $maximum ) {
            $value = $input[ $key ] ?? self::defaults()[ $key ];
            if ( ! is_scalar( $value ) || ! preg_match( '/^\d+(?:\.\d{1,2})?$/D', (string) $value ) || (float) $value > $maximum || ( 'rounding' === $key && (float) $value < 0.01 ) ) return new WP_Error( 'import_pricing', 'Use non-negative amounts with at most two decimals; rounding must be $0.01–$100.' );
            $rule[ $key ] = (string) $value;
        }
        return $rule;
    }

    public static function quote( $source_price, $category_id ) {
        if ( 'USD' !== get_woocommerce_currency() || 2 !== wc_get_price_decimals() ) return new WP_Error( 'import_currency', 'Marketplace imports currently require a USD store with two decimal places.', array( 'status' => 422 ) );
        if ( ! is_scalar( $source_price ) || ! preg_match( '/^\d{1,6}(?:\.\d{1,2})?$/D', (string) $source_price ) ) return new WP_Error( 'import_price', 'Provide the Facebook price as a non-negative USD amount, for example 25.00.', array( 'status' => 422 ) );
        $settings = self::settings();
        $rule = $settings; $rule_category = 0;
        foreach ( array_merge( array( (int) $category_id ), get_ancestors( $category_id, 'product_cat', 'taxonomy' ) ) as $id ) {
            if ( isset( $settings['categories'][ $id ] ) ) { $rule = $settings['categories'][ $id ]; $rule_category = (int) $id; break; }
        }
        $rule = self::clean_rule( $rule );
        if ( is_wp_error( $rule ) ) return $rule;
        // Integer cents/basis points: round UP, avoiding underpricing from float error.
        $source_minor = (int) round( (float) $source_price * 100 );
        $basis = (int) round( (float) $rule['percent'] * 100 );
        $fixed = (int) round( (float) $rule['fixed'] * 100 );
        $minimum = (int) round( (float) $rule['minimum'] * 100 );
        $increment = (int) round( (float) $rule['rounding'] * 100 );
        $marked = intdiv( $source_minor * ( 10000 + $basis ) + 9999, 10000 ) + $fixed;
        $minor = intdiv( max( $marked, $minimum ) + $increment - 1, $increment ) * $increment;
        if ( $minor > 99999999 ) return new WP_Error( 'import_price_limit', 'Calculated price exceeds the import limit.', array( 'status' => 422 ) );
        return array( 'source_price' => number_format( $source_minor / 100, 2, '.', '' ), 'regular_price' => number_format( $minor / 100, 2, '.', '' ), 'currency' => 'USD', 'shipping' => 'Calculated separately by native WooCommerce checkout', 'rule_category_id' => $rule_category );
    }

    public static function menu() { add_submenu_page( 'woocommerce', 'Marketplace Imports', 'Marketplace Imports', 'manage_woocommerce', 'krev-marketplace-imports', array( __CLASS__, 'admin' ) ); }

    public static function admin() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) return;
        $notice = '';
        if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
            check_admin_referer( 'krev_import_pricing' );
            $input = wp_unslash( $_POST );
            $clean = self::clean_rule( $input['global'] ?? array() );
            $categories = array();
            foreach ( (array) ( $input['categories'] ?? array() ) as $id => $row ) {
                if ( empty( $row['enabled'] ) ) continue;
                $term = get_term( (int) $id, 'product_cat' );
                $parsed = self::clean_rule( $row );
                if ( ! $term || is_wp_error( $term ) || is_wp_error( $parsed ) ) { $clean = new WP_Error( 'import_pricing', 'One category rule is invalid. No settings were saved.' ); break; }
                $categories[ (int) $id ] = $parsed;
            }
            if ( is_wp_error( $clean ) ) $notice = $clean->get_error_message();
            else { $clean['categories'] = $categories; update_option( self::OPTION, $clean, false ); $notice = 'Pricing settings saved.'; }
        }
        $settings = self::settings();
        echo '<div class="wrap"><h1>Marketplace Imports</h1>';
        if ( $notice ) echo '<div class="notice notice-info"><p>' . esc_html( $notice ) . '</p></div>';
        echo '<p>Muse prepares an item and opens a private link. The seller completes it in ListLab. No product is published by preparation.</p><p>Price = Facebook price × (1 + markup %) + fixed addition, then minimum and rounding up. Shipping is added separately at native checkout. A markup is a percentage of source price, not gross profit margin.</p><p>Default example: $25 × 1.20 = $30 + shipping. Category overrides apply to children unless a closer override exists. Changing settings affects new imports and unclaimed reviews; existing drafts keep their price.</p><form method="post">';
        wp_nonce_field( 'krev_import_pricing' );
        echo '<h2>Default pricing</h2>';
        self::rule_inputs( 'global', $settings );
        echo '<h2>Category overrides</h2><table class="widefat"><thead><tr><th>Category / override</th><th>Pricing</th></tr></thead><tbody>';
        $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
        foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
            $id = (int) $term->term_id; $enabled = isset( $settings['categories'][ $id ] );
            echo '<tr><td><label><input type="checkbox" name="categories[' . $id . '][enabled]" value="1" ' . checked( $enabled, true, false ) . '> ' . esc_html( $term->name ) . ' (#' . $id . ')</label></td><td>';
            self::rule_inputs( 'categories[' . $id . ']', $enabled ? $settings['categories'][ $id ] : $settings );
            echo '</td></tr>';
        }
        echo '</tbody></table>'; submit_button( 'Save import pricing' ); echo '</form></div>';
    }

    private static function rule_inputs( $prefix, $rule ) {
        foreach ( array( 'percent' => 'Markup %', 'fixed' => 'Fixed addition $', 'minimum' => 'Minimum price $', 'rounding' => 'Round up to $' ) as $key => $label ) {
            echo '<label style="display:inline-block;margin:0 18px 12px 0">' . esc_html( $label ) . ' <input style="width:100px" type="number" step="0.01" min="' . ( 'rounding' === $key ? '0.01' : '0' ) . '" name="' . esc_attr( $prefix . '[' . $key . ']' ) . '" value="' . esc_attr( $rule[ $key ] ) . '"></label>';
        }
    }
}
