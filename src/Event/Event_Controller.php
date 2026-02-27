<?php

/**
 * The main controller which registers all the event handlers.
 *
 * @since 1.2.0
 *
 * @package Internet_Archive\Wayback_Machine_Link_Fixer\Event
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Event;

defined( 'ABSPATH' ) || exit;

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
		add_action( Create_New_Snapshot_Event::HANDLE, new Create_New_Snapshot_Event(), 10, 2 );
		add_action( Update_Archive_URL_Event::HANDLE, new Update_Archive_URL_Event(), 10, 2 );
		add_action( Scan_Posts_Event::HANDLE, new Scan_Posts_Event(), 10, 1 );
		add_action( Check_Snapshot_Status_Event::HANDLE, new Check_Snapshot_Status_Event(), 10, 3 );
		add_action( Find_Or_Create_Snapshot_Event::HANDLE, new Find_Or_Create_Snapshot_Event(), 10, 1 );
		add_action( Link_Access_Validator_Event::HANDLE, new Link_Access_Validator_Event(), 10, 1 );
		add_action( Check_Validator_Status::HANDLE, new Check_Validator_Status(), 10, 3 );
		add_action( Check_Archive_Services_Online_Event::HANDLE, new Check_Archive_Services_Online_Event(), 10, 0 );
		add_action( Process_Local_Post_Event::HANDLE, new Process_Local_Post_Event(), 10, 1 );
		add_action( Scan_Own_Posts_Event::HANDLE, new Scan_Own_Posts_Event(), 10, 0 );
		add_action( Failed_Event_Garbage_Collection_Event::HANDLE, new Failed_Event_Garbage_Collection_Event(), 10, 0 );

		// Ensure the post scan event is added to the action scheduler.
		add_action( 'init', array( Scan_Posts_Event::class, 'add_to_action_scheduler' ) );
		add_action( 'init', array( Scan_Own_Posts_Event::class, 'add_to_action_scheduler' ) );
		add_action( 'init', array( Failed_Event_Garbage_Collection_Event::class, 'add_to_action_scheduler' ) );
	}
}
