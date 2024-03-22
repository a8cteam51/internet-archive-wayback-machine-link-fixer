<?php

/**
 * Handles the registration of the CLI commands.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\CLI;

use WPCOMSpecialProjects\Wayback_Link_Fixer\CLI\Scan_Command;

/**
 * Handles the registration of the CLI commands.
 */
class Commands {

	/**
	 * Register all commands.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function initialize(): void {
		( new Scan_Command() )->register();
	}
}
