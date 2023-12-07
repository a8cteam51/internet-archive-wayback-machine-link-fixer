/**
 * JS used for the report list and details
 *
 * @since 1.0.0
 */(function () {
	"use strict";
	jQuery(document).ready(function () {
		const reportToggle = jQuery('.wlf-report-log__header .accordion-toggle');
		const generateCSVAjax = jQuery('.wlf-download-report-csv');
		const notifications = jQuery('#wlf-report-notifications');


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

		const showNotification = (message, type) => {
			// Clear any notifications.
			notifications.html('');

			// Show the notification.
			notifications.append(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`);
		};

		// When the generate CSV button is clicked.
		generateCSVAjax.on('click', function () {
			// Get the data attributes.
			const report = jQuery(this).data('report');
			const nonce = wlf_report_viewer.csv_nonce;
			const action = wlf_report_viewer.csv_action;
			const ajaxUrl = wlf_report_viewer.ajax_url;
			console.log(report, nonce, action, ajaxUrl);

			// Clear any notifications.
			showNotification('Generating CSV...', 'info');



			// Make the ajax call.
			jQuery.ajax({
				url: ajaxUrl,
				method: 'POST',
				data: {
					action: action,
					nonce: nonce,
					report: report
				},
				success: function (response) {

					// If we have an error, show it.
					if (false === response.success) {
						showNotification(response.data.message, 'error');
						return;
					}

					// If we have a url in the response, trigger download.
					if (response.data.url) {
						top.location.href = response.data.url;
						showNotification(`If your download doesn't start automatically please click here to <a href="${response.data.url}" download>download CSV</a>`, 'success');
					} else {
						showNotification('Something went wrong, please try again.', 'error');
					}
				},
				error: function (error) {
					showNotification(error.responseText, 'error');
				}
			});
		});
	});
})();
