import { createHooks } from '@wordpress/hooks';
import domReady from '@wordpress/dom-ready';

window.wpcomsp_scaffold = window.wpcomsp_scaffold || {};
window.wpcomsp_scaffold.hooks = createHooks();

domReady( () => {
	window.wpcomsp_scaffold.hooks.doAction( 'editor.ready' );
} );
