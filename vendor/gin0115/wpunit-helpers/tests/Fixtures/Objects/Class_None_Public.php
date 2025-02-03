<?php

/**
 * Mock class with various protected and private methods/properties.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 */

declare( strict_types=1 );

namespace Gin0115\WPUnit_Helpers\Tests\Fixtures\Objects;

class Class_None_Public {

	/**
	 * Constant for all returned results from this object.
	 */
	public const RESULTS = array(
		// PROPERTIES
		'protected_prop'          => 'Protected Property',
		'private_prop'            => 'Private Property',
		'public_prop'             => 'Public Property',
		'protected_static_prop'   => 'Protected Static Property',
		'private_static_prop'     => 'Private Static Property',
		'public_static_prop'      => 'Public Static Property',
		// METHODS
		'protected_method'        => 'Protected Method',
		'private_method'          => 'Private Method',
		'public_method'           => 'Public Method',
		'protected_static_method' => 'Protected Static Method',
		'private_static_method'   => 'Private Static Method',
		'public_static_method'    => 'Public Static Method',
	);

	// INSTANCED PROPERTIES
	/** @var string */
	protected $protected_prop = self::RESULTS['protected_prop'];
	/** @var string */
	private $private_prop = self::RESULTS['private_prop'];
	/** @var string */
	public $public_prop = self::RESULTS['public_prop'];

	// STATIC PROPERTIES
	protected static $protected_static_prop = self::RESULTS['protected_static_prop'];
	/** @var string */
	private static $private_static_prop = self::RESULTS['private_static_prop'];
	/** @var string */
	public static $public_static_prop = self::RESULTS['public_static_prop'];

	// INSTANCED METHODS
	/** @return string */
	protected function protected_method( string $arg = '' ): string {
		return self::RESULTS['protected_method'] . $arg;
	}
	/** @return string */
	private function private_method( string $arg = '' ): string {
		return self::RESULTS['private_method'] . $arg;
	}
	/** @return string */
	public function public_method( string $arg = '' ): string {
		return self::RESULTS['public_method'] . $arg;
	}

	// STATIC METHODS
	/** @return string */
	protected function protected_static_method( string $arg = '' ): string {
		return self::RESULTS['protected_static_method'] . $arg;
	}
	/** @return string */
	private function private_static_method( string $arg = '' ): string {
		return self::RESULTS['private_static_method'] . $arg;
	}
	/** @return string */
	public function public_static_method( string $arg = '' ): string {
		return self::RESULTS['public_static_method'] . $arg;
	}
}
