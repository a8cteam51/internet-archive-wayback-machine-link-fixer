<?php

/**
 * Migration 3
 *
 * Created: 29 May 2025
 * Iteration: 3
 *
 * @since 1.3.0-RC5
 *
 * Adds a new column to track if a link should be ignored.
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer_Migration;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;
use Internet_Archive\Wayback_Machine_Link_Fixer\Migration\Abstract_Migration;

/**
 * Migration 3
 */
class Migration_3 extends Abstract_Migration {

	/**
	 * Run when the table is created.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function up(): void {
		// Create the report table.
		global $wpdb;

		// Create the link cache table.
		$link_cache_table_name = Settings::get_link_table_name();

		// Add an additional column to track a links status (new, pending, done). Called column state with a text
		// type.
		$wpdb->query(
		 "ALTER TABLE `{$link_cache_table_name}` ADD `archive_process` VARCHAR(36) NOT NULL DEFAULT 'new' AFTER `excluded`;" // phpcs:ignore
		);

		// Clean up the IA links.
		$this->remove_ia_links();
	}


	/**
	 * Run when the table is dropped.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function down(): void {
		// no-op
	}

	/**
	 * Remove all IA links and all associated meta.
	 *
	 * @since 1.3.0-RC5
	 *
	 * @return void
	 */
	public function remove_ia_links(): void {
		$link_repos = new Link_Repository();
		$results    = $link_repos->query_links(
			9999999,
			1,
			array(),
			array(),
			array(),
			'id_desc',
			'://web.archive.org/web/'
		);

		// Iterate over the links and find the posts for them.
		foreach ( $results as $link ) {
			$posts = $link_repos->get_post_ids_from_link_id( $link->get_id() );
			foreach ( $posts as $post_id ) {
				// Remove the link from the post meta.
				$this->remove_link_from_post_meta( $post_id, $link );
			}

			// Delete the link from the database.
			$link_repos->delete_link( $link );
		}
	}

	/**
	 * Removes a given link from meta based on the post.
	 *
	 * @since 1.3.0-RC5
	 *
	 * @param integer $post_id The post ID to remove the link from.
	 * @param Link    $link    The link to remove.
	 *
	 * @return void
	 */
	public function remove_link_from_post_meta( int $post_id, Link $link ): void {
		$links = get_post_meta( $post_id, Settings::LINK_META_KEY, true );

		// If we have no links, return.
		if ( empty( $links ) ) {
			return;
		}

		// Remove the link from the links array.
		$links = array_filter(
			$links,
			static function ( $l ) use ( $link ) {
				return $l !== $link->get_id();
			}
		);

		// If we have no links left, delete the meta.
		if ( empty( $links ) ) {
			delete_post_meta( $post_id, Settings::LINK_META_KEY );
		} else {
			// Update the post meta with the new links.
			update_post_meta( $post_id, Settings::LINK_META_KEY, $links );
		}
	}
}
