<?php

/**
 * Tests for the Setup_Wizard URLs.
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Setup_Wizard
 *
 * @group Dashboard
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Dashboard;

use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Setup_Wizard;

/**
 * Test_Setup_Wizard
 */
class Test_Setup_Wizard extends \WP_UnitTestCase {

	/**
	 * @testdox get_wizard_url and the rerun link build the exact URLs the old concatenation produced. (S162)
	 *
	 * @return void
	 */
	public function test_wizard_urls_match_the_previous_concatenated_form(): void {
		// The old concatenations, verbatim.
		$this->assertSame( admin_url( 'admin.php?page=iawmlf-setup-wizard' ), Setup_Wizard::get_wizard_url() );
		$this->assertSame(
			Setup_Wizard::get_wizard_url() . '&rerun-wizard=1',
			add_query_arg( 'rerun-wizard', '1', Setup_Wizard::get_wizard_url() )
		);
	}
}
