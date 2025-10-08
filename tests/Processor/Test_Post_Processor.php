<?php

/**
 * Test the Post Processor
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Processor\Post_Processor
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Processor;

use Internet_Archive\Wayback_Machine_Link_Fixer\Processor\Post_Processor;

/**
 * Test_Post_Processor
 */
class Test_Post_Processor extends \WP_UnitTestCase {

	/**
	 * @testdox It should be possible to process a post and have the links added to the database and returned ready for updating as meta.
	 *
	 * @return void
	 */
	public function test_can_process_post_and_return_links(): void {
		// Create a post with some links.
		$post_id = \WP_UnitTestCase_Base::factory()->post->create();

		// Add some content to the post.
		$content = 'This is a post with a link to <a href="https://from.post/content">example</a><br>And another link to <a href="https://from.post/content_twice">example</a>';

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			)
		);

		$processor = new Post_Processor( $post_id );
		$links     = $processor->process();

		// Both should have IDs and valid URLs.
		$this->assertCount( 2, $links );
		$this->assertIsInt( $links[0]->get_id() );
		$this->assertEquals( 'https://from.post/content', $links[0]->get_href() );
		$this->assertIsInt( $links[1]->get_id() );
		$this->assertEquals( 'https://from.post/content_twice', $links[1]->get_href() );
	}

	/**
	 * @testdox Only links which start with http or https should be added to the list of links. Mailto and other protocols should be ignored.
	 *
	 * @return void
	 */
	public function test_only_http_and_https_links_are_added(): void {
		// Create a post with some links.
		$post_id = \WP_UnitTestCase_Base::factory()->post->create();

		// Add some content to the post.
		$content = join(
			' ',
			array(
				'http'       => '<a href="http://from.post/content">example</a>',
				'https'      => '<a href="https://from.post/content_twice">example</a>',
				'mailto'     => '<a href="mailto:something@them.com">example</a>',
				'ftp'        => '<a href="ftp://from.post/content">example</a>',
				'javascript' => '<a href="javascript:alert(\'hello\')">example</a>',
				'sms'        => '<a href="sms:1234567890">example</a>',
				'data'       => '<a href="data:base64,SGVsbG8gV29ybGQ=">example</a>',
				'geo'        => '<a href="geo:37.786971,-122.399677;u=35">example</a>',
				'file'       => '<a href="file:///etc/passwd">example</a>',
				'tel'        => '<a href="tel:+1234567890">example</a>',

			)
		);

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			)
		);

		$processor = new Post_Processor( $post_id );
		$links     = $processor->process();

		// Only the http and https links should be added.
		$this->assertCount( 2, $links );
		$this->assertEquals( 'http://from.post/content', $links[0]->get_href() );
		$this->assertEquals( 'https://from.post/content_twice', $links[1]->get_href() );
	}

}
