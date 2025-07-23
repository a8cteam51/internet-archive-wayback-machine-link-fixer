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
	<h3><?php esc_html_e( 'Archive Status', 'wayback-link-fixer' ); ?></h3>
	<p><?php esc_html_e( 'Indicates whether the link has a valid snapshot from the Internet Archive associated with it.', 'wayback-link-fixer' ); ?></p>
	<p>
		<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Has a valid archive snapshot', 'wayback-link-fixer' ); ?><br>
		<span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'New link, not yet processed', 'wayback-link-fixer' ); ?><br>
		<span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Processing in progress', 'wayback-link-fixer' ); ?><br>
		<span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'No archive available', 'wayback-link-fixer' ); ?>
	</p>

	<h3><?php esc_html_e( 'Link Health', 'wayback-link-fixer' ); ?></h3>
	<p>
		<?php
		echo esc_html(
			sprintf(
				// translators: %d is the number of consecutive failed checks required to mark a link as broken.
				__( 'Indicates whether the live link is active or broken. A link is marked as broken after %d consecutive failed checks.', 'wayback-link-fixer' ),
				$wlf_failed_count
			)
		);
		?>
	</p>
	<p>
		<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Link is active', 'wayback-link-fixer' ); ?><br>
		<span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Link status pending verification', 'wayback-link-fixer' ); ?><br>
		<span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'Link is broken', 'wayback-link-fixer' ); ?>
	</p>

	<h3><?php esc_html_e( 'Times Checked', 'wayback-link-fixer' ); ?></h3>
	<p>
		<?php
		echo esc_html(
			sprintf(
				// translators: %d is the number of days between checks.
				__( 'Shows how many times the link has been checked. Links are automatically checked every %d days.', 'wayback-link-fixer' ),
				$wlf_check_frequency
			)
		);
		?>
	</p>

	<h3><?php esc_html_e( 'Last Check', 'wayback-link-fixer' ); ?></h3>
	<p><?php esc_html_e( 'Displays the date and result (e.g., HTTP status code) of the most recent check on the live link, if available.', 'wayback-link-fixer' ); ?></p>
</div>
