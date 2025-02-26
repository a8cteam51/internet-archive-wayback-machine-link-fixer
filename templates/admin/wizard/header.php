<?php

/**
 * The template for the first step of the setup wizard.
 *
 * @since 1.3.0
 *
 * @param array  $step_data The step data.
 */
?>

<div id="wlf_wizard"> <!-- Wizard container -->
	<div class="wlf-wizard__content"> <!-- Wizard content -->
		<h1><?php esc_html_e( 'Wayback Link Fixer Setup Wizard', 'wpcomsp_wayback_link_fixer' ); ?></h1>

		<form method="post" action="">
			<input type="hidden" name="wlf-action" value="start-wizard" />
			<input type="hidden" name="wlf-current-step" value="<?php echo esc_attr( $step_data['step'] ); ?>" />
			<input type="hidden" name="wlf-next-step" value="<?php echo esc_attr( $step_data['next'] ); ?>" />
			<?php wp_nonce_field( 'wlf_wizard_nonce', 'wlf_wizard_nonce' ); ?>
