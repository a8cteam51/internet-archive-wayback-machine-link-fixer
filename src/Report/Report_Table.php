<?php

/**
 * Handles the rendering and processing of the report table.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;

/**
 * The report table class.
 */
class Report_Table extends \WP_List_Table {

	// Table columns.
	public const COLUMN_CHECKBOX         = 'cb';
	public const COLUMN_POSTS            = 'report-post-id';
	public const COLUMN_IS_BROKEN        = 'report-is-broken';
	public const COLUMN_LINK_URL         = 'report-link-url';
	public const COLUMN_LINK_ARCHIVE     = 'report-link-archive';
	public const COLUMN_LINK_CHECKS      = 'report-link-checks';
	public const COLUMN_LINK_CHECKS_LAST = 'report-link-checks-last';



	/**
	 * Holds all the links from the logs.
	 *
	 * @since 1.1.0
	 *
	 * @var Link_Repository
	 */
	private $links;

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
	 * @param Link_Repository $links The links repository.
	 */
	public function __construct( Link_Repository $links ) {
		$this->links = $links;

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
	 * Number of links per page to render.
	 *
	 * @since 1.1.0
	 *
	 * @return integer
	 */
	private function get_links_per_page(): int {
		return 10;
	}




	/**
	 * Sets the pagination args.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	private function define_pagination_args() {
		// Get the total number of links.
		$link_count = count( $this->get_links( \PHP_INT_MAX, 1 ) );

		// Set the pagination args.
		$this->set_pagination_args(
			array(
				'total_items' => $link_count,
				'per_page'    => $this->get_links_per_page(),
				'total_pages' => absint( ceil( $link_count / $this->get_links_per_page() ) ),
			)
		);
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
			self::COLUMN_CHECKBOX         => '',
			self::COLUMN_LINK_URL         => __( 'URL', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_LINK_ARCHIVE     => __( 'Archived', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_IS_BROKEN        => __( 'Broken', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_LINK_CHECKS      => __( 'Checks', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_LINK_CHECKS_LAST => __( 'Last Check', 'wpcomsp_wayback_link_fixer' ),
		);
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
	 * @param integer $limit  The limit of links to return.
	 * @param integer $page   The page of links to return.
	 * @param array   $status The status of the links to return.
	 *
	 * @return array<Link>
	 */
	private function get_links( int $limit = 10, int $page = 1, array $status = array() ): array {
		return $this->links->query_links(
			$limit,
			$page,
			$status,
			$this->get_link_ids_from_url(),
			$this->get_sort_order_from_url()
		);
	}

	/**
	 * Get the sort order from URL.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	private function get_sort_order_from_url(): string {
		// if orderby is not set, return default.
		if ( ! array_key_exists( 'orderby', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			return Link_Repository::ORDER_ID_DESC;
		}

		$order_by = sanitize_text_field( $_GET['orderby'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible

		// If orderby is not a valid column, return default.
		if ( ! in_array( $order_by, array( self::COLUMN_LINK_URL, self::COLUMN_LINK_CHECKS_LAST ), true ) ) {
			return Link_Repository::ORDER_ID_DESC;
		}

		// Get the direction.
		$order = array_key_exists( 'order', $_GET ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			? sanitize_text_field( $_GET['order'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			: 'asc';

		// Return the correct order.
		switch ( $order ) {
			case 'asc':
				return self::COLUMN_LINK_URL === $order_by
					? Link_Repository::ORDER_URL_ASC
					: Link_Repository::ORDER_DATE_ASC;
			case 'desc':
				return self::COLUMN_LINK_URL === $order_by
					? Link_Repository::ORDER_URL_DESC
					: Link_Repository::ORDER_DATE_DESC;
			default:
				return Link_Repository::ORDER_ID_DESC;
		}
	}

	/**
	 * Get any link ids form the url.
	 *
	 * @since 1.2.0
	 *
	 * @return int[]
	 */
	private function get_link_ids_from_url(): array {
		return \array_key_exists( 'wlf_links', $_GET ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			? array_map(
				function ( $id ): int {
					return absint( $id );
				},
				\is_array( $_GET['wlf_links'] )   // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
					? $_GET['wlf_links']          // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
					: array( $_GET['wlf_links'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			)
			: array();
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
		$sortable              = $this->get_sortable_columns();
		$primary               = self::COLUMN_LINK_URL;
		$this->_column_headers = array( $columns, $hidden, $sortable, $primary );

		// Get the reports.
		$this->items = $this->get_links( $this->get_links_per_page(), $this->get_pagenum() );
	}

	/**
	 * Get sortable columns
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			self::COLUMN_LINK_URL         => array( self::COLUMN_LINK_URL, true ),
			self::COLUMN_LINK_CHECKS_LAST => array( self::COLUMN_LINK_CHECKS_LAST, true ),
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
			case self::COLUMN_LINK_URL:
				$url = Report_Page::get_page_url();

				return sprintf(
					'<a href="%s">%s</a>',
					esc_url(
						\add_query_arg(
							array(
								'wlf_link_id' => $item->get_id(),
							),
							$url
						)
					),
					// Trim url to 36 chars.
					esc_html( mb_strimwidth( $item->get_href(), 0, 54, '...' ) )
				);
			case self::COLUMN_LINK_ARCHIVE:
				return $item->has_archived_href()
					? sprintf(
						'<a href="%s" target="_blank"><span class="dashicons dashicons-yes-alt"></span></a>',
						esc_url( $item->get_archived_href() )
					)
					: '<span class="dashicons dashicons-dismiss"></span>';
			case self::COLUMN_IS_BROKEN:
				return $item->is_broken()
					? sprintf(
						'<span class="dashicons dashicons-yes-alt"></span>'
					)
					: '<span class="dashicons dashicons-dismiss"></span>';
			case self::COLUMN_LINK_CHECKS:
				return count( $item->get_checks() );

			case self::COLUMN_LINK_CHECKS_LAST:
				return $this->compile_details_cell( $item );
			default:
				return '';
		}
	}

	/**
	 * Compile the details cell for link.
	 *
	 * @param Link $item The link.
	 *
	 * @return string
	 */
	private function compile_details_cell( Link $item ): string {
		$last_check = $item->get_last_check();

		// If we have no checks, return N/a.
		if ( ! $last_check ) {
			return __( 'N/a', 'wpcomsp_wayback_link_fixer' );
		}

		return sprintf(
			// translators: %1$s is the last check date, %2$s is the last check http code.
			__( '%1$s with %2$s status', 'wpcomsp_wayback_link_fixer' ),
			$last_check['date']
				? \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $last_check['date'] )->format( get_option( 'date_format' ) )
				: __( 'Missing date', 'wpcomsp_wayback_link_fixer' ),
			$last_check
				? esc_html( $last_check['http_code'] )
				: __( 'No HTTP Code', 'wpcomsp_wayback_link_fixer' )
		);
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function no_items() {
		echo esc_html__( 'No links have been created yet.', 'wpcomsp_wayback_link_fixer' );
	}
}
