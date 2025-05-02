<?php

/**
 * Abstract Migration.
 *
 * @since 0.1.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Migration.
 */
abstract class Abstract_Migration {

	/**
	 * Runs on create/activation
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	abstract public function up(): void;

	/**
	 * Runs when on drop/deactivation
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	abstract public function down(): void;
}
