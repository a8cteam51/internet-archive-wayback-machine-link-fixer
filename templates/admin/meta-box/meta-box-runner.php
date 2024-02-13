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
	<div class="checkbox_container">
		<label for="wlf-meta-box-runner__ignore-cache">Ignore Link Cache</label>
		<input type="checkbox" name="wlf-meta-box-runner__ignore-cache" value="1" />
	</div>
	<div class="text_container">
		<label for="wlf-meta-box-runner__status-codes">Find Status Codes</label>
		<input type="text" name="wlf-meta-box-runner__status-codes" value="<?php echo esc_html( Settings::get_http_status_codes() ); ?>" />
	</div>
	<div class="button_container">
		<button class="button" data-post="<?php echo esc_attr( $post->ID ); ?>">Run</button>
	</div>

</div>
<div id="wlf-meta-box-results__progress" style="display: none;">
	<div id="wlf-meta-box-results__progress-bar"></div>
</div>
<hr>
<div id="wlf-meta-box-results">
	<?php wpcomsp_wayback_link_fixer_render_template( 'admin/meta-box/meta-box-report-list.php', array( 'reports' => $reports ) ); ?>
</div>
