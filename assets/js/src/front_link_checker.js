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
 * Holds all the checked links
 *
 * @type {Array}
 */
const checkedLinks = [];

/**
 * Initialize event listeners
 *
 * @returns {void}
 */
const initObservers = () => {
	// Select all links you want to monitor
	const links = document.querySelectorAll('a');

	// Observe each link
	links.forEach(link => {
		linkObserver.observe(link);
	});

}

/**
 * Create an instance of the IntersectionObserver for the links
 *
 * @returns {void}
 */
const linkObserver = new IntersectionObserver((links, observer) => {
	links.forEach(entry => {
		if (entry.isIntersecting) {
			// Check the link

			checkLink(entry.target.href);
		}
	});
}, {
	root: null, // Use viewport as root
	rootMargin: '0px', // No margin around the viewport
	threshold: 0 // Callback executed as soon as even one pixel is visible
});


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
	return [];

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
 * Checks if the link has been checked.
 *
 * @param {string} link The link to check
 * @returns {boolean} If the link has been checked
 */
const hasBeenChecked = (link) => checkedLinks.includes(link);

/**
 * Add a link to the checked links
 *
 * @param {string} link The link to add
 * @returns
 */
const addCheckedLink = (link) => checkedLinks.push(link);



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
 * Checks a link.
 *
 * Verifies if the link has an archived version and if it is broken.
 * Will also check the link if it has not been checked in the last x days.
 *
 * @param {string} link The link to check
 * @return {void}
 */
const checkLink = (link) => {
	// If the link has been checked, continue
	if (hasBeenChecked(link)) {
		return;
	} else {
		addCheckedLink(link);
	}

	// Check if the link has been archived and data passed.
	const archived = getArchivedLink(link);

	// If we dont have any details, continue
	if (archived === null) {
		return;
	}

	// If the link is already marked as broken, add the data attributes
	if (archived.broken) {
		addDataAttributes(archived);
		return;
	}

	// IF the last checked is NULL or outside the delay, check the link
	if (archived.last_checked === null || daysSince(archived.last_checked.date) > linkDelay) {
		// Check the link
		verifyLink(link).then((result) => {
			addDataAttributes(result.data.link);
		});
	}
}


/**
 * Verifies the link using the server.
 *
 * @param {string} link The link to check
 * @return {number} The status of the link
 */
const verifyLink = async (link) => {
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
	initObservers();
	toolTip.init();
}
init();
