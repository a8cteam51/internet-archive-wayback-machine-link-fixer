<?php

/**
 * The template for the first step of the setup wizard.
 *
 * @since 1.3.0
 *
 * @param string $header The header template.
 * @param string $footer The footer template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php echo wp_kses_post( $header ); ?>

<div class="wlf-wizard__content__header">
	<h2><?php esc_html_e( 'Setup complete.', 'internet-archive-wayback-machine-link-fixer' ); ?></h2>
</div>

<div class="wlf-wizard__content__intro">
	<p>
	<?php
	printf(
		// translators: %s is a link to the plugin settings page.
		esc_html__( 'Setup is now complete! You can edit these settings at any time from the %s page.', 'internet-archive-wayback-machine-link-fixer' ),
		'<a href="' . esc_url( menu_page_url( 'wpcomsp_wayback_link_fixer_settings', false ) ) . '">' . esc_html__( 'Wayback Link Fixer Settings', 'internet-archive-wayback-machine-link-fixer' ) . '</a>'
	);
	?>
	</p>
</div>


<?php echo wp_kses_post( $footer ); ?>
