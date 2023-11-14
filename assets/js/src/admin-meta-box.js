(function () {
	"use strict";
	jQuery(document).ready(function () {
		// Get the full url with all args.
		const currentUrl = window.location.href;

		// Shared constants.
		const runAction = jQuery('#wlf-meta-box-runner button');
		const ignoreLinkCache = jQuery('#wlf-meta-box-runner input[name="wlf-meta-box-runner__ignore-cache"]');
		const searchHTTPCodes = jQuery('#wlf-meta-box-runner input[name="wlf-meta-box-runner__status-codes"]');

		// When run action is clicked.
		runAction.click(function (e) {
			e.preventDefault();

			// Get the data to send.
			const data = {
				'ignoreCache': ignoreLinkCache.is(':checked') ? 1 : 0,
				'httpCodes': searchHTTPCodes.val(),
			};

			// Build the url.
			const url = currentUrl + '&wlf_runner_action=run&ignore_cache=' + data.ignoreCache + '&http_codes=' + data.httpCodes;

			// Prompt the user and redirect to the URL if approved.
			if (confirm("Running this will see the page reload, any unsaved changes will be ignored. Are you sure you want to run this now?")) {
				// Redirect to the URL.
				window.location.replace(url);
			} else {
				console.log('User cancelled the action.');
			}
		});

		/**
		 * Clear any url params on page load.
		 *
		 * @since 1.0.0
		 *
		 */
		function clearUrlParams() {
			const url = new URL(window.location.href);
			url.searchParams.delete('wlf_runner_action');
			url.searchParams.delete('autofix');
			url.searchParams.delete('remove_redirects');
			history.replaceState(null, null, url);
		}

		// Clear any url params on page load.
		clearUrlParams();
	});
})();
