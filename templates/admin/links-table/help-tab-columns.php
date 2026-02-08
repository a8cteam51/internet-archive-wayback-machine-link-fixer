<?php
/**
 * Template for the tab columns content of the help section.
 *
 * @since 1.3.0
 */

defined( 'ABSPATH' ) || exit;


$iawmlf_failed_count    = \Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings::get_failed_count();
$iawmlf_check_frequency = \Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings::get_link_check_duration();
?>

<div id="iawmlf_help_tab_columns" class="iawmlf_help_tab">
	<h3><?php esc_html_e( 'Archive Status', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
	<p><?php esc_html_e( 'Indicates whether the link has a valid snapshot from the Internet Archive associated with it.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
	<p>
		<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Has a valid archive snapshot', 'internet-archive-wayback-machine-link-fixer' ); ?><br>
		<span class="dashicons dashicons-plus-alt"></span> <?php esc_html_e( 'New link, not yet processed', 'internet-archive-wayback-machine-link-fixer' ); ?><br>
		<span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Processing in progress', 'internet-archive-wayback-machine-link-fixer' ); ?><br>
		<span class="dashicons dashicons-dismiss"></span> <?php esc_html_e( 'No archive available', 'internet-archive-wayback-machine-link-fixer' ); ?><br>
		<span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'The link is excluded from being archived.', 'internet-archive-wayback-machine-link-fixer' ); ?>
	</p>

	<h3><?php esc_html_e( 'Link Health', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
	<p>
		<?php
		echo esc_html(
			sprintf(
				// translators: %d is the number of consecutive failed checks required to mark a link as broken.
				__( 'Indicates whether the live link is active or broken. A link is marked as broken after %d consecutive failed checks.', 'internet-archive-wayback-machine-link-fixer' ),
				$iawmlf_failed_count
			)
		);
		?>
	</p>
	<p>
		<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Link is active', 'internet-archive-wayback-machine-link-fixer' ); ?><br>
		<span class="dashicons dashicons-clock"></span> <?php esc_html_e( 'Link status pending verification', 'internet-archive-wayback-machine-link-fixer' ); ?><br>
		<span class="dashicons dashicons-editor-unlink"></span> <?php esc_html_e( 'Link is broken', 'internet-archive-wayback-machine-link-fixer' ); ?>
	</p>

	<h3><?php esc_html_e( 'Times Checked', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
	<p>
		<?php
		echo esc_html(
			sprintf(
				// translators: %d is the number of days between checks.
				__( 'Shows how many times the link has been checked. Links are automatically checked every %d days.', 'internet-archive-wayback-machine-link-fixer' ),
				$iawmlf_check_frequency
			)
		);
		?>
	</p>

	<h3><?php esc_html_e( 'Last Check', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
	<p><?php esc_html_e( 'Displays the date and result (e.g., HTTP status code) of the most recent check on the live link, if available.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
</div>
