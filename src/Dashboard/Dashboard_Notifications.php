<?php

/**
 * Render the dashboard notifications.
 *
 * @since 1.3.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Dashboard;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Class used to render the dashboard notifications.
 */
class Dashboard_Notifications {

	/**
	 * Initialize the dashboard notifications.
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register the dashboard widgets.
	 *
	 * @return void
	 */
	public function register_widgets(): void {
		wp_add_dashboard_widget(
			'wayback_link_fixer_dashboard_widget',
			__( 'Wayback Link Fixer', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_widget' ),
			null,
			null,
			'normal',
			'high'
		);
	}

	/**
	 * Render the dashboard widget.
	 *
	 * @return void
	 */
	public function render_widget(): void {
		$status         = $this->get_status_update();
		$api_configured = Settings::is_archive_api_configured();

		// If the api is not configured, show a message.
		if ( ! $api_configured ) {
			printf(
				'<p><strong>%s</strong></p>',
				esc_html__( 'You are using Link Fixer in unauthenticated mode, which restricts you to 4000 new snapshots per day. To unlock higher limits, please enter your API credentials to authenticate with Archive.org.', 'wpcomsp_wayback_link_fixer' )
			);

			return;
		}

		// if the status is pending, show a message.
		if ( $status['pending'] ) {
			printf(
				'<p>%s</p>',
				esc_html__( 'The status of the link fixer is currently pending, please check again shortly', 'wpcomsp_wayback_link_fixer' )
			);

			return;
		}

		print 'TODO';

		printf(
			'<p>%s: %s</p>',
			esc_html__( 'Last Checked', 'wpcomsp_wayback_link_fixer' ),
			$status['last_checked'] ? esc_html( $status['last_checked']->format( wpcomsp_wayback_link_fixer_get_date_format() ) ) : esc_html__( 'Never', 'wpcomsp_wayback_link_fixer' )
		);
	}

	/**
	 * Get the current status update.
	 *
	 * @return array{
	 * 'is_online' => bool,
	 * 'last_checked' => \DateTimeImmutable|null,
	 * 'pending' => bool,
	 * 'link_checker_online => bool,
	 * 'snapshot_online' => bool,}
	 */
	public static function get_status_update(): array {
		// Get the status.
		$status = Settings::get_archive_api_status();

		// if the status is null, return a default status.
		if ( null === $status ) {
			return array(
				'is_online'           => false,
				'last_checked'        => null,
				'pending'             => true,
				'link_checker_online' => false,
				'snapshot_online'     => false,
			);
		} else {
			$time = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $status['last-checked'] );
			return array(
				'is_online'           => 'online' === $status['status'],
				'last_checked'        => $time ? $time : null,
				'pending'             => false,
				'link_checker_online' => (bool) $status['link_checker'],
				'snapshot_online'     => (bool) $status['snapshot'],
			);
		}
	}
}
