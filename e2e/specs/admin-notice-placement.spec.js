const { test, expect } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );
const path = require( 'path' );

/**
 * Admin notices must be printed inside the page's `.wrap` container.
 *
 * Both the Links page and the Advanced Settings page used to print their notice
 * before opening `.wrap`, so it landed outside the container that WordPress
 * styles and positions notices within.
 *
 * These assertions run against the server-rendered HTML rather than the live
 * DOM. Admin scripts can relocate `.notice` elements after load, so a DOM
 * position would not prove where the page actually printed the notice.
 */

const PLUGIN_DIR = 'wp-content/plugins/internet-archive-wayback-machine-link-fixer';
const REPO_ROOT = path.join( __dirname, '../..' );

const SETTINGS_PATH = '/wp-admin/admin.php?page=iawmlf_settings';
const LINKS_PATH = '/wp-admin/admin.php?page=iawmlf-links';

function wpCli( command ) {
	return execSync(
		`npx wp-env run cli --env-cwd='${ PLUGIN_DIR }' -- ${ command }`,
		{ cwd: REPO_ROOT, encoding: 'utf8' }
	);
}

function seed() {
	const output = wpCli( 'wp eval-file e2e/fixtures/seed-notice-placement.php' );

	const parse = ( key ) => {
		const m = output.match( new RegExp( `^${ key }=(.+)$`, 'm' ) );
		if ( ! m ) {
			throw new Error( `Seeder did not print ${ key }. Output was:\n${ output }` );
		}
		return m[ 1 ].trim();
	};

	return {
		action: parse( 'NOTICE_ACTION' ),
		key: parse( 'NOTICE_KEY' ),
		message: parse( 'NOTICE_MESSAGE' ),
	};
}

/**
 * Assert a notice containing `needle` is printed after `.wrap` opens.
 *
 * @param {string} html   The server-rendered page HTML.
 * @param {string} needle Text unique to the notice under test.
 */
function expectNoticeInsideWrap( html, needle ) {
	const wrapAt = html.indexOf( '<div class="wrap">' );
	const noticeAt = html.indexOf( needle );

	expect( wrapAt, 'the page should render a .wrap container' ).toBeGreaterThan( -1 );
	expect( noticeAt, `the page should render a notice containing "${ needle }"` ).toBeGreaterThan( -1 );
	expect( noticeAt, 'the notice should be printed inside .wrap, not before it' ).toBeGreaterThan( wrapAt );
}

test.describe( 'admin notice placement', () => {
	let seeded;

	test.beforeAll( () => {
		seeded = seed();
	} );

	test.afterAll( () => {
		wpCli( 'wp eval-file e2e/fixtures/clean-notice-placement.php' );
	} );

	test( 'the settings page prints its notice inside .wrap', async ( { page } ) => {
		const response = await page.request.get( SETTINGS_PATH );
		expect( response.ok() ).toBe( true );

		expectNoticeInsideWrap( await response.text(), 'unauthenticated mode' );
	} );

	test( 'the links page prints its notice inside .wrap', async ( { page } ) => {
		const url = `${ LINKS_PATH }&iawmlf_completed_action=${ seeded.action }&iawmlf_notification=${ seeded.key }`;

		const response = await page.request.get( url );
		expect( response.ok() ).toBe( true );

		expectNoticeInsideWrap( await response.text(), seeded.message );
	} );
} );
