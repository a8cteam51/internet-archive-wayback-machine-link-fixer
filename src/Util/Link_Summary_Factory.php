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
		$last_check        = $this->link->get_last_check();

		// If the link is excluded, return the excluded message.
		if ( $this->link->is_excluded() ) {
			return __( 'The link is excluded from being archived.', 'internet-archive-wayback-machine-link-fixer' );
		}

		if ( null === $last_check ) {
			return __( 'The link is archived on archive.org. No check history yet.', 'internet-archive-wayback-machine-link-fixer' );
		}

		// If the last check was successful, return early.
		if ( $last_check_status ) {
			return __( 'This link has an archived version on archive.org, and the original URL is still accessible.', 'internet-archive-wayback-machine-link-fixer' );
		}

		// If we have more failed checks than the threshold, return the redirected already message.
		if ( $last_failed_count >= Settings::get_failed_count() ) {
			return sprintf(
				// translators: The number of failed checks.
				__( 'This link is redirecting to the archived version, after %d failed checks.', 'internet-archive-wayback-machine-link-fixer' ),
				$last_failed_count
			);
		}

		$remaining = Settings::get_failed_count() - $last_failed_count;

		return sprintf(
			// translators: 1: The number of consecutive failed checks (e.g. "3 consecutive checks"). 2: The number of remaining failed checks before redirect (e.g. "2 more failed checks").
			__( 'The link is archived on archive.org but currently not working on the live site. It has failed %1$s, and after %2$s it will redirect to the archived version.', 'internet-archive-wayback-machine-link-fixer' ),
			sprintf(
				// translators: %d: The number of consecutive failed checks.
				_n( '%d consecutive check', '%d consecutive checks', $last_failed_count, 'internet-archive-wayback-machine-link-fixer' ),
				$last_failed_count
			),
			sprintf(
				// translators: %d: The number of remaining failed checks before redirect.
				_n( '%d more failed check', '%d more failed checks', $remaining, 'internet-archive-wayback-machine-link-fixer' ),
				$remaining
			)
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
			// translators: %s: The number of consecutive failed checks (e.g. "3 consecutive checks").
			__( 'The link is not archived on archive.org and currently not working on the live site. It has failed %s.', 'internet-archive-wayback-machine-link-fixer' ),
			sprintf(
				// translators: %d: The number of consecutive failed checks.
				_n( '%d consecutive check', '%d consecutive checks', $this->get_consecutive_failed_checks_count(), 'internet-archive-wayback-machine-link-fixer' ),
				$this->get_consecutive_failed_checks_count()
			)
		);
	}

	/**
	 * Checks if the last check was a success.
	 *
	 * @return boolean
	 */
	private function was_last_check_successful(): bool {
		$last_check = $this->link->get_last_check();
		if ( ! is_array( $last_check ) || ! isset( $last_check['http_code'] ) ) {
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
			// Skip malformed items.
			if ( ! is_array( $check ) || ! array_key_exists( 'http_code', $check ) ) {
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

	/**
	 * Gets the links current message.
	 *
	 * @return string
	 */
	public function get_current_message(): string {
		$message = $this->link->get_message();
		// If the message doesnt start with error:, return the original message.
		if ( 0 !== strpos( $message, 'error:' ) ) {
			return $message;
		}

		// If the link is still is still pending.
		if ( Link::PROCESS_DONE !== $this->link->get_archive_process() ) {
			return sprintf(
				// translators: 1: The error message.
				'<span class="dashicons dashicons-info" title="%s"></span> - %s',
				__( 'Error encountered while processing the link, will retry shortly.', 'internet-archive-wayback-machine-link-fixer' ),
				iawmlf_get_human_readable_status_message( $message )
			);
		}

		return sprintf(
			// translators: 1: The error message.
			'<span class="dashicons dashicons-warning" title="%s"></span> - %s',
			__( 'Error encountered while processing the link, no more retries will be made.', 'internet-archive-wayback-machine-link-fixer' ),
			iawmlf_get_human_readable_status_message( $message )
		);
	}
}
