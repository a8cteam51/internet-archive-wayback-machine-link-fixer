<?php

/**
 * Utility class to create link summaries.
 *
 * @since 1.3.1
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Util;

use Internet_Archive\Wayback_Machine_Link_Fixer\Link\Link;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Link_Summary_Factory
 */
class Link_Summary_Factory {

	/**
	 * The link being summarized.
	 *
	 * @var Link
	 */
	private $link;

	/**
	 * Constructor.
	 *
	 * @param Link $link The link to summarize.
	 */
	public function __construct( Link $link ) {
		$this->link = $link;
	}

	/**
	 * Get the summary of the link.
	 *
	 * @return string
	 */
	public function get_summary(): string {
		if ( Link::PROCESS_DONE !== $this->link->get_archive_process() ) {
			return __( 'The link is still being processed to find or create an archive snapshot. Please check back later.', 'internet-archive-wayback-machine-link-fixer' );
		}

		return $this->link->has_archived_href()
			? $this->get_archive_snapshot_message()
			: $this->get_no_archive_snapshot_message();
	}

	/**
	 * Compiles the message if the link has an archive.org snapshot.
	 *
	 * @return string
	 */
	private function get_archive_snapshot_message(): string {
		$last_check_status = $this->was_last_check_successful();
		$last_failed_count = $this->get_consecutive_failed_checks_count();

		// If the last check was successful, return early.
		if ( $last_check_status ) {
			return __( 'The link is archived on archive.org and still working.', 'internet-archive-wayback-machine-link-fixer' );
		}

		// If we have more failed checks than the threshold, return the redirected already message.
		if ( $last_failed_count >= Settings::get_failed_count() ) {
			return sprintf(
				// translators: The number of failed checks.
				__( 'This link is redirecting to the archived version, after %d failed checks.', 'internet-archive-wayback-machine-link-fixer' ),
				$last_failed_count
			);
		}

		return sprintf(
		// translators: 1: The number of failed checks. 2: The number of remaining checks before redirect. 3: Plural S if needed.
			__( 'The link is archived on archive.org but currently not working. It has failed %1$d previous consecutive check%3$s, %2$d more and it will redirect to the archived version.', 'internet-archive-wayback-machine-link-fixer' ),
			$last_failed_count,
			Settings::get_failed_count() - $last_failed_count,
			1 === $last_failed_count ? '' : 's'
		);
	}

	/**
	 * Compiles the message if the link does not have an archive.org snapshot.
	 *
	 * @return string
	 */
	private function get_no_archive_snapshot_message(): string {
		if ( $this->was_last_check_successful() ) {
			return __( 'The link is not archived on archive.org but is currently working.', 'internet-archive-wayback-machine-link-fixer' );
		}

		return sprintf(
		// translators: 1: The number of failed checks. 2: The plural S if needed.
			__( 'The link is not archived on archive.org and currently not working. It has failed %1$d previous consecutive check%2$s.', 'internet-archive-wayback-machine-link-fixer' ),
			$this->get_consecutive_failed_checks_count(),
			1 === $this->get_consecutive_failed_checks_count() ? '' : 's'
		);
	}

	/**
	 * Checks if the last check was a success.
	 *
	 * @return boolean
	 */
	private function was_last_check_successful(): bool {
		$last_check = $this->link->get_last_check();
		if ( null === $last_check ) {
			return false;
		}

		return $this->is_http_code_valid( absint( $last_check['http_code'] ) );
	}

	/**
	 * Gets the count of consecutive failed checks.
	 *
	 * @return integer
	 */
	private function get_consecutive_failed_checks_count(): int {
		$checks       = $this->link->get_checks();
		$failed_count = 0;
		foreach ( array_reverse( $checks ) as $check ) {
			// If we dont have a http code, skip it.
			if ( is_array( $check ) === false || ! isset( $check['http_code'] ) ) {
				continue;
			}

			if ( ! $this->is_http_code_valid( absint( $check['http_code'] ) ) ) {
				++$failed_count;
			} else {
				break;
			}
		}
		return $failed_count;
	}

	/**
	 * Checks if a given HTTP code is considered valid.
	 *
	 * @param integer $http_code The HTTP code to check.
	 *
	 * @return boolean
	 */
	private function is_http_code_valid( int $http_code ): bool {
		return in_array( $http_code, Settings::get_valid_http_status_codes(), true );
	}
}

