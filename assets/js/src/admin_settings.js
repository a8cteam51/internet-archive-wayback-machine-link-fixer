/**
 * JS used for the admin settings page.
 *
 * @since 1.0.0
 */
(function () {
	"use strict";

	const escapeHTML = wp.escapeHtml.escapeHTML;
	const { __ } = wp.i18n;

	/**
	 * Reusable exclusion list manager.
	 *
	 * Handles adding items from a template, removing items via event delegation,
	 * and toggling an empty-state message. Used by link exclusions and post exclusions.
	 *
	 * @since 1.4.0
	 *
	 * @param {Object}      config
	 * @param {HTMLElement}  config.container    - The wrapper element containing the list items.
	 * @param {HTMLElement}  config.emptyMessage - The "no items" element to show/hide.
	 * @param {string}       config.template     - HTML template string with placeholders.
	 * @param {string}       config.removeClass  - CSS class on the remove buttons.
	 * @param {string}       config.itemClass    - CSS class on each item row.
	 * @param {string}       config.indexSelector - CSS selector to find elements with data-index (for getNextIndex).
	 *
	 * @return {Object|null} API with addItem, hasItem, checkEmpty methods. Null if container missing.
	 */
	function ExclusionList(config) {
		const container     = config.container;
		const emptyMessage  = config.emptyMessage;
		const template      = config.template;
		const removeClass   = config.removeClass;
		const itemClass     = config.itemClass;
		const indexSelector = config.indexSelector;

		if (!container) {
			return null;
		}

		// Event delegation for remove buttons.
		container.addEventListener('click', function (e) {
			const removeButton = e.target.closest('.' + removeClass);
			if (removeButton) {
				e.preventDefault();
				removeButton.closest('.' + itemClass).remove();
				checkEmpty();
			}
		});

		/**
		 * Get the next available index.
		 *
		 * @return {number}
		 */
		function getNextIndex() {
			const elements = container.querySelectorAll(indexSelector);
			let lastIndex = 0;
			elements.forEach(function (el) {
				const index = parseInt(el.dataset.index) || 0;
				if (index > lastIndex) {
					lastIndex = index;
				}
			});
			return lastIndex + 1;
		}

		/**
		 * Toggle the empty-state message visibility.
		 */
		function checkEmpty() {
			if (!emptyMessage) {
				return;
			}
			const items = container.querySelectorAll('.' + itemClass);
			emptyMessage.style.display = items.length > 0 ? 'none' : '';
		}

		/**
		 * Add an item to the list.
		 *
		 * @param {Object} replacements - Key/value pairs to replace in the template.
		 *                                Keys should include the curly braces, e.g. {newUrl}.
		 */
		function addItem(replacements) {
			let html = template;
			replacements['{newIndex}'] = getNextIndex();
			Object.keys(replacements).forEach(function (key) {
				html = html.replace(new RegExp(key.replace(/[{}]/g, '\\$&'), 'g'), escapeHTML(String(replacements[key])));
			});
			container.insertAdjacentHTML('beforeend', html);
			checkEmpty();
		}

		/**
		 * Check if an item with a given data attribute value already exists.
		 *
		 * @param {string} attr  - The data attribute name (without "data-" prefix).
		 * @param {string} value - The value to check for.
		 *
		 * @return {boolean}
		 */
		function hasItem(attr, value) {
			return container.querySelector('[data-' + attr + '="' + value + '"]') !== null;
		}

		return {
			addItem: addItem,
			hasItem: hasItem,
			checkEmpty: checkEmpty,
		};
	}

	/**
	 * Reusable AJAX-powered search dropdown.
	 *
	 * Debounces input, fetches results via AJAX, renders a dropdown with
	 * highlighted search terms, and supports keyboard navigation.
	 *
	 * @since 1.4.0
	 *
	 * @param {Object}      config
	 * @param {HTMLElement}  config.input    - The search input element.
	 * @param {HTMLElement}  config.dropdown - The dropdown container element.
	 * @param {string}       config.ajaxUrl  - The WordPress AJAX URL.
	 * @param {string}       config.nonce    - The nonce for the AJAX request.
	 * @param {string}       config.action   - The AJAX action name.
	 * @param {string}       [config.context]   - Optional context identifier sent with AJAX request (e.g. 'link_fixer', 'auto_archiver').
	 * @param {Function}     config.onSelect    - Callback when a result is selected. Receives the result object.
	 * @param {Function}     [config.isExcluded] - Optional callback to filter out results. Receives result, returns true to exclude.
	 *
	 * @return {Object|null} API with close method. Null if input or dropdown missing.
	 */
	function PostSearchDropdown(config) {
		const input    = config.input;
		const dropdown = config.dropdown;
		const ajaxUrl  = config.ajaxUrl;
		const nonce    = config.nonce;
		const action   = config.action;
		const context  = config.context || '';
		const onSelect   = config.onSelect;
		const isExcluded = config.isExcluded || function () { return false; };
		const debounceMs = 300;
		const minChars   = 2;

		if (!input || !dropdown) {
			return null;
		}

		let debounceTimer  = null;
		let abortCtrl      = null;
		let activeIndex    = -1;
		let currentResults = [];
		let currentSearch  = '';
		let currentPage    = 1;
		let hasMore        = false;
		let loadingMore    = false;

		/**
		 * Highlight search term in text using <mark> tags.
		 *
		 * @param {string} text   - The text to highlight within.
		 * @param {string} search - The search term to highlight.
		 *
		 * @return {string} HTML string with highlighted matches.
		 */
		function highlight(text, search) {
			if (!search || !text) {
				return text || '';
			}
			const escaped = search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
			return text.replace(new RegExp('(' + escaped + ')', 'gi'), '<mark>$1</mark>');
		}

		/**
		 * Show the dropdown.
		 */
		function show() {
			dropdown.style.display = '';
		}

		/**
		 * Hide the dropdown and reset state.
		 */
		function close() {
			dropdown.style.display = 'none';
			activeIndex = -1;
			currentResults = [];
			currentPage = 1;
			hasMore = false;
		}

		/**
		 * Render a loading state in the dropdown.
		 */
		function showLoading() {
			dropdown.innerHTML =
				'<div class="iawmlf-post-search__loading">' +
				escapeHTML(__('Searching\u2026', 'internet-archive-wayback-machine-link-fixer')) +
				'</div>';
			show();
		}

		/**
		 * Render a no-results state in the dropdown.
		 */
		function showNoResults() {
			dropdown.innerHTML =
				'<div class="iawmlf-post-search__no-results">' +
				escapeHTML(__('No posts found.', 'internet-archive-wayback-machine-link-fixer')) +
				'</div>';
			show();
		}

		/**
		 * Build the "Show more" sentinel row.
		 *
		 * @return {string} The row HTML.
		 */
		function moreRowHtml() {
			return '<div class="iawmlf-post-search__item iawmlf-post-search__more">' + escapeHTML(__('Show more…', 'internet-archive-wayback-machine-link-fixer')) + '</div>';
		}

		/**
		 * Render the search results in the dropdown.
		 *
		 * @param {Array}   results - Array of result objects.
		 * @param {string}  search  - The search term for highlighting.
		 * @param {boolean} append  - Append to the current results rather than replacing them.
		 */
		function renderResults(results, search, append) {
			const startIndex = append ? currentResults.length : 0;
			currentResults = append ? currentResults.concat(results) : results;

			let html = '';
			results.forEach(function (result, i) {
				html += '<div class="iawmlf-post-search__item" data-index="' + (startIndex + i) + '">';
				html += '<div class="iawmlf-post-search__item-title">' + highlight(escapeHTML(result.title), search) + '</div>';
				html += '<div class="iawmlf-post-search__item-meta">' + escapeHTML(result.post_type) + ' &middot; ID: ' + escapeHTML(String(result.id)) + ' &middot; /' + highlight(escapeHTML(result.slug), search) + '</div>';
				html += '</div>';
			});

			if (append) {
				// Append in place - a full innerHTML rewrite would reset the scroll position and keyboard state.
				const moreRow = dropdown.querySelector('.iawmlf-post-search__more');
				if (moreRow) {
					moreRow.remove();
				}
				dropdown.insertAdjacentHTML('beforeend', html + (hasMore ? moreRowHtml() : ''));
				show();
				return;
			}

			activeIndex = -1;
			dropdown.innerHTML = html + (hasMore ? moreRowHtml() : '');
			show();
		}

		/**
		 * Update the active (highlighted) item in the dropdown.
		 */
		function updateActive() {
			const items = dropdown.querySelectorAll('.iawmlf-post-search__item');
			items.forEach(function (item, i) {
				if (i === activeIndex) {
					item.classList.add('iawmlf-post-search__item--active');
					item.scrollIntoView({ block: 'nearest' });
				} else {
					item.classList.remove('iawmlf-post-search__item--active');
				}
			});
		}

		/**
		 * Perform the AJAX search.
		 *
		 * @param {string}  search - The search term.
		 * @param {number}  page   - The page of results to fetch.
		 * @param {boolean} append - Append the results rather than replacing the list.
		 */
		function doSearch(search, page, append) {
			page = page || 1;

			// Abort any in-flight request.
			if (abortCtrl) {
				abortCtrl.abort();
			}
			abortCtrl = new AbortController();

			currentSearch = search;
			loadingMore = !!append;

			if (!append) {
				// A fresh search resets the paging state - stale hasMore/currentPage would let the
				// scroll event fired by the collapsing dropdown trigger a bogus loadMore of the old search.
				currentPage = 1;
				hasMore = false;
				currentResults = [];
				showLoading();
			}

			const formData = new FormData();
			formData.append('action', action);
			formData.append('nonce', nonce);
			formData.append('search', search);
			formData.append('page', page);
			if (context) {
				formData.append('context', context);
			}

			fetch(ajaxUrl, {
				method: 'POST',
				body: formData,
				signal: abortCtrl.signal,
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (data) {
					const payload = data.success && data.data ? data.data : null;
					const results = payload && payload.results ? payload.results : [];
					hasMore = !!(payload && payload.has_more);
					// Only advance the page on success, so a failed append can be retried rather than skipped.
					currentPage = page;

					var filtered = results.filter(function (result) {
						return !isExcluded(result);
					});

					if (filtered.length === 0 && !append) {
						showNoResults();
						return;
					}

					renderResults(filtered, search, append);
				})
				.catch(function (err) {
					if (err.name !== 'AbortError' && !append) {
						showNoResults();
					}
				})
				.finally(function () {
					loadingMore = false;
				});
		}

		/**
		 * Load the next page of results, appending to the list.
		 */
		function loadMore() {
			if (hasMore && !loadingMore) {
				doSearch(currentSearch, currentPage + 1, true);
			}
		}

		// Debounced input listener.
		input.addEventListener('input', function () {
			const value = input.value.trim();

			if (debounceTimer) {
				clearTimeout(debounceTimer);
			}

			if (value.length < minChars) {
				close();
				return;
			}

			debounceTimer = setTimeout(function () {
				doSearch(value);
			}, debounceMs);
		});

		// Keyboard navigation.
		input.addEventListener('keydown', function (e) {
			if (dropdown.style.display === 'none' || currentResults.length === 0) {
				if (e.key === 'Enter') {
					e.preventDefault();
				}
				return;
			}

			if (e.key === 'ArrowDown') {
				e.preventDefault();
				activeIndex = activeIndex < currentResults.length - 1 ? activeIndex + 1 : 0;
				updateActive();
			} else if (e.key === 'ArrowUp') {
				e.preventDefault();
				activeIndex = activeIndex > 0 ? activeIndex - 1 : currentResults.length - 1;
				updateActive();
			} else if (e.key === 'Enter') {
				e.preventDefault();
				if (activeIndex >= 0 && activeIndex < currentResults.length) {
					onSelect(currentResults[activeIndex]);
					input.value = '';
					close();
				}
			} else if (e.key === 'Escape') {
				close();
			}
		});

		// Click on result.
		dropdown.addEventListener('click', function (e) {
			if (e.target.closest('.iawmlf-post-search__more')) {
				loadMore();
				return;
			}

			const item = e.target.closest('.iawmlf-post-search__item');
			if (item) {
				const idx = parseInt(item.dataset.index, 10);
				if (idx >= 0 && idx < currentResults.length) {
					onSelect(currentResults[idx]);
					input.value = '';
					close();
				}
			}
		});

		// Load the next page when scrolled to the bottom of the list.
		dropdown.addEventListener('scroll', function () {
			if (dropdown.scrollTop + dropdown.clientHeight >= dropdown.scrollHeight - 4) {
				loadMore();
			}
		});

		// Click outside closes dropdown.
		document.addEventListener('click', function (e) {
			if (!input.contains(e.target) && !dropdown.contains(e.target)) {
				close();
			}
		});

		return {
			close: close,
		};
	}

	// Wait for DOM to be ready
	document.addEventListener('DOMContentLoaded', function () {

		// Handle dismissing the donation CTA.
		const DONATION_CTA = document.getElementById('iawmlf_donation_cta');
		if (DONATION_CTA) {
			const dismissButton = DONATION_CTA.querySelector('.notice-dismiss');
			if (dismissButton) {
				dismissButton.addEventListener('click', function () {
					DONATION_CTA.remove();
					const formData = new FormData();
					formData.append('action', 'iawmlf_dismiss_donation_cta');
					formData.append('_ajax_nonce', IawmlfSettings.dismissDonationCtaNonce);
					fetch(IawmlfSettings.ajaxUrl, {
						method: 'POST',
						body: formData,
					});
				});
			}
		}

		// Get the settings.
		const PROCESS_LINK = document.getElementById('iawmlf_process_links');
		const AUTO_ARCHIVE = document.getElementById('iawmlf_allow_own_content_submissions');
		const ENVIRONMENTAL = IawmlfSettings.environment;
		const API_ACCESS_KEY = document.getElementById('iawmlf_archive_api_access');
		const API_SECRET_KEY = document.getElementById('iawmlf_archive_api_secret');

		// --- Link Exclusions (using ExclusionList) ---
		const linkExclusionList = ExclusionList({
			container:     document.getElementById('iawmlf_excluded_links'),
			emptyMessage:  document.getElementById('iawmlf_excluded_empty'),
			template:      IawmlfSettings.newExcludedTemplate,
			removeClass:   'remove-exclusion',
			itemClass:     'link',
			indexSelector: 'input[data-index]',
		});

		const NEW_LINK = document.getElementById('iawmlf_excluded_links_new');
		const NEW_LINK_BUTTON = document.getElementById('iawmlf_excluded_links_new_action');

		if (NEW_LINK_BUTTON && NEW_LINK && linkExclusionList) {
			NEW_LINK_BUTTON.addEventListener('click', function (e) {
				e.preventDefault();
				const newLink = NEW_LINK.value.trim();
				if (newLink.length > 0) {
					linkExclusionList.addItem({
						'{newUrl}': newLink,
					});
					NEW_LINK.value = '';
				}
			});
		}

		// --- Post Exclusions (using ExclusionList) ---
		const postExclusionContainer = document.getElementById('iawmlf_excluded_posts');
		const postExclusionList = ExclusionList({
			container:     postExclusionContainer,
			emptyMessage:  postExclusionContainer ? postExclusionContainer.querySelector('.iawmlf-exclusion-list__empty') : null,
			template:      IawmlfSettings.newExcludedPostTemplate,
			removeClass:   'iawmlf-exclusion-list__remove',
			itemClass:     'iawmlf-exclusion-list__item',
			indexSelector: '[data-index]',
		});

		// AJAX post search for adding post exclusions.
		const POST_SEARCH_INPUT = postExclusionContainer ? postExclusionContainer.querySelector('.iawmlf-post-search__input') : null;
		const POST_SEARCH_DROPDOWN = postExclusionContainer ? postExclusionContainer.querySelector('.iawmlf-post-search__dropdown') : null;
		if (POST_SEARCH_INPUT && POST_SEARCH_DROPDOWN && postExclusionList) {
			PostSearchDropdown({
				input:    POST_SEARCH_INPUT,
				dropdown: POST_SEARCH_DROPDOWN,
				ajaxUrl:  IawmlfSettings.ajaxUrl,
				nonce:    IawmlfSettings.postSearchNonce,
				action:   'iawmlf_post_search',
				context:  'link_fixer',
				isExcluded: function (result) {
					return postExclusionList.hasItem('post-id', result.id.toString());
				},
				onSelect: function (result) {
					if (!postExclusionList.hasItem('post-id', result.id.toString())) {
						postExclusionList.addItem({
							'{postId}':    String(result.id),
							'{postTitle}': result.title,
							'{postType}': result.post_type,
						});
					}
				},
			});
		}

		// --- Auto Archiver Post Exclusions (using ExclusionList) ---
		const archiverExclusionContainer = document.getElementById('iawmlf_excluded_archiver_posts');
		const archiverExclusionList = ExclusionList({
			container:     archiverExclusionContainer,
			emptyMessage:  archiverExclusionContainer ? archiverExclusionContainer.querySelector('.iawmlf-exclusion-list__empty') : null,
			template:      IawmlfSettings.newExcludedArchiverPostTemplate,
			removeClass:   'iawmlf-exclusion-list__remove',
			itemClass:     'iawmlf-exclusion-list__item',
			indexSelector: '[data-index]',
		});

		// AJAX post search for adding auto archiver post exclusions.
		const ARCHIVER_SEARCH_INPUT = archiverExclusionContainer ? archiverExclusionContainer.querySelector('.iawmlf-post-search__input') : null;
		const ARCHIVER_SEARCH_DROPDOWN = archiverExclusionContainer ? archiverExclusionContainer.querySelector('.iawmlf-post-search__dropdown') : null;
		if (ARCHIVER_SEARCH_INPUT && ARCHIVER_SEARCH_DROPDOWN && archiverExclusionList) {
			PostSearchDropdown({
				input:    ARCHIVER_SEARCH_INPUT,
				dropdown: ARCHIVER_SEARCH_DROPDOWN,
				ajaxUrl:  IawmlfSettings.ajaxUrl,
				nonce:    IawmlfSettings.postSearchNonce,
				action:   'iawmlf_post_search',
				context:  'auto_archiver',
				isExcluded: function (result) {
					return archiverExclusionList.hasItem('post-id', result.id.toString());
				},
				onSelect: function (result) {
					if (!archiverExclusionList.hasItem('post-id', result.id.toString())) {
						archiverExclusionList.addItem({
							'{postId}':    String(result.id),
							'{postTitle}': result.title,
							'{postType}': result.post_type,
						});
					}
				},
			});
		}

		/**
		 * Watch for changes on the API key fields.
		 *
		 * @since 1.3.1
		 */
		if (API_ACCESS_KEY || API_SECRET_KEY) {
			// Constants for API key UI management
			const INVALID_API_KEYS_CLASS = 'iawmlf_toggle_setting__invalid_api_keys';
			const UNCHECKED_API_KEYS_CLASS = 'iawmlf_toggle_setting__unchecked_api_keys';
			const INVALID_API_CREDS_ID = 'invalid_api_creds';
			const UNCHECKED_API_CREDS_ID = 'unchecked_api_creds';

			// Check if either field has data-is-valid = 0
			const shouldWatch = (API_ACCESS_KEY && API_ACCESS_KEY.dataset.isValid === '0') ||
								(API_SECRET_KEY && API_SECRET_KEY.dataset.isValid === '0');

			/**
			 * Updates the API key UI state.
			 *
			 * @param {boolean} isUnchecked - Whether to show unchecked state.
			 */
			function updateApiKeyUI(isUnchecked) {
				const parentDivs = document.querySelectorAll('.' + (isUnchecked ? INVALID_API_KEYS_CLASS : UNCHECKED_API_KEYS_CLASS));
				const targetClass = isUnchecked ? UNCHECKED_API_KEYS_CLASS : INVALID_API_KEYS_CLASS;
				const removeClassName = isUnchecked ? INVALID_API_KEYS_CLASS : UNCHECKED_API_KEYS_CLASS;

				parentDivs.forEach(function(parentDiv) {
					parentDiv.classList.remove(removeClassName);
					parentDiv.classList.add(targetClass);
				});

				const invalidCreds = document.getElementById(INVALID_API_CREDS_ID);
				const uncheckedCreds = document.getElementById(UNCHECKED_API_CREDS_ID);

				if (invalidCreds) {
					invalidCreds.style.display = isUnchecked ? 'none' : 'block';
				}
				if (uncheckedCreds) {
					uncheckedCreds.style.display = isUnchecked ? 'block' : 'none';
				}
			}

			if (shouldWatch) {
				// Watch for changes on both fields
				[API_ACCESS_KEY, API_SECRET_KEY].forEach(function(field) {
					if (field) {
						field.addEventListener('input', function() {
							const currentValue = this.value;
							const previousValue = this.dataset.previousValue || '';

							if (currentValue !== previousValue) {
								updateApiKeyUI(true);
							} else if (currentValue === previousValue && previousValue !== '') {
								updateApiKeyUI(false);
							}
						});
					}
				});
			}
		}

		/**
		 * Prevents default behavior of select elements.
		 *
		 * @param {Event} e - The event object.
		 *
		 */
		function doNothing(e) {
			e.preventDefault();
		}

		// When a checkbox or radio has iawmlf-read-only class, prevent the user from changing it.
		document.addEventListener('click', function (e) {
			if (e.target.matches('.iawmlf_settings_card input.iawmlf-read-only, .iawmlf_settings_card select.iawmlf-read-only')) {
				doNothing(e);
			}
		});

		// When Process Links is checked, enable the excluded links.
		if (PROCESS_LINK) {
			PROCESS_LINK.addEventListener('change', function () {
				toggleElements(this.checked, 'link_fixer');
			});
		}

		// if not production, disable the auto archive option.
		if ('production' !== ENVIRONMENTAL) {
			AUTO_ARCHIVE.disabled = true;
		}

		// When Auto Archive is checked, enable the excluded links.
		if (AUTO_ARCHIVE) {
			AUTO_ARCHIVE.addEventListener('change', function () {
				toggleElements(this.checked, 'auto_archiver');
			});
		}

		/**
		 * Toggles the state of elements in the specified group.
		 * @param {boolean} isChecked - The state of the checkbox.
		 * @param {string} groupName - The data-group attribute value.
		 */
		function toggleElements(isChecked, groupName) {

			// Get the field list, based on the group name.
			const fieldList = 'link_fixer' === groupName
				? '.iawmlf_toggle_setting__fixer'
				: '.iawmlf_toggle_setting__auto_archiver';

			const elements = document.querySelectorAll(fieldList);
			elements.forEach(function (element) {
				if (!isChecked) {
					element.classList.add('hidden');
				} else {
					element.classList.remove('hidden');
				}
			});
		}

		// --- Link Icon Preview ---
		const LINK_ICON_SELECT  = document.getElementById('iawmlf_link_icon');
		const LINK_ICON_PREVIEW = document.getElementById('iawmlf_link_icon_preview');
		const FIXER_OPTION      = document.getElementById('iawmlf_fixer_option');

		if (LINK_ICON_SELECT && LINK_ICON_PREVIEW && IawmlfSettings.linkIconStyles) {
			const previewStyle = document.getElementById('iawmlf-link-icon-preview-style');

			/**
			 * Update the link icon preview.
			 */
			function updateLinkIconPreview() {
				const selectedId = LINK_ICON_SELECT.value;
				const css = IawmlfSettings.linkIconStyles[selectedId] || '';
				previewStyle.textContent = css;
			}

			LINK_ICON_SELECT.addEventListener('change', updateLinkIconPreview);
		}

		// --- Fixer Option toggles Link Icon visibility ---
		if (FIXER_OPTION) {
			/**
			 * Toggle the link icon field visibility based on the fixer option.
			 */
			function toggleFixerReplaceFields() {
				var isReplace = FIXER_OPTION.value === 'replace_link';
				var elements = document.querySelectorAll('.iawmlf_toggle_setting__fixer_replace');
				elements.forEach(function (element) {
					if (isReplace) {
						element.classList.remove('hidden');
					} else {
						element.classList.add('hidden');
					}
				});
			}

			FIXER_OPTION.addEventListener('change', toggleFixerReplaceFields);
			toggleFixerReplaceFields();
		}

		// Runs the checks on load
		if (PROCESS_LINK) {
			toggleElements(PROCESS_LINK.checked, 'link_fixer');
		}
		if (AUTO_ARCHIVE) {
			toggleElements(AUTO_ARCHIVE.checked, 'auto_archiver');
		}
	});
})();
