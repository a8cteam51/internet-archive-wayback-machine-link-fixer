<?php

/**
 * Updates the contents for a Log (post)
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Updater;

use WP_Post;
use Symfony\Component\DomCrawler\Crawler;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Log;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Log Updater
 */
class Log_Processor {

	/**
	 * The Log.
	 *
	 * @since 1.0.0
	 *
	 * @var Log
	 */
	private Log $log;

	/**
	 * The logs post.
	 *
	 * @since 1.0.0
	 *
	 * @var WP_Post|null
	 */
	private ?WP_Post $post = null;

	/**
	 * The content buffer.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private string $content = '';

	/**
	 * Processed links.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, Link>
	 */
	private array $processed_links = array();

	/**
	 * Create instance of Log_Processor.
	 *
	 * @since 1.0.0
	 *
	 * @param Log $log The Log.
	 */
	public function __construct( Log $log ) {
		$this->log = $log;
	}

	/**
	 * Checks that the post exists and can be updated.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function can_update(): bool {
		// Attempt to get the post from the log.
		$this->post = get_post( $this->log->get_post_id() );

		// If the post doesn't exist, we can't update it.
		if ( ! $this->post ) {
			return false;
		}

		return true;
	}


	/**
	 * Checks if it has links which can be updated.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	private function has_links_to_update(): bool {
		// Iterate over the links and check if any have replacements.
		foreach ( $this->log->get_links() as $link ) {
			// Check if only 1 replacement option exists.
			if ( count( $link->get_replacement_options() ) === 1 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Update the content for the post.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function update_content(): bool {

		// If we have no links to update, bail.
		if ( ! $this->has_links_to_update() ) {
			return false;
		}

		// Set the buffer.
		$this->content = $this->post->post_content;

		// Iterate over the links and update the content.
		foreach ( $this->log->get_links() as $link ) {
			$this->update_link( $link );
		}

		return $this->save();
	}

	/**
	 * Get all classes whos links should be skipped.
	 *
	 * @since 1.1.0
	 *
	 * @return array<string>
	 * @todo add filter to allow adding custom classes to skip.
	 */
	private function get_skip_classes(): array {
		return array(
			'wlf-archived',
			'wp-element-button',
			'wlf-archived__redirect',
		);
	}

	/**
	 * Checks if an array of classes contains any of the skipped classes.
	 *
	 * @since 1.1.0
	 *
	 * @param array<string> $classes The classes to check.
	 *
	 * @return boolean
	 */
	private function has_skipped_classes( array $classes ): bool {
		$skip_classes = Settings::get_ignored_classes();
		foreach ( $classes as $class ) {
			if ( in_array( $class, $skip_classes, true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Updates a link within the content.
	 *
	 * @since 1.0.0
	 *
	 * @param Link $link The link to update.
	 *
	 * @return void
	 */
	private function update_link( Link $link ): void {
		// If the link has no replacement or multiple replacements, skip.
		if ( count( $link->get_replacement_options() ) !== 1 ) {
			$this->processed_links[] = $link;
			return;
		}

		// Get the replacement.
		$replacement = $link->get_replacement_options()[0];
		$href        = $link->get_href();

		// HTML Processor.
		$processor = new \WP_HTML_Tag_Processor( $this->content );

		// Iterate through all the links until we find the matching href.
		while ( $processor->next_tag( 'a' ) ) {

			// IF the link already has the class or its a button link, skip.
			if ( $this->has_skipped_classes( iterator_to_array( $processor->class_list() ) ) ) {
				// Add the skipped class to ensure it's not processed again.
				$processor->add_class( 'wlf-archived__skipped' );

				// Skip to the next A tag in while.
				continue;
			}

			if ( \untrailingslashit( $processor->get_attribute( 'href' ) ) === \untrailingslashit( $href ) ) {

				// Add the 'wlf-archived' class to the link.
				$processor->add_class( 'wlf-archived' );

				$processor->set_attribute( 'data-replacement-link', $replacement );

				// Update the content.
				$this->content = $processor->get_updated_html();

				// Attempt to add the archived missing link afterward
				$added_archived_link = $this->add_archived_link( $href, $replacement );

				// Add the note to the link to say updated.
				$link = $link->add_comment(
					sprintf(
						'Found broken link, updated with broken class and %s',
						$added_archived_link
						? 'added archived link ' . esc_html( $replacement )
						: 'no archived link added'
					)
				);

				// Mark the link as fixed
				$link = $link->as_updated();
			}
		}

		// Add the link to the processed links.
		$this->processed_links[] = $link;
		dump(['processed' => $this->processed_links]);
	}

	/**
	 * Add an archived link after the existing link.
	 *
	 * @since 1.1.0
	 *
	 * @param string $broken_link The broken link.
	 * @param string $replacement The replacement link.
	 *
	 * @return boolean If the link was added.
	 */
	private function add_archived_link( string $broken_link, ?string $replacement = null ): bool {

		// If we have no replacement, return false.
		if ( ! $replacement ) {
			return false;
		}

		// Create instance of DOMDocument.
		$doc = new \DOMDocument();
		// Load the content with a temp wrapper
		$doc->loadHtml( '<div id="__WLF_TEMP__">' . $this->content . '</div>' );

		// Create a crawler instance.
		$crawler = new Crawler( $doc );
		$links   = $crawler->filter( 'a' );

		// Iterate through all links and look for a match.
		$found = false;
		foreach ( $links as $link_node ) {
			if ( \untrailingslashit( $broken_link ) === \untrailingslashit( $link_node->attributes->getNamedItem( 'href' )->nodeValue ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$attributes = $link_node->attributes;

				// If the link already has the class, skip.
				if ( $attributes->getNamedItem( 'class' ) && strpos( $attributes->getNamedItem( 'class' )->nodeValue, 'wlf-archived__skipped' ) !== false ) {
					continue;
				}

				// Create the new link to add.
				$new_link = $doc->createElement( 'a', $replacement );
				$new_link->setAttribute( 'href', $replacement );
				$new_link->setAttribute( 'data-old-link', $broken_link );

				// Set link text.
				$new_link->nodeValue = __( 'Archived Link', 'wpcomsp_wayback_link_fixer' );

				// Add class
				$new_link->setAttribute( 'class', 'wlf-archived__redirect' );

				// Insert after the link.
				$link_node->parentNode->insertBefore( $new_link, $link_node->nextSibling );

				// Denote link found.
				$found = true;
			}
		}

		// If we found the link, update the content.
		if ( $found ) {
			// Extract the content between <body><div id="__WLF_TEMP__"> and </div></body>
			$this->content = $crawler->filter( '#__WLF_TEMP__' )->html();
		}

		return $found;
	}

	/**
	 * Update the post_content if any changes made.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	private function save(): bool {
		// If buffer and post content has changed
		if ( $this->content !== $this->post->post_content ) {
			// Update post content.
			wp_update_post(
				array(
					'ID'           => $this->post->ID,
					'post_content' => $this->content,
				),
				false,
				false
			);
			return true;
		}
		return false;
	}

	/**
	 * Get final Log.
	 *
	 * @since 1.0.0
	 *
	 * @return Log
	 */
	public function get_log(): Log {
		dump( array( 'get_log with processed links' => $this->processed_links ) );
		return $this->log->with_links( $this->processed_links );
	}

	/**
	 * Get all processed links.
	 *
	 * @since 1.1.0
	 *
	 * @return Link[]
	 */
	public function get_processed_links(): array {
		return $this->processed_links;
	}
}
