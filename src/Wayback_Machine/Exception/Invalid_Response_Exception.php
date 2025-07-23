<?php

/**
 * Custom exception for when the response is invalid.
 *
 * @since 1.2.0
 *
 * @package WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Invalid_Response_Exception
 */
class Invalid_Response_Exception extends Exception {
	/**
	 * Static creator for the exception.
	 *
	 * @param string $message The exception message.
	 *
	 * @return Invalid_Response_Exception
	 */
	public static function create( string $message = ' ' ): Invalid_Response_Exception {
		return new Invalid_Response_Exception( esc_html( __( 'The response is invalid.', 'wayback-link-fixer' ) . $message ) );
	}
}
