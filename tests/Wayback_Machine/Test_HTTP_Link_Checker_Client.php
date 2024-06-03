<?php

/**
 * Tests for HTTP implementation of the Wayback Machine Link Checker Client.
 *
 * @since 1.2.0
 *
 * @coversDefaultClass WPCOMSpecialProjects\Wayback_Link_Fixer\Link_Checker\Link_Checker
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Service_Offline_Exception;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Invalid_Response_Exception;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\HTTP_Client\HTTP_Link_Checker_Client;

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
		remove_all_filters( 'wlf_link_checker_url_base' );
		remove_all_filters( 'wlf_link_checker_url_params' );
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

		$client->check_single( 'https://example.com' );

		// Check the url starts with https://iabot-api.archive.org/livewebcheck
		$this->assertStringStartsWith( 'https://iabot-api.archive.org/livewebcheck', $called_url );

		// Check contains url=https://example.com
		$this->assertStringContainsString( 'url=https://example.com', $called_url );
	}

	/**
	 * @testdox When calling the link checker, it should be possible to pass additional parameters.
	 *
	 * @return void
	 */
	public function test_should_be_able_to_pass_additional_parameters() {
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

		$client->check_single( 'https://example.com', array( 'foo' => 'bar' ) );

		// Check the url starts with https://iabot-api.archive.org/livewebcheck
		$this->assertStringStartsWith( 'https://iabot-api.archive.org/livewebcheck', $called_url );

		// Check contains url=https://example.com
		$this->assertStringContainsString( 'url=https://example.com', $called_url );

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
			'wlf_link_checker_url_base',
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

		$client->check_single( 'https://example.com' );

		// Check we have our custom URL.
		$this->assertStringStartsWith( 'https://anotherurl.someplace.fakeit', $called_url );
	}

	/**
	 * @testdox It should be possible to pass custom url params when checking a single link via a filter.
	 *
	 * @return void
	 */
	public function test_should_be_able_to_set_custom_url_params_via_filter() {
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
			'wlf_link_checker_url_params',
			function ( $url_params ) {
				$url_params['banana'] = 'cherry';
				return $url_params;
			}
		);

		$client->check_single( 'https://example.com' );

		// Check contains foo=bar
		$this->assertStringContainsString( 'banana=cherry', $called_url );
	}

	/**
	 * @testdox When checking a link, if the service is offline, an exception should be thrown.
	 *
	 * @return void
	 */
	public function test_should_throw_exception_if_service_offline() {
		$client = new HTTP_Link_Checker_Client();

		// Mock the response with a none 200 code.
		$this->mock_wp_http_response( array( 'response' => array( 'code' => 404 ) ) );

		$this->expectException( Service_Offline_Exception::class );
		$this->expectExceptionMessage( 'The service is offline. Response:404' );

		$client->check_single( 'https://example.com' );
	}

	/**
	 * @testdox When checking a link, if the response is invalid (no code index in body), an exception should be thrown.
	 *
	 * @return void
	 */
	public function test_should_throw_exception_if_response_invalid_no_code() {
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

		$client->check_single( 'https://example.com' );
	}

	/**
	 * @testdox When checking a link, if the response is invalid (no body), an exception should be thrown.
	 *
	 * @return void
	 */
	public function test_should_throw_exception_if_response_invalid_no_body() {
		$client = new HTTP_Link_Checker_Client();

		// Mock the response without a body.
		$this->mock_wp_http_response( array( 'response' => array( 'code' => 200 ) ) );

		$this->expectException( Invalid_Response_Exception::class );
		$this->expectExceptionMessage( 'The response is invalid.' );

		$client->check_single( 'https://example.com' );
	}

	/**
	 * @testdox It should be possible to resolve a URL to its final destination.
	 *
	 * @return void
	 */
	public function test_should_resolve_url_to_final_destination() {
		$client = new HTTP_Link_Checker_Client();

		// Mock the response without a body.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'location' => 'http://redirect.com' ) ),
			)
		);

		$final = $client->get_final_url( 'https://example.com' );

		$this->assertEquals( 'http://redirect.com', $final );
	}

	/**
	 * @testdox When trying to get the final destination for a link, if there is no location key, return the original URL.
	 *
	 * @return void
	 */
	public function test_should_return_original_url_if_no_location_key() {
		$client = new HTTP_Link_Checker_Client();

		// Mock the response body with a custom url.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'foo' => 'bar' ) ),
			)
		);

		$final = $client->get_final_url( 'https://example.com' );

		$this->assertEquals( 'https://example.com', $final );
	}

	/**
	 * @testdox When checking a link, if a WP_Error is returned, an exception should be thrown.
	 *
	 * @return void
	 */
	public function test_should_throw_exception_if_wp_error() {
		$client = new HTTP_Link_Checker_Client();

		// Mock the response with a WP_Error.
		$this->mock_wp_http_response( new \WP_Error( 'error', 'SomeError' ) );

		$this->expectException( Service_Offline_Exception::class );
		$this->expectExceptionMessage( 'The service is offline. SomeError' );

		$client->check_single( 'https://example.com' );
	}

	/**
	 * @testdox When checking a link, if the response body is not a (json) string, an exception should be thrown.
	 *
	 * @return void
	 */
	public function test_should_throw_exception_if_response_body_not_string() {
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

		$client->check_single( 'https://example.com' );
	}

	/**
	 * @testdox When checking a link, the links HTTP code should be returned.
	 *
	 * @return void
	 */
	public function test_should_return_http_code() {
		$client = new HTTP_Link_Checker_Client();

		// Mock the response body with a custom url.
		$this->mock_wp_http_response(
			array(
				'response' => array( 'code' => 200 ),
				'body'     => json_encode( array( 'status' => 201 ) ),
			)
		);

		$code = $client->check_single( 'https://example.com' );

		$this->assertEquals( 201, $code );
	}

	/**
	 * @testdox When checking a link, an exception should be thrown if the status code is not numeric.
	 *
	 * @return void
	 */
	public function test_should_throw_exception_if_status_code_not_numeric() {
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

		$client->check_single( 'https://example.com' );
	}
}
