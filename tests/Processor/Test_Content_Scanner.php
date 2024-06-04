<?php

/**
 * Test for the post scanner class.
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \WPCOMSpecialProjects\Wayback_Link_Fixer\Processor\Content_Scanner
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests\Processor;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Processor\Content_Scanner;

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
}
