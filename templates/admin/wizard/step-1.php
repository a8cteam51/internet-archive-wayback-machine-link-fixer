<?php

/**
 * The template for the first step of the setup wizard.
 *
 * @since 1.3.0
 *
 * @param array  $step_data The step data.
 * @param Settings $settings The settings object.
 * @param array $post_types The post types.
 * @param string $header The header template.
 * @param string $footer The footer template.
 */

defined( 'ABSPATH' ) || exit;

?>

<?php echo wp_kses( $header, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>

<div class="iawmlf-wizard__content__intro">
	<p><?php esc_html_e( 'Thanks for installing the Internet Archive Wayback Machine Link Fixer!', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
	<p>
		<?php
		printf(
			/* translators: %s: Wayback Machine link */
			esc_html__( 'This plugin scans your posts for links, checks whether or not they work, and if they don\'t work it automatically redirects them to an archived snapshot on the %s. Here\'s what you need to know:', 'internet-archive-wayback-machine-link-fixer' ),
			'<a href="https://wayback.archive.org" target="_blank">' . esc_html__( 'Wayback Machine', 'internet-archive-wayback-machine-link-fixer' ) . '</a>'
		);
		?>
	</p>

	<ul>
		<li>
			<h3><?php esc_html_e( 'Checking links takes time.', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
			<p><?php esc_html_e( 'It takes some time to check your links. We process them in small batches over time so we don\'t slow down your site. After you set it up, check back in a couple days on the progress. We keep the dashboard updated with how many posts have been scanned and how many links have been checked.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
		</li>
		<li>
			<h3><?php esc_html_e( 'Broken links redirect to the Wayback Machine after 3 checks.', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
			<p><?php esc_html_e( 'We want to make sure the link is actually broken and not just an intermittent issue, so we check it 3 times, at least 3 days apart. We keep checking it, so if it comes back online we\'ll stop redirecting it.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
		</li>
		<li>
			<h3><?php esc_html_e( 'Set it and forget it.', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
			<p><?php esc_html_e( 'Once you set up this plugin, it continues to run and check your links in the background, no action required from you.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
		</li>
	</ul>
</div>


<?php echo wp_kses( $footer, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>
