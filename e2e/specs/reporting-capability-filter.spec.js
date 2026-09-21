const { test, expect } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );
const path = require( 'path' );

/**
 * The iawmlf_reporting_page_capability filter must work when added on init. (S049)
 *
 * Dashboard_Page, Dashboard_Notifications and WP_Post_Table_Controller are all
 * initialized on plugins_loaded. Reading the capability at that moment happens
 * before any init-time filter has been added, so the filter is silently ignored
 * and the widened users never see the dashboard, the widget or the post column.
 *
 * A must-use plugin adds the filter on init, gated behind an option, so this
 * asserts both directions against a real WordPress: filter off means no access,
 * filter on means access. The "off" case is the control that keeps the "on"
 * case honest.
 */

const PLUGIN_DIR = 'wp-content/plugins/internet-archive-wayback-machine-link-fixer';
const REPO_ROOT = path.join( __dirname, '../..' );

const MENU = '#adminmenu';
const LINK_FIXER_MENU = 'Link Fixer';

function wpCli( command ) {
	return execSync(
		`npx wp-env run cli --env-cwd='${ PLUGIN_DIR }' -- ${ command }`,
		{ cwd: REPO_ROOT, encoding: 'utf8' }
	);
}

function seed() {
	const output = wpCli( 'wp eval-file e2e/fixtures/seed-capability-filter.php' );

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
		adminUrl: parse( 'ADMIN_URL' ),
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
	wpCli(
		`wp option update iawmlf_e2e_cap_filter_enabled ${ enabled ? '1' : '0' }`
	);
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
	const page = await context.newPage();

	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', seeded.user );
	await page.fill( '#user_pass', seeded.pass );
	await page.click( '#wp-submit' );

	await expect( page.locator( MENU ) ).toBeVisible();

	return { context, page };
}

test.describe( 'reporting capability filter', () => {
	let seeded;

	test.beforeAll( () => {
		seeded = seed();
	} );

	test.afterAll( () => {
		wpCli( 'wp eval-file e2e/fixtures/clean-capability-filter.php' );
	} );

	test( 'an editor sees no Link Fixer menu while the capability is not widened', async ( {
		browser,
	} ) => {
		setFilter( false );

		const { context, page } = await loginAsEditor( browser, seeded );

		await expect(
			page.locator( MENU ).getByRole( 'link', { name: LINK_FIXER_MENU } )
		).toHaveCount( 0 );

		await context.close();
	} );

	test( 'an editor sees the Link Fixer menu once an init-time filter widens the capability', async ( {
		browser,
	} ) => {
		setFilter( true );

		const { context, page } = await loginAsEditor( browser, seeded );

		await expect(
			page.locator( MENU ).getByRole( 'link', { name: LINK_FIXER_MENU } )
		).toHaveCount( 1 );

		await context.close();
	} );
} );
