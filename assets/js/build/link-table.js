/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
/*!*************************************!*\
  !*** ./assets/js/src/link-table.js ***!
  \*************************************/
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
    };

    /**
     * Select a defined tab.
     *
     * @param {string} tabName - The name of the tab to select.
     */
    const selectTab = tabName => {
      const tabLink = document.querySelector(`#tab-link-iawmlf_help_${tabName} a`);
      if (tabLink) {
        tabLink.click();
      }
    };

    // When the trigger icon is clicked, show the help tab.
    if (TRIGGER_BULK_ACTIONS) {
      TRIGGER_BULK_ACTIONS.addEventListener('click', function (e) {
        e.preventDefault();
        showHelpTab();

        // Open the Help tab (if it's not open)
        selectTab('bulk_actions');
      });
    }
  });
})();
/******/ })()
;
//# sourceMappingURL=link-table.js.map