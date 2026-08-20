<?php

/**
 * Tests for the Event Controller.
 *
 * @since 1.4.4
 *
 * @coversDefaultClass \Internet_Archive\Wayback_Machine_Link_Fixer\Event\Event_Controller
 *
 * @group Event
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Event;

use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Event_Controller;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Scan_Posts_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Scan_Own_Posts_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Failed_Event_Garbage_Collection_Event;

/**
 * Test_Event_Controller
 */
class Test_Event_Controller extends \WP_UnitTestCase {

	/**
	 * On tear down, remove the filters.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		remove_all_filters( 'wp_doing_cron' );
	}

	/**
	 * @testdox The action scheduler self-checks should not be hooked on front-end requests. (S014)
	 *
	 * @return void
	 */
	public function test_scheduler_self_checks_not_hooked_on_front_end(): void {
		remove_all_actions( 'init' );
		add_filter( 'wp_doing_cron', '__return_false' );

		( new Event_Controller() )->initialize();

		$this->assertFalse( has_action( 'init', array( Scan_Posts_Event::class, 'add_to_action_scheduler' ) ) );
		$this->assertFalse( has_action( 'init', array( Scan_Own_Posts_Event::class, 'add_to_action_scheduler' ) ) );
		$this->assertFalse( has_action( 'init', array( Failed_Event_Garbage_Collection_Event::class, 'add_to_action_scheduler' ) ) );
	}

	/**
	 * @testdox The action scheduler self-checks should be hooked when running under cron.
	 *
	 * @return void
	 */
	public function test_scheduler_self_checks_hooked_in_cron(): void {
		remove_all_actions( 'init' );
		add_filter( 'wp_doing_cron', '__return_true' );

		( new Event_Controller() )->initialize();

		$this->assertNotFalse( has_action( 'init', array( Scan_Posts_Event::class, 'add_to_action_scheduler' ) ) );
		$this->assertNotFalse( has_action( 'init', array( Scan_Own_Posts_Event::class, 'add_to_action_scheduler' ) ) );
		$this->assertNotFalse( has_action( 'init', array( Failed_Event_Garbage_Collection_Event::class, 'add_to_action_scheduler' ) ) );
	}
}
