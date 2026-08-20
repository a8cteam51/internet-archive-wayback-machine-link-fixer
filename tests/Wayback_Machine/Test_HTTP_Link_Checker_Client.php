<?php

/**
 * Tests for HTTP implementation of the Wayback Machine Link Checker Client.
 *
 * @since 1.2.0
 *
 * @coversDefaultClass Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\HTTP_Client\HTTP_Link_Checker_Client
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests;

use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Exception\Service_Offline_Exception;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Exception\Invalid_Response_Exception;
use Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\HTTP_Client\HTTP_Link_Checker_Client;

/**
 * Test class for HTTP_Link_Checker_Client.
 */
class Test_HTTP_Link_Checker_Client extends \WP_UnitTestCase {

	/**
	 * On tear down, remove the filters.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'iawmlf_link_checker_url_base' );
		remove_all_filters( 'iawmlf_link_checker_url_params' );
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
			10,
			3
		);
	}

	/**
	 * @testdox When checking a link, the client should make a request to the Wayback Machine API.
	 *
	 * @return void
	 */
	public function test_should_make_request_to_wayback_machine_api() {

		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		$called_url = null;

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) use ( &$called_url ) {
				// The header should be set.
				$this->assertArrayHasKey( 'headers', $args );
				$this->assertArrayHasKey( 'WP-Wayback-Link-Fixer', $args['headers'] );

				$called_url = $url;
				return false;
			},
			10,
			3
		);

		try{
			$client->check_single( 'https://make-request.example.com' );
		} catch (Service_Offline_Exception $e) {
			// Show a note to say offline, but dont stop the test.
			$this->markTestSkipped( 'The service is offline' );
		}

		// Check the url starts with https://iabot-api.archive.org/livewebcheck
		$this->assertStringStartsWith( 'https://iabot-api.archive.org/livewebcheck', $called_url );

		// Check contains url=https://make-request.example.com
		$this->assertStringContainsString( 'url=https://make-request.example.com', $called_url );
	}

	/**
	 * @testdox When calling the link checker, it should be possible to pass additional parameters.
	 *
	 * @return void
	 */
	public function test_should_be_able_to_pass_additional_parameters() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		$called_url = null;

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) use ( &$called_url ) {
				$called_url = $url;
				return false;
			},
			10,
			3
		);

		try{
			$client->check_single( 'https://additional-params.example.com', array( 'foo' => 'bar' ) );
		} catch (Service_Offline_Exception $e) {
			// Show a note to say offline, but dont stop the test.
			$this->markTestSkipped( 'The service is offline' );
		}

		// Check the url starts with https://iabot-api.archive.org/livewebcheck
		$this->assertStringStartsWith( 'https://iabot-api.archive.org/livewebcheck', $called_url );

		// Check contains url=https://additional-params.example.com
		$this->assertStringContainsString( 'url=https://additional-params.example.com', $called_url );

		// Check contains foo=bar
		$this->assertStringContainsString( 'foo=bar', $called_url );
	}

	/**
	 * @testdox It should be possible to use a filter to change the URL called when checking a link.
	 *
	 * @return void
	 */
	public function test_should_be_able_to_change_url_called() {
		// Filter the URL.
		add_filter(
			'iawmlf_link_checker_url_base',
			function ( $url ) {
				return 'https://anotherurl.someplace.fakeit';
			}
		);

		$client = new HTTP_Link_Checker_Client();

		$called_url = null;

		// Mock the reponse body with a custom url.
		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) use ( &$called_url ) {
				$called_url = $url;
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => json_encode( array( 'status' => 201 ) ),
				);
			},
			10,
			3
		);

		$client->check_single( 'https://change-url-base.example.com' );

		// Check we have our custom URL.
		$this->assertStringStartsWith( 'https://anotherurl.someplace.fakeit', $called_url );
	}

	/**
	 * @testdox It should be possible to pass custom url params when checking a single link via a filter.
	 *
	 * @return void
	 */
	public function test_should_be_able_to_set_custom_url_params_via_filter() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		$called_url = null;

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) use ( &$called_url ) {
				$called_url = $url;
				return false;
			},
			10,
			3
		);

		// Filter the url params.
		add_filter(
			'iawmlf_link_checker_url_params',
			function ( $url_params ) {
				$url_params['banana'] = 'cherry';
				return $url_params;
			}
		);

		try{
			$client->check_single( 'https://custom-params.example.com' );
		} catch (Service_Offline_Exception $e) {
			// Show a note to say offline, but dont stop the test.
			$this->markTestSkipped( 'The service is offline ' . $e->getMessage() );
		}

		// Check contains foo=bar
		$this->assertStringContainsString( 'banana=cherry', $called_url );
	}

	/**
	 * @testdox When checking a link, if the service is offline, an exception should be thrown.
	 *
	 * @return void
	 */
	public function test_should_throw_exception_if_service_offline() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		// Mock the response with a none 200 code.
		$this->mock_wp_http_response( array( 'response' => array( 'code' => 404 ) ) );

		$this->expectException( Service_Offline_Exception::class );
		$this->expectExceptionMessage( 'The service is offline. Response:404' );

		$client->check_single( 'https://service-offline.example.com' );
	}

	/**
	 * @testdox When checking a link, if the response is invalid (no code index in body), an exception should be thrown.
	 *
	 * @return void
	 */
	public function test_should_throw_exception_if_response_invalid_no_code() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		// Mock the response body without a http code in body (response code is 200 is needed)
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'foo' => 'bar' ) ),
			)
		);

		$this->expectException( Invalid_Response_Exception::class );
		$this->expectExceptionMessage( 'The response is invalid.' );

		$client->check_single( 'https://invalid-no-code.example.com' );
	}

	/**
	 * @testdox When checking a link, if the response is invalid (no body), an exception should be thrown.
	 *
	 * @return void
	 */
	public function test_should_throw_exception_if_response_invalid_no_body() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		// Mock the response without a body.
		$this->mock_wp_http_response( array( 'response' => array( 'code' => 200 ) ) );

		$this->expectException( Invalid_Response_Exception::class );
		$this->expectExceptionMessage( 'The response is invalid.' );

		$client->check_single( 'https://invalid-no-body.example.com' );
	}

	/**
	 * @testdox It should be possible to resolve a URL to its final destination.
	 *
	 * @return void
	 */
	public function test_should_resolve_url_to_final_destination() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		// Mock the response without a body.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'location' => 'http://redirect.com' ) ),
			)
		);

		$final = $client->get_final_url( 'https://resolve-final.example.com' );

		$this->assertEquals( 'http://redirect.com', $final );
	}

	/**
	 * @testdox When trying to get the final destination for a link, if there is no location key, return the original URL.
	 *
	 * @return void
	 */
	public function test_should_return_original_url_if_no_location_key() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		// Mock the response body with a custom url.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'foo' => 'bar' ) ),
			)
		);

		$final = $client->get_final_url( 'https://no-location-key.example.com' );

		$this->assertEquals( 'https://no-location-key.example.com', $final );
	}

	/**
	 * @testdox When trying to get the final destination for a link, a non-string location should be treated as absent, not fatal.
	 *
	 * @return void
	 */
	public function test_should_return_original_url_if_location_not_string() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		// Mock the response with a numeric location value.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'location' => 123 ) ),
			)
		);

		$final = $client->get_final_url( 'https://location-not-string.example.com' );

		$this->assertEquals( 'https://location-not-string.example.com', $final );
	}

	/**
	 * @testdox When trying to get the final destination for a link, an empty-string location should fall back to the original URL.
	 *
	 * @return void
	 */
	public function test_should_return_original_url_if_location_empty_string() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		// Mock the response with an empty location value.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'location' => '' ) ),
			)
		);

		$final = $client->get_final_url( 'https://location-empty.example.com' );

		$this->assertEquals( 'https://location-empty.example.com', $final );
	}

	/**
	 * @testdox When checking a link, if a WP_Error is returned, an exception should be thrown.
	 *
	 * @return void
	 */
	public function test_should_throw_exception_if_wp_error() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		// Mock the response with a WP_Error.
		$this->mock_wp_http_response( new \WP_Error( 'error', 'SomeError' ) );

		$this->expectException( Service_Offline_Exception::class );
		$this->expectExceptionMessage( 'The service is offline. SomeError' );

		$client->check_single( 'https://wp-error.example.com' );
	}

	/**
	 * @testdox When checking a link, if the response body is not a (json) string, an exception should be thrown.
	 *
	 * @return void
	 */
	public function test_should_throw_exception_if_response_body_not_string() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		// Mock the response body with an array.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => array( 'foo' => 'bar' ),
			)
		);
		$this->expectException( Invalid_Response_Exception::class );
		$this->expectExceptionMessage( 'The response is invalid.' );

		$client->check_single( 'https://body-not-string.example.com' );
	}

	/**
	 * @testdox When checking a link, the links HTTP code should be returned.
	 *
	 * @return void
	 */
	public function test_should_return_http_code() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		// Mock the response body with a custom url.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'status' => 201 ) ),
			)
		);

		$code = $client->check_single( 'https://return-http-code.example.com' );

		$this->assertEquals( 201, $code );
	}

	/**
	 * @testdox When checking a link, an exception should be thrown if the status code is not numeric.
	 *
	 * @return void
	 */
	public function test_should_throw_exception_if_status_code_not_numeric() {
		if ( $GLOBALS['iawmlf_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$client = new HTTP_Link_Checker_Client();

		// Mock the response body with a custom url.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'status' => 'foo' ) ),
			)
		);

		$this->expectException( Invalid_Response_Exception::class );
		$this->expectExceptionMessage( 'The response is invalid' );

		$client->check_single( 'https://status-not-numeric.example.com' );
	}

	/**
	 * @testdox The request timeout must be passed to the HTTP client in seconds - the 5000ms setting converts to 5s. (S001)
	 *
	 * @return void
	 */
	public function test_timeout_is_passed_to_http_client_in_seconds() {
		$captured_timeout = null;
		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) use ( &$captured_timeout ) {
				$captured_timeout = $args['timeout'];
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => json_encode( array( 'status' => 200 ) ),
				);
			},
			10,
			3
		);

		( new HTTP_Link_Checker_Client() )->check_single( 'https://timeout-seconds.example.com' );

		$this->assertSame( 5, $captured_timeout );
	}

	/**
	 * @testdox Checking a link whose response holds no redirect location should make a single HTTP request, the repeat query being served from the cache. (S012)
	 *
	 * @return void
	 */
	public function test_check_single_without_redirect_makes_one_request() {
		$request_count = 0;
		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) use ( &$request_count ) {
				++$request_count;
				return array(
					'response' => array( 'code' => 200 ),
					'body'     => json_encode( array( 'status' => 200 ) ),
				);
			},
			10,
			3
		);

		$code = ( new HTTP_Link_Checker_Client() )->check_single( 'https://iawmlf-cache-test.com' );

		$this->assertSame( 200, $code );
		$this->assertSame( 1, $request_count );
	}
}
