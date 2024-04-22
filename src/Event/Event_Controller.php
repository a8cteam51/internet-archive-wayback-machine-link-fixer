<?php

/**
 * The main controller which registers all the event handlers.
 *
 * @since 1.2.0
 *
 * @package WPCOMSpecialProjects\Wayback_Link_Fixer\Event
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

/**
 * Event Controller class.
 */
class Event_Controller {

	/**
	 * Initializes the event controller.
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( Archive_Link_Event::HANDLE, new Archive_Link_Event(), 10, 1 );
		add_action( Update_Archive_URL_Event::HANDLE, new Update_Archive_URL_Event(), 10, 2 );
		add_action( Scan_Posts_Event::HANDLE, new Scan_Posts_Event(), 10, 1 );

		// Ensure the post scan event is added to the action scheduler.
		add_action(
			'init',
			function () {
				Scan_Posts_Event::add_to_action_scheduler();
			}
		);
	}
}
