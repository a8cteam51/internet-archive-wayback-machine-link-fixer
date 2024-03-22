/**
 * Handles the tooltip functionality.
 *
 * Are rendered dynamically and are removed when the mouse leaves the element.
 */
export const toolTip = {

	/**
	 * Setup the class.
	 */
	init: function () {
		this.dom();
		this.attachEventListeners();
	},

	/**
	 * Get the DOM elements.
	 */
	dom: function () {
		this.body = document.body;
		this.toolTips = document.body.querySelectorAll('.wlf-broken-link');
	},

	/**
	 * Attach event listeners.
	 */
	attachEventListeners: function () {
		// Event delegation
		document.body.addEventListener('mouseenter', (e) => {
			if (e.target.matches('.wlf-broken-link')) {
				this.toolTipInit(e);
			}
		}, true);
	},

	/**
	 * Initialize and render the tooltip.
	 * @param {object} e The Event object
	 * @returns
	 */
	toolTipInit: function (e) {
		const toolTipCurrent = e.currentTarget;
		const linkCurrent = e.srcElement;
		if (window.innerWidth < 200 || toolTipCurrent.classList.contains('tooltip__tigger--active')) {
			return;
		}

		toolTipCurrent.classList.add('tooltip__tigger--active');

		// Set the tooltip position.
		// @TODO this needs to be made dynamic based on the location of the tooltip to the edge of the screen.
		const toolTipPosition = toolTipCurrent.classList.contains('tooltip__trigger--right') ? 'tooltip--right' :
			toolTipCurrent.classList.contains('tooltip__trigger--left') ? 'tooltip--left' : 'tooltip--bottom';

		// Create the tooltip
		const output = document.createElement('div');
		output.className = `tooltip ${toolTipPosition}`;
		output.innerHTML = `<div class="tooltip__inner">${this.composeToolTipContents(linkCurrent)}</div><div class="tooltip__detail"></div>`;
		this.body.appendChild(output);


		toolTipCurrent.addEventListener('mousemove', (e) => this.tooltipLive(e, output, toolTipCurrent));
		output.classList.add('tooltip__trigger--dynamic--enabled');

		linkCurrent.addEventListener('mouseleave', () => this.tooltipDestroy(toolTipCurrent, output));
	},

	/**
	 * Destroy the tooltip.
	 * @param {HTMLElement} toolTipCurrent The current tooltip
	 * @param {HTMLElement} output The tooltip output
	 */
	tooltipDestroy: function (toolTipCurrent, output) {
		output.classList.add('removing');
		toolTipCurrent.classList.remove('tooltip__tigger--active');
		setTimeout(() => output.remove(), 500);
		toolTipCurrent.removeEventListener('mouseleave', this.tooltipDestroy);
		toolTipCurrent.removeEventListener('mousemove', this.tooltipLive);
	},

	/**
	 * Show the tooltip live.
	 * @param {event} e
	 * @param {HTMLElement} output The tooltip output
	 * @param {HTMLElement} current  The current tooltip
	 */
	tooltipLive: function (e, output, current) {
		this.positionToolTip({ left: e.clientX, top: e.clientY, output: output, current: current });
	},

	/**
	 * Position the tooltip based on the mouse position.
	 * @param {object} param0 Tool tip
	 */
	positionToolTip: function ({ left, top, current, output }) {
		const instanceDetails = output.getBoundingClientRect();
		const offset = current.getBoundingClientRect();

		const calculatedLeft = left ?? offset.left + offset.width / 2 - instanceDetails.width / 2;
		const calculatedTop = top ?? offset.top + offset.height;

		output.style.left = `${calculatedLeft}px`;
		output.style.top = `${calculatedTop}px`;
		output.classList.add('tooltip--visible');
	},

	/**
	 * Generate the tooltip contents.
	 * @param {HTMLElement} link
	 * @returns
	 */
	composeToolTipContents: function (link) {
		const currentLink = link.getAttribute('data-wlf-current-url');
		const archivedLink = link.getAttribute('data-wlf-archived-url');
		const lastChecked = link.getAttribute('data-wlf-archived-last-checked');

		return `${currentLink} is broken. <br>The archived link is ${archivedLink}<br>Last checked: ${lastChecked}`;
	},
};
