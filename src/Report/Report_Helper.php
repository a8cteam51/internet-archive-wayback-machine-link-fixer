<?php

/**
 * Collection of helper methods for Reports.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report;

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Viewer\Report_Viewer_Page;

/**
 * Report Helpers
 */
class Report_Helper {

	/**
	 * Generate the link for a single report.
	 *
	 * @since 1.0.0
	 *
	 * @param Report $report The report to generate the link for.
	 *
	 * @return string
	 */
	public static function get_single_report_link( Report $report ): string {

		// Get the base url.
		$url = self::get_report_list_link();

		return \add_query_arg(
			array(
				'report_id' => $report->get_report_id(),
			),
			$url
		);
	}

	/**
	 * Get the link for the report list page.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_report_list_link(): string {
		return \admin_url( 'admin.php?page=' . Report_Viewer_Page::PAGE_SLUG );
	}
}
