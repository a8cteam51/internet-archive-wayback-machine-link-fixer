<?php

/**
 * Template for rendering the report list pagination.
 *
 * @since      1.0.0
 *
 * Defined vars.
 * @var integer $current_page
 * @var integer $total_pages
 * @var array   $filters
 */

/**
 * Generate the URL for the page with a given id.
 *
 * @param integer $page The page no
 *
 * @return string
 */
$wpcomsp_get_url = function ( int $page ): string {
	// Get all the current url params.
	$params = array_merge(
		array_map( 'sanitize_text_field', $_GET ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		array(
			'paged' => $page,
		)
	);

	// Apply params to the admin url.
	return add_query_arg( $params, admin_url() );
};

// If total pages is 0, bail.
if ( 0 === $total_pages ) {
	return;
}
?>

<div class="wlf-report-list-pagination">
	<ul>
		<li role="previous">
			<?php if ( 1 === $current_page ) : ?>
				<span class="dashicons dashicons-arrow-left-alt2 active"></span>
			<?php else : ?>
				<a href="<?php echo esc_url( $wpcomsp_get_url( $current_page - 1 ) ); ?>">
					<span class="dashicons dashicons-arrow-left-alt2"></span>
				</a>
			<?php endif; ?>
		</li>

		<!-- Iterate through all the pages and render numbered links -->
		<?php foreach ( range( 1, $total_pages ) as $pagination_page ) : // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound ?>
			<?php if ( $pagination_page === $current_page ) : ?>
				<li class="active"><span><?php echo absint( $pagination_page ); ?></span></li>
			<?php else : ?>
				<li><a href="<?php echo esc_url( $wpcomsp_get_url( $pagination_page ) ); ?>"><?php echo absint( $pagination_page ); ?></a></li>
			<?php endif; ?>
		<?php endforeach; ?>

		<li role="next">
			<?php if ( $total_pages === $current_page ) : ?>
				<span class="dashicons dashicons-arrow-right-alt2 active"></span>
			<?php else : ?>
				<a href="<?php echo esc_url( $wpcomsp_get_url( $current_page + 1 ) ); ?>">
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</a>
			<?php endif; ?>
		</li>
	</ul>
</div>
