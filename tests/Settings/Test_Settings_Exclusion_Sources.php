<?php

/**
 * Tests for the built-in exclusion list getter on Settings.
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Settings;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

/**
 * Test_Settings_Exclusion_Sources
 *
 * @group Settings
 * @group Link_Exclusion
 */
class Test_Settings_Exclusion_Sources extends \WP_UnitTestCase {

	public function tear_down(): void {
		remove_all_filters( 'iawmlf_bundled_link_exclusions' );
		parent::tear_down();
	}

	/**
	 * @testdox The built-in exclusion list ships with the bundled defaults and is extendable via the undocumented iawmlf_bundled_link_exclusions hook.
	 *
	 * @return void
	 */
	public function test_bundled_link_exclusions_default_and_filterable(): void {
		// Ships with LinkedIn (which blocks the checker) by default.
		$this->assertContains( '*.linkedin.com*', Settings::get_bundled_link_exclusions() );

		add_filter(
			'iawmlf_bundled_link_exclusions',
			function ( array $list ): array {
				$list[] = '*blocked.test*';
				return $list;
			}
		);

		$filtered = Settings::get_bundled_link_exclusions();
		$this->assertContains( '*.linkedin.com*', $filtered, 'The bundled default is kept.' );
		$this->assertContains( '*blocked.test*', $filtered, 'The filter can add more.' );
	}
}
