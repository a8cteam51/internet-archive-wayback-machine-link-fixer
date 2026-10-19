<?php

/**
 * Tests for Report_Table column rendering.
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Report\Report_Table
 *
 * @group Report
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Report;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Report\Report_Table;

/**
 * Test_Report_Table_Columns
 */
class Test_Report_Table_Columns extends \WP_UnitTestCase {

	/**
	 * @testdox The archive column href must be escaped with esc_url — a stored archived URL cannot break out of the attribute. (S047)
	 *
	 * @return void
	 */
	public function test_archive_column_escapes_href(): void {
		$raw_href = 'https://web.archive.org/web/1/https://example.com/?a=1&b="x y"';

		$link = new Link( 'https://example.com/?a=1' );
		$link->set_archived_href( $raw_href );

		$table  = new Report_Table( new Link_Repository() );
		$output = $table->column_default( $link, Report_Table::COLUMN_LINK_ARCHIVE );

		// get_archived_href() rewrites the host, so build the expectation from the getter.
		$this->assertStringContainsString( '"' . esc_url( $link->get_archived_href() ) . '"', $output );
		$this->assertStringNotContainsString( '"' . $link->get_archived_href() . '"', $output );
	}

	/**
	 * @testdox A malformed check date must not fatal the last-check column - the raw value is shown instead. (S058)
	 *
	 * @return void
	 */
	public function test_last_check_column_survives_malformed_check_date(): void {
		$link = new Link( 'https://example.com/malformed-date' );
		$link->add_check( 200, 'not-a-date' );

		$table  = new Report_Table( new Link_Repository() );
		$output = $table->column_default( $link, Report_Table::COLUMN_LINK_CHECKS_LAST );

		$this->assertIsString( $output );
		$this->assertStringContainsString( 'not-a-date', $output );
	}

	/**
	 * The Link Health cell for a done link with the given checks.
	 *
	 * @param integer[] $codes  The check codes, oldest first.
	 * @param boolean   $broken Whether the link is stored as broken.
	 *
	 * @return string
	 */
	private function link_health( array $codes, bool $broken = false ): string {
		$link = ( new Link( 'https://example.com/health' ) )->set_done();
		foreach ( $codes as $i => $code ) {
			$link->add_check( $code, '2025-01-0' . ( $i + 1 ) . ' 00:00:00' );
		}
		if ( $broken ) {
			$link->set_broken();
		}

		return ( new Report_Table( new Link_Repository() ) )->column_default( $link, Report_Table::COLUMN_LINK_HEALTH );
	}

	/**
	 * @testdox The Link Health column flags a link the site blocks the link checker on, instead of calling it active.
	 *
	 * @return void
	 */
	public function test_link_health_flags_a_blocked_link(): void {
		$output = $this->link_health( array( 404, 403 ) );

		$this->assertStringContainsString( 'blocks the Internet Archive link checker', $output );
		$this->assertStringNotContainsString( 'Link is active', $output );
	}

	/**
	 * @testdox The Link Health column still shows active and broken links as before.
	 *
	 * @return void
	 */
	public function test_link_health_still_shows_active_and_broken(): void {
		$this->assertStringContainsString( 'Link is active', $this->link_health( array( 403, 200 ) ) );
		$this->assertStringContainsString( 'Link is broken', $this->link_health( array( 404, 404, 404 ), true ) );
		$this->assertStringNotContainsString( 'blocks the Internet Archive link checker', $this->link_health( array( 404, 404, 404 ), true ) );
	}

	/**
	 * @testdox The Links table help tab explains the blocked flag.
	 *
	 * @return void
	 */
	public function test_help_tab_lists_the_blocked_flag(): void {
		ob_start();
		require IAWMLF_PATH . 'templates/admin/links-table/help-tab-columns.php';
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'blocks the Internet Archive link checker', $output );
	}
}
