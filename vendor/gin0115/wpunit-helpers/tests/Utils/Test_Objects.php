<?php

declare(strict_types=1);

/**
 * Tests for the Objects helper class
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

namespace Gin0115\WPUnit_Helpers\Utils;

use PHPUnit\Framework\TestCase;
use Gin0115\WPUnit_Helpers\Objects;
use Gin0115\WPUnit_Helpers\Tests\Fixtures\Objects\Class_None_Public;

class Test_Objects extends TestCase {

	/** @var Class_None_Public */
	protected $mock_class;

	protected function setUp(): void {
		parent::setUp();
		$this->mock_class = new Class_None_Public();
	}

	public function tearDown(): void {
		parent::tearDown();
		unset( $this->mock_class );
	}

	/** PROPERTY GETTERSc */

	/** @testdox Can get a public property using get_property() */
	public function test_get_public_property() {
		$this->assertSame(
			Class_None_Public::RESULTS['public_prop'],
			Objects::get_property( $this->mock_class, 'public_prop' )
		);
	}

	/** @testdox Can get a protected property using get_property() */
	public function test_get_protected_property() {
		$this->assertSame(
			Class_None_Public::RESULTS['protected_prop'],
			Objects::get_property( $this->mock_class, 'protected_prop' )
		);
	}

	/** @testdox Can get a private property using get_property() */
	public function test_get_private_property() {
		$this->assertSame(
			Class_None_Public::RESULTS['private_prop'],
			Objects::get_property( $this->mock_class, 'private_prop' )
		);
	}

	/** @testdox Can get a public static property using get_property() */
	public function test_get_public_static_property() {
		$this->assertSame(
			Class_None_Public::RESULTS['public_static_prop'],
			Objects::get_property( $this->mock_class, 'public_static_prop' )
		);
	}

	/** @testdox Can get a protected static property using get_property() */
	public function test_get_protected_static_property() {
		$this->assertSame(
			Class_None_Public::RESULTS['protected_static_prop'],
			Objects::get_property( $this->mock_class, 'protected_static_prop' )
		);
	}

	/** @testdox Can get a private static property using get_property() */
	public function test_get_private_static_property() {
		$this->assertSame(
			Class_None_Public::RESULTS['private_static_prop'],
			Objects::get_property( $this->mock_class, 'private_static_prop' )
		);
	}

	/** PROPERTY SETTERS */

	/** @testdox Can set a public property using set_property() */
	public function test_set_public_property() {
		Objects::set_property( $this->mock_class, 'public_prop', '__!public_prop!__' );
		$this->assertSame( '__!public_prop!__', Objects::get_property( $this->mock_class, 'public_prop' ) );
	}

	/** @testdox Can set a protected property using set_property() */
	public function test_set_protected_property() {
		Objects::set_property( $this->mock_class, 'protected_prop', '__!protected_prop!__' );
		$this->assertSame( '__!protected_prop!__', Objects::get_property( $this->mock_class, 'protected_prop' ) );
	}

	/** @testdox Can set a private property using set_property() */
	public function test_set_private_property() {
		Objects::set_property( $this->mock_class, 'private_prop', '__!private_prop!__' );
		$this->assertSame( '__!private_prop!__', Objects::get_property( $this->mock_class, 'private_prop' ) );
	}

	/** @testdox Can set a public static property using set_property() */
	public function test_set_public_static_property() {
		Objects::set_property( $this->mock_class, 'public_static_prop', '__!public_static_prop!__' );
		$this->assertSame( '__!public_static_prop!__', Objects::get_property( $this->mock_class, 'public_static_prop' ) );

		// Reset the property
		Objects::set_property( $this->mock_class, 'public_static_prop', 'Public Static Property' );
		$this->assertSame( 'Public Static Property', Objects::get_property( $this->mock_class, 'public_static_prop' ) );
	}

	/** @testdox Can set a protected static property using set_property() */
	public function test_set_protected_static_property() {
		Objects::set_property( $this->mock_class, 'protected_static_prop', '__!protected_static_prop!__' );
		$this->assertSame( '__!protected_static_prop!__', Objects::get_property( $this->mock_class, 'protected_static_prop' ) );

		// Reset the property
		Objects::set_property( $this->mock_class, 'protected_static_prop', 'Protected Static Property' );
		$this->assertSame( 'Protected Static Property', Objects::get_property( $this->mock_class, 'protected_static_prop' ) );

	}

	/** @testdox Can set a private static property using set_property() */
	public function test_set_private_static_property() {
		Objects::set_property( $this->mock_class, 'private_static_prop', '__!private_static_prop!__' );
		$this->assertSame( '__!private_static_prop!__', Objects::get_property( $this->mock_class, 'private_static_prop' ) );

		// Reset the property
		Objects::set_property( $this->mock_class, 'private_static_prop', 'Private Static Property' );
		$this->assertSame( 'Private Static Property', Objects::get_property( $this->mock_class, 'private_static_prop' ) );
	}

	/** INVOKE METHODS */

	/** @testdox Can invoke a public method using invoke_method() */
	public function test_invoke_public_property() {
		$this->assertSame(
			Class_None_Public::RESULTS['public_method'] . '__FF__',
			Objects::invoke_method( $this->mock_class, 'public_method', array( '__FF__' ) )
		);
	}

	/** @testdox Can invoke a protected method using invoke_method() */
	public function test_invoke_protected_property() {
		$this->assertSame(
			Class_None_Public::RESULTS['protected_method'] . '__FF__',
			Objects::invoke_method( $this->mock_class, 'protected_method', array( '__FF__' ) )
		);
	}

	/** @testdox Can invoke a private method using invoke_method() */
	public function test_invoke_private_property() {
		$this->assertSame(
			Class_None_Public::RESULTS['private_method'] . '__FF__',
			Objects::invoke_method( $this->mock_class, 'private_method', array( '__FF__' ) )
		);
	}

	/** @testdox Can invoke a public method using invoke_mthod() */
	public function test_invoke_public_static_property() {
		$this->assertSame(
			Class_None_Public::RESULTS['public_static_method'] . '__FF__',
			Objects::invoke_method( $this->mock_class, 'public_static_method', array( '__FF__' ) )
		);
	}

	/** @testdox Can invoke a protected method using invoke_mthod() */
	public function test_invoke_protected_static_property() {
		$this->assertSame(
			Class_None_Public::RESULTS['protected_static_method'] . '__FF__',
			Objects::invoke_method( $this->mock_class, 'protected_static_method', array( '__FF__' ) )
		);
	}

	/** @testdox Can invoke a private method using invoke_mthod() */
	public function test_invoke_private_static_property() {
		$this->assertSame(
			Class_None_Public::RESULTS['private_static_method'] . '__FF__',
			Objects::invoke_method( $this->mock_class, 'private_static_method', array( '__FF__' ) )
		);
	}

}
