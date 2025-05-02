<?php

/**
 * Interface for the Link Checker Client.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine;

defined( 'ABSPATH' ) || exit;

/**
 * Link Checker Client interface.
 */
interface Link_Checker_Client {

	/**
	 * Get final url.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return string The final URL.
	 *
	 * @throws \Exception If the service is offline or the response is invalid.
	 */
	public function get_final_url( string $url ): string;

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
	public function check_single( string $url, array $additional_params = array() ): int;

	/**
	 * Checks if the service is online.
	 *
	 * @since 1.3.0
	 *
	 * @return boolean
	 */
	public function is_online(): bool;
}
