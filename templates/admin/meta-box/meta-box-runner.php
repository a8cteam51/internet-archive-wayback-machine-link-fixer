<?php
/**
 * Template for rendering the runner meta box
 *
 * @since      1.0.0
 *
 * Defined vars.
 * @var \WP_Post                           $post    The current post.
 * @var array{report:Report, logs:Log[]}[] $reports The reports for the post.
 */


use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Log;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Helper;

?>
<div id="wlf-meta-box-runner" class="wrap">
	<div>
		<label>Ignore Link Cache
			<input type="checkbox" name="wlf-meta-box-runner__ignore-cache" value="1" />
		</label>
	</div>
	<div>
		<label>Find Status Codes
			<input type="text" name="wlf-meta-box-runner__status-codes" value="<?php echo esc_html( Settings::get_http_status_codes() ); ?>" />
		</label>
	</div>
	<div>
		<button class="button" data-post="<?php echo esc_attr( $post->ID ); ?>">Run</button>
	</div>

</div>
<hr>
<div id="wlf-meta-box-results">
	<h2>Previous Reports</h2>
	<?php if ( empty( $reports ) ) : ?>
		<p>No reports found.</p>
	<?php else : ?>
		<?php foreach ( $reports as $wlf_report ) : ?>
			<div id="wlf_report__<?php echo esc_html( $wlf_report['report']->get_report_id() ); ?>" class="report">
				<p><strong><?php echo esc_html( $wlf_report['report']->get_created_at()->format( get_option( 'date_format' ) . ' : ' . get_option( 'time_format' ) ) ); ?></strong></p>
				<p>Found <?php echo absint( count( $wlf_report['logs'][0]->get_links() ) ); ?> links.</p>
				<p><a href="<?php echo esc_url( Report_Helper::get_single_report_link( $wlf_report['report'] ) ); ?>">View Report</a></p>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
