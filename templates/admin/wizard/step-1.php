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
	<p><?php esc_html_e( 'Welcome to the Internet Archive Wayback Machine Link Fixer!', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
	<p>
		<?php
		printf(
			/* translators: %s: Wayback Machine link */
			esc_html__( 'This plugin automatically fixes broken links on your site by redirecting them to archived versions on the %s.', 'internet-archive-wayback-machine-link-fixer' ),
			'<a href="https://wayback.archive.org" target="_blank">' . esc_html__( 'Wayback Machine', 'internet-archive-wayback-machine-link-fixer' ) . '</a>'
		);
		?>
	</p>
	<h3><?php esc_html_e( 'How it works:', 'internet-archive-wayback-machine-link-fixer' ); ?></h3>
	<ul>
		<li>
			<p><strong><?php esc_html_e( 'Background processing', 'internet-archive-wayback-machine-link-fixer' ); ?></strong> <?php esc_html_e( '– We check links in small batches to keep your site fast. Initial scanning takes a few days.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
		</li>
		<li>
			<p><strong><?php esc_html_e( 'Smart verification', 'internet-archive-wayback-machine-link-fixer' ); ?></strong> <?php esc_html_e( '– Links are checked 3 times over 9+ days before redirecting, ensuring they\'re truly broken.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
		</li>
		<li>
			<p><strong><?php esc_html_e( 'Automatic updates', 'internet-archive-wayback-machine-link-fixer' ); ?></strong> <?php esc_html_e( '– If a link comes back online, we\'ll restore the original automatically.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
		</li>
	</ul>

	<p><?php esc_html_e( 'Once configured, everything runs in the background. No maintenance required.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
</div>


<?php echo wp_kses( $footer, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>
