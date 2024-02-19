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
		const linkTableRows = jQuery('table.reports tbody#the-list');

		// Modal.
		const modal = jQuery('#wlf-modal');
		const modalContent = jQuery('#wlf-modal__inner-content');
		const modalClose = jQuery('#wlf-modal__inner-header-close span.dashicons');
		const modalTitle = jQuery('#wlf-modal__inner-header-title');

		// Show link comments.
		const linkComments = jQuery('.wlf-report-link-actions__comments');
		const linkFixes = jQuery('.wlf-report-link-actions__fixes');

		// Handles the fix modal submit.
		const fixForm = jQuery('#wlf-report-fix-form');

		// Ensure modal is hidden by default.
		modal.hide();

		/**
		 * Enable select2 for all filters on either single or list view.
		 *
		 * @since 1.1.0
		 */
		jQuery('.wlf-multiselect2').select2({
			placeholder: function () {
				jQuery(this).data('placeholder');
			},
			allowClear: true,
			height: '32px',
			width: '100px'
		});
		jQuery('.wlf-select2').select2({
			height: '32px',
			dropdownAutoWidth: true,
			width: '100px'
		});


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

		/**
		 * When link fixes is clicked.
		 */
		linkFixes.on('click', function (e) {
			// Get the post id.
			const postId = jQuery(this).data('post_id');

			// Get the link href
			const link = jQuery(this).data('url');

			// Get the log id.
			const logId = jQuery(this).data('log');

			// If we have no link or postId, show error in modal.
			if (!link) {
				showInModal('Error', 'This link is not valid and can not be fixed');
				return;
			}

			if (!postId) {
				showInModal('Error', 'Something went wrong, please try again.');
				return;
			}

			// Get any options from the link.
			const options = jQuery(this).data('options');


			// Render the form.
			const form = `
				<form action="" method="post" id="wlf-report-fix-form">
					{{#options}}
					<input type="hidden" id="fix-form__post_id" value="${postId}">
					<input type="hidden" id="fix-form__url" value="${link}">
					<input type="hidden" id="fix-form__log" value="${logId}">
					<input type="hidden" id="action" value="wlf_report_fix">
					<label for="custom_url">Custom URL</label>
					<input type="text" id="fix-form__custom_url" value="" placeholder="Please enter a custom URL">
					<button type="submit" id="wlf-fix-form-submission" class="button button-primary">Fix</button>
				</form>
			`;

			// If we have any options, render them as select.
			const optionsHtml = options.length !== 0
				? `<select id="fix-form__select_url"><option value="__custom_url" id="__custom_url">Custom URL</option>${options.map(option => `<option value="${option}">${option}</option>`).join('')}</select>`
				: '';


			// Show the form in the modal.
			showInModal('Link Options', form.replace('{{#options}}', optionsHtml));

		});

		/**
		 * Show custom url form if has the value __custom_url
		 *
		 * Is loaded in dynamically.
		 */
		jQuery(document).on('change', '#fix-form__select_url', function (e) {
			// Get the value.
			const val = jQuery(this).find(":checked").val()

			// Based on the value, either hide or show the custom url field.
			if ('__custom_url' === val) {
				jQuery('#fix-form__custom_url').show();
			} else {
				jQuery('#fix-form__custom_url').hide();
			}
		});


		/**
		 * When the fix form is clicked.
		 */
		jQuery(document).on("click", '#wlf-fix-form-submission', function (e) {

			// Prevent default.
			e.preventDefault();

			// Get the fields.
			const postID = jQuery('#fix-form__post_id').val();
			const url = jQuery('#fix-form__url').val();
			const customUrl = jQuery('#fix-form__custom_url').val();
			const select = jQuery('#fix-form__select_url').find(":selected").val();
			const log = jQuery('#fix-form__log').val();

			// If select is empty or __custom_url, use the customUrl.
			const useCustomUrl = !select || select === '__custom_url' || select === ''
				? customUrl
				: select;

			// If we are using a custom url, ensure it's not empty.
			if (select === '__custom_url' && useCustomUrl === '') {
				alert('Please enter a custom URL');
				return;
			}

			const data = {
				action: wlf_report_viewer.fix_action,
				nonce: wlf_report_viewer.fix_nonce,
				post_id: postID,
				log_id: log,
				url: url,
				new_url: useCustomUrl
			};

			// Disable all form fields to prevent double submission.
			jQuery('#wlf-report-fix-form input').prop('disabled', true);
			jQuery('#wlf-report-fix-form button').prop('disabled', true);
			jQuery('#fix-form__select_url').prop('disabled', true);



			// Make the ajax call.
			jQuery.ajax({
				url: wlf_report_viewer.ajax_url,
				method: 'POST',
				data: data,
				success: function (response) {
					// Get all the links.
					const links = response.data.updatedLinks;

					// if we have no links, show error.
					if (!links || links.length === 0) {
						showNotification('Something went wrong, please try again.', 'error');
						modal.hide();
						return;
					}

					// Iterate over the links and udpate them.
					links.forEach(link => {
						updateLinkInTable(link);
					});

					// Hide the modal.
					modal.hide();

					// Show notification.
					showNotification('Link(s) updated', 'success');

				},
				error: function (error) {
					showNotification(error.responseText, 'error');
				}
			});
		});

		/**
		 * Mark a row as updated.
		 *
		 * @param {object} updatedLink The Updated link object.-
		 */
		const updateLinkInTable = (updatedLink) => {
			const postId = updatedLink.post_id;
			const index = updatedLink.index;

			// Look for a row <tr> with the same postId and index.
			linkTableRows.find('tr').each((i, row) => {

				// Get the tr data.
				let data = jQuery(row).data();

				// If we have the correct post id and index, update the row.
				if (data.postId === postId && data.index === index) {


					// Update the comments.
					jQuery(row).find('.report-link-actions span.wlf-report-link-actions__comments')
						.attr('data-comments', JSON.stringify(updatedLink.comments));

					// Remove the current classes for updated or not.
					jQuery(row).find('.report-link-fixed span.dashicons')
						.removeClass('dashicons-yes-alt')
						.removeClass('dashicons-no');

					// Add the correct class based on the updated value.
					jQuery(row).find('.report-link-fixed span.dashicons')
						.addClass(updatedLink.updated ? 'dashicons-yes-alt' : 'dashicons-no');

					// Remove the option to fix if updated.
					if (updatedLink.updated) {
						jQuery(this).find('.report-link-actions span.wlf-report-link-actions__fixes[data-post_id="' + postId + '"][data-link_index="' + index + '"]')
							.hide()

					}
				}

			})

		}

		/**
		 * When show comments is clicked.
		 */
		linkComments.on('click', function (e) {
			// Get the comments from the data attribute.
			const comments = jQuery(this).data('comments');

			// Cast to array from object
			const commentsArray = Object.values(comments);

			// Unpack the array into a string of <p>
			const commentsHtml = commentsArray.map(comment => `<p>${comment}</p>`).join('');

			// Show the comments in the modal.
			showInModal('Details', commentsHtml);
		});

		/**
		 * Show contents in modal.
		 * @param {string} message The message to show in the modal.
		 */
		const showInModal = (title, message) => {
			// Set the content.
			modalContent.html(message);

			// Set the title.
			modalTitle.html(title)

			// Show the modal.
			modal.show();
		}

		/**
		 * Hides the modal.
		 */
		modalClose.on('click', function () {
			// Clear the content.
			modalContent.html('');

			modal.hide();
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
