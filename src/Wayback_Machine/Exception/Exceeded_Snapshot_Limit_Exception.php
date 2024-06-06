<?php

/**
 * Exception thrown when the snapshot limit has been exceeded.
 *
 * @since 1.3.0
 *
 * @package WPCOMSpecialProjects\Wayback_Link_Fixer\Exception
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception;

use Exception;

/**
 * Exceeded Snapshot Limit Exception class.
 */
class Exceeded_Snapshot_Limit_Exception extends Exception {

	/**
	 * Creates a new instance of the exception.
	 *
	 * @return Exceeded_Snapshot_Limit_Exception
	 */
	public static function create(): Exceeded_Snapshot_Limit_Exception {
		return new Exceeded_Snapshot_Limit_Exception( esc_html( __( 'Snapshot creation limit exceeded', 'wpcomsp_wayback_link_fixer' ) ) );
	}
}
