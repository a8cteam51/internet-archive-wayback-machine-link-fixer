<?php

/**
 * Unit test for the link collection object.
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Collection
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Link;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;

/**
 * Test_Link_Collection
 */
class Test_Link_Collection extends \WP_UnitTestCase {

	/**
	 * @testdox It should be possible to add a link to the collection and retrieve it.
	 *
	 * @return void
	 */
	public function test_can_add_link_to_collection(): void {
		$collection = new \Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Collection( 1 );
		$link       = new Link( 'https://example.com' );

		$collection->add( $link );

		$this->assertSame( $link, $collection->get_links()[0] );
	}

	/**
	 * @testdox It should be possible to get the post ID from the collection.
	 *
	 * @return void
	 */
	public function test_can_get_post_id(): void {
		$collection = new \Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Collection( 1 );

		$this->assertSame( 1, $collection->get_post_id() );
	}

	/**
	 * @testdox It should be possible to get the links from the collection as an array
	 *
	 * @return void
	 */
	public function test_can_get_links(): void {
		$collection = new \Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Collection( 1 );
		$link       = new Link( 'https://example.com' );

		$collection->add( $link );

		$this->assertSame( array( $link ), $collection->get_links() );
	}

	/**
	 * @testdox It should be possible to get the links from the collection as json
	 *
	 * @return void
	 */
	public function test_can_get_links_as_json(): void {
		$collection = new \Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Collection( 1 );
		$link       = new Link( 'https://example.com' );

		$collection->add( $link );

		$this->assertSame(
			'[{"id":null,"href":"https:\/\/example.com","archived_href":null,"redirect_href":null,"checks":[],"broken":false,"last_checked":null,"process":"new"}]',
			$collection->to_json()
		);
	}
}
