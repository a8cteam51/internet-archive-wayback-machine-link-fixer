<?php

/**
 * Event for checking if the archive system is online.
 *
 * @package Wayback_Link_Fixer
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Event;

defined( 'ABSPATH' ) || exit;


/**
 * Check Archive Status Event class.
 */
class Check_Archive_Services_Online_Event {

	public const HANDLE = 'iawmlf_check_archive_services_online';

	/**
	 * Adds the event to the queue.
	 *
	 * @return void
	 */
	public static function add_to_queue(): void {
		// Add an single event with the date as epoch.
		as_schedule_single_action(
			0, // Forces the event to run immediately.
			self::HANDLE,
			array(),
			'iawmlf_check_archive_services_online',
			'',
			0
		);
	}

	/**
	 * Check if the archive services are online.
	 *
	 * @return void
	 */
	public function __invoke(): void {
		iawmlf_is_archive_api_online( true );
	}
}
