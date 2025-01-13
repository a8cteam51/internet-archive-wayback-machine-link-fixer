<?php

declare (strict_types=1);

/**
 * Objects helper functions for working private and protected properties/methods
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package Gin0115\WPUnit_Helpers
 * @since 1.0.0
 * */

namespace Gin0115\WPUnit_Helpers;

use stdClass;

class Objects {

	/**
		 * Gets private or protected propertoes from an object.
		 *
		 * @param object $object
		 * @param string $property
		 * @return mixed
		 */
	public static function get_property( &$object, string $property ) {
		$reflection     = new \ReflectionClass( get_class( $object ) );
		$property_value = $reflection->getProperty( $property );
		$property_value->setAccessible( true );
		return $property_value->getValue( $object );
	}

	/**
	 * Call protected/private method of a class.
	 *
	 * @param object $object    Instantiated object that we will run method on.
	 * @param string $method_name Method name to call.
	 * @param array<string, mixed>  $parameters Array of parameters to pass into method.
	 * @return mixed Method return.
	 */
	public static function invoke_method( &$object, string $method_name, array $parameters = array() ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $parameters );
	}

	/**
	 * Allows the setting of a private/protected property
	 *
	 * @param object $object
	 * @param string $property
	 * @param mixed $value
	 * @return void
	 */
	public static function set_property( &$object, string $property, $value ): void {
		$reflection     = new \ReflectionClass( get_class( $object ) );
		$property_value = $reflection->getProperty( $property );
		$property_value->setAccessible( true );
		$property_value->setValue( $object, $value );

	}

}
