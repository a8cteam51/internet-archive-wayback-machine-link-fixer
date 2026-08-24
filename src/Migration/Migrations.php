<?php

/**
 * The Database Migration Service.
 *
 * @since 0.1.0
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Migration;

use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * The migration class.
 */
class Migrations {

	/**
	 * The list of all migrations that should be run.
	 *
	 * @since 0.1.0
	 * @var class-string<Abstract_Migration>[]
	 */
	public static $migrations = array();

	/**
	 * Run the migrations on plugin activation.
	 *
	 * @since 0.1.0
	 *
	 * @param boolean $force Can be forced to run the migrations.
	 *
	 * @return void
	 */
	public static function up( bool $force = false ): void {
		// Get previous migrations.
		$previously_run_migrations = Settings::migrations();

		foreach ( self::$migrations as $migration ) {
			// If the migration has already been run, skip it.
			if ( true === $force
			|| ! in_array( $migration, $previously_run_migrations, true )
			) {
				( new $migration() )->up();

				// Add the migration to the list of migrations that have been run.
				$previously_run_migrations[] = $migration;
			}
		}

		// Update the list of migrations that have been run.
		Settings::update_migrations( $previously_run_migrations );
	}

	/**
	 * Run any pending migrations if the plugin version has changed.
	 *
	 * WordPress does not fire the activation hook on an update, so this is the only
	 * thing that gets a schema change onto a site that was already running the plugin.
	 *
	 * @since 1.4.4
	 *
	 * @return void
	 */
	public static function maybe_run(): void {
		if ( IAWMLF_VERSION === Settings::installed_version() ) {
			return;
		}

		self::up();

		Settings::update_installed_version( IAWMLF_VERSION );
	}

	/**
	 * Run the migrations on plugin uninstall.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function down(): void {
		// Get previous migrations.
		$previously_run_migrations = Settings::migrations();

		foreach ( array_reverse( self::$migrations ) as $migration ) {
			// A migration that never ran has nothing to undo.
			if ( ! in_array( $migration, $previously_run_migrations, true ) ) {
				continue;
			}

			( new $migration() )->down();

			// Remove the migration from the list of migrations that have been run.
			$previously_run_migrations = array_diff( $previously_run_migrations, array( $migration ) );
		}

		// Update the list of migrations that have been run.
		Settings::update_migrations( $previously_run_migrations );
	}
}
