<?php

/**
 * The template for the first step of the setup wizard.
 *
 * @since 1.3.0
 *
 * @param array  $step_data The step data.
 * @param Settings $settings The settings object.
 * @param array $post_types The post types.
 * @param string $header The header template.
 * @param string $footer The footer template.
 */

defined( 'ABSPATH' ) || exit;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

?>

<?php echo wp_kses( $header, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>
<input type="hidden" name="iawmlf_wizard_activate_link_fixer" value="<?php echo Settings::is_link_processing_enabled( true ) ? '1' : '0'; ?>" />

<div class="iawmlf-wizard__content__intro">
	<p><?php esc_html_e( 'We\'ll scan your site\'s content for links and redirect the broken ones to snapshots on the Wayback Machine of what those links used to show.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
</div>


<div class="iawmlf-wizard__content__field" >
	<label for="iawmlf_wizard_post_types">
		<?php esc_html_e( 'Automatically fix broken links in:', 'internet-archive-wayback-machine-link-fixer' ); ?>
	</label>
	<div class="iawmlf-wizard__content__inner-field checkboxes">
		<?php foreach ( $post_types as $iawmlf_pt_slug => $iawmlf_pt_name ) : ?>
			<div class="inner-spaced-between__list">
				<label>
					<?php echo esc_html( $iawmlf_pt_name ); ?>
				</label>
				<input type="checkbox" name="iawmlf_wizard_post_types[]" value="<?php echo esc_attr( $iawmlf_pt_slug ); ?>" <?php checked( in_array( $iawmlf_pt_slug, Settings::get_allowed_post_types(), true ) ); ?> />
			</div>
		<?php endforeach; ?>
	</div>
</div>




<?php echo wp_kses( $footer, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>
