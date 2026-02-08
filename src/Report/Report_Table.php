<?php

/**
 * Handles the rendering and processing of the report table.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Report;

use DateTimeImmutable;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Action\Link_Check_Action;
use Internet_Archive\Wayback_Machine_Link_Fixer\Action\Validate_Link_Action;
use Internet_Archive\Wayback_Machine_Link_Fixer\Action\Link_New_Snapshot_Action;
use Internet_Archive\Wayback_Machine_Link_Fixer\Action\Link_Latest_Snapshot_Action;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Create_New_Snapshot_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Util\List_Table_Action_Notification_Cache;
use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Report_Page;

defined( 'ABSPATH' ) || exit;
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
	protected array $notices = array();

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
	 * Displays the bulk actions dropdown.
	 *
	 * @since 3.1.0
	 *
	 * @param string $which The location of the bulk actions: Either 'top' or 'bottom'.
	 *                      This is designated as optional for backward compatibility.
	 *
	 * @return void
	 */
	protected function bulk_actions( $which = '' ) {
		\ob_start();
		parent::bulk_actions( $which );
		$bulk_actions = \ob_get_clean();

		$help_icon = '<span id="iawmlf_help_info_bulk_actions" class="iawmlf_bulk_actions_trigger dashicons dashicons-editor-help"></span>';

		$bulk_actions = preg_replace(
			'/<input\b[^>]*\bid\s*=\s*["\']doaction["\'][^>]*>/i',
			'$0' . $help_icon,
			$bulk_actions
		);
		$allowed_html = array(
			'label'  => array(
				'for'   => array(),
				'class' => array(),
			),
			'select' => array(
				'name'  => array(),
				'id'    => array(),
				'class' => array(),
			),
			'option' => array(
				'value' => array(),
			),
			'input'  => array(
				'type'  => array(),
				'name'  => array(),
				'id'    => array(),
				'class' => array(),
				'value' => array(),
			),
			'span'   => array(
				'id'    => array(),
				'class' => array(),
			),
		);

		echo wp_kses( $bulk_actions, $allowed_html );
	}

	/**
	 * Populated any cached notifications.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	private function populate_cached_notifications(): void {
		// Check if 'iawmlf_completed_action' is set in url.
		if ( ! array_key_exists( 'iawmlf_completed_action', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			return;
		}

		// Check the cache key is set in url.
		if ( ! array_key_exists( 'iawmlf_notification', $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			return;
		}

		$key    = sanitize_text_field( wp_unslash( $_GET['iawmlf_notification'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
		$action = sanitize_text_field( wp_unslash( $_GET['iawmlf_completed_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible

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
			'iawmlf_link_action',
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

		// Check if _wpnonce is not set or is not valid.
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-' . $this->_args['plural'] ) || ! check_admin_referer( 'bulk-' . $this->_args['plural'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			// Add a notice.
			$this->notices[] = array(
				'message' => __( 'Something went wrong, please try again', 'internet-archive-wayback-machine-link-fixer' ),
				'type'    => 'error',
			);

			return;
		}

		$current_action = $this->current_action();

		if ( ! in_array( $current_action, array( 'updated_snapshot', 'new_snapshot', 'validate', 'check' ), true ) ) {
			return;
		}

		$links = array_key_exists( 'iawmlf_link_action', $_GET ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			? array_map( 'absint', $_GET['iawmlf_link_action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			: array();

		if ( empty( $links ) ) {
			$this->notices[] = array(
				'message' => __( 'No links selected.', 'internet-archive-wayback-machine-link-fixer' ),
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
					'message' => __( 'No action selected, are services offline?', 'internet-archive-wayback-machine-link-fixer' ),
					'type'    => 'error',
				);
				$this->redirect_after_action();
				break;

			default:
				// Generate an error notification unknown action.
				$this->notices[] = array(
					'message' => __( 'Unknown action.', 'internet-archive-wayback-machine-link-fixer' ),
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
		$redirect = remove_query_arg( array( 'action', 'action2', 'iawmlf_link_action', 'iawmlf_links', '_wpnonce', '_wp_http_referer' ) );

		$redirect = add_query_arg( 'iawmlf_notification', $cache_key, $redirect );
		$redirect = add_query_arg( 'iawmlf_completed_action', esc_attr( $this->current_action() ), $redirect );

		// Get the previous url params.
		$params = $this->get_previous_url_params();

		// If we have a post id, add it and its links to the redirect.
		if ( array_key_exists( 'iawmlf_filtered_post_id', $params ) ) {
			$redirect = add_query_arg( 'iawmlf_filtered_post_id', absint( $params['iawmlf_filtered_post_id'] ), $redirect );
		}

		// Add to the redirect the current page.
		$url = home_url() . $redirect;

		// Redirect to the page using JS as page already loaded headers.
		printf( '<script>window.location = %s;</script>', wp_json_encode( $url ) );
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
						__( 'Link not found with id:%d', 'internet-archive-wayback-machine-link-fixer' ),
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
						__( 'It was not possible to check %s', 'internet-archive-wayback-machine-link-fixer' ),
						esc_html( iawmlf_trim_string( $results['link']->get_href(), 54 ) )
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
						__( 'Link %s has no checks.', 'internet-archive-wayback-machine-link-fixer' ),
						esc_html( iawmlf_trim_string( $link_url, 54 ) )
					),
					'type'    => 'error',
				);
				continue;
			}

			// Add a success notice.
			$this->notices[] = array(
				'message' => sprintf(
					// translators: %1$s is the link url, %2$s is the last check date, %3$s is the last check http code.
					__( 'Link %1$s checked successfully on %2$s with %3$s status', 'internet-archive-wayback-machine-link-fixer' ),
					esc_html( iawmlf_trim_string( $link_url, 54 ) ),
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
						__( 'Link not found with id:%d', 'internet-archive-wayback-machine-link-fixer' ),
						absint( $link_id )
					),
					'type'    => 'error',
				);
				continue;
			}

			// If an internet archived link, show the message.
			if ( iawmlf_is_archive_link( $result['link']->get_href() ) ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %s is the link url.
						__( 'Link %s is already an archived link.', 'internet-archive-wayback-machine-link-fixer' ),
						esc_html( iawmlf_trim_string( $result['link']->get_href(), 54 ) )
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
						__( 'No archived version found for %s.', 'internet-archive-wayback-machine-link-fixer' ),
						esc_html( iawmlf_trim_string( $result['link']->get_href(), 54 ) )
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
						__( 'It was not possible to update %s, the latest archive link is the same', 'internet-archive-wayback-machine-link-fixer' ),
						esc_html( iawmlf_trim_string( $result['link']->get_href(), 54 ) )
					),
					'type'    => 'notice',
				);
				continue;
			}

			// We have a success notice.
			$this->notices[] = array(
				'message' => sprintf(
					// translators: %s is the link url.
					__( 'Archived URL for %s updated successfully.', 'internet-archive-wayback-machine-link-fixer' ),
					esc_html( iawmlf_trim_string( $result['link']->get_href(), 54 ) )
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
		// If we have more than 5 links, we will process them in a batch.
		if ( count( $links ) > 5 ) {
			$this->delay_new_snapshot_processing( $links );
			return;
		}

		// Iterate over the links and update them.
		foreach ( $links as $link_id ) {
			$result = $action->create_new_snapshot( absint( $link_id ) );

			// If we have no link, add a notice.
			if ( null === $result['link'] ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %d is the link id.
						__( 'Link not found with id:%d', 'internet-archive-wayback-machine-link-fixer' ),
						absint( $link_id )
					),
					'type'    => 'error',
				);
				continue;
			}

			// If we don't have a job id, add error and include the message.
			if ( ! $result['job_id'] ) {
				$this->notices[] = array(
					'message' => sprintf(
						// translators: %s is the link url.
						__( 'Link %1$s could not have a new snapshot created: %2$s', 'internet-archive-wayback-machine-link-fixer' ),
						iawmlf_trim_string( $result['link']->get_href(), 54 ),
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
					__( 'Link %s added to the queue and a new snapshot will be created and added as the archived URL in the coming minutes.', 'internet-archive-wayback-machine-link-fixer' ),
					iawmlf_trim_string( $result['link']->get_href(), 54 )
				),
				'type'    => 'success',
			);
		}
	}

	/**
	 * Batch process new snapshot requests.
	 *
	 * @since 1.3.0
	 *
	 * @param integer[] $links The links to process.
	 *
	 * @return void
	 */
	private function delay_new_snapshot_processing( array $links ): void {
		$link_not_found = array();
		$own_links      = array();
		$archived_links = array();
		$added_links    = array();

		//Iterate over the links and update them.
		foreach ( $links as $link_id ) {
			// Check the link exists.
			$link = $this->links->find_by_id( $link_id );

			if ( ! $link ) {
				// Add a notice.
				$link_not_found[] = absint( $link_id );
				continue;
			}

			if ( iawmlf_is_archive_link( $link->get_href() ) ) {
				// Add a notice.
				$archived_links[] = $link;
				continue;
			}

			if ( iawmlf_is_current_site_link( $link->get_href() ) ) {
				// Add a notice.
				$own_links[] = $link;
				continue;
			}

			// Add the task to the queue.
			Create_New_Snapshot_Event::add_to_queue( $link_id );
			$added_links[] = $link;
		}

		// Create the report to show.
		$notice = '';

		$error_icon = '<span style="color: red;">❌</span>';

		// If we have any where the link could not be found, add a notice.
		if ( ! empty( $link_not_found ) ) {
			$notice .= sprintf(
				// translators: %s is the icon for error, %d is the number of links that could not be found.
				__( '%1$s - %2$d links could not be found.', 'internet-archive-wayback-machine-link-fixer' ),
				$error_icon,
				count( $link_not_found )
			);
		}

		// If we have any where the link is an archived link, add a notice.
		if ( ! empty( $archived_links ) ) {
			$notice .= sprintf(
				// translators: %s is the icon for error, %d is the number of links that are archived.
				__( '%1$s - %2$d links that are already snapshots and will be skipped', 'internet-archive-wayback-machine-link-fixer' ),
				$error_icon,
				count( $archived_links )
			);

			// List the urls.
			$notice .= '<ul>';
			foreach ( $archived_links as $link ) {
				$notice .= sprintf(
					'<li>%s</li>',
					iawmlf_trim_string( $link->get_href(), 54 )
				);
			}
			$notice .= '</ul>';
		}

		// If we have any where the link is an own link, add a notice.
		if ( ! empty( $own_links ) ) {
			$notice .= sprintf(
				// translators: %s is the icon for error, , %d is the number of links that are own links.
				__( '%1$s - %2$d links are from this site and will not be processed. Please enable the Auto Archiver to archive your own content.', 'internet-archive-wayback-machine-link-fixer' ),
				$error_icon,
				count( $own_links )
			);

			// List the urls.
			$notice .= '<ul>';
			foreach ( $own_links as $link ) {
				$notice .= sprintf(
					'<li>%s</li>',
					iawmlf_trim_string( $link->get_href(), 54 )
				);
			}
			$notice .= '</ul>';
		}

		$success_notice = __( '✅ - The following links were added to the queue for a new snapshot to be created:', 'internet-archive-wayback-machine-link-fixer' );
		// If we have any where the link was added to the queue, add a notice.
		if ( ! empty( $added_links ) ) {

			// List the urls.
			$success_notice .= '<ul>';
			foreach ( $added_links as $link ) {
				$success_notice .= sprintf(
					'<li>%s</li>',
					iawmlf_trim_string( $link->get_href(), 54 )
				);
			}
			$success_notice .= '</ul>';
			$success_notice .= '<p>' . __( 'Snapshots are being queued for processing and will appear soon. Thanks for your patience!', 'internet-archive-wayback-machine-link-fixer' ) . '</p>';
		}

		// Add the notices.
		if ( '' !== $notice ) {
			$this->notices[] = array(
				'message' => $notice,
				'type'    => 'error',
			);
		}
		// Add the success notice.
		$this->notices[] = array(
			'message' => $success_notice,
			'type'    => 'success',
		);
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
						__( 'Link not found with id:%d', 'internet-archive-wayback-machine-link-fixer' ),
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
					__( 'Validating %s to ensure we can check its current status', 'internet-archive-wayback-machine-link-fixer' ),
					esc_html( iawmlf_trim_string( $result['link']->get_href(), 54 ) )
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
				<p><?php echo \wp_kses_post( $notice['message'] ); ?></p>
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
		$columns = array(
			self::COLUMN_CHECKBOX         => '<input type="checkbox" />',
			self::COLUMN_LINK_URL         => __( 'URL', 'internet-archive-wayback-machine-link-fixer' ),
			self::COLUMN_LINK_ARCHIVE     => __( 'Archive Status', 'internet-archive-wayback-machine-link-fixer' ),
			self::COLUMN_LINK_HEALTH      => __( 'Link Health', 'internet-archive-wayback-machine-link-fixer' ),
			self::COLUMN_LINK_CHECKS      => __( 'Times Checked', 'internet-archive-wayback-machine-link-fixer' ),
			self::COLUMN_LINK_CHECKS_LAST => __( 'Last Check', 'internet-archive-wayback-machine-link-fixer' ),
		);

		if ( Settings::show_link_table_debug_data() ) {
			$exclude = array(
				self::COLUMN_LINK_EXCLUDE => __( 'Link Excluded', 'internet-archive-wayback-machine-link-fixer' ),
			);

			// Add exclude after health.
			$health_index = array_search( self::COLUMN_LINK_HEALTH, array_keys( $columns ), true );
			if ( false !== $health_index ) {
				$columns = array_merge(
					array_slice( $columns, 0, $health_index + 1, true ),
					$exclude,
					array_slice( $columns, $health_index + 1, null, true )
				);
			} else {
				// Add exclude at the end.
				$columns = array_merge( $columns, $exclude );
			}
		}
		return $columns;
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
		<label for="iawmlf_status" class="screen-reader-text"><?php esc_html_e( 'Filter by status', 'internet-archive-wayback-machine-link-fixer' ); ?></label>
		<select name="iawmlf_status" id="iawmlf_status">
			<option value="all"><?php esc_html_e( 'Show valid and broken links', 'internet-archive-wayback-machine-link-fixer' ); ?></option>
			<?php
			$statuses = array(
				Link_Repository::LINK_STATUS_BROKEN => __( 'Show broken links', 'internet-archive-wayback-machine-link-fixer' ),
				Link_Repository::LINK_STATUS_OK     => __( 'Show valid links', 'internet-archive-wayback-machine-link-fixer' ),
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

		<label for="iawmlf_has_archive" class="screen-reader-text"><?php esc_html_e( 'Filter by archived link', 'internet-archive-wayback-machine-link-fixer' ); ?></label>
		<select name="iawmlf_has_archive" id="iawmlf_has_archive">
			<option value=""><?php esc_html_e( 'Show with or without archived link', 'internet-archive-wayback-machine-link-fixer' ); ?></option>
			<?php
			$has_archive = array(
				Link_Repository::LINK_HAS_ARCHIVE => __( 'Show links with archived version', 'internet-archive-wayback-machine-link-fixer' ),
				Link_Repository::LINK_NO_ARCHIVE  => __( 'Show links without archived version', 'internet-archive-wayback-machine-link-fixer' ),
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

		<?php if ( Settings::show_link_table_debug_data() ) : ?>
			<label for="iawmlf_is_excluded" class="screen-reader-text"><?php esc_html_e( 'Filter by excluded', 'internet-archive-wayback-machine-link-fixer' ); ?></label>
			<select name="iawmlf_is_excluded" id="iawmlf_is_excluded">
				<option value=""><?php esc_html_e( 'Show with or without excluded link', 'internet-archive-wayback-machine-link-fixer' ); ?></option>
				<?php
				$has_archive = array(
					Link_Repository::LINK_IS_EXCLUDED  => __( 'Show links that are excluded', 'internet-archive-wayback-machine-link-fixer' ),
					Link_Repository::LINK_NOT_EXCLUDED => __( 'Show links that are not excluded', 'internet-archive-wayback-machine-link-fixer' ),
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
		<?php endif; ?>

		<?php if ( array_key_exists( 'iawmlf_filtered_post_id', $_GET ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible ?>
			<input type="hidden" name="iawmlf_filtered_post_id" value="<?php echo absint( \sanitize_text_field( wp_unslash( $_GET['iawmlf_filtered_post_id'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible ?>" />
		<?php endif; ?>

		<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'internet-archive-wayback-machine-link-fixer' ); ?>"  />

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
		return array_key_exists( 'iawmlf_status', $_GET ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			? sanitize_text_field( wp_unslash( $_GET['iawmlf_status'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			: '';
	}

	/**
	 * Get the excluded status filter values from url if set.
	 *
	 * @return string
	 */
	private function get_excluded_status_from_url(): ?string {
		return array_key_exists( 'iawmlf_is_excluded', $_GET ) && '' !== $_GET['iawmlf_is_excluded'] // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			? sanitize_text_field( wp_unslash( $_GET['iawmlf_is_excluded'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			: null;
	}

	/**
	 * Gets the archived status filter values from url if set.
	 *
	 * @return string
	 */
	private function get_archived_status_from_url(): ?string {
		return array_key_exists( 'iawmlf_has_archive', $_GET ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			? sanitize_text_field( wp_unslash( $_GET['iawmlf_has_archive'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
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

		$order_by = sanitize_text_field( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible

		// If orderby is not a valid column, return default.
		if ( ! in_array( $order_by, array( self::COLUMN_LINK_URL, self::COLUMN_LINK_CHECKS_LAST, self::COLUMN_LINK_ARCHIVE, self::COLUMN_LINK_CHECKS, self::COLUMN_LINK_EXCLUDE, self::COLUMN_LINK_HEALTH ), true ) ) {
			return Link_Repository::ORDER_ID_DESC;
		}

		// Get the direction.
		$order = array_key_exists( 'order', $_GET ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			? sanitize_text_field( wp_unslash( $_GET['order'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
			: 'asc';

		$asc_map = array(
			self::COLUMN_LINK_URL         => Link_Repository::ORDER_URL_ASC,
			self::COLUMN_LINK_CHECKS_LAST => Link_Repository::ORDER_DATE_ASC,
			self::COLUMN_LINK_ARCHIVE     => Link_Repository::ORDER_HAS_ARCHIVE_ASC,
			self::COLUMN_LINK_HEALTH      => Link_Repository::ORDER_LINK_HEALTH_ASC,
			self::COLUMN_LINK_EXCLUDE     => Link_Repository::ORDER_LINK_EXCLUDED_ASC,
			self::COLUMN_LINK_CHECKS      => Link_Repository::ORDER_LINK_CHECKS_ASC,
		);

		$desc_map = array(
			self::COLUMN_LINK_URL         => Link_Repository::ORDER_URL_DESC,
			self::COLUMN_LINK_CHECKS_LAST => Link_Repository::ORDER_DATE_DESC,
			self::COLUMN_LINK_ARCHIVE     => Link_Repository::ORDER_HAS_ARCHIVE_DESC,
			self::COLUMN_LINK_HEALTH      => Link_Repository::ORDER_LINK_HEALTH_DESC,
			self::COLUMN_LINK_EXCLUDE     => Link_Repository::ORDER_LINK_EXCLUDED_DESC,
			self::COLUMN_LINK_CHECKS      => Link_Repository::ORDER_LINK_CHECKS_DESC,
		);

		// Return the correct order.
		switch ( $order ) {
			case 'asc':
				return $asc_map[ $order_by ];
			case 'desc':
				return $desc_map[ $order_by ];
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
			'updated_snapshot' => __( 'Update to latest snapshot', 'internet-archive-wayback-machine-link-fixer' ),
			'new_snapshot'     => __( 'Create new snapshot', 'internet-archive-wayback-machine-link-fixer' ),
			'check'            => __( 'Check link status', 'internet-archive-wayback-machine-link-fixer' ),
			'validate'         => __( 'Verify link allows checking', 'internet-archive-wayback-machine-link-fixer' ),
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
		if ( ! array_key_exists( 'iawmlf_filtered_post_id', $_GET ) ) { // phpcs:ignore
			return array();
		}

		$post_id = absint( $_GET['iawmlf_filtered_post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible

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
			? sanitize_text_field( wp_unslash( $_GET['s'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended, from url so no nonce possible
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
			self::COLUMN_LINK_ARCHIVE     => array( self::COLUMN_LINK_ARCHIVE, false ),
			self::COLUMN_LINK_HEALTH      => array( self::COLUMN_LINK_HEALTH, false ),
			self::COLUMN_LINK_EXCLUDE     => array( self::COLUMN_LINK_EXCLUDE, false ),
			self::COLUMN_LINK_CHECKS      => array( self::COLUMN_LINK_CHECKS, false ),
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
					esc_url( add_query_arg( array( 'iawmlf_link_id' => $item->get_id() ), $url ) ),
					$this->compile_link_name( $item )
				);
			case self::COLUMN_LINK_ARCHIVE:
				// If the link is excluded, return the excluded icon.
				if ( $item->is_excluded() ) {
					return $this->get_dashicon( 'dashicons-warning', __( 'The link is excluded from being archived.', 'internet-archive-wayback-machine-link-fixer' ) );
				}

				if ( $item->has_archived_href() ) {
					return sprintf(
						'<a href="%s" target="_blank">%s</a>',
						$item->get_archived_href(),
						$this->get_dashicon( 'dashicons-yes-alt', __( 'Has a valid archive snapshot', 'internet-archive-wayback-machine-link-fixer' ) )
					);
				}
				$process = $item->get_archive_process();
				if ( Link::PROCESS_NEW === $process ) {
					return $this->get_dashicon( 'dashicons-plus-alt', __( 'New link, not yet processed', 'internet-archive-wayback-machine-link-fixer' ) );
				} elseif ( Link::PROCESS_PENDING === $process ) {
					return $this->get_dashicon( 'dashicons-clock', __( 'Processing in progress', 'internet-archive-wayback-machine-link-fixer' ) );
				} else {
					return $this->get_dashicon( 'dashicons-dismiss', __( 'No archive available', 'internet-archive-wayback-machine-link-fixer' ) );
				}

			case self::COLUMN_LINK_HEALTH:
				if ( $item->has_archived_href() || Link::PROCESS_DONE === $item->get_archive_process() ) {
					// If we have no checks yet, show still pending.
					if ( 0 === count( $item->get_checks() ) ) {
						return $this->get_dashicon( 'dashicons-clock', __( 'Link has not been checked yet', 'internet-archive-wayback-machine-link-fixer' ) );
					}

					return ! $item->is_broken()
						? $this->get_dashicon( 'dashicons-yes-alt', __( 'Link is active', 'internet-archive-wayback-machine-link-fixer' ) )
						: $this->get_dashicon( 'dashicons-editor-unlink', __( 'Link is broken', 'internet-archive-wayback-machine-link-fixer' ) );
				} else {
					return $this->get_dashicon( 'dashicons-clock', __( 'Link status pending verification', 'internet-archive-wayback-machine-link-fixer' ) );
				}
			case self::COLUMN_LINK_CHECKS:
				return count( $item->get_checks() );

			case self::COLUMN_LINK_CHECKS_LAST:
				return $this->compile_details_cell( $item );
			case self::COLUMN_LINK_EXCLUDE:
				return $item->is_excluded()
					? $this->get_dashicon( 'dashicons-yes-alt', __( 'Link is excluded from checks', 'internet-archive-wayback-machine-link-fixer' ) )
					: $this->get_dashicon( 'dashicons-dismiss', __( 'Link is not excluded from checks', 'internet-archive-wayback-machine-link-fixer' ) );
			case 'cb':
				return sprintf(
					'<input type="checkbox" name="iawmlf_links[]" value="%d" />',
					$item->get_id()
				);
			default:
				return '';
		}
	}

	/**
	 * Returns a populate dash icon.
	 *
	 * @param string $icon  The icon name.
	 * @param string $title The icon title.
	 *
	 * @return string
	 */
	private function get_dashicon( string $icon, string $title = '' ): string {
		return '' === $title ? sprintf(
			'<span class="dashicons %s"></span>',
			esc_attr( $icon )
		) : sprintf(
			'<span class="dashicons %s" title="%s"></span>',
			esc_attr( $icon ),
			esc_attr( $title )
		);
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
			esc_html( iawmlf_trim_string( $item->get_href(), 200 ) ),
			esc_url( $item->get_href() ),
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
			return __( 'N/a', 'internet-archive-wayback-machine-link-fixer' );
		}

		$last_check_status = $last_check['http_code'] ?? null;

		// Replace non-numeric characters from status.
		if ( null !== $last_check_status ) {
			$last_check_status = preg_replace( '/[^0-9]/', '', (string) $last_check_status );
		}

		$last_status_display = $last_check_status
			? "<a href=\"https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/{$last_check_status}\" target=\"_blank\">{$last_check_status}  status</a>"
			: __( 'No HTTP Code', 'internet-archive-wayback-machine-link-fixer' );

		return sprintf(
			// translators: %1$s is the last check date, %2$s is the last check http code.
			__( '%1$s with %2$s', 'internet-archive-wayback-machine-link-fixer' ),
			$last_check['date']
				? DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $last_check['date'] )->format( get_option( 'date_format' ) )
				: __( 'Missing date', 'internet-archive-wayback-machine-link-fixer' ),
			$last_check
				? $last_status_display
				: __( 'No HTTP Code', 'internet-archive-wayback-machine-link-fixer' )
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
		echo esc_html__( 'No links have been created yet.', 'internet-archive-wayback-machine-link-fixer' );
	}
}
