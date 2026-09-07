const { test, expect } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );
const path = require( 'path' );

/**
 * Every post shown in a query loop must carry its own link data.
 *
 * wp_localize_script only hands the front-end script the CURRENT post's links,
 * so every other post in a loop depends entirely on its own
 * `__iawmlf-post-loop-links` span. This guards that: three seeded posts, two
 * distinct links each, all six URLs different.
 *
 * For each post the spec asserts its span holds BOTH of that post's links and
 * NONE of the other four, which is the property the loop rendering depends on.
 */

const PLUGIN_DIR = 'wp-content/plugins/internet-archive-wayback-machine-link-fixer';
const REPO_ROOT = path.join( __dirname, '../..' );

const SPAN = '.__iawmlf-post-loop-links';

// Must match iawmlf_e2e_query_loop_posts() in query-loop-shared.php.
const EXPECTED = {
	A: [
		'https://example.com/iawmlf-e2e-loop-alpha-one',
		'https://example.com/iawmlf-e2e-loop-alpha-two',
	],
	B: [
		'https://example.com/iawmlf-e2e-loop-bravo-one',
		'https://example.com/iawmlf-e2e-loop-bravo-two',
	],
	C: [
		'https://example.com/iawmlf-e2e-loop-charlie-one',
		'https://example.com/iawmlf-e2e-loop-charlie-two',
	],
};

function wpCli( command ) {
	return execSync(
		`npx wp-env run cli --env-cwd='${ PLUGIN_DIR }' -- ${ command }`,
		{ cwd: REPO_ROOT, encoding: 'utf8' }
	);
}

function seed() {
	const output = wpCli( 'wp eval-file e2e/fixtures/seed-query-loop-links.php' );

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
		homeUrl: parse( 'HOME_URL' ),
		ids: {
			A: parse( 'POST_A_ID' ),
			B: parse( 'POST_B_ID' ),
			C: parse( 'POST_C_ID' ),
		},
	};
}

/**
 * The hrefs listed in a post's own payload span within the loop.
 *
 * @param {import('@playwright/test').Page} page   The page.
 * @param {string}                          postId The post ID.
 *
 * @return {Promise<Array<string>>} The hrefs in that post's span.
 */
async function hrefsInPostSpan( page, postId ) {
	// The post-<id> class token is exact, so post-12 never matches post-120.
	const span = page.locator( `.post-${ postId } ${ SPAN }` );

	await expect( span ).toHaveCount( 1 );

	const json = await span.getAttribute( 'data-iawmlf-links' );

	return JSON.parse( json ).map( ( link ) => link.href );
}

test.describe( 'query loop link data', () => {
	let seeded;

	test.beforeAll( () => {
		seeded = seed();
	} );

	test.afterAll( () => {
		wpCli( 'wp eval-file e2e/fixtures/clean-query-loop-links.php' );
	} );

	test( 'each post in the loop carries all of its own links and none of the others', async ( {
		page,
	} ) => {
		await page.goto( seeded.homeUrl );

		for ( const label of Object.keys( EXPECTED ) ) {
			const hrefs = await hrefsInPostSpan( page, seeded.ids[ label ] );

			// Every one of this post's links is present.
			for ( const url of EXPECTED[ label ] ) {
				expect(
					hrefs,
					`post ${ label } span should list ${ url }`
				).toContain( url );
			}

			// And nothing belonging to the other two posts leaked in.
			const foreign = Object.keys( EXPECTED )
				.filter( ( other ) => other !== label )
				.flatMap( ( other ) => EXPECTED[ other ] );

			for ( const url of foreign ) {
				expect(
					hrefs,
					`post ${ label } span should not list ${ url }`
				).not.toContain( url );
			}
		}
	} );
} );
