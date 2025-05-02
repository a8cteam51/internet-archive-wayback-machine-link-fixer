<?php

/**
 * The template for the first step of the setup wizard.
 *
 * @since 1.3.0
 *
 * @param string $header The header template.
 * @param string $footer The footer template.
 */
?>

<?php echo $header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<div class="wlf-wizard__content__header">
	<h2><?php esc_html_e( 'Setup complete.', 'wpcomsp_wayback_link_fixer' ); ?></h2>
</div>

<div class="wlf-wizard__content__intro">
	<p>
	<?php
	printf(
		// translators: %1$s is the page title, %2$s is the page URL.
		esc_html__( 'Setup is now complete, you can edit these settings at any times, %s', 'wpcomsp_wayback_link_fixer' ),
		'<a href="' . esc_url( menu_page_url( 'wpcomsp_wayback_link_fixer_settings', false ) ) . '">Wayback Link Fixer Settings</a>'
	);
	?>
	</p>
</div>


<?php echo $footer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
