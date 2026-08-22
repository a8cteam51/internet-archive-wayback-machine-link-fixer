<?php

/**
 * Scans a block of content for valid links.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Processor;

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
	 * Whether a scan is currently rendering content.
	 *
	 * @var boolean
	 */
	private static $rendering = false;

	/**
	 * Is a scan currently rendering content?
	 *
	 * Lets display-time output (the link payload span) opt out of a scan render.
	 *
	 * @return boolean
	 */
	public static function is_rendering(): bool {
		return self::$rendering;
	}

	/**
	 * Creates a new instance of the post scanner for a given post id.
	 *
	 * @param integer $post_id The post id.
	 *
	 * @return self
	 */
	public static function for_post( int $post_id ): self {
		$content = (string) get_post_field( 'post_content', $post_id );

		return new self( self::render_content( $content, $post_id ) );
	}

	/**
	 * Render blocks and shortcodes so the links they output are visible to the scan.
	 *
	 * The full the_content chain is deliberately not applied - it would pull in links
	 * belonging to other plugins (embeds, related posts, ad injectors). Use the
	 * iawmlf_scan_content filter to widen or replace this.
	 *
	 * @param string  $content The raw post content.
	 * @param integer $post_id The post id.
	 *
	 * @return string
	 */
	private static function render_content( string $content, int $post_id ): string {
		self::$rendering = true;

		// Buffered, so a shortcode that echoes its output is scanned too.
		ob_start();
		try {
			$rendered = do_shortcode( do_blocks( $content ) );
		} catch ( \Throwable $e ) {
			// Third party render callbacks run here - a failing one must not break the save or the scan batch.
			$rendered = $content;
		} finally {
			$echoed          = (string) ob_get_clean();
			self::$rendering = false;
		}

		return (string) apply_filters( 'iawmlf_scan_content', $rendered . $echoed, $content, $post_id );
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
				continue;
			}

			// International URLs (non-ASCII host, path or query) fail the raw check, so retry encoded.
			$encoded = iawmlf_normalize_url( $href );
			if ( filter_var( $encoded, FILTER_VALIDATE_URL ) ) {
				$this->links[] = $encoded;
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
				return ! iawmlf_is_archive_link( $link ) && ! iawmlf_is_current_site_link( $link );
			}
		);
	}
}
