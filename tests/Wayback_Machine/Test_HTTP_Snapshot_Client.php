<?php

/**
 * Tests for HTTP implementation of the Wayback Machine Snapshot Client.
 *
 * @since 1.2.0
 *
 * @coversDefaultClass WPCOMSpecialProjects\Wayback_Link_Fixer\Link_Checker\HTTP_Snapshot_Client
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Tests;

use DateTime;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\HTTP_Client\HTTP_Snapshot_Client;

/**
 * Test class for HTTP_Snapshot_Client.
 */
class Test_HTTP_Snapshot_Client extends \WP_UnitTestCase {

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
			10,
			3
		);
	}

	/**
	 * @testdox Any urls which is used to find a snapshot, should have any trailing slash stripped.
	 *
	 * @return void
	 */
	public function test_should_strip_tailing_slash_from_url() {
		if ( $GLOBALS['wpcomsp_wayback_link_fixer_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) {
				$this->assertStringEndsWith( 'http%3A%2F%2Fexample.com', $url );
				return new \WP_Error( 'http_request_failed', 'Error' );
			},
			10,
			3
		);

		$client = new HTTP_Snapshot_Client();
		$client->get_latest_snapshot( 'http://example.com/' );
	}

	/**
	 * @testdox It should be possible to use a filter to change the baseURL which is used to find a snapshot.
	 *
	 * @return void
	 */
	public function test_should_use_filter_to_change_base_url_for_find() {
		if ( $GLOBALS['wpcomsp_wayback_link_fixer_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) {
				$this->assertStringStartsWith( 'http://snashot.com', $url );
				return new \WP_Error( 'http_request_failed', 'Error' );
			},
			10,
			3
		);

		add_filter(
			'wlf_find_snapshot_base_url',
			function ( $url ) {
				return 'http://snashot.com';
			}
		);

		$client = new HTTP_Snapshot_Client();
		$client->get_latest_snapshot( 'http://example.com/' );
	}

	/**
	 * @testdox It should be possible to use a filter to change the url which is used to find the latest snapshot.
	 *
	 * @return void
	 */
	public function test_should_use_filter_to_change_url_for_find_latest_snapshot() {
		if ( $GLOBALS['wpcomsp_wayback_link_fixer_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) {
				$this->assertEquals( 'http://custom-snapshot.com', $url );
				return new \WP_Error( 'http_request_failed', 'Error' );
			},
			10,
			3
		);

		add_filter(
			'wlf_get_latest_snapshot_url',
			function ( $url ) {
				return 'http://custom-snapshot.com';
			}
		);

		$client = new HTTP_Snapshot_Client();
		$client->get_latest_snapshot( 'http://example.com/' );
	}

	/**
	 * @testdox When getting the latest snapshot, only fully formed responses will be used.
	 *
	 * @dataProvider latest_snapshot_response_provider
	 *
	 * @param array|\WP_Error $response
	 * @param boolean         $is_valid_response
	 */
	public function test_should_only_use_fully_formed_responses( $response, $is_valid_response ) {
		if ( $GLOBALS['wpcomsp_wayback_link_fixer_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		$this->mock_wp_http_response( $response );

		$client = new HTTP_Snapshot_Client();
		$result = $client->get_latest_snapshot( 'http://example.com' );

		$this->assertEquals( $is_valid_response, is_array( $result ) );
	}

	/**
	 * Data provider for test_should_only_use_fully_formed_responses.
	 *
	 * @return array
	 */
	public static function latest_snapshot_response_provider() {
		return array(
			'invalid body'               => array( array( 'body' => 'not json' ), false ),
			'missing archived_snapshots' => array( array( 'body' => json_encode( array( 'url' => 'http://example.com' ) ) ), false ),
			'empty archived_snapshots'   => array(
				array(
					'body' => json_encode(
						array(
							'url'                => 'http://example.com',
							'archived_snapshots' => array(),
						)
					),
				),
				false,
			),
			'empty closest'              => array(
				array(
					'body' => json_encode(
						array(
							'url'                => 'http://example.com',
							'archived_snapshots' => array( 'closest' => array() ),
						)
					),
				),
				false,
			),
			'empty available'            => array(
				array(
					'body' => json_encode(
						array(
							'url'                => 'http://example.com',
							'archived_snapshots' => array( 'closest' => array() ),
						)
					),
				),
				false,
			),
			'available false'            => array(
				array(
					'body' => json_encode(
						array(
							'url'                => 'http://example.com',
							'archived_snapshots' => array( 'closest' => array( 'available' => false ) ),
						)
					),
				),
				false,
			),
			'valid response'             => array(
				array(
					'body' => json_encode(
						array(
							'url'                => 'http://example.com',
							'archived_snapshots' => array(
								'closest' => array(
									'available' => true,
									'status'    => 200,
									'url'       => 'http://archived.url',
									'timestamp' => '20240422041157',
								),
							),
						)
					),
				),
				true,
			),
		);
	}

	/**
	 * @testdox It should be possible to get a snapshot based on a timestamp.
	 *
	 * @return void
	 */
	public function test_should_get_snapshot_based_on_timestamp() {
		if ( $GLOBALS['wpcomsp_wayback_link_fixer_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) {
				// Check url has timestamp={Ymd}
				$this->assertStringContainsString( 'timestamp=20240422', $url );
				return new \WP_Error( 'http_request_failed', 'Error' );
			},
			10,
			3
		);

		$client = new HTTP_Snapshot_Client();
		$client->get_closest_snapshot(
			'http://example.com',
			\DateTime::createFromFormat( 'd/m/Y', '22/04/2024' )
		);
	}

	/**
	 * @testdox It should be possible to use a filter to change the url which is used to get a snapshot based on a timestamp.
	 *
	 * @return void
	 */
	public function test_should_use_filter_to_change_url_for_get_closest_snapshot() {
		if ( $GLOBALS['wpcomsp_wayback_link_fixer_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) {
				$this->assertEquals( 'http://custom-snapshot.com?url=http%3A%2F%2Fexample.com&timestamp=20240422', $url );
				return new \WP_Error( 'http_request_failed', 'Error' );
			},
			10,
			3
		);

		add_filter(
			'wlf_get_closest_snapshot_url',
			function ( $url, $queried_url, $timestamp ) {
				return 'http://custom-snapshot.com?url=' . $queried_url . '&timestamp=' . $timestamp->format( 'Ymd' );
			},
			10,
			4
		);

		$client = new HTTP_Snapshot_Client();
		$client->get_closest_snapshot(
			'http://example.com',
			\DateTime::createFromFormat( 'd/m/Y', '22/04/2024' )
		);
	}

	/**
	 * @testdox It should be possible to create a snapshot of a given URL.
	 *
	 * @return void
	 */
	public function test_should_create_snapshot() {
		if ( $GLOBALS['wpcomsp_wayback_link_fixer_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) {
				$this->assertStringStartsWith( 'https://web.archive.org/save', $url );
				$this->assertArrayHasKey( 'body', $args );
				$this->assertArrayHasKey( 'url', $args['body'] );
				$this->assertEquals( 'http%3A%2F%2Fexample.com', $args['body']['url'] );

				// Mock a valid response.
				return array(
					'body'     => 'spn.watchJob("some id")',
					'response' => array( 'code' => 200 ),
				);

			},
			10,
			3
		);

		$client = new HTTP_Snapshot_Client();
		$client->create_snapshot( 'http://example.com' );
	}

	/**
	 * @testdox It should be possible to use a filter to change the url which is used to create a snapshot.
	 *
	 * @return void
	 */
	public function test_should_use_filter_to_change_url_for_create_snapshot() {
		if ( $GLOBALS['wpcomsp_wayback_link_fixer_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) {
				$this->assertEquals( 'http://custom-snapshot.com/', $url );
				// Mock a valid response.
				return array(
					'body'     => 'spn.watchJob("some id")',
					'response' => array( 'code' => 200 ),
				);
			},
			10,
			3
		);

		add_filter(
			'wlf_create_snapshot_url',
			function ( $queried_url ) {
				return 'http://custom-snapshot.com/';
			}
		);

		$client = new HTTP_Snapshot_Client();
		$client->create_snapshot( 'http://example.com' );
	}

	/**
	 * @testdox It should be possible to check if a url has a snapshot in the Wayback Machine.
	 *
	 * @return void
	 */
	public function test_should_check_if_url_has_snapshot_valid() {
		if ( $GLOBALS['wpcomsp_wayback_link_fixer_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) {
				return array(
					'body' => json_encode(
						array(
							'url'                => 'http://example.com',
							'archived_snapshots' => array(
								'closest' => array(
									'available' => true,
									'status'    => 200,
									'url'       => 'http://archived.url',
									'timestamp' => '20240422041157',
								),
							),
						)
					),
				);
			},
			10,
			3
		);

		$client = new HTTP_Snapshot_Client();
		$this->assertTrue( ( $client->has_snapshot( 'http://example.com' ) ) );
	}

	/**
	 * @testdox It should be possible to check if a url has a snapshot in the Wayback Machine.
	 *
	 * @return void
	 */
	public function test_should_check_if_url_has_snapshot_invalid() {
		if ( $GLOBALS['wpcomsp_wayback_link_fixer_skip_live_api_tests'] === true ) {
			$this->markTestSkipped( 'Skipping live API tests' );
		}

		add_filter(
			'pre_http_request',
			function ( $response, $args, $url ) {
				return new \WP_Error( 'http_request_failed', 'Error' );
			},
			10,
			3
		);

		$client = new HTTP_Snapshot_Client();
		$this->assertFalse( ( $client->has_snapshot( 'http://example.com' ) ) );
	}
}
