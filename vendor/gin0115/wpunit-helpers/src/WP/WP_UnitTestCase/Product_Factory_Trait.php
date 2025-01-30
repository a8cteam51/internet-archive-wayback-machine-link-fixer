<?php

/**
 * WP_UnitTestCase trait for creating WooCommerce products
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

declare( strict_types=1 );

namespace Gin0115\WPUnit_Helpers\WP\WP_UnitTestCase;

use WC_Product;

trait Product_Factory_Trait {

	/**
	 * Returns a simple product.
	 *
	 * Can manuipluate product with callable.
	 *
	 * @param callable(WC_Product):WC_Product $filter
	 * @return \WC_Product
	 */
	public function create_simple_product( ?callable $filter = null ): \WC_Product {
		// Ensure set to product
		$args['post_type'] = 'product';

		// Create product, and run save once to popualte missing values.
		$product_id = $this->factory->post->create( $args );
		\wp_set_object_terms( $product_id, 'simple', 'product_type' );

		// Get product and set price.
		$product = \wc_get_product( $product_id );
		$product->set_price( '9.99' );

		// Pass through filter if defined.
		if ( ! is_null( $filter ) ) {
			$product = $filter( $product );
		}

		$product->save();
		return $product;
	}
}
