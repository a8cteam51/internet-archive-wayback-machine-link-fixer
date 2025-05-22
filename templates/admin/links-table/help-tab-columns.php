<?php
/**
 * Template for the tab columns content of the help section.
 *
 * @since 1.3.0
 */

$wlf_failed_count    = \WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings::get_failed_count();
$wlf_check_frequency = \WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings::get_link_check_duration();
?>

<div id="wlf_help_tab_columns" class="wlf_help_tab">
	<h3><?php esc_html_e( 'Has Archive', 'wpcomsp_wayback_link_fixer' ); ?></h3>
	<p><?php esc_html_e( 'Indicates whether the link has a valid snapshot from the Internet Archive associated with it.', 'wpcomsp_wayback_link_fixer' ); ?></p>

	<h3><?php esc_html_e( 'Link Health', 'wpcomsp_wayback_link_fixer' ); ?></h3>
	<p>
		<?php
		echo esc_html(
			sprintf(
				// translators: %d is the number of consecutive failed checks required to mark a link as broken.
				__( 'Indicates whether the live link is active or broken. A link is marked as broken after %d consecutive failed checks.', 'wpcomsp_wayback_link_fixer' ),
				$wlf_failed_count
			)
		);
		?>
	</p>

	<h3><?php esc_html_e( 'Times Checked', 'wpcomsp_wayback_link_fixer' ); ?></h3>
	<p>
		<?php
		echo esc_html(
			sprintf(
				// translators: %d is the number of days between checks.
				__( 'Shows how many times the link has been checked. Links are automatically checked every %d days.', 'wpcomsp_wayback_link_fixer' ),
				$wlf_check_frequency
			)
		);
		?>
	</p>

	<h3><?php esc_html_e( 'Last Check', 'wpcomsp_wayback_link_fixer' ); ?></h3>
	<p><?php esc_html_e( 'Displays the date and result (e.g., HTTP status code) of the most recent check on the live link, if available.', 'wpcomsp_wayback_link_fixer' ); ?></p>
</div>
