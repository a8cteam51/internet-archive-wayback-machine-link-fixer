<?php
/**
 * Template for the onboarding overview stats section.
 *
 * @since 1.3.0
 *
 * @param array  $iawmlf_onboarding_details      The onboarding details array.
 * @param int    $iawmlf_total_links_count       Total number of links found.
 * @param string $iawmlf_link_table              URL to the links report page.
 */
defined( 'ABSPATH' ) || exit;
?>

<!-- Onboarding Overview Stats -->
<div class="iawmlf_dashboard-onboarding-text">
	<p><?php esc_html_e( 'We\'re scanning your site for links and checking their status. This process may take some time depending on the number of posts you have.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
</div>
<div class="iawmlf_dashboard-stats-grid ">
	<!-- Posts Checked -->
	<div class="iawmlf_dashboard-stats-box iawmlf_dashboard-stats-box--info">
		<div class="iawmlf_dashboard-stats-number">
			<?php echo esc_html( $iawmlf_onboarding_details['total_post_count'] - $iawmlf_onboarding_details['unprocessed_post_count'] ); ?> / <?php echo esc_html( $iawmlf_onboarding_details['total_post_count'] ); ?>
		</div>
		<div class="iawmlf_dashboard-stats-label"><?php esc_html_e( 'Posts Checked', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
	</div>
	<!-- Links Found So Far -->
	<div class="iawmlf_dashboard-stats-box iawmlf_dashboard-stats-box--success">
		<a href="<?php echo esc_url( $iawmlf_link_table ); ?>" class="iawmlf_dashboard-stats-number iawmlf_dashboard-stats-link">
			<?php echo esc_html( $iawmlf_total_links_count ); ?>
		</a>
		<div class="iawmlf_dashboard-stats-label"><?php esc_html_e( 'Links Found So Far', 'internet-archive-wayback-machine-link-fixer' ); ?></div>
	</div>
</div>
