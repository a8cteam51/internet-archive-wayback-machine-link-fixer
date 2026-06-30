const { test, expect } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );
const path = require( 'path' );

/**
 * Regression: whatever status the link checker returns, a working link must not
 * be marked broken on the front end. Forces each status via the e2e mu-plugin.
 */

const PLUGIN_DIR = 'wp-content/plugins/internet-archive-wayback-machine-link-fixer';

// 200 baseline + the non-2xx codes seen during the DDoS outage.
const CODES = [ 200, 403, 404, 500, 502, 503 ];

function wpCli( cmd ) {
	return execSync(
		`npx wp-env run cli --env-cwd='${ PLUGIN_DIR }' -- wp ${ cmd }`,
		{ cwd: path.join( __dirname, '../..' ), encoding: 'utf8' }
	);
}

function seed( code ) {
	const output = wpCli( `iawmlf-e2e-non200 seed ${ code }` );
	const parse = ( key ) => {
		const m = output.match( new RegExp( `^${ key }=(.+)$`, 'm' ) );
		if ( ! m ) {
			throw new Error( `Seeder did not print ${ key }. Output was:\n${ output }` );
		}
		return m[ 1 ].trim();
	};
	return { postUrl: parse( 'POST_URL' ), linkUrl: parse( 'LINK_URL' ) };
}

test.describe( 'link checker status codes', () => {
	test.afterEach( () => {
		wpCli( 'iawmlf-e2e-non200 cleanup' );
	} );

	for ( const code of CODES ) {
		test( `link checker returning ${ code } does not mark the link broken`, async ( { page } ) => {
			const seeded = seed( code );

			const checkResponse = page.waitForResponse( ( response ) =>
				response.url().includes( 'iawmlf/v1/link-check' )
			);

			await page.goto( seeded.postUrl );

			const checkedLink = page.locator( 'a', { hasText: 'checked link' } );
			await expect( checkedLink ).toBeVisible();

			// 200 is a real verdict; any non-200 makes the route return a 500.
			const response = await checkResponse;
			expect( response.status() ).toBe( 200 === code ? 200 : 500 );

			// The checker processed the link (proves the JS ran)...
			await expect( checkedLink ).toHaveAttribute( 'data-iawmlf-archived-url', /.+/ );

			// ...but the link must never be marked broken or have its href rewritten.
			await expect( checkedLink ).not.toHaveClass( /iawmlf-broken-link/ );
			await expect( checkedLink ).toHaveAttribute( 'href', seeded.linkUrl );
		} );
	}
} );
