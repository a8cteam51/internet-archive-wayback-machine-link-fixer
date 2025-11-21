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

<input type="hidden" name="iawmlf_wizard_activate_auto_archiver" value="<?php echo esc_attr( Settings::add_own_links( true ) ? '1' : '0' ); ?>"  />

<div class="iawmlf-wizard__content__intro">
	<p><?php esc_html_e( 'In addition to fixing broken links, this plugin can preserve your content on the Wayback Machine in case your site goes offline. New posts will be preserved when published, and we\'ll schedule regular snapshots so your content stays preserved over time.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
</div>

<div class="iawmlf-wizard__content__field" >
	<label for="iawmlf_wizard_post_types">
		<?php esc_html_e( 'Automatically preserve:', 'internet-archive-wayback-machine-link-fixer' ); ?>
	</label>
	<div class="iawmlf-wizard__content__inner-field checkboxes">
		<?php foreach ( $post_types as $iawmlf_pt_slug => $iawmlf_pt_name ) : ?>
			<div class="inner-spaced-between__list">
				<label>
					<?php echo esc_html( $iawmlf_pt_name ); ?>
				</label>
					<input type="checkbox" name="iawmlf_wizard_post_types[]" value="<?php echo esc_attr( $iawmlf_pt_slug ); ?>" <?php checked( in_array( $iawmlf_pt_slug, Settings::own_link_allowed_post_types(), true ) ); ?> />
			</div>
		<?php endforeach; ?>
	</div>
</div>


<?php echo wp_kses( $footer, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>
