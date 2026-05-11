# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: check-on-load.spec.js >> Front-end check-on-load flow >> mutates the DOM based on mock responses for each link
- Location: tests/e2e/specs/check-on-load.spec.js:21:2

# Error details

```
SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
```

# Test source

```ts
  1  | const { test, expect } = require( '@playwright/test' );
  2  | 
  3  | const FIXTURE_BASE = 'https://example.com';
  4  | 
  5  | const linkSpecs = [
  6  | 	{ slug: 'valid-archived',       hasArchive: true,  broken: false },
  7  | 	{ slug: 'valid-no-archive',     hasArchive: false, broken: false },
  8  | 	{ slug: 'broken-archived',      hasArchive: true,  broken: true  },
  9  | 	{ slug: 'broken-no-archive',    hasArchive: false, broken: true  },
  10 | 	{ slug: 'flipping-archived',    hasArchive: true,  broken: true,  flips: true },
  11 | 	{ slug: 'flipping-no-archive',  hasArchive: false, broken: true,  flips: true },
  12 | 	{ slug: 'valid-archived-2',     hasArchive: true,  broken: false },
  13 | 	{ slug: 'valid-no-archive-2',   hasArchive: false, broken: false },
  14 | 	{ slug: 'broken-archived-2',    hasArchive: true,  broken: true  },
  15 | 	{ slug: 'recovering-archived',  hasArchive: true,  broken: false, recovers: true },
  16 | ];
  17 | 
  18 | const urlFor = ( slug ) => `${ FIXTURE_BASE }/${ slug }`;
  19 | 
  20 | test.describe( 'Front-end check-on-load flow', () => {
  21 | 	test( 'mutates the DOM based on mock responses for each link', async ( { page, request } ) => {
  22 | 		await request.post( '/wp-json/iawmlf-test/v1/reset' );
  23 | 
  24 | 		const responses = {};
  25 | 		for ( const link of linkSpecs ) {
  26 | 			// `flips` and existing-broken URLs return 404; everything else returns 200.
  27 | 			// `recovering-archived` is 200 even though it has prior 404s — that's the recovery.
  28 | 			responses[ urlFor( link.slug ) ] = link.broken && ! link.recovers ? 404 : 200;
  29 | 		}
  30 | 
  31 | 		await request.post( '/wp-json/iawmlf-test/v1/mock', {
  32 | 			data: { responses },
  33 | 		} );
  34 | 
  35 | 		const seedRes = await request.post( '/wp-json/iawmlf-test/v1/seed' );
> 36 | 		const seed    = await seedRes.json();
     |                   ^ SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON
  37 | 		expect( seed.post_url ).toBeTruthy();
  38 | 		expect( seed.links ).toHaveLength( linkSpecs.length );
  39 | 
  40 | 		await page.goto( seed.post_url );
  41 | 
  42 | 		// Wait until every fixture anchor has been touched by the JS round trip.
  43 | 		await expect.poll(
  44 | 			async () => page.evaluate( ( base ) => {
  45 | 				const anchors = Array.from( document.querySelectorAll( 'a' ) ).filter( ( a ) => {
  46 | 					const original = a.getAttribute( 'data-iawmlf-current-url' ) || a.getAttribute( 'href' ) || '';
  47 | 					return original.indexOf( base + '/' ) !== -1;
  48 | 				} );
  49 | 				if ( anchors.length === 0 ) {
  50 | 					return -1;
  51 | 				}
  52 | 				return anchors.filter( ( a ) => a.hasAttribute( 'data-iawmlf-archived-last-checked' ) ).length;
  53 | 			}, FIXTURE_BASE ),
  54 | 			{ timeout: 15000, intervals: [ 250, 500, 1000 ] }
  55 | 		).toBe( linkSpecs.length );
  56 | 
  57 | 		for ( const spec of linkSpecs ) {
  58 | 			const anchor = page
  59 | 				.locator( `a[data-iawmlf-current-url$="/${ spec.slug }/"], a[data-iawmlf-current-url$="/${ spec.slug }"]` )
  60 | 				.first();
  61 | 
  62 | 			await expect( anchor, `${ spec.slug } should be in the DOM` ).toHaveCount( 1 );
  63 | 
  64 | 			if ( spec.broken ) {
  65 | 				await expect( anchor, `${ spec.slug }: expected iawmlf-broken-link class` )
  66 | 					.toHaveClass( /iawmlf-broken-link/ );
  67 | 			} else {
  68 | 				await expect( anchor, `${ spec.slug }: should NOT have iawmlf-broken-link class` )
  69 | 					.not.toHaveClass( /iawmlf-broken-link/ );
  70 | 			}
  71 | 
  72 | 			if ( spec.broken && spec.hasArchive ) {
  73 | 				const href = await anchor.getAttribute( 'href' );
  74 | 				expect( href, `${ spec.slug }: href should be rewritten to archive` )
  75 | 					.toMatch( /web\.archive\.org/ );
  76 | 			}
  77 | 
  78 | 			if ( spec.broken && ! spec.hasArchive ) {
  79 | 				const href = await anchor.getAttribute( 'href' );
  80 | 				expect( href, `${ spec.slug }: href should NOT be rewritten` )
  81 | 					.toMatch( new RegExp( `/${ spec.slug }$|/${ spec.slug }/$` ) );
  82 | 			}
  83 | 		}
  84 | 	} );
  85 | } );
  86 | 
```