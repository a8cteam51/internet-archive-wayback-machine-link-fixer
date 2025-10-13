/**
 * JS used for the admin settings page.
 *
 * @since 1.0.0
 */
(function () {
	"use strict";

	// Wait for DOM to be ready
	document.addEventListener('DOMContentLoaded', function () {

		// Get the settings.
		const PROCESS_LINK = document.getElementById('iawmlf_process_links');
		const AUTO_ARCHIVE = document.getElementById('iawmlf_allow_own_content_submissions');

		const EXCLUDED_LINKS = document.getElementById('iawmlf_excluded_links');
		const NEW_LINK = document.getElementById('iawmlf_excluded_links_new');
		const NEW_LINK_BUTTON = document.getElementById('iawmlf_excluded_links_new_action');
		const NEW_LINK_TEMPLATE = IawmlfSettings.newExcludedTemplate;
		const NO_LINKS = document.getElementById('iawmlf_excluded_empty');

		// Handle removing a link.
		if (EXCLUDED_LINKS) {
			EXCLUDED_LINKS.addEventListener('click', function (e) {
				if (e.target.classList.contains('remove-exclusion')) {
					e.preventDefault();
					e.target.parentElement.remove();
					checkNoLinks();
				}
			});
		}

		/**
		 * Prevents default behavior of select elements.
		 *
		 * @param {Event} e - The event object.
		 *
		 */
		function doNothing(e) {
			e.preventDefault();
		}

		// When a checkbox or radio has iawmlf-read-only class, prevent the user from changing it.
		document.addEventListener('click', function (e) {
			if (e.target.matches('.iawmlf_settings_card input.iawmlf-read-only, .iawmlf_settings_card select.iawmlf-read-only')) {
				doNothing(e);
			}
		});

		// When Process Links is checked, enable the excluded links.
		if (PROCESS_LINK) {
			PROCESS_LINK.addEventListener('change', function () {
				toggleElements(this.checked, 'link_fixer');
			});
		}

		// When Auto Archive is checked, enable the excluded links.
		if (AUTO_ARCHIVE) {
			AUTO_ARCHIVE.addEventListener('change', function () {
				toggleElements(this.checked, 'auto_archiver');
			});
		}

		/**
		 * Toggles the state of elements in the specified group.
		 * @param {boolean} isChecked - The state of the checkbox.
		 * @param {string} groupName - The data-group attribute value.
		 */
		function toggleElements(isChecked, groupName) {

			// Get the field list, based on the group name.
			const fieldList = 'link_fixer' === groupName
				? '.iawmlf_toggle_setting__fixer'
				: '.iawmlf_toggle_setting__auto_archiver';

			const elements = document.querySelectorAll(fieldList);
			elements.forEach(function (element) {
				if (!isChecked) {
					element.classList.add('hidden');
				} else {
					element.classList.remove('hidden');
				}
			});
		}

		// Runs the checks on load
		if (PROCESS_LINK) {
			toggleElements(PROCESS_LINK.checked, 'link_fixer');
		}
		if (AUTO_ARCHIVE) {
			toggleElements(AUTO_ARCHIVE.checked, 'auto_archiver');
		}

		/**
		 * Get the last index from the links.
		 *
		 * @since 1.0.0
		 *
		 * @return {int} The last index.
		 */
		function getNextIndex() {
			if (!EXCLUDED_LINKS) return 0;

			const links = EXCLUDED_LINKS.querySelectorAll('input[type="text"]');
			let lastIndex = 0;
			links.forEach(function (link) {
				// Get index from data-index attribute.
				const index = parseInt(link.dataset.index) || 0;
				if (index > lastIndex) {
					lastIndex = index;
				}
			});
			return lastIndex;
		}

		/**
		 * Parses a new link.
		 *
		 * @since 1.0.0
		 *
		 * @param {string} newLink The new link.
		 * @param {int} index The index of the new link.
		 *
		 * @return {string} The parsed link.
		 */
		const parseNewLink = (newLink, index) => NEW_LINK_TEMPLATE.replace(/{newIndex}/g, index).replace(/{newUrl}/g, newLink)

		// When the user clicks the add new link button.
		if (NEW_LINK_BUTTON) {
			NEW_LINK_BUTTON.addEventListener('click', function (e) {
				e.preventDefault();
				const newLink = NEW_LINK ? NEW_LINK.value : '';
				if (newLink.length > 0 && EXCLUDED_LINKS) {
					EXCLUDED_LINKS.insertAdjacentHTML('beforeend', parseNewLink(newLink, getNextIndex()));
					if (NEW_LINK) {
						NEW_LINK.value = '';
					}
				}

				checkNoLinks();
			});
		}

		/**
		 * Check if the no links message should be shown.
		 *
		 * @since 1.0.0
		 *
		 * @return {void}
		 */
		function checkNoLinks() {
			if (!EXCLUDED_LINKS || !NO_LINKS) return;

			const linkDivs = EXCLUDED_LINKS.querySelectorAll('div.link');
			if (linkDivs.length > 0) {
				NO_LINKS.style.display = 'none';
			} else {
				NO_LINKS.style.display = '';
			}
		}
	});
})();
