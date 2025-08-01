/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
/*!*****************************************!*\
  !*** ./assets/js/src/admin_settings.js ***!
  \*****************************************/
/**
 * JS used for the admin settings page.
 *
 * @since 1.0.0
 */
(function () {
  "use strict";

  jQuery(document).ready(function () {
    // Get the settings.
    const PROCESS_LINK = jQuery('#t51_wlf_process_links');
    const AUTO_ARCHIVE = jQuery('#t51_wlf_allow_own_content_submissions');
    const EXCLUDED_LINKS = jQuery('#wlf_excluded_links');
    const NEW_LINK = jQuery('#wlf_excluded_links_new');
    const NEW_LINK_BUTTON = jQuery('#wlf_excluded_links_new_action');
    const NEW_LINK_TEMPLATE = WlfSettings.newExcludedTemplate;
    const NO_LINKS = jQuery('#wlf_excluded_empty');

    // Handle removing a link.
    EXCLUDED_LINKS.on('click', '.remove-exclusion', function (e) {
      e.preventDefault();
      jQuery(this).parent().remove();
      checkNoLinks();
    });

    /**
     * Prevents default behavior of select elements.
     *
     * @param {Event} e - The event object.
     *
     */
    function doNothing(e) {
      e.preventDefault();
    }

    // When a checkbox or radio has wlf-read-only class, prevent the user from changing it.
    jQuery(document).on('click', '.wlf_settings_card input.wlf-read-only, .wlf_settings_card select.wlf-read-only', doNothing);

    // When Process Links is checked, enable the excluded links.
    PROCESS_LINK.on('change', function () {
      toggleElements(this.checked, 'link_fixer');
    });

    // When Auto Archive is checked, enable the excluded links.
    AUTO_ARCHIVE.on('change', function () {
      toggleElements(this.checked, 'auto_archiver');
    });

    /**
     * Toggles the state of elements in the specified group.
     * @param {boolean} isChecked - The state of the checkbox.
     * @param {string} groupName - The data-group attribute value.
     */
    function toggleElements(isChecked, groupName) {
      // Get the field list, based on the group name.
      const fieldList = 'link_fixer' === groupName ? '.wlf_toggle_setting__fixer' : '.wlf_toggle_setting__auto_archiver';
      if (!isChecked) {
        jQuery(fieldList).addClass('hidden');
      } else {
        jQuery(fieldList).removeClass('hidden');
      }
    }

    // Runs the checks on load
    toggleElements(PROCESS_LINK.prop('checked'), 'link_fixer');
    toggleElements(AUTO_ARCHIVE.prop('checked'), 'auto_archiver');

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
    const parseNewLink = (newLink, index) => NEW_LINK_TEMPLATE.replace(/{newIndex}/g, index).replace(/{newUrl}/g, newLink);

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
  });
})();
/******/ })()
;
//# sourceMappingURL=admin_settings.js.map