<?php
/**
 * Template for the dashboard widget.
 *
 * @since 1.3.0
 *
 * @param array  $wlf_details          The account details from Archive.org.
 * @param bool   $wlf_api_configured   Whether the Archive.org API is configured.
 * @param bool   $wlf_is_online        Whether the Archive.org API is online.
 * @param string $wlf_link_to_settings URL to the settings page.
 * @param string $wlf_link_table       URL to the links report page.
 * @param array  $wlf_total_links      Array of all links in the system.
 * @param bool   $wlf_auto_archiver_enabled   Whether auto archiver is enabled.
 * @param bool   $wlf_scan_existing_enabled   Whether scanning existing posts is enabled.
 * @param bool   $wlf_link_processing_enabled Whether link processing is enabled.
 * @param int    $wlf_link_check_duration     Number of days between link checks.
 * @param int    $wlf_failed_check_count      Number of failed checks before marking as broken.
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wlf_dashboard-wrapper">
	<?php if ( ! $wlf_api_configured ) : ?>
		<div class="wlf_dashboard-warning">
			<?php esc_html_e( 'You are using Link Fixer in unauthenticated mode, which restricts you to 4000 new snapshots per day. To unlock higher limits, please enter your API credentials to authenticate with Archive.org.', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</div>
	<?php endif; ?>

	<?php if ( $wlf_api_configured && ! \WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings::has_valid_archive_api_credentials() ) : ?>
		<div class="wlf_dashboard-warning">
			<?php esc_html_e( 'Your Archive.org API credentials are invalid. Please check your settings.', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</div>
	<?php endif; ?>

	<div class="wlf_dashboard-status-section">
		<span class="wlf_dashboard-status-indicator <?php echo esc_attr( $wlf_is_online ? 'online' : 'offline' ); ?>"></span>
		<strong>
			<?php
			echo $wlf_is_online
				? esc_html__( 'Archive.org API services are online.', 'internet-archive-wayback-machine-link-fixer' )
				: esc_html__( 'Archive.org API is offline. Processes will be delayed, until service is resumed.', 'internet-archive-wayback-machine-link-fixer' );
			?>
		</strong>
	</div>

	<?php if ( $wlf_details && is_array( $wlf_details ) ) : ?>
		<div class="wlf_dashboard-status-section">
			<div class="wlf_dashboard-stats-grid">
				<div class="wlf_dashboard-stats-box">
					<div class="wlf_dashboard-stats-ratio">
						<?php
						printf(
							'%d/%d',
							absint( $wlf_details['daily_captures'] ),
							absint( $wlf_details['daily_captures_limit'] )
						);
						?>
					</div>
					<div class="wlf_dashboard-stats-label">
						<?php esc_html_e( "Today's Snapshots", 'internet-archive-wayback-machine-link-fixer' ); ?>
					</div>
				</div>
				<div class="wlf_dashboard-stats-box">
					<div class="wlf_dashboard-stats-number"><?php echo esc_html( absint( $wlf_details['processing'] ) ); ?></div>
					<div class="wlf_dashboard-stats-label">
						<?php esc_html_e( 'Pending Snapshots', 'internet-archive-wayback-machine-link-fixer' ); ?>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="wlf_dashboard-status-section">
		<h3 class="wlf_dashboard-section-title"><?php esc_html_e( 'Active Features', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
		<div class="wlf_dashboard-features">
			<div class="wlf_dashboard-features-item">
				<span class="wlf_dashboard-features-status <?php echo esc_attr( $wlf_link_processing_enabled ? 'enabled' : 'disabled' ); ?>">
					<span class="dashicons <?php echo esc_attr( $wlf_link_processing_enabled ? 'dashicons-yes-alt' : 'dashicons-no-alt' ); ?>"></span>
				</span>
				<div class="wlf_dashboard-features-content">
					<span class="wlf_dashboard-features-label"><?php esc_html_e( 'Link Processing', 'internet-archive-wayback-machine-link-fixer' ); ?></span>
					<div class="wlf_dashboard-features-description"><?php esc_html_e( 'Create snapshots of external links when posts are created or updated', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
				</div>
			</div>
			<div class="wlf_dashboard-features-item">
				<span class="wlf_dashboard-features-status <?php echo esc_attr( $wlf_auto_archiver_enabled ? 'enabled' : 'disabled' ); ?>">
					<span class="dashicons <?php echo esc_attr( $wlf_auto_archiver_enabled ? 'dashicons-yes-alt' : 'dashicons-no-alt' ); ?>"></span>
				</span>
				<div class="wlf_dashboard-features-content">
					<span class="wlf_dashboard-features-label"><?php esc_html_e( 'Auto Archiver', 'internet-archive-wayback-machine-link-fixer' ); ?></span>
					<div class="wlf_dashboard-features-description"><?php esc_html_e( 'Create snapshots of your own content', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
				</div>
			</div>
			<div class="wlf_dashboard-features-item">
				<span class="wlf_dashboard-features-status <?php echo esc_attr( $wlf_scan_existing_enabled ? 'enabled' : 'disabled' ); ?>">
					<span class="dashicons <?php echo esc_attr( $wlf_scan_existing_enabled ? 'dashicons-yes-alt' : 'dashicons-no-alt' ); ?>"></span>
				</span>
				<div class="wlf_dashboard-features-content">
					<span class="wlf_dashboard-features-label"><?php esc_html_e( 'Scan Existing Posts', 'internet-archive-wayback-machine-link-fixer' ); ?></span>
					<div class="wlf_dashboard-features-description"><?php esc_html_e( 'Process links in previously published content', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
				</div>
			</div>
			<div class="wlf_dashboard-features-item">
				<span class="wlf_dashboard-features-status <?php echo esc_attr( $wlf_link_processing_enabled ? 'enabled' : 'disabled' ); ?>">
					<span class="dashicons dashicons-clock"></span>
				</span>
				<div class="wlf_dashboard-features-content">
					<span class="wlf_dashboard-features-label"><?php esc_html_e( 'Link Checking', 'internet-archive-wayback-machine-link-fixer' ); ?></span>
					<div class="wlf_dashboard-features-description">
						<?php if ( $wlf_link_processing_enabled ) : ?>
							<?php
							printf(
								/* translators: 1: number of days between checks, 2: number of failed checks before marking as broken */
								esc_html__( 'Links are checked every %1$d days and marked as broken after %2$d consecutive failures', 'internet-archive-wayback-machine-link-fixer' ),
								absint( $wlf_link_check_duration ),
								absint( $wlf_failed_check_count )
							);
							?>
						<?php else : ?>
							<?php
							printf(
								/* translators: 1: opening link tag, 2: closing link tag */
								esc_html__( 'Links are not being checked. %1$sEnable Link Processing%2$s to turn this back on.', 'internet-archive-wayback-machine-link-fixer' ),
								'<a href="' . esc_url( $wlf_link_to_settings ) . '">',
								'</a>'
							);
							?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="wlf_dashboard-navigation">
		<a href="<?php echo esc_url( $wlf_link_to_settings ); ?>" class="button">
			<span class="dashicons dashicons-admin-settings" style="margin-top: 3px;"></span>
			<?php esc_html_e( 'Settings', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</a>
		<a href="<?php echo esc_url( $wlf_link_table ); ?>" class="button">
			<span class="dashicons dashicons-list-view" style="margin-top: 3px;"></span>
			<?php
			printf(
				/* translators: %d: number of links */
				esc_html__( 'View Links (%d)', 'internet-archive-wayback-machine-link-fixer' ),
				count( $wlf_total_links )
			);
			?>
		</a>
	</div>
</div>
