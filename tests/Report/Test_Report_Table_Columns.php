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
}
