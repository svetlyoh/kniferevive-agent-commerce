<?php

defined( 'ABSPATH' ) || exit;

final class KREV_PA_Editor {
	const FIELD_PREFIX = 'krev_pa_';

	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_filter( 'woocommerce_json_search_found_product_categories', array( __CLASS__, 'filter_admin_attribute_choices' ), 20, 2 );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'validate_admin_attributes' ), 90 );

		add_filter( 'dokan_product_editor_schema', array( __CLASS__, 'add_dokan_schema' ), 30, 2 );
		add_filter( 'dokan_product_editor_schema_value', array( __CLASS__, 'dokan_schema_value' ), 20, 3 );
		add_action( 'dokan_new_product_form', array( __CLASS__, 'render_classic_dokan_fields' ), 40, 2 );
		add_action( 'dokan_new_product_added', array( __CLASS__, 'save_dokan_fields' ), 40, 2 );
		add_action( 'dokan_product_updated', array( __CLASS__, 'save_dokan_fields' ), 40, 2 );
	}

	public static function enqueue_admin_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->post_type || ! in_array( $screen->base, array( 'post', 'post-new' ), true ) ) {
			return;
		}

		$term_map = array();
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_map[ (string) $term->term_id ] = $term->slug;
			}
		}

		wp_enqueue_script(
			'krev-pa-admin',
			KREV_PA_URL . 'assets/admin-product-attributes.js',
			array( 'jquery' ),
			KREV_PA_VERSION,
			true
		);
		wp_localize_script(
			'krev-pa-admin',
			'krevProductAttributes',
			array(
				'productId' => isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0,
				'termMap'   => $term_map,
				'groups'    => KREV_PA_Config::category_groups(),
				'message'   => __( 'Choose a product category to see the relevant product attributes.', 'kniferevive-product-attributes' ),
			)
		);
	}

	public static function filter_admin_attribute_choices( array $choices, $search_text ) {
		$category_slugs = array();
		if ( isset( $_GET['krev_categories'] ) ) {
			$category_slugs = array_filter( array_map( 'sanitize_title', explode( ',', wp_unslash( (string) $_GET['krev_categories'] ) ) ) );
		}
		$product_id = isset( $_GET['krev_product_id'] ) ? absint( $_GET['krev_product_id'] ) : 0;
		if ( ! $category_slugs && $product_id ) {
			$category_slugs = KREV_PA_Config::category_slugs_for_product( $product_id );
		}

		$allowed = KREV_PA_Config::allowed_slugs_for_groups( KREV_PA_Config::groups_for_category_slugs( $category_slugs ) );
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				foreach ( $product->get_attributes() as $attribute ) {
					if ( $attribute->is_taxonomy() ) {
						$allowed[] = preg_replace( '/^pa_/', '', $attribute->get_name() );
					}
				}
			}
		}
		$allowed = array_unique( $allowed );

		return array_values(
			array_filter(
				$choices,
				static function ( $choice ) use ( $allowed ) {
					$slug = preg_replace( '/^pa_/', '', (string) ( $choice['slug'] ?? '' ) );
					return in_array( $slug, $allowed, true );
				}
			)
		);
	}

	public static function validate_admin_attributes( WC_Product $product ) {
		$groups = KREV_PA_Config::groups_for_category_slugs( self::posted_admin_category_slugs( $product->get_id() ) );
		$allowed = array_map( 'wc_attribute_taxonomy_name', KREV_PA_Config::allowed_slugs_for_groups( $groups ) );
		$stored = (array) get_post_meta( $product->get_id(), '_product_attributes', true );
		$existing = array_keys( $stored );
		$attributes = $product->get_attributes();

		foreach ( $attributes as $key => $attribute ) {
			if ( ! $attribute->is_taxonomy() ) {
				continue;
			}
			$name = $attribute->get_name();
			if ( ! in_array( $name, $allowed, true ) && ! in_array( $name, $existing, true ) ) {
				unset( $attributes[ $key ] );
			}
		}
		$product->set_attributes( $attributes );
	}

	private static function posted_admin_category_slugs( $product_id ) {
		if ( isset( $_POST['tax_input']['product_cat'] ) && is_array( $_POST['tax_input']['product_cat'] ) ) {
			$slugs = array();
			foreach ( array_map( 'absint', wp_unslash( $_POST['tax_input']['product_cat'] ) ) as $term_id ) {
				$term = get_term( $term_id, 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$slugs[] = $term->slug;
				}
			}
			return $slugs;
		}
		return KREV_PA_Config::category_slugs_for_product( $product_id );
	}

	public static function add_dokan_schema( array $items, $product_id = 0 ) {
		$groups = $product_id ? KREV_PA_Config::groups_for_product( $product_id ) : array();
		$allowed = KREV_PA_Config::allowed_slugs_for_groups( $groups );
		$description = $allowed
			? __( 'Only specifications relevant to the saved product category are shown. Separate multiple values with a vertical bar (|).', 'kniferevive-product-attributes' )
			: __( 'Choose a category and save the product as a draft to display its relevant specification fields.', 'kniferevive-product-attributes' );

		$items[] = array(
			'id'          => 'krev-product-specifications',
			'section_id'  => null,
			'type'        => 'section',
			'label'       => __( 'Product specifications', 'kniferevive-product-attributes' ),
			'description' => $description,
			'visibility'  => true,
			'priority'    => 25,
		);

		$definitions = KREV_PA_Config::all_definitions();
		foreach ( $allowed as $position => $slug ) {
			$definition = $definitions[ $slug ] ?? array( 'name' => $slug, 'description' => '' );
			$items[] = array(
				'id'          => self::field_id( $slug ),
				'section_id'  => 'krev-product-specifications',
				'type'        => 'field',
				'label'       => $definition['name'],
				'variant'     => 'text',
				'description' => $definition['description'],
				'required'    => false,
				'visibility'  => true,
				'priority'    => 26 + $position,
			);
		}

		return $items;
	}

	public static function dokan_schema_value( $value, $field_name, $product ) {
		if ( ! $product instanceof WC_Product || ! str_starts_with( (string) $field_name, self::FIELD_PREFIX ) ) {
			return $value;
		}
		$slug = self::slug_from_field_id( $field_name );
		return implode( ' | ', self::attribute_values( $product, $slug ) );
	}

	public static function render_classic_dokan_fields( $post = null, $product_id = 0 ) {
		$groups = $product_id ? KREV_PA_Config::groups_for_product( $product_id ) : array();
		$allowed = KREV_PA_Config::allowed_slugs_for_groups( $groups );
		echo '<div class="dokan-form-group krev-dokan-specifications"><h3>' . esc_html__( 'Product specifications', 'kniferevive-product-attributes' ) . '</h3>';
		if ( ! $allowed ) {
			echo '<p class="help-block">' . esc_html__( 'Choose a category and save the product as a draft to display its relevant specification fields.', 'kniferevive-product-attributes' ) . '</p></div>';
			return;
		}
		$product = $product_id ? wc_get_product( $product_id ) : null;
		$definitions = KREV_PA_Config::all_definitions();
		foreach ( $allowed as $slug ) {
			$definition = $definitions[ $slug ];
			$value = $product ? implode( ' | ', self::attribute_values( $product, $slug ) ) : '';
			echo '<label for="' . esc_attr( self::field_id( $slug ) ) . '">' . esc_html( $definition['name'] ) . '</label>';
			echo '<input class="dokan-form-control" type="text" id="' . esc_attr( self::field_id( $slug ) ) . '" name="' . esc_attr( self::field_id( $slug ) ) . '" value="' . esc_attr( $value ) . '">';
			echo '<p class="help-block">' . esc_html( $definition['description'] ) . '</p>';
		}
		echo '</div>';
	}

	public static function save_dokan_fields( $product_id, $data = array() ) {
		$product_id = absint( $product_id );
		if ( ! $product_id || ! is_array( $data ) ) {
			return;
		}
		$post = get_post( $product_id );
		if ( ! $post || ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'edit_post', $product_id ) ) ) {
			return;
		}
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}

		$allowed = KREV_PA_Config::allowed_slugs_for_groups( KREV_PA_Config::groups_for_product( $product_id ) );
		$changed = false;
		foreach ( $allowed as $slug ) {
			$field = self::field_id( $slug );
			if ( ! array_key_exists( $field, $data ) ) {
				continue;
			}
			$values = array_values( array_filter( array_map( 'sanitize_text_field', preg_split( '/\s*\|\s*/', wp_unslash( (string) $data[ $field ] ) ) ) ) );
			self::set_taxonomy_attribute( $product, $slug, $values );
			$changed = true;
		}
		if ( $changed ) {
			$product->save();
		}
	}

	public static function set_taxonomy_attribute( WC_Product $product, $slug, array $values ) {
		$taxonomy = wc_attribute_taxonomy_name( $slug );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return false;
		}
		$term_ids = array();
		foreach ( $values as $value ) {
			$value = trim( wp_strip_all_tags( (string) $value ) );
			if ( '' === $value ) {
				continue;
			}
			$term = get_term_by( 'name', $value, $taxonomy );
			if ( ! $term ) {
				$created = wp_insert_term( $value, $taxonomy );
				if ( is_wp_error( $created ) ) {
					continue;
				}
				$term_ids[] = (int) $created['term_id'];
			} else {
				$term_ids[] = (int) $term->term_id;
			}
		}

		wp_set_object_terms( $product->get_id(), $term_ids, $taxonomy, false );
		$attributes = $product->get_attributes();
		if ( ! $term_ids ) {
			unset( $attributes[ $taxonomy ] );
			$product->set_attributes( $attributes );
			return true;
		}

		$attribute_id = wc_attribute_taxonomy_id_by_name( $taxonomy );
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( $attribute_id );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( array_values( array_unique( $term_ids ) ) );
		$attribute->set_position( isset( $attributes[ $taxonomy ] ) ? $attributes[ $taxonomy ]->get_position() : count( $attributes ) );
		$attribute->set_visible( true );
		$attribute->set_variation( false );
		$attributes[ $taxonomy ] = $attribute;
		$product->set_attributes( $attributes );
		return true;
	}

	public static function attribute_values( WC_Product $product, $slug ) {
		$taxonomy = wc_attribute_taxonomy_name( $slug );
		$attribute = $product->get_attribute( $taxonomy );
		if ( '' === $attribute ) {
			return array();
		}
		return array_values( array_filter( array_map( 'trim', explode( ',', $attribute ) ) ) );
	}

	private static function field_id( $slug ) {
		return self::FIELD_PREFIX . str_replace( '-', '_', $slug );
	}

	private static function slug_from_field_id( $field ) {
		return str_replace( '_', '-', substr( (string) $field, strlen( self::FIELD_PREFIX ) ) );
	}
}
