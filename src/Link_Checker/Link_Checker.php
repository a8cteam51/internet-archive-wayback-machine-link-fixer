<?php

/**
 * Link Checker.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Link_Checker;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;

/**
 * Link Checker.
 */
class Link_Checker {


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
		// $this->timeout = 999999;
	}

	/**
	 * Check a link.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return integer the HTTP status code.
	 *
	 * @throws Exception If the service is offline or the response is invalid.
	 */
	public function check_single( string $url ): int {

		// Compile the url for the livewebcheck service.
		$url_params = array(
			'url' => esc_url( $url ),
		);

		// Filter the url params.
		$url_params = apply_filters( 'wayback_link_fixer_check_url_params', $url_params );

		$query_url = add_query_arg(
			$url_params,
			'https://iabot-api.archive.org/livewebcheck'
		);

		// Do a simple HEAD request to check the status code.
		$response = wp_remote_get( $query_url, array( 'timeout' => $this->timeout ) );

		// If we dont have a 200 response, service may be offline, throw exception.
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			throw new \Exception( 'Service is offline' );
		}

		// Unpack the body.
		$body = wp_remote_retrieve_body( $response );

		// If we dont have a valid body, throw exception.
		if ( ! is_string( $body ) ) {
			throw new \Exception( 'Invalid response body' );
		}

		// Decode the body.
		$body = json_decode( $body, true );

		// If we dont have a valid body, throw exception.
		if ( ! is_array( $body ) ) {
			throw new \Exception( 'Invalid response body' );
		}

		// If we dont have a status code, throw exception.
		if ( ! isset( $body['status'] ) ) {
			throw new \Exception( 'Invalid response body' );
		}

		$code = absint( $body['status'] );

		// If we dont have a valid status code, throw exception.
		if ( ! $code ) {
			throw new \Exception( 'Invalid response body' );
		}

		return $code;
	}

	/**
	 * Check a collection of links.
	 *
	 * @param array<string> $urls The URLs to check.
	 *
	 * @return array<string, integer> The HTTP status codes.
	 */
	public function check_multiple( array $urls ): array {
		return array_reduce(
			$urls,
			function ( $carry, $url ) {
				try {
					$carry[ esc_url( $url ) ] = $this->check_single( $url );
				} catch ( \Throwable $th ) {
					return $carry;
				}

				return $carry;
			},
			array()
		);
	}
}
