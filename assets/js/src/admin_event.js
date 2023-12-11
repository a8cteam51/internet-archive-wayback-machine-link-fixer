/**
 * JS used for the admin event page.
 *
 * @since 1.0.0
 */
(function () {
	"use strict";
	jQuery(document).ready(function () {
		console.log('admin_event.js', adminEvents);
		// Form fields.
		const eventHttp = jQuery('#event_http');
		const eventIgnoreCache = jQuery('#event_ignore_cache');
		const eventTrigger = jQuery('#event_trigger');
		const eventPostTypes = jQuery('input[name="event_post_types[]"]');
		const eventLocalized = adminEvents;
		const eventIgnorePosts = jQuery('#wlf_event_ignore_posts');
		const eventIgnorePostErrors = jQuery('#wlf-event-select2-errors');

		/**
		 * Get the selected post types.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		const getSelectedPostTypes = () => eventPostTypes.filter(':checked').map((key, value) => jQuery(value).val()).get();


		// On trigger click.
		eventTrigger.on('click', function () {
			// Get the data.
			const data = {
				'action': eventLocalized.ajaxActionCreateEvent,
				'nonce': eventLocalized.nonceCreateEvent,
				'event_http': eventHttp.val(),
				'event_ignore_cache': eventIgnoreCache.is(':checked'),
				'event_post_types': getSelectedPostTypes(),
				'user': eventLocalized.userId,
				'blog': eventLocalized.blogId,
				'event_exclude_posts': eventIgnorePosts.val(),
			};
			// Make the ajax call.
			jQuery.ajax({
				url: eventLocalized.ajaxUrl,
				method: 'POST',
				data: data,
				success: function (response) {
					// Add the row to the table.
					const row = response.data.data.row;
					jQuery('table#events tbody').append(row);

					// Hide the no results if shown.
					jQuery('#events #no_results').hide();

				},
				error: function (error) {
					console.log(error.responseJSON.data.message, 'error');
				}
			});
		});

		/**
		 * Populates the select2 for posts to ignore.
		 *
		 * @since 1.0.0
		 */
		eventIgnorePosts.select2({
			ajax: {
				cacheDataSource: [],
				url: eventLocalized.ajaxUrl,
				data: function (params) {
					return {
						q: params.term,
						post_types: getSelectedPostTypes(),
						action: eventLocalized.ajaxActionExcludePosts,
						nonce: eventLocalized.nonceExcludePosts,
					}
				},
				method: 'POST',
				dataType: 'json',
				processResults: function (data) {

					// If we have success false. shpw the error.
					if (!data.success) {
						eventIgnorePostErrors.html(data.data.message).show();
						return { results: [] }; // Return dataset to load after error
					}

					// Clear the event errors.
					eventIgnorePostErrors.html('').hide();
					return {
						results: data.data.items
					};
				}
			}
		});

	});
})();

