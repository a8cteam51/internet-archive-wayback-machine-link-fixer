<?php

/**
 * The template for the first step of the setup wizard.
 *
 * @since 1.3.0
 *
 * @param string $header The header template.
 * @param string $footer The footer template.
 */

defined( 'ABSPATH' ) || exit;

use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Settings_Page;

?>

<?php echo wp_kses( $header, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>

<div class="iawmlf-wizard__content__header">
	<h2><?php esc_html_e( 'You\'re all set!', 'internet-archive-wayback-machine-link-fixer' ); ?></h2>
</div>

<div class="iawmlf-wizard__content__intro">
	<p><?php esc_html_e( 'The plugin is now scanning your existing content. You can:', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
	<ul>
		<li><?php esc_html_e( 'Check progress anytime via "Link Fixer" in your admin menu.', 'internet-archive-wayback-machine-link-fixer' ); ?></li>
		<li><?php esc_html_e( 'Expect broken links to start redirecting in ~2 weeks.', 'internet-archive-wayback-machine-link-fixer' ); ?></li>
		<li><?php esc_html_e( 'Adjust settings later in Advanced Settings.', 'internet-archive-wayback-machine-link-fixer' ); ?></li>
	</ul>
	<p><?php esc_html_e( 'No further action needed—we\'ll handle everything automatically from here.', 'internet-archive-wayback-machine-link-fixer' ); ?></p>
</div>


<?php echo wp_kses( $footer, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>
