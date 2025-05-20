console.log('link-table.js loaded');
/**
 * JS used for the admin settings page.
 *
 * @since 1.0.0
 */
(function () {
	"use strict";
	jQuery(document).ready(function () {

		const TRIGGER_BULK_ACTIONS = jQuery('#wlf_help_info_bulk_actions');

		/**
		 * Shows the help tab.
		 */
		const showHelpTab = () => {
			const helpToggle = jQuery('#contextual-help-link');
			if (!helpToggle.hasClass('screen-meta-active')) {
				helpToggle.click();
			}
		}

		/**
		 * Select a defined tab.
		 *
		 * @param {string} tabName - The name of the tab to select.
		 */
		const selectTab = (tabName) => {
			const tabLink = jQuery(`#tab-link-wlf_help_${tabName} a`);
			if (tabLink.length) {
				tabLink.trigger('click');
			}
		}

		// When the trigger icon is clicked, show the help tab.
		TRIGGER_BULK_ACTIONS.on('click', function (e) {
			e.preventDefault();
			showHelpTab();

			// Open the Help tab (if it's not open)
			selectTab('bulk_actions');
		});
	});
})();

