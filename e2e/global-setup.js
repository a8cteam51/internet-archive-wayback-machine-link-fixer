const { test: setup } = require( '@wordpress/e2e-test-utils-playwright' );

/**
 * Authenticate once via the REST API and persist the storage state.
 *
 * Uses @wordpress/e2e-test-utils-playwright (the WordPress-official helper,
 * same as the Pink-Crab Jukebox reference) instead of driving the wp-login
 * form in a browser. REST auth is resilient to a freshly-started wp-env
 * still warming up — the manual form-fill raced WP readiness and errored
 * with chrome-error://chromewebdata/ on a cold start.
 *
 * Reads WP_BASE_URL / WP_USERNAME / WP_PASSWORD and writes the storage
 * state to STORAGE_STATE_PATH (both set in playwright.config.js).
 */
setup( 'authenticate', async ( { requestUtils } ) => {
	await requestUtils.setupRest();
} );
