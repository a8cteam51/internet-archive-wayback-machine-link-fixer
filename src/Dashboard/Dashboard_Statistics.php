<?php

/**
 * Handles the dashboard statistics.
 *
 * @since 1.3.4
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;
use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link_Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Class to handle the dashboard statistics.
 */
class Dashboard_Statistics {

	private const LINK_STATS_TRANSIENT_KEY       = 'iawmlf_dashboard_stats';
	private const ONBOARDING_STATS_TRANSIENT_KEY = 'iawmlf_dashboard_onboarding_stats';

	/**
	 * Get the link statistics, either from cache or fresh.
	 *
	 * @return array{
	 *     total_links: int<0, max>,
	 *     broken_links: int<0, max>,
	 *     links_with_archive: int<0, max>,
	 *     links_without_archive: int<0, max>,
	 *     not_checked: int<0, max>,
	 *     process_done: int<0, max>,
	 *     process_new: int<0, max>,
	 *     process_pending: int<0, max>,
	 * }
	 */
	public static function get_link_statistics(): array {
		// Attempt to get from cache.
		$from_cache = get_transient( self::LINK_STATS_TRANSIENT_KEY );

		// If we dont have an array or invalid data, compile fresh.
		if ( false === $from_cache || ! is_array( $from_cache ) ) {
			$stats = self::compile_link_statistics();
		} else {
			// Validate and normalize cached data.
			$stats = self::normalize_link_statistics( $from_cache );
			if ( null === $stats ) {
				$stats = self::compile_link_statistics();
				$stats = self::normalize_link_statistics( $stats );
			} else {
				return $stats;
			}
		}
		// Cache the stats.
		$expiry = absint( apply_filters( 'iawmlf_dashboard_link_stats_cache_expiry', 2 * \MINUTE_IN_SECONDS ) );
		set_transient( self::LINK_STATS_TRANSIENT_KEY, $stats, $expiry );
		return $stats;
	}

	/**
	 * Validate and normalize the link statistics array.
	 * If missing or invalid data, return null.
	 *
	 * @param array $stats The statistics array.
	 *
	 * @return array|null
	 */
	private static function normalize_link_statistics( array $stats ): ?array {
		$required_keys = array(
			'total_links',
			'all_broken_links',
			'broken_and_redirected_links',
			'broken_not_redirected_links',
			'links_with_archive',
			'links_without_archive',
			'not_checked',
			'process_done',
			'process_new',
			'process_pending',
			'last_checks',
		);

		foreach ( $required_keys as $key ) {
			if ( ! array_key_exists( $key, $stats ) ) {
				return null;
			}
		}

		// Only return the required keys.
		$stats = array(
			'total_links'                 => \absint( $stats['total_links'] ),
			'broken_links'                => \absint( $stats['all_broken_links'] ),
			'all_broken_links'            => \absint( $stats['all_broken_links'] ),
			'links_with_archive'          => \absint( $stats['links_with_archive'] ),
			'links_without_archive'       => \absint( $stats['links_without_archive'] ),
			'not_checked'                 => \absint( $stats['not_checked'] ),
			'process_done'                => \absint( $stats['process_done'] ),
			'process_new'                 => \absint( $stats['process_new'] ),
			'process_pending'             => \absint( $stats['process_pending'] ),
			'broken_and_redirected_links' => \absint( $stats['broken_and_redirected_links'] ),
			'broken_not_redirected_links' => \absint( $stats['broken_not_redirected_links'] ),
			'last_checks'                 => $stats['last_checks'],
		);

		return $stats;
	}

	/**
	 * Compile the link statistics fresh.
	 *
	 * @return array{
	 *     total_links: int<0, max>,
	 *     broken_links: int<0, max>,
	 *     links_with_archive: int<0, max>,
	 *     links_without_archive: int<0, max>,
	 *     not_checked: int<0, max>,
	 *     process_done: int<0, max>,
	 *     process_new: int<0, max>,
	 *     process_pending: int<0, max>,
	 * }
	 */
	private static function compile_link_statistics(): array {
		$all_links = ( new Link_Repository() )->query_links( \PHP_INT_MAX, 1, array(), array(), array(), Link_Repository::ORDER_DATE_DESC, null, null, null );

		// Get all the links stats.
		$all_broken        = array();
		$redirected_broken = array();
		$has_archive_link  = array();
		$not_checked       = array();
		$process_done      = array();
		$process_new       = array();
		$process_pending   = array();
		$last_checks       = array();

		// Loop through all links to gather stats.
		foreach ( $all_links as $link ) {
			if ( $link->is_broken() && ! $link->is_excluded() ) {
				$all_broken[] = $link->get_id();
			}

			if ( $link->is_broken() && $link->has_archived_href() && ! $link->is_excluded() ) {
				$redirected_broken[] = $link->get_id();
			}

			if ( $link->has_archived_href() ) {
				$has_archive_link[] = $link->get_id();
			}

			if ( null === $link->get_last_check() ) {
				$not_checked[] = $link->get_id();
			} else {
				$last          = $link->get_last_check();
				$last_checks[] = array(
					'id'         => $link->get_id(),
					'last_check' => $last,
				);
			}

			switch ( $link->get_archive_process() ) {
				case Link::PROCESS_NEW:
					$process_new[] = $link->get_id();
					break;
				case Link::PROCESS_PENDING:
					$process_pending[] = $link->get_id();
					break;
				default:
					$process_done[] = $link->get_id();
					break;
			}
		}

		// Sort the last checks by date desc.
		usort(
			$last_checks,
			function ( $a, $b ) {
				return strtotime( $b['last_check']['date'] ) <=> strtotime( $a['last_check']['date'] );
			}
		);

		$stats = array(
			'total_links'                 => count( $all_links ),
			'all_broken_links'            => count( $all_broken ),
			'broken_and_redirected_links' => count( $redirected_broken ),
			'broken_not_redirected_links' => count( $all_broken ) - count( $redirected_broken ),
			'links_with_archive'          => count( $has_archive_link ),
			'links_without_archive'       => count( $all_links ) - count( $has_archive_link ),
			'not_checked'                 => count( $not_checked ),
			'process_done'                => count( $process_done ),
			'process_new'                 => count( $process_new ),
			'process_pending'             => count( $process_pending ),
			'last_checks'                 => array_slice( $last_checks, 0, absint( apply_filters( 'iawmlf_dashboard_link_count', 10 ) ) ),
		);

		return $stats;
	}

	/**
	 * Get onboarding statistics.
	 *
	 * @return array{
	 * show_onboarding: bool,
	 *    onboarding_date: string|null,
	 *   days_since_onboarding: int|null
	 * total_post_count:int<0, max>
	 * unprocessed_post_count:int<0, max>
	 * }
	 */
	public static function get_onboarding_statistics(): array {
		$from_cache = get_transient( self::ONBOARDING_STATS_TRANSIENT_KEY );

		// If we dont have an array or invalid data, compile fresh.
		if ( false === $from_cache || ! is_array( $from_cache ) ) {
			$stats = self::compile_onboarding_statistics();
		} else {
			// Validate and normalize cached data.
			$stats = self::normalize_onboarding_statistics( $from_cache );
			if ( null === $stats ) {
				$stats = self::compile_onboarding_statistics();
			} else {
				return $stats;
			}
		}

		// Set the cache.
		$expiry = absint( apply_filters( 'iawmlf_dashboard_onboarding_stats_cache_expiry', 2 * \MINUTE_IN_SECONDS ) );
		set_transient( self::ONBOARDING_STATS_TRANSIENT_KEY, $stats, $expiry );

		return $stats;
	}

	/**
	 * Normalize onboarding statistics.
	 *
	 * @param array $stats The stats array.
	 *
	 * @return array|null
	 */
	private static function normalize_onboarding_statistics( array $stats ): ?array {
		$required_keys = array(
			'total_post_count',
			'unprocessed_post_count',
			'show_onboarding',
			'onboarding_date',
			'days_since_onboarding',
		);

		foreach ( $required_keys as $key ) {
			if ( ! array_key_exists( $key, $stats ) ) {
				return null;
			}
		}

		// Only return the required keys.
		$stats = array(
			'total_post_count'       => \absint( $stats['total_post_count'] ),
			'unprocessed_post_count' => \absint( $stats['unprocessed_post_count'] ),
			'show_onboarding'        => (bool) $stats['show_onboarding'],
			'onboarding_date'        => is_string( $stats['onboarding_date'] ) ? $stats['onboarding_date'] : null,
			'days_since_onboarding'  => is_int( $stats['days_since_onboarding'] ) ? $stats['days_since_onboarding'] : null,
		);

		return $stats;
	}

	/**
	 * Compile the onboarding statistics fresh.
	 *
	 * @return array{
	 * show_onboarding: bool,
	 *    onboarding_date: string|null,
	 *   days_since_onboarding: int|null
	 * total_post_count:int<0, max>
	 * unprocessed_post_count:int<0, max>
	 * }
	 */
	private static function compile_onboarding_statistics(): array {
		$onboarding_date = Settings::get_onboarding_date();

		$return_data = array(
			'show_onboarding'        => false,
			'onboarding_date'        => $onboarding_date ? (string) $onboarding_date : null,
			'days_since_onboarding'  => $onboarding_date ? (int) floor( ( time() - strtotime( $onboarding_date ) ) / DAY_IN_SECONDS ) : null,
			'total_post_count'       => 0,
			'unprocessed_post_count' => 0,
		);

		// If we have no oneboarding date or its 7 days or more ago, return empty array, as onboarding is over.
		if ( ! $onboarding_date || ( time() - strtotime( $onboarding_date ) ) >= 7 * DAY_IN_SECONDS ) {
			return $return_data;
		}

		$return_data['show_onboarding'] = true;
		$total_post_count               = self::get_post_count( true );
		$unprocessed_post_count         = self::get_post_count( false );

		// If we have 0 unprocessed posts, onboarding is over.
		if ( 0 === $unprocessed_post_count ) {
			$return_data['show_onboarding'] = false;
		} else {
			$return_data['total_post_count']       = $total_post_count;
			$return_data['unprocessed_post_count'] = $unprocessed_post_count;
		}

		return $return_data;
	}

	/**
	 * Get post count.
	 *
	 * @param boolean $all Whether to get all posts or only processed.
	 *
	 * @return integer
	 */
	private static function get_post_count( bool $all = true ): int {

		$meta_query = array();
		if ( ! $all ) {
			$meta_query = array(
				'relation' => 'OR',
				array(
					'key'     => Settings::LINK_META_KEY,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => Settings::LINK_META_KEY,
					'value'   => time(),
					'compare' => '=',
				),
			);
		}

		$args = array(
			'post_type'              => Settings::get_allowed_post_types(),
			'cache_results'          => false,
			'update_post_meta_cache' => false,
			'meta_query'             => $meta_query,

		);
		$query = new \WP_Query( $args );
		return absint( $query->found_posts );
	}
}
