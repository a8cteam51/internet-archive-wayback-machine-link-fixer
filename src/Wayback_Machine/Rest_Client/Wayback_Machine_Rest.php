<?php

/**
 * Implementation of the Wayback Machine Client.
 *
 * Uses the public Wayback Machine API to interact with the Wayback Machine.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Rest_Client;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\HTTP_Client\Wayback_Machine_HTTP_Client;

/**
 * The Wayback Machine Rest Client.
 */
class Wayback_Machine_Rest implements Client {

	/**
	 * Checks if a URL is in the Wayback Machine.
	 *
	 * @since 1.2.0
	 *
	 * @param string $url The URL to check.
	 *
	 * @return boolean True if the URL is in the Wayback Machine, false otherwise.
	 */
	public function has_snapshot( string $url ): bool {
		return null !== $this->get_latest_snapshot( $url );
	}

	/**
	 * Gets the latest snapshot for a given URL.
	 *
	 * @since 1.2.0
	 *
	 * @param string $url The URL to check.
	 *
	 * @return array{status:int, available:boolean, url:string, timestamp:string}|null
	 */
	public function get_latest_snapshot( string $url ): ?array {

		// Strip any trailing slash from url.
		$url = rtrim( $url, '/' );

		$api_url = 'https://archive.org/wayback/available';

		// add the url to the query string
		$url = add_query_arg( 'url', \esc_url_raw( $url ), $api_url );

		$response = wp_remote_get( $url );

		return $this->extract_response( $response );
	}

	/**
	 * Gets the closest snapshot to a given date for a given URL.
	 *
	 * @since 1.2.0
	 *
	 * @param string             $url  The URL to check.
	 * @param \DateTimeImmutable $date The date to check.
	 *
	 * @return array{status:int, available:boolean, url:string, timestamp:string}|null
	 */
	public function get_closest_snapshot( string $url, \DateTimeImmutable $date ): ?array {

		// Strip any trailing slash from url.
		$url = rtrim( $url, '/' );

		$api_url = 'https://archive.org/wayback/available';

		// add the url to the query string
		$url = add_query_arg( 'url', \esc_url_raw( $url ), $api_url );

		// add the timestamp to the query string
		$url = add_query_arg( 'timestamp', $date->format( 'Ymd' ), $url );

		$response = wp_remote_get( $url );


		return $this->extract_response( $response );
	}

	/**
	 * Create a snapshot of a given URL.
	 *
	 * @since 1.2.0
	 *
	 * @param string $url The URL to snapshot.
	 *
	 * @return void
	 */
	public function create_snapshot( string $url ): void {
		// Base url.
		$base_url = apply_filters( 'wlf_create_snapshot_base_url', 'https://web.archive.org/save/' );

		// Create the snapshot url.
		$snapshot_url = esc_url( $base_url . $url );

		// Trigger a none blocking wp_get request.
		$r = wp_remote_get(
			$snapshot_url,
			array(
				'timeout'   => 100,
				'blocking'  => false,
				'sslverify' => false,
			)
		);
	}

	/**
	 * Extracts a HTTP response.
	 *
	 * @param array|WP_Error $response The response to extract.
	 *
	 * @return array{status:int, available:boolean, url:string, timestamp:string}|null The extracted response.
	 */
	private function extract_response( $response ): ?array {
		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_body = wp_remote_retrieve_body( $response );

		if ( ! $response_body ) {
			return null;
		}

		$response_body = json_decode( $response_body, true );

		// If not an array.
		if ( ! is_array( $response_body ) ) {
			return null;
		}

		// If response has `archived_snapshots`
		if ( ! \array_key_exists( 'archived_snapshots', $response_body )
		|| empty( $response_body['archived_snapshots'] )
		|| ! \is_array( $response_body['archived_snapshots'] )
		) {
			return null;
		}

		// If response has `closest`
		if ( ! \array_key_exists( 'closest', $response_body['archived_snapshots'] )
		|| empty( $response_body['archived_snapshots']['closest'] )
		|| ! is_array( $response_body['archived_snapshots']['closest'] )
		) {
			return null;
		}

		// If response has `available` and is true.
		if ( ! \array_key_exists( 'available', $response_body['archived_snapshots']['closest'] )
		|| ! $response_body['archived_snapshots']['closest']['available']
		) {
			return null;
		}

		return array(
			'status'    => $response_body['archived_snapshots']['closest']['status'],
			'available' => $response_body['archived_snapshots']['closest']['available'],
			'url'       => $response_body['archived_snapshots']['closest']['url'],
			'timestamp' => $response_body['archived_snapshots']['closest']['timestamp'],
		);
	}
}
