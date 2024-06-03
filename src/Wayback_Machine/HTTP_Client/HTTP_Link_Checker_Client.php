<?php

/**
 * Link Checker.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\HTTP_Client;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Link_Checker_Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Service_Offline_Exception;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Invalid_Response_Exception;

/**
 * Link Checker.
 */
class HTTP_Link_Checker_Client implements Link_Checker_Client {


	/**
	 * Timeout duration.
	 *
	 * @var integer
	 */
	private $timeout;

	/**
	 * Creates a new instance of the Link Checker.
	 */
	public function __construct() {
		$this->timeout = absint( Settings::get_link_checker_timeout() );
	}

	/**
	 * Get final url.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return string The final URL.
	 *
	 * @throws Exception If the service is offline or the response is invalid.
	 */
	public function get_final_url( string $url ): string {
		$response = $this->get_decoded_response( $this->query_url( $url ) );

		// Return the location if it exists, otherwise return the original url.
		return isset( $response['location'] )
			? esc_url( $response['location'] ?? '' )
			: $url;
	}

	/**
	 * Query URL.
	 *
	 * @param string $url               The URL to check.
	 * @param array  $additional_params Additional parameters to pass to the service.
	 *
	 * @return array|WP_Error The Response.
	 */
	private function query_url( string $url, array $additional_params = array() ): array {

		// Compile the url for the live web check service.
		$url_params = array(
			'url'         => esc_url( $url ?? '' ),
			'impersonate' => 1,
		);

		$url_params = wp_parse_args( $additional_params, $url_params );

		$query_url = add_query_arg(
			apply_filters( 'wlf_link_checker_url_params', $url_params ),
			apply_filters( 'wlf_link_checker_url_base', 'https://iabot-api.archive.org/livewebcheck' )
		);

		// Get the response.
		$response = wp_remote_get( $query_url, array( 'timeout' => $this->timeout ) );

		// If we dont have a valid response, throw exception
		if ( is_wp_error( $response ) ) {
			throw Service_Offline_Exception::create( esc_attr( $response->get_error_message() ) );
		}

		return $response;
	}

	/**
	 * Get the decoded response.
	 *
	 * @param array $response The response.
	 *
	 * @return array The decoded response.
	 *
	 * @throws Exception If the response is invalid.
	 */
	private function get_decoded_response( array $response ): array {
		// If we dont have a 200 response, service may be offline, throw exception.
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			throw Service_Offline_Exception::create( esc_attr( 'Response:' . wp_remote_retrieve_response_code( $response ) ) );
		}

		// Unpack the body.
		$body = wp_remote_retrieve_body( $response );

		// If we dont have a valid body, throw exception.
		if ( ! is_string( $body ) ) {
			throw Invalid_Response_Exception::create();
		}

		// Decode the body.
		$body = json_decode( $body, true );

		// If we dont have a valid body, throw exception.
		if ( ! is_array( $body ) ) {
			throw Invalid_Response_Exception::create();
		}

		return $body;
	}

	/**
	 * Check a link.
	 *
	 * @param string               $url               The URL to check.
	 * @param array<string, mixed> $additional_params Additional parameters to pass to the service.
	 *
	 * @return integer the HTTP status code.
	 *
	 * @throws Exception If the service is offline or the response is invalid.
	 */
	public function check_single( string $url, array $additional_params = array() ): int {

		// Get the redirected URL if it exists.
		$url = $this->get_final_url( $url );

		$response = $this->get_decoded_response( $this->query_url( $url, $additional_params ) );

		// If we dont have a status code, throw exception.
		if ( ! isset( $response['status'] ) ) {
			throw Invalid_Response_Exception::create();
		}

		$code = \sanitize_text_field( $response['status'] );

		// If we dont have a valid status code, throw exception.
		if ( ! is_numeric( $code ) ) {
			throw Invalid_Response_Exception::create();
		}

		return absint( $code );
	}
}
