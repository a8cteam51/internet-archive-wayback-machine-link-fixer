<?php

/**
 * Link Checker.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Link_Checker;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;

/**
 * Link Checker.
 */
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
		$this->timeout = absint( Settings::get_link_checker_timeout() );
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
		$response = wp_remote_head( $url, array( 'timeout' => $this->timeout ) );

		// Get the status code.
		return wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Check a collection of links.
	 *
	 * @param array<string> $urls The URLs to check.
	 *
	 * @return array<string, integer> The HTTP status codes.
	 */
	public function check_multiple( array $urls ): array {
		return array_reduce(
			$urls,
			function ( $carry, $url ) {
				$carry[ esc_url( $url ) ] = $this->check_single( $url );
				return $carry;
			},
			array()
		);
	}
}
