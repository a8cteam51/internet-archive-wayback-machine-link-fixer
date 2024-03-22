<?php

/**
 * Link Checker.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Link_Checker;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;

class Link_Checker {


	/**
	 * Timeout duration.
	 *
	 * @var integer
	 */
	private $timeout;

	/**
	 * Creates a new instance of the Link Checker.
	 */
	public function __construct() {
		$this->timeout = Settings::get_link_checker_timeout();
	}

	/**
	 * Check a link.
	 *
	 * @param string $url The URL to check.
	 *
	 * @return integer the HTTP status code.
	 */
	public function check_single( string $url ): int {
		// Do a simple HEAD request to check the status code.
		$response = wp_remote_head( $url, [ 'timeout' => $this->timeout ] );

		// Get the status code.
		return wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Check a collection of links.
	 *
	 * @param array<string> $urls The URLs to check.
	 *
	 * @return array<integer> The HTTP status codes.
	 */
	public function check_multiple( array $urls ): array{

	}
}
