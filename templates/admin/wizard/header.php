<?php

/**
 * The template for the first step of the setup wizard.
 *
 * @since 1.3.0
 *
 * @param array  $step_data The step data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="iawmlf_wizard" class="iawmlf_settings_card"> <!-- Wizard container -->
	<div class="iawmlf-wizard__content"> <!-- Wizard content -->
		<form method="post" action="">
			<input type="hidden" name="iawmlf-action" value="start-wizard" />
			<input type="hidden" name="iawmlf-current-step" value="<?php echo esc_attr( $step_data['step'] ); ?>" />
			<input type="hidden" name="iawmlf-next-step" value="<?php echo esc_attr( $step_data['next'] ); ?>" />
			<?php wp_nonce_field( 'iawmlf_wizard_nonce', 'iawmlf_wizard_nonce' ); ?>
