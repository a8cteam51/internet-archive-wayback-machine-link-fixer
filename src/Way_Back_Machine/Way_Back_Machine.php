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
		$latest_snapshot = $this->client->get_latest_snapshot( $url );

		if ( ! $latest_snapshot ) {
			return null;
		}

		$archive_url = $latest_snapshot['url'];

		$response = wp_remote_get( $archive_url );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_body = wp_remote_retrieve_body( $response );

		if ( ! $response_body ) {
			return null;
		}

		return $response_body;
	}
}
