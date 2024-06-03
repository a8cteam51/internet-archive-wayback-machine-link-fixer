<?php

/**
 * Unit tests for the Link model class.
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository
 *
 * @group Link
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests\Link;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;

/**
 * Test_Link_Repository
 */
class Test_Link_Repository extends \WP_UnitTestCase {

	/**
	 * Link Repository instance.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * The current link ids from being inserted.
	 *
	 * @var array<integer>
	 */
	private $link_ids = array();

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->link_repository = new Link_Repository();
		$this->link_ids        = array();

		// Empty the table.
		$GLOBALS['wpdb']->query( 'TRUNCATE TABLE ' . Settings::get_link_table_name() );
	}

	/**
	 * @testdox It should be possible to add a link to the repository.
	 *
	 * @return void
	 */
	public function test_can_add_link(): void {
		$link = new Link( 'https://example.com' );
		$link = $this->link_repository->upsert( $link );

		$this->assertNotNull( $link->get_id() );

		$wpdb       = $GLOBALS['wpdb'];
		$table_name = Settings::get_link_table_name();

		$found_link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$link->get_id()
			)
		);

		$this->assertSame( $link->get_id(), (int) $found_link->id );
		$this->assertSame( $link->get_href(), $found_link->url );
	}

	/**
	 * @testdox It should be possible to find a link by its URL.
	 *
	 * @return void
	 */
	public function test_can_find_link_by_url(): void {
		$link = new Link( 'https://example.com' );

		$link = $this->link_repository->upsert( $link );

		// Check the link is in the repository.
		$found_link = $this->link_repository->find_by_url( 'https://example.com' );

		// Ignore deprecation warning.
		$this->assertSame( $link->get_id(), $found_link->get_id() );
		$this->assertSame( $link->get_href(), $found_link->get_href() );
	}

	/**
	 * @testdox It should be possible to find a link by its ID.
	 *
	 * @return void
	 */
	public function test_can_find_link_by_id(): void {
		$link = new Link( 'https://example.com' );

		$link = $this->link_repository->upsert( $link );

		// Check the link is in the repository.
		$found_link = $this->link_repository->find_by_id( $link->get_id() );

		// Ignore deprecation warning.
		$this->assertSame( $link->get_id(), $found_link->get_id() );
		$this->assertSame( $link->get_href(), $found_link->get_href() );
	}

	/**
	 * @testdox When searching for a link by its id, if no link is found, null should be returned.
	 *
	 * @return void
	 */
	public function test_find_by_id_returns_null_if_no_link_found(): void {
		$found_link = $this->link_repository->find_by_id( 999999999 );

		$this->assertNull( $found_link );
	}

	/**
	 * @testdox It should be possible to find or create a link by its URL.
	 *
	 * @return void
	 */
	public function test_can_find_or_create_link_by_url(): void {
		// Create a link.
		$link = $this->link_repository->find_or_create( 'https://example.com' );

		$this->assertNotNull( $link->get_id() );
		$this->assertSame( 'https://example.com', $link->get_href() );

		// Call again to ensure we get the same link.
		$found_link = $this->link_repository->find_or_create( 'https://example.com' );

		$this->assertSame( $link->get_id(), $found_link->get_id() );
		$this->assertSame( $link->get_href(), $found_link->get_href() );
	}

	/**
	 * @testdox It should be possible to query links and set a limit and offset for them.
	 *
	 * @return void
	 */
	public function test_can_query_links_with_limit_and_offset(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links( 8, 2 );

		$this->assertCount( 2, $queried_links );

		// We should get the last two links (ordered by Date DESC)
		$expected = array(
			'https://foo.com', // 2021-02-01 00:00:00
			'https://jump.uk', // 2020-06-01 00:00:00
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertContains( $link->get_href(), $expected );
		}
	}

	/**
	 * @testdox It should be possible to order the links by the first check date.
	 *
	 * @return void
	 */
	public function test_can_order_links_by_first_check_date_dec(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links( 2, 1, array(), array(), array(), Link_Repository::ORDER_DATE_DESC );

		// We should have the first two links
		$expected = array(
			'https://walnut.org',  // 2023-08-01 00:00:00
			'https://example.com', // 2022-01-01 00:00:00
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertContains( $link->get_href(), $expected );
		}
	}

	/**
	 * @testdox It should be possible to order the links by the first check date ASC.
	 *
	 * @return void
	 */
	public function test_can_order_links_by_first_check_date_asc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links( 2, 1, array(), array(), array(), Link_Repository::ORDER_DATE_ASC );

		// We should have the first two links
		$expected = array(
			'https://jump.uk', // 2020-06-01 00:00:00
			'https://foo.com', // 2021-02-01 00:00:00
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertContains( $link->get_href(), $expected );
		}
	}

	/**
	 * @testdox It should be possible to order the by the id ASC
	 *
	 * @return void
	 */
	public function test_can_order_links_by_id_asc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links( 2, 1, array(), array(), array(), Link_Repository::ORDER_ID_ASC );

		// We should have the first two from data provider
		$expected = array(
			'https://example.com',
			'https://foo.com',
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertContains( $link->get_href(), $expected );
		}
	}

	/**
	 * @testdox It should be possible to order the by the id DESC
	 *
	 * @return void
	 */
	public function test_can_order_links_by_id_desc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links( 2, 1, array(), array(),  array(), Link_Repository::ORDER_ID_DESC );

		// We should have the first two links
		$expected = array(
			'https://zebra.zoo',
			'https://acorn.retro',
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertContains( $link->get_href(), $expected );
		}
	}

	/**
	 * @testdox It should be possible to order the by the url ASC
	 *
	 * @return void
	 */
	public function test_can_order_links_by_url_asc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links( 2, 1, array(), array(),  array(), Link_Repository::ORDER_URL_ASC );

		// We should have the first two links
		$expected = array(
			'https://banana.fruit',
			'https://acorn.retro',
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertContains( $link->get_href(), $expected );
		}
	}

	/**
	 * @testdox It should be possible to filter links based on if they are broken or not.
	 *
	 * @return void
	 */
	public function test_can_filter_links_by_broken(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			10,
			1,
			array( Link_Repository::LINK_STATUS_BROKEN ), // Statuses
		);

		$this->assertCount( 3, $queried_links );
	}

	/**
	 * @testdox It should be possible to filter links based on if they are not broken.
	 *
	 * @return void
	 */
	public function test_can_filter_links_by_not_broken(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			10,
			1,
			array( Link_Repository::LINK_STATUS_OK ), // Statuses
		);

		$this->assertCount( 7, $queried_links );
	}

	/**
	 * @testdox It should be possible to filter links based on if they are broken or not.
	 *
	 * @return void
	 */
	public function test_can_filter_links_by_broken_and_not_broken(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			10,
			1,
			array(
				Link_Repository::LINK_STATUS_BROKEN,
				Link_Repository::LINK_STATUS_OK,
			), // Statuses
		);

		$this->assertCount( 10, $queried_links );
	}

	/**
	 * @testdox It should be possible to order the by the url DESC
	 *
	 * @return void
	 */
	public function test_can_order_links_by_url_desc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links( 2, 1, array(), array(),  array(), Link_Repository::ORDER_URL_DESC );

		// We should have the first two links
		$expected = array(
			'https://zebra.zoo',
			'https://walnut.org',
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertContains( $link->get_href(), $expected );
		}
	}



	## HELPER FUNCTIONS ##

	/**
	 * Populate the database with some links.
	 *
	 * @return void
	 */
	public function populate_database(): void {
		$links = $this->link_provider();

		foreach ( $links as $link ) {
			$inserted         = $this->link_repository->upsert( $link );
			$this->link_ids[] = $inserted->get_id();
		}
	}

	/**
	 * @testdox It should be possible to get all links for a post, from its post meta
	 *
	 * @return void
	 */
	public function test_can_get_links_for_post(): void {
		// Create the links.
		$links = array(
			new Link( 'https://example.com' ),
			new Link( 'https://foo.com' ),
		);

		$link_ids = array();

		// Insert the links.
		foreach ( $links as $link ) {
			$inserted   = $this->link_repository->upsert( $link );
			$link_ids[] = $inserted->get_id();
		}

		// Add as post meta.
		update_post_meta( 1, Settings::LINK_META_KEY, $link_ids );

		// Get the links.
		$link_collection = $this->link_repository->get_links_for_post( 1 );

		// Check links have the same ids.
		$this->assertCount( 2, $link_collection->get_links() );
		$this->assertSame(
			$link_ids,
			array_map(
				function ( $link ) {
					return $link->get_id();
				},
				$link_collection->get_links()
			)
		);
	}

	/**
	 * @testdox It should be possible to query based on link ids.
	 *
	 * @return void
	 */
	public function test_can_query_links_by_ids(): void {
	 // Insert the default links.
	 $this->populate_database();

	 // Get first 5 link ids.
	 $link_ids = array_slice( $this->link_ids, 0, 5 );

	 // Query the links.
	 $queried_links = $this->link_repository->query_links( 10, 1, array(), $link_ids );

	 $this->assertCount( 5, $queried_links );

	 foreach ( $queried_links as $index => $link ) {
	     $this->assertContains( $link->get_id(), $link_ids );
	 }
	}

	/**
	 * @testdox It should be possible to filter the links based on the yyyy-mm date format.
	 *
	 * @return void
	 */
	public function test_can_filter_links_by_date(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links( 10, 1, array(), array(),  array(), Link_Repository::ORDER_DATE_DESC, null, '2022-01' );

		$this->assertCount( 1, $queried_links );

		// We should get back this site only.
		$expected = array(
			'https://example.com', // Matches the last date of 3 checks {"date":"2022-01-01 00:00:00","http_code":200}
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertContains( $link->get_href(), $expected );
		}
	}

	/**
	 * @testdox It should be possible to get a list of all posts that a link is used on.
	 *
	 * @return void
	 */
	public function test_can_get_posts_for_link(): void {
		// Create the link.
		$link = new Link( 'https://example.com' );

		// Insert the link.
		$link = $this->link_repository->upsert( $link );

		// Create 2 mock posts.
		$post_ids = array( 1, 2 );

		// Add the link to the posts.
		foreach ( $post_ids as $post_id ) {
			// Add as post meta.
			update_post_meta( $post_id, Settings::LINK_META_KEY, array( $link->get_id() ) );
		}

		// Try to get the posts for the link.
		$posts = $this->link_repository->get_post_ids_from_link_id( $link->get_id() );

		$this->assertCount( 2, $posts );

		// Check the post ids are the same.
		$this->assertSame( $post_ids, $posts );

		// Clean up.
		foreach ( $post_ids as $post_id ) {
			delete_post_meta( $post_id, Settings::LINK_META_KEY );
		}
	}

	/**
	 * @testdox It should be possible to search links using partial URLs.
	 *
	 * @return void
	 */
	public function test_can_search_links_by_partial_url(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links( 10, 1, array(), array(),  array(), Link_Repository::ORDER_DATE_DESC, 'glynn' );

		$this->assertCount( 1, $queried_links );

		// We should get back this site only.
		$expected = array(
			'https://glynn.com', // Matches the last date of 3 checks {"date":"2022-01-01 00:00:00","http_code":200}
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertContains( $link->get_href(), $expected );
		}
	}

	/**
	 * Link Data Provider. (10)
	 *
	 * @return array<Link>
	 */
	public function link_provider(): array {
		return array(
			( new Link( 'https://example.com' ) ) // Checks are use using the last date for sorting!
			->add_check(
				200,
				'2000-01-01 00:00:00'
			)->add_check(
				404,
				'2001-01-01 00:00:00'
			)->add_check(
				200,
				'2022-01-01 00:00:00'
			)
			->set_broken(),
			( new Link( 'https://foo.com' ) )
			->add_check(
				404,
				'2021-02-01 00:00:00'
			),
			( new Link( 'https://glynn.com' ) )
			->add_check(
				200,
				'2021-03-01 00:00:00'
			),
			( new Link( 'https://hello.you' ) )
			->add_check(
				500,
				'2021-04-01 00:00:00'
			),
			( new Link( 'https://banana.fruit' ) )
			->add_check(
				200,
				'2021-05-01 00:00:00'
			),
			( new Link( 'https://jump.uk' ) )
			->add_check(
				404,
				'2020-06-01 00:00:00'
			)->set_broken(),
			( new Link( 'https://example.com/6' ) )
			->add_check(
				200,
				'2021-07-01 00:00:00'
			),
			( new Link( 'https://walnut.org' ) )
			->add_check(
				500,
				'2023-08-01 00:00:00'
			),
			( new Link( 'https://acorn.retro' ) )
			->add_check(
				200,
				'2021-09-01 00:00:00'
			)->set_broken(),
			( new Link( 'https://zebra.zoo' ) )
			->add_check(
				404,
				'2021-10-01 00:00:00'
			),

		);
	}

	/**
	 * @testdox It should be possible to use the upsert method to update an existing link.
	 *
	 * @return void
	 */
	public function test_can_update_existing_link(): void {
		$link = new Link( 'https://example.com' );
		$link = $this->link_repository->upsert( $link );

		$this->assertNotNull( $link->get_id() );

		// Update the link.
		$link->set_archived_href( 'https://example.com/updated' );
		$link = $this->link_repository->upsert( $link );

		$this->assertSame( 'https://example.com/updated', $link->get_archived_href() );
	}

	/**
	 * @testdox Attempting to get links for a post that has none defined in meta should result in an empty collection.
	 *
	 * @return void
	 */
	public function test_get_links_for_post_with_no_links(): void {
		$link_collection = $this->link_repository->get_links_for_post( 999999999 );

		$this->assertCount( 0, $link_collection->get_links() );
	}
}
