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

<div class="iawmlf-wizard__content__header">
	<h2><?php esc_html_e( 'Step 3: Configure the Auto Archiver', 'internet-archive-wayback-machine-link-fixer' ); ?></h2>
</div>
<input type="hidden" name="iawmlf_wizard_activate_auto_archiver" value="<?php echo esc_attr( Settings::add_own_links( true ) ? '1' : '0' ); ?>"  />

<div class="iawmlf-wizard__content__intro">
	<p><?php esc_html_e( 'Easily preserve your website’s content by enabling automatic archiving with the Internet Archive, setting up regular archiving, and choosing which post types to include.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
</div>

<div class="iawmlf-wizard__content__field" >
	<label for="iawmlf_wizard_post_types">
		<?php esc_html_e( 'Post Types', 'internet-archive-wayback-machine-link-fixer' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'Select which post types should be archived on the Wayback Machine.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
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
