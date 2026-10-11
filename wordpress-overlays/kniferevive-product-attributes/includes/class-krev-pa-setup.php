<?php

defined( 'ABSPATH' ) || exit;

final class KREV_PA_Setup {
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade_schema' ) );
	}

	public static function maybe_upgrade_schema() {
		if ( get_option( 'krev_pa_schema_version' ) === KREV_PA_VERSION ) {
			return;
		}

		$result = self::ensure_schema();
		if ( empty( $result['errors'] ) ) {
			update_option( 'krev_pa_schema_version', KREV_PA_VERSION, false );
		}
	}

	public static function activate() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		$result = self::ensure_schema();
		if ( empty( $result['errors'] ) ) {
			update_option( 'krev_pa_schema_version', KREV_PA_VERSION, false );
		}
	}

	public static function dependency_notice() {
		if ( class_exists( 'WooCommerce' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>' . esc_html__( 'KnifeRevive Product Attributes requires WooCommerce.', 'kniferevive-product-attributes' ) . '</p></div>';
	}

	public static function ensure_schema() {
		$result = array(
			'category'   => null,
			'created'    => array(),
			'existing'   => array(),
			'errors'     => array(),
			'preserved'  => array(),
		);

		$other_finds = get_term_by( 'slug', 'other_finds', 'product_cat' );
		$parent_id   = $other_finds ? (int) $other_finds->term_id : 0;
		$category = get_term_by( 'slug', 'world-coins', 'product_cat' );
		if ( ! $category ) {
			$inserted = wp_insert_term(
				'World Coins',
				'product_cat',
				array(
					'slug'        => 'world-coins',
					'parent'      => $parent_id,
					'description' => KREV_PA_Config::WORLD_COINS_DESCRIPTION,
				)
			);
			if ( is_wp_error( $inserted ) ) {
				$result['errors'][] = $inserted->get_error_message();
			} else {
				$result['category'] = (int) $inserted['term_id'];
			}
		} else {
			$result['category'] = (int) $category->term_id;
			if ( $parent_id !== (int) $category->parent || KREV_PA_Config::WORLD_COINS_DESCRIPTION !== $category->description || 'World Coins' !== $category->name ) {
				$updated = wp_update_term(
					$category->term_id,
					'product_cat',
					array(
						'name'        => 'World Coins',
						'slug'        => 'world-coins',
						'parent'      => $parent_id,
						'description' => KREV_PA_Config::WORLD_COINS_DESCRIPTION,
					)
				);
				if ( is_wp_error( $updated ) ) {
					$result['errors'][] = $updated->get_error_message();
				}
			}
		}

		$by_slug = array();
		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$by_slug[ $attribute->attribute_name ] = $attribute;
		}

		foreach ( array_keys( KREV_PA_Config::existing_definitions() ) as $slug ) {
			if ( isset( $by_slug[ $slug ] ) ) {
				$result['preserved'][] = $slug;
			} else {
				$result['errors'][] = sprintf( 'Required existing attribute pa_%s is missing; it was not recreated automatically.', $slug );
			}
		}

		// The existing global Condition attribute is shared by several catalog
		// groups. Add only missing standard terms; never rename or replace a
		// merchant's existing condition vocabulary.
		$condition_taxonomy = wc_attribute_taxonomy_name( 'condition' );
		if ( taxonomy_exists( $condition_taxonomy ) ) {
			foreach ( array( 'New', 'Used', 'Refurbished', 'Open Box' ) as $name ) {
				if ( ! term_exists( $name, $condition_taxonomy ) ) {
					$created = wp_insert_term( $name, $condition_taxonomy );
					if ( is_wp_error( $created ) ) { $result['errors'][] = sprintf( 'Condition %s: %s', $name, $created->get_error_message() ); }
				}
			}
		}

		foreach ( KREV_PA_Config::definitions() as $slug => $definition ) {
			if ( isset( $by_slug[ $slug ] ) ) {
				$result['existing'][] = $slug;
				continue;
			}
			$attribute_id = wc_create_attribute(
				array(
					'name'         => $definition['name'],
					'slug'         => $slug,
					'type'         => 'select',
					'order_by'     => 'menu_order',
					'has_archives' => false,
				)
			);
			if ( is_wp_error( $attribute_id ) ) {
				$result['errors'][] = sprintf( '%s: %s', $slug, $attribute_id->get_error_message() );
			} else {
				$result['created'][] = $slug;
			}
		}

		delete_transient( 'wc_attribute_taxonomies' );
		flush_rewrite_rules( false );
		return $result;
	}
}
