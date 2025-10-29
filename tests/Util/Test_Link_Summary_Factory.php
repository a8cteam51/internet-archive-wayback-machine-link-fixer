<?php

/**
 * Unit tests for the Link_Summary_Factory class.
 *
 * @since 1.3.1
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Link_Summary_Factory
 *
 * @group Util
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Util;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Util\Link_Summary_Factory;

/**
 * Test_Link_Summary_Factory
 */
class Test_Link_Summary_Factory extends \WP_UnitTestCase {

	/**
	 * Clean up after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		parent::tear_down();
	}

	/**
	 * @testdox It should return a processing message when the link is still being processed.
	 *
	 * @return void
	 */
	public function test_returns_processing_message_when_link_still_processing(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_PENDING );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'still being processed', $summary );
	}

	/**
	 * @testdox It should return a processing message when the link is new.
	 *
	 * @return void
	 */
	public function test_returns_processing_message_when_link_is_new(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_NEW );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'still being processed', $summary );
	}

	/**
	 * @testdox It should return archived and working message when link has archive and last check is successful.
	 *
	 * @return void
	 */
	public function test_returns_archived_working_message_when_archive_exists_and_working(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );
		$link->add_check( 200, '2024-01-01 12:00:00' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'archived on archive.org', $summary );
		$this->assertStringContainsString( 'still working', $summary );
	}

	/**
	 * @testdox It should return archived but failing message with singular check when link has archive and 1 failed check.
	 *
	 * @return void
	 */
	public function test_returns_archived_failing_message_with_singular_check(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );
		$link->add_check( 500, '2024-01-01 12:00:00' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'archived on archive.org', $summary );
		$this->assertStringContainsString( 'not working', $summary );
		$this->assertStringContainsString( 'failed 1 previous consecutive check', $summary );
		$this->assertStringContainsString( '4 more', $summary );
		$this->assertStringNotContainsString( 'checks', $summary );
	}

	/**
	 * @testdox It should return archived but failing message with plural checks when link has archive and multiple failed checks.
	 *
	 * @return void
	 */
	public function test_returns_archived_failing_message_with_plural_checks(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );
		$link->add_check( 500, '2024-01-01 10:00:00' );
		$link->add_check( 500, '2024-01-01 11:00:00' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'archived on archive.org', $summary );
		$this->assertStringContainsString( 'not working', $summary );
		$this->assertStringContainsString( 'failed 2 previous consecutive checks', $summary );
		$this->assertStringContainsString( '3 more', $summary );
	}

	/**
	 * @testdox It should return not archived but working message when link has no archive and is working.
	 *
	 * @return void
	 */
	public function test_returns_not_archived_working_message_when_no_archive_and_working(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->add_check( 200, '2024-01-01 12:00:00' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'not archived on archive.org', $summary );
		$this->assertStringContainsString( 'currently working', $summary );
	}

	/**
	 * @testdox It should return not archived and failing message with singular check when link has no archive and 1 failed check.
	 *
	 * @return void
	 */
	public function test_returns_not_archived_failing_message_with_singular_check(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->add_check( 500, '2024-01-01 12:00:00' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'not archived on archive.org', $summary );
		$this->assertStringContainsString( 'not working', $summary );
		$this->assertStringContainsString( 'failed 1 previous consecutive check', $summary );
		$this->assertStringNotContainsString( 'checks', $summary );
	}

	/**
	 * @testdox It should return not archived and failing message with plural checks when link has no archive and multiple failed checks.
	 *
	 * @return void
	 */
	public function test_returns_not_archived_failing_message_with_plural_checks(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->add_check( 500, '2024-01-01 10:00:00' );
		$link->add_check( 500, '2024-01-01 11:00:00' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'not archived on archive.org', $summary );
		$this->assertStringContainsString( 'not working', $summary );
		$this->assertStringContainsString( 'failed 2 previous consecutive checks', $summary );
	}

	/**
	 * @testdox It should handle null last check by treating it as failed.
	 *
	 * @return void
	 */
	public function test_handles_null_last_check_as_failed(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'archived on archive.org', $summary );
		$this->assertStringContainsString( 'No check history yet', $summary );
	}

	/**
	 * @testdox It should handle checks with missing http_code by skipping them.
	 *
	 * @return void
	 */
	public function test_handles_checks_with_missing_http_code(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );

		// Add a check with missing http_code
		$link->add_check( 500, '2024-01-01 10:00:00' );
		$link->add_check( 200, '2024-01-01 11:00:00' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'archived on archive.org', $summary );
		$this->assertStringContainsString( 'still working', $summary );
	}

	/**
	 * @testdox It should handle boundary condition when failed checks equal the threshold.
	 *
	 * @return void
	 */
	public function test_handles_boundary_condition_at_threshold(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );
		$link->add_check( 500, '2024-01-01 09:00:00' );
		$link->add_check( 500, '2024-01-01 10:00:00' );
		$link->add_check( 500, '2024-01-01 11:00:00' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'archived on archive.org', $summary );
		$this->assertStringContainsString( 'not working', $summary );
		$this->assertStringContainsString( 'failed 3 previous consecutive checks', $summary );
		$this->assertStringContainsString( '2 more', $summary );
	}

	/**
	 * @testdox It should handle mixed valid and invalid checks correctly.
	 *
	 * @return void
	 */
	public function test_handles_mixed_valid_and_invalid_checks(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );
		$link->add_check( 200, '2024-01-01 09:00:00' );
		$link->add_check( 500, '2024-01-01 10:00:00' );
		$link->add_check( 500, '2024-01-01 11:00:00' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'archived on archive.org', $summary );
		$this->assertStringContainsString( 'not working', $summary );
		$this->assertStringContainsString( 'failed 2 previous consecutive checks', $summary );
		$this->assertStringContainsString( '3 more', $summary );
	}

	/**
	 * @testdox It should handle empty archived href as no archive.
	 *
	 * @return void
	 */
	public function test_handles_empty_archived_href_as_no_archive(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->set_archived_href( '' );
		$link->add_check( 200, '2024-01-01 12:00:00' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'not archived on archive.org', $summary );
		$this->assertStringContainsString( 'currently working', $summary );
	}

	/**
	 * @testdox It should return redirect message when failed checks meet the threshold.
	 *
	 * @return void
	 */
	public function test_returns_redirect_message_when_failed_checks_meet_threshold(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );
		$link->add_check( 500, '2024-01-01 09:00:00' );
		$link->add_check( 500, '2024-01-01 10:00:00' );
		$link->add_check( 500, '2024-01-01 11:00:00' );
		$link->add_check( 500, '2024-01-01 12:00:00' );
		$link->add_check( 500, '2024-01-01 13:00:00' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'redirecting to the archived version', $summary );
		$this->assertStringContainsString( 'after 5 failed checks', $summary );
	}

	/**
	 * @testdox It should return redirect message when failed checks exceed the threshold.
	 *
	 * @return void
	 */
	public function test_returns_redirect_message_when_failed_checks_exceed_threshold(): void {
		$link = new Link( 'https://example.com' );
		$link->set_archive_process( Link::PROCESS_DONE );
		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );
		$link->add_check( 500, '2024-01-01 08:00:00' );
		$link->add_check( 500, '2024-01-01 09:00:00' );
		$link->add_check( 500, '2024-01-01 10:00:00' );
		$link->add_check( 500, '2024-01-01 11:00:00' );
		$link->add_check( 500, '2024-01-01 12:00:00' );
		$link->add_check( 500, '2024-01-01 13:00:00' );

		$factory = new Link_Summary_Factory( $link );
		$summary = $factory->get_summary();

		$this->assertStringContainsString( 'redirecting to the archived version', $summary );
		$this->assertStringContainsString( 'after 6 failed checks', $summary );
	}
}
