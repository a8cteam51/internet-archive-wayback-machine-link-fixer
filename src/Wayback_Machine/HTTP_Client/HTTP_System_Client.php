<?php

/**
 * Implementation of the Wayback Machine System Client interface using HTTP requests.
 *
 * Uses the public Wayback Machine API to interact with the Wayback Machine.
 *
 * @since 1.3.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\HTTP_Client;

use Throwable;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\System_Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Invalid_Response_Exception;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Service_Offline_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * HTTP System Client.
 */
class HTTP_System_Client implements System_Client {



	/**
	 * Checks if the Wayback Machine service is online.
	 *
	 * @since 1.3.0
	 *
	 * @return boolean True if the service is online, false otherwise.
	 */
	public function is_online(): bool {
		return true; // Placeholder for actual online check logic.
	}

	/**
	 * Checks if the user account details are valid.
	 *
	 * @since 1.3.0
	 *
	 * @param string $access_key The access key to check.
	 * @param string $secret_key The secret key to check.
	 *
	 * @return boolean True if the user account details are valid, false otherwise.
	 * @throws Service_Offline_Exception If the service is offline.
	 */
	public function is_valid_user( string $access_key, string $secret_key ): bool {
		try {
			$stats = $this->get_user_stats( $access_key, $secret_key );
		} catch ( Throwable $e ) {
			return false;
		}

		return is_array( $stats );
	}

	/**
	 * Get a user's account stats.
	 *
	 * @since 1.3.0
	 *
	 * @param string $access_key The access key to check.
	 * @param string $secret_key The secret key to check.
	 *
	 * @return array{available:int, daily_captures:int, daily_captures_limit:int, processing:int}|null
	 * @throws Service_Offline_Exception If the service is offline.
	 * @throws Invalid_Response_Exception If the response is invalid.
	 */
	public function get_user_stats( string $access_key, string $secret_key ): ?array {

		// Compile the headers.
		$headers                  = array();
		$headers['Authorization'] = sprintf(
			'LOW %s:%s',
			sanitize_text_field( $access_key ),
			sanitize_text_field( $secret_key )
		);
		$headers['Accept']        = 'application/json';

		$url = 'http://web.archive.org/save/status/user?_t=' . time();

		try {
			$response = wp_remote_get( $url, array( 'headers' => $headers ) );
		} catch ( \Exception $e ) {
			throw Invalid_Response_Exception::create( esc_html( $e->getMessage() ) );
		}
		if ( is_wp_error( $response ) ) {
			throw Invalid_Response_Exception::create( esc_html( $response->get_error_message() ) );
		}

		// If we dont have a 200 response, return null.
		if ( ! isset( $response['response']['code'] ) || 200 !== (int) $response['response']['code'] ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );

		// If we dont have a valid body, return null.
		if ( ! is_string( $body ) ) {
			return null;
		}
		// Decode the body.
		$body = json_decode( $body, true );

		// If we dont have a valid body, return null.
		if ( ! is_array( $body ) ) {
			return null;
		}

		return array(
			'available'            => isset( $body['available'] ) ? (int) $body['available'] : 0,
			'daily_captures'       => isset( $body['daily_captures'] ) ? (int) $body['daily_captures'] : 0,
			'daily_captures_limit' => isset( $body['daily_captures_limit'] ) ? (int) $body['daily_captures_limit'] : 0,
			'processing'           => isset( $body['processing'] ) ? (int) $body['processing'] : 0,
		);
	}
}
