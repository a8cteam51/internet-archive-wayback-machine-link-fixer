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
	<p class="wlf-report__back">
		<a href="<?php echo esc_url( $back_url ); ?>"><?php esc_html_e( '<< Back to Reports', 'wpcomsp_wayback_link_fixer' ); ?></a>
	</p>
	<div class="wlf-report-details">
		<p class="wlf-report-details__description">
			<?php
			printf(
				// translators: %1$s is the title and %2$s is the description.
				'<strong>%s</strong> : %s',
				esc_html__( 'Description', 'wpcomsp_wayback_link_fixer' ),
				esc_html( $report->get_description() )
			);
			?>
		</p>
		<?php if ( is_multisite() ) : ?>
			<p class="wlf-report-details__site">
			<?php
			printf(
				// translators: %1$s is the title and %2$s is the description.
				'<strong>%s</strong> : %s',
				esc_html__( 'Site', 'wpcomsp_wayback_link_fixer' ),
				esc_html( get_blog_option( $report->get_blog_id(), 'blogname' ) )
			);
			?>
		</p>
		<?php endif; ?>
		<p class="wlf-report-details__author">
			<?php
			printf(
				// translators: %1$s is the title and %2$s is the description.
				'<strong>%s</strong> : %s',
				esc_html__( 'Author', 'wpcomsp_wayback_link_fixer' ),
				esc_html( $author->display_name )
			);
			?>
		</p>
		<p class="wlf-report-details__date">
			<?php
			printf(
				// translators: %1$s is the title and %2$s is the description.
				'<strong>%s</strong> : %s',
				esc_html__( 'Date Created', 'wpcomsp_wayback_link_fixer' ),
				esc_html( $report->get_created_at()->format( get_option( 'date_format' ) . ' : ' . get_option( 'time_format' ) ) )
			);
			?>

			<?php if ( $report->get_completed_at() ) : ?>
				<?php
				printf(
					// translators: %1$s is the title and %2$s is the description.
					'<br> <strong>%s</strong> : %s',
					esc_html__( 'Date Completed', 'wpcomsp_wayback_link_fixer' ),
					esc_html( $report->get_completed_at()->format( get_option( 'date_format' ) . ' : ' . get_option( 'time_format' ) ) )
				);
				?>
			<?php endif; ?>

	</div>
	<!-- The Logs -->
	<div id="wlf-report-logs">
		<?php if ( 0 === count( $logs ) ) : ?>
			<p><?php esc_html_e( 'No logs found.', 'wpcomsp_wayback_link_fixer' ); ?></p>
		<?php else : ?>
			<?php foreach ( $logs as $wlf_log ) : ?>
				<?php wpcomsp_wayback_link_fixer_render_template( 'admin/reports/log-details.php', array( 'log' => $wlf_log ) ); ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
