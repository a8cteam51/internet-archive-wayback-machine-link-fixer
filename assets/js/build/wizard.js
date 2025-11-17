/******/ (function() { // webpackBootstrap
var __webpack_exports__ = {};
/*!*********************************!*\
  !*** ./assets/js/src/wizard.js ***!
  \*********************************/
(function () {
  // If #iawmlf_wizard form has an input with the id of is_active, add an event listenter when its toggled.
  const isActive = document.getElementById('is_active');
  if (isActive) {
    // Add event listener to the is_active checkbox, and make fields disabled.
    isActive.addEventListener('change', function () {
      const optionalFields = document.querySelectorAll('.is_optional');

      // Iterate over the optional fields and find the inputs/selects.
      optionalFields.forEach(function (field) {
        const inputs = field.querySelectorAll('input, select');

        // Iterate over the inputs and set the disabled state.
        inputs.forEach(function (input) {
          input.disabled = !isActive.checked;
        });

        // Add or remove the disabled class from the field.
        field.classList.toggle('disabled', !isActive.checked);
      });
    });
  }
})();
/******/ })()
;
//# sourceMappingURL=wizard.js.map