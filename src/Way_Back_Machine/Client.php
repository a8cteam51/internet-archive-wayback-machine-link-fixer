<?php

/**
 * Way Back Machine Client
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Way_Back_Machine;

defined( 'ABSPATH' ) || exit;

/**
 * Client
 */
class Client {

	/**
	 * The api url.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $api_url = 'https://archive.org/wayback/available';

	/**
	 * Checks if a given url has any snapshots in the Way Back Machine.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The url to check.
	 *
	 * @return boolean
	 */
	public function has_snapshots( string $url ): bool {
		return null !== $this->get_latest_snapshot( $url );
	}

	/**
	 * Gets the latest snapshot for a given url.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The url to check.
	 *
	 * @return array{status:int, available:boolean, url:string, timestamp:string}|null
	 */
	public function get_latest_snapshot( string $url ): ?array {

		// Filter the url.
		$url = \apply_filters( 'wlf_get_latest_snapshot_url', $url );

		// add the url to the query string
		$url = add_query_arg( 'url', \esc_url_raw( $url ), $this->api_url );

		$response = wp_remote_get( $url );

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
