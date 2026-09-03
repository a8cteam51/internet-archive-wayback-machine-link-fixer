const { test, expect } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );
const path = require( 'path' );

/**
 * The "Last Archived" column on the posts list screen (#351).
 *
 * The column reports Settings::OWN_LINK_LAST_PROCESSED, the timestamp written
 * once a post's own permalink has been snapshotted.
 *
 * It is registered on the post types that get auto archived, offered as a
 * Screen Options checkbox, and hidden until the user ticks that box. Once
 * ticked the preference is saved per user, so the assertions below re-load the
 * page to prove the choice survived rather than trusting the live DOM.
 */

const PLUGIN_DIR = 'wp-content/plugins/internet-archive-wayback-machine-link-fixer';
const REPO_ROOT = path.join( __dirname, '../..' );

const LIST_PATH = '/wp-admin/edit.php?post_type=post';
const COLUMN_KEY = 'wayback_archived';

function wpCli( command ) {
	return execSync(
		`npx wp-env run cli --env-cwd='${ PLUGIN_DIR }' -- ${ command }`,
		{ cwd: REPO_ROOT, encoding: 'utf8' }
	);
}

function seed() {
	const output = wpCli( 'wp eval-file e2e/fixtures/seed-archived-column.php' );

	const parse = ( key ) => {
		const m = output.match( new RegExp( `^${ key }=(.+)$`, 'm' ) );
		if ( ! m ) {
			throw new Error( `Seeder did not print ${ key }. Output was:\n${ output }` );
		}
		return m[ 1 ].trim();
	};

	return {
		archivedId: parse( 'ARCHIVED_POST_ID' ),
		neverId: parse( 'NEVER_POST_ID' ),
		excludedId: parse( 'EXCLUDED_POST_ID' ),
		expectedDate: parse( 'EXPECTED_DATE' ),
	};
}

// Each test sets the admin's column preference itself, so neither depends on
// the other having run first.
const clearColumnPreference = () =>
	wpCli( 'wp eval-file e2e/fixtures/hide-archived-column.php' );
const showColumn = () =>
	wpCli( 'wp eval-file e2e/fixtures/show-archived-column.php' );

/**
 * The archived cell for a given post row.
 *
 * @param {import('@playwright/test').Page} page    The page.
 * @param {string}                          postId  The post ID.
 *
 * @return {import('@playwright/test').Locator} The cell.
 */
const archivedCell = ( page, postId ) =>
	page.locator( `#post-${ postId } td.${ COLUMN_KEY }` );

test.describe( 'posts list last archived column', () => {
	let seeded;

	test.beforeAll( () => {
		seeded = seed();
	} );

	test.afterAll( () => {
		wpCli( 'wp eval-file e2e/fixtures/clean-archived-column.php' );
	} );

	test( 'the column is offered in Screen Options and hidden until it is ticked', async ( {
		page,
	} ) => {
		clearColumnPreference();
		await page.goto( LIST_PATH );

		const header = page.locator( `th#${ COLUMN_KEY }` );
		const toggle = page.locator( `#${ COLUMN_KEY }-hide` );

		// The column is registered, so the header and the Screen Options checkbox both exist.
		await expect( header ).toHaveCount( 1 );
		await expect( toggle ).toHaveCount( 1 );
		await expect( toggle ).toHaveAttribute( 'value', COLUMN_KEY );

		// Unticked and the header carries the hidden class: hidden by default.
		await expect( toggle ).not.toBeChecked();
		await expect( header ).toHaveClass( /\bhidden\b/ );

		// Tick it in Screen Options.
		await page.locator( '#show-settings-link' ).click();
		await expect( toggle ).toBeVisible();
		await toggle.check();

		// The preference is saved per user, so it must survive a reload.
		await page.goto( LIST_PATH );
		await expect( page.locator( `#${ COLUMN_KEY }-hide` ) ).toBeChecked();
		await expect( page.locator( `th#${ COLUMN_KEY }` ) ).not.toHaveClass(
			/\bhidden\b/
		);
		await expect( page.locator( `th#${ COLUMN_KEY }` ) ).toHaveText(
			'Last Archived'
		);
	} );

	test( 'each row reports its own archive state', async ( { page } ) => {
		showColumn();
		await page.goto( LIST_PATH );

		// A post with an archive date shows it, in the site's date and time format.
		await expect( archivedCell( page, seeded.archivedId ) ).toContainText(
			seeded.expectedDate
		);
		await expect(
			archivedCell( page, seeded.archivedId ).locator( 'time' )
		).toHaveAttribute( 'datetime', '2025-01-02T03:04:05+00:00' );

		// A post that has never been archived says so, with no date.
		await expect( archivedCell( page, seeded.neverId ) ).toContainText(
			'Never archived'
		);
		await expect(
			archivedCell( page, seeded.neverId ).locator( 'time' )
		).toHaveCount( 0 );

		// A post excluded from auto archiving says so, even though it has a stored date.
		const excluded = archivedCell( page, seeded.excludedId );
		await expect( excluded ).toContainText( 'Excluded post' );
		await expect( excluded ).not.toContainText( seeded.expectedDate );
		await expect( excluded.locator( 'a' ) ).toHaveAttribute(
			'href',
			/page=iawmlf_settings/
		);
	} );
} );
