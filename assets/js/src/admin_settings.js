/**
 * JS used for the admin settings page.
 *
 * @since 1.0.0
 */
(function () {
	"use strict";
	jQuery(document).ready(function () {

		// Get the settings.
		const EXCLUDED_LINKS = jQuery('#wlf_excluded_links');
		const NEW_LINK = jQuery('#wlf_excluded_links_new');
		const NEW_LINK_BUTTON = jQuery('#wlf_excluded_links_new_action');
		const NEW_LINK_TEMPLATE = WlfSettings.newExcludedTemplate;
		const NO_LINKS = jQuery('#wlf_excluded_empty');
		const HTTP_CODES = jQuery('#t51_wlf_http_status_codes');
		const HTTP_CODES_SELECT = jQuery('#t51_wlf_http_status_codes_select');

		// Handle removing a link.
		EXCLUDED_LINKS.on('click', '.remove-exclusion', function (e) {
			e.preventDefault();
			jQuery(this).parent().remove();
			checkNoLinks();
		});

		/**
		 * Get the last index from the links.
		 *
		 * @since 1.0.0
		 *
		 * @return {int} The last index.
		 */
		function getNextIndex() {
			const links = EXCLUDED_LINKS.find('input[type="text"]');
			let lastIndex = 0;
			links.each(function () {
				// Get index from data-index attribute.
				const index = jQuery(this).data('index');
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
		NEW_LINK_BUTTON.on('click', function (e) {
			e.preventDefault();
			const newLink = NEW_LINK.val();
			if (newLink.length > 0) {
				EXCLUDED_LINKS.append(parseNewLink(newLink, getNextIndex()));
				NEW_LINK.val('');
			}

			checkNoLinks();
		});

		/**
		 * Check if the no links message should be shown.
		 *
		 * @since 1.0.0
		 *
		 * @return {void}
		 */
		function checkNoLinks() {
			if (EXCLUDED_LINKS.find('div.link').length > 0) {
				NO_LINKS.hide();
			} else {
				NO_LINKS.show();
			}
		}

		/**
		 * Use Select2 for the HTTP status code select.
		 *
		 * @since 1.1.0
		 *
		 * @return {void}
		 */
		HTTP_CODES_SELECT.select2({
			width: '100%',
			allowClear: true
		});

		/**
		 * On changes of select, update list.
		 *
		 * @since 1.1.0
		 */
		HTTP_CODES_SELECT.on('change', function () {
			const selected = HTTP_CODES_SELECT.val();
			HTTP_CODES.val(selected.join(','));
		});
	});
})();
