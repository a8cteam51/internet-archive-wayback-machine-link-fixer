<?php

/**
 * The Client interface for the Wayback Machine.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine;

interface Client {

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
	 * @param string $url  The URL to check.
	 * @param \DateTimeImmutable $date The date to check.
	 *
	 * @return array{status:int, available:boolean, url:string, timestamp:string}|null
	 */
	public function get_closest_snapshot( string $url, \DateTimeImmutable $date ): ?array;

	/**
	 * Create a snapshot of a given URL.
	 *
	 * @since 1.2.0
	 *
	 * @param string $url The URL to snapshot.
	 *
	 * @return void
	 */
	public function create_snapshot( string $url ): void;
}
