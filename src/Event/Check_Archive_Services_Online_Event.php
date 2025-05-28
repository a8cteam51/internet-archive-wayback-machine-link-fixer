<?php

/**
 * Event for checking if the archive system is online.
 *
 * @package Wayback_Link_Fixer
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Wayback_Machine_Service;

defined( 'ABSPATH' ) || exit;


/**
 * Check Archive Status Event class.
 */
class Check_Archive_Services_Online_Event {

	public const HANDLE = 'wlf_check_archive_services_online';

	/**
	 * Wayback Machine Client.
	 *
	 * @var Wayback_Machine_Service
	 */
	private $wayback_machine;

	/**
	 * Setup the event.
	 *
	 * @return void
	 */
	public function setup(): void {
		$this->wayback_machine = new Wayback_Machine_Service();
	}

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
			'wlf_check_archive_services_online',
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
		$this->setup();
		wpcomsp_wayback_link_fixer_is_archive_api_online( true );
	}
}
