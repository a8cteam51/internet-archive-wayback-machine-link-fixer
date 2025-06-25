<?php

/**
 * Scans a block of content for valid links.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Processor;

use DOMDocument;

defined( 'ABSPATH' ) || exit;

/**
 * Content Scanner class.
 */
class Content_Scanner {

	/**
	 * The content.
	 *
	 * @var string
	 */
	private $content;

	/**
	 * The collection of links.
	 *
	 * @var string[]
	 */
	private $links;

	/**
	 * Creates a new instance of the post scanner.
	 *
	 * @param string $content The post content.
	 */
	public function __construct( string $content ) {
		$this->content = $content;
		$this->links   = array();
	}

	/**
	 * Creates a new instance of the post scanner for a given post id.
	 *
	 * @param integer $post_id The post id.
	 *
	 * @return self
	 */
	public static function for_post( int $post_id ): self {
		$content = get_post_field( 'post_content', $post_id );
		return new self( $content );
	}

	/**
	 * Scans the post content for links.
	 *
	 * @return self
	 */
	public function scan(): self {

		// If we have no content, we have no links.
		if ( empty( $this->content ) ) {
			return $this;
		}

		$dom = new \WP_HTML_Tag_Processor( $this->content );

		while ( $dom->next_tag( 'a' ) ) {
			$href = $dom->get_attribute( 'href' );

			// If href doesnt start with http or https, skip.
			if ( ! preg_match( '/^https?:\/\//', $href ?? '' ) ) {
				continue;
			}

			// If this is a valid url, add it to the collection.
			if ( filter_var( $href, FILTER_VALIDATE_URL ) ) {
				$this->links[] = $href;
			}
		}

		// Remove duplicates.
		$this->links = array_unique( $this->links );

		return $this;
	}

	/**
	 * Get the links.
	 *
	 * @return string[]
	 */
	public function get_links(): array {
		// Remove any links that are from the Wayback Machine.
		return array_filter(
			$this->links,
			function ( string $link ): bool {
				return ! wpcomsp_wayback_link_fixer_is_archive_link( $link ) && ! wpcomsp_wayback_link_fixer_is_current_site_link( $link );
			}
		);
	}
}
