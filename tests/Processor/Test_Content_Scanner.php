<?php

/**
 * Test for the post scanner class.
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Processor\Content_Scanner
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Processor;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Processor\Content_Scanner;
use Internet_Archive\Wayback_Machine_Link_Fixer\WP_Post\WP_Post_Controller;

/**
 * Test_Content_Scanner
 */
class Test_Content_Scanner extends \WP_UnitTestCase {

	/**
	 * @testdox It should be possible to pass in some content and get back an array of links.
	 *
	 * @return void
	 */
	public function test_can_get_links_from_content(): void {
		$content = 'This is a post with a link to <a href="https://not-from.post/content">example</a><br>And another link to <a href="https://not-from.post/content_twice">example</a>';
		$scanner = new Content_Scanner( $content );
		$links   = $scanner->scan()->get_links();

		$this->assertCount( 2, $links );
		$this->assertSame( 'https://not-from.post/content', $links[0] );
		$this->assertSame( 'https://not-from.post/content_twice', $links[1] );
	}

	/**
	 * @testdox Any invalid links should be excluded from the list.
	 *
	 * @return void
	 */
	public function test_invalid_links_are_excluded(): void {
		$content = 'This is a post with a link to <a href="https://not-from.post/content">example</a><br>And a broken link <a href="https://from_invalid/content_broken">example</a>';
		$scanner = new Content_Scanner( $content );
		$links   = $scanner->scan()->get_links();

		$this->assertCount( 1, $links );
	}

	/**
	 * @testdox It should be possible to create an instance of a the content scanner with a post ID.
	 */
	public function test_can_create_instance_with_post_id(): void {
		$post_id = \WP_UnitTestCase_Base::factory()->post->create();

		// Create the content scanner.
		$content = 'This is a post with a link to <a href="https://from.post/content">example</a><br>And another link to <a href="https://from.post/content_twice">example</a>';
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			)
		);

		$scanner = Content_Scanner::for_post( $post_id );
		$links   = $scanner->scan()->get_links();

		$this->assertCount( 2, $links );
		$this->assertSame( 'https://from.post/content', $links[0] );
		$this->assertSame( 'https://from.post/content_twice', $links[1] );
	}

	/**
	 * @testdox International URLs should be scanned in their encoded form, not silently dropped. (S126)
	 *
	 * @dataProvider data_provider_international_urls
	 *
	 * @param string $href     The href as written in the content.
	 * @param string $expected The link expected in the scan results.
	 *
	 * @return void
	 */
	public function test_international_urls_are_scanned( string $href, string $expected ): void {
		$scanner = new Content_Scanner( sprintf( 'A link to <a href="%s">example</a>', $href ) );
		$links   = array_values( $scanner->scan()->get_links() );

		$this->assertCount( 1, $links, "The link {$href} should have been scanned." );
		$this->assertSame( $expected, $links[0] );
	}

	/**
	 * Data provider for test_international_urls_are_scanned.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function data_provider_international_urls(): array {
		return array(
			'accented path'          => array( 'https://not-from.post/café', 'https://not-from.post/caf%C3%A9' ),
			'accented query'         => array( 'https://not-from.post/page?q=café', 'https://not-from.post/page?q=caf%C3%A9' ),
			'cyrillic path'          => array( 'https://not-from.post/привет', 'https://not-from.post/%D0%BF%D1%80%D0%B8%D0%B2%D0%B5%D1%82' ),
			'idn host'               => array( 'https://例え.jp/path', 'https://xn--r8jz45g.jp/path' ),
			'idn host accented path' => array( 'https://例え.jp/café', 'https://xn--r8jz45g.jp/caf%C3%A9' ),
			'space in path'          => array( 'https://not-from.post/a b', 'https://not-from.post/a%20b' ),
		);
	}

	/**
	 * @testdox Plain ASCII URLs must be left exactly as written - the encoding fallback must not touch them. (S126)
	 *
	 * @dataProvider data_provider_ascii_urls_untouched
	 *
	 * @param string $href The href as written in the content.
	 *
	 * @return void
	 */
	public function test_ascii_urls_are_untouched( string $href ): void {
		$scanner = new Content_Scanner( sprintf( 'A link to <a href="%s">example</a>', $href ) );
		$links   = array_values( $scanner->scan()->get_links() );

		$this->assertCount( 1, $links );
		$this->assertSame( $href, $links[0], 'A valid ASCII URL must not be rewritten.' );
	}

	/**
	 * Data provider for test_ascii_urls_are_untouched.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function data_provider_ascii_urls_untouched(): array {
		return array(
			'plain'             => array( 'https://not-from.post/content' ),
			'trailing slash'    => array( 'https://not-from.post/content/' ),
			'query string'      => array( 'https://not-from.post/page?a=1&b=2' ),
			'already encoded'   => array( 'https://not-from.post/caf%C3%A9' ),
			'fragment'          => array( 'https://not-from.post/page#section' ),
			'port'              => array( 'https://not-from.post:8080/content' ),
		);
	}

	/**
	 * @testdox A malformed URL must still be rejected, even after the encoding fallback. (S126)
	 *
	 * @dataProvider data_provider_still_invalid_urls
	 *
	 * @param string $href The href as written in the content.
	 *
	 * @return void
	 */
	public function test_malformed_urls_are_still_rejected( string $href ): void {
		$scanner = new Content_Scanner( sprintf( 'A link to <a href="%s">example</a>', $href ) );

		$this->assertCount( 0, $scanner->scan()->get_links(), "The malformed URL {$href} should not have been scanned." );
	}

	/**
	 * Data provider for test_malformed_urls_are_still_rejected.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function data_provider_still_invalid_urls(): array {
		return array(
			'underscore host' => array( 'https://from_invalid/content_broken' ),
			'no host'         => array( 'https:///just-a-path' ),
			'not http'        => array( 'ftp://not-from.post/file' ),
			'relative'        => array( '/relative/path' ),
		);
	}

	/**
	 * @testdox Links output by a shortcode should be scanned, whether the shortcode returns or echoes them. (S126)
	 *
	 * @return void
	 */
	public function test_shortcode_links_are_scanned(): void {
		add_shortcode( 'iawmlf_returns_link', fn() => '<a href="https://not-from.post/returned">returned</a>' );
		add_shortcode(
			'iawmlf_echoes_link',
			function () {
				echo '<a href="https://not-from.post/echoed">echoed</a>';
				return '';
			}
		);

		$post_id = self::factory()->post->create(
			array( 'post_content' => 'Before [iawmlf_returns_link] middle [iawmlf_echoes_link] after.' )
		);

		$links = Content_Scanner::for_post( $post_id )->scan()->get_links();

		remove_shortcode( 'iawmlf_returns_link' );
		remove_shortcode( 'iawmlf_echoes_link' );

		$this->assertContains( 'https://not-from.post/returned', $links );
		$this->assertContains( 'https://not-from.post/echoed', $links );
	}

	/**
	 * @testdox A shortcode that throws must not break the scan - it falls back to the raw content. (S126)
	 *
	 * @return void
	 */
	public function test_throwing_shortcode_falls_back_to_raw_content(): void {
		add_shortcode(
			'iawmlf_throws',
			function () {
				throw new \RuntimeException( 'Third party shortcode blew up.' );
			}
		);

		$post_id = self::factory()->post->create(
			array( 'post_content' => 'A <a href="https://not-from.post/survives">link</a> and [iawmlf_throws].' )
		);

		$links = Content_Scanner::for_post( $post_id )->scan()->get_links();

		remove_shortcode( 'iawmlf_throws' );

		$this->assertContains( 'https://not-from.post/survives', $links, 'The raw content must still be scanned.' );
		$this->assertFalse( Content_Scanner::is_rendering(), 'The rendering flag must be reset even when the render throws.' );
	}

	/**
	 * @testdox Links rendered by a dynamic block should be scanned. (S126)
	 *
	 * @return void
	 */
	public function test_dynamic_block_links_are_scanned(): void {
		register_block_type(
			'iawmlf-test/dynamic',
			array(
				'render_callback' => fn() => '<a href="https://not-from.post/from-block">block</a>',
			)
		);

		$post_id = self::factory()->post->create(
			array( 'post_content' => '<!-- wp:iawmlf-test/dynamic /-->' )
		);

		$links = Content_Scanner::for_post( $post_id )->scan()->get_links();

		unregister_block_type( 'iawmlf-test/dynamic' );

		$this->assertContains( 'https://not-from.post/from-block', $links );
	}

	/**
	 * @testdox Raw links in the content must still be scanned once blocks and shortcodes are rendered. (S126)
	 *
	 * @return void
	 */
	public function test_raw_links_still_scanned_after_rendering(): void {
		$post_id = self::factory()->post->create(
			array( 'post_content' => '<!-- wp:paragraph --><p>A <a href="https://not-from.post/raw">raw link</a>.</p><!-- /wp:paragraph -->' )
		);

		$links = Content_Scanner::for_post( $post_id )->scan()->get_links();

		$this->assertSame( array( 'https://not-from.post/raw' ), array_values( $links ) );
	}

	/**
	 * @testdox The link payload span must not be injected while a scan renders blocks. (S126)
	 *
	 * @return void
	 */
	public function test_own_render_block_output_is_suppressed_during_a_scan(): void {
		update_option( Settings::FIXER_OPTION, Settings::FIXER_OPTION_REPLACE_LINK );

		$post_id = self::factory()->post->create(
			array( 'post_content' => '<!-- wp:paragraph --><p>A <a href="https://not-from.post/payload">link</a>.</p><!-- /wp:paragraph -->' )
		);

		// Store links against the post, so the payload span would have something to render.
		( new WP_Post_Controller() )->process_links_in_content( $post_id );

		$rendered_during_scan = null;
		add_filter(
			'iawmlf_scan_content',
			function ( string $content ) use ( &$rendered_during_scan ): string {
				$rendered_during_scan = $content;
				return $content;
			}
		);

		Content_Scanner::for_post( $post_id )->scan()->get_links();

		remove_all_filters( 'iawmlf_scan_content' );

		$this->assertIsString( $rendered_during_scan );
		$this->assertStringNotContainsString( '__iawmlf-post-loop-links', $rendered_during_scan );

		// Outside a scan the span is still added.
		$this->assertFalse( Content_Scanner::is_rendering() );
		$GLOBALS['post'] = get_post( $post_id );
		$this->assertStringContainsString( '__iawmlf-post-loop-links', do_blocks( get_post_field( 'post_content', $post_id ) ) );
		unset( $GLOBALS['post'] );
	}

	/**
	 * @testdox The iawmlf_scan_content filter should be able to widen what is scanned. (S126)
	 *
	 * @return void
	 */
	public function test_scan_content_filter_can_widen_the_scan(): void {
		$post_id = self::factory()->post->create(
			array( 'post_content' => 'No links here.' )
		);

		add_filter(
			'iawmlf_scan_content',
			fn( string $content ): string => $content . '<a href="https://not-from.post/added-by-filter">added</a>'
		);

		$links = Content_Scanner::for_post( $post_id )->scan()->get_links();

		remove_all_filters( 'iawmlf_scan_content' );

		$this->assertContains( 'https://not-from.post/added-by-filter', $links );
	}

	/**
	 * @testdox Any duplicate links should be excluded from the list.
	 *
	 * @return void
	 */
	public function test_duplicate_links_are_excluded(): void {
		$content = 'This is a post with a link to <a href="https://not-from.post/content">example</a><br>And another link to <a href="https://not-from.post/content">example</a>';
		$scanner = new Content_Scanner( $content );
		$links   = $scanner->scan()->get_links();

		$this->assertCount( 1, $links );
	}

	/**
	 * @testdox Invalid HTML should not throw any exceptions.
	 *
	 * @return void
	 */
	public function test_invalid_html_does_not_throw_exceptions(): void {
		$content = '<header>Some text here</header>';
		$scanner = new Content_Scanner( $content );
		$links   = $scanner->scan()->get_links();

		$this->assertCount( 0, $links );
	}

	/**
	 * @testdox Ensure that any wayback links are excluded from the list of links found in a post.
	 *
	 * @see https://github.com/a8cteam51/wayback-link-fixer/issues/148
	 *
	 * @return void
	 */
	public function test_wayback_links_are_excluded(): void {
		// Content with 4 links, http, https from web.archive.org, and a wayback link.
		$content = 'This is a post with a link to <a href="https://not-from.post/content">example</a><br>And another link to <a href="https://not-from.post/content_twice">example</a><br>And a wayback link to <a href="https://web.archive.org/web/20231001000000/https://from.post/content">wayback example</a><br>And a http link to <a href="http://web.archive.org/web/20231001000000/https://from.post/content">http example</a>';
		$scanner = new Content_Scanner( $content );
		$links   = $scanner->scan()->get_links();
		$this->assertCount( 2, $links );
	}

	/**
	 * @testdox Ensure links from the current site should be excluded from the list of links found in a post.
	 *
	 * @dataProvider data_provider_exclude_links_from_current_site
	 *
	 * @param string $content        The content to scan.
	 * @param array  $expected_links The expected links after scanning.
	 *
	 * @return void
	 */
	public function test_exclude_links_from_current_site( string $content, array $expected_links ): void {
		$scanner = new Content_Scanner( $content );
		$links   = $scanner->scan()->get_links();

		// Check the count.
		$this->assertCount( count( $expected_links ), $links, 'The number of links found does not match the expected count.' );

		// Itearate through the expected links and check they are in the links array.
		foreach ( $expected_links as $expected_link ) {
			$this->assertContains( $expected_link, $links, "The expected link {$expected_link} was not found in the links array." );
		}
	}

	/**
	 * Data provider for test_exclude_links_from_current_site.
	 *
	 * @return array
	 */
	public static function data_provider_exclude_links_from_current_site(): array {
		// example.org is the current so,

		// exclude links from https://example.org and http://example.org
		// allow links from https://sub.example.org and http://sub.example.org
		// allow links from https://not-from.post and http://not-from.post

		return array(
			'Links from current site'              => array(
				'content'        => 'This is a post with a link to <a href="https://example.org/content">example</a><br>And another link to <a href="http://example.org/content_twice">example</a>',
				'expected_links' => array(),
			),
			'Links from subdomain of current site' => array(
				'content'        => 'This is a post with a link to <a href="https://sub.example.org/content">example</a><br>And another link to <a href="http://sub.example.org/content_twice">example</a>',
				'expected_links' => array( 'https://sub.example.org/content', 'http://sub.example.org/content_twice' ),
			),
			'Links from different site'            => array(
				'content'        => 'This is a post with a link to <a href="https://not-from.post/content">example</a><br>And another link to <a href="http://not-from.post/content_twice">example</a>',
				'expected_links' => array( 'https://not-from.post/content', 'http://not-from.post/content_twice' ),
			),
		);
	}
}
