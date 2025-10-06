<?php
/**
 * Template for displaying a list of links with their associated posts.
 * This template is reused for both "Recent Link Checks" and "Latest Links" sections.
 *
 * @since 1.3.0
 *
 * @param array  $wlf_links    Array of recent links
 * @param boolean $wlf_is_active     Whether this section is currently active (expanded) or not.
 * @param string $wlf_section_id    The HTML ID for this section (e.g., 'recent-checks' or 'latest-links').
 * @param string $wlf_link_table    URL to the links report page.
 * @param string $wlf_no_links_message Message to display when no links are available.
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wlf_dashboard-accordion-content wlf_dashboard-accordion-content<?php echo esc_attr( $wlf_is_active ? '--active' : '' ); ?>" id="<?php echo esc_attr( $wlf_section_id ); ?>">
	<div class="wlf_dashboard-link-checks">
		<?php if ( ! empty( $wlf_links ) ) : ?>
			<?php foreach ( $wlf_links as $wlf_check_data ) : ?>
				<?php
				$wlf_link  = $wlf_check_data['link'] ?? null;     // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, not a global
				$wlf_posts = $wlf_check_data['posts'] ?? array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, not a global
				if ( ! $wlf_link ) {
					continue;
				}
				?>
				<div class="wlf_dashboard-link-check-item">
					<div class="wlf_dashboard-link-check-header">
						<div class="wlf_dashboard-link-check-url">
							<span class="wlf_dashboard-link-check-status <?php echo esc_attr( $wlf_link->is_broken() ? 'broken' : 'working' ); ?>">
								<span class="dashicons <?php echo esc_attr( $wlf_link->is_broken() ? 'dashicons-no-alt' : 'dashicons-yes-alt' ); ?>"></span>
							</span>
							<a href="<?php echo esc_url( add_query_arg( array( 'wlf_link_id' => $wlf_link->get_id() ), $wlf_link_table ) ); ?>" class="wlf_dashboard-link-check-title">
								<?php echo esc_html( $wlf_link->get_href() ); ?>
							</a>
						</div>
						<div class="wlf_dashboard-link-check-meta">
							<?php if ( $wlf_link->get_last_check() ) : ?>
								<span class="wlf_dashboard-link-check-date">
									<?php
									$wlf_last_check = $wlf_link->get_last_check();
									$wlf_date_time  = DateTimeImmutable::createFromFormat(
										'Y-m-d H:i:s',
										$wlf_last_check['date']
									);
									$wlf_http_code  = $wlf_last_check['http_code'] ?? null;

									// Clean HTTP code for link
									$wlf_clean_http_code = $wlf_http_code ? preg_replace( '/[^0-9]/', '', (string) $wlf_http_code ) : null;

									$wlf_http_status_display = $wlf_clean_http_code
										? sprintf(
											'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s %3$s</a>',
											esc_url( sprintf( 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/%d', (int) $wlf_clean_http_code ) ),
											esc_html( (string) $wlf_clean_http_code ),
											esc_html__( 'status', 'internet-archive-wayback-machine-link-fixer' )
										)
										: esc_html__( 'No HTTP Code', 'internet-archive-wayback-machine-link-fixer' );

									if ( $wlf_date_time && $wlf_clean_http_code ) {
										printf(
											/* translators: %1$s is the last check date, %2$s is the last check http code. */
											esc_html__( '%1$s with %2$s', 'internet-archive-wayback-machine-link-fixer' ),
											esc_html( $wlf_date_time->format( 'j M Y' ) ),
											$wlf_http_status_display // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, already escaped above
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
					<?php if ( ! empty( $wlf_posts ) ) : ?>
						<div class="wlf_dashboard-link-check-posts">
							<div class="wlf_dashboard-link-check-details">
								<div class="wlf_dashboard-link-check-details-item">
									<strong><?php esc_html_e( 'Link Details:', 'internet-archive-wayback-machine-link-fixer' ); ?></strong>
									<a href="<?php echo esc_url( add_query_arg( array( 'wlf_link_id' => $wlf_link->get_id() ), $wlf_link_table ) ); ?>" class="wlf_dashboard-link-details-link">
										<?php esc_html_e( 'View Full Report', 'internet-archive-wayback-machine-link-fixer' ); ?>
									</a>
								</div>
							</div>
							<span class="wlf_dashboard-link-check-posts-label">
								<?php
								printf(
									/* translators: %d: number of posts */
									esc_html( _n( 'Found in %d post:', 'Found in %d posts:', count( $wlf_posts ), 'internet-archive-wayback-machine-link-fixer' ) ),
									count( $wlf_posts )
								);
								?>
							</span>
							<div class="wlf_dashboard-link-check-posts-list">
								<?php
								$wlf_displayed_posts = array_slice( $wlf_posts, 0, 12 ); // Show max 12 posts now
								foreach ( $wlf_displayed_posts as $wlf_displayed_post ) :
									?>
									<a href="<?php echo esc_url( get_edit_post_link( $wlf_displayed_post->ID ) ); ?>" class="wlf_dashboard-link-check-post">
										<?php echo esc_html( '' !== $wlf_displayed_post->post_title ? $wlf_displayed_post->post_title : __( '(No title)', 'internet-archive-wayback-machine-link-fixer' ) ); ?>
									</a>
								<?php endforeach; ?>
								<?php if ( count( $wlf_posts ) > 12 ) : ?>
									<span class="wlf_dashboard-link-check-posts-more">
										<?php
										printf(
											/* translators: %d: number of additional posts */
											esc_html__( '... and %d more', 'internet-archive-wayback-machine-link-fixer' ),
											count( $wlf_posts ) - 12
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
				<p><?php echo esc_html( $wlf_no_links_message ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>
