/**
 * Script that will check if the link is valid or not
 *
 * @since 1.2.0
 */

import { toolTip } from "./tooltip";



/**
 * Get all the links from the localised object
 */
const linkArchives = JSON.parse(wlfArchivedLinks.links);

/**
 * Delays between checking the links
 */
const linkDelay = wlfArchivedLinks.linkDelayInDays;

/**
 * The settings for the link check
 */
const linkCheckSettings = {
	'action': wlfArchivedLinks.linkCheckAjax,
	'nonce': wlfArchivedLinks.linkCheckNonce,
	'url': wlfArchivedLinks.ajaxUrl
};

/**
 * Initialize event listeners
 *
 * @returns {void}
 */
const initEventListeners = () => {
	// When the screen is scrolled by the user, check the links
	window.addEventListener('scroll', checkLinks);
}

/**
 * Gets details of the size of the viewport
 * @returns {width: number, height: number}
 */
const getViewportSize = () => {
	return {
		width: window.innerWidth || document.documentElement.clientWidth,
		height: window.innerHeight || document.documentElement.clientHeight
	};
}
const viewPortWith = getViewportSize().width;
const viewPortHeight = getViewportSize().height;

// When the page is scrolled, get all the link that are visible and check if they are valid using VANILLA JS
const getLinksInViewport = () => {
	// Get the bounding rectangle of the viewport
	const viewport = {
		top: 0,
		left: 0,
		right: viewPortWith,
		bottom: viewPortHeight
	};

	// Get all <a> elements in the document
	const links = document.getElementsByTagName('a');
	const linksInViewport = [];

	// Iterate through all <a> elements
	for (var i = 0; i < links.length; i++) {
		let link = links[i];
		let rect = link.getBoundingClientRect();

		// Check if the link is in the viewport
		if (
			rect.top >= viewport.top &&
			rect.left >= viewport.left &&
			rect.bottom <= viewport.bottom &&
			rect.right <= viewport.right
		) {
			// Add the link's href to the array
			linksInViewport.push(link.href);
		}
	}

	return linksInViewport;
}

/**
 * Gets the archived link details if it exists
 *
 * @param {string} link The link to check
 *
 * @returns {object} The archived link details
 */
const getArchivedLink = (link) => {
	const archivedLinks = linkArchives;

	// Iterate through all the archived links
	for (let i = 0; i < archivedLinks.length; i++) {
		let archivedLink = archivedLinks[i];

		// Check if the link exists in the archived links
		if (archivedLink.href === link) {
			return archivedLink;
		}
	}

	return null;
}

/**
 * Adds the data attributes to a link.
 *
 * @param {object} link The link to add the data attributes to
 * @returns {void}
 */
const addDataAttributes = (link) => {
	// Get the href.
	const href = link.href;

	// Look for every instance of the link on the page.
	const links = document.getElementsByTagName('a');

	// Iterate through all the links
	for (let i = 0; i < links.length; i++) {
		let currentLink = links[i];

		// If the link is the same as the current link, add the data attributes
		if (currentLink.href === href) {
			currentLink.setAttribute('data-wlf-archived-url', link.archived_href);
			currentLink.setAttribute('data-wlf-current-url', href);
			currentLink.setAttribute('data-wlf-archived-broken', link.broken);
			currentLink.setAttribute('data-wlf-archived-last-checked', link.last_checked.date);

			// If the link is broken, add a class and change the href
			if (link.broken) {
				currentLink.classList.add('wlf-broken-link');
				currentLink.href = link.archived_href;
			}
		}
	}
}

/**
 * Checks all the links in the viewport and checks if they are valid
 *
 * @returns {void}
 */
const checkLinks = () => {
	// Get all the links in the viewport
	const linksInViewport = getLinksInViewport();

	// Iterate through all the links in the viewport and see if they exist in the archived links
	for (let i = 0; i < linksInViewport.length; i++) {
		let link = linksInViewport[i];

		let archived = getArchivedLink(link);

		// If we dont have any details, continue
		if (archived === null) {
			continue;
		}

		// If the link is already marked as broken, add the data attributes
		if (archived.broken) {
			addDataAttributes(archived);
			continue;
		}


		// IF the last checked is NULL or outside the delay, check the link
		if (archived.last_checked === null || daysSince(archived.last_checked.date) > linkDelay) {
			// Check the link
			checkLink(link).then((result) => {
				addDataAttributes(result.data.link);
			});

			continue;
		}
	}
}

/**
 * Gets the number of days since the passed date.
 *
 * @param {string} date The date to check
 * @returns {number} The number of days since the date
 */
const daysSince = (date) => {
	const now = new Date();
	const lastChecked = new Date(date);

	const diff = now - lastChecked;
	const days = Math.ceil(diff / (1000 * 60 * 60 * 24));

	return days;
}

/**
 * Check if a link is valid or not.
 *
 * @param {string} link The link to check
 * @return {number} The status of the link
 */
const checkLink = async (link) => {
	const settings = linkCheckSettings;

	const formData = new FormData();
	formData.append('action', settings.action);
	formData.append('nonce', settings.nonce);
	formData.append('link', link);

	const response = await fetch(settings.url, {
		method: 'POST',
		body: formData
	})
		.then(response => response.json())
		.then(data => {
			return data;
		})
		.catch((error) => {
			console.error('Error:', error);
		});

	return await response;

}



/**
 * Initialize the scripts.
 *
 * @returns {void}
 */
const init = () => {
	initEventListeners();
	checkLinks();
	toolTip.init();
}
init();
