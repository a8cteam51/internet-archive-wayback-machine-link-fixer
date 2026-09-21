const { test, expect } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );
const path = require( 'path' );

/**
 * The iawmlf_reporting_page_capability filter must work when added on init.
 *
 * Dashboard_Page, Dashboard_Notifications and WP_Post_Table_Controller are all
 * initialized on plugins_loaded. Reading the capability at that moment happens
 * before any init-time filter has been added, so the filter was silently
 * ignored and the widened users never saw the dashboard, the widget or the
 * post list column.
 *
 * iawmlf-e2e-capability.php adds the filter on init, gated behind an option, so
 * this asserts both directions against a real WordPress: filter off means no
 * access, filter on means access. The "off" case keeps the "on" case honest.
 *
 * Advanced Settings must stay closed either way, it renders the archive.org
 * credentials and is deliberately not on the reporting capability.
 */

const PLUGIN_DIR = 'wp-content/plugins/internet-archive-wayback-machine-link-fixer';
const REPO_ROOT = path.join( __dirname, '../..' );

const MENU = '#adminmenu';
const LINK_FIXER_MENU = 'Link Fixer';
const LINKS_MENU = 'Links';
const SETTINGS_MENU = 'Advanced Settings';

function wpCli( command ) {
	return execSync(
		`npx wp-env run cli --env-cwd='${ PLUGIN_DIR }' -- ${ command }`,
		{ cwd: REPO_ROOT, encoding: 'utf8' }
	);
}

function seed() {
	const output = wpCli( 'wp iawmlf-e2e-capability seed' );

	const parse = ( key ) => {
		const m = output.match( new RegExp( `^${ key }=(.+)$`, 'm' ) );
		if ( ! m ) {
			throw new Error(
				`Seeder did not print ${ key }. Output was:\n${ output }`
			);
		}
		return m[ 1 ].trim();
	};

	return {
		user: parse( 'EDITOR_USER' ),
		pass: parse( 'EDITOR_PASS' ),
	};
}

/**
 * Turn the init-time capability filter on or off.
 *
 * @param {boolean} enabled Whether the filter should apply.
 *
 * @return {void}
 */
function setFilter( enabled ) {
	wpCli( `wp iawmlf-e2e-capability filter ${ enabled ? '1' : '0' }` );
}

/**
 * Log in as the seeded editor in a context of its own.
 *
 * The suite's stored auth state is the administrator, who passes the default
 * manage_options and would prove nothing here.
 *
 * @param {import('@playwright/test').Browser} browser The browser.
 * @param {Object}                             seeded  The seeder output.
 *
 * @return {Promise<{context: import('@playwright/test').BrowserContext, page: import('@playwright/test').Page}>} The logged in editor.
 */
async function loginAsEditor( browser, seeded ) {
	const context = await browser.newContext( { storageState: undefined } );

	// Post the credentials rather than typing them. Both contexts share one
	// Chromium process, so after the first login its password manager autofills
	// the saved credential over anything typed into the form.
	const response = await context.request.post( '/wp-login.php', {
		form: {
			log: seeded.user,
			pwd: seeded.pass,
			rememberme: 'forever',
			'wp-submit': 'Log In',
			redirect_to: '/wp-admin/',
		},
	} );

	expect( response.ok(), 'The editor could not log in.' ).toBeTruthy();

	const page = await context.newPage();
	await page.goto( '/wp-admin/' );

	await expect( page.locator( MENU ) ).toBeVisible();

	return { context, page };
}

/**
 * How many times a named admin menu link appears for the logged in editor.
 *
 * @param {import('@playwright/test').Page} page The page.
 * @param {string}                          name The link text.
 *
 * @return {import('@playwright/test').Locator} The matching links.
 */
function menuLink( page, name ) {
	return page.locator( MENU ).getByRole( 'link', { name, exact: true } );
}

test.describe( 'reporting capability filter', () => {
	let seeded;

	test.beforeAll( () => {
		seeded = seed();
	} );

	test.afterAll( () => {
		wpCli( 'wp iawmlf-e2e-capability cleanup' );
	} );

	test( 'an editor sees no Link Fixer menu while the capability is not widened', async ( {
		browser,
	} ) => {
		setFilter( false );

		const { context, page } = await loginAsEditor( browser, seeded );

		await expect( menuLink( page, LINK_FIXER_MENU ) ).toHaveCount( 0 );

		await context.close();
	} );

	test( 'an init-time filter opens the reporting screens but never the settings page', async ( {
		browser,
	} ) => {
		setFilter( true );

		const { context, page } = await loginAsEditor( browser, seeded );

		await expect( menuLink( page, LINK_FIXER_MENU ) ).toHaveCount( 1 );
		await expect( menuLink( page, LINKS_MENU ) ).toHaveCount( 1 );

		// Advanced Settings renders the archive.org credentials, so it stays on
		// manage_options and must not follow the reporting capability.
		await expect( menuLink( page, SETTINGS_MENU ) ).toHaveCount( 0 );

		await context.close();
	} );
} );
