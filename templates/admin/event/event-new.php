<?php
/**
 * Renders the new event form.
 *
 * @since      1.0.0
 */

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;

// Get a post types label.
$wlf_cpt_label = function ( string $cpt ): string {
	$data = get_post_type_object( $cpt );
	if ( ! $data ) {
		return $cpt;
	}
	return $data->label;
};

?>
<div id="wlf-events-trigger">
	<h2><?php esc_html_e( 'Run a new check', 'wpcomsp_wayback_link_fixer' ); ?></h2>
	<div class="wlf-event-trigger-row">
		<p><strong><?php esc_html_e( 'HTTP Status Codes to check', 'wpcomsp_wayback_link_fixer' ); ?></strong></p>
		<input type="text" id="event_http" value="<?php echo esc_attr( Settings::get_http_status_codes() ); ?>">
		<p class="description"><?php esc_html_e( 'Comma separated list of HTTP status codes to check.', 'wpcomsp_wayback_link_fixer' ); ?></p>
	</div>
	<div class="wlf-event-trigger-row">
		<p class="with-checkbox"><strong>
			<?php esc_html_e( 'Ignore Link Cache', 'wpcomsp_wayback_link_fixer' ); ?></strong>
			<input type="checkbox" id="event_ignore_cache">
			<span class="description"><?php esc_html_e( 'Ignore the link cache and check all links.', 'wpcomsp_wayback_link_fixer' ); ?></span>
		</p>
	</div>
	<div class="wlf-event-trigger-row">
		<p><strong><?php esc_html_e( 'Post Types to check', 'wpcomsp_wayback_link_fixer' ); ?></strong></p>
		<div class="checkbox-grid">
			<?php foreach ( Settings::get_post_types() as $wlf_cpt ) : ?>
				<label>
					<?php echo esc_html( $wlf_cpt_label( $wlf_cpt ) ); ?>
					<input type="checkbox" id="event_post_types_<?php echo esc_attr( $wlf_cpt ); ?>" name="event_post_types[]" checked value="<?php echo esc_attr( $wlf_cpt ); ?>">
				</label>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php esc_html_e( 'Select the post types to check.', 'wpcomsp_wayback_link_fixer' ); ?></p>
	</div>
	<div class="wlf-event-trigger-row">
		<p><strong><?php esc_html_e( 'Posts to ignore', 'wpcomsp_wayback_link_fixer' ); ?></strong></p>
		<select id="wlf_event_ignore_posts" class="select2" multiple style="width: 100%"></select>
		<p class="description"><?php esc_html_e( 'Select the posts to ignore.', 'wpcomsp_wayback_link_fixer' ); ?></p>
		<p id="wlf-event-select2-errors"></p>
	</div>
	<div class="wlf-event-trigger-row">
		<input type="button" id="event_trigger" class="button" value="<?php esc_html_e( 'Run Check', 'wpcomsp_wayback_link_fixer' ); ?>">
	</div>
</div>
