import { createHooks } from '@wordpress/hooks';
import domReady from '@wordpress/dom-ready';

window.wpcomsp_wayback-link-fixer = window.wpcomsp_wayback-link-fixer || {};
window.wpcomsp_wayback-link-fixer.hooks = createHooks();

domReady( () => {
	window.wpcomsp_wayback-link-fixer.hooks.doAction( 'editor.ready' );
} );
