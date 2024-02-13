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

		$this->links = array_reduce(
			$logs,
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

		// Populate the parent class properties.
		parent::__construct(
			array(
				'singular' => 'report',
				'plural'   => 'reports',
				'ajax'     => false,
			)
		);
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
	 * @since 1.1.0
	 *
	 * @param integer      $limit   The limit of links to return.
	 * @param integer      $page    The page of links to return.
	 * @param array        $status  The status of the links to return.
	 * @param integer|null $post_id The post id to filter by.
	 *
	 * @return array<Link>
	 */
	private function get_links( int $limit = 10, int $page = 1, array $status = array(), ?int $post_id = null ): array {
		$links = $this->links;

		if ( ! empty( $status ) ) {
			$links = array_filter(
				$links,
				function ( Link $link ) use ( $status, $post_id ): bool {
					// If we have a post id, filter by that and status.
					return $post_id
						? in_array( $link->get_http_code(), $status, true ) && ( $link->get_post_id() === $post_id )
						: in_array( $link->get_http_code(), $status, true );
				}
			);
		}

		$links = array_slice( $links, ( $page - 1 ) * $limit, $limit );
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

		// Get the reports.
		$this->items = $this->get_links();
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
				return sprintf(
					'<a href="%s">%s</a>',
					esc_url( get_edit_post_link( $item['link']->get_post_id() ) ),
					esc_html( get_the_title( $item['link']->get_post_id() ) )
				);
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
