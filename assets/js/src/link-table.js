/**
 * JS used for the admin settings page.
 *
 * @since 1.0.0
 */
(function () {
	"use strict";

	// Wait for DOM to be ready
	document.addEventListener('DOMContentLoaded', function () {

		const TRIGGER_BULK_ACTIONS = document.getElementById('iawmlf_help_info_bulk_actions');

		/**
		 * Shows the help tab.
		 */
		const showHelpTab = () => {
			const helpToggle = document.getElementById('contextual-help-link');
			if (helpToggle && !helpToggle.classList.contains('screen-meta-active')) {
				helpToggle.click();
			}
		}

		/**
		 * Select a defined tab.
		 *
		 * @param {string} tabName - The name of the tab to select.
		 */
		const selectTab = (tabName) => {
			const tabLink = document.querySelector(`#tab-link-iawmlf_help_${tabName} a`);
			if (tabLink) {
				tabLink.click();
			}
		}

		// When the trigger icon is clicked, show the help tab.
		if (TRIGGER_BULK_ACTIONS) {
			TRIGGER_BULK_ACTIONS.addEventListener('click', function (e) {
				e.preventDefault();
				showHelpTab();

				// Open the Help tab (if it's not open)
				selectTab('bulk_actions');
			});
		}

		// Handle the link exclusion toggle confirmation.
		const TOGGLE_EXCLUSION = document.getElementById('iawmlf_toggle_exclusion');
		const EXCLUSION_FORM = document.getElementById('iawmlf_link_details_form');

		if (TOGGLE_EXCLUSION && EXCLUSION_FORM && typeof iawmlf_link_table !== 'undefined') {
			const initialState = TOGGLE_EXCLUSION.checked;

			TOGGLE_EXCLUSION.addEventListener('change', function () {
				if (this.checked === initialState) {
					return;
				}

				const message = this.checked
					? iawmlf_link_table.confirmExclude
					: iawmlf_link_table.confirmInclude;

				if (!confirm(message)) {
					this.checked = !this.checked;
				}
			});
		}
	});
})();

