<?php
/**
 * Template for the dashboard widget.
 *
 * @since 1.3.0
 *
 * @param array  $iawmlf_details          The account details from Archive.org.
 * @param bool   $iawmlf_api_configured   Whether the Archive.org API is configured.
 * @param bool   $iawmlf_is_online        Whether the Archive.org API is online.
 * @param string $iawmlf_link_to_settings URL to the settings page.
 * @param string $iawmlf_link_table       URL to the links report page.
 * @param array  $iawmlf_total_links      Array of all links in the system.
 * @param bool   $iawmlf_auto_archiver_enabled   Whether auto archiver is enabled.
 * @param bool   $iawmlf_scan_existing_enabled   Whether scanning existing posts is enabled.
 * @param bool   $iawmlf_link_processing_enabled Whether link processing is enabled.
 * @param int    $iawmlf_link_check_duration     Number of days between link checks.
 * @param int    $iawmlf_failed_check_count      Number of failed checks before marking as broken.
 */

use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Dashboard_Page;

defined( 'ABSPATH' ) || exit;
?>

<div class="iawmlf_dashboard-wrapper">
	<?php if ( ! $iawmlf_api_configured ) : ?>
		<div class="iawmlf_dashboard-warning">
			<?php esc_html_e( 'You are using Link Fixer in unauthenticated mode, which restricts you to 4000 new snapshots per day. To unlock higher limits, please enter your API credentials to authenticate with Archive.org.', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</div>
	<?php endif; ?>
		<?php if ( $iawmlf_onboarding_details['show_onboarding'] && ! Dashboard_Page::is_current_page() ) : ?>
			<div class="iawmlf_dashboard-status-section">
				<?php
				iawmlf_render_template(
					'admin/dashboard/onboarding.php',
					array(
						'iawmlf_onboarding_details' => $iawmlf_onboarding_details,
						'iawmlf_total_links_count'  => $iawmlf_link_stats['total_links'],
						'iawmlf_link_table'         => \Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Report_Page::get_page_url(),
					)
				);
				?>
			</div>
		<?php endif; ?>

	<?php if ( $iawmlf_api_configured && ! \Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings::has_valid_archive_api_credentials() ) : ?>
		<div class="iawmlf_dashboard-warning">
			<?php esc_html_e( 'Your Archive.org API credentials are invalid. Please check your settings.', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</div>
	<?php endif; ?>

	<div class="iawmlf_dashboard-status-section">
		<span class="iawmlf_dashboard-status-indicator <?php echo esc_attr( $iawmlf_is_online ? 'online' : 'offline' ); ?>"></span>
		<strong>
			<?php
			echo $iawmlf_is_online
				? esc_html__( 'Archive.org API services are online.', 'internet-archive-wayback-machine-link-fixer' )
				: esc_html__( 'Archive.org API is offline. Processes will be delayed, until service is resumed.', 'internet-archive-wayback-machine-link-fixer' );
			?>
		</strong>
	</div>


	<?php if ( $iawmlf_details && is_array( $iawmlf_details ) ) : ?>
		<div class="iawmlf_dashboard-status-section">
			<div class="iawmlf_dashboard-stats-grid">
				<div class="iawmlf_dashboard-stats-box">
					<div class="iawmlf_dashboard-stats-ratio">
						<?php
						printf(
							'%d/%d',
							absint( $iawmlf_details['daily_captures'] ),
							absint( $iawmlf_details['daily_captures_limit'] )
						);
						?>
					</div>
					<div class="iawmlf_dashboard-stats-label">
						<?php esc_html_e( "Today's Snapshots", 'internet-archive-wayback-machine-link-fixer' ); ?>
					</div>
				</div>
				<div class="iawmlf_dashboard-stats-box">
					<div class="iawmlf_dashboard-stats-number"><?php echo esc_html( absint( $iawmlf_details['processing'] ) ); ?></div>
					<div class="iawmlf_dashboard-stats-label">
						<?php esc_html_e( 'Pending Snapshots', 'internet-archive-wayback-machine-link-fixer' ); ?>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="iawmlf_dashboard-status-section">
		<h3 class="iawmlf_dashboard-section-title"><?php esc_html_e( 'Active Features', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
		<div class="iawmlf_dashboard-features">
			<div class="iawmlf_dashboard-features-item">
				<span class="iawmlf_dashboard-features-status <?php echo esc_attr( $iawmlf_link_processing_enabled ? 'enabled' : 'disabled' ); ?>">
					<span class="dashicons <?php echo esc_attr( $iawmlf_link_processing_enabled ? 'dashicons-yes-alt' : 'dashicons-no-alt' ); ?>"></span>
				</span>
				<div class="iawmlf_dashboard-features-content">
					<span class="iawmlf_dashboard-features-label"><?php esc_html_e( 'Link Processing', 'internet-archive-wayback-machine-link-fixer' ); ?></span>
					<div class="iawmlf_dashboard-features-description"><?php esc_html_e( 'Create snapshots of external links when posts are created or updated', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
				</div>
			</div>
			<div class="iawmlf_dashboard-features-item">
				<span class="iawmlf_dashboard-features-status <?php echo esc_attr( $iawmlf_auto_archiver_enabled ? 'enabled' : 'disabled' ); ?>">
					<span class="dashicons <?php echo esc_attr( $iawmlf_auto_archiver_enabled ? 'dashicons-yes-alt' : 'dashicons-no-alt' ); ?>"></span>
				</span>
				<div class="iawmlf_dashboard-features-content">
					<span class="iawmlf_dashboard-features-label"><?php esc_html_e( 'Auto Archiver', 'internet-archive-wayback-machine-link-fixer' ); ?></span>
					<div class="iawmlf_dashboard-features-description"><?php esc_html_e( 'Create snapshots of your own content', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
				</div>
			</div>
			<div class="iawmlf_dashboard-features-item">
				<span class="iawmlf_dashboard-features-status <?php echo esc_attr( $iawmlf_scan_existing_enabled ? 'enabled' : 'disabled' ); ?>">
					<span class="dashicons <?php echo esc_attr( $iawmlf_scan_existing_enabled ? 'dashicons-yes-alt' : 'dashicons-no-alt' ); ?>"></span>
				</span>
				<div class="iawmlf_dashboard-features-content">
					<span class="iawmlf_dashboard-features-label"><?php esc_html_e( 'Scan Existing Posts', 'internet-archive-wayback-machine-link-fixer' ); ?></span>
					<div class="iawmlf_dashboard-features-description"><?php esc_html_e( 'Process links in previously published content', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
				</div>
			</div>
			<div class="iawmlf_dashboard-features-item">
				<span class="iawmlf_dashboard-features-status <?php echo esc_attr( $iawmlf_link_processing_enabled ? 'enabled' : 'disabled' ); ?>">
					<span class="dashicons dashicons-clock"></span>
				</span>
				<div class="iawmlf_dashboard-features-content">
					<span class="iawmlf_dashboard-features-label"><?php esc_html_e( 'Link Checking', 'internet-archive-wayback-machine-link-fixer' ); ?></span>
					<div class="iawmlf_dashboard-features-description">
						<?php if ( $iawmlf_link_processing_enabled ) : ?>
							<?php
							printf(
								/* translators: 1: number of days between checks, 2: number of failed checks before marking as broken */
								esc_html__( 'Links are checked every %1$d days and marked as broken after %2$d consecutive failures', 'internet-archive-wayback-machine-link-fixer' ),
								absint( $iawmlf_link_check_duration ),
								absint( $iawmlf_failed_check_count )
							);
							?>
						<?php else : ?>
							<?php
							printf(
								/* translators: 1: opening link tag, 2: closing link tag */
								esc_html__( 'Links are not being checked. %1$sEnable Link Processing%2$s to turn this back on.', 'internet-archive-wayback-machine-link-fixer' ),
								'<a href="' . esc_url( $iawmlf_link_to_settings ) . '">',
								'</a>'
							);
							?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="iawmlf_dashboard-navigation">
		<?php if ( ! Dashboard_Page::is_current_page() ) : ?>
		<a href="<?php echo esc_url( Dashboard_Page::get_page_url() ); ?>" class="button">
			<span class="dashicons dashicons-dashboard" style="margin-top: 3px;"></span>
			<?php esc_html_e( 'Dashboard', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</a>
		<?php endif; ?>
		<a href="<?php echo esc_url( $iawmlf_link_to_settings ); ?>" class="button">
			<span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
			<?php esc_html_e( 'Settings', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</a>
		<a href="<?php echo esc_url( $iawmlf_link_table ); ?>" class="button">
			<span class="dashicons dashicons-list-view" style="margin-top: 3px;"></span>
			<?php
			printf(
				/* translators: %d: number of links */
				esc_html__( 'View Links (%d)', 'internet-archive-wayback-machine-link-fixer' ),
				count( $iawmlf_total_links )
			);
			?>
		</a>
	</div>
</div>
