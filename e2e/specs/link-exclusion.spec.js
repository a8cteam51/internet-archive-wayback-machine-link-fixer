const { test, expect } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );
const path = require( 'path' );

/**
 * Regression: a link marked excluded via the per-link "Exclude this link"
 * toggle in the admin (the `excluded` column on the iawmlf links table)
 * must NOT be replaced/decorated by the front-end JS.
 *
 * The post contains two anchors:
 *
 *   - Sentinel link (excluded=0): the JS will rewrite this href to the
 *     archived URL and add the `iawmlf-broken-link` class. We wait for
 *     that to land — it proves the front-end checker has run on the page.
 *
 *   - Excluded link (excluded=1): once the sentinel has been rewritten,
 *     we assert this anchor was left alone (href unchanged, no class,
 *     no data-iawmlf-archived-url attribute).
 *
 * If `Link_Repository::get_links_for_post()` correctly filtered out
 * per-link excluded entries, the excluded link wouldn't even appear in
 * the localised `iawmlfArchivedLinks.links` data — so the JS can't
 * touch it. Right now it does — that's the bug.
 */

const PLUGIN_DIR = 'wp-content/plugins/internet-archive-wayback-machine-link-fixer';

function runSeeder() {
	const output = execSync(
		`npx wp-env run cli --env-cwd='${ PLUGIN_DIR }' -- wp eval-file e2e/fixtures/seed-exclusion-bug.php`,
		{ cwd: path.join( __dirname, '../..' ), encoding: 'utf8' }
	);

	const parse = ( key ) => {
		const m = output.match( new RegExp( `^${ key }=(.+)$`, 'm' ) );
		if ( ! m ) {
			throw new Error( `Seeder did not print ${ key }. Output was:\n${ output }` );
		}
		return m[ 1 ].trim();
	};

	return {
		postUrl:     parse( 'POST_URL' ),
		excludedUrl: parse( 'EXCLUDED_URL' ),
		sentinelUrl: parse( 'SENTINEL_URL' ),
	};
}

test.describe( 'per-link exclusion', () => {
	let seeded;

	test.beforeAll( () => {
		seeded = runSeeder();
	} );

	test( 'excluded link is not rewritten on the frontend', async ( { page } ) => {
		await page.goto( seeded.postUrl );

		const excludedLink = page.locator( 'a', { hasText: 'excluded link' } );
		const sentinelLink = page.locator( 'a', { hasText: 'sentinel link' } );

		await expect( excludedLink ).toBeVisible();
		await expect( sentinelLink ).toBeVisible();

		// Wait for the front-end checker to have processed the sentinel.
		// The script adds data-iawmlf-archived-url when it touches a link.
		await expect( sentinelLink ).toHaveAttribute( 'data-iawmlf-archived-url', /.+/ );
		await expect( sentinelLink ).toHaveClass( /iawmlf-broken-link/ );

		// Excluded link should have been left entirely alone.
		await expect( excludedLink ).toHaveAttribute( 'href', seeded.excludedUrl );
		await expect( excludedLink ).not.toHaveClass( /iawmlf-broken-link/ );
		await expect( excludedLink ).not.toHaveAttribute( 'data-iawmlf-archived-url', /.+/ );
	} );
} );
