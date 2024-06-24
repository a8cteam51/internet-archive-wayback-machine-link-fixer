<?php

/**
 * Test the Post Processor
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \WPCOMSpecialProjects\Wayback_Link_Fixer\Processor\Post_Processor
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests\Processor;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Processor\Post_Processor;

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
}
