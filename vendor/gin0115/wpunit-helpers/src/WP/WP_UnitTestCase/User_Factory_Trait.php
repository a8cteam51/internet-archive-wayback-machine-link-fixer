<?php

/**
 * WP_UnitTestCase trait for creating generic users
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

declare( strict_types=1 );

namespace Gin0115\WPUnit_Helpers\WP\WP_UnitTestCase;

use WP_User;

trait User_Factory_Trait {

	/**
	 * Creates a new admin user and returns the user object.
	 *
	 * @return WP_User
	 */
	public function create_admin_user(): WP_User {
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		return new WP_User( $admin );
	}

	/**
	 * Creates a new customer user and returns the user object.
	 *
	 * @return WP_User
	 */
	public function create_customer_user(): WP_User {
		$customer = $this->factory->user->create( array( 'role' => 'customer' ) );
		return new WP_User( $customer );
	}

	/**
	 * Creates a new subscriber user and returns the user object.
	 *
	 * @return WP_User
	 */
	public function create_subscriber_user(): WP_User {
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		return new WP_User( $subscriber );
	}
}

