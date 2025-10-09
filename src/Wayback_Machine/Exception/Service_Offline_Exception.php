<?php
/**
 * Custom exception for when the service is offline.
 *
 * @since 1.2.0
 *
 * @package Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Exception
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Exception;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Service_Offline_Exception
 */
class Service_Offline_Exception extends Exception {
	/**
	 * Static creator for the exception.
	 *
	 *@param string $message The message to display.
	 *
	 * @return Service_Offline_Exception
	 */
	public static function create( string $message = '' ): Service_Offline_Exception {
		return new Service_Offline_Exception(
			sprintf(
				// translators: %s is the message.
				__( 'The service is offline.%s', 'internet-archive-wayback-machine-link-fixer' ),
				'' !== $message ? esc_html( ' ' . $message ) : ''
			)
		);
	}
}
