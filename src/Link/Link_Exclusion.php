<?php

/**
 * Handles the exclusion of links.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Link;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the exclusion of links.
 */
class Link_Exclusion {

	/**
	 * Holds the global exclusions.
	 *
	 * @var string[]
	 */
	private static $exclusions = null;


	/**
	 * Create an instance of the class.
	 *
	 * @return void
	 */
	public function __construct() {
		// If we have not loaded the exclusions, load them.
		if ( null === self::$exclusions ) {
			self::$exclusions = get_option( Settings::LINK_EXCLUSIONS, array() );
		}
	}

	/**
	 * Get an instance of the class.
	 *
	 * @return Link_Exclusion
	 */
	public static function get_instance(): Link_Exclusion {
		return new self();
	}

	/**
	 * Checks if a give link is excluded.
	 *
	 * @param Link         $link    The link to check.
	 * @param integer|null $post_id The post ID to check.
	 *
	 * @return boolean
	 */
	public function is_excluded( Link $link, ?int $post_id = null ): bool {
		return null !== $post_id
			? apply_filters( 'wlf_exclude_link_from_post', $this->is_global_excluded( $link ), $link, $post_id )
			: $this->is_global_excluded( $link );
	}

	/**
	 * Filters an array of links to check for exclusions.
	 *
	 * @param Link[]       $links   The links to check.
	 * @param integer|null $post_id The post ID to check.
	 *
	 * @return Link[]
	 */
	public function filter_excluded( array $links, ?int $post_id = null ): array {
		return array_filter(
			$links,
			function ( Link $link ) use ( $post_id ): bool {
				return ! $this->is_global_excluded( $link, $post_id );
			}
		);
	}

	/**
	 * Checks if a link is excluded from the global exclusions.
	 *
	 * @param Link $link The link to check.
	 *
	 * @return boolean
	 */
	private function is_global_excluded( Link $link ): bool {
		foreach ( self::$exclusions as $ex ) {
			if ( fnmatch( $ex, $link->get_href() ) ) {
				return true;
			}
		}

		return false;
	}
}
