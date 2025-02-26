<?php

/**
 * The template for the first step of the setup wizard.
 *
 * @since 1.3.0
 *
 * @param array $step_data The step data.
 */

$wlf_previous_state = 'step-1' === $step_data['step'] ? 'DISABLED' : '';
$wlf_next_state     = 'complete' === $step_data['step'] ? 'DISABLED' : '';
$wlf_next_label     = 'complete' === $step_data['next']
	? esc_html__( 'Finish', 'wpcomsp_wayback_link_fixer' )
	: esc_html__( 'Next Step', 'wpcomsp_wayback_link_fixer' );

$wlf_progress_string = sprintf(
	/* translators: 1: current step, 2: total steps */
	esc_html__( 'Step %1$d of %2$d', 'wpcomsp_wayback_link_fixer' ),
	$step_data['progress']['current'],
	$step_data['progress']['total']
);

$wlf_progress_pc = ( $step_data['progress']['current'] / $step_data['progress']['total'] ) * 100;
?>
	</div> <!-- END Wizard content -->

	<div id="wlf-wizard__footer">
		<div class="wlf-wizard__footer__previous">
			<button class="button button-primary" type="submit" name="wlf-previous-step" <?php echo esc_attr( $wlf_previous_state ); ?>><?php esc_html_e( 'Previous Step', 'wpcomsp_wayback_link_fixer' ); ?></button>
		</div>
		<div class="wlf-wizard__footer__progress">
			<?php if ( 'complete' !== $step_data['step'] ) : ?>
			<div class="wlf-wizard__footer__progress__bar">
				<p><?php echo esc_html( $wlf_progress_string ); ?></p>
				<div class="wlf-wizard__footer__progress__bar__inner" style="width: <?php echo esc_attr( $wlf_progress_pc ); ?>%"></div>
			</div>
			<?php endif; ?>
		</div>
		<div class="wlf-wizard__footer__next">
			<?php if ( 'complete' !== $step_data['step'] ) : ?>
				<button class="button button-primary" type="submit" name="next-step" <?php echo esc_attr( $wlf_next_state ); ?>><?php echo esc_html( $wlf_next_label ); ?></button>
			<?php endif; ?>
		</div>
	</div>
	</form> <!-- END Wizard form -->
</div> <!-- END Wizard container -->

<script>

</script>
