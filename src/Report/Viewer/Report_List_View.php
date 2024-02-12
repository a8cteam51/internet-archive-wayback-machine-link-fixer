<?php

/**
 * Renders the report list view.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Viewer;

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Event_Page;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\List_Table\Report_List_Table;

/**
 * List View
 */
class Report_List_View {

	/**
	 * Access to the Report Repository.
	 *
	 * @since 1.0.0
	 *
	 * @var Report_Repository
	 */
	private Report_Repository $report_repository;

	/**
	 * Create instance of Report_List_View.
	 *
	 * @since 1.0.0
	 *
	 * @param Report_Repository $report_repository The Report Repository.
	 */
	public function __construct( Report_Repository $report_repository ) {
		$this->report_repository = $report_repository;
	}

	/**
	 * Renders the View.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __invoke(): void {
		// Render the list table.
		$table = new Report_List_Table( $this->report_repository );

		// Run the bulk actions.
		$table->process_bulk_action();

		// Render any notices.
		$table->render_notices();

		echo '<div class="wrap">';
		printf(
			'<h1 class="wp-heading-inline">%s <a href="%s" class="page-title-action">%s</a></h1>',
			esc_html__( 'Reports', 'wayback-link-fixer' ),
			\menu_page_url( Event_Page::PAGE_SLUG, false ),
			esc_html__( 'New Report', 'wayback-link-fixer' )
		);
		echo '<hr class="wp-header-end">';

		// Render the table.
		$table->prepare_items();
		echo '<form method="get">';
		$table->display();
		echo '</form>';

		echo '</div>';
	}
}
