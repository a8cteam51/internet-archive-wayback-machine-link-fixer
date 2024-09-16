<?php

declare(strict_types=1);

/**
 * Tests the
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

namespace Gin0115\WPUnit_Helpers\WP\WP_UnitTestCase;

use Gin0115\WPUnit_Helpers\WP\WP_UnitTestCase\User_Factory_Trait;

class Test_User_Factory_Trait extends \WP_UnitTestCase {

	/**
	 * @uses User_Factory_Trait::create_admin_user
	 * @uses User_Factory_Trait::create_subscriber_user
	 * @uses User_Factory_Trait::create_customer_user
	 */
	use User_Factory_Trait;

	/** @testdox Can create a generic admin user */
	public function test_can_create_admin(): void {
		$user = $this->create_admin_user();
		$this->assertContains( 'administrator', $user->roles );
	}

	/** @testdox Can create a generic subscriber user */
	public function test_can_create_subscriber(): void {
		$user = $this->create_subscriber_user();
		$this->assertContains( 'subscriber', $user->roles );
	}

	/** @testdox Can create a generic customer user */
	public function test_can_create_customer(): void {
		$user = $this->create_customer_user();
		$this->assertContains( 'customer', $user->roles );
	}
}

