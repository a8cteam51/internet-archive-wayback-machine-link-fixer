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

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
?>

<?php echo $header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<div class="wlf-wizard__content__header">
	<h2><?php esc_html_e( 'Step 3: Configure the Auto Archiver', 'wpcomsp_wayback_link_fixer' ); ?></h2>
</div>

<div class="wlf-wizard__content__intro">
	<p><?php esc_html_e( 'Easily preserve your website’s content by enabling automatic archiving with the Internet Archive, setting up routine backups, and choosing which post types to include.', 'wpcomsp_wayback_link_fixer' ); ?></p>
</div>

<div class="wlf-wizard__content__field">
	<div class="wlf-wizard__content__inner-field checkbox">
		<label for="wlf_wizard_activate_auto_archiver">
			<?php esc_html_e( 'Enable auto archiver', 'wpcomsp_wayback_link_fixer' ); ?>
		</label>
		<input type="checkbox" id="is_active" name="wlf_wizard_activate_auto_archiver" value="1" <?php checked( Settings::is_link_processing_enabled() ); ?> />
	</div>
</div>

<div class="wlf-wizard__content__field is_optional" >
	<label for="wlf_wizard_post_types">
		<?php esc_html_e( 'Post Types', 'wpcomsp_wayback_link_fixer' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'Select which post types should be archived on the Wayback Machine.', 'wpcomsp_wayback_link_fixer' ); ?></p>
	<div class="wlf-wizard__content__inner-field checkboxes">
		<?php foreach ( $post_types as $wlf_pt_slug => $wlf_pt_name ) : ?>
			<label>
				<input type="checkbox" name="wlf_wizard_post_types[]" value="<?php echo esc_attr( $wlf_pt_slug ); ?>" <?php checked( in_array( $wlf_pt_slug, Settings::own_link_allowed_post_types(), true ) ); ?> />
				<?php echo esc_html( $wlf_pt_name ); ?>
			</label>
		<?php endforeach; ?>
	</div>
</div>

<div class="wlf-wizard__content__field  is_optional">
	<div class="wlf-wizard__content__inner-field checkbox">
		<label for="wlf_wizard_recurring_backup">
			<?php esc_html_e( 'Routinely auto archive posts', 'wpcomsp_wayback_link_fixer' ); ?>
		</label>
	<p class="description"><?php esc_html_e( 'If enabled, your posts will be routinely updated on the Wayback Machine.', 'wpcomsp_wayback_link_fixer' ); ?></p>
		<input type="checkbox" name="wlf_wizard_recurring_backup" value="1" <?php checked( Settings::is_link_processing_enabled() ); ?> />
	</div>
</div>

<?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
