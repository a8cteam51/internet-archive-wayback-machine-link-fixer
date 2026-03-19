<?php

/**
 * Tests for the Link Check REST API endpoint.
 *
 * @since 2.0.0
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Rest\Link_Check_Rest
 *
 * @group Rest
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Rest;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Rest\Link_Check_Rest;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Link_Checker_Client;

/**
 * Test_Link_Check_Rest
 */
class Test_Link_Check_Rest extends \WP_UnitTestCase {

	/**
	 * The REST server instance.
	 *
	 * @var \WP_REST_Server
	 */
	private $server;

	/**
	 * The link repository instance.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Set up the REST server.
		global $wp_rest_server;
		$wp_rest_server = new \Spy_REST_Server();
		$this->server   = $wp_rest_server;

		// Register the routes via the rest_api_init action.
		do_action( 'rest_api_init' );

		$this->link_repository = new Link_Repository();
	}

	/**
	 * Tear down the test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;

		remove_all_filters( 'iawmlf_link_checker_client' );
		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Helper to dispatch a request to the link check endpoint.
	 *
	 * @param array         $params The request parameters.
	 * @param callable|null $config Optional callback to configure the request.
	 *
	 * @return \WP_REST_Response
	 */
	private function dispatch_request( array $params = array(), ?callable $config = null ): \WP_REST_Response {
		$request = new \WP_REST_Request(
			'POST',
			'/' . Link_Check_Rest::NAMESPACE . Link_Check_Rest::ROUTE
		);

		if ( ! empty( $params ) ) {
			$request->set_body_params( $params );
		}

		if ( $config ) {
			$request = $config( $request );
		}

		return $this->server->dispatch( $request );
	}

	/**
	 * @testdox The link-check REST route should be registered.
	 *
	 * @return void
	 */
	public function test_route_is_registered(): void {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey(
			'/' . Link_Check_Rest::NAMESPACE . Link_Check_Rest::ROUTE,
			$routes
		);
	}

	/**
	 * @testdox A request without a link parameter should return a 400 error.
	 *
	 * @return void
	 */
	public function test_missing_link_param_returns_400(): void {
		$response = $this->dispatch_request();
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * @testdox A request with a link not in the database should return a 404 error.
	 *
	 * @return void
	 */
	public function test_link_not_found_returns_404(): void {
		$response = $this->dispatch_request( array( 'link' => 'https://not-in-database.com' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * @testdox A link that has been recently checked should return cached data without re-checking.
	 *
	 * @return void
	 */
	public function test_recently_checked_link_returns_cached_data(): void {
		// Create a link with a recent check.
		$link = new Link( 'https://recently-checked.com' );
		$link->set_archived_href( 'https://web.archive.org/web/20240101/https://recently-checked.com' );
		$link->add_check( 200, gmdate( 'Y-m-d H:i:s' ) );
		$this->link_repository->upsert( $link );

		$response = $this->dispatch_request( array( 'link' => 'https://recently-checked.com' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertFalse( $data['updated'] );
		$this->assertTrue( $data['valid'] );
	}

	/**
	 * @testdox A link that needs re-checking should call the link checker client and return updated data.
	 *
	 * @return void
	 */
	public function test_link_needing_recheck_calls_client(): void {
		// Create a link with an old check (more than the default duration ago).
		$link = new Link( 'https://needs-recheck.com' );
		$link->set_archived_href( 'https://web.archive.org/web/20240101/https://needs-recheck.com' );
		$link->add_check( 200, '2020-01-01 00:00:00' );
		$this->link_repository->upsert( $link );

		// Mock the link checker client to return a 200 status.
		$mock_client = $this->createMock( Link_Checker_Client::class );
		$mock_client->method( 'check_single' )
			->willReturn( 200 );

		add_filter( 'iawmlf_link_checker_client', fn() => $mock_client );

		$response = $this->dispatch_request( array( 'link' => 'https://needs-recheck.com' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['updated'] );
		$this->assertTrue( $data['valid'] );
	}

	/**
	 * @testdox A link that fails the check should be marked as broken.
	 *
	 * @return void
	 */
	public function test_broken_link_is_marked_broken(): void {
		// Create a link with 2 prior failed checks (needs 3 total to be broken).
		$link = new Link( 'https://broken-link.com' );
		$link->set_archived_href( 'https://web.archive.org/web/20240101/https://broken-link.com' );
		$link->add_check( 404, '2020-01-01 00:00:00' );
		$link->add_check( 404, '2020-01-02 00:00:00' );
		$this->link_repository->upsert( $link );

		// Mock the link checker client to return a 404 status (3rd failed check).
		$mock_client = $this->createMock( Link_Checker_Client::class );
		$mock_client->method( 'check_single' )
			->willReturn( 404 );

		add_filter( 'iawmlf_link_checker_client', fn() => $mock_client );

		$response = $this->dispatch_request( array( 'link' => 'https://broken-link.com' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['updated'] );
		$this->assertFalse( $data['valid'] );
		$this->assertTrue( $data['link']->is_broken() );
	}

	/**
	 * @testdox A link that passes the check should be marked as valid.
	 *
	 * @return void
	 */
	public function test_valid_link_is_marked_valid(): void {
		// Create a link with no checks (will trigger a check).
		$link = new Link( 'https://valid-link.com' );
		$link->set_archived_href( 'https://web.archive.org/web/20240101/https://valid-link.com' );
		$this->link_repository->upsert( $link );

		// Mock the link checker client to return a 200 status.
		$mock_client = $this->createMock( Link_Checker_Client::class );
		$mock_client->method( 'check_single' )
			->willReturn( 200 );

		add_filter( 'iawmlf_link_checker_client', fn() => $mock_client );

		$response = $this->dispatch_request( array( 'link' => 'https://valid-link.com' ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['updated'] );
		$this->assertTrue( $data['valid'] );
		$this->assertFalse( $data['link']->is_broken() );
	}

	/**
	 * @testdox A link check that throws an exception should return a 500 error.
	 *
	 * @return void
	 */
	public function test_link_check_exception_returns_500(): void {
		// Create a link with no checks (will trigger a check).
		$link = new Link( 'https://error-link.com' );
		$link->set_archived_href( 'https://web.archive.org/web/20240101/https://error-link.com' );
		$this->link_repository->upsert( $link );

		// Mock the link checker client to throw an exception.
		$mock_client = $this->createMock( Link_Checker_Client::class );
		$mock_client->method( 'check_single' )
			->willThrowException( new \Exception( 'Service unavailable' ) );

		add_filter( 'iawmlf_link_checker_client', fn() => $mock_client );

		$response = $this->dispatch_request( array( 'link' => 'https://error-link.com' ) );

		$this->assertEquals( 500, $response->get_status() );
	}
}
