/**
 * Script that will check if the link is valid or not
 *
 * @since 1.2.0
 */

/**
 * Get all the links from the localized object
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
	root: null,
	rootMargin: '0px',
	threshold: 0
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
		if (removeTrailingSlash(archivedLink.href) === removeTrailingSlash(link)) {
			return archivedLink;
		}
	}

	return null;
}

/**
 * Removes the trailing slash from a string if set.
 *
 * @param {string} str The string to check
 * @returns {string} The string without the trailing slash
 */
const removeTrailingSlash = (str) => {
	// If we have no string, return it
	if (str === null || str === '') {
		return str;
	}

	// If str does not have a trailing slash, return it
	if (str.slice(-1) !== '/') {
		return str;
	}

	// Remove the trailing slash
	return str.replace(/\/$/, '');
};


/**
 * Adds the data attributes to a link.
 *
 * @param {object} link The link to add the data attributes to
 * @returns {void}
 */
const addDataAttributes = (link) => {

	// Bail if not internal staff.
	if(wlfArchivedLinks.isInternal == '0'){
		return;
	}

	// Get the href.
	const href = removeTrailingSlash(link.href);

	// Look for every instance of the link on the page.
	const links = document.getElementsByTagName('a');

	// Iterate through all the links
	for (let i = 0; i < links.length; i++) {
		let currentLink = links[i];

		// If the link is the same as the current link, add the data attributes
		if (removeTrailingSlash(currentLink.href) === href) {
			currentLink.setAttribute('data-wlf-archived-url', link.archived_href);
			currentLink.setAttribute('data-wlf-current-url', href);
			currentLink.setAttribute('data-wlf-archived-broken', link.broken);
			// If we have a last checked date, add it
			if (link.last_checked !== null && link.last_checked.date !== null) {
				currentLink.setAttribute('data-wlf-archived-last-checked', link.last_checked.date);
			}

			// If the link is broken, add a class and change the href
			if (link.broken) {
				currentLink.classList.add('wlf-broken-link');
				currentLink.href = '' !== link.archived_href ? link.archived_href : href;
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
		// add a checked link
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

	// If there is no archived link, return
	if (archived.archived_href === null || archived.archived_href === '') {
		return;
	}

	// IF the last checked is NULL or outside the delay, check the link
	if (archived.last_checked === null || daysSince(archived.last_checked.date) > linkDelay) {
		// Check the link
		verifyLink(link).then((result) => {

			// If the link can not be found, skip.
			if (result.success === false || result.data || result.data.link) {
				return;
			}

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
}
init();
