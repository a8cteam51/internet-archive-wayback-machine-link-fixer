const { defineConfig, devices } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );
const path = require( 'path' );
require( 'dotenv' ).config( { path: path.join( __dirname, '.env' ) } );

/**
 * Resolve the WordPress base URL.
 *
 * wp-env assigns a different port per project/machine (and it can change),
 * so a hardcoded fallback is wrong everywhere except by luck. Order of
 * precedence:
 *   1. WP_BASE_URL env / e2e/.env  (explicit override — CI sets this)
 *   2. Ask the running wp-env what URL it's actually on (`wp option get home`)
 *   3. wp-env's documented default, only if discovery fails
 */
function resolveBaseURL() {
	if ( process.env.WP_BASE_URL ) {
		return process.env.WP_BASE_URL;
	}
	try {
		const url = execSync(
			"npx wp-env run cli --env-cwd='wp-content/plugins/internet-archive-wayback-machine-link-fixer' -- wp option get home",
			{ cwd: path.join( __dirname, '..' ), stdio: [ 'ignore', 'pipe', 'ignore' ] }
		)
			.toString()
			.trim()
			.split( '\n' )
			.pop()
			.trim();
		if ( /^https?:\/\//.test( url ) ) {
			return url;
		}
	} catch ( e ) {
		// wp-env not up yet / cli unavailable — fall through to default.
	}
	return 'http://localhost:8888';
}

const BASE_URL = resolveBaseURL();
// Propagate so spec files (which read process.env.WP_BASE_URL directly)
// get the same discovered URL, not their own hardcoded fallback.
process.env.WP_BASE_URL = BASE_URL;

// Where @wordpress/e2e-test-utils-playwright (global-setup) writes the
// authenticated storage state, and where the chromium project loads it.
const STORAGE_STATE_PATH = path.join(
	__dirname,
	'artifacts/storage-states/admin.json'
);
process.env.STORAGE_STATE_PATH = STORAGE_STATE_PATH;
require( 'fs' ).mkdirSync( path.dirname( STORAGE_STATE_PATH ), {
	recursive: true,
} );

module.exports = defineConfig( {
	testDir: __dirname,
	outputDir: path.join( __dirname, '../test-results' ),
	fullyParallel: false,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: 1,
	timeout: 60 * 1000,
	reporter: [
		[ 'html', { outputFolder: path.join( __dirname, '../playwright-report' ), open: 'never' } ],
		[ 'list' ],
	],
	use: {
		baseURL: BASE_URL,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'retain-on-failure',
		actionTimeout: 15 * 1000,
		navigationTimeout: 30 * 1000,
	},
	projects: [
		{
			name: 'setup',
			testMatch: /global-setup\.js$/,
		},
		{
			name: 'chromium',
			testMatch: /specs\/.*\.spec\.js$/,
			use: {
				...devices[ 'Desktop Chrome' ],
				storageState: STORAGE_STATE_PATH,
				launchOptions: {
					slowMo: process.env.SLOWMO ? parseInt( process.env.SLOWMO, 10 ) : 0,
				},
			},
			dependencies: [ 'setup' ],
		},
	],
} );
