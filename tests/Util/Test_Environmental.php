<?php

/**
 * Unit tests for the Environmental utility class.
 *
 * @since 1.3.3
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Environmental
 *
 * @group Util
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Util;

use Internet_Archive\Wayback_Machine_Link_Fixer\Util\Environmental;

class Test_Environmental extends \WP_UnitTestCase {

	/**
	 * @testdox By default, the is_production method should return true as no environment is set in the tests wp config.
	 *
	 * @return void
	 */
	public function test_is_production_by_default(): void {
		$this->assertTrue( Environmental::is_production() );
	}

	/**
	 * @testdox It should be possible to filter the is_production result.
	 */
	public function test_is_production_filter(): void {
		add_filter(
			'iawmlf_is_production_environment',
			function ( bool $is_production ): bool {
				return false;
			}
		);
		$this->assertFalse( Environmental::is_production() );

		\remove_all_filters( 'iawmlf_is_production_environment' );
	}

	/**
	 * @testdox If the environment is set to staging, is_production should return false.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_is_production_staging(): void {
		define( 'WP_ENVIRONMENT_TYPE', 'staging' );
		define('WP_RUN_CORE_TESTS', true); // To bypass non test environment check.
		$this->assertFalse( Environmental::is_production() );
	}

	/**
	 * @testdox If the environment is set to production, is_production should return true.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_is_production_production(): void {
		define( 'WP_ENVIRONMENT_TYPE', 'production' );
		define('WP_RUN_CORE_TESTS', true);
		$this->assertTrue( Environmental::is_production() );
	}

	/**
	 * @testdox If the environment is set to development, is_production should return false.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_is_production_development(): void {
		define( 'WP_ENVIRONMENT_TYPE', 'development' );
		define('WP_RUN_CORE_TESTS', true);
		$this->assertFalse( Environmental::is_production() );

	}

	/**
	 * @testdox If the environment is set to local, is_production should return false.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_is_production_local(): void {
		define( 'WP_ENVIRONMENT_TYPE', 'local' );
		define('WP_RUN_CORE_TESTS', true);
		$this->assertFalse( Environmental::is_production() );
	}
}
