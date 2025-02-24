<?php

/**
 * Handles the rendering and processing of the report table.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report;

use DateTimeImmutable;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Page;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Action\Link_Check_Action;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Action\Validate_Link_Action;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Action\Link_New_Snapshot_Action;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Action\Link_Latest_Snapshot_Action;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Util\List_Table_Action_Notification_Cache;

/**
 * The report table class.
 */
class Report_Table extends \WP_List_Table {

	// Table columns.
	public const COLUMN_CHECKBOX         = 'cb';
	public const COLUMN_POSTS            = 'report-post-id';
	public const COLUMN_LINK_HEALTH      = 'report-is-broken';
	public const COLUMN_LINK_URL         = 'report-link-url';
	public const COLUMN_LINK_ARCHIVE     = 'report-link-archive';
	public const COLUMN_LINK_CHECKS      = 'report-link-checks';
	public const COLUMN_LINK_CHECKS_LAST = 'report-link-checks-last';
	public const COLUMN_LINK_EXCLUDE     = 'report-link-exclude';

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
		// Populate the parent class properties.
		parent::__construct(
			array(
				'singular' => 'report',
				'plural'   => 'reports',
				'ajax'     => true,
			)
		);

		$this->links = $links;

		// Attempt to get any cached notifications.
		$this->populate_cached_notifications();
	}

	/**
	 * Populated any cached notifications.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function populate_cached_notifications(): void {
		// Check if 'wlf_completed_action' is set in url.
		if ( ! array_key_exists( 'wlf_completed_action', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			return;
		}

		// Check the cache key is set in url.
		if ( ! array_key_exists( 'wlf_notification', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			return;
		}

		$key    = sanitize_text_field( $_GET['wlf_notification'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
		$action = sanitize_text_field( $_GET['wlf_completed_action'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible

		// Get the cache and populate the notices.
		$cache         = new List_Table_Action_Notification_Cache( $action );
		$this->notices = $cache->get( $key );
	}

	/**
	 * Number of links per page to render.
	 *
	 * @since 1.1.0
	 *
	 * @return integer
	 */
	private function get_links_per_page(): int {
		$option = get_current_screen()->get_option( 'per_page' );

		// If the option is not set, return 10.
		if ( ! $option ) {
			return 10;
		}

		$from_meta = get_user_meta( get_current_user_id(), $option['option'], true );

		return is_numeric( $from_meta )
			? absint( $from_meta )
			: absint( $option['default'] );
	}

	/**
	 * Set the column checkbox.
	 *
	 * @param Link $link The link being rendered on this row.
	 *
	 * @return string
	 */
	public function column_cb( $link ): string {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'wlf_link_action',
			$link->get_id()
		);
	}

	/**
	 * Process the bulk actions.
	 *
	 * @return void
	 */
	public function process_bulk_action(): void {

		// If action or action2 not set in url, return.
		if ( ! array_key_exists( 'action', $_REQUEST ) && ! array_key_exists( 'action2', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			return;
		}

		// Verify the nonce and referrer.
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) && check_admin_referer( 'bulk-' . $this->_args['plural'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			// Add a notice.
			$this->notices[] = array(
				'message' => __( 'Something went wrong, please try again', 'wpcomsp_wayback_link_fixer' ),
				'type'    => 'error',
			);

			return;
		}

		$current_action = $this->current_action();

		if ( ! in_array( $current_action, array( 'updated_snapshot', 'new_snapshot', 'validate', 'check' ), true ) ) {
			return;
		}

		$links = array_key_exists( 'wlf_link_action', $_GET ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			? array_map( 'absint', $_GET['wlf_link_action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			: array();

		if ( empty( $links ) ) {
			$this->notices[] = array(
				'message' => __( 'No links selected.', 'wpcomsp_wayback_link_fixer' ),
				'type'    => 'error',
			);
			return;
		}

		switch ( $current_action ) {
			case 'check':
				$this->process_check_action( $links );
				$this->redirect_after_action();
				break;

			case 'updated_snapshot':
				$this->process_update_latest_snapshot( $links );
				$this->redirect_after_action();
				break;

			case 'new_snapshot':
				$this->process_new_snapshot( $links );
				$this->redirect_after_action();
				break;

			case 'validate':
				$this->process_excluded_links( $links );
				$this->redirect_after_action();
				break;

			case 'no-op':
				$this->notices[] = array(
					'message' => __( 'No action selected, are services offline?', 'wpcomsp_wayback_link_fixer' ),
					'type'    => 'error',
				);
				$this->redirect_after_action();
				break;

			default:
				// Generate an error notification unknown action.
				$this->notices[] = array(
					'message' => __( 'Unknown action.', 'wpcomsp_wayback_link_fixer' ),
					'type'    => 'error',
				);
				break;
		}
	}

	/**
	 * Redirect after action is processed.
	 *
	 * @return never|void
	 */
	public function redirect_after_action(): void {

		// Cache the notifications.
		$cache = new List_Table_Action_Notification_Cache( $this->current_action() );
		foreach ( $this->notices as $notice ) {
			$cache->push( $notice );
		}
		$cache_key = $cache->save();

		// Redirect to the same page with all actions removed.
		$redirect = remove_query_arg( array( 'action', 'action2', 'wlf_link_action', 'wlf_links', '_wpnonce', '_wp_http_referer' ) );

		$redirect = add_query_arg( 'wlf_notification', $cache_key, $redirect );
		$redirect = add_query_arg( 'wlf_completed_action', esc_attr( $this->current_action() ), $redirect );

		// Get the previous url params.
		$params = $this->get_previous_url_params();

		// If we have a post id, add it and its links to the redirect.
		if ( array_key_exists( 'wlf_filtered_post_id', $params ) ) {
			$redirect = add_query_arg( 'wlf_filtered_post_id', absint( $params['wlf_filtered_post_id'] ), $redirect );
		}

		// Add to the redirect the current page.
		$url = home_url() . $redirect;

		// Redirect to the page using JS as page already loaded headers.
		echo "<script>window.location = '$url';</script>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, this is just a redirect.
		exit;
	}

	/**
	 * Get the previous url params, from the referrer.
	 *
	 * @return array<string, mixed>
	 */
	private function get_previous_url_params(): array {
		$referrer = wp_get_referer();
		$referrer = wp_parse_url( $referrer );
		$query    = $referrer['query'] ?? '';
		parse_str( $query, $params );
		return $params;
	}

	/**
	 * Process the Link Checking action.
	 *
	 * @param integer[] $links The links to check.
	 *
	 * @return void
	 */
	private function process_check_action( array $links ): void {
		$checker = new Link_Check_Action();

		foreach ( $links as $link_id ) {
			$results = $checker->check_link( $link_id );

			// If we have no link, add a notice.
			if ( ! $results['link'] ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %d is the link id.
						__( 'Link not found with id:%d', 'wpcomsp_wayback_link_fixer' ),
						absint( $link_id )
					),
					'type'    => 'error',
				);
				continue;
			}

			// If the link was not updated
			if ( ! $results['checked'] ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %s is the link url.
						__( 'It was not possible to check %s', 'wpcomsp_wayback_link_fixer' ),
						esc_html( wpcomsp_wayback_link_fixer_trim_string( $results['link']->get_href(), 54 ) )
					),
					'type'    => 'error',
				);
				continue;
			}

			$last_check = $results['link']->get_last_check();
			$link_url   = $results['link']->get_href();

			// If we have no last check, add a notice.
			if ( ! $last_check ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %s is the link url.
						__( 'Link %s has no checks.', 'wpcomsp_wayback_link_fixer' ),
						esc_html( wpcomsp_wayback_link_fixer_trim_string( $link_url, 54 ) )
					),
					'type'    => 'error',
				);
				continue;
			}

			// Add a success notice.
			$this->notices[] = array(
				'message' => sprintf(
					// translators: %1$s is the link url, %2$s is the last check date, %3$s is the last check http code.
					__( 'Link %1$s checked successfully on %2$s with %3$s status', 'wpcomsp_wayback_link_fixer' ),
					esc_html( wpcomsp_wayback_link_fixer_trim_string( $link_url, 54 ) ),
					DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $last_check['date'] )->format( get_option( 'date_format' ) ),
					esc_html( $last_check['http_code'] )
				),
				'type'    => 'success',
			);
		}
	}

	/**
	 * Process the Snapshot Update action.
	 *
	 * @param integer[] $links The links to be updated to the latest snapshot.
	 *
	 * @return void
	 */
	private function process_update_latest_snapshot( array $links ): void {
		$action = new Link_Latest_Snapshot_Action();

		// Itrerates over the links and rescan them.
		foreach ( $links as $link_id ) {
			$result = $action->rescan_link( absint( $link_id ) );

			// If we have no link, add a notice.
			if ( null === $result['link'] ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %d is the link id.
						__( 'Link not found with id:%d', 'wpcomsp_wayback_link_fixer' ),
						absint( $link_id )
					),
					'type'    => 'error',
				);
				continue;
			}

			// If an internet archived link, show the message.
			if ( wpcomsp_wayback_link_fixer_is_archive_link( $result['link']->get_href() ) ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %s is the link url.
						__( 'Link %s is already an archived link.', 'wpcomsp_wayback_link_fixer' ),
						esc_html( wpcomsp_wayback_link_fixer_trim_string( $result['link']->get_href(), 54 ) )
					),
					'type'    => 'notice',
				);
				continue;
			}

			// If no archived link was found.
			if ( ! $result['found'] ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %s is the link url.
						__( 'No archived link found for %s', 'wpcomsp_wayback_link_fixer' ),
						esc_html( wpcomsp_wayback_link_fixer_trim_string( $result['link']->get_href(), 54 ) )
					),
					'type'    => 'error',
				);
				continue;
			}

			// If the link was not updated
			if ( ! $result['updated'] ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %s is the link url.
						__( 'It was not possible to update %s, the latest archive link is the same', 'wpcomsp_wayback_link_fixer' ),
						esc_html( wpcomsp_wayback_link_fixer_trim_string( $result['link']->get_href(), 54 ) )
					),
					'type'    => 'notice',
				);
				continue;
			}

			// We have a success notice.
			$this->notices[] = array(
				'message' => sprintf(
					// translators: %s is the link url.
					__( 'Link %s updated successfully', 'wpcomsp_wayback_link_fixer' ),
					esc_html( wpcomsp_wayback_link_fixer_trim_string( $result['link']->get_href(), 54 ) )
				),
				'type'    => 'success',
			);
		}
	}

	/**
	 * Process the Link Update action.
	 *
	 * @param integer[] $links The links to update.
	 *
	 * @return void
	 */
	private function process_new_snapshot( array $links ): void {
		$action = new Link_New_Snapshot_Action();
		//Iterate over the links and update them.
		foreach ( $links as $link_id ) {
			$result = $action->create_new_snapshot( absint( $link_id ) );

			// If we have no link, add a notice.
			if ( null === $result['link'] ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %d is the link id.
						__( 'Link not found with id:%d', 'wpcomsp_wayback_link_fixer' ),
						absint( $link_id )
					),
					'type'    => 'error',
				);
				continue;
			}

			// If we dont have a job id, add error and include the  mesage.
			if ( ! $result['job_id'] ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %s is the link url.
						__( 'Link %1$s could not have a new snapshot created: %2$s', 'wpcomsp_wayback_link_fixer' ),
						wpcomsp_wayback_link_fixer_trim_string( $result['link']->get_href(), 54 ),
						esc_html( $result['message'] )
					),
					'type'    => 'error',
				);
				continue;
			}

			// Show a success notice.
			$this->notices[] = array(
				'message' => sprintf(
					// translators: %s is the link url.
					__( 'Link %s added to the queue for updating, please wait.', 'wpcomsp_wayback_link_fixer' ),
					wpcomsp_wayback_link_fixer_trim_string( $result['link']->get_href(), 54 )
				),
				'type'    => 'success',
			);
		}
	}

	/**
	 * Process the Link Exclusion action.
	 *
	 * @param integer[] $links The links to exclude.
	 *
	 * @return void
	 */
	private function process_excluded_links( array $links ): void {
		$action = new Validate_Link_Action();

		foreach ( $links as $link_id ) {
			$result = $action->validate_link( absint( $link_id ) );

			// If we have no link, add a notice.
			if ( null === $result['link'] ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %d is the link id.
						__( 'Link not found with id:%d', 'wpcomsp_wayback_link_fixer' ),
						absint( $link_id )
					),
					'type'    => 'error',
				);
				continue;
			}

			// Add a success notice.
			$this->notices[] = array(
				'message' => sprintf(
					// translators: %s is the link url.
					__( 'Validating %s to ensure we can check its current status', 'wpcomsp_wayback_link_fixer' ),
					esc_html( wpcomsp_wayback_link_fixer_trim_string( $result['link']->get_href(), 54 ) )
				),
				'type'    => 'success',
			);
		}
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
			self::COLUMN_CHECKBOX         => '<input type="checkbox" />',
			self::COLUMN_LINK_URL         => __( 'URL', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_LINK_ARCHIVE     => __( 'Has Archived Link', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_LINK_HEALTH      => __( 'Link Health', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_LINK_CHECKS      => __( 'Check Count', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_LINK_CHECKS_LAST => __( 'Last Check', 'wpcomsp_wayback_link_fixer' ),
			self::COLUMN_LINK_EXCLUDE     => __( 'Link Excluded', 'wpcomsp_wayback_link_fixer' ),
		);
	}

	/**
	 * Adds the status filter.
	 *
	 * @since 1.2.0
	 *
	 * @param string $which The location of the filter.
	 *
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		// If not top, return.
		if ( 'top' !== $which ) {
			return;
		}
		?>
		<label for="wlf_status" class="screen-reader-text"><?php esc_html_e( 'Filter by status', 'wpcomsp_wayback_link_fixer' ); ?></label>
		<select name="wlf_status" id="wlf_status">
			<option value="all"><?php esc_html_e( 'Show valid and broken links', 'wpcomsp_wayback_link_fixer' ); ?></option>
			<?php
			$statuses = array(
				Link_Repository::LINK_STATUS_BROKEN => __( 'Show Broken links ', 'wpcomsp_wayback_link_fixer' ),
				Link_Repository::LINK_STATUS_OK     => __( 'Show Valid links', 'wpcomsp_wayback_link_fixer' ),
			);
			foreach ( $statuses as $status => $label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $status ),
					selected( $this->get_status_from_url(), $status, false ),
					esc_html( $label )
				);
			}
			?>
		</select>

		<label for="wlf_has_archive" class="screen-reader-text"><?php esc_html_e( 'Filter by archived link', 'wpcomsp_wayback_link_fixer' ); ?></label>
		<select name="wlf_has_archive" id="wlf_has_archive">
			<option value=""><?php esc_html_e( 'Show with or without archived link', 'wpcomsp_wayback_link_fixer' ); ?></option>
			<?php
			$has_archive = array(
				Link_Repository::LINK_HAS_ARCHIVE => __( 'Show links with archived link', 'wpcomsp_wayback_link_fixer' ),
				Link_Repository::LINK_NO_ARCHIVE  => __( 'Show links without archived link', 'wpcomsp_wayback_link_fixer' ),
			);
			foreach ( $has_archive as $archive => $label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $archive ),
					selected( $this->get_archived_status_from_url(), $archive, false ),
					esc_html( $label )
				);
			}
			?>
		</select>


		<label for="wlf_is_excluded" class="screen-reader-text"><?php esc_html_e( 'Filter by excluded', 'wpcomsp_wayback_link_fixer' ); ?></label>
		<select name="wlf_is_excluded" id="wlf_is_excluded">
			<option value=""><?php esc_html_e( 'Show with or without excluded link', 'wpcomsp_wayback_link_fixer' ); ?></option>
			<?php
			$has_archive = array(
				Link_Repository::LINK_IS_EXCLUDED  => __( 'Show links that are excluded', 'wpcomsp_wayback_link_fixer' ),
				Link_Repository::LINK_NOT_EXCLUDED => __( 'Show links that are not excluded', 'wpcomsp_wayback_link_fixer' ),
			);
			foreach ( $has_archive as $archive => $label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $archive ),
					selected( $this->get_excluded_status_from_url(), $archive, false ),
					esc_html( $label )
				);
			}
			?>
		</select>

		<?php if ( array_key_exists( 'wlf_filtered_post_id', $_GET ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible ?>
			<input type="hidden" name="wlf_filtered_post_id" value="<?php echo esc_attr( $_GET['wlf_filtered_post_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible ?>" />
		<?php endif; ?>

		<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wpcomsp_wayback_link_fixer' ); ?>"  />

		<?php
	}

	/**
	 * Get the status filter values from url if set.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	private function get_status_from_url(): ?string {
		return array_key_exists( 'wlf_status', $_GET ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			? sanitize_text_field( $_GET['wlf_status'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			: '';
	}

	/**
	 * Get the excluded status filter values from url if set.
	 *
	 * @return string
	 */
	private function get_excluded_status_from_url(): ?string {
		return array_key_exists( 'wlf_is_excluded', $_GET ) && '' !== $_GET['wlf_is_excluded'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			? sanitize_text_field( $_GET['wlf_is_excluded'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			: null;
	}

	/**
	 * Gets the archived status filter values from url if set.
	 *
	 * @return string
	 */
	private function get_archived_status_from_url(): ?string {
		return array_key_exists( 'wlf_has_archive', $_GET ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			? sanitize_text_field( $_GET['wlf_has_archive'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			: '';
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
			array( $this->get_status_from_url() ),
			$this->get_link_ids_from_url(),
			array( $this->get_archived_status_from_url() ),
			$this->get_sort_order_from_url(),
			$this->get_search_term(),
			null,
			is_null( $this->get_excluded_status_from_url() ) ? null : boolval( $this->get_excluded_status_from_url() )
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
	 * Get the bulk actions.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, string>
	 */
	protected function get_bulk_actions(): array {
		return array(
			'updated_snapshot' => __( 'Update To Latest Snapshot', 'wpcomsp_wayback_link_fixer' ),
			'new_snapshot'     => __( 'Create New Snapshot', 'wpcomsp_wayback_link_fixer' ),
			'check'            => __( 'Check Link Status', 'wpcomsp_wayback_link_fixer' ),
			'validate'         => __( 'Verify Link Allows Checking', 'wpcomsp_wayback_link_fixer' ),
		);
	}

	/**
	 * Get any link ids form the url.
	 *
	 * @since 1.2.0
	 *
	 * @return int[]
	 */
	private function get_link_ids_from_url(): array {

		// If we have the post id in the url, return the links ids.
		if ( ! array_key_exists( 'wlf_filtered_post_id', $_GET ) ) { // phpcs:ignore
			return array();
		}

		$post_id = absint( $_GET['wlf_filtered_post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible

		$links = $this->links->get_links_for_post( $post_id );

		return array_map(
			function ( $link ): int {
				return absint( $link->get_id() );
			},
			$links->get_links()
		);
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
		$this->items = $this->get_links(
			$this->get_links_per_page(),
			$this->get_pagenum(),
			array( $this->get_status_from_url() ),
			$this->get_search_term()
		);
	}

	/**
	 * Get the defined search term if set.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	private function get_search_term(): string {
		return array_key_exists( 's', $_GET ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			? sanitize_text_field( $_GET['s'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			: '';
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
					esc_url( add_query_arg( array( 'wlf_link_id' => $item->get_id() ), $url ) ),
					$this->compile_link_name( $item )
				);
			case self::COLUMN_LINK_ARCHIVE:
				return $item->has_archived_href()
					? sprintf(
						'<a href="%s" target="_blank"><span class="dashicons dashicons-yes-alt"></span></a>',
						$item->get_archived_href()
					)
					: '<span class="dashicons dashicons-dismiss"></span>';
			case self::COLUMN_LINK_HEALTH:
				return sprintf(
					'<span class="%s"><img src="%s" alt="%s" style="width:20px"/></span>',
					$item->is_broken()
						? 'wlf-broken'
						: 'wlf-not-broken',
					wpcomsp_wayback_link_fixer_get_image_asset_url(
						$item->is_broken()
							? 'error.svg'
							: 'heart.svg'
					),
					$item->is_broken()
						? esc_html__( 'Broken', 'wpcomsp_wayback_link_fixer' )
						: esc_html__( 'Not Broken', 'wpcomsp_wayback_link_fixer' )
				);
			case self::COLUMN_LINK_CHECKS:
				return count( $item->get_checks() );

			case self::COLUMN_LINK_CHECKS_LAST:
				return $this->compile_details_cell( $item );
			case self::COLUMN_LINK_EXCLUDE:
				return $item->is_excluded()
					? '<span class="dashicons dashicons-yes-alt"></span>'
					: '<span class="dashicons dashicons-dismiss"></span>';
			case 'cb':
				return sprintf(
					'<input type="checkbox" name="wlf_links[]" value="%d" />',
					$item->get_id()
				);
			default:
				return '';
		}
	}

	/**
	 * Compiles the links name/title for the table.
	 *
	 * @param Link $item The link.
	 *
	 * @return string
	 */
	private function compile_link_name( Link $item ): string {
		return sprintf(
			'%s <a href="%s" target="_blank">%s</a>',
			esc_html( wpcomsp_wayback_link_fixer_trim_string( $item->get_href(), 54 ) ),
			$item->get_href(),
			'<span class="dashicons dashicons-external"></span>'
		);
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
				? DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $last_check['date'] )->format( get_option( 'date_format' ) )
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
