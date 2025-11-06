<?php

/**
 * Unit tests for the Link model class.
 *
 * @since 1.2.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link
 *
 * @group Link
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Link;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;

/**
 * Test_Link
 */
class Test_Link extends \WP_UnitTestCase {

	// Clear all custom actions on tear down.
	public function tear_down(): void {
		parent::tear_down();
		remove_all_filters( 'iawmlf_failed_count' );
		remove_all_filters( 'iawmlf_is_valid_check' );
	}

	/**
	 * @testdox It should be possible to set a new link based only on a URl.
	 *
	 * @return void
	 */
	public function test_can_set_link_from_url(): void {
		$link = new Link( 'https://example.com' );
		$this->assertSame( 'https://example.com', $link->get_href() );
	}

	/**
	 * @testdox It should be possible to set a link ID to the link and access it via the getter.
	 *
	 * @return void
	 */
	public function test_can_set_link_id(): void {
		$link = new Link( 'https://example.com' );

		// Should return null if not set.
		$this->assertNull( $link->get_id() );

		$link->set_id( 1 );
		$this->assertSame( 1, $link->get_id() );
	}

	/**
	 * @testdox It should be possible to set the archived href and access it via the getter.
	 *
	 * @return void
	 */
	public function test_can_set_archived_href(): void {
		$link = new Link( 'https://example.com' );

		// Should return null if not set.
		$this->assertNull( $link->get_archived_href() );

		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );
		$this->assertSame( 'https://web-wp.archive.org/web/20240101000000/https://example.com', $link->get_archived_href() );
	}

	/**
	 * @testdox It should be possible to set the redirect href and access it via the getter.
	 *
	 * @return void
	 */
	public function test_can_set_redirect_href(): void {
		$link = new Link( 'https://example.com' );

		// Should return null if not set.
		$this->assertNull( $link->get_redirect_href() );

		$link->set_redirect_href( 'https://example.com' );
		$this->assertSame( 'https://example.com', $link->get_redirect_href() );
	}

	/**
	 * @testdox It should be possible to set the link as broken and check if it is broken.
	 *
	 * @return void
	 */
	public function test_can_set_broken(): void {
		$link = new Link( 'https://example.com' );

		// Should return false if not set.
		$this->assertFalse( $link->is_broken() );

		$link->set_broken();
		$this->assertTrue( $link->is_broken() );
	}

	/**
	 * @testdox It should be possible to add a check to the link.
	 *
	 * @return void
	 */
	public function test_can_add_check(): void {
		$link = new Link( 'https://example.com' );

		// Should return an empty array if not set.
		$this->assertSame( array(), $link->get_checks() );

		$link->add_check( 418, '20240101000000' );
		$this->assertSame(
			array(
				array(
					'date'      => '20240101000000',
					'http_code' => 418,
				),
			),
			$link->get_checks()
		);
	}

	/**
	 * @testdox It should be possible to get the last check.
	 *
	 * @return void
	 */
	public function test_can_get_last_check(): void {
		$link = new Link( 'https://example.com' );

		// Should return null if not set.
		$this->assertNull( $link->get_last_check() );

		$link->add_check( 418, '20230101000000' );
		$link->add_check( 418, '20240101000000' );
		$this->assertSame(
			array(
				'date'      => '20240101000000',
				'http_code' => 418,
			),
			$link->get_last_check()
		);
	}

	/**
	 * @testdox It should be possible to check if any checks have been made with a defined http code.
	 *
	 * @return void
	 */
	public function test_can_check_if_has_check_with_http_code(): void {
		$link = new Link( 'https://example.com' );

		// Should return false if not set.
		$this->assertFalse( $link->has_http_code( 500 ) );

		$link->add_check( 418, '20230101000000' );
		$this->assertTrue( $link->has_http_code( 418 ) );
	}

	/**
	 * @testdox It should be possible to check if the link is valid.
	 *
	 * @return void
	 */
	public function test_can_check_if_link_is_valid(): void {
		add_filter( 'iawmlf_failed_count', fn () => 3 );
		$link = new Link( 'https://example.com' );

		// By default the link should be valid.
		$this->assertTrue( $link->is_valid() );

		// By having 3 checks with 500, the link should be invalid.
		$link->add_check( 500, '20230101000000' );
		$link->add_check( 500, '20240101000000' );
		$link->add_check( 500, '20250101000000' );

		$this->assertFalse( $link->is_valid() );
	}

	/**
	 * @testdox It should be possible to use a filter to change how many failed checks are needed to be considered invalid.
	 *
	 * @hook iawmlf_failed_count
	 *
	 * @return void
	 */
	public function test_can_use_filter_to_change_failed_count(): void {
		add_filter( 'iawmlf_failed_count', fn () => 2 );

		$link = new Link( 'https://example.com' );

		// By default the link should be valid.
		$this->assertTrue( $link->is_valid() );

		// By having 2 checks with 500, the link should be invalid.
		$link->add_check( 500, '20230101000000' );
		$link->add_check( 500, '20240101000000' );

		$this->assertFalse( $link->is_valid() );

		// Clear the filter.
		remove_all_filters( 'iawmlf_failed_count' );
	}

	/**
	 * @testdox It should be possible to override the is_valid logic using a filter.
	 *
	 * @hook iawmlf_is_valid_check
	 *
	 * @return void
	 */
	public function test_can_use_filter_to_override_is_valid(): void {
		add_filter( 'iawmlf_failed_count', fn () => 3 );

		add_filter(
			'iawmlf_is_valid_check',
			/**
			 * @param boolean                           $is_valid If the link is valid.
			 * @param array{date:string, http_code:int} $check    The check.
			 * @param Link                              $link     The link.
			 *
			 * @return boolean
			 */
			function ( bool $is_valid, array $check, Link $link ) {
				// Only a 502 will make the link invalid.
				return 502 !== $check['http_code'];
			},
			10,
			3
		);

		$link = new Link( 'https://example.com' );

		// Only 2 502s in the last 3, so should be valid.
		$link->add_check( 500, '20250101000000' );
		$link->add_check( 502, '20230101000000' );
		$link->add_check( 502, '20240101000000' );
		$this->assertTrue( $link->is_valid() );

		// Now has 3 in last 3, so should be invalid.
		$link->add_check( 502, '20260101000000' );
		$this->assertFalse( $link->is_valid() );

		// Clear the filter.
		remove_all_filters( 'iawmlf_is_valid_check' );
		remove_all_filters( 'iawmlf_failed_count' );
	}

	/**
	 * @testdox It should be possible to create a link from a JSON representation.
	 *
	 * @return void
	 */
	public function test_can_create_link_from_json(): void {
		$json = json_encode(
			array(
				'id'            => 1,
				'href'          => 'https://example.com',
				'archived_href' => 'https://web-wp.archive.org/web/20240101000000/https://example.com',
				'redirect_href' => 'https://example.com',
				'checks'        => array(
					array(
						'date'      => '20240101000000',
						'http_code' => 418,
						'junk'      => 'data',
					),
				),
				'junk'          => 'data',
			)
		);

		$link = Link::from_json( $json );

		$this->assertSame( 1, $link->get_id() );
		$this->assertSame( 'https://example.com', $link->get_href() );
		$this->assertSame( 'https://web-wp.archive.org/web/20240101000000/https://example.com', $link->get_archived_href() );
		$this->assertSame( 'https://example.com', $link->get_redirect_href() );
		$this->assertSame(
			array(
				array(
					'date'      => '20240101000000',
					'http_code' => 418,
				),
			),
			$link->get_checks()
		);
	}

	/**
	 * @testdox It should be possible to convert a link to a JSON representation.
	 *
	 * @return void
	 */
	public function test_can_convert_link_to_json(): void {
		$link = new Link( 'https://example.com' );
		$link->set_id( 1 );
		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );
		$link->set_redirect_href( 'https://example.com' );
		$link->add_check( 418, '20240101000000' );

		$json = json_encode( $link );

		$this->assertJson( $json );
		$this->assertStringContainsString( '"id":1', $json );
		$this->assertStringContainsString( '"href":"https:\/\/example.com"', $json );
		$this->assertStringContainsString( '"archived_href":"https:\/\/web.archive.org\/web\/20240101000000\/https:\/\/example.com"', $json );
		$this->assertStringContainsString( '"redirect_href":"https:\/\/example.com"', $json );
		$this->assertStringContainsString( '"checks":[{', $json );
		$this->assertStringContainsString( '"date":"20240101000000"', $json );
		$this->assertStringContainsString( '"http_code":418', $json );
	}

	/**
	 * @testdox It should be possible to cast a link to json and back to a link and have the order the checks remain the same (oldest first).
	 *
	 * @return void
	 */
	public function test_can_cast_link_to_json_and_back_and_maintain_order(): void {
		$link = new Link( 'https://example.com' );
		$link->add_check( 418, '20240101000000' );
		$link->add_check( 418, '20250101000000' );
		$link->add_check( 418, '20260101000000' );

		$json = json_encode( $link );

		// Add a mock id.
		$json = str_replace( '"id":null', '"id":1', $json );

		$link = Link::from_json( $json );

		$checks = $link->get_checks();

		$this->assertSame( '20240101000000', $checks[0]['date'] );
		$this->assertSame( '20250101000000', $checks[1]['date'] );
		$this->assertSame( '20260101000000', $checks[2]['date'] );
	}

	/**
	 * @testdox It should be possible to check if a link has an archived href.
	 *
	 * @return void
	 */
	public function test_can_check_if_has_archived_href(): void {
		// If archived link is null, this shoud fail.
		$link = new Link( 'https://example.com' );

		$this->assertFalse( $link->has_archived_href() );

		// If link is empty string, should fail.
		$link->set_archived_href( '' );

		$this->assertFalse( $link->has_archived_href() );

		// If link is set, should pass.
		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );

		$this->assertTrue( $link->has_archived_href() );
	}

	/**
	 * @testdox When checking if a link is valid, less checks that the min failures required, will result in being valid.
	 *
	 * @return void
	 */
	public function test_less_checks_than_min_failures(): void {
		add_filter( 'iawmlf_failed_count', fn () => 3 );

		$link = new Link( 'https://example.com' );

		// By default the link should be valid.
		$this->assertTrue( $link->is_valid() );

		// By having 1 check with 500, the link should be valid.
		$link->add_check( 500, '20230101000000' );
		$this->assertTrue( $link->is_valid() );

		// By having 3 checks with 500, the link should be invalid.
		$link->add_check( 500, '20240101000000' );
		$link->add_check( 500, '20250101000000' );

		$this->assertFalse( $link->is_valid() );
	}

	/**
	 * @testdox You should be able to set if a link is excluded.
	 *
	 * @return void
	 */
	public function test_can_set_excluded(): void {
		$link = new Link( 'https://example.com' );

		// Should return false if not set.
		$this->assertFalse( $link->is_excluded() );

		$link->set_excluded();
		$this->assertTrue( $link->is_excluded() );
	}

	/**
	 * @testdox When we get a snapshot url from the model, it should be reparsed as web-wp.archive.org to web.archive.org
	 *
	 * @since 1.3.2
	 *
	 * @return void
	 */
	public function test_can_reparse_snapshot_url(): void {
		// HTTPS
		$link = new Link( 'https://example.com' );
		$link->set_archived_href( 'https://web.archive.org/web/20240101000000/https://example.com' );
		$this->assertSame( 'https://web-wp.archive.org/web/20240101000000/https://example.com', $link->get_archived_href() );

		// HTTP
		$link = new Link( 'http://example.com' );
		$link->set_archived_href( 'http://web.archive.org/web/20240101000000/https://example.com' );
		$this->assertSame( 'http://web-wp.archive.org/web/20240101000000/https://example.com', $link->get_archived_href() );
	}
}
