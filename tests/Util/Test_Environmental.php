<?php

/**
 * Tests for the Environmental utility.
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Environmental
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer_Tests\Util;

use Internet_Archive\Wayback_Machine_Link_Fixer\Util\Environmental;

/**
 * Test_Environmental
 *
 * @group Util
 * @group Environmental
 */
class Test_Environmental extends \WP_UnitTestCase {

	/**
	 * Tear down
	 */
	public function tear_down(): void {
		remove_all_filters( 'iawmlf_is_production_environment' );
		parent::tear_down();
	}

	/**
	 * @testdox A filter returning a non-bool truthy value should not fatal is_production(). (S068)
	 *
	 * @return void
	 */
	public function test_is_production_casts_non_bool_filter_return(): void {
		add_filter( 'iawmlf_is_production_environment', fn() => 'yes' );

		$this->assertTrue( Environmental::is_production() );
	}

	/**
	 * @testdox A filter returning false keeps is_production() false.
	 *
	 * @return void
	 */
	public function test_is_production_respects_false_filter_return(): void {
		add_filter( 'iawmlf_is_production_environment', fn() => false );

		$this->assertFalse( Environmental::is_production() );
	}
}
