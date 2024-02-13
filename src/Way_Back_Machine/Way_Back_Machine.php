<?php

/**
 * Way Back Machine Service
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Way_Back_Machine;

defined( 'ABSPATH' ) || exit;

/**
 * Service
 */
class Way_Back_Machine {

	/**
	 * The client.
	 *
	 * @var Client
	 */
	private Client $client;

	/**
	 * Create instance of Way_Back_Machine.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->client = new Client();
	}

	/**
	 * Get the content from an archived version of a given url.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The url to get the content from.
	 *
	 * @return string|null
	 */
	public function get_content( string $url ): ?string {
		// Ensure the url has a trailing slash.
		$url = trailingslashit( untrailingslashit( $url ) );

		$latest_snapshot = $this->client->get_latest_snapshot( $url );

		if ( ! $latest_snapshot ) {
			return null;
		}

		$archive_url = esc_url( $latest_snapshot['url'] );

		// Get the link prefix
		$prefix = sprintf( 'http://web.archive.org/web/%s/', $latest_snapshot['timestamp'] );

		$response = wp_remote_get( $archive_url );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_body = wp_remote_retrieve_body( $response );

		if ( ! $response_body ) {
			return null;
		}

		// Remove all instances of the prefix from the response body.
		$response_body = str_replace( $prefix, '', $response_body );

		return $response_body;
	}

	/**
	 * Attempt to find an arcihved version of a given url.
	 *
	 * @since 1.1.0
	 *
	 * @param string $url The url to find the archived version of.
	 *
	 * @return string|null
	 */
	public function find_archive( string $url ): ?string {
		$latest_snapshot = $this->client->get_latest_snapshot( $url );

		if ( ! $latest_snapshot ) {
			return null;
		}

		return esc_url( $latest_snapshot['url'] );
	}
}
