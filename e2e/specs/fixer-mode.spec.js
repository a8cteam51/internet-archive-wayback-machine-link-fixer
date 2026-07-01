const { test, expect } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );
const path = require( 'path' );

/**
 * The Fixer Option controls what the front-end checker does with a broken link:
 *
 *   - replace_link : rewrite the href to the archived version + add `iawmlf-broken-link`.
 *   - check_only   : still check the link (records the result), but never touch the href.
 *   - do_nothing   : don't even enqueue the checker; the link is left completely alone.
 *
 * One broken link (with an archived version) is seeded once, then the mode is toggled
 * and the same page re-loaded to assert each contract.
 */

const PLUGIN_DIR = 'wp-content/plugins/internet-archive-wayback-machine-link-fixer';

function wpCli( cmd ) {
	return execSync(
		`npx wp-env run cli --env-cwd='${ PLUGIN_DIR }' -- wp ${ cmd }`,
		{ cwd: path.join( __dirname, '../..' ), encoding: 'utf8' }
	);
}

function parse( output, key ) {
	const m = output.match( new RegExp( `^${ key }=(.+)$`, 'm' ) );
	if ( ! m ) {
		throw new Error( `Seeder did not print ${ key }. Output was:\n${ output }` );
	}
	return m[ 1 ].trim();
}

function seed() {
	const output = wpCli( 'iawmlf-e2e-fixer-mode seed' );
	return {
		postUrl:     parse( output, 'POST_URL' ),
		linkUrl:     parse( output, 'LINK_URL' ),
		archivedUrl: parse( output, 'ARCHIVED_URL' ),
	};
}

function setMode( mode ) {
	wpCli( `iawmlf-e2e-fixer-mode mode ${ mode }` );
}

test.describe( 'fixer option modes', () => {
	let seeded;

	test.beforeAll( () => {
		seeded = seed();
	} );

	test.afterAll( () => {
		wpCli( 'iawmlf-e2e-fixer-mode cleanup' );
	} );

	test( 'replace_link rewrites the broken link to the archived version', async ( { page } ) => {
		setMode( 'replace_link' );
		await page.goto( seeded.postUrl );

		const link = page.locator( 'a', { hasText: 'fixer mode link' } );
		await expect( link ).toBeVisible();

		// The checker ran and, in replace mode, swapped the href + flagged it broken.
		// The plugin renders archived links on the web-wp.archive.org host, so match the
		// archive path host-agnostically rather than a specific subdomain.
		await expect( link ).toHaveAttribute( 'data-iawmlf-archived-url', /.+/ );
		await expect( link ).toHaveClass( /iawmlf-broken-link/ );
		await expect( link ).toHaveAttribute( 'href', /archive\.org\/web/ );
	} );

	test( 'check_only checks the link but leaves the href untouched', async ( { page } ) => {
		setMode( 'check_only' );
		await page.goto( seeded.postUrl );

		const link = page.locator( 'a', { hasText: 'fixer mode link' } );
		await expect( link ).toBeVisible();

		// The checker still ran (proves the check is recorded)...
		await expect( link ).toHaveAttribute( 'data-iawmlf-archived-url', /.+/ );

		// ...but the visitor-facing link is unchanged: no class, original href.
		await expect( link ).not.toHaveClass( /iawmlf-broken-link/ );
		await expect( link ).toHaveAttribute( 'href', seeded.linkUrl );
	} );

	test( 'do_nothing does not enqueue the checker and leaves the link alone', async ( { page } ) => {
		setMode( 'do_nothing' );
		await page.goto( seeded.postUrl );

		const link = page.locator( 'a', { hasText: 'fixer mode link' } );
		await expect( link ).toBeVisible();

		// The front-end checker script is never enqueued in this mode.
		await expect(
			page.locator( 'script#iawm-link-fixer-front-link-checker-js' )
		).toHaveCount( 0 );

		// So the link is completely untouched.
		await expect( link ).not.toHaveClass( /iawmlf-broken-link/ );
		await expect( link ).not.toHaveAttribute( 'data-iawmlf-archived-url', /.+/ );
		await expect( link ).toHaveAttribute( 'href', seeded.linkUrl );
	} );
} );
