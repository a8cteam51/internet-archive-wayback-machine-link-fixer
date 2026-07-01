const { test, expect } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );
const path = require( 'path' );

/**
 * Link-details exclusion priority — the "unwind" test.
 *
 * One link is excluded by all three layers at once, then peeled back one at a time. The "Link
 * Exclusion" panel must walk the priority order as each layer is removed:
 *
 *   all three on   -> per-link flag wins the message ("currently excluded"), checkbox read-only
 *   remove flag    -> settings list  ("excluded by your exclusion settings list"), read-only
 *   remove settings-> built-in list  ("excluded by the built-in exclusion list"), read-only
 *   remove built-in-> nothing        ("Excluding this link will stop it…"), editable checkbox
 *
 * Layers are toggled via the iawmlf-e2e-exclusion mu-plugin (per-link flag + option-gated
 * built-in filter) and the normal settings option.
 */

const PLUGIN_DIR = 'wp-content/plugins/internet-archive-wayback-machine-link-fixer';
const REPO_ROOT = path.join( __dirname, '../..' );

function wpCli( command ) {
	return execSync(
		`npx wp-env run cli --env-cwd='${ PLUGIN_DIR }' -- ${ command }`,
		{ cwd: REPO_ROOT, encoding: 'utf8' }
	);
}

function seedLink() {
	const output = wpCli( 'wp iawmlf-e2e-exclusion seed' );
	const match = output.match( /^LINK_ID=(\d+)$/m );
	if ( ! match ) {
		throw new Error( `Seeder did not print LINK_ID. Output was:\n${ output }` );
	}
	return match[ 1 ];
}

const setPerLinkFlag = ( linkId, on ) => wpCli( `wp iawmlf-e2e-exclusion flag ${ linkId } ${ on ? 1 : 0 }` );
const setSettingsList = ( jsonArray ) => wpCli( `wp option update iawmlf_link_exclusions '${ jsonArray }' --format=json` );
const setBuiltInList = ( on ) => ( on
	? wpCli( 'wp option update iawmlf_e2e_builtin_exclusion 1' )
	: wpCli( 'wp option delete iawmlf_e2e_builtin_exclusion' ) );

const PATTERN = '["*iawmlf-e2e-panel-link*"]';

test.describe( 'link details exclusion priority', () => {
	let linkId;
	let detailsPath;

	test.beforeAll( () => {
		linkId = seedLink();
		detailsPath = `/wp-admin/admin.php?page=iawmlf-links&iawmlf_link_id=${ linkId }`;
	} );

	test.afterAll( () => {
		setPerLinkFlag( linkId, false );
		setSettingsList( '[]' );
		setBuiltInList( false );
		wpCli( 'wp iawmlf-e2e-exclusion cleanup' );
	} );

	test( 'all three on, then peeled back one at a time, walk the priority order', async ( { page } ) => {
		const panel = page.locator( '#iawmlf_link_exclusion .inside' );
		const toggle = page.locator( '#iawmlf_toggle_exclusion' );

		// 1) All three layers on: the per-link flag wins the message; checkbox is read-only.
		setPerLinkFlag( linkId, true );
		setSettingsList( PATTERN );
		setBuiltInList( true );
		await page.goto( detailsPath );
		await expect( panel ).toContainText( 'currently excluded' );
		await expect( toggle ).toBeDisabled();

		// 2) Remove the per-link flag: the settings list now owns it.
		setPerLinkFlag( linkId, false );
		await page.goto( detailsPath );
		await expect( panel ).toContainText( 'excluded by your exclusion settings list' );
		await expect( toggle ).toBeDisabled();

		// 3) Remove the settings list: the built-in list now owns it.
		setSettingsList( '[]' );
		await page.goto( detailsPath );
		await expect( panel ).toContainText( 'excluded by the built-in exclusion list' );
		await expect( toggle ).toBeDisabled();

		// 4) Remove the built-in list: nothing excludes it — the checkbox is editable again.
		setBuiltInList( false );
		await page.goto( detailsPath );
		await expect( panel ).toContainText( 'Excluding this link will stop it' );
		await expect( toggle ).toBeEnabled();
		await expect( toggle ).toHaveAttribute( 'name', 'iawmlf_exclude_link' );
	} );
} );
