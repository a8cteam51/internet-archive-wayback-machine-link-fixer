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

// Default HTTP codes.
$wlf_default_http_codes = explode( ',', Settings::get_http_status_codes() );

?>

<table class="form-table" role="presentation">
	<tbody>
		<tr class="form-field">
			<th scope="row"><label for="user_login">HTTP Codes</label></th>
			<td>
				<select id="event_http" class="select2" multiple style="width: 100%">
					<?php foreach ( wpcomsp_wayback_link_fixer_get_http_codes() as $wlf_http_code ) : ?>
						<option value="<?php echo esc_attr( $wlf_http_code ); ?>" <?php echo in_array( (string) $wlf_http_code, $wlf_default_http_codes, true ) ? 'SELECTED' : ''; ?>>
							<?php echo esc_html( $wlf_http_code ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<label for="event_http">Which HTTP code(s) should be checked</label>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">Link Cache</th>
			<td>
				<input type="checkbox" name="event_ignore_cache" id="event_ignore_cache" value="1" checked="checked">
				<label for="event_ignore_cache">Ignore cache and check every link?</label>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row">Auto fix</th>
			<td>
				<input type="checkbox" name="event_fix_links" id="event_fix_links" value="1" checked="checked">
				<label for="event_fix_links">If archived link found, auto fix?</label>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="user_login">Post Types</label></th>
			<td>
				<select id="event_post_types" class="select2" multiple style="width: 100%">
					<?php foreach ( Settings::get_post_types() as $wlf_cpt ) : ?>
						<option value="<?php echo esc_attr( $wlf_cpt ); ?>" SELECTED>
							<?php echo esc_attr( ucfirst( $wlf_cpt ) ); ?>
						</option>
					<?php endforeach; ?>

				</select>
				<label for="event_post_types">Which post types should be checked.</label>
			</td>
		</tr>
	</tbody>
</table>

		<input type="button" id="event_trigger" class="button" value="<?php esc_html_e( 'Create Report', 'wpcomsp_wayback_link_fixer' ); ?>">

		<?php if ( ! is_multisite() ) : ?>
			<input type="hidden" id="wlf_event_blog_ids[1]" name="wlf_event_blog_ids[]" value="1">
		<?php else : ?>
			<?php if ( is_network_admin() ) : ?>
				<p><strong><?php esc_html_e( 'Sites to check', 'wpcomsp_wayback_link_fixer' ); ?></strong></p>
				<select id="wlf_event_blog_ids" class="select2" multiple style="width: 100%">
					<?php foreach ( get_sites() as $wlf_site ) : ?>
						<option value="<?php echo esc_attr( $wlf_site->blog_id ); ?>" SELECTED>
							<?php echo esc_html( $wlf_site->blogname ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e( 'A separate report will be generated per site selected.', 'wpcomsp_wayback_link_fixer' ); ?></p>
			<?php else : ?>
				<input type="hidden" id="wlf_event_blog_ids" name="wlf_event_blog_ids[]" value="<?php echo esc_attr( get_current_blog_id() ); ?>">
			<?php endif; ?>
		<?php endif; ?>
