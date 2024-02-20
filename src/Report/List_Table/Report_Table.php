<?php

/**
 * WP List Table used to render a single report.
 *
 * @since      1.1.0
 *
 * @package    WPCOMSpecialProjects\Wayback_Link_Fixer
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report\List_Table;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Log;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Repository;

/**
 * Report List Table
 */
class Report_Table extends \WP_List_Table {

	// Table columns.
	public const COLUMN_CHECKBOX      = 'cb';
	public const COLUMN_POST          = 'report-post-id';
	public const COLUMN_LINK_CODE     = 'report-link-code';
	public const COLUMN_LINK_URL      = 'report-link-url';
	public const COLUMN_LINK_CONTENTS = 'report-link-contents';
	public const COLUMN_LINK_STATUS   = 'report-link-status';
	public const COLUMN_DETAILS       = 'report-link-actions';
	public const COLUMN_FIXED         = 'report-link-fixed';

	// Filters
	public const FILTER_POST_ID = 'filter_post_id';
	public const FILTER_STATUS  = 'filter_status';
	public const FILTER_FIXED   = 'filter_fixed';



	/**
	 * Holds the report with is being rendered.
	 *
	 * @since 1.1.0
	 *
	 * @var Report
	 */
	private Report $report;

	/**
	 * Holds all the logs for the report
	 * Logs are pages.
	 *
	 * @since 1.1.0
	 *
	 * @var array<Log>
	 */
	private array $logs = array();

	/**
	 * Holds all the links from the logs.
	 *
	 * @since 1.1.0
	 *
	 * @var array{log: Log, link: Link}[]
	 */
	private array $links = array();

	/**
	 * All unfiltered links.
	 *
	 * @since 1.1.0
	 *
	 * @var array{log: Log, link: Link}[]
	 */
	private array $unfiltered_links = array();

	/**
	 * Custom notices.
	 *
	 * @since 1.1.0
	 *
	 * @var array{message:string, type:string}[]
	 */
	private array $notices = array();

	/**
	 * Create instance of Report_List_Table.
	 *
	 * @since 1.1.0
	 *
	 * @param Report     $report The report to render.
	 * @param array<Log> $logs   The logs to render.
	 */
	public function __construct( Report $report, array $logs ) {
		$this->report = $report;
		$this->logs   = $logs;

		$this->unfiltered_links = array_reduce(
			$this->logs,
			function ( array $links, Log $log ): array {
				// Create sub array with log and link.
				$log_links = array_map(
					function ( Link $link ) use ( $log ): array {
						return array(
							'log'  => $log,
							'link' => $link,
						);
					},
					$log->get_links()
				);

				return array_merge( $links, $log_links );
			},
			array()
		);

		$this->links = $this->get_filtered_links();

		// Populate the parent class properties.
		parent::__construct(
			array(
				'singular' => 'report',
				'plural'   => 'reports',
				'ajax'     => false,
			)
		);

			$this->_actions = array();
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

		// Get existing filters.
		$filters = $this->get_filters();

		?>
		<div class="wlf-filters">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page_arg ); ?>" />
			<input type="hidden" name="report_id" value="<?php echo esc_attr( $this->report->get_report_id() ); ?>" />

			<div class="wlf-filter select-multi wide">
				<label for="<?php echo esc_attr( self::FILTER_STATUS ); ?>" class="screen-reader-text"><?php esc_html_e( 'HTTP Code', 'wpcomsp_wayback_link_fixer' ); ?></label>
				<select data-placeholder="Any HTTP Code" class="wlf-multiselect2" name="<?php echo esc_attr( self::FILTER_STATUS ); ?>[]" id="<?php echo esc_attr( self::FILTER_STATUS ); ?>" multiple>
					<option value=""><?php echo esc_html__( 'Any HTTP Code', 'wpcomsp_wayback_link_fixer' ); ?></option>
					<?php foreach ( $this->get_http_codes() as $http_code ) : ?>
						<option value="<?php echo esc_attr( $http_code ); ?>" <?php echo in_array( (string) $http_code, $filters[ self::FILTER_STATUS ], true ) ? 'selected="selected"' : ''; ?>><?php echo esc_attr( $http_code ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="wlf-filter select-single">
				<label for="<?php echo esc_attr( self::FILTER_POST_ID ); ?>" class="screen-reader-text"><?php esc_html_e( 'Post', 'wpcomsp_wayback_link_fixer' ); ?></label>
				<select class="wlf-select2" name="<?php echo esc_attr( self::FILTER_POST_ID ); ?>" id="<?php echo esc_attr( self::FILTER_POST_ID ); ?>">
					<option value=""><?php echo esc_html__( 'Any Post', 'wpcomsp_wayback_link_fixer' ); ?></option>
					<?php foreach ( $this->get_posts_in_report() as $post_id => $post_title ) : ?>
						<option value="<?php echo esc_attr( $post_id ); ?>" <?php selected( $post_id, $filters[ self::FILTER_POST_ID ] ); ?>><?php echo esc_attr( $post_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="wlf-filter select-single">
				<label for="<?php echo esc_attr( self::FILTER_FIXED ); ?>" class="screen-reader-text"><?php esc_html_e( 'Fixed', 'wpcomsp_wayback_link_fixer' ); ?></label>
				<select class="wlf-select2" name="<?php echo esc_attr( self::FILTER_FIXED ); ?>" id="<?php echo esc_attr( self::FILTER_FIXED ); ?>">
					<option value="any"><?php echo esc_html__( 'Any Fixed', 'wpcomsp_wayback_link_fixer' ); ?></option>
					<option value="fixed" <?php selected( 'fixed', $filters[ self::FILTER_FIXED ] ); ?>><?php echo esc_html__( 'Fixed Link', 'wpcomsp_wayback_link_fixer' ); ?></option>
					<option value="unfixed" <?php selected( 'unfixed', $filters[ self::FILTER_FIXED ] ); ?>><?php echo esc_html__( 'Unfixed Link', 'wpcomsp_wayback_link_fixer' ); ?></option>
				</select>
			</div>

			<?php submit_button( __( 'Filter Links', 'wpcomsp_wayback_link_fixer' ), '', 'filter_action', false, array( 'id' => 'wlf-table-filter' ) ); ?>

		</div>

		<?php
	}
	/**
	 * Get the filters from URL.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, mixed>
	 */
	private function get_filters(): array {

		$filters[ self::FILTER_POST_ID ] = \array_key_exists( self::FILTER_POST_ID, $_GET ) && '' !== $_GET[ self::FILTER_POST_ID ] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? \absint( $_GET[ self::FILTER_POST_ID ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: null;

		$filters[ self::FILTER_STATUS ] = \array_key_exists( self::FILTER_STATUS, $_GET ) && '' !== $_GET[ self::FILTER_STATUS ] // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? \array_map( 'sanitize_text_field', (array) $_GET[ self::FILTER_STATUS ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: array();

		// Remove any empty statuses.
		$filters[ self::FILTER_STATUS ] = \array_filter( $filters[ self::FILTER_STATUS ] );

		$filters[ self::FILTER_FIXED ] = ( function () {
			// If not set in url, return as 'any'.
			if ( ! \array_key_exists( self::FILTER_FIXED, $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return 'any';
			}

			if ( 'fixed' !== $_GET[ self::FILTER_FIXED ] && 'unfixed' !== $_GET[ self::FILTER_FIXED ] ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return 'any';
			}

			// Return as fixed or unfixed.
			return \sanitize_text_field( $_GET[ self::FILTER_FIXED ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} )();

		return $filters;
	}

	/**
	 * Get all HTTP codes from reports.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string>
	 */
	private function get_http_codes(): array {
		$codes = array_map(
			function ( array $link ): string {
				return $link['link']->get_http_code() ?? esc_html__( 'Unknown', 'wpcomsp_wayback_link_fixer' );
			},
			$this->unfiltered_links
		);

		$codes = array_unique( $codes );

		// Sort the codes.
		sort( $codes );

		return $codes;
	}

	/**
	 * Get all posts in the report.
	 *
	 * @since 1.1.0
	 *
	 * @return array<int, string>
	 */
	private function get_posts_in_report(): array {
		$posts = array();

		foreach ( $this->unfiltered_links as $link ) {
			$post_id           = $link['link']->get_post_id();
			$title             = get_the_title( $post_id );
			$posts[ $post_id ] = sprintf(
				'%s (#%d)',
				'' !== $title ? $title : esc_html__( 'Unknown Post', 'wpcomsp_wayback_link_fixer' ),
				$post_id
			);
		}

		return $posts;
	}



	/**
	 * Add link specific data to the rows <tr>
	 *
	 * @since 1.1.0
	 *
	 * @param array{link: Link, log: Log} $item The Rows data.
	 *
	 * @return void
	 */
	public function single_row( $item ) {
		printf(
			'<tr data-post-id="%d" data-index="%d">',
			absint( $item['link']->get_post_id() ),
			absint( $item['link']->get_index() )
		);
		$this->single_row_columns( $item );
		echo '</tr>';
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

	/**
	 * Get the columns for the table.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string, string>
	 */
	public function get_columns() {
		return array(
			self::COLUMN_CHECKBOX      => '',
			self::COLUMN_POST          => __( 'Post', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_LINK_CODE     => __( 'Status Code', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_LINK_URL      => __( 'URL', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_LINK_CONTENTS => __( 'Contents', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_FIXED         => __( 'Fixed', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_DETAILS       => __( 'Details', 'wpcomsp_wayback_link_fixer' ),
		);
	}

	/**
	 * Get all the posts form the links.
	 *
	 * @since 1.1.0
	 *
	 * @return array<int, WP_Post>
	 */
	public function get_posts(): array {
		$post_ids = array_map(
			function ( array $link ): int {
				return $link['link']->get_post_id();
			},
			$this->links
		);

		$post_ids = array_unique( $post_ids );

		$posts = array_map(
			function ( int $post_id ): ?\WP_Post {
				$post = get_post( $post_id );
				return is_wp_error( $post ) ? null : $post;
			},
			$post_ids
		);

		// Strip and null values.
		$posts = array_filter( $posts );

		return $posts;
	}

	/**
	 * Get all the statuses from the links.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string>
	 */
	public function get_statuses(): array {
		$statuses = array_map(
			function ( array $link ): ?int {
				return $link['link']->get_http_code();
			},
			$this->links
		);

		$statuses = array_unique( $statuses );

		// Remove any empty/null values from the array.
		$statuses = array_filter( $statuses );

		// Cast to string
		$statuses = array_map(
			function ( int $status ): string {
				return esc_html( (string) $status );
			},
			$statuses
		);

		return $statuses;
	}

	/**
	 * Get the links.
	 *
	 * @since 1.1.0.
	 *
	 * @return array<Link>
	 */
	private function get_filtered_links(): array {
		$links = $this->unfiltered_links;

		// Get the current state of the filters.
		$filters = $this->get_filters();

		// If we have any statuses.
		if ( ! empty( $filters[ self::FILTER_STATUS ] ) ) {
			$links = array_filter(
				$links,
				function ( array $link ) use ( $filters ): bool {

					// If we have any filters with unknown, ensure any with null as status are returned.
					if ( in_array( 'Unknown', $filters[ self::FILTER_STATUS ], true )
					&& $link['link']->get_http_code() === null
					) {
						return true;
					}

					// Check if the links status code is in the fitlers.
					return in_array( (string) $link['link']->get_http_code(), $filters[ self::FILTER_STATUS ], true );
				}
			);
		}

		// If we have any post ids.
		if ( ! empty( $filters[ self::FILTER_POST_ID ] ) ) {
			$links = array_filter(
				$links,
				function ( array $link ) use ( $filters ): bool {
					return $link['link']->get_post_id() === $filters[ self::FILTER_POST_ID ];
				}
			);
		}

		// If we have a fixed filter.
		if ( 'any' !== $filters[ self::FILTER_FIXED ] ) {
			$links = array_filter(
				$links,
				function ( array $link ) use ( $filters ): bool {
					$fixed = 'fixed' === $filters[ self::FILTER_FIXED ];
					return $fixed === $link['link']->has_been_updated();
				}
			);
		}

		return $links;
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

		// Set items based on paginations.
		$this->items = $this->extract_links();
	}

	/**
	 * Extract links based on pagination.
	 *
	 * @since 1.1.0
	 *
	 * @return array{log: Log, link: Link}[]
	 */
	private function extract_links(): array {
		$page  = $this->get_pagenum();
		$limit = $this->get_links_per_page();

		// If we have no links, return empty array.
		if ( empty( $this->links ) ) {
			return array();
		}

		return array_slice( $this->links, ( $page - 1 ) * $limit, $limit );
	}

	/**
	 * Get the links per page.
	 *
	 * @since 1.1.0
	 *
	 * @return integer
	 */
	public function get_links_per_page() {
		return absint( \apply_filters( 'wpcomsp_wayback_link_fixer_reports_per_report', 10 ) );
	}

	/**
	 * Sets the pagination args.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	private function define_pagination_args() {
		// Get the reports.
		$per_page    = $this->get_links_per_page();
		$total_links = absint( count( $this->links ) );

		// Set the pagination args.
		$this->set_pagination_args(
			array(
				'total_items' => $total_links,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_links / $per_page ),
			)
		);
	}

	/**
	 * Get the current page.
	 *
	 * @since 1.1.0
	 *
	 * @return integer
	 */
	public function get_pagenum() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Post title column with links.
	 *
	 * @since 1.1.0
	 *
	 * @param integer $post_id The post id.
	 *
	 * @return string
	 */
	public function get_log_post_title( int $post_id ): string {
		// Compile actions.
		$actions = array(
			'view' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( \get_the_permalink( $post_id ) ),
				esc_html__( 'View', 'wpcomsp_wayback_link_fixer' )
			),
			'edit' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_post_link( $post_id ) ),
				esc_html__( 'Edit', 'wpcomsp_wayback_link_fixer' )
			),
		);

		return sprintf(
			'%1$s %2$s',
			esc_html( get_the_title( $post_id ) ),
			$this->row_actions( $actions )
		);
	}

	/**
 * Set the column values
 *
 * @param Link   $item        The item.
 * @param string $column_name The column name.
 *
 * @return string The column value.
 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case self::COLUMN_POST:
				return $this->get_log_post_title( $item['link']->get_post_id() );
			case self::COLUMN_LINK_CODE:
				return esc_html( $item['link']->get_http_code() );
			case self::COLUMN_LINK_URL:
				return sprintf(
					'<a href="%s">%s</a>',
					esc_url( $item['link']->get_href() ),
					esc_html( $item['link']->get_href() )
				);
			case self::COLUMN_LINK_CONTENTS:
				return esc_html( $item['link']->get_contents() );
			case self::COLUMN_DETAILS:
				return $this->compile_details_cell( $item );
			case self::COLUMN_FIXED:
				return sprintf(
					'<span class="dashicons %s"></span>',
					$item['link']->has_been_updated() ? 'dashicons-yes-alt' : 'dashicons-no'
				);

			default:
				return '';
		}
	}

	/**
	 * Compile the details cell for link.
	 *
	 * @param array{log: Log, link: Link} $item The link.
	 *
	 * @return string
	 */
	private function compile_details_cell( array $item ): string {
		$links = array();

		// If the link has not been fixed, offer a fix.
		if ( ! $item['link']->has_been_updated() ) {
			$links[] = sprintf(
				'<span class="wlf-report-link-actions__fixes dashicons dashicons-admin-tools" data-post_id="%s" data-link_index="%d" data-log="%s" data-tooltip="%s" data-options=\'%s\' data-url="%s"></span>',
				$item['link']->get_post_id(),
				$item['link']->get_index(),
				$item['log']->get_id(),
				\esc_html__( 'Attempt to fix', 'wpcomsp_wayback_link_fixer' ),
				wp_json_encode( $item['link']->get_replacement_options() ),
				$item['link']->get_href()
			);
		}

		// If we have comments, show the icon to render modal.
		if ( ! empty( $item['link']->get_comments() ) ) {
			$links[] = sprintf(
				'<span class="wlf-report-link-actions__comments dashicons dashicons-admin-comments" data-comments=\'%s\' data-tooltip="%s"></span>',
				\str_replace(
					'>>',
					'→',
					json_encode( $item['link']->get_comments() ) // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
				),
				esc_html__( 'Comments', 'wpcomsp_wayback_link_fixer' )
			);
		}

		return join( ' | ', $links );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function no_items() {
		echo esc_html__( 'Report has no links', 'wpcomsp_wayback_link_fixer' );
	}
}
