<?php

/**
 * Tests for HTTP implementation of the Wayback Machine System Client.
 *
 * @since 1.3.0
 *
 * @coversDefaultClass Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\HTTP_Client\HTTP_System_Client
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests;

use DateTime;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\HTTP_Client\HTTP_System_Client;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Exception\Invalid_Response_Exception;

/**
 * Test class for HTTP_System_Client.
 */
class Test_HTTP_System_Client extends \WP_UnitTestCase {

	/**
	 * On tear down, remove the filters.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'pre_http_request' );
	}

	/**
	 * Set HTTP client to return the following response.
	 *
	 * @param array|\WP_Error $mock_response
	 *
	 * @return void
	 */
	private function mock_wp_http_response( $mock_response ) {
		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) use ( $mock_response ) {
				return $mock_response;
			},
			999999999,
			3
		);
	}

	/**
	 * @testdox When valid account creds are supplied it should be possible to get a users archive.org stats.
	 */
	public function test_get_user_stats_with_valid_creds(): void {
		// Mock the HTTP response to simulate a successful API call.
		$mock_response = array(
			'available'            => 1000,
			'daily_captures'       => 10,
			'daily_captures_limit' => 100,
			'processing'           => 5,
		);
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( $mock_response ),
			)
		);

		// Create an instance of the HTTP_System_Client.
		$client = new HTTP_System_Client();
		// Call the method with valid credentials.
		$stats = $client->get_user_stats( 'valid_access_key', 'valid_secret_key' );
		// Assert that the returned stats match the mock response.
		$this->assertIsArray( $stats );
	}

	/**
	 * @testdox If some fields are missing from the responce of user stats, they should be defaulted.
	 */
	public function test_get_user_stats_with_missing_fields(): void {
		$mock_response = array(
			'foo' => 1000,
			'bar' => 10,
		);

		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( $mock_response ),
			)
		);

		// Create an instance of the HTTP_System_Client.
		$client = new HTTP_System_Client();

		// Call the method with valid credentials.
		$stats = $client->get_user_stats( 'valid_access_key', 'valid_secret_key' );

		// Assert that the returned stats have default values for missing fields.
		$this->assertIsArray( $stats );
		$this->assertArrayHasKey( 'available', $stats );
		$this->assertArrayHasKey( 'daily_captures', $stats );
		$this->assertArrayHasKey( 'daily_captures_limit', $stats );
		$this->assertArrayHasKey( 'processing', $stats );
		$this->assertEquals( 0, $stats['available'] );
		$this->assertEquals( 0, $stats['daily_captures'] );
		$this->assertEquals( 0, $stats['daily_captures_limit'] );
		$this->assertEquals( 0, $stats['processing'] );
	}

	/**
	 * @testdox If we get a 401 when attempted to get a users creds, it should return null.
	 */
	public function test_get_user_stats_with_invalid_creds(): void {
		// Mock the HTTP response to simulate a 401 Unauthorized error.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 401 ),
				'body'     => json_encode( array( 'error' => 'Unauthorized' ) ),
			)
		);

		// Create an instance of the HTTP_System_Client.
		$client = new HTTP_System_Client();

		// Call the method with invalid credentials.
		$stats = $client->get_user_stats( 'invalid_access_key', 'invalid_secret_key' );

		// Assert that the returned stats are null.
		$this->assertNull( $stats );
	}

	/**
	 * @testdox An exception should be thrown if an exception is generated doing the get call.
	 */
	public function test_get_user_stats_with_exception(): void {
		// Mock the HTTP response to simulate an exception.
		$this->mock_wp_http_response( new \WP_Error( 'http_error', 'An error occurred.' ) );

		// Create an instance of the HTTP_System_Client.
		$client = new HTTP_System_Client();

		// Assert that an exception is thrown when calling get_user_stats.
		$this->expectException( Invalid_Response_Exception::class );
		$client->get_user_stats( 'valid_access_key', 'valid_secret_key' );
	}

	/**
	 * @testdox an Invalid_Response_Exception should be thrown if the response is a wp_eror
	 */
	public function test_get_user_stats_with_invalid_json_response(): void {
		// Mock the HTTP response to simulate an invalid JSON response.
		$this->mock_wp_http_response(
			new \WP_Error( 'http_request_failed', 'Error' )
		);

		// Create an instance of the HTTP_System_Client.
		$client = new HTTP_System_Client();

		// Assert that an Invalid_Response_Exception is thrown when calling get_user_stats.
		$this->expectException( Invalid_Response_Exception::class );
		$client->get_user_stats( 'valid_access_key', 'valid_secret_key' );
	}

	/**
	 * @testdox If the response is not a proper string, null should be returned when checking user creds.
	 */
	public function test_get_user_stats_with_invalid_response_body(): void {
		// Mock the HTTP response to simulate an invalid response body.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => 12345, // Not a valid JSON string.
			)
		);

		// Create an instance of the HTTP_System_Client.
		$client = new HTTP_System_Client();

		// Call the method with valid credentials.
		$stats = $client->get_user_stats( 'valid_access_key', 'valid_secret_key' );

		// Assert that the returned stats are null due to invalid response body.
		$this->assertNull( $stats );
	}

	/**
	 * @testdox If the response is not a valid JSON String, null should be returned when checking user creds.
	 */
	public function test_get_user_stats_with_invalid_json_string(): void {
		// Mock the HTTP response to simulate an invalid JSON string.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => 'This is not a valid JSON string', // Invalid JSON.
			)
		);

		// Create an instance of the HTTP_System_Client.
		$client = new HTTP_System_Client();

		// Call the method with valid credentials.
		$stats = $client->get_user_stats( 'valid_access_key', 'valid_secret_key' );

		// Assert that the returned stats are null due to invalid JSON string.
		$this->assertNull( $stats );
	}

	/**
	 * @testdox When checking if a user is valid, it should return true if the service is online and the credentials are valid.
	 */
	public function test_is_valid_user_with_valid_creds(): void {
		// Mock the HTTP response to simulate a successful API call.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'processing' => 1 ) ),
			)
		);

		// Create an instance of the HTTP_System_Client.
		$client = new HTTP_System_Client();

		// Call the method with valid credentials.
		$is_valid = $client->is_valid_user( 'valid_access_key', 'valid_secret_key' );

		// Assert that the user is valid.
		$this->assertTrue( $is_valid );
	}

	/**
	 * @testdox When checking if a user is valid, it should return false if the service is offline.
	 */
	public function test_is_valid_user_with_service_offline(): void {
		// Mock the HTTP response to simulate an offline service.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 401 ),
				'body'     => json_encode( array( 'fail' => 1 ) ),
			)
		);

		// Create an instance of the HTTP_System_Client.
		$client = new HTTP_System_Client();

		// Call the method with valid credentials.
		$is_valid = $client->is_valid_user( 'valid_access_key', 'valid_secret_key' );

		// Assert that the user is not valid due to service being offline.
		$this->assertFalse( $is_valid );
	}

	/**
	 * @testdox When checking if a user is valid, it should return false if an exception is thrown during the request.
	 */
	public function test_is_valid_user_with_exception(): void {
		// Mock the HTTP response to simulate an exception.
		$this->mock_wp_http_response( new \WP_Error( 'http_error', 'An error occurred.' ) );

		// Create an instance of the HTTP_System_Client.
		$client = new HTTP_System_Client();

		$resp = $client->is_valid_user( 'valid_access_key', 'valid_secret_key' );
		// Assert that the user is not valid due to an exception being thrown.
		$this->assertFalse( $resp );
	}
}
