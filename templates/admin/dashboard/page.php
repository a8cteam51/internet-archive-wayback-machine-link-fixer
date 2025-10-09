<?php
/**
 * Template for the dashboard page.
 *
 * @since 1.3.0
 *
 * @param array  $iawmlf_account_details       The account details from Archive.org.
 * @param bool   $iawmlf_api_configured   Whether the Archive.org API is configured.
 * @param bool   $iawmlf_is_online        Whether the Archive.org API is online.
 * @param string $iawmlf_link_to_settings URL to the settings page.
 * @param string $iawmlf_link_table       URL to the links report page.
 * @param string $iawmlf_report_page_base Base URL to the report page.
 * @param string $iawmlf_filtered_broken  URL to filtered broken links report.
 * @param string $iawmlf_filtered_valid   URL to filtered valid links report.
 * @param string $iawmlf_filtered_has_archive URL to filtered links with archive report.
 * @param string $iawmlf_filtered_no_archive  URL to filtered links without archive report.
 * @param array  $iawmlf_total_links            Array of all links in the system (for widget).
 * @param bool   $iawmlf_auto_archiver_enabled   Whether auto archiver is enabled.
 * @param bool   $iawmlf_scan_existing_enabled   Whether scanning existing posts is enabled.
 * @param bool   $iawmlf_link_processing_enabled Whether link processing is enabled.
 * @param int    $iawmlf_link_check_duration     Number of days between link checks.
 * @param int    $iawmlf_failed_check_count      Number of failed checks before marking as broken.
 * @param array  $iawmlf_link_stats       Link statistics array.
 * @param array  $iawmlf_last_checks      Array of last 10 link checks with associated posts.
 * @param array  $iawmlf_latest_links     Array of latest links with associated posts.
 */

defined( 'ABSPATH' ) || exit;


// Extract link statistics
$iawmlf_total_links_count     = $iawmlf_link_stats['total_links'] ?? 0;
$iawmlf_broken_links          = $iawmlf_link_stats['broken_links'] ?? 0;
$iawmlf_links_with_archive    = $iawmlf_link_stats['links_with_archive'] ?? 0;
$iawmlf_links_without_archive = $iawmlf_link_stats['links_without_archive'] ?? 0;
$iawmlf_not_checked           = $iawmlf_link_stats['not_checked'] ?? 0;
$iawmlf_process_done          = $iawmlf_link_stats['process_done'] ?? 0;
$iawmlf_process_new           = $iawmlf_link_stats['process_new'] ?? 0;
$iawmlf_process_pending       = $iawmlf_link_stats['process_pending'] ?? 0;
$iawmlf_still_processing      = $iawmlf_process_new + $iawmlf_process_pending;
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
					<h3 class="iawmlf_dashboard-section-title"><?php esc_html_e( 'Link Statistics Overview', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
					<div class="iawmlf_dashboard-stats-grid iawmlf_dashboard-stats-sidebar">
						<!-- Row 1: Total Links | Still Processing -->
						<div class="iawmlf_dashboard-stats-box">
							<a href="<?php echo esc_url( $iawmlf_link_table ); ?>" class="iawmlf_dashboard-stats-number iawmlf_dashboard-stats-link">
								<?php echo esc_html( $iawmlf_total_links_count ); ?>
							</a>
							<div class="iawmlf_dashboard-stats-label"><?php esc_html_e( 'Total Links', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
						</div>
						<div class="iawmlf_dashboard-stats-box <?php echo 0 === $iawmlf_still_processing ? 'iawmlf_dashboard-stats-box--success' : 'iawmlf_dashboard-stats-box--info'; ?>">
							<?php if ( 0 === $iawmlf_still_processing ) : ?>
								<div class="iawmlf_dashboard-stats-number">✓</div>
								<div class="iawmlf_dashboard-stats-label"><?php esc_html_e( 'All Links Processed', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
							<?php else : ?>
								<div class="iawmlf_dashboard-stats-number"><?php echo esc_html( $iawmlf_still_processing ); ?></div>
								<div class="iawmlf_dashboard-stats-label"><?php esc_html_e( 'Still Processing', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
							<?php endif; ?>
						</div>

						<!-- Row 2: With Archive | Without -->
						<div class="iawmlf_dashboard-stats-box iawmlf_dashboard-stats-box--success">
							<a href="<?php echo esc_url( $iawmlf_filtered_has_archive ); ?>" class="iawmlf_dashboard-stats-number iawmlf_dashboard-stats-link">
								<?php echo esc_html( $iawmlf_links_with_archive ); ?>
							</a>
							<div class="iawmlf_dashboard-stats-label"><?php esc_html_e( 'With Archive', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
						</div>
						<div class="iawmlf_dashboard-stats-box iawmlf_dashboard-stats-box--warning">
							<a href="<?php echo esc_url( $iawmlf_filtered_no_archive ); ?>" class="iawmlf_dashboard-stats-number iawmlf_dashboard-stats-link">
								<?php echo esc_html( $iawmlf_links_without_archive ); ?>
							</a>
							<div class="iawmlf_dashboard-stats-label"><?php esc_html_e( 'Without Archive', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
						</div>

						<!-- Row 3: Not Checked | Broken Links -->
						<div class="iawmlf_dashboard-stats-box iawmlf_dashboard-stats-box--info">
							<div class="iawmlf_dashboard-stats-number"><?php echo esc_html( $iawmlf_not_checked ); ?></div>
							<div class="iawmlf_dashboard-stats-label"><?php esc_html_e( 'Not Checked', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
						</div>
						<div class="iawmlf_dashboard-stats-box iawmlf_dashboard-stats-box--danger">
							<a href="<?php echo esc_url( $iawmlf_filtered_broken ); ?>" class="iawmlf_dashboard-stats-number iawmlf_dashboard-stats-link">
								<?php echo esc_html( $iawmlf_broken_links ); ?>
							</a>
							<div class="iawmlf_dashboard-stats-label"><?php esc_html_e( 'Broken Links', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
						</div>
					</div>
				</div>
			</div>

			<?php
			// Set up variables for the widget (it expects $iawmlf_details, not $iawmlf_account_details)
			$iawmlf_details = $iawmlf_account_details;

			// Create a total links array for the widget (pass the actual count)
			$iawmlf_total_links = array_fill( 0, $iawmlf_total_links_count, null ); // Mock array with correct count

			// Include the existing widget template
			require __DIR__ . '/widget.php';
			?>
		</div>
	</div>

</div>
