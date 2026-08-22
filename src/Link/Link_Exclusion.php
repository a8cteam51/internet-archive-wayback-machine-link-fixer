<?php

/**
 * Handles the exclusion of links.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Link;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the exclusion of links against the built-in and user exclusion lists.
 */
class Link_Exclusion {

	/**
	 * The built-in exclusion patterns.
	 *
	 * @var string[]
	 */
	private $bundled;

	/**
	 * The user settings exclusion patterns.
	 *
	 * @var string[]
	 */
	private $settings;

	/**
	 * Create an instance of the class, loading both exclusion lists.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->bundled  = Settings::get_bundled_link_exclusions();
		$this->settings = Settings::get_link_exclusions();
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
	 * Checks if a given link is excluded by either the built-in or the settings list.
	 *
	 * @param Link         $link    The link to check.
	 * @param integer|null $post_id The post ID to check.
	 *
	 * @return boolean
	 */
	public function is_excluded( Link $link, ?int $post_id = null ): bool {
		$excluded = $this->is_globally_excluded( $link ) || $this->is_settings_excluded( $link );

		return null !== $post_id
			? apply_filters( 'iawmlf_exclude_link_from_post', $excluded, $link, $post_id )
			: $excluded;
	}

	/**
	 * Checks if a link is in the built-in exclusion list.
	 *
	 * @param Link $link The link to check.
	 *
	 * @return boolean
	 */
	public function is_globally_excluded( Link $link ): bool {
		$href = self::canonical_href( $link->get_href() );

		foreach ( $this->bundled as $pattern ) {
			if ( fnmatch( $pattern, $href ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if a link is in the user settings exclusion list.
	 *
	 * @param Link $link The link to check.
	 *
	 * @return boolean
	 */
	public function is_settings_excluded( Link $link ): bool {
		$href = self::canonical_href( $link->get_href() );

		foreach ( $this->settings as $pattern ) {
			if ( fnmatch( $pattern, $href ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Lowercase the scheme and host of a URL, leaving the rest untouched.
	 *
	 * Scheme and host are case-insensitive, so this is canonicalisation. The path and
	 * query are case-sensitive, so lowercasing those would widen what a pattern matches.
	 *
	 * @param string $href The URL to canonicalise.
	 *
	 * @return string
	 */
	private static function canonical_href( string $href ): string {
		$parts = wp_parse_url( $href );

		if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
			return $href;
		}

		$prefix = $parts['scheme'] . '://' . $parts['host'];

		return 0 === strpos( $href, $prefix )
			? strtolower( $prefix ) . substr( $href, strlen( $prefix ) )
			: $href;
	}

	/**
	 * Filters an array of links to remove excluded ones.
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
				return ! $this->is_excluded( $link, $post_id );
			}
		);
	}
}
