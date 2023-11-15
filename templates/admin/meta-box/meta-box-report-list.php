<?php

/**
 * Renders the list of reports for the metabox.
 *
 * @since      1.0.0
 *
 * Defined vars.
 * @var array{
 *  report:\WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report,
 *  logs:Log[]
 * }[] $reports The reports for the post.
 */

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Helper;
?>


<?php if ( empty( $reports ) ) : ?>
	<p>No reports found.</p>
<?php else : ?>
	<?php foreach ( array_reverse( $reports ) as $wlf_report ) : ?>
		<div id="wlf_report__<?php echo esc_html( $wlf_report['report']->get_report_id() ); ?>" class="report">
			<p><strong><?php echo esc_html( $wlf_report['report']->get_created_at()->format( get_option( 'date_format' ) . ' : ' . get_option( 'time_format' ) ) ); ?></strong></p>
			<p>Found <?php echo absint( count( $wlf_report['logs'][0]->get_links() ) ); ?> links.</p>
			<p><a href="<?php echo esc_url( Report_Helper::get_single_report_link( $wlf_report['report'] ) ); ?>">View Report</a></p>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
