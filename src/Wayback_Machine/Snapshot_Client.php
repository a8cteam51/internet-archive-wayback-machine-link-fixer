<?php

/**
 * The Client interface for the Wayback Machine.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine;

interface Snapshot_Client {

	/**
	 * Checks if a URL is in the Wayback Machine.
	 *
	 * @since 1.2.0
	 *
	 * @param string $url The URL to check.
	 *
	 * @return boolean True if the URL is in the Wayback Machine, false otherwise.
	 */
	public function has_snapshot( string $url ): bool;

	/**
	 * Gets the latest snapshot for a given URL.
	 *
	 * @since 1.2.0
	 *
	 * @param string $url The URL to check.
	 *
	 * @return array{status:int, available:boolean, url:string, timestamp:string}|null
	 */
	public function get_latest_snapshot( string $url ): ?array;

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
	public function get_closest_snapshot( string $url, \DateTime $date ): ?array;

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
	 * @throws Exceeded_Snapshot_Limit_Exception If the snapshot limit has been exceeded.
	 */
	public function create_snapshot( string $url ): string;

	/**
	 * Get snapshot creation status.
	 *
	 * @since 1.2.1
	 *
	 * @param string $ref_code The snapshot reference.
	 *
	 * @return array{job_id:string, status:'pending'|'success'|'failure', message:string}
	 *
	 * @throws Exception If the service is offline or the response is invalid.
	 */
	public function get_snapshot_status( string $ref_code ): array;

	/**
	 * Checks if the service is online.
	 *
	 * @since 1.3.0
	 *
	 * @return boolean
	 */
	public function is_online(): bool;
}
