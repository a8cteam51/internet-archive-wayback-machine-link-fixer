<?php

/**
 * Handles the getting/settings of link cache values.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link_Cache;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;

/**
 * Link Cache
 */
class Link_Cache {

	/**
	 * The time at which links should be invalidated.
	 *
	 * @var int
	 */
	private int $expiry = 86400;

	/**
	 * Create instance of Link Cache.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $expiry The time at which links should be invalidated.
	 */
	public function __construct( int $expiry = 86400 ) {
		$this->expiry = $expiry;
	}

	/**
	 * Get default instance of Link Cache.
	 *
	 * @since 1.0.0
	 *
	 * @return Link_Cache
	 */
	public static function get_default(): Link_Cache {
		return new self( Settings::get_link_cache_expiration() );
	}

	/**
	 * Attempt to find a link based on its URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The URL to search for.
	 *
	 * @return Link|null
	 */
	public function find_link( string $url ): ?Link {
		global $wpdb;

		$table_name = Settings::SCAN_LINK_CACHE_TABLE;

		$date = new \DateTime();
		$date->modify( '-' . $this->expiry . ' seconds' );

		// Get the results.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE url = %s AND create_date > %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, cant prepare table name.
				$url,
				$date->format( 'Y-m-d H:i:s' )
			)
		);

		// If we have no results, return null.
		if ( empty( $results ) ) {
			return null;
		}

		$first = $results[0];
		$link  = maybe_unserialize( $first->link );
		return is_a( $link, Link::class ) ? $link : null;
	}

	/**
	 * Add a link to the cache.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url  The URL to add.
	 * @param Link   $link The link to add.
	 *
	 * @return void
	 */
	public function add_link( string $url, Link $link ): void {
		global $wpdb;

		$table_name = Settings::SCAN_LINK_CACHE_TABLE;

		$wpdb->insert(
			$table_name,
			array(
				'url'         => $url,
				'link'        => serialize( $link ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
				'create_date' => \current_time( 'mysql', true ),
			),
			array(
				'%s',
				'%s',
				'%s',
			)
		);
	}
}
