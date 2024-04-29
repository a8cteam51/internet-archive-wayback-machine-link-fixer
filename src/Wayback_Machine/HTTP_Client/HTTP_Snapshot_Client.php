<?php

/**
 * Implementation of the Wayback Machine Client.
 *
 * Uses the public Wayback Machine API to interact with the Wayback Machine.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\HTTP_Client;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Snapshot_Client;

/**
 * The Wayback Machine Rest Client.
 */
class HTTP_Snapshot_Client implements Snapshot_Client {

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
	 * Get the base url for finding a snapshot.
	 *
	 * @since 1.2.0
	 *
	 * @return string The base url.
	 */
	public function get_base_url(): string {
		return \trailingslashit(
			\untrailingslashit(
				apply_filters( 'wlf_find_snapshot_base_url', 'https://archive.org/wayback/available/' )
			)
		);
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
		$url = untrailingslashit( $url );

		$api_url = $this->get_base_url();

		// add the url to the query string
		$url = add_query_arg( 'url', \esc_url_raw( $url ), $api_url );

		$url = apply_filters( 'wlf_get_latest_snapshot_url', $url, $api_url );

		$response = wp_remote_get( $url );

		return $this->extract_response( $response );
	}

	/**
	 * Gets the closest snapshot to a given date for a given URL.
	 *
	 * @since 1.2.0
	 *
	 * @param string    $url  The URL to check.
	 * @param \DateTime $date The date to check.
	 *
	 * @return array{status:int, available:boolean, url:string, timestamp:string}|null
	 */
	public function get_closest_snapshot( string $url, \DateTime $date ): ?array {

		// Strip any trailing slash from url.
		$url = untrailingslashit( $url );

		$api_url = $this->get_base_url();

		// add the url to the query string
		$api_url = add_query_arg( 'url', \esc_url_raw( $url ), $api_url );

		// add the timestamp to the query string
		$api_url = add_query_arg( 'timestamp', $date->format( 'Ymd' ), $api_url );

		// Allow the url to be filtered.
		$api_url = apply_filters( 'wlf_get_closest_snapshot_url', $api_url, $url, $date );

		$response = wp_remote_get( $api_url );

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

		// Strip any trailing slash from url.
		$url = untrailingslashit( $url );

		$base_url = 'https://web.archive.org/save/';

		// Base url.
		$snapshot_url = apply_filters( 'wlf_create_snapshot_url', esc_url( $base_url . $url ), $base_url, $url );

		// Trigger a none blocking wp_get request.
		wp_remote_get(
			esc_url( $snapshot_url ),
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
