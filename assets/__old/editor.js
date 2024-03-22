import { createHooks } from '@wordpress/hooks';
import domReady from '@wordpress/dom-ready';

window.wpcomsp_wayback_link_fixer = window.wpcomsp_wayback_link_fixer || {};
window.wpcomsp_wayback_link_fixer.hooks = createHooks();

domReady( () => {
	window.wpcomsp_wayback_link_fixer.hooks.doAction( 'editor.ready' );
} );
