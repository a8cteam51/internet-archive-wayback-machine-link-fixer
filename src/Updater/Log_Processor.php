<?php

/**
 * Updates the contents for a Log (post)
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Updater;

use WP_Post;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Log;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link;

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

		// Include the admin post.php file.
		require_once ABSPATH . 'wp-admin/includes/post.php';

		// If the post is locked, we can't update it.
		if ( wp_check_post_lock( $this->post ) ) {
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

		// Update the content.
		$this->content = str_replace( $href, $replacement, $this->content );

		// Update the link with a description and mark as updated
		$link = $link->add_comment( 'Updated with ' . $replacement )->as_updated();

		// Add the link to the processed links.
		$this->processed_links[] = $link;
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
		return $this->log->with_links( $this->processed_links );
	}
}
