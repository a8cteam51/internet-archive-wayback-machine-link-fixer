<?php
/**
 * Template for displaying a list of links with their associated posts.
 * This template is reused for both "Recent Link Checks" and "Latest Links" sections.
 *
 * @since 1.3.0
 *
 * @param array  $iawmlf_links    Array of recent links
 * @param boolean $iawmlf_is_active     Whether this section is currently active (expanded) or not.
 * @param string $iawmlf_section_id    The HTML ID for this section (e.g., 'recent-checks' or 'latest-links').
 * @param string $iawmlf_link_table    URL to the links report page.
 * @param string $iawmlf_no_links_message Message to display when no links are available.
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="iawmlf_dashboard-accordion-content iawmlf_dashboard-accordion-content<?php echo esc_attr( $iawmlf_is_active ? '--active' : '' ); ?>" id="<?php echo esc_attr( $iawmlf_section_id ); ?>">
	<div class="iawmlf_dashboard-link-checks">
		<?php if ( ! empty( $iawmlf_links ) ) : ?>
			<?php foreach ( $iawmlf_links as $iawmlf_check_data ) : ?>
				<?php
				$iawmlf_link  = $iawmlf_check_data['link'] ?? null;     // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, not a global
				$iawmlf_posts = $iawmlf_check_data['posts'] ?? array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, not a global
				if ( ! $iawmlf_link ) {
					continue;
				}
				?>
				<div class="iawmlf_dashboard-link-check-item">
					<div class="iawmlf_dashboard-link-check-header">
						<div class="iawmlf_dashboard-link-check-url">
							<span class="iawmlf_dashboard-link-check-status <?php echo esc_attr( $iawmlf_link->is_broken() ? 'broken' : 'working' ); ?>">
								<span class="dashicons <?php echo esc_attr( $iawmlf_link->is_broken() ? 'dashicons-no-alt' : 'dashicons-yes-alt' ); ?>"></span>
							</span>
							<a href="<?php echo esc_url( add_query_arg( array( 'iawmlf_link_id' => $iawmlf_link->get_id() ), $iawmlf_link_table ) ); ?>" class="iawmlf_dashboard-link-check-title">
								<?php echo esc_html( $iawmlf_link->get_href() ); ?>
							</a>
						</div>
						<div class="iawmlf_dashboard-link-check-meta">
							<?php if ( $iawmlf_link->get_last_check() ) : ?>
								<span class="iawmlf_dashboard-link-check-date">
									<?php
									$iawmlf_last_check = $iawmlf_link->get_last_check();
									$iawmlf_date_time  = DateTimeImmutable::createFromFormat(
										'Y-m-d H:i:s',
										$iawmlf_last_check['date']
									);
									$iawmlf_http_code  = $iawmlf_last_check['http_code'] ?? null;

									// Clean HTTP code for link
									$iawmlf_clean_http_code = $iawmlf_http_code ? preg_replace( '/[^0-9]/', '', (string) $iawmlf_http_code ) : null;

									$iawmlf_http_status_display = $iawmlf_clean_http_code
										? sprintf(
											'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s %3$s</a>',
											esc_url( sprintf( 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/%d', (int) $iawmlf_clean_http_code ) ),
											esc_html( (string) $iawmlf_clean_http_code ),
											esc_html__( 'status', 'internet-archive-wayback-machine-link-fixer' )
										)
										: esc_html__( 'No HTTP Code', 'internet-archive-wayback-machine-link-fixer' );

									if ( $iawmlf_date_time && $iawmlf_clean_http_code ) {
										printf(
											/* translators: %1$s is the last check date, %2$s is the last check http code. */
											esc_html__( '%1$s with %2$s', 'internet-archive-wayback-machine-link-fixer' ),
											esc_html( $iawmlf_date_time->format( 'j M Y' ) ),
											$iawmlf_http_status_display // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, already escaped above
										);
									} elseif ( $iawmlf_date_time ) {
										printf(
											/* translators: %s: last checked date */
											esc_html__( 'Checked: %s', 'internet-archive-wayback-machine-link-fixer' ),
											esc_html( $iawmlf_date_time->format( 'j M Y' ) )
										);
									}
									?>
								</span>
							<?php endif; ?>
						</div>
					</div>
					<?php if ( ! empty( $iawmlf_posts ) ) : ?>
						<div class="iawmlf_dashboard-link-check-posts">
							<div class="iawmlf_dashboard-link-check-details">
								<div class="iawmlf_dashboard-link-check-details-item">
									<strong><?php esc_html_e( 'Link Details:', 'internet-archive-wayback-machine-link-fixer' ); ?></strong>
									<a href="<?php echo esc_url( add_query_arg( array( 'iawmlf_link_id' => $iawmlf_link->get_id() ), $iawmlf_link_table ) ); ?>" class="iawmlf_dashboard-link-details-link">
										<?php esc_html_e( 'View Full Report', 'internet-archive-wayback-machine-link-fixer' ); ?>
									</a>
								</div>
							</div>
							<span class="iawmlf_dashboard-link-check-posts-label">
								<?php
								printf(
									/* translators: %d: number of posts */
									esc_html( _n( 'Found in %d post:', 'Found in %d posts:', count( $iawmlf_posts ), 'internet-archive-wayback-machine-link-fixer' ) ),
									count( $iawmlf_posts )
								);
								?>
							</span>
							<div class="iawmlf_dashboard-link-check-posts-list">
								<?php
								$iawmlf_displayed_posts = array_slice( $iawmlf_posts, 0, 12 ); // Show max 12 posts now
								foreach ( $iawmlf_displayed_posts as $iawmlf_displayed_post ) :
									?>
									<a href="<?php echo esc_url( get_edit_post_link( $iawmlf_displayed_post->ID ) ); ?>" class="iawmlf_dashboard-link-check-post">
										<?php echo esc_html( '' !== $iawmlf_displayed_post->post_title ? $iawmlf_displayed_post->post_title : __( '(No title)', 'internet-archive-wayback-machine-link-fixer' ) ); ?>
									</a>
								<?php endforeach; ?>
								<?php if ( count( $iawmlf_posts ) > 12 ) : ?>
									<span class="iawmlf_dashboard-link-check-posts-more">
										<?php
										printf(
											/* translators: %d: number of additional posts */
											esc_html__( '... and %d more', 'internet-archive-wayback-machine-link-fixer' ),
											count( $iawmlf_posts ) - 12
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
			<div class="iawmlf_dashboard-link-checks-empty">
				<p><?php echo esc_html( $iawmlf_no_links_message ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>
