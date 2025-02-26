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
	<h2><?php esc_html_e( 'Step 2: Configure the Link Fixer', 'wpcomsp_wayback_link_fixer' ); ?></h2>
</div>

<div class="wlf-wizard__content__intro">
	<p><?php esc_html_e( 'If you wish to create more than 200 links per month, you will need to get a free account with archive.org and enter the supplied credentials here.', 'wpcomsp_wayback_link_fixer' ); ?></p>
</div>

<div class="wlf-wizard__content__field">
	<div class="wlf-wizard__content__inner-field checkbox">
		<label for="wlf_wizard_activate_link_fixer">
			<?php esc_html_e( 'Enable link fixer', 'wpcomsp_wayback_link_fixer' ); ?>
		</label>
		<input type="checkbox" id="is_active" name="wlf_wizard_activate_link_fixer" value="1" <?php checked( Settings::is_link_processing_enabled() ); ?> />
	</div>
</div>

<div class="wlf-wizard__content__field is_optional" >
	<label for="wlf_wizard_post_types">
		<?php esc_html_e( 'Post Types', 'wpcomsp_wayback_link_fixer' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'Select the post types you want to enable the link fixer for.', 'wpcomsp_wayback_link_fixer' ); ?></p>
	<div class="wlf-wizard__content__inner-field checkboxes">
		<?php foreach ( $post_types as $wlf_pt_slug => $wlf_pt_name ) : ?>
			<label>
				<input type="checkbox" name="wlf_wizard_post_types[]" value="<?php echo esc_attr( $wlf_pt_slug ); ?>" <?php checked( in_array( $wlf_pt_slug, Settings::get_allowed_post_types(), true ) ); ?> />
				<?php echo esc_html( $wlf_pt_name ); ?>
			</label>
		<?php endforeach; ?>
	</div>
</div>

<div class="wlf-wizard__content__field  is_optional">
	<div class="wlf-wizard__content__inner-field checkbox">
		<label for="wlf_wizard_scan_existing_content">
			<?php esc_html_e( 'Scan existing content', 'wpcomsp_wayback_link_fixer' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'If enabled, all existing posts will be scanned and processed.', 'wpcomsp_wayback_link_fixer' ); ?></p>
		<input type="checkbox" id="wlf_is_active" name="wlf_wizard_scan_existing_content" value="1" <?php checked( Settings::should_scan_existing_posts() ); ?> />
	</div>
</div>

<div class="wlf-wizard__content__field is_optional" >
	<label for="wlf_wizard_outcome">
		<?php esc_html_e( 'Broken Link Outcome', 'wpcomsp_wayback_link_fixer' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'When a link is considered as broken, what should the outcome be?', 'wpcomsp_wayback_link_fixer' ); ?></p>
	<select
		id="wlf_wizard_outcome"
		name="wlf_wizard_outcome"
	>
		<option value="<?php echo esc_attr( Settings::FIXER_OPTION_REPLACE_LINK ); ?>" <?php selected( Settings::get_fixer_option(), Settings::FIXER_OPTION_REPLACE_LINK ); ?>>
			<?php esc_html_e( 'Replace Link (No Notification)', 'wpcomsp_wayback_link_fixer' ); ?>
		</option>
		<option value="<?php echo esc_attr( Settings::FIXER_OPTION_DO_NOTHING ); ?>" <?php selected( Settings::get_fixer_option(), Settings::FIXER_OPTION_DO_NOTHING ); ?>>
			<?php esc_html_e( 'Do Nothing', 'wpcomsp_wayback_link_fixer' ); ?>
		</option>
	</select>
</div>



<?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>


