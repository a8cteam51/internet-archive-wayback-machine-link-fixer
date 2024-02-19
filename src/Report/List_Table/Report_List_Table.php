<?php

/**
 * Page used to render the Reports as a post list table.
 *
 * @since      1.1.0
 *
 * @package    WPCOMSpecialProjects\Wayback_Link_Fixer
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report\List_Table;

defined( 'ABSPATH' ) || exit;

use DateTime;
use PHPCompatibility\Sniffs\FunctionDeclarations\NewNullableTypesSniff;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Reports;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Helper;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;

/**
 * List Table
 */
class Report_List_Table extends \WP_List_Table {

	/**
	 * Column Names.
	 */
	public const COLUMN_BLOG       = 'blog';
	public const COLUMN_USER       = 'user';
	public const COLUMN_STATUS     = 'status';
	public const COLUMN_PAGE_COUNT = 'page_count';
	public const COLUMN_CREATED_AT = 'created_at';
	public const COLUMN_ACTIONS    = 'actions';

	// Filters.
	public const FILTER_USER_ID   = 'wlf-user';
	public const FILTER_BLOG_ID   = 'wlf-blog';
	public const FILTER_STATUS    = 'wlf-status';
	public const FILTER_DATE_FROM = 'wlf-date-from';
	public const FILTER_DATE_TO   = 'wlf-date-to';
	public const FILTER_DATE      = 'wlf-date';

	//Actions
	public const ACTION_DELETE = 'delete';

	/**
	 * Access to the reports repository.
	 *
	 * @since 1.1.0
	 *
	 * @var Report_Repository
	 */
	private Report_Repository $report_repository;

	/**
	 * Holds the items in the table.
	 *
	 * @var array<int, array{report: Report, logs: integer}> $items
	 */
	public $items = array();

	/**
	 * Custom notices.
	 *
	 * @since 1.1.0
	 *
	 * @var array{message:string, type:string}[]
	 */
	private array $notices = array();

	/**
	 * Report_List_Table constructor.
	 *
	 * @since 1.1.0
	 *
	 * @param Report_Repository $report_repository Access to the reports repository.
	 */
	public function __construct( Report_Repository $report_repository ) {
		parent::__construct(
			array(
				'singular' => __( 'Report', 'wpcomsp_wayback_link_fixer' ),
				'plural'   => __( 'Reports', 'wpcomsp_wayback_link_fixer' ),
				'ajax'     => false,
			)
		);

		// Set the repository.
		$this->report_repository = $report_repository;
	}

	/**
	 * Defines the column names and labels.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, string> Associative array of column names and labels.
	 */
	public function get_columns() {
		$columns = array(
			'cb'                    => '<input type="checkbox" />',
			self::COLUMN_USER       => __( 'User', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_BLOG       => __( 'Blog', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_STATUS     => __( 'Status', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_PAGE_COUNT => __( 'Pages Scanned', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_CREATED_AT => __( 'Created', 'wpcomsp_wayback_link_fixer' ),
		);

		// If not a multsite, remove the blog column.
		if ( ! is_multisite() ) {
			unset( $columns[ self::COLUMN_BLOG ] );
		}

		return $columns;
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function no_items() {
		echo esc_html__( 'No reports found.', 'wpcomsp_wayback_link_fixer' );
	}

	/**
	 * Gets the sites blog id, if on multisite, subsite.
	 *
	 * If not multisite, returns null.
	 * If on network admin, returns null.
	 *
	 * @since 1.1.0
	 *
	 * @return integer|null
	 */
	private function get_blog_id(): ?int {
		// If not a multisite, return null.
		if ( ! is_multisite() ) {
			return null;
		}

		// If viewing from the network admin, return null.
		if ( is_network_admin() ) {
			return null;
		}

		// From filters.
		$filters = $this->get_filters();

		return $filters[ self::FILTER_BLOG_ID ] ?? get_current_blog_id();
	}

	/**
	 * Get the args for query based on filters, pagination and site id.
	 *
	 * @since 1.1.0
	 *
	 * @return array{
	 *  user_id: integer|null,
	 *  blog_id: integer|null,
	 *  statuses: array<string>,
	 *  date_from: string|null,
	 *  date_to: string|null
	 * }
	 */
	private function get_query_args(): array {

		$filters = $this->get_filters();

		$args = array(
			'user_id'   => $filters[ self::FILTER_USER_ID ],
			'blog_id'   => $this->get_blog_id(),
			'statuses'  => $filters[ self::FILTER_STATUS ],
			'date_from' => $filters[ self::FILTER_DATE_FROM ],
			'date_to'   => $filters[ self::FILTER_DATE_TO ],
		);

		// Add the date range, if set.
		return $args;
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param array{report: Report, logs:integer} $item The item.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="report[]" value="%s" />',
			$item['report']->get_report_id()
		);
	}

	/**
	 * Get the reports per page.
	 *
	 * @since 1.1.0
	 *
	 * @return integer
	 */
	public function get_reports_per_page(): int {
		return absint( \apply_filters( 'wpcomsp_wayback_link_fixer_reports_per_page', 10 ) );
	}

	/**
	 * Sets the pagination args.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	private function define_pagination_args() {
		$total_reports = absint( $this->report_repository->get_total_count( ...$this->get_query_args() ) );
		$per_page      = $this->get_reports_per_page();

		// Set the pagination args.
		$this->set_pagination_args(
			array(
				'total_items' => $total_reports,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_reports / $per_page ),
			)
		);
	}

	/**
	 * Get defined filters from URL.
	 *
	 * @since 1.1.0
	 *
	 * @return array{
	 * user_id: integer|null,
	 * blog_id: integer|null,
	 * statuses: array<string>,
	 * date_from: string|null,
	 * date_to: string|null
	 * }
	 */
	private function get_filters(): array {
		$filters = array(
			self::FILTER_USER_ID   => \array_key_exists( self::FILTER_USER_ID, $_GET ) && '' !== $_GET[ self::FILTER_USER_ID ] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				? absint( $_GET[ self::FILTER_USER_ID ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				: null,
			self::FILTER_BLOG_ID   => \array_key_exists( self::FILTER_BLOG_ID, $_GET ) && '' !== $_GET[ self::FILTER_BLOG_ID ] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				? absint( $_GET[ self::FILTER_BLOG_ID ] )  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				: null,
			self::FILTER_STATUS    => array(),
			self::FILTER_DATE_FROM => null,
			self::FILTER_DATE_TO   => null,
			self::FILTER_DATE      => null,
		);

		// If date is set in the url.
		if ( \array_key_exists( self::FILTER_DATE, $_GET ) && '' !== $_GET[ self::FILTER_DATE ] ) {
			$month = new \DateTime( sanitize_text_field( $_GET[ self::FILTER_DATE ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// If we have a valid date, set the date from and date to.
			if ( $month instanceof \DateTime ) {
				$filters[ self::FILTER_DATE_FROM ] = $month->format( 'Y-m-01' );
				$filters[ self::FILTER_DATE_TO ]   = $month->format( 'Y-m-t' );
				$filters[ self::FILTER_DATE ]      = $month->format( 'Y-m-01' );
			}
		}

		// Add the statuses if in the URL.
		if ( \array_key_exists( self::FILTER_STATUS, $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$filters[ self::FILTER_STATUS ] = array_map( 'sanitize_text_field', (array) $_GET[ self::FILTER_STATUS ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// Remove any empty values.
			$filters[ self::FILTER_STATUS ] = array_filter( $filters[ self::FILTER_STATUS ] );
		}

		return $filters;
	}

	/**
	 * Get all months from existing reports.
	 *
	 * @return array{date:string, display:string}[]
	 */
	private function get_report_months(): array {
		$months = array();

		// Get all the report created dates.
		$dates = $this->report_repository->get_dates_of_reports( $this->get_blog_id() );

		// Cast to datetime.
		$dates = array_map( fn( string $date ) => new \DateTime( $date ), $dates );

		// Iterate through dates.
		foreach ( $dates as $date ) {
			// Get the year.
			$year  = $date->format( 'Y' );
			$month = $date->format( 'm' );

			// If the year doesnt exist in the months array, add it.
			if ( ! array_key_exists( $year, $months ) ) {
				$months[ $year ] = array();
			}

			// If the months doesnt exist as child of year, add it.
			if ( ! array_key_exists( $month, $months[ $year ] ) ) {
				$months[ $year ][ $month ] = array(
					'date'    => $date->format( 'Y-m-01' ),
					'display' => $date->format( 'F Y' ),
				);
			}
		}

		// Flatten the array
		$months = array_reduce( $months, fn( $carry, $item ) => array_merge( $carry, array_values( $item ) ), array() );

		// Sort the array based on the date.
		usort(
			$months,
			fn( array $a, array $b ) => strtotime( $b['date'] ) - strtotime( $a['date'] )
		);

		return $months;
	}

	/**
	 * Generates the table navigation above or below the table
	 *
	 * @since 3.1.0
	 *
	 * @param string $which Position of the nav, top or bottom.
	 *
	 * @return void
	 */
	protected function display_tablenav( $which ) {
		parent::display_tablenav( $which );

		// Render the custom filters, if top
		if ( 'top' === $which ) {
			$this->render_filters();
		}
	}

	/**
	 * Render the filters.
	 *
	 * @return void
	 */
	protected function render_filters() {

		// Get the current page slug.
		$page_arg = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$users    = array_map( fn( int $id ) => \get_user_by( 'id', $id ), $this->report_repository->get_users_with_reports( $this->get_blog_id() ) );
		// Remove any non WP_User objects.
		$users = array_filter( $users, 'is_object' );

		// Get existing filters.
		$filters = $this->get_filters();

		?>
		<div class="wlf-filters select-multi wide">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page_arg ); ?>" />
			<label for="<?php echo esc_attr( self::FILTER_USER_ID ); ?>" class="screen-reader-text"><?php esc_html_e( 'Reported created by user', 'wpcomsp_wayback_link_fixer' ); ?></label>
			<div class="wlf-filter select-single">
				<select class="wlf-multiselect2" MULTIPLE data-placeholder="<?php echo esc_html__( 'Any user', 'wpcomsp_wayback_link_fixer' ); ?>" name="<?php echo esc_attr( self::FILTER_USER_ID ); ?>" id="<?php echo esc_attr( self::FILTER_USER_ID ); ?>">
					<?php foreach ( $users as $user ) : ?>
						<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $filters[ self::FILTER_USER_ID ], $user->ID ); ?>><?php echo esc_html( wpcomsp_wayback_link_fixer_get_user_name( $user ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php if ( \is_multisite() && \is_network_admin() ) : ?>
				<div class="wlf-filter select-multi wide">
					<label for="<?php echo esc_attr( self::FILTER_BLOG_ID ); ?>" class="screen-reader-text"><?php esc_html_e( 'Reported created by user', 'wpcomsp_wayback_link_fixer' ); ?></label>
					<select class="wlf-multiselect2" MULTIPLE data-placeholder="<?php echo esc_html__( 'Any site', 'wpcomsp_wayback_link_fixer' ); ?>" name="<?php echo esc_attr( self::FILTER_BLOG_ID ); ?>" id="<?php echo esc_attr( self::FILTER_BLOG_ID ); ?>" >
						<?php foreach ( \get_sites() as $site ) : ?>
							<option value="<?php echo esc_attr( $site->blog_id ); ?>"><?php echo esc_html( $site->blogname ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			<?php endif; ?>
			<div class="wlf-filter select-multi wide">
				<label for="<?php echo esc_attr( self::FILTER_STATUS ); ?>" class="screen-reader-text"><?php esc_html_e( 'Report status', 'wpcomsp_wayback_link_fixer' ); ?></label>
				<select class="wlf-multiselect2" data-placeholder="<?php esc_html_e( 'Any Status', 'wpcomsp_wayback_link_fixer' ); ?>" name="<?php echo esc_attr( self::FILTER_STATUS ); ?>[]" id="<?php echo esc_attr( self::FILTER_STATUS ); ?>" MULTIPLE>
					<option value="<?php echo esc_attr( Reports::PENDING_STATUS ); ?>"<?php echo in_array( Reports::PENDING_STATUS, $filters[ self::FILTER_STATUS ], true ) ? ' selected' : ''; ?>><?php esc_html_e( 'Pending', 'wpcomsp_wayback_link_fixer' ); ?></option>
					<option value="<?php echo esc_attr( Reports::IN_PROGRESS_STATUS ); ?>"<?php echo in_array( Reports::IN_PROGRESS_STATUS, $filters[ self::FILTER_STATUS ], true ) ? ' selected' : ''; ?>><?php esc_html_e( 'In Progress', 'wpcomsp_wayback_link_fixer' ); ?></option>
					<option value="<?php echo esc_attr( Reports::COMPLETED_STATUS ); ?>"<?php echo in_array( Reports::COMPLETED_STATUS, $filters[ self::FILTER_STATUS ], true ) ? ' selected' : ''; ?>><?php esc_html_e( 'Completed', 'wpcomsp_wayback_link_fixer' ); ?></option>
				</select>
			</div>

			<div class="wlf-filter select-single">
				<label for="<?php echo esc_attr( self::FILTER_DATE ); ?>" class="screen-reader-text"><?php esc_html_e( 'Date', 'wpcomsp_wayback_link_fixer' ); ?></label>
				<select class="wlf-select2" name="<?php echo esc_attr( self::FILTER_DATE ); ?>" id="<?php echo esc_attr( self::FILTER_DATE ); ?>">
					<option value="" ><?php esc_html_e( 'All Dates', 'wpcomsp_wayback_link_fixer' ); ?></option>
					<?php foreach ( $this->get_report_months() as $month ) : ?>
						<option value="<?php echo esc_attr( $month['date'] ); ?>" <?php selected( $filters[ self::FILTER_DATE ], $month['date'] ); ?>><?php echo esc_html( $month['display'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php submit_button( __( 'Filter Reports', 'wpcomsp_wayback_link_fixer' ), '', 'filter_action', false, array( 'id' => 'wlf-table-filter' ) ); ?>

		</div>


		<?php
	}

	/**
	 * Prepare the items for the table to process
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function prepare_items() {

		// Set the pagination args.
		$this->define_pagination_args();

		// Set the headers
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$primary               = 'name';
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );
		// Get the reports.
		$this->items = $this->get_items();
	}

	/**
	 * Get the items based on pagination and filters.
	 *
	 * @return array{report: Report, logs: integer}[]
	 */
	public function get_items(): array {
		$limit  = $this->get_reports_per_page();
		$offset = 1 === $this->get_pagenum() ? 0 : ( $this->get_pagenum() - 1 ) * $limit;

		// Filter values.
		$filter_values = $this->get_query_args();

		return $this->report_repository->query_reports(
			$limit,
			$offset,
			$filter_values['user_id'],
			$filter_values['blog_id'],
			$filter_values['statuses'],
			$filter_values['date_from'],
			$filter_values['date_to']
		);
	}


	/**
	 * Set the column values
	 *
	 * @param array{report: Report, logs:integer} $item        The item.
	 * @param string                              $column_name The column name.
	 *
	 * @return string The column value.
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case self::COLUMN_BLOG:
				return esc_html( wpcomsp_wayback_link_fixer_get_blog_name( $item['report']->get_blog_id() ) );
			case self::COLUMN_USER:
				return esc_html( wpcomsp_wayback_link_fixer_get_report_author( $item['report'] ) );
			case self::COLUMN_STATUS:
				return esc_html( \ucfirst( $item['report']->get_process() ) );
			case self::COLUMN_PAGE_COUNT:
				return absint( $item['logs'] );
			case self::COLUMN_CREATED_AT:
				return esc_html( $item['report']->get_created_at()->format( wpcomsp_wayback_link_fixer_get_date_time_format() ) );
			default:
				return '';
		}
	}

	/**
	 * Add the actions under the main column (User)
	 *
	 * @param array{report: Report, logs:integer} $item The item.
	 *
	 * @return string
	 */
	public function column_user( $item ) {
		$actions = array(
			'view'   => sprintf(
				'<a href="%s">%s</a>',
				esc_url( Report_Helper::get_single_report_link( $item['report'] ) ),
				esc_html__( 'View', 'wpcomsp_wayback_link_fixer' )
			),
			'delete' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( Report_Helper::get_delete_report_link( $item['report'] ) ),
				esc_html__( 'Delete', 'wpcomsp_wayback_link_fixer' )
			),
		);

		return sprintf(
			'%1$s %2$s',
			esc_html( wpcomsp_wayback_link_fixer_get_report_author( $item['report'] ) ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Add the bulk actions.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, string>
	 */
	public function get_bulk_actions() {
		$actions = array(
			self::ACTION_DELETE => __( 'Delete', 'wpcomsp_wayback_link_fixer' ),
		);

		return $actions;
	}

	/**
	 * Process bulk actions.
	 *
	 * @return void
	 */
	public function process_bulk_action() {

		// if action or action2 is not set in url, bail.
		if ( ! isset( $_GET['action'] ) && ! isset( $_GET['action2'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// security check!
		if ( isset( $_GET['_wpnonce'] ) && ! empty( $_GET['_wpnonce'] ) ) {
			$nonce  = \sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
			$action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_die( 'Nope! Security check failed!' );
			}
		}

		$action = $this->current_action();

		// If we have no action, bail.
		if ( ! $action ) {
			return;
		}

		// If the action is ACTION_DELETE.
		if ( self::ACTION_DELETE === $action ) {
			// Get the reports.
			$reports = isset( $_GET['report'] ) ? (array) $_GET['report'] : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// If we have no reports, bail.
			if ( empty( $reports ) ) {
				return;
			}

			// Delete the reports.
			foreach ( $reports as $report_id ) {
				$this->report_repository->delete_report( $report_id );
			}

			$this->notices[] = array(
				'message' => __( 'Reports deleted successfully.', 'wpcomsp_wayback_link_fixer' ),
				'type'    => 'success',
			);
		}
	}

	/**
	 * Render any notices.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function render_notices() {
		foreach ( $this->notices as $notice ) {
			?>
			<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
				<p><?php echo esc_html( $notice['message'] ); ?></p>
			</div>
			<?php
		}
	}
}
