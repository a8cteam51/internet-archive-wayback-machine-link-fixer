const { test, expect } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );
const path = require( 'path' );

/**
 * Issue #346: the link data span must not change how the content around it is
 * formatted.
 *
 * The span used to be appended inside the block content, before wpautop ran.
 * wpautop then saw the content's trailing newline as no longer trailing and
 * turned it into a visible <br />. It is now appended on the_content at
 * priority 12, after wpautop, so there is nothing left for wpautop to react to.
 *
 * Only Classic Editor content can show this: do_blocks() unhooks wpautop for
 * anything containing block delimiters.
 */

const PLUGIN_DIR = 'wp-content/plugins/internet-archive-wayback-machine-link-fixer';
const REPO_ROOT = path.join( __dirname, '../..' );

const SPAN = '.__iawmlf-post-loop-links';

function wpCli( command ) {
	return execSync(
		`npx wp-env run cli --env-cwd='${ PLUGIN_DIR }' -- ${ command }`,
		{ cwd: REPO_ROOT, encoding: 'utf8' }
	);
}

function seed() {
	const output = wpCli(
		'wp eval-file e2e/fixtures/seed-trailing-newline.php'
	);

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
		trailingNewline: {
			url: parse( 'TRAILING_NEWLINE_URL' ),
			marker: parse( 'TRAILING_NEWLINE_MARKER' ),
		},
		blankLines: {
			url: parse( 'BLANK_LINES_URL' ),
			marker: parse( 'BLANK_LINES_MARKER' ),
		},
		blockLevel: {
			url: parse( 'BLOCK_LEVEL_URL' ),
			marker: parse( 'BLOCK_LEVEL_MARKER' ),
		},
		authoredBreak: {
			url: parse( 'AUTHORED_BREAK_URL' ),
			marker: parse( 'AUTHORED_BREAK_MARKER' ),
		},
	};
}

/**
 * The rendered HTML of the element that holds the post's final marker word.
 *
 * Locating by the marker rather than by a theme class keeps this independent of
 * whichever theme wp-env is running.
 *
 * @param {import('@playwright/test').Page} page     The page.
 * @param {string}                          marker   The marker word.
 * @param {string}                          selector The tag holding the marker.
 *
 * @return {Promise<string>} The element's inner HTML.
 */
async function markerElementHtml( page, marker, selector = 'p' ) {
	const element = page
		.locator( selector )
		.filter( { hasText: marker } )
		.last();

	await expect( element ).toBeVisible();

	return element.innerHTML();
}

test.describe( 'trailing newline rendering (#346)', () => {
	let seeded;

	test.beforeAll( () => {
		seeded = seed();
	} );

	test.afterAll( () => {
		wpCli( 'wp eval-file e2e/fixtures/clean-trailing-newline.php' );
	} );

	test( 'content ending in a newline does not gain a line break', async ( {
		page,
	} ) => {
		await page.goto( seeded.trailingNewline.url );

		// The span is on the page, so the case under test is actually exercised.
		await expect( page.locator( SPAN ) ).toHaveCount( 1 );

		const html = await markerElementHtml(
			page,
			seeded.trailingNewline.marker
		);
		expect( html ).not.toContain( '<br>' );
	} );

	test( 'content ending in blank lines does not gain a line break or an empty paragraph', async ( {
		page,
	} ) => {
		await page.goto( seeded.blankLines.url );

		await expect( page.locator( SPAN ) ).toHaveCount( 1 );

		const html = await markerElementHtml( page, seeded.blankLines.marker );
		expect( html ).not.toContain( '<br>' );

		// wpautop used to wrap the bare span in a paragraph of its own.
		await expect( page.locator( `p > ${ SPAN }` ) ).toHaveCount( 0 );
	} );

	test( 'content ending in a block level element keeps the span out of a paragraph', async ( {
		page,
	} ) => {
		await page.goto( seeded.blockLevel.url );

		await expect( page.locator( SPAN ) ).toHaveCount( 1 );

		// This shape's marker is the last list item, not a paragraph.
		const html = await markerElementHtml(
			page,
			seeded.blockLevel.marker,
			'li'
		);
		expect( html ).not.toContain( '<br>' );

		await expect( page.locator( `p > ${ SPAN }` ) ).toHaveCount( 0 );
	} );

	test( "a line break the author wrote is still rendered", async ( {
		page,
	} ) => {
		await page.goto( seeded.authoredBreak.url );

		await expect( page.locator( SPAN ) ).toHaveCount( 1 );

		// The paragraph holds both lines, so read the marker's parent.
		const paragraph = page
			.locator( 'p' )
			.filter( { hasText: seeded.authoredBreak.marker } )
			.last();

		await expect( paragraph ).toHaveCount( 1 );
		expect( await paragraph.innerHTML() ).toContain( '<br>' );
	} );
} );
