<?php

declare(strict_types=1);

/**
 * Tests the
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

namespace Gin0115\WPUnit_Helpers\WP\WP_UnitTestCase;

use Gin0115\WPUnit_Helpers\WP\WP_UnitTestCase\Product_Factory_Trait;

class Test_Product_Factory_Trait extends \WP_UnitTestCase {
	use Product_Factory_Trait;

	/** @testdox Can a simple product be created */
	public function t_est_create_simple_product(): void {
		$product = $this->create_simple_product();
		$this->assertEquals( 'simple', $product->get_type() );
		$this->assertInstanceOf( \WC_Product::class, $product );
	}

	/** @testdox Can a simple product be created with meta*/
	public function test_create_simple_product_with_meta(): void {
		$product = $this->create_simple_product(
			function( \WC_Product $product ): \WC_Product {
				$product->set_price( '123.45' );
				return $product;
			}
		);

		$this->assertEquals( '123.45', $product->get_price() );
		$this->assertInstanceOf( \WC_Product::class, $product );
	}
}
