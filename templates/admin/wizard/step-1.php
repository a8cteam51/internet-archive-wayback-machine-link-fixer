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
?>

<?php echo $header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<div class="wlf-wizard__content__header">
	<h2><?php esc_html_e( 'Step 1: Configure the Wayback Machine API', 'wpcomsp_wayback_link_fixer' ); ?></h2>
</div>

<div class="wlf-wizard__content__intro">
	<p><?php esc_html_e( 'If you wish to create more than 200 links per month, you will need to get a free account with archive.org and enter the supplied credentials here.', 'wpcomsp_wayback_link_fixer' ); ?></p>
</div>

<div class="wlf-wizard__content__field">
	<label for="wlf_wizard_archive_access_key">
		<?php esc_html_e( 'Archive.org Access Key', 'wpcomsp_wayback_link_fixer' ); ?>
	</label>
	<input type="text" name="wlf_wizard_archive_access_key" value="<?php echo esc_attr( $settings->get_archive_access_key() ); ?>" />
</div>
<div class="wlf-wizard__content__field">
	<label for="wlf_wizard_archive_secret_key">
		<?php esc_html_e( 'Archive.org Secret Key', 'wpcomsp_wayback_link_fixer' ); ?>
	</label>
	<input type="text" name="wlf_wizard_archive_secret_key" value="<?php echo esc_attr( $settings->get_archive_secret_key() ); ?>" />
</div>


<?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
