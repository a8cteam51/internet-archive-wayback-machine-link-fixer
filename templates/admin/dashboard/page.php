<?php
/**
 * Template for the dashboard page.
 *
 * @since 1.3.0
 *
 * @param array  $iawmlf_account_details         The account details from Archive.org.
 * @param bool   $iawmlf_api_configured          Whether the Archive.org API is configured.
 * @param bool   $iawmlf_is_online               Whether the Archive.org API is online.
 * @param string $iawmlf_link_to_settings        URL to the settings page.
 * @param string $iawmlf_link_table              URL to the links report page.
 * @param string $iawmlf_report_page_base        Base URL to the report page.
 * @param string $iawmlf_filtered_valid          URL to filtered valid links report.
 * @param string $iawmlf_filtered_has_archive    URL to filtered links with archive report.
 * @param string $iawmlf_filtered_no_archive     URL to filtered links without archive report.
 * @param array  $iawmlf_total_links             Array of all links in the system (for widget).
 * @param bool   $iawmlf_auto_archiver_enabled   Whether auto archiver is enabled.
 * @param bool   $iawmlf_scan_existing_enabled   Whether scanning existing posts is enabled.
 * @param bool   $iawmlf_link_processing_enabled Whether link processing is enabled.
 * @param int    $iawmlf_link_check_duration     Number of days between link checks.
 * @param int    $iawmlf_failed_check_count      Number of failed checks before marking as broken.
 * @param array  $iawmlf_link_stats              Link statistics array.
 * @param array  $iawmlf_last_checks             Array of last 10 link checks with associated posts.
 * @param array  $iawmlf_latest_links            Array of latest links with associated posts.
 * @param string $iawmlf_filtered_broken_all     URL to all broken links report.
 * @param string $iawmlf_filtered_redirected     URL to all redirected links report.
 * @param array{show_onboarding:bool, onboarding_date:non-empty-string|null, days_since_onboarding:int|null, total_post_count:int<0,max>, unprocessed_post_count:int<0,max> } $iawmlf_onboarding_details Additional data for the widget.
 */
defined( 'ABSPATH' ) || exit;


// Extract link statistics
$iawmlf_total_link_count      = $iawmlf_link_stats['total_links'] ?? 0;
$iawmlf_all_broken_links      = $iawmlf_link_stats['all_broken_links'] ?? 0;
$iawmlf_links_with_archive    = $iawmlf_link_stats['links_with_archive'] ?? 0;
$iawmlf_links_without_archive = $iawmlf_link_stats['links_without_archive'] ?? 0;
$iawmlf_not_checked           = $iawmlf_link_stats['not_checked'] ?? 0;
$iawmlf_process_done          = $iawmlf_link_stats['process_done'] ?? 0;
$iawmlf_process_new           = $iawmlf_link_stats['process_new'] ?? 0;
$iawmlf_process_pending       = $iawmlf_link_stats['process_pending'] ?? 0;
$iawmlf_still_processing      = $iawmlf_process_new + $iawmlf_process_pending;
$iawmlf_broken_redirected     = $iawmlf_link_stats['broken_and_redirected_links'] ?? 0;
$iawmlf_broken_not_redirected = $iawmlf_link_stats['broken_not_redirected_links'] ?? 0;

// Compile the tooltips
$iawmlf_tooltip_links_saved           = __( 'These broken links are being redirected to archived snapshots on the Wayback Machine.', 'internet-archive-wayback-machine-link-fixer' );
$iawmlf_tooltip_archived_successfully = __( 'These links have snapshots available on the Wayback Machine.', 'internet-archive-wayback-machine-link-fixer' );
$iawmlf_tooltip_no_archive            = __( 'These links do not have archived snapshots on the Wayback Machine, so we can\'t redirect them.', 'internet-archive-wayback-machine-link-fixer' );
$iawmlf_tooltip_checks_in_progress    = __( 'The plugin is still working through checking the status of these links and whether archived snapshots are available.', 'internet-archive-wayback-machine-link-fixer' );
$iawmlf_tooltip_broken_links          = sprintf(
	// translators: 1: number of broken links being redirected, 2: number of broken links not being redirected.
	__( '%1$s being redirected, %2$s ineligible for redirect', 'internet-archive-wayback-machine-link-fixer' ),
	$iawmlf_broken_redirected,
	$iawmlf_broken_not_redirected
);
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Wayback Link Fixer - Dashboard', 'internet-archive-wayback-machine-link-fixer' ); ?></h1>
	<div class="iawmlf_dashboard-page-grid">
		<!-- Left Column (66%) - Link Details -->
		<div class="iawmlf_dashboard-page-main">
			<div class="iawmlf_dashboard-page-column-wrapper">
				<!-- Link Activity Accordion -->
				<div class="iawmlf_dashboard-wrapper">
					<div class="iawmlf_dashboard-status-section">
						<div class="iawmlf_dashboard-accordion">
							<!-- Accordion Navigation -->
							<div class="iawmlf_dashboard-accordion-nav">
								<button class="iawmlf_dashboard-accordion-tab iawmlf_dashboard-accordion-tab--active" data-tab="recent-checks">
									<?php esc_html_e( 'Recent Link Checks', 'internet-archive-wayback-machine-link-fixer' ); ?>
								</button>
								<button class="iawmlf_dashboard-accordion-tab" data-tab="latest-links">
									<?php esc_html_e( 'Latest Links', 'internet-archive-wayback-machine-link-fixer' ); ?>
								</button>
							</div>

							<!-- Recent Link Checks Content -->
							<?php
							iawmlf_render_template(
								'admin/dashboard/link-list.php',
								array(
									'iawmlf_links'      => $iawmlf_last_checks,
									'iawmlf_is_active'  => true,
									'iawmlf_section_id' => 'recent-checks',
									'iawmlf_link_table' => $iawmlf_link_table,
									'iawmlf_no_links_message' => __( 'No recent link checks available.', 'internet-archive-wayback-machine-link-fixer' ),
								)
							);
							?>

							<!-- Latest Links Content -->
							<?php
							iawmlf_render_template(
								'admin/dashboard/link-list.php',
								array(
									'iawmlf_links'      => $iawmlf_latest_links,
									'iawmlf_is_active'  => false,
									'iawmlf_section_id' => 'latest-links',
									'iawmlf_link_table' => $iawmlf_link_table,
									'iawmlf_no_links_message' => __( 'No recent links available.', 'internet-archive-wayback-machine-link-fixer' ),
								)
							);
							?>

						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Right Column (33%) - Overview Stats & Widget -->
		<div class="iawmlf_dashboard-page-sidebar">
			<div class="iawmlf_dashboard-page-column-wrapper">
				<!-- Overview Stats -->
				<div class="iawmlf_dashboard-wrapper">
					<div class="iawmlf_dashboard-status-section">
						<?php if ( $iawmlf_onboarding_details['show_onboarding'] ) : ?>
							<h3 class="iawmlf_dashboard-section-title"><?php esc_html_e( 'Onboarding Process', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
							<?php
							iawmlf_render_template(
								'admin/dashboard/onboarding.php',
								array(
									'iawmlf_onboarding_details' => $iawmlf_onboarding_details,
									'iawmlf_total_links_count' => $iawmlf_link_stats['total_links'],
									'iawmlf_link_table' => \Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Report_Page::get_page_url(),
								)
							);
							?>
						<?php else : ?>
							<!-- Regular Overview Stats -->
							<h3 class="iawmlf_dashboard-section-title"><?php esc_html_e( 'Link Statistics Overview', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
							<div class="iawmlf_dashboard-stats-grid iawmlf_dashboard-stats-sidebar">
								<!-- Row 1: Total Links | Being Redirected -->
								<div class="iawmlf_dashboard-stats-box">
									<a href="<?php echo esc_url( $iawmlf_link_table ); ?>" class="iawmlf_dashboard-stats-number iawmlf_dashboard-stats-link">
										<?php echo esc_html( $iawmlf_total_link_count ); ?>
									</a>
									<div class="iawmlf_dashboard-stats-label"><?php esc_html_e( 'Total Links', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
								</div>
								<div class="iawmlf_dashboard-stats-box iawmlf_dashboard-stats-box--black">
									<a href="<?php echo esc_url( $iawmlf_filtered_broken_redirected ); ?>" title="<?php echo esc_attr( $iawmlf_tooltip_links_saved ); ?>" class="iawmlf_dashboard-stats-number iawmlf_dashboard-stats-link">
										<?php echo esc_html( $iawmlf_broken_redirected ); ?>
									</a>
									<div class="iawmlf_dashboard-stats-label" title="<?php echo esc_attr( $iawmlf_tooltip_links_saved ); ?>"><?php esc_html_e( 'Links Saved', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
								</div>

								<!-- Row 2: With Archive | Without -->
								<div class="iawmlf_dashboard-stats-box iawmlf_dashboard-stats-box--success">
									<a href="<?php echo esc_url( $iawmlf_filtered_has_archive ); ?>" title="<?php echo esc_attr( $iawmlf_tooltip_archived_successfully ); ?>" class="iawmlf_dashboard-stats-number iawmlf_dashboard-stats-link">
										<?php echo esc_html( $iawmlf_links_with_archive ); ?>
									</a>
									<div class="iawmlf_dashboard-stats-label" title="<?php echo esc_attr( $iawmlf_tooltip_archived_successfully ); ?>"><?php esc_html_e( 'Archived Successfully', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
								</div>
								<div class="iawmlf_dashboard-stats-box iawmlf_dashboard-stats-box--warning">
									<a href="<?php echo esc_url( $iawmlf_filtered_no_archive ); ?>" title="<?php echo esc_attr( $iawmlf_tooltip_no_archive ); ?>" class="iawmlf_dashboard-stats-number iawmlf_dashboard-stats-link">
										<?php echo esc_html( $iawmlf_links_without_archive ); ?>
									</a>
									<div class="iawmlf_dashboard-stats-label" title="<?php echo esc_attr( $iawmlf_tooltip_no_archive ); ?>"><?php esc_html_e( 'Ineligible for redirect', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
								</div>

								<!-- Row 3: Checks in progress | Broken Links -->
								<div class="iawmlf_dashboard-stats-box iawmlf_dashboard-stats-box--info">
									<div class="iawmlf_dashboard-stats-number" title="<?php echo esc_attr( $iawmlf_tooltip_checks_in_progress ); ?>"><?php echo esc_html( $iawmlf_not_checked ); ?></div>
									<div class="iawmlf_dashboard-stats-label" title="<?php echo esc_attr( $iawmlf_tooltip_checks_in_progress ); ?>"><?php esc_html_e( 'Checks in progress', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
								</div>
								<div class="iawmlf_dashboard-stats-box iawmlf_dashboard-stats-box--danger">
									<a href="<?php echo esc_url( $iawmlf_filtered_broken_all ); ?>" title="<?php echo esc_attr( $iawmlf_tooltip_broken_links ); ?>" class="iawmlf_dashboard-stats-number iawmlf_dashboard-stats-link">
										<?php echo esc_html( $iawmlf_all_broken_links ); ?>
									</a>
									<div class="iawmlf_dashboard-stats-label" title="<?php echo esc_attr( $iawmlf_tooltip_broken_links ); ?>"><?php esc_html_e( 'Total Broken Links', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>

					<?php
					// Set up variables for the widget (it expects $iawmlf_details, not $iawmlf_account_details)
					$iawmlf_details = $iawmlf_account_details;

					// Create a total links array for the widget (pass the actual count)
					$iawmlf_total_links = array_fill( 0, $iawmlf_total_link_count, null ); // Mock array with correct count

					// Include the existing widget template
					require __DIR__ . '/widget.php';
					?>
			</div>
		</div>
	</div>
</div>
