<?php

/**
 * Tests for the Post_Search_Ajax class.
 *
 * @since 1.5.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Ajax\Post_Search_Ajax
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Ajax;

use Internet_Archive\Wayback_Machine_Link_Fixer\Ajax\Post_Search_Ajax;

/**
 * Test_Post_Search_Ajax
 */
class Test_Post_Search_Ajax extends \WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// wp_send_json() ends with wp_die() during AJAX; throw instead so the test carries on.
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter(
			'wp_die_ajax_handler',
			static function () {
				return static function () {
					throw new \WPDieException( 'wp_die' );
				};
			}
		);
	}

	public function tear_down(): void {
		unset( $_POST['search'], $_POST['nonce'], $_POST['context'] );

		parent::tear_down();
	}

	/**
	 * Runs a search through the AJAX handler and returns its results.
	 *
	 * @param string $search The search term.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function search( string $search ): array {
		$_POST['search'] = $search;
		$_POST['nonce']  = wp_create_nonce( Post_Search_Ajax::NONCE );

		ob_start();
		try {
			( new Post_Search_Ajax() )->__invoke();
		} catch ( \WPDieException $e ) {
			// The response has been sent.
		}
		$response = json_decode( (string) ob_get_clean(), true );

		return $response['data']['results'] ?? array();
	}

	/**
	 * Maps each result's title to its status.
	 *
	 * @param array<int, array<string, mixed>> $results The search results.
	 *
	 * @return array<string, string>
	 */
	private function statuses_by_title( array $results ): array {
		return array_column( $results, 'status', 'title' );
	}

	/**
	 * @testdox Drafts and other unpublished posts are offered, with their status, so they can be excluded before they are published. (#384)
	 *
	 * @return void
	 */
	public function test_unpublished_posts_are_offered_with_their_status(): void {
		self::factory()->post->create( array( 'post_title' => 'Zebra published' ) );
		self::factory()->post->create(
			array(
				'post_title'  => 'Zebra draft',
				'post_status' => 'draft',
			)
		);
		self::factory()->post->create(
			array(
				'post_title'  => 'Zebra pending',
				'post_status' => 'pending',
			)
		);
		self::factory()->post->create(
			array(
				'post_title'  => 'Zebra scheduled',
				'post_status' => 'future',
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '+1 week' ) ),
			)
		);
		self::factory()->post->create(
			array(
				'post_title'  => 'Zebra private',
				'post_status' => 'private',
			)
		);

		$this->assertEqualsCanonicalizing(
			array(
				'Zebra published' => 'Published',
				'Zebra draft'     => 'Draft',
				'Zebra pending'   => 'Pending',
				'Zebra scheduled' => 'Scheduled',
				'Zebra private'   => 'Private',
			),
			$this->statuses_by_title( $this->search( 'Zebra' ) )
		);
	}

	/**
	 * @testdox A draft can be found by its ID. (#384)
	 *
	 * @return void
	 */
	public function test_a_draft_can_be_found_by_id(): void {
		// A fixed ID, as a single digit one is shorter than the 2 character minimum search.
		$draft_id = self::factory()->post->create(
			array(
				'post_status' => 'draft',
				'import_id'   => 424242,
			)
		);

		$results = $this->search( (string) $draft_id );

		$this->assertSame( $draft_id, $results[0]['id'] ?? null );
	}

	/**
	 * @testdox Trashed posts are not offered. (#384)
	 *
	 * @return void
	 */
	public function test_trashed_posts_are_not_offered(): void {
		self::factory()->post->create(
			array(
				'post_title'  => 'Zebra trashed',
				'post_status' => 'trash',
			)
		);

		$this->assertSame( array(), $this->search( 'Zebra' ) );
	}

	/**
	 * @testdox A title is not matched inside a stored entity, so "amp" does not offer "Tips &amp; Tricks". (#384)
	 *
	 * @return void
	 */
	public function test_titles_are_not_matched_inside_an_entity(): void {
		self::factory()->post->create( array( 'post_title' => 'Zebra Tips &amp; Tricks' ) );
		self::factory()->post->create( array( 'post_title' => 'Zebra summer camp' ) );

		$titles = array_column( $this->search( 'amp' ), 'title' );

		$this->assertContains( 'Zebra summer camp', $titles );
		$this->assertNotContains( 'Zebra Tips & Tricks', $titles );
		$this->assertNotContains( 'Zebra Tips &amp; Tricks', $titles );
	}

	/**
	 * @testdox Titles are sent as plain text, with stored entities decoded. (#384)
	 *
	 * @return void
	 */
	public function test_titles_are_sent_as_plain_text(): void {
		self::factory()->post->create( array( 'post_title' => 'Zebra Tips &amp; Tricks' ) );

		$this->assertSame( array( 'Zebra Tips & Tricks' ), array_column( $this->search( 'Zebra' ), 'title' ) );
	}
}
