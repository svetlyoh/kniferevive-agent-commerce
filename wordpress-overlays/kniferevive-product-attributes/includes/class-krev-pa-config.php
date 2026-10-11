<?php

defined( 'ABSPATH' ) || exit;

final class KREV_PA_Config {
	const WORLD_COINS_DESCRIPTION = 'Collectible coins from countries and issuing authorities around the world, organized by country, denomination, year, composition, and condition or grade.';

	public static function category_groups() {
		return array(
			'knives' => array(
				'boning-knife', 'bread-knife', 'butcher-knife', 'chefs-knife', 'cleaver',
				'fillet-knife', 'nakiri-knife', 'oyster-knife', 'paring-knife', 'santoku-knife',
				'serated-utility-knife', 'carving-knife', 'steak-knife', 'tomato-knife', 'utility-knife',
			),
			'tech'             => array( 'technology' ),
			'art'              => array( 'art' ),
			'world-coins'      => array( 'world-coins' ),
			'world-spices'     => array( 'world-spices' ),
			'knife-sharpening' => array( 'knife-sharpening' ),
			'other-finds'      => array( 'other_finds' ),
		);
	}

	public static function group_attributes() {
		return array(
			'knives' => array( 'model-number', 'msrp', 'condition', 'blade-length', 'blade-steel', 'edge-type', 'handle-material', 'country-of-origin' ),
			'tech' => array( 'brand', 'condition', 'processor', 'memory', 'storage', 'graphics', 'operating-system', 'form-factor' ),
			'art' => array( 'artist', 'medium', 'art-style', 'subject', 'dimensions', 'year-period', 'condition' ),
			// WordPress reserves the taxonomy name "year". WooCommerce therefore
			// refuses pa_year through its public API; pa_coin-year is the safe,
			// update-compatible physical taxonomy for the CSV's Year / Date field.
			'world-coins' => array( 'condition', 'country', 'denomination', 'coin-year', 'composition', 'coin-grade' ),
			'world-spices' => array( 'condition', 'spice-type', 'country-of-origin', 'spice-form', 'net-weight' ),
			'knife-sharpening' => array( 'service' ),
			'other-finds' => array(),
		);
	}

	public static function definitions() {
		return array(
			'msrp' => array( 'name' => 'MSRP', 'description' => "Manufacturer's suggested retail price in the store currency. Store a non-negative decimal amount without a currency symbol." ),
			'blade-length' => array( 'name' => 'Blade Length', 'description' => 'Specifies the length of the knife blade, typically measured from the heel to the tip.' ),
			'blade-steel' => array( 'name' => 'Blade Steel', 'description' => 'Identifies the steel or primary blade material used in the knife.' ),
			'edge-type' => array( 'name' => 'Edge Type', 'description' => 'Describes the cutting-edge style, such as straight, serrated, Granton, or partially serrated.' ),
			'handle-material' => array( 'name' => 'Handle Material', 'description' => 'Identifies the primary material used to construct the knife handle.' ),
			'country-of-origin' => array( 'name' => 'Country of Origin', 'description' => 'Identifies the country where the knife was manufactured when known.' ),
			'brand' => array( 'name' => 'Brand / Manufacturer', 'description' => 'Identifies the company or manufacturer that produced the computer or system.' ),
			'processor' => array( 'name' => 'Processor (CPU)', 'description' => "Identifies the computer's processor model, family, and generation when known." ),
			'memory' => array( 'name' => 'Memory (RAM)', 'description' => 'Specifies the amount of installed system memory available to the computer.' ),
			'storage' => array( 'name' => 'Storage', 'description' => "Describes the computer's storage capacity and storage type, such as SSD or HDD." ),
			'graphics' => array( 'name' => 'Graphics (GPU)', 'description' => 'Identifies the integrated or dedicated graphics processor installed in the computer.' ),
			'operating-system' => array( 'name' => 'Operating System', 'description' => 'Identifies the operating system installed on or included with the computer.' ),
			'form-factor' => array( 'name' => 'Form Factor / PC Type', 'description' => 'Describes the physical computer type or chassis format, such as desktop, tower, SFF, mini PC, or laptop.' ),
			'artist' => array( 'name' => 'Artist', 'description' => 'Identifies the artist or creator responsible for producing the artwork.' ),
			'medium' => array( 'name' => 'Medium', 'description' => 'Describes the primary materials or artistic medium used to create the artwork.' ),
			'art-style' => array( 'name' => 'Art Style', 'description' => 'Classifies the artwork by its visual or artistic style, such as abstract, realism, or impressionism.' ),
			'subject' => array( 'name' => 'Subject', 'description' => 'Describes the main subject or theme depicted in the artwork.' ),
			'dimensions' => array( 'name' => 'Dimensions', 'description' => 'Provides the physical size of the artwork, typically including height, width, and depth when applicable.' ),
			'year-period' => array( 'name' => 'Year / Period', 'description' => 'Indicates the year the artwork was created or the approximate artistic period when the exact year is unknown.' ),
			'country' => array( 'name' => 'Country / Issuing Authority', 'description' => 'Identifies the country, territory, or issuing authority responsible for the coin.' ),
			'denomination' => array( 'name' => 'Denomination', 'description' => "Specifies the coin's stated monetary denomination and unit of currency." ),
			'coin-year' => array( 'name' => 'Year / Date', 'description' => 'Identifies the date or year shown on the coin or associated with its issue.' ),
			'composition' => array( 'name' => 'Composition / Metal', 'description' => 'Describes the primary metal or material composition of the coin.' ),
			'coin-grade' => array( 'name' => 'Condition / Grade', 'description' => "Describes the coin's preservation level or recognized numismatic grade." ),
			'spice-type' => array( 'name' => 'Spice / Blend Type', 'description' => 'Identifies the spice, seasoning, or blend contained in the listing.' ),
			'spice-form' => array( 'name' => 'Form / Grind', 'description' => 'Describes the product form, such as whole, ground, crushed, or flakes.' ),
			'net-weight' => array( 'name' => 'Net Weight / Package Size', 'description' => 'Describes the packaged net quantity; this is catalog information and is not the WooCommerce shipping weight.' ),
		);
	}

	public static function existing_definitions() {
		return array(
			'model-number' => array( 'name' => 'Model', 'description' => 'Existing KnifeRevive model-number attribute.' ),
			'condition'    => array( 'name' => 'Condition', 'description' => 'Existing KnifeRevive product condition attribute.' ),
			'service'      => array( 'name' => 'Service', 'description' => 'Existing KnifeRevive service attribute.' ),
		);
	}

	public static function all_definitions() {
		return array_merge( self::existing_definitions(), self::definitions() );
	}

	public static function groups_for_category_slugs( array $slugs ) {
		$groups = array();
		foreach ( self::category_groups() as $group => $category_slugs ) {
			if ( array_intersect( $slugs, $category_slugs ) ) {
				$groups[] = $group;
			}
		}
		return $groups;
	}

	public static function category_slugs_for_product( $product_id ) {
		$terms = wp_get_post_terms( (int) $product_id, 'product_cat' );
		if ( is_wp_error( $terms ) ) {
			return array();
		}
		$slugs = array();
		foreach ( $terms as $term ) {
			$slugs[] = $term->slug;
			foreach ( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'product_cat' );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$slugs[] = $ancestor->slug;
				}
			}
		}
		return array_values( array_unique( $slugs ) );
	}

	public static function groups_for_product( $product_id ) {
		return self::groups_for_category_slugs( self::category_slugs_for_product( $product_id ) );
	}

	public static function allowed_slugs_for_groups( array $groups ) {
		$mapping = self::group_attributes();
		$slugs = array();
		foreach ( $groups as $group ) {
			$slugs = array_merge( $slugs, $mapping[ $group ] ?? array() );
		}
		return array_values( array_unique( $slugs ) );
	}

	public static function merchant_details() {
		return array(
			'knives' => array(
				'blade-length' => array( 'Knife specifications', 'Blade length' ),
				'blade-steel' => array( 'Knife specifications', 'Blade steel' ),
				'edge-type' => array( 'Knife specifications', 'Edge type' ),
				'handle-material' => array( 'Knife specifications', 'Handle material' ),
				'country-of-origin' => array( 'Knife specifications', 'Country of origin' ),
			),
			'tech' => array(
				'processor' => array( 'Processor', 'CPU' ),
				'memory' => array( 'Memory', 'RAM' ),
				'storage' => array( 'Memory', 'Storage' ),
				'graphics' => array( 'Graphics', 'GPU' ),
				'operating-system' => array( 'System', 'Operating system' ),
				'form-factor' => array( 'System', 'Form factor' ),
			),
			'art' => array(
				'artist' => array( 'Artwork details', 'Artist' ),
				'medium' => array( 'Artwork details', 'Medium' ),
				'art-style' => array( 'Artwork details', 'Art style' ),
				'subject' => array( 'Artwork details', 'Subject' ),
				'dimensions' => array( 'Artwork details', 'Dimensions' ),
				'year-period' => array( 'Artwork details', 'Year / period' ),
			),
			'world-coins' => array(
				'country' => array( 'Coin details', 'Country / issuing authority' ),
				'denomination' => array( 'Coin details', 'Denomination' ),
				'coin-year' => array( 'Coin details', 'Year / date' ),
				'composition' => array( 'Coin details', 'Material' ),
				'coin-grade' => array( 'Coin details', 'Condition / grade' ),
			),
			'world-spices' => array(
				'spice-type' => array( 'Spice details', 'Spice / blend type' ),
				'country-of-origin' => array( 'Spice details', 'Country / region of origin' ),
				'spice-form' => array( 'Spice details', 'Form / grind' ),
				'net-weight' => array( 'Spice details', 'Net weight / package size' ),
			),
		);
	}
}
