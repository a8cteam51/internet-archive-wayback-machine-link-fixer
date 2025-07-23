<?php
/**
 * Template for the tab bulk actions content.
 *
 * @since 1.3.0
 */

?>
<div id="wlf_help_tab_bulk_actions" class="wlf_help_tab">
	<h3><?php esc_html_e( 'Update to latest snapshot', 'wayback-link-fixer' ); ?></h3>
	<p><?php esc_html_e( 'When this option is selected, the link will have its archive updated to the latest snapshot available from the Internet Archive.', 'wayback-link-fixer' ); ?></p>

	<h3><?php esc_html_e( 'Create new snapshot', 'wayback-link-fixer' ); ?></h3>
	<p><?php esc_html_e( 'When this option is selected, a request to create a new snapshot of the link on the Internet Archive will be made. This process can take some time, depending on the Archive.org systems. The link\'s archived version in your records will be updated once the snapshot is complete and available.', 'wayback-link-fixer' ); ?></p>

	<h3><?php esc_html_e( 'Check link status', 'wayback-link-fixer' ); ?></h3>
	<p><?php esc_html_e( 'This option checks the current status and availability of the live link.', 'wayback-link-fixer' ); ?></p>

	<h3><?php esc_html_e( 'Verify Link Can Be Checked', 'wayback-link-fixer' ); ?></h3>
	<p><?php esc_html_e( 'This action checks if the link\'s host (e.g., via robots.txt or meta tags) or your plugin settings prevent the link from being archived or checked. It helps determine if a link should be excluded.', 'wayback-link-fixer' ); ?></p>
</div>
