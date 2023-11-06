<?php
/**
 * Template for rendering a single report
 *
 * @since      1.0.0
 *
 * Defined vars.
 * @var Report       $report      The Report
 * @var Log[]        $logs        The logs for the report.
 * @var string       $back_url    The url to go back to.
 * @var WP_User|null $author      The author of the report.
 * @var string       $%site_title The site title (not used if not a multi site)
 */


use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Log;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;

?>
<div id="wlf-report" class="wrap">

	<h1><?php esc_html_e( 'Report Details', 'wpcomsp_wayback_link_fixer' ); ?></h1>
	<div class="wlf-report-details">

	</div>
	<!-- The Logs -->
	<div id="wlf-report-logs">
		<?php if ( 0 === count( $logs ) ) : ?>
			<p><?php esc_html_e( 'No logs found.', 'wpcomsp_wayback_link_fixer' ); ?></p>
		<?php else : ?>
			<?php foreach ( $logs as $wlf_log ) : ?>
				<?php
				$wlf_log_post_title = get_the_title( $wlf_log->get_post_id() );
				$wlf_log_post_title = '' === $wlf_log_post_title
					? esc_html__( 'Post Not Found', 'wpcomsp_wayback_link_fixer' )
					: '<a href="' . esc_url( get_edit_post_link( $wlf_log->get_post_id() ) ) . '">' . esc_html( $wlf_log_post_title ) . '</a>';

				?>
				<div id="wlf-report-log__<?php echo absint( $wlf_log->get_id() ); ?>" class="wlf-report-log closed">
					<p class="wlf-report-log__title">
						<?php echo $wlf_log_post_title; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</p>
					<p>
						<?php
						printf(
							// translators: %1$d is the number of links found, %2$s is the post title.
							'Found %1$d links on %2$s',
							count( $wlf_log->get_links() ),
							get_post_type( $wlf_log->get_post_id() ) ?: esc_html__( 'Unknown type', 'wpcomsp_wayback_link_fixer' ) //phpcs:ignore Universal.Operators.DisallowShortTernary.Found, WordPress.Security.EscapeOutput.OutputNotEscaped
						);
						?>
					</p>
				</div>
			<?php endforeach; ?>
			<?php endif; ?>
	</div>
</div>
