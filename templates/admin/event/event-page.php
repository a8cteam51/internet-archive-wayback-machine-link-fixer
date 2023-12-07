<?php

/**
 * Template used to render the event page.
 *
 * @since      1.0.0
 *
 * Defined vars
 * @var \ActionScheduler_Action[] $events The list of actions.
 */
?>
<div class="wrap">
	<h1><?php esc_html_e( 'New Report', 'wpcomsp_wayback_link_fixer' ); ?></h1>
	<?php wpcomsp_wayback_link_fixer_render_template( 'admin/event/event-new.php' ); ?>
	<div id="wlf-events-details">
		<p><?php esc_html_e( 'The following reports are scheduled to be run/running.', 'wpcomsp_wayback_link_fixer' ); ?></p>
		<table id="events" style="width:100%">
			<thead>
				<tr>
					<th>ID</th>
					<th>Status</th>
					<th>Posts Processed</th>
					<th>Ignore Link Cache</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( 0 === count( $events ) ) : ?>
					<tr id="no_results">
						<td colspan="5"><?php esc_html_e( 'No events currently being processed.', 'wpcomsp_wayback_link_fixer' ); ?></td>
					</tr>
				<?php else : ?>
					<?php
					foreach ( $events as $wlf_id => $wlf_event ) {
						wpcomsp_wayback_link_fixer_render_template(
							'admin/event/event-row.php',
							array(
								'id'    => $wlf_id,
								'event' => $wlf_event,
							)
						);
					}
					?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
