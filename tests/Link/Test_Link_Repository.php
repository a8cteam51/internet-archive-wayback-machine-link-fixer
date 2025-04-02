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
		$queried_links = $this->link_repository->query_links( 2, 1, array(), array(), array(), Link_Repository::ORDER_ID_DESC );

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
		$queried_links = $this->link_repository->query_links( 2, 1, array(), array(), array(), Link_Repository::ORDER_URL_ASC );

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
		$queried_links = $this->link_repository->query_links( 2, 1, array(), array(), array(), Link_Repository::ORDER_URL_DESC );

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
		$queried_links = $this->link_repository->query_links( 10, 1, array(), array(), array(), Link_Repository::ORDER_DATE_DESC, null, '2022-01' );

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
		$queried_links = $this->link_repository->query_links( 10, 1, array(), array(), array(), Link_Repository::ORDER_DATE_DESC, 'glynn' );

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
			->set_broken()
			->set_excluded(),
			( new Link( 'https://foo.com' ) )
			->add_check(
				404,
				'2021-02-01 00:00:00'
			)->set_excluded(),
			( new Link( 'https://glynn.com' ) )
			->set_archived_href( 'https://archive.com/https://glynn.com' )
			->add_check(
				200,
				'2021-03-01 00:00:00'
			),
			( new Link( 'https://hello.you' ) )
			->add_check(
				500,
				'2021-04-01 00:00:00'
			)->set_excluded(),
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
			)->set_excluded(),
			( new Link( 'https://acorn.retro' ) )
			->add_check(
				200,
				'2021-09-01 00:00:00'
			)->set_broken(),
			( new Link( 'https://zebra.zoo' ) )
			->set_archived_href( 'https://archive.com/https://zebra.zoo' )
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

	/**
	 * @testdox The totals of links with or without archives should add up to the same as without the constraint.
	 * @see https://github.com/a8cteam51/wayback-link-fixer/issues/73
	 *
	 * @return void
	 */
	public function test_totals_with_and_without_archives(): void {
		global $wpdb;

		$links = array(
			array(
				'url'    => 'https://wlf1.com',
				'checks' => '[]',
			),
			array(
				'url'    => 'https://wlf2.com',
				'checks' => '[]',
			),
			array(
				'url'    => 'https://wlf3.com',
				'checks' => '[]',
			),
			array(
				'url'    => 'https://wlf4.com',
				'checks' => '[]',
			),
			array(
				'url'      => 'https://wlf5.com',
				'archived' => '',
				'checks'   => '[]',
			),
			array(
				'url'      => 'https://wlf6.com',
				'archived' => '',
				'checks'   => '[]',
			),
			array(
				'url'      => 'https://wlf7.com',
				'archived' => '',
				'checks'   => '[]',
			),
			array(
				'url'      => 'https://wlf8.com',
				'archived' => '',
				'checks'   => '[]',
			),
			array(
				'url'      => 'https://wlf9.com',
				'archived' => 'https://arch9.com',
				'checks'   => '[]',
			),
			array(
				'url'      => 'https://wlf10.com',
				'archived' => 'https://arch10.com',
				'checks'   => '[]',
			),
			array(
				'url'      => 'https://wlf11.com',
				'archived' => 'https://arch11.com',
				'checks'   => '[]',
			),
			array(
				'url'      => 'https://wlf12.com',
				'archived' => 'https://arch12.com',
				'checks'   => '[]',
			),
		);

		foreach ( $links as $link ) {
			$wpdb->insert( Settings::get_link_table_name(), $link );
		}

		// Check the total links.
		$this->assertCount( 12, $this->link_repository->query_links( 100, 1 ) );

		// Check without archives.
		$this->assertCount( 8, $this->link_repository->query_links( 100, 1, array(), array(), array( Link_Repository::LINK_NO_ARCHIVE ) ) );

		// Check with archives.
		$this->assertCount( 4, $this->link_repository->query_links( 100, 1, array(), array(), array( Link_Repository::LINK_HAS_ARCHIVE ) ) );
	}

	/**
	 * @testdox It should be possible to filter links based on if they are excluded or not.
	 *
	 * @return void
	 */
	public function test_can_filter_links_by_excluded(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			10,
			1,
			array(),
			array(),
			array(),
			Link_Repository::ORDER_DATE_DESC,
			null,
			null,
			true
		);

		// 4 are set as excluded
		$this->assertCount( 4, $queried_links );

		// Check the links are excluded.
		foreach ( $queried_links as $link ) {
			$this->assertTrue( $link->is_excluded() );
		}

		// Query the links for non-excluded.
		$queried_links = $this->link_repository->query_links(
			10,
			1,
			array(),
			array(),
			array(),
			Link_Repository::ORDER_DATE_DESC,
			null,
			null,
			false
		);

		// 6 are not excluded
		$this->assertCount( 6, $queried_links );

		// Check the links are not excluded.
		foreach ( $queried_links as $link ) {
			$this->assertFalse( $link->is_excluded() );
		}
	}

	/**
	 * @testdox It should be possible to order the links by the last check date. [ASC]
	 *
	 * @return void
	 */
	public function test_can_order_links_by_last_check_date_asc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			2,
			1,
			array(),
			array(),
			array(),
			Link_Repository::ORDER_DATE_ASC
		);

		// dd($queried_links);

		// We should have the first two links
		$expected = array(
			'https://jump.uk', // 2020-06-01 00:00:00
			'https://foo.com', // 2021-02-01 00:00:00
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertEquals( $link->get_href(), $expected[ $index ] );
		}
	}

	/**
	 * @testdox It should be possible to order the links by the last check date. [DESC]
	 *
	 * @return void
	 */
	public function test_can_order_links_by_last_check_date_desc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			2,
			1,
			array(),
			array(),
			array(),
			Link_Repository::ORDER_DATE_DESC
		);

		// We should have the first two links
		$expected = array(
			'https://walnut.org',  // 2023-08-01 00:00:00
			'https://example.com', // 2022-01-01 00:00:00
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertEquals( $link->get_href(), $expected[ $index ] );
		}
	}

	/**
	 * @testdox It should be possible to order the links by the number of checks (with 2nd on the url) [ASC]
	 *
	 * @return void
	 */
	public function test_can_order_links_by_number_of_checks_asc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			10,
			1,
			array(),
			array(),
			array(),
			Link_Repository::ORDER_LINK_CHECKS_ASC
		);

		// We should have the first two links
		$expected = array(
			'https://acorn.retro', // Count 1
			'https://banana.fruit', // Count 1
			'https://example.com/6',  // Count 1
			'https://foo.com',    // Count 1
			'https://glynn.com',  // Count 1
			'https://hello.you',  // Count 1
			'https://jump.uk',    // Count 1
			'https://walnut.org', // Count 1
			'https://zebra.zoo',  // Count 1

			'https://example.com', // Count 3
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertEquals( $link->get_href(), $expected[ $index ] );
		}
	}

	/**
	 * @testdox It should be possible to order the links by the number of checks (with 2nd on the url) [DESC]
	 *
	 * @return void
	 */
	public function test_can_order_links_by_number_of_checks_desc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			10,
			1,
			array(),
			array(),
			array(),
			Link_Repository::ORDER_LINK_CHECKS_DESC
		);

		// We should have the first two links
		$expected = array(
			'https://example.com', // Count 3

			'https://zebra.zoo',  // Count 1
			'https://walnut.org', // Count 1
			'https://jump.uk',    // Count 1
			'https://hello.you',  // Count 1
			'https://glynn.com',  // Count 1
			'https://foo.com',    // Count 1
			'https://example.com/6',  // Count 1
			'https://banana.fruit', // Count 1
			'https://acorn.retro', // Count 1
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertEquals( $link->get_href(), $expected[ $index ] );
		}
	}

	/**
	 * @testdox It should be possible to order the links based on the health, with url being the 2nd sortable value [ASC]
	 *
	 * @return void
	 */
	public function test_can_order_links_by_health_and_url_asc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			10,
			1,
			array(),
			array(),
			array(),
			Link_Repository::ORDER_LINK_HEALTH_ASC
		);

		// We should have the first two links
		$expected = array(
			'https://banana.fruit', // is_broken = false
			'https://example.com/6',  // is_broken = false
			'https://foo.com', // is_broken = false
			'https://glynn.com',  // is_broken = false
			'https://hello.you',  // is_broken = false
			'https://walnut.org',  // is_broken = false
			'https://zebra.zoo',  // is_broken = false

			'https://acorn.retro', // is_broken = true
			'https://example.com', // is_broken = true
			'https://jump.uk',    // is_broken = true
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertEquals( $link->get_href(), $expected[ $index ] );
		}
	}

	/**
	 * @testdox It should be possible to order the links based on the health, with url being the 2nd sortable value [DESC]
	 *
	 * @return void
	 */
	public function test_can_order_links_by_health_and_url_desc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			10,
			1,
			array(),
			array(),
			array(),
			Link_Repository::ORDER_LINK_HEALTH_DESC
		);

		// We should have the first two links
		$expected = array(
			'https://jump.uk',    // is_broken = true
			'https://example.com', // is_broken = true
			'https://acorn.retro', // is_broken = true

			'https://zebra.zoo',  // is_broken = false
			'https://walnut.org',  // is_broken = false
			'https://hello.you',  // is_broken = false
			'https://glynn.com',  // is_broken = false
			'https://foo.com', // is_broken = false
			'https://example.com/6',  // is_broken = false
			'https://banana.fruit', // is_broken = false
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertEquals( $link->get_href(), $expected[ $index ] );
		}
	}

	/**
	 * @testdox It should be possible to order the links based on the exclusion, with url being the 2nd sortable value [DESC]
	 *
	 * @return void
	 */
	public function test_can_order_links_by_exclusion_and_url_desc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			10,
			1,
			array(),
			array(),
			array(),
			Link_Repository::ORDER_LINK_EXCLUDED_DESC
		);

		dump($queried_links);

		// We should have the first two links
		$expected = array(
			'https://walnut.org',  // is_excluded = true
			'https://hello.you',  // is_excluded = true
			'https://foo.com', // is_excluded = true
			'https://example.com', // is_excluded = true

			'https://zebra.zoo',  // is_excluded = false
			'https://jump.uk',    // is_excluded = false
			'https://glynn.com',  // is_excluded = false
			'https://example.com/6',  // is_excluded = false
			'https://banana.fruit', // is_excluded = false
			'https://acorn.retro', // is_excluded = false
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertEquals( $link->get_href(), $expected[ $index ] );
		}
	}

	/**
	 * @testdox It should be possible to order the links based on the exclusion, with url being the 2nd sortable value [ASC]
	 *
	 * @return void
	 */
	public function test_can_order_links_by_exclusion_and_url_asc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			10,
			1,
			array(),
			array(),
			array(),
			Link_Repository::ORDER_LINK_EXCLUDED_ASC
		);

		// We should have the first two links
		$expected = array(
			'https://acorn.retro', // is_excluded = false
			'https://banana.fruit', // is_excluded = false
			'https://example.com/6',  // is_excluded = false
			'https://glynn.com',  // is_excluded = false
			'https://jump.uk',    // is_excluded = false
			'https://zebra.zoo',  // is_excluded = false

			'https://example.com', // is_excluded = true
			'https://foo.com', // is_excluded = true
			'https://hello.you',  // is_excluded = true
			'https://walnut.org',  // is_excluded = true
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertEquals( $link->get_href(), $expected[ $index ] );
		}
	}

	/**
	 * @testdox It should be possible to order the links based on if the link has an archived link, with url being the 2nd sortable value [ASC]
	 *
	 * @return void
	 */
	public function test_can_order_links_by_has_archive_and_url_asc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			20,
			1,
			array(),
			array(),
			array(),
			Link_Repository::ORDER_HAS_ARCHIVE_ASC
		);

		// We should have the first two links
		$expected = array(
			'https://acorn.retro', // archive_url = ''
			'https://banana.fruit', // archive_url = ''
			'https://example.com', // archive_url = ''
			'https://example.com/6',  // archive_url = ''
			'https://foo.com', // archive_url = ''
			'https://hello.you',  // archive_url = ''
			'https://jump.uk',    // archive_url = ''
			'https://walnut.org',  // archive_url = ''

			'https://glynn.com',  // archive_url = 'https://archive.com/https://glynn.com'
			'https://zebra.zoo',  // archive_url = 'https://archive.com/https://zebra.zoo'
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertEquals( $link->get_href(), $expected[ $index ] );
		}
	}

	/**
	 * @testdox It should be possible to order the links based on if the link has an archived link, with url being the 2nd sortable value [DESC]
	 *
	 * @return void
	 */
	public function test_can_order_links_by_has_archive_and_url_desc(): void {
		// Insert the default links.
		$this->populate_database();

		// Query the links.
		$queried_links = $this->link_repository->query_links(
			20,
			1,
			array(),
			array(),
			array(),
			Link_Repository::ORDER_HAS_ARCHIVE_DESC
		);

		// We should have the first two links
		$expected = array(
			'https://zebra.zoo',  // archive_url = 'https://archive.com/https://zebra.zoo'
			'https://glynn.com',  // archive_url = 'https://archive.com/https://glynn.com'


			'https://walnut.org',  // archive_url = ''
			'https://jump.uk',    // archive_url = ''
			'https://hello.you',  // archive_url = ''
			'https://foo.com', // archive_url = ''
			'https://example.com/6',  // archive_url = ''
			'https://example.com', // archive_url = ''
			'https://banana.fruit', // archive_url = ''
			'https://acorn.retro', // archive_url = ''
		);

		foreach ( $queried_links as $index => $link ) {
			$this->assertEquals( $link->get_href(), $expected[ $index ] );
		}
	}
}
