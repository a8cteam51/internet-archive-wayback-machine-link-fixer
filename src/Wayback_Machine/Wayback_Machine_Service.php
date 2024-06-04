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
	 * Create a snapshot of a given URL.
	 *
	 * @since 1.2.0
	 *
	 * @param string $url The URL to snapshot.
	 *
	 * @return string The job id.
	 *
	 * @throws Service_Offline_Exception If the service is offline.
	 * @throws Exception If the response is invalid.
	 */
	public function create_snapshot( string $url ): string {
		return $this->snapshot_client->create_snapshot( $url );
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

	/**
	 * Get final url.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return string The final URL.
	 *
	 * @throws \Exception If the service is offline or the response is invalid.
	 */
	public function get_final_url( string $url ): string {
		return $this->link_checker_client->get_final_url( $url );
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
		return $this->link_checker_client->check_single( $url, $additional_params );
	}

	/**
	 * Get snapshot creation status.
	 *
	 * @since 1.2.1
	 *
	 * @param string $ref_code The snapshot reference
	 *
	 * @return array{job_id:string, status:'pending'|'success'|'failure', message:string}
	 *
	 * @throws Exception If the service is offline or the response is invalid.
	 */
	public function get_snapshot_status( string $ref_code ): array {
		return $this->snapshot_client->get_snapshot_status( $ref_code );
	}
}
