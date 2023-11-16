/**
 * JS used for the report list and details
 *
 * @since 1.0.0
 */(function () {
	"use strict";
	jQuery(document).ready(function () {
		const reportToggle = jQuery('.wlf-report-log__header .accordion-toggle');

		// When a report toggle is clicked, toggle the report details.
		reportToggle.on('click', function () {
			// Get the action from the data attribute.
			const action = jQuery(this).data('action');

			// Look up for the .wlf-report-log wrapper.
			const reportLog = jQuery(this).parents('.wlf-report-log');

			// If the action is show, remove closed from the wrapper.
			if (action === 'show') {
				reportLog.removeClass('closed');
			} else {
				reportLog.addClass('closed');
			}

		});
	});
})();
