/**
 * Script that will check if the link is valid or not
 *
 * @since 1.2.0
 */

/**
 * Get all the links from the localized object
 */
let allLinks = JSON.parse(iawmlfArchivedLinks.links);

/**
 * Delays between checking the links
 */
const linkDelay = iawmlfArchivedLinks.linkDelayInDays;

/**
 * The settings for the link check
 */
const linkCheckSettings = {
	'nonce': iawmlfArchivedLinks.linkCheckNonce,
	'url': iawmlfArchivedLinks.restUrl,
	'fixerOption': iawmlfArchivedLinks.fixerOption
};

/**
 * Holds all the checked links
 *
 * @type {Array}
 */
const checkedLinks = [];

/**
 * Holds all the links found on the rendered page.
 *
 * Updates when new links are added to the DOM.
 *
 * @type {Array}
 */
let pageLinks = [];

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

	pageLinks = links;

	// Create a MutationObserver to watch for new links
	const observer = new MutationObserver((mutationsList) => {
		for (const mutation of mutationsList) {
			if (mutation.type === 'childList') {
				// Check if new nodes were added
				mutation.addedNodes.forEach(node => {
					if (node.nodeType === Node.ELEMENT_NODE) {
						// Update the links
						allLinks = getRenderedLinks();

						// If a new link is added, observe it
						if (node.tagName === 'A') {
							linkObserver.observe(node);
						}

						// Check for links added inside newly added elements
						const newLinks = node.querySelectorAll ? node.querySelectorAll('a') : [];
						newLinks.forEach(link => {
							linkObserver.observe(link);
						});

						// add the new links to the pageLinks
						pageLinks = [...pageLinks, ...newLinks];
					}
				});
			}
		}
	});

	// Observe the entire document for changes
	observer.observe(document.body, {
		childList: true,
		subtree: true
	});
}


/**
 * Get all links from the loop items on the page.
 *
 * @returns {Array}
 */
const getRenderedLinks = () => {

	/**
	 * Adds an array of links to the allLinks array
	 * @param {Array} links The links to add
	 * @returns {void}
	 */
	const addLinks = (links) => {
		// If we dont have an array or its empty, return
		if (links === null || links === undefined || links.length === 0) {
			return;
		}

		// Iterate through all the links.
		links.forEach((link) => {
			// If link.id is not in the links array, add it
			if (!allLinks.some(e => e.id === link.id)) {
				allLinks.push(link);
			}
		});
	}

	// Look for all divs with '__iawmlf-post-loop-links' class
	const loopLinks = document.querySelectorAll('.__iawmlf-post-loop-links');

	// Get the links from data-iawmlf-post-links attribute
	loopLinks.forEach((loopLink) => {
		const links = JSON.parse(loopLink.getAttribute('data-iawmlf-post-links'));
		addLinks(links);
	});


	return allLinks;
}

/**
 * Create an instance of the IntersectionObserver for the links
 *
 * @returns {void}
 */
const linkObserver = new IntersectionObserver((links, observer) => {

	// Update the links
	allLinks = getRenderedLinks();

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
	// Iterate through all the archived links
	for (let i = 0; i < allLinks.length; i++) {
		let archivedLink = allLinks[i];

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
	// Get the href.
	const href = removeTrailingSlash(link.href);

	// Iterate through all the links
	for (let i = 0; i < pageLinks.length; i++) {
		let currentLink = pageLinks[i];

		// If the link is the same as the current link, add the data attributes
		if (removeTrailingSlash(currentLink.href) === href) {
			currentLink.setAttribute('data-iawmlf-archived-url', link.archived_href);
			currentLink.setAttribute('data-iawmlf-current-url', href);
			currentLink.setAttribute('data-iawmlf-archived-broken', link.broken);
			// If we have a last checked date, add it
			if (link.last_checked !== null && link.last_checked.date !== null) {
				currentLink.setAttribute('data-iawmlf-archived-last-checked', link.last_checked.date);
			}

			// If the link is broken, add a class and change the href
			if (link.broken && linkCheckSettings.fixerOption !== 'do_nothing') {
				currentLink.classList.add('iawmlf-broken-link');
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


	// If there is no archived link, return
	if (archived.archived_href === null || archived.archived_href === '') {
		return;
	}

	// IF the last checked is NULL or outside the delay, check the link
	if (archived.last_checked === null || daysSince(archived.last_checked.date) > linkDelay) {
		// Check the link
		verifyLink(link).then((result) => {

			// If the link can not be found, use the archived link data.
			if (result && result.link) {
				addDataAttributes(result.link);
				return;
			}

			// Use the default archived link data
			addDataAttributes(archived);
		});

		return;
	} else {
		// If the link has been checked, add the data attributes
		addDataAttributes(archived);
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

	const response = await fetch(settings.url, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': settings.nonce
		},
		body: JSON.stringify({ link: link })
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
