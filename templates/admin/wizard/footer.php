<?php

/**
 * The template for the first step of the setup wizard.
 *
 * @since 1.3.0
 *
 * @param array $step_data The step data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$iawmlf_previous_state = 'step-1' === $step_data['step'] ? 'DISABLED' : '';
$iawmlf_next_state     = 'complete' === $step_data['step'] ? 'DISABLED' : '';
$iawmlf_next_label     = 'complete' === $step_data['next']
	? esc_html__( 'Finish', 'internet-archive-wayback-machine-link-fixer' )
	: esc_html__( 'Next Step', 'internet-archive-wayback-machine-link-fixer' );

$iawmlf_progress_string = sprintf(
	/* translators: 1: current step, 2: total steps */
	esc_html__( 'Step %1$d of %2$d', 'internet-archive-wayback-machine-link-fixer' ),
	$step_data['progress']['current'],
	$step_data['progress']['total']
);
$iawmlf_progress_pc  = ( $step_data['progress']['current'] / $step_data['progress']['total'] ) * 100;
$iawmlf_rerun_wizard = isset( $_GET['rerun-wizard'] ) && '1' === sanitize_text_field( $_GET['rerun-wizard'] ); // phpcs:ignore
?>
	</div> <!-- END Wizard content -->

	<div id="iawmlf-wizard__footer">
		<div class="iawmlf-wizard__footer__previous">
			<?php if ( 'complete' !== $step_data['step'] || $iawmlf_rerun_wizard ) : ?>
				<button class="button button-primary" type="submit" name="iawmlf-previous-step" <?php echo esc_attr( $iawmlf_previous_state ); ?>><?php esc_html_e( 'Previous Step', 'internet-archive-wayback-machine-link-fixer' ); ?></button>
			<?php endif; ?>
		</div>
		<div class="iawmlf-wizard__footer__progress">
			<?php if ( 'complete' !== $step_data['step'] ) : ?>
			<div class="iawmlf-wizard__footer__progress__bar">
				<p><?php echo esc_html( $iawmlf_progress_string ); ?></p>
				<div class="iawmlf-wizard__footer__progress__bar__inner" style="width: <?php echo esc_attr( $iawmlf_progress_pc ); ?>%"></div>
			</div>
			<?php endif; ?>
		</div>
		<div class="iawmlf-wizard__footer__next">
			<?php if ( 'complete' !== $step_data['step'] ) : ?>
				<button class="button button-primary" type="submit" name="next-step" <?php echo esc_attr( $iawmlf_next_state ); ?>><?php echo esc_html( $iawmlf_next_label ); ?></button>
			<?php endif; ?>
		</div>
	</div>
	</form> <!-- END Wizard form -->
</div> <!-- END Wizard container -->
