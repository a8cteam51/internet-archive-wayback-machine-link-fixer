<?php

/**
 * Handles the getting and setting of links in the database.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Link;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Processor\Post_Handler;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Archive_Link_Event;

/**
 * Link Response class.
 */
class Link_Repository {

	/**
	 * The table name, taking into account the prefix.
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * The WordPress database object.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * Creates a new instance of the link repository.
	 *
	 * @param string|null $table_name The table name.
	 */
	public function __construct( string $table_name = null ) {
		global $wpdb;

		$this->wpdb       = $wpdb;
		$this->table_name = $table_name ?? Settings::get_link_table_name();
	}

	/**
	 * Find a link by its URL.
	 *
	 * @param string $url The URL to find.
	 *
	 * @return Link|null
	 */
	public function find_by_url( string $url ): ?Link {
		// Query.
		$query = $this->wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE url = %s", $url ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, only table name is interpolated.

		// Get the row.
		$row = $this->wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, is prepared!

		// If no row, return null.
		if ( null === $row ) {
			return null;
		}

		// Map the link.
		return $this->map_link( $row );
	}

	/**
	 * Find s alink based on its ID.
	 *
	 * @param integer $id The link ID.
	 *
	 * @return Link|null
	 */
	public function find_by_id( int $id ): ?Link {

		// Query.
		$query = $this->wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE id = %d", $id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, only table name is interpolated.

		// Get the row.
		$row = $this->wpdb->get_row( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, is prepared!

		// If no row, return null.
		if ( null === $row ) {
			return null;
		}

		// Map the link.
		return $this->map_link( $row );
	}

	/**
	 * Upsert link.
	 *
	 * @param Link $link The link to upsert.
	 *
	 * @return Link
	 *
	 * @throws \Exception If the link cannot be upserted.
	 */
	public function upsert( Link $link ): Link {
		// If the link has an id, update it.
		if ( null !== $link->get_id() ) {
			$link = $this->update( $link );
		} else {
			$link = $this->insert( $link );
		}

		return $link;
	}

	/**
	 * Insert a link.
	 *
	 * @param Link $link The link to insert.
	 *
	 * @return Link
	 *
	 * @throws \Exception If the link cannot be inserted.
	 */
	private function insert( Link $link ): Link {
		// Extract the values.
		$href          = $link->get_href();
		$archived_href = $link->get_archived_href();
		$checks        = $link->get_checks();

		// Json encode the checks.
		$checks = wp_json_encode( $checks );

		// Prepare the insert.
		$result = $this->wpdb->insert(
			$this->table_name,
			array(
				'url'      => $href,
				'archived' => $archived_href,
				'checks'   => $checks,
			),
			array(
				'%s',
				'%s',
				'%s',
			)
		);

		// If the insert failed, throw an exception.
		if ( false === $result ) {
			throw new \Exception( esc_html( 'Failed to insert link: ' . $this->wpdb->last_error ) );
		}

		// Get the last insert id.
		$id = $this->wpdb->insert_id;

		// Set the id on the link.
		return $link->set_id( $id );
	}

	/**
	 * Update an existing link.
	 *
	 * @param Link $link The link to update.
	 *
	 * @return Link
	 *
	 * @throws \Exception If the link cannot be updated.
	 */
	private function update( Link $link ): Link {
		// Extract the values.
		$id            = $link->get_id();
		$href          = $link->get_href();
		$archived_href = $link->get_archived_href();
		$checks        = $link->get_checks();

		// Json encode the checks.
		$checks = wp_json_encode( $checks );

		// Prepare the update.
		$result = $this->wpdb->update(
			$this->table_name,
			array(
				'url'      => $href,
				'archived' => $archived_href,
				'checks'   => $checks,
			),
			array(
				'id' => $id,
			),
			array(
				'%s',
				'%s',
				'%s',
			),
			array(
				'%d',
			)
		);

		// If we dont have a valid row id, throw an exception.
		if ( false === $result ) {
			throw new \Exception( 'Failed to update link.' );
		}

		return $this->find_by_id( $id );
	}

	/**
	 * Find or create a new link.
	 *
	 * @param string $url The URL to find or create.
	 *
	 * @return Link
	 */
	public function find_or_create( string $url ): Link {
		$link = $this->find_by_url( $url );

		if ( null === $link ) {
			$link = new Link( $url );
			$link = $this->upsert( $link );

			// Trigger the event to get the archived link.
			Archive_Link_Event::add_to_queue( $link->get_id() );
		}

		return $link;
	}

	/**
	 * Map a link from a database row.
	 *
	 * @param object $row The database row.
	 *
	 * @return Link
	 */
	private function map_link( object $row ): Link {
		$link = new Link( esc_url( $row->url ) );
		$link->set_id( (int) $row->id )
			->set_archived_href( esc_url( $row->archived ) );

		// Iterate through the checks and add them to the link.
		$checks = json_decode( $row->checks, true );

		if ( is_array( $checks ) ) {
			foreach ( $checks as $check ) {
				$link->add_check( (int) $check['http_code'], esc_attr( $check['date'] ) );
			}
		}

		// Set the broken status.
		if ( 1 === (int) $row->is_broken ) {
			$link->set_broken();
		}

		return $link;
	}

	/**
	 * Get all links for a given post id.
	 *
	 * @param integer $post_id The post id.
	 *
	 * @return Link_Collection
	 */
	public function get_links_for_post( int $post_id ): Link_Collection {
		$collection = new Link_Collection( $post_id );

		// Get from post meta.
		$links = get_post_meta( $post_id, Settings::LINK_META_KEY, true );

		// Get all links.
		$links = array_map(
			function ( int $link_id ): ?Link {
				return $this->find_by_id( $link_id );
			},
			$links
		);

		// Remove any nulls.
		$links = array_filter( $links );

		foreach ( $links as $link ) {
			$collection->add( $link );
		}

		return $collection;
	}
}
