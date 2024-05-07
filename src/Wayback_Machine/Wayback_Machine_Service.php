<?php

/**
 * Way Back Machine Service
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine;

defined( 'ABSPATH' ) || exit;

/**
 * Service
 */
class Wayback_Machine_Service {

	/**
	 * The snapshot client.
	 *
	 * @var Snapshot_Client
	 */
	private $snapshot_client;

	/**
	 * The Link Checker Client.
	 *
	 * @var Link_Checker_Client
	 */
	private $link_checker_client;


	/**
	 * Creates an instance of the Wayback Machine Client.
	 */
	public function __construct() {
		$this->snapshot_client     = wpcomsp_wayback_link_fixer_get_snapshot_client();
		$this->link_checker_client = wpcomsp_wayback_link_fixer_get_link_checker_client();
	}

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
		return $this->snapshot_client->has_snapshot( $url );
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
		return $this->snapshot_client->get_latest_snapshot( $url );
	}

	/**
	 * Creates a snapshot of a given url.
	 *
	 * @since 1.2.0
	 *
	 * @param string $url The url to create a snapshot of.
	 *
	 * @return void
	 */
	public function create_snapshot( string $url ): void {
		$this->snapshot_client->create_snapshot( $url );
	}

	/**
	 * Attempt to find an archived version of a given url.
	 *
	 * @since 1.1.0
	 *
	 * @param string $url The url to find the archived version of.
	 *
	 * @return string|null
	 */
	public function find_archive( string $url ): ?string {
		$latest_snapshot = $this->snapshot_client->get_latest_snapshot( $url );

		if ( ! $latest_snapshot ) {
			return null;
		}

		return esc_url( $latest_snapshot['url'] ?? '' );
	}
}
