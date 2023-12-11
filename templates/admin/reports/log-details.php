<?php
/**
 * Template for rendering the details of a single log and links
 *
 * @since      1.0.0
 *
 * Defined vars.
 * @var \WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Log $log The Log
 */

?>
<div id="wlf-report-log__<?php echo absint( $log->get_id() ); ?>" class="wlf-report-log closed ">
	<div class="wlf-report-log__header">
		<p class="wlf-report-log__header-post-title">
			<span class="accordion-toggle dashicons dashicons-visibility show-log" data-action="show"></span>
			<span class="accordion-toggle dashicons dashicons-hidden hide-log" data-action="hide"></span>
			<?php echo wpcomsp_wayback_link_fixer_get_log_post_title( $log->get_post_id(), $log->get_blog_id() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</p>
		<p class="wlf-report-log__header-link-details" ><strong>
			<?php
			printf(
				// translators: %1$d is the number of links found, %2$s is the post title.
				esc_html__( 'Found %1$d links | %2$d broken', 'wpcomsp_wayback_link_fixer' ),
				count( $log->get_links() ),
				absint( $log->count_broken_links() )
			);
			?>
		</strong></p>
	</div>
	<div class="wlf-report-log__links">
		<?php if ( 0 === count( $log->get_links() ) ) : ?>
			<p><?php esc_html_e( 'No links found.', 'wpcomsp_wayback_link_fixer' ); ?></p>
		<?php endif; ?>
		<?php foreach ( $log->get_links() as $wlf_link ) : ?>
			<?php $wlf_link_http_class = sprintf( 'http_%s', $wlf_link->get_http_code() ?? 'none' ); ?>
			<div id="wlf-report-log__<?php echo absint( $wlf_link->get_index() ); ?>" class="wlf-report-link ">
				<div class="wlf-report-link__header <?php echo esc_attr( $wlf_link_http_class ); ?>">
					<p>
						<?php
						printf(
							// translators: %1$s is the dashicon for link status, %2$s is he url
							'<span class="dashicons %s"></span>%s',
							( true === $wlf_link->is_broken() ? 'dashicons-warning' : 'dashicons-yes-alt' ),
							esc_html( $wlf_link->get_href() ?? __( 'No href on link', 'wpcomsp_wayback_link_fixer' ) )
						);
						?>
						</p>
					<p><?php echo esc_attr( $wlf_link->get_http_code() ); ?></p>
				</div>
				<div class="wlf-report-link__body">
					<div class="wlf-report-link__html">
						<p>
						<?php
						printf(
							'<strong>%s</strong>: %s',
							esc_html__( 'Link Contents', 'wpcomsp_wayback_link_fixer' ),
							esc_attr( $wlf_link->get_contents() ?? __( 'No contents on link', 'wpcomsp_wayback_link_fixer' ) )
						);
						?>
						</p>
					</div>
					<div class="wlf-report-link__replacements">
						<p class="wlf-report-link__replacements-header"><strong><?php esc_html_e( 'Replacement Options', 'wpcomsp_wayback_link_fixer' ); ?></strong></p>
						<?php if ( empty( $wlf_link->get_replacement_options() ) ) : ?>
							<p class="wlf-report-link__replacements-row"><em><?php esc_html_e( 'No replacements found.', 'wpcomsp_wayback_link_fixer' ); ?></em></p>
						<?php else : ?>
							<?php foreach ( $wlf_link->get_replacement_options() as $wlf_link_replacement ) : ?>
								<p class="wlf-report-link__replacements-row">* <?php echo esc_url( $wlf_link_replacement ); ?></p>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<div class="wlf-report-link__comments">
						<p class="wlf-report-link__comments-header"><strong><?php esc_html_e( 'Comments', 'wpcomsp_wayback_link_fixer' ); ?></strong></p>
						<?php if ( empty( $wlf_link->get_comments() ) ) : ?>
							<p class="wlf-report-link__comments-row"><em><?php esc_html_e( 'No comments found.', 'wpcomsp_wayback_link_fixer' ); ?></em></p>
						<?php else : ?>
							<?php foreach ( $wlf_link->get_comments() as $wlf_link_comment ) : ?>
								<p class="wlf-report-link__comments-row"><?php echo esc_attr( $wlf_link_comment ); ?></p>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; // Endforeach through all links. ?>
	</div>
</div>
