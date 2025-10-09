<?php

/**
 * Exception thrown when the snapshot limit has been exceeded.
 *
 * @since 1.3.0
 *
 * @package Internet_Archive\Wayback_Machine_Link_Fixer\Exception
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Exception;

use Exception;

defined( 'ABSPATH' ) || exit;

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
		return new Exceeded_Snapshot_Limit_Exception( esc_html( __( 'Snapshot creation limit exceeded', 'internet-archive-wayback-machine-link-fixer' ) ) );
	}
}
