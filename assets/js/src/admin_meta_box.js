/**
 * JS used for the admin meta box.
 *
 * @since 1.0.0
 */
(function () {
	"use strict";
	jQuery(document).ready(function () {
		// Shared constants.
		const runAction = jQuery('#wlf-meta-box-runner button');
		const ignoreLinkCache = jQuery('#wlf-meta-box-runner input[name="wlf-meta-box-runner__ignore-cache"]');
		const searchHTTPCodes = jQuery('#wlf-meta-box-runner input[name="wlf-meta-box-runner__status-codes"]');
		const progressBar = jQuery('#wlf-meta-box-results__progress');
		const reports = jQuery('#wlf-meta-box-results');

		/**
		 * Show the progress bar.
		 *
		 * @since 1.0.0
		 *
		 * @param {string} message
		 */
		function showProgressBar(message) {
			progressBar.text(message);
			progressBar.css('display', 'block');
		}

		/**
		 * Hide the progress bar.
		 *
		 * @since 1.0.0
		 *
		 */
		function hideProgressBar() {
			progressBar.text('');
			progressBar.css('display', 'none');
		}

		/**
		 * Render a notice.
		 *
		 * @since 1.0.0
		 *
		 * @param {string} message
		 * @param {string} type
		 */
		function renderNotice(message, type) {
			// If the type is not set, set it to info.
			if (typeof type === 'undefined') {
				type = 'info';
			}

			wp.data.dispatch("core/notices").createNotice(
				type,
				message,
				{
					id: 'email_status_notice',
					isDismissible: true
				}
			);
		}

		// When run action is clicked.
		runAction.click(function (e) {
			e.preventDefault();

			// Show the progress bar.
			showProgressBar('Running...');

			// Get the data to send.
			const data = {
				'ignoreCache': ignoreLinkCache.is(':checked') ? 1 : 0,
				'httpCodes': searchHTTPCodes.val(),
				'nonce': reportRunner.nonce,
				'postId': reportRunner.postId,
				'action': reportRunner.action
			};

			renderNotice('Running the report, this may take a while.', 'error');



			// If we dont have a post ID or the http codes are empty, show an error.
			if (data.postId === '' || data.httpCodes === '') {
				renderNotice('Please select a post and enter some status codes to search for.', 'error');
				return;
			}

			// Do the ajax request.
			jQuery.ajax({
				url: reportRunner.ajaxUrl,
				method: 'POST',
				data: data,
				success: function (response) {
					// Hide the progress bar.
					hideProgressBar();

					// Render the notices.
					renderNotice(response.data.message, 'success');

					// Update the list of reports.
					reports.html(response.data.reportsHTML);
					// Add the updated class to the reports.
					reports.addClass('updated-results');

					console.log(response.data.details);
				},
				error: function (error) {
					// Hide the progress bar.
					hideProgressBar();

					// Render the notices.
					renderNotice(error.responseJSON.data.message, 'error');
				}
			});
		});
	});
})();
