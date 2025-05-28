<?php

/**
 * The Client interface for the Wayback Machine meta and user data.
 *
 * @since 1.3.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine;

defined( 'ABSPATH' ) || exit;

/**
 * System Client interface.
 */
interface System_Client {

	/**
	 * Checks if the Wayback Machine service is online.
	 *
	 * @since 1.3.0
	 *
	 * @return boolean True if the service is online, false otherwise.
	 */
	public function is_online(): bool;

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
	public function is_valid_user( string $access_key, string $secret_key ): bool;

	/**
	 * Get a users account stats.
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
	public function get_user_stats( string $access_key, string $secret_key ): ?array;
}
