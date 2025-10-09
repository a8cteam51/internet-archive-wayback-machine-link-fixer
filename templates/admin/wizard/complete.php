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
	<h2><?php esc_html_e( 'Setup complete.', 'internet-archive-wayback-machine-link-fixer' ); ?></h2>
</div>

<div class="iawmlf-wizard__content__intro">
	<p>
	<?php
	printf(
		// translators: %s is a link to the plugin settings page.
		esc_html__( 'Setup is now complete! You can edit these settings at any time from the %s page.', 'internet-archive-wayback-machine-link-fixer' ),
		'<a href="' . esc_url( Settings_Page::get_page_url() ) . '">' . esc_html__( 'Wayback Link Fixer Settings', 'internet-archive-wayback-machine-link-fixer' ) . '</a>'
	);
	?>
	</p>
</div>


<?php echo wp_kses( $footer, \Internet_Archive\Wayback_Machine_Link_Fixer\Util\Esc::wizard_allowed_tags() ); ?>
