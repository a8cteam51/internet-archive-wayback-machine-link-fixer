<?php

/**
 * Template for rendering the report list
 *
 * @since      1.0.0
 *
 * Defined vars.
 * @var integer $current_page
 * @var integer $total_pages
 * @var array   $filters
 * @var integer $total_reports
 * @var integer $reports_per_page
 * @var \WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report[] $reports
 */

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report_Helper;

// Calculate which reports are shown.
$wlf_reports_shown = ( function () use ( $current_page, $reports_per_page, $total_reports ) {

		$start = ( ( $current_page - 1 ) * $reports_per_page ) + 1;

		$end = ( $current_page ) * $reports_per_page;
		// If end is greater than total reports, set it to total reports.
		$end = $end > $total_reports ? $total_reports : $end;

		return $start . ' - ' . $end;
} )();

?>

<div id="wlf-report-list" class="wrap">
	<h2><?php esc_html_e( 'Reports', 'wpcomsp_wayback_link_fixer' ); ?></h2>
	<!-- Filters -->
	<div id="wlf-report-count">
		<p>
		<?php
		echo esc_html(
			// translators: %1$s is the number of reports shown, %2$d is the total number of reports.
			sprintf( __( 'Showing %1$s of %2$d reports', 'wpcomsp_wayback_link_fixer' ), $wlf_reports_shown, $total_reports )
		);
		?>
		</p>
	</div>
	<?php
	wpcomsp_wayback_link_fixer_render_template(
		'admin/reports/report-list-filters.php',
		array(
			'filters'          => $filters,
			'reports_per_page' => $reports_per_page,
		)
	);
	?>

	<div id="wlf-report-table">
		<div id="wlf-report-table-header">
			<table width="100%">
				<thead>
					<tr>
						<th scope="col"></th>
						<th scope="col"><?php esc_html_e( 'Created By', 'wpcomsp_wayback_link_fixer' ); ?></th>
						<?php if ( is_multisite() ) : ?>
							<th scope="col"><?php esc_html_e( 'Blog', 'wpcomsp_wayback_link_fixer' ); ?></th>
						<?php endif; ?>
						<th scope="col"><?php esc_html_e( 'Status', 'wpcomsp_wayback_link_fixer' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Pages Checked', 'wpcomsp_wayback_link_fixer' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Created', 'wpcomsp_wayback_link_fixer' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'wpcomsp_wayback_link_fixer' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( 0 === $total_reports ) : ?>
						<tr>
							<td colspan="7"><?php esc_html_e( 'No reports found.', 'wpcomsp_wayback_link_fixer' ); ?></td>
						</tr>
				<?php else : ?>
					<?php foreach ( $reports as $wlf_report ) : ?>
						<tr>
							<td>
								<input type="checkbox" name="delete_report[]" value="<?php echo esc_attr( $wlf_report['report']->get_id() ); ?>" />
							</td>

							<td><?php echo esc_html( wpcomsp_wayback_link_fixer_get_report_author( $wlf_report['report'] ) ); ?></td>
							<?php if ( is_multisite() ) : ?>
								<td><?php echo esc_html( get_blog_details( $wlf_report['report']->get_blog_id() )->blogname ); ?></td>
							<?php endif; ?>
							<td><?php echo esc_html( $wlf_report['report']->get_process() ); ?></td>
							<td><?php echo esc_html( $wlf_report['logs'] ); ?></td>
							<td><?php echo esc_html( $wlf_report['report']->get_created_at()->format( get_option( 'date_format' ) . ' : ' . get_option( 'time_format' ) ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( Report_Helper::get_single_report_link( $wlf_report['report'] ) ); ?>">
									<span class="dashicons dashicons-visibility"></span>
								</a>
								<span class="dashicons dashicons-trash"></span>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

		</div>
	</div>


<!-- Pagination -->
<?php

wpcomsp_wayback_link_fixer_render_template(
	'admin/reports/report-list-pagination.php',
	array(
		'current_page' => $current_page,
		'total_pages'  => $total_pages,
		'filters'      => $filters,
	)
)
?>
</div>
