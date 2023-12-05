<?php

/**
 * The template used to render the details of an event.
 *
 * @since      1.0.0
 *
 * Defined vars
 * @var \ActionScheduler_Action $event The event.
 * @var integer                 $id    The event id.
 */

use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Event;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Helper;

// Get the event args.
$wlf_args = $event->get_args();

// If we do not have an event in the args, show an error.
if ( ! isset( $wlf_args['event'] ) ) {
	printf( '<tr><td>%d</td><td colspan=4">%s</td><tr>', absint( $id ), esc_html__( 'No event found.', 'wpcomsp_wayback_link_fixer' ) );
	return;
}

// Unserialize the event.
$wlf_event = unserialize( $wlf_args['event'] ); //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

// If we dont have a valid Event instance, show an error.
if ( ! $wlf_event instanceof Event ) {
	printf( '<tr><td>%d</td><td colspan=4">%s</td><tr>', absint( $id ), esc_html__( 'Malformed data', 'wpcomsp_wayback_link_fixer' ) );
	return;
}

// Extract the report.
$wlf_report = $wlf_event->get_report();

?>
<tr id="event_<?php echo esc_attr( $id ); ?>" data-row="<?php echo esc_attr( $id ); ?>">
	<th><?php echo esc_attr( $id ); ?></th>
	<th><?php echo esc_html( ucfirst( $wlf_report->get_process() ) ); ?></th>
	<th>
		<?php
		printf(
			// Translators: %1$d is the number of posts processed, %2$d is the total number of posts.
			'%1$d/%2$d',
			count( $wlf_event->get_processed_post_ids() ),
			count( $wlf_event->get_post_ids() )
		);
		?>
	</th>
	<th><span class="dashicons dashicons-<?php echo esc_attr( $wlf_event->ignore_cache() ? 'yes-alt' : 'dismiss' ); ?>"></span></th>
	<th>
		<?php
		printf(
			// Translators: %1$s is the link to the report, %2$s link contents
			'<a href="%s">%s</a>',
			esc_url_raw( Report_Helper::get_single_report_link( $wlf_report ) ),
			esc_html__( 'View Report', 'wpcomsp_wayback_link_fixer' )
		);
		?>
	</th>
</tr>
