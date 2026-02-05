<?php

/**
 * Handles the getting and setting of links in the database.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Link;

use DateTime;
use Exception;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Find_Or_Create_Snapshot_Event;

defined( 'ABSPATH' ) || exit;

/**
 * Link Response class.
 */
class Link_Repository {

	public const LINK_STATUS_BROKEN       = 1;
	public const LINK_STATUS_OK           = 0;
	public const LINK_HAS_ARCHIVE         = 1;
	public const LINK_NO_ARCHIVE          = 0;
	public const LINK_IS_EXCLUDED         = 1;
	public const LINK_NOT_EXCLUDED        = 0;
	public const ORDER_DATE_ASC           = 'date_asc';
	public const ORDER_DATE_DESC          = 'date_desc';
	public const ORDER_ID_ASC             = 'id_asc';
	public const ORDER_ID_DESC            = 'id_desc';
	public const ORDER_URL_ASC            = 'url_asc';
	public const ORDER_URL_DESC           = 'url_desc';
	public const ORDER_HAS_ARCHIVE_DESC   = 'has_archive_desc';
	public const ORDER_HAS_ARCHIVE_ASC    = 'has_archive_asc';
	public const ORDER_LINK_HEALTH_DESC   = 'link_health_desc';
	public const ORDER_LINK_HEALTH_ASC    = 'link_health_asc';
	public const ORDER_LINK_EXCLUDED_DESC = 'link_excluded_desc';
	public const ORDER_LINK_EXCLUDED_ASC  = 'link_excluded_asc';
	public const ORDER_LINK_CHECKS_DESC   = 'link_checks_desc';
	public const ORDER_LINK_CHECKS_ASC    = 'link_checks_asc';

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
	public function __construct( ?string $table_name = null ) {
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
		$query = $this->wpdb->prepare( "SELECT * FROM {$this->table_name} WHERE url = %s OR redirect_url = %s", $url, $url ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, only table name is interpolated.

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
		$href            = $link->get_href();
		$archived_href   = $link->get_archived_href();
		$checks          = $link->get_checks();
		$redirect_href   = $link->get_redirect_href();
		$is_broken       = $link->is_broken();
		$message         = $link->get_message();
		$is_excluded     = $link->is_excluded();
		$archive_process = $link->get_archive_process();

		// Json encode the checks.
		$checks = wp_json_encode( $checks );

		// Prepare the insert.
		$result = $this->wpdb->insert(
			$this->table_name,
			array(
				'url'             => $href,
				'archived'        => $archived_href,
				'checks'          => $checks,
				'redirect_url'    => $redirect_href,
				'is_broken'       => $is_broken,
				'message'         => $message,
				'excluded'        => $is_excluded ? 1 : 0,
				'archive_process' => $archive_process,
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
				'%d',
				'%s',
			)
		);

		// If the insert failed, throw an exception.
		if ( false === $result ) {
			throw new Exception( esc_html( 'Failed to insert link: ' . $this->wpdb->last_error ) );
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
	 * @throws Exception If the link cannot be updated.
	 */
	private function update( Link $link ): Link {
		// Extract the values.
		$id              = $link->get_id();
		$href            = $link->get_href();
		$archived_href   = $link->get_archived_href();
		$checks          = $link->get_checks();
		$redirect_href   = $link->get_redirect_href();
		$is_broken       = $link->is_broken();
		$message         = $link->get_message();
		$is_excluded     = $link->is_excluded();
		$archive_process = $link->get_archive_process();

		// Json encode the checks.
		$checks = wp_json_encode( $checks );

		// Prepare the update.
		$result = $this->wpdb->update(
			$this->table_name,
			array(
				'url'             => $href,
				'archived'        => $archived_href,
				'checks'          => $checks,
				'redirect_url'    => $redirect_href,
				'is_broken'       => $is_broken,
				'message'         => $message,
				'excluded'        => $is_excluded ? 1 : 0,
				'archive_process' => $archive_process,
			),
			array(
				'id' => $id,
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
				'%d',
				'%s',
			),
			array(
				'%d',
			)
		);

		// If we dont have a valid row id, throw an exception.
		if ( false === $result ) {
			throw new Exception( 'Failed to update link.' );
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

		// Strip any trailing slashes.
		$url = untrailingslashit( $url );

		$link = $this->find_by_url( $url );

		if ( null === $link ) {
			$link = $this->upsert( new Link( $url ) );

			// Trigger the event to get the archived link.
			Find_Or_Create_Snapshot_Event::add_to_queue( $link->get_id() );
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
		$link = new Link( $row->url ?? '' );
		$link
			->set_id( (int) $row->id )
			->set_archived_href( $row->archived ?? '' )
			->set_redirect_href( $row->redirect_url ?? '' )
			->set_message( esc_attr( $row->message ?? '' ) )
			->set_excluded( (bool) $row->excluded );

		// Set archive process status
		if ( isset( $row->archive_process ) ) {
			switch ( $row->archive_process ) {
				case Link::PROCESS_PENDING:
					$link->set_pending();
					break;
				case Link::PROCESS_DONE:
					$link->set_done();
					break;
				default:
					$link->set_new();
			}
		}

		// Iterate through the checks and add them to the link.
		$checks = json_decode( $row->checks, true );

		if ( is_array( $checks ) ) {
			foreach ( $checks as $check ) {
				$code = array_key_exists( 'http_code', $check ) ? (int) $check['http_code'] : 0;
				$date = array_key_exists( 'date', $check ) ? esc_attr( $check['date'] ) : null;

				$link->add_check( $code, $date );
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
	 * @param integer $post_id          The post id.
	 * @param boolean $exclude_excluded Whether to exclude excluded links.
	 *
	 * @return Link_Collection
	 */
	public function get_links_for_post( int $post_id, bool $exclude_excluded = false ): Link_Collection {
		$collection = new Link_Collection( $post_id );

		// Get from post meta.
		$links = get_post_meta( $post_id, Settings::LINK_META_KEY, true );

		// If not an array or empty, returnc ollection.
		if ( ! is_array( $links ) || empty( $links ) ) {
			return $collection;
		}

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
			if ( $exclude_excluded && Link_Exclusion::get_instance()->is_excluded( $link, $post_id ) ) {
				continue;
			}

			$collection->add( $link );
		}

		return $collection;
	}

	/**
	 * Query the database for links.
	 *
	 * @since 1.2.0
	 *
	 * @param integer       $limit            The limit of links to return.
	 * @param integer       $page             The page of links to return.
	 * @param array         $status           The status of the links to return.
	 * @param array         $link_ids         The link ids to query.
	 * @param array         $archive_status   The archive status of the links to return.
	 * @param string        $order_by         The order by.
	 * @param string|NULL   $search_term      The search term to query.
	 * @param string|NULL   $date             The date of the links to return (yy-mm).
	 * @param boolean|NULL  $excluded         Whether to return excluded links.
	 * @param string[]|NULL $snapshot_process The snapshot status of the links to return.
	 * @param boolean|NULL  $has_checks       Whether to return links with or without checks.
	 *
	 * @return Link[]
	 */
	public function query_links(
		int $limit = 10,
		int $page = 1,
		array $status = array(),
		array $link_ids = array(),
		array $archive_status = array(),
		string $order_by = self::ORDER_DATE_DESC,
		?string $search_term = null,
		?string $date = null,
		?bool $excluded = null,
		?array $snapshot_process = null,
		?bool $has_checks = null
	): array {
		$rows = $this->perform_query( $limit, $page, $status, $link_ids, $archive_status, $order_by, $search_term, $date, $excluded, $snapshot_process, $has_checks );
		return array_map( array( $this, 'map_link' ), $rows );
	}

	/**
	 * Gets the count of links for a given query.
	 *
	 * @param integer       $limit            The limit of links to return.
	 * @param integer       $page             The page of links to return.
	 * @param array         $status           The status of the links to return.
	 * @param array         $link_ids         The link ids to query.
	 * @param array         $archive_status   The archive status of the links to return.
	 * @param string        $order_by         The order by.
	 * @param string|NULL   $search_term      The search term to query.
	 * @param string|NULL   $date             The date of the links to return (yy-mm).
	 * @param boolean|NULL  $excluded         Whether to return excluded links.
	 * @param string[]|NULL $snapshot_process The snapshot status of the links to return.
	 * @param boolean|NULL  $has_checks       Whether to return links with or without checks.
	 *
	 * @return integer
	 */
	public function count_links(
		int $limit = 10,
		int $page = 1,
		array $status = array(),
		array $link_ids = array(),
		array $archive_status = array(),
		string $order_by = self::ORDER_DATE_DESC,
		?string $search_term = null,
		?string $date = null,
		?bool $excluded = null,
		?array $snapshot_process = null,
		?bool $has_checks = null
	): int {
		return count( $this->perform_query( $limit, $page, $status, $link_ids, $archive_status, $order_by, $search_term, $date, $excluded, $snapshot_process, $has_checks ) );
	}

	/**
	 * Performs a link query.
	 *
	 * @param integer       $limit            The limit of links to return.
	 * @param integer       $page             The page of links to return.
	 * @param array         $status           The status of the links to return.
	 * @param array         $link_ids         The link ids to query.
	 * @param array         $archive_status   The archive status of the links to return.
	 * @param string        $order_by         The order by.
	 * @param string|NULL   $search_term      The search term to query.
	 * @param string|NULL   $date             The date of the links to return (yy-mm).
	 * @param boolean|NULL  $excluded         Whether to return excluded links.
	 * @param string[]|NULL $snapshot_process The snapshot status of the links to return.
	 * @param boolean|NULL  $has_checks       Whether to return links with or without checks.
	 *
	 * @return stdClass[]
	 */
	private function perform_query(
		int $limit = 10,
		int $page = 1,
		array $status = array(),
		array $link_ids = array(),
		array $archive_status = array(),
		string $order_by = self::ORDER_DATE_DESC,
		?string $search_term = null,
		?string $date = null,
		?bool $excluded = null,
		?array $snapshot_process = null,
		?bool $has_checks = null
	): array {
				// Remove any invalid statuses.
		$status = array_filter(
			$status,
			function ( $status ): bool {
				return is_numeric( $status ) && in_array( (int) $status, array( self::LINK_STATUS_BROKEN, self::LINK_STATUS_OK ), true );
			}
		);

		// Remove any invalid archive statuses.
		$archive_status = array_filter(
			$archive_status,
			function ( $status ): bool {
				return is_numeric( $status ) && in_array( (int) $status, array( self::LINK_HAS_ARCHIVE, self::LINK_NO_ARCHIVE ), true );
			}
		);

		// Remove any invalid link ids.
		$link_ids = array_filter(
			$link_ids,
			function ( $link_id ): bool {
				return is_int( $link_id );
			}
		);

		// Ensure limit is a positive integer and not zero.
		$limit = absint( $limit );

		// Ensure page is a positive integer and not zero.
		$page = absint( $page );

		// Ensure date is a valid date.
		$date = $date ? gmdate( 'Y-m', strtotime( esc_attr( $date ) ) ) : null;

		// Prepare the query.
		$query = "SELECT * FROM {$this->table_name}";

		// Where statement has been used.
		$where = false;

		// If we have statuses, add to the query.
		if ( ! empty( $status ) ) {
			$where           = true;
			$status_template = sprintf( ' WHERE is_broken IN (%s)', join( ',', array_fill( 0, count( $status ), '%d' ) ) );
			$query          .= $this->wpdb->prepare( $status_template, $status ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, Format is prepared.
		}

		// If we have archive statuses, add to the query.
		if ( ! empty( $archive_status ) ) {
			$archive_status = array_values( $archive_status );
			$query         .= true === $where ? ' AND' : ' WHERE';
			$query         .= boolval( $archive_status[0] ) ? ' (archived != "" AND archived IS NOT NULL)' : ' (archived = "" OR archived IS NULL)';
			$where          = true;
		}

		// If we have link ids, add to the query.
		if ( ! empty( $link_ids ) ) {
			$place_holders = join( ',', array_fill( 0, count( $link_ids ), '%d' ) );
			$ids_template  = true === $where ? " AND id IN ({$place_holders})" : " WHERE id IN ({$place_holders})";

			$query .= $this->wpdb->prepare( $ids_template, $link_ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared,  the column name is not interpolated.
			$where  = true;
		}

		// If we have a date, add to the query getting the last date from json column.
		if ( $date ) {
			$date_range = $this->get_date_range( $date );
			$query     .= true === $where ? ' AND' : ' WHERE';
			$query     .= ' STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(`checks`, CONCAT("$[", JSON_LENGTH(`checks`) - 1, "].date"))), \'%Y-%m-%d %H:%i:%s\')'; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, Compiled in parts, very hard to escape
			$query     .= $this->wpdb->prepare( ' BETWEEN %s AND %s', $date_range['start'], $date_range['end'] );
			$where      = true;
		}

		// If we are looking for links with or without checks, add to the query.
		if ( null !== $has_checks ) {
			$query .= true === $where ? ' AND' : ' WHERE';
			$query .= $has_checks ? ' JSON_LENGTH(`checks`) > 0' : ' JSON_LENGTH(`checks`) = 0';
			$where  = true;
		}

		// If we have snapshot process status, add to the query.
		if ( ! empty( $snapshot_process ) ) {
			// Remove any invalid snapshot statuses.
			$snapshot_process = array_filter(
				$snapshot_process,
				function ( $status ): bool {
					return is_string( $status ) && in_array( $status, array( Link::PROCESS_NEW, Link::PROCESS_PENDING, Link::PROCESS_DONE ), true );
				}
			);

			$place_holders     = join( ',', array_fill( 0, count( $snapshot_process ), '%s' ) );
			$snapshot_template = true === $where ? " AND archive_process IN ({$place_holders})" : " WHERE archive_process IN ({$place_holders})";
			$query            .= $this->wpdb->prepare( $snapshot_template, $snapshot_process ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, Compiled in parts, very hard to escape
			$where             = true;
		}

		// If we have a search term, add to the query.
		if ( $search_term ) {
			// Prepare the search term.
			$search_term          = str_replace( '%', '', sanitize_text_field( $search_term ) );
			$search_term          = "%{$search_term}%";
			$search_term_template = true === $where ? ' AND url LIKE %s' : ' WHERE url LIKE %s';

			$query .= $this->wpdb->prepare( $search_term_template, $search_term ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, Compiled in parts, very hard to escape
			$where  = true;
		}

		// If we have excluded, add to the query.
		if ( null !== $excluded ) {
			$query .= true === $where ? ' AND' : ' WHERE';
			$query .= $excluded ? ' excluded = 1' : ' excluded = 0';
			$where  = true;
		}

		// Add the order by.
		$query .= $this->compile_order_by( $order_by );

		// Add the limit and offset.
		$offset = ( $page - 1 ) * $limit;
		$query .= $this->wpdb->prepare( ' LIMIT %d OFFSET %d', $limit, $offset ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, Compiled in parts, very hard to escape

		// Get the rows.
		$rows = $this->wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, Compiled in parts, very hard to escape

		// If no rows, return an empty collection.
		if ( empty( $rows ) ) {
			return array();
		}

		return $rows;
	}

	/**
	 * Gets the date range from a defined date.
	 *
	 * @param string $date The date to get the range from.
	 *
	 * @return array
	 */
	private function get_date_range( string $date ): array {
		// Create DateTime object from 'yyyy-mm' date.
		$date = new DateTime( $date );

		// Get the start of the month.
		$start = $date->format( 'Y-m-01' );

		// Get the end of the month.
		$end = $date->format( 'Y-m-t' );

		return array(
			'start' => esc_attr( $start ),
			'end'   => esc_attr( $end ),
		);
	}

	/**
	 * Compile the Query order by.
	 *
	 * @param string $order_by The order by.
	 *
	 * @return string
	 */
	private function compile_order_by( string $order_by ): string {
		// Sanitize the order by.
		$order_by = sanitize_text_field( $order_by );
		switch ( $order_by ) {
			case self::ORDER_DATE_ASC:
				// Here we check we have a date as the last entry of checks, if not use a late for last in results.
				return ' ORDER BY
  CASE WHEN JSON_EXTRACT(`checks`, CONCAT("$[", JSON_LENGTH(`checks`) - 1, "].date")) IS NULL
       THEN \'9999-12-31\'
       ELSE JSON_EXTRACT(`checks`, CONCAT("$[", JSON_LENGTH(`checks`) - 1, "].date"))
  END ASC';

			case self::ORDER_DATE_DESC:
				return ' ORDER BY
    CASE WHEN JSON_LENGTH(`checks`) = 0 THEN 1 ELSE 0 END ASC,
    CASE WHEN JSON_LENGTH(`checks`) = 0 THEN NULL ELSE JSON_EXTRACT(`checks`, CONCAT("$[", JSON_LENGTH(`checks`) - 1, "].date")) END DESC';

			case self::ORDER_URL_ASC:
				return ' ORDER BY url ASC';
			case self::ORDER_URL_DESC:
				return ' ORDER BY url DESC';
			case self::ORDER_ID_ASC:
				return ' ORDER BY id ASC';
			case self::ORDER_HAS_ARCHIVE_ASC:
				return ' ORDER BY archived ASC, url ASC';
			case self::ORDER_HAS_ARCHIVE_DESC:
				return ' ORDER BY archived DESC, url DESC';

			case self::ORDER_LINK_HEALTH_ASC:
				return ' ORDER BY is_broken ASC, url ASC';
			case self::ORDER_LINK_HEALTH_DESC:
				return ' ORDER BY is_broken DESC, url DESC';
			case self::ORDER_LINK_EXCLUDED_ASC:
				return ' ORDER BY excluded ASC, url ASC';
			case self::ORDER_LINK_EXCLUDED_DESC:
				return ' ORDER BY excluded DESC, url DESC';

			case self::ORDER_LINK_CHECKS_ASC:
				return ' ORDER BY JSON_LENGTH(`checks`) ASC, url ASC';
			case self::ORDER_LINK_CHECKS_DESC:
				return ' ORDER BY JSON_LENGTH(`checks`) DESC, url DESC';
			case self::ORDER_ID_DESC:
			default:
				return ' ORDER BY id DESC';
		}
	}

	/**
	 * Get the post id form link id.
	 *
	 * @param integer $link_id The link id.
	 *
	 * @return integer[]
	 */
	public function get_post_ids_from_link_id( int $link_id ): array {
		static $meta = null;
		if ( null === $meta ) {
			$meta = $this->get_all_link_meta();
		}

		return $meta[ $link_id ] ?? array();
	}

	/**
	 * Get all the link meta values.
	 *
	 * @since 1.2.0
	 *
	 * @return array
	 */
	private function get_all_link_meta(): array {
		// Prepare the query.
		$query = "SELECT * FROM {$this->wpdb->postmeta} WHERE meta_key = %s";

		// Get the rows.
		$rows = $this->wpdb->get_results(
			$this->wpdb->prepare( $query, Settings::LINK_META_KEY ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, wpdb called as $this->wpdb
		);

		$return = array();

		// If no rows, return an empty collection.
		if ( empty( $rows ) ) {
			return array();
		}

		// Iterate over each row and extract the links.
		foreach ( $rows as $row ) {
			$links = maybe_unserialize( $row->meta_value );

			if ( ! is_array( $links ) ) {
				continue;
			}

			// Iterate over each link and add to the return array with link to post id..
			foreach ( $links as $link ) {
				$return[ absint( $link ) ][] = (int) $row->post_id;
			}
		}

		return $return;
	}

	/**
	 * Remove a link from the database.
	 *
	 * @since 1.3.0
	 *
	 * @param Link $link The link to remove.
	 *
	 * @return boolean True on success, false on failure.
	 */
	public function delete_link( Link $link ): bool {
		// If the link has no id, return false.
		if ( null === $link->get_id() ) {
			return false;
		}

		// Prepare the delete.
		$result = $this->wpdb->delete(
			$this->table_name,
			array(
				'id' => $link->get_id(),
			),
			array(
				'%d',
			)
		);

		return (bool) $result;
	}
}
