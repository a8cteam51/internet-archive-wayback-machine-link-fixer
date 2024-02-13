<?php

/**
 * Template for rendering the report filters
 *
 * @since      1.0.0
 *
 * Defined vars.
 * @var array   $filters
 * @var integer $reports_per_page
 */

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Reports;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Viewer\Report_List_View;

// Get all the current url params.
$wlf_page_arg = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

?>

<div id="wlf-report-filters">

	<form method="get" action="<?php echo esc_url( admin_url() ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( $wlf_page_arg ); ?>" />
		<div class="wlf-report-filter-row">
			<!-- Users -->
			<label><?php esc_html_e( 'Created by: ', 'wpcomsp_wayback_link_fixer' ); ?>
				<select class="filter-single" id="user-filter" name="<?php echo esc_attr( Report_List_View::PARAM_USER_ID ); ?>">
					<option value="" <?php selected( $filters['user_id'], null ); ?>><?php esc_html_e( 'Any', 'wpcomsp_wayback_link_fixer' ); ?>></option>
					<option value="0" <?php selected( $filters['user_id'], 0 ); ?>><?php esc_html_e( 'Unknown', 'wpcomsp_wayback_link_fixer' ); ?></option>
					<?php foreach ( get_users( array( 'role__in' => array( 'administrator', 'editor', 'author' ) ) ) as $wlf_user ) : ?>
						<option value="<?php echo esc_attr( $wlf_user->ID ); ?>" <?php selected( (int) $filters['user_id'], $wlf_user->ID ); ?>>
							<?php echo esc_html( wpcomsp_wayback_link_fixer_get_user_name( $wlf_user ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<!-- Blogs -->
			<?php if ( is_multisite() ) : ?>
				<?php // If viewing from the network admin, show all sites. ?>
				<?php if ( is_network_admin() ) : ?>
					<label><?php esc_html_e( 'Blog: ', 'wpcomsp_wayback_link_fixer' ); ?>
						<select class="filter-multiple" id="blog-filter"  name="<?php echo esc_attr( Report_List_View::PARAM_BLOG_ID ); ?>[]" multiple>
							<option value=""><?php esc_html_e( 'Any', 'wpcomsp_wayback_link_fixer' ); ?></option>
							<?php foreach ( get_sites() as $wlf_site ) : ?>
								<option value="<?php echo esc_attr( $wlf_site->blog_id ); ?>" <?php selected( $filters['blog_id'], $wlf_site->blog_id ); ?>>
									<?php echo esc_html( $wlf_site->blogname ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					<?php // If viewing from a single site, show only that site. ?>
				<?php else : ?>
					<input type="hidden" name="<?php echo esc_attr( Report_List_View::PARAM_BLOG_ID ); ?>[]" value="<?php echo esc_attr( get_current_blog_id() ); ?>" />
				<?php endif; ?>
			<?php endif; ?>

			<!-- Status -->
			<label><?php esc_html_e( 'Status: ', 'wpcomsp_wayback_link_fixer' ); ?>
				<select class="filter-multiple" id="status_filter" name="<?php echo esc_attr( Report_List_View::PARAM_STATUS ); ?>[]" multiple>
					<option value=""><?php esc_html_e( 'Any', 'wpcomsp_wayback_link_fixer' ); ?></option>
					<option value="<?php echo esc_attr( Reports::PENDING_STATUS ); ?>"<?php echo in_array( Reports::PENDING_STATUS, $filters['status'], true ) ? ' selected' : ''; ?>><?php esc_html_e( 'Pending', 'wpcomsp_wayback_link_fixer' ); ?></option>
					<option value="<?php echo esc_attr( Reports::IN_PROGRESS_STATUS ); ?>"<?php echo in_array( Reports::IN_PROGRESS_STATUS, $filters['status'], true ) ? ' selected' : ''; ?>><?php esc_html_e( 'In Progress', 'wpcomsp_wayback_link_fixer' ); ?></option>
					<option value="<?php echo esc_attr( Reports::COMPLETED_STATUS ); ?>"<?php echo in_array( Reports::COMPLETED_STATUS, $filters['status'], true ) ? ' selected' : ''; ?>><?php esc_html_e( 'Completed', 'wpcomsp_wayback_link_fixer' ); ?></option>
				</select>
			</label>
		</div>
		<div class="wlf-report-filter-row">
			<!-- Date From -->
			<label><?php esc_html_e( 'Date From: ', 'wpcomsp_wayback_link_fixer' ); ?>
				<input type="date" name="<?php echo esc_attr( Report_List_View::PARAM_DATE_FROM ); ?>" value="<?php echo esc_attr( $filters['date_from'] ); ?>" />
			</label>

			<!-- Date To -->
			<label><?php esc_html_e( 'Date To: ', 'wpcomsp_wayback_link_fixer' ); ?>
				<input type="date" name="<?php echo esc_attr( Report_List_View::PARAM_DATE_TO ); ?>" value="<?php echo esc_attr( $filters['date_to'] ); ?>" />
			</label>

			<!-- Reports Per Page -->
			<label><?php esc_html_e( 'Reports Per Page: ', 'wpcomsp_wayback_link_fixer' ); ?>
				<select name="<?php echo esc_attr( Report_List_View::PARAM_REPORTS_PER_PAGE ); ?>">
					<option value="10" <?php selected( $reports_per_page, 10 ); ?>>10</option>
					<option value="25" <?php selected( $reports_per_page, 25 ); ?>>25</option>
					<option value="50" <?php selected( $reports_per_page, 50 ); ?>>50</option>
					<option value="100" <?php selected( $reports_per_page, 100 ); ?>>100</option>
				</select>
			</label>
		</div>

		<!-- Set the pagination to 1 -->
		<input type="hidden" name="<?php echo esc_attr( Report_List_View::PARAM_CURRENT_PAGE ); ?>" value="1" />

		<!-- Submit -->
		<div class="wlf-report-filter-button">
			<input class="button" type="submit" value="<?php esc_attr_e( 'Filter Reports', 'wpcomsp_wayback_link_fixer' ); ?>" />
		</div>
	</form>
</div>

<script>
	// Use select2 for the status filter.
	jQuery( document ).ready( function ( $ ) {
		$( '.filter-multiple' ).select2({
			multiple: true,
			placeholder: 'Any',
			width: 'resolve',
			allowClear: true
		});

		$( '.filter-single' ).select2({
			multiple: false,
			placeholder: 'Any',
			width: 'resolve',
			allowClear: true
		});
	} );
</script>
