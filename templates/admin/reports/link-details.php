<?php
/**
 * Template for rendering the details of a link within a report.
 *
 * @since 1.2.0
 *
 * @var WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link $wlf_link The link.
 * @var \WP_Post[] $wlf_posts The posts that contain the link.
 * @var string $wlf_back_url The URL to return to the report.
 */

defined( 'ABSPATH' ) || exit;

// Check if we have any previous links to show.
$wlf_check_count      = count( $wlf_link->get_checks() );
$wlf_hide_check_count = $wlf_check_count > 10 ? absint( $wlf_check_count - 10 ) : 0;

// Generate the title.
$wlf_link_title = wpcomsp_wayback_link_fixer_trim_string( str_replace( array( 'http://', 'https://' ), '', $wlf_link->get_href() ), 55 );
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( $wlf_link_title ); ?></h1>
	<a class="page-title-action" href="<?php echo esc_url( $wlf_back_url ); ?>"><?php esc_html_e( 'Return to Links', 'wpcomsp_wayback_link_fixer' ); ?></a>
	<hr class="wp-header-end">


	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-1">
			<div id="postbox-container-2" class="postbox-container">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
					<div id="wlf_link_details" class="postbox ">
						<div class="postbox-header">
							<h2 class="handle ui-sortable-handle"><?php esc_html_e( 'Link Details', 'wpcomsp_wayback_link_fixer' ); ?></h2>
						</div>
						<div class="inside">
							<p class="wlf_link_url"><strong><?php esc_html_e( 'URL', 'wpcomsp_wayback_link_fixer' ); ?></strong>: <a href="<?php echo esc_url( $wlf_link->get_href() ); ?>" target="_blank"><?php echo esc_html( $wlf_link->get_href() ); ?></a></p>

							<?php if ( '' !== $wlf_link->get_archived_href() && ! $wlf_link->is_excluded() ) : ?>
								<p class="wlf_link_archived_url"><strong><?php esc_html_e( 'Archived URL', 'wpcomsp_wayback_link_fixer' ); ?></strong>: <a href="<?php echo esc_url( $wlf_link->get_archived_href() ); ?>" target="_blank"><?php echo esc_html( $wlf_link->get_archived_href() ); ?></a></p>
							<?php else : ?>
								<p class="wlf_link_archived_url"><strong><?php esc_html_e( 'Archived URL', 'wpcomsp_wayback_link_fixer' ); ?></strong>: <?php esc_html_e( 'No Archived Link Found', 'wpcomsp_wayback_link_fixer' ); ?></p>
							<?php endif; ?>

							<?php if ( '' !== $wlf_link->get_redirect_href() ) : ?>
								<p class="wlf_link_redirected_url"><strong><?php esc_html_e( 'Has Redirect URL', 'wpcomsp_wayback_link_fixer' ); ?></strong>: <?php echo esc_html( $wlf_link->get_redirect_href() ); ?></p>
							<?php endif; ?>

							<?php if ( '' !== $wlf_link->get_message() ) : ?>
								<p class="wlf_link_message"><strong><?php esc_html_e( 'Message', 'wpcomsp_wayback_link_fixer' ); ?></strong>: <?php echo esc_html( $wlf_link->get_message() ); ?></p>
							<?php endif; ?>

							<?php if ( $wlf_link->is_excluded() ) : ?>
								<p class="wlf_link_excluded"><strong><?php esc_html_e( 'Excluded', 'wpcomsp_wayback_link_fixer' ); ?></strong>: <?php esc_html_e( 'This link does not allow indexing', 'wpcomsp_wayback_link_fixer' ); ?></p>
							<?php endif; ?>
						</div>
					</div>


					<div id="wlf_link_checks" class="postbox ">
						<div class="postbox-header">
							<h2 class="handle ui-sortable-handle"><?php esc_html_e( 'Link Checks', 'wpcomsp_wayback_link_fixer' ); ?></h2>
						</div>
						<div class="inside">
							<?php if ( $wlf_link->is_broken() ) : ?>
								<p class="wlf_link_broken"><strong><?php esc_html_e( 'Is Broken', 'wpcomsp_wayback_link_fixer' ); ?></strong>: Yes</p>
							<?php else : ?>
								<p class="wlf_link_broken"><strong><?php esc_html_e( 'Is Broken', 'wpcomsp_wayback_link_fixer' ); ?></strong>: No</p>
							<?php endif; ?>
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Date', 'wpcomsp_wayback_link_fixer' ); ?></th>
										<th><?php esc_html_e( 'Status', 'wpcomsp_wayback_link_fixer' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php if ( 0 === count( $wlf_link->get_checks() ) ) : ?>
										<tr>
											<td colspan="2"><?php esc_html_e( 'No checks found for this link.', 'wpcomsp_wayback_link_fixer' ); ?></td>
										</tr>
									<?php endif; ?>

									<?php if ( $wlf_hide_check_count > 0 ) : ?>
										<tr id="wlf_reveal_hidden_checks">
											<td colspan="2">
												<button class="button" id="wlf_show_hidden_checks">
													<?php esc_html_e( 'Show Previous Checks', 'wpcomsp_wayback_link_fixer' ); ?>
												</button>
											</td>
										</tr>
									<?php endif; ?>

									<?php foreach ( $wlf_link->get_checks() as $wlf_index => $wlf_check ) : ?>
										<?php // Hide the first n posts to the value of $wlf_hide_check_count. ?>
										<?php if ( $wlf_index < $wlf_hide_check_count ) : ?>
											<tr class="wlf_hidden_check" style="display: none;">
										<?php else : ?>
											<tr>
										<?php endif; ?>
												<td><?php echo esc_html( \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', esc_attr( $wlf_check['date'] ) )->format( wpcomsp_wayback_link_fixer_get_date_format() ) ); ?></td>
												<?php if ( is_numeric( $wlf_check['http_code'] ) ) : ?>
													<td class="wlf-archived__http-code"><a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/<?php echo esc_url( $wlf_check['http_code'] ); ?>" target="_blank"><?php echo esc_html( $wlf_check['http_code'] ); ?></a></td>
												<?php else : ?>
													<td class="wlf-archived__error"><?php esc_html_e( 'Error', 'wpcomsp_wayback_link_fixer' ); ?></td>
												<?php endif; ?>
											</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>

					<div id="wlf_link_posts" class="postbox ">
						<div class="postbox-header">
							<h2 class="handle ui-sortable-handle"><?php esc_html_e( 'Places This Link Appears', 'wpcomsp_wayback_link_fixer' ); ?></h2>
						</div>
						<div class="inside">
							<?php if ( empty( $wlf_posts ) ) : ?>
								<p><?php esc_html_e( 'No posts found for this link.', 'wpcomsp_wayback_link_fixer' ); ?></p>
							<?php else : ?>
								<table class="wp-list-table widefat fixed striped">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Post', 'wpcomsp_wayback_link_fixer' ); ?></th>
											<th><?php esc_html_e( 'Post Type', 'wpcomsp_wayback_link_fixer' ); ?></th>
											<th><?php esc_html_e( 'Status', 'wpcomsp_wayback_link_fixer' ); ?></th>
											<th><?php esc_html_e( 'Actions', 'wpcomsp_wayback_link_fixer' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $wlf_posts as $wlf_post ) : ?>
											<tr>
												<td>
													<a href="<?php echo esc_url( get_edit_post_link( $wlf_post->ID ) ); ?>">
														<?php if ( '' === $wlf_post->post_title ) : ?>
															<?php
															printf(
																// Translators: %1$d is the post ID, %2$s is the post type.
																'No title [#%1$d - %2$s]',
																absint( $wlf_post->ID ),
																esc_html( $wlf_post->post_type )
															);
															?>
														<?php else : ?>
															<?php echo esc_html( wpcomsp_wayback_link_fixer_trim_string( $wlf_post->post_title, 50 ) ); ?>
														<?php endif; ?>
													</a>
												</td>
												<td><?php echo wpcomsp_wayback_link_fixer_get_admin_post_type_link( $wlf_post->post_type );  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, escaped in function ?></td>
												<td>
													<?php
													// Get the post status.
													$wlf_post_status = get_post_status( $wlf_post->ID );
													?>
													<?php if ( 'publish' === $wlf_post_status ) : ?>
														<span class="wlf-archived__redirect"><?php esc_html_e( 'Published', 'wpcomsp_wayback_link_fixer' ); ?></span>
													<?php elseif ( 'draft' === $wlf_post_status ) : ?>
														<span class="wlf-archived__redirect"><?php esc_html_e( 'Draft', 'wpcomsp_wayback_link_fixer' ); ?></span>
													<?php elseif ( 'pending' === $wlf_post_status ) : ?>
														<span class="wlf-archived__redirect"><?php esc_html_e( 'Pending', 'wpcomsp_wayback_link_fixer' ); ?></span>
													<?php elseif ( 'future' === $wlf_post_status ) : ?>
														<span class="wlf-archived__redirect"><?php esc_html_e( 'Scheduled', 'wpcomsp_wayback_link_fixer' ); ?></span>
													<?php else : ?>
														<span class="wlf-archived__redirect"><?php echo esc_html( $wlf_post_status ); ?></span>
													<?php endif; ?>
												</td>
												<td>
													<a href="<?php echo esc_url( get_edit_post_link( $wlf_post->ID ) ); ?>">
														<?php esc_html_e( 'Edit', 'wpcomsp_wayback_link_fixer' ); ?>
													</a>
													|
													<a href="<?php echo esc_url( get_permalink( $wlf_post->ID ) ); ?>">
														<?php esc_html_e( 'View', 'wpcomsp_wayback_link_fixer' ); ?>
													</a>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php if ( 0 !== $wlf_hide_check_count ) : ?>
	<script>
		// When revealing the hidden checks, show the hidden checks and hide the button.
		document.getElementById( 'wlf_show_hidden_checks' ).addEventListener( 'click', function() {
			document.querySelectorAll( '.wlf_hidden_check' ).forEach( function( element ) {
				element.style.display = 'table-row';
			} );

			document.getElementById( 'wlf_reveal_hidden_checks' ).style.display = 'none';
		} );
	</script>
<?php endif; ?>
