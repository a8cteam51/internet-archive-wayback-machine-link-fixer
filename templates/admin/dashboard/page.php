<?php
/**
 * Template for the dashboard page.
 *
 * @since 1.3.0
 *
 * @param array  $wlf_account_details       The account details from Archive.org.
 * @param bool   $wlf_api_configured   Whether the Archive.org API is configured.
 * @param bool   $wlf_is_online        Whether the Archive.org API is online.
 * @param string $wlf_link_to_settings URL to the settings page.
 * @param string $wlf_link_table       URL to the links report page.
 * @param string $wlf_report_page_base Base URL to the report page.
 * @param string $wlf_filtered_broken  URL to filtered broken links report.
 * @param string $wlf_filtered_valid   URL to filtered valid links report.
 * @param string $wlf_filtered_has_archive URL to filtered links with archive report.
 * @param string $wlf_filtered_no_archive  URL to filtered links without archive report.
 * @param array  $wlf_total_links            Array of all links in the system (for widget).
 * @param bool   $wlf_auto_archiver_enabled   Whether auto archiver is enabled.
 * @param bool   $wlf_scan_existing_enabled   Whether scanning existing posts is enabled.
 * @param bool   $wlf_link_processing_enabled Whether link processing is enabled.
 * @param int    $wlf_link_check_duration     Number of days between link checks.
 * @param int    $wlf_failed_check_count      Number of failed checks before marking as broken.
 * @param array  $wlf_link_stats       Link statistics array.
 * @param array  $wlf_last_checks      Array of last 10 link checks with associated posts.
 * @param array  $wlf_latest_links     Array of latest links with associated posts.
 */

defined( 'ABSPATH' ) || exit;

// Extract link statistics
$wlf_total_links_count     = $wlf_link_stats['total_links'] ?? 0;
$wlf_broken_links          = $wlf_link_stats['broken_links'] ?? 0;
$wlf_links_with_archive    = $wlf_link_stats['links_with_archive'] ?? 0;
$wlf_links_without_archive = $wlf_link_stats['links_without_archive'] ?? 0;
$wlf_not_checked           = $wlf_link_stats['not_checked'] ?? 0;
$wlf_process_done          = $wlf_link_stats['process_done'] ?? 0;
$wlf_process_new           = $wlf_link_stats['process_new'] ?? 0;
$wlf_process_pending       = $wlf_link_stats['process_pending'] ?? 0;
$wlf_still_processing      = $wlf_process_new + $wlf_process_pending;
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Wayback Link Fixer - Dashboard', 'internet-archive-wayback-machine-link-fixer' ); ?></h1>
	<div class="wlf_dashboard-page-grid">
		<!-- Left Column (66%) - Link Details -->
		<div class="wlf_dashboard-page-main">
			<div class="wlf_dashboard-page-column-wrapper">
				<!-- Link Activity Accordion -->
				<div class="wlf_dashboard-wrapper">
					<div class="wlf_dashboard-status-section">
						<div class="wlf_dashboard-accordion">
							<!-- Accordion Navigation -->
							<div class="wlf_dashboard-accordion-nav">
								<button class="wlf_dashboard-accordion-tab wlf_dashboard-accordion-tab--active" data-tab="recent-checks">
									<?php esc_html_e( 'Recent Link Checks', 'internet-archive-wayback-machine-link-fixer' ); ?>
								</button>
								<button class="wlf_dashboard-accordion-tab" data-tab="latest-links">
									<?php esc_html_e( 'Latest Links', 'internet-archive-wayback-machine-link-fixer' ); ?>
								</button>
							</div>

							<!-- Recent Link Checks Content -->
							<div class="wlf_dashboard-accordion-content wlf_dashboard-accordion-content--active" id="recent-checks">
								<div class="wlf_dashboard-link-checks">
									<?php if ( ! empty( $wlf_last_checks ) ) : ?>
										<?php foreach ( $wlf_last_checks as $wlf_check_data ) : ?>
											<?php
											$link  = $wlf_check_data['link'] ?? null;     // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, not a global
											$link_posts = $wlf_check_data['posts'] ?? array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, not a global
											if ( ! $link ) {
												continue;
											}
											?>
											<div class="wlf_dashboard-link-check-item">
												<div class="wlf_dashboard-link-check-header">
													<div class="wlf_dashboard-link-check-url">
														<span class="wlf_dashboard-link-check-status <?php echo esc_attr( $link->is_broken() ? 'broken' : 'working' ); ?>">
															<span class="dashicons <?php echo esc_attr( $link->is_broken() ? 'dashicons-no-alt' : 'dashicons-yes-alt' ); ?>"></span>
														</span>
														<a href="<?php echo esc_url( add_query_arg( array( 'wlf_link_id' => $link->get_id() ), $wlf_link_table ) ); ?>" class="wlf_dashboard-link-check-title">
															<?php echo esc_html( $link->get_href() ); ?>
														</a>
													</div>
													<div class="wlf_dashboard-link-check-meta">
														<?php if ( $link->get_last_check() ) : ?>
															<span class="wlf_dashboard-link-check-date">
																<?php
																$wlf_last_check = $link->get_last_check();
																$wlf_date_time  = DateTimeImmutable::createFromFormat(
																	'Y-m-d H:i:s',
																	$wlf_last_check['date']
																);
																$wlf_http_code  = $wlf_last_check['http_code'] ?? null;

																// Clean HTTP code for link
																$wlf_clean_http_code = $wlf_http_code ? preg_replace( '/[^0-9]/', '', (string) $wlf_http_code ) : null;

																$wlf_http_status_display = $wlf_clean_http_code
																	? sprintf( '<a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/%s" target="_blank">%s status</a>', esc_attr( $wlf_clean_http_code ), esc_html( $wlf_clean_http_code ) )
																	: esc_html__( 'No HTTP Code', 'internet-archive-wayback-machine-link-fixer' );

																if ( $wlf_date_time && $wlf_clean_http_code ) {
																	printf(
																		/* translators: %1$s: last checked date, %2$s: HTTP status code with link */
																		esc_html__( '%1$s with %2$s', 'internet-archive-wayback-machine-link-fixer' ),
																		esc_html( $wlf_date_time->format( 'j M Y' ) ),
																		$wlf_http_status_display // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped - already escaped.
																	);
																} elseif ( $wlf_date_time ) {
																	printf(
																		/* translators: %s: last checked date */
																		esc_html__( 'Checked: %s', 'internet-archive-wayback-machine-link-fixer' ),
																		esc_html( $wlf_date_time->format( 'j M Y' ) )
																	);
																}
																?>
															</span>
														<?php endif; ?>
													</div>
												</div>
												<?php if ( ! empty( $link_posts ) ) : ?>
													<div class="wlf_dashboard-link-check-posts">
														<div class="wlf_dashboard-link-check-details">
															<div class="wlf_dashboard-link-check-details-item">
																<strong><?php esc_html_e( 'Link Details:', 'internet-archive-wayback-machine-link-fixer' ); ?></strong>
																<a href="<?php echo esc_url( add_query_arg( array( 'wlf_link_id' => $link->get_id() ), $wlf_link_table ) ); ?>" class="wlf_dashboard-link-details-link">
																	<?php esc_html_e( 'View Full Report', 'internet-archive-wayback-machine-link-fixer' ); ?>
																</a>
															</div>
														</div>
														<span class="wlf_dashboard-link-check-posts-label">
															<?php
															printf(
																/* translators: %d: number of posts */
																esc_html( _n( 'Found in %d post:', 'Found in %d posts:', count( $link_posts ), 'internet-archive-wayback-machine-link-fixer' ) ),
																count( $link_posts )
															);
															?>
														</span>
														<div class="wlf_dashboard-link-check-posts-list">
															<?php
															$displayed_posts = array_slice( $link_posts, 0, 12 ); // Show max 12 posts now
															foreach ( $displayed_posts as $post ) :
																?>
																<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" class="wlf_dashboard-link-check-post">
																	<?php echo esc_html( $post->post_title ?: __( '(No title)', 'internet-archive-wayback-machine-link-fixer' ) ); ?>
																</a>
															<?php endforeach; ?>
															<?php if ( count( $link_posts ) > 12 ) : ?>
																<span class="wlf_dashboard-link-check-posts-more">
																	<?php
																	printf(
																		/* translators: %d: number of additional posts */
																		esc_html__( '... and %d more', 'internet-archive-wayback-machine-link-fixer' ),
																		count( $link_posts ) - 12
																	);
																	?>
																</span>
															<?php endif; ?>
														</div>
													</div>
												<?php endif; ?>
											</div>
										<?php endforeach; ?>
									<?php else : ?>
										<div class="wlf_dashboard-link-checks-empty">
											<p><?php esc_html_e( 'No recent link checks available.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
										</div>
									<?php endif; ?>
								</div>
							</div>

							<!-- Latest Links Content -->
							<div class="wlf_dashboard-accordion-content" id="latest-links">
								<div class="wlf_dashboard-link-checks">
									<?php if ( ! empty( $wlf_latest_links ) ) : ?>
										<?php foreach ( $wlf_latest_links as $link_data ) : ?>
											<?php
											$link  = $link_data['link'] ?? null;
											$posts = $link_data['posts'] ?? array();
											if ( ! $link ) {
												continue;
											}
											?>
											<div class="wlf_dashboard-link-check-item">
												<div class="wlf_dashboard-link-check-header">
													<div class="wlf_dashboard-link-check-url">
														<span class="wlf_dashboard-link-check-status <?php echo esc_attr( $link->is_broken() ? 'broken' : 'working' ); ?>">
															<span class="dashicons <?php echo esc_attr( $link->is_broken() ? 'dashicons-no-alt' : 'dashicons-yes-alt' ); ?>"></span>
														</span>
														<a href="<?php echo esc_url( add_query_arg( array( 'wlf_link_id' => $link->get_id() ), $wlf_link_table ) ); ?>" class="wlf_dashboard-link-check-title">
															<?php echo esc_html( $link->get_href() ); ?>
														</a>
													</div>
													<div class="wlf_dashboard-link-check-meta">
														<?php if ( $link->get_last_check() ) : ?>
															<span class="wlf_dashboard-link-check-date">
																<?php
																$wlf_last_check = $link->get_last_check();
																$wlf_date_time  = DateTimeImmutable::createFromFormat(
																	'Y-m-d H:i:s',
																	$wlf_last_check['date']
																);
																$wlf_http_code  = $wlf_last_check['http_code'] ?? null;

																// Clean HTTP code for link
																$wlf_clean_http_code = $wlf_http_code ? preg_replace( '/[^0-9]/', '', (string) $wlf_http_code ) : null;

																$wlf_http_status_display = $wlf_clean_http_code
																	? sprintf( '<a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/%s" target="_blank">%s status</a>', esc_attr( $wlf_clean_http_code ), esc_html( $wlf_clean_http_code ) )
																	: esc_html__( 'No HTTP Code', 'internet-archive-wayback-machine-link-fixer' );

																if ( $wlf_date_time && $wlf_clean_http_code ) {
																	printf(
																		/* translators: %1$s: last checked date, %2$s: HTTP status code with link */
																		__( '%1$s with %2$s', 'internet-archive-wayback-machine-link-fixer' ),
																		esc_html( $wlf_date_time->format( 'j M Y' ) ),
																		$wlf_http_status_display
																	);
																} elseif ( $wlf_date_time ) {
																	printf(
																		/* translators: %s: last checked date */
																		esc_html__( 'Checked: %s', 'internet-archive-wayback-machine-link-fixer' ),
																		esc_html( $wlf_date_time->format( 'j M Y' ) )
																	);
																}
																?>
															</span>
														<?php endif; ?>
													</div>
												</div>
												<?php if ( ! empty( $posts ) ) : ?>
													<div class="wlf_dashboard-link-check-posts">
														<div class="wlf_dashboard-link-check-details">
															<div class="wlf_dashboard-link-check-details-item">
																<strong><?php esc_html_e( 'Link Details:', 'internet-archive-wayback-machine-link-fixer' ); ?></strong>
																<a href="<?php echo esc_url( add_query_arg( array( 'wlf_link_id' => $link->get_id() ), $wlf_link_table ) ); ?>" class="wlf_dashboard-link-details-link">
																	<?php esc_html_e( 'View Full Report', 'internet-archive-wayback-machine-link-fixer' ); ?>
																</a>
															</div>
														</div>
														<span class="wlf_dashboard-link-check-posts-label">
															<?php
															printf(
																/* translators: %d: number of posts */
																esc_html( _n( 'Found in %d post:', 'Found in %d posts:', count( $posts ), 'internet-archive-wayback-machine-link-fixer' ) ),
																count( $posts )
															);
															?>
														</span>
														<div class="wlf_dashboard-link-check-posts-list">
															<?php
															$displayed_posts = array_slice( $posts, 0, 12 ); // Show max 12 posts now
															foreach ( $displayed_posts as $post ) :
																?>
																<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" class="wlf_dashboard-link-check-post">
																	<?php echo esc_html( $post->post_title ?: __( '(No title)', 'internet-archive-wayback-machine-link-fixer' ) ); ?>
																</a>
															<?php endforeach; ?>
															<?php if ( count( $posts ) > 12 ) : ?>
																<span class="wlf_dashboard-link-check-posts-more">
																	<?php
																	printf(
																		/* translators: %d: number of additional posts */
																		esc_html__( '... and %d more', 'internet-archive-wayback-machine-link-fixer' ),
																		count( $posts ) - 12
																	);
																	?>
																</span>
															<?php endif; ?>
														</div>
													</div>
												<?php endif; ?>
											</div>
										<?php endforeach; ?>
									<?php else : ?>
										<div class="wlf_dashboard-link-checks-empty">
											<p><?php esc_html_e( 'No latest links available.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>



			</div>
			<!-- Right Column (33%) - Overview Stats & Widget -->
		<div class="wlf_dashboard-page-sidebar">
			<div class="wlf_dashboard-page-column-wrapper">
					<!-- Overview Stats -->
					<div class="wlf_dashboard-wrapper">
						<div class="wlf_dashboard-status-section">
					<h3 class="wlf_dashboard-section-title"><?php esc_html_e( 'Link Statistics Overview', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
					<div class="wlf_dashboard-stats-grid wlf_dashboard-stats-sidebar">
						<!-- Row 1: Total Links | Still Processing -->
						<div class="wlf_dashboard-stats-box">
							<a href="<?php echo esc_url( $wlf_link_table ); ?>" class="wlf_dashboard-stats-number" style="text-decoration: none; color: inherit;">
								<?php echo esc_html( $wlf_total_links_count ); ?>
							</a>
							<div class="wlf_dashboard-stats-label"><?php esc_html_e( 'Total Links', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
						</div>
						<div class="wlf_dashboard-stats-box <?php echo $wlf_still_processing === 0 ? 'wlf_dashboard-stats-box--success' : 'wlf_dashboard-stats-box--info'; ?>">
							<?php if ( $wlf_still_processing === 0 ) : ?>
								<div class="wlf_dashboard-stats-number">✓</div>
								<div class="wlf_dashboard-stats-label"><?php esc_html_e( 'All Links Processed', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
							<?php else : ?>
								<div class="wlf_dashboard-stats-number"><?php echo esc_html( $wlf_still_processing ); ?></div>
								<div class="wlf_dashboard-stats-label"><?php esc_html_e( 'Still Processing', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
							<?php endif; ?>
						</div>

						<!-- Row 2: With Archive | Without -->
						<div class="wlf_dashboard-stats-box wlf_dashboard-stats-box--success">
							<a href="<?php echo esc_url( $wlf_filtered_has_archive ); ?>" class="wlf_dashboard-stats-number" style="text-decoration: none; color: inherit;">
								<?php echo esc_html( $wlf_links_with_archive ); ?>
							</a>
							<div class="wlf_dashboard-stats-label"><?php esc_html_e( 'With Archive', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
						</div>
						<div class="wlf_dashboard-stats-box wlf_dashboard-stats-box--warning">
							<a href="<?php echo esc_url( $wlf_filtered_no_archive ); ?>" class="wlf_dashboard-stats-number" style="text-decoration: none; color: inherit;">
								<?php echo esc_html( $wlf_links_without_archive ); ?>
							</a>
							<div class="wlf_dashboard-stats-label"><?php esc_html_e( 'Without Archive', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
						</div>

						<!-- Row 3: Not Checked | Broken Links -->
						<div class="wlf_dashboard-stats-box wlf_dashboard-stats-box--info">
							<div class="wlf_dashboard-stats-number"><?php echo esc_html( $wlf_not_checked ); ?></div>
							<div class="wlf_dashboard-stats-label"><?php esc_html_e( 'Not Checked', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
						</div>
						<div class="wlf_dashboard-stats-box wlf_dashboard-stats-box--danger">
							<a href="<?php echo esc_url( $wlf_filtered_broken ); ?>" class="wlf_dashboard-stats-number" style="text-decoration: none; color: inherit;">
								<?php echo esc_html( $wlf_broken_links ); ?>
							</a>
							<div class="wlf_dashboard-stats-label"><?php esc_html_e( 'Broken Links', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
						</div>
					</div>
				</div>
			</div>

			<?php
			// Set up variables for the widget (it expects $wlf_details, not $wlf_account_details)
			$wlf_details = $wlf_account_details;

			// Create a total links array for the widget (pass the actual count)
			$wlf_total_links = array_fill( 0, $wlf_total_links_count, null ); // Mock array with correct count

			// Include the existing widget template
			require __DIR__ . '/widget.php';
			?>
		</div>
	</div>

</div>
