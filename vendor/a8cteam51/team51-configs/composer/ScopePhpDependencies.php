<?php

namespace WPCOMSpecialProjects\Config\Composer;

use Composer\Script\Event;

/**
 * Static Composer commands to be executed on certain script events to scope PHP dependencies after every update.
 */
class ScopePhpDependencies {
	/**
	 * When running the PHP scoper, the Composer autoloader will be executed first. However, that will throw a fatal
	 * error if it contains files and directories that are scoped and which haven't been generated yet (e.g., when installing
	 * the packages immediately after cloning the repository). Particularly, the `files` and `classmap` autoloaders are affected.
	 *
	 * This method is meant to ensure that any such files exist before the autoloader is executed to prevent
	 * 'file-not-found' fatal errors.
	 *
	 * @param   Event   $event  Composer event object.
	 *
	 * @throws  \JsonException      If the composer.json file cannot be parsed.
	 * @throws  \RuntimeException   If the file or directory cannot be created.
	 *
	 * @return  void
	 */
	public static function preAutoloadDump( Event $event ): void {
		$console_io = $event->getIO();
		$vendor_dir = $event->getComposer()->getConfig()->get( 'vendor-dir' );

		$console_io->write( 'Making sure autoloaded files exist...' );

		$composer_config = file_get_contents( dirname( $vendor_dir ) . '/composer.json' );
		$composer_config = json_decode( $composer_config, true, 512, JSON_THROW_ON_ERROR );

		$autoloaded_files       = $composer_config['autoload']['files'] ?? array();
		$autoloaded_directories = $composer_package['autoload']['classmap'] ?? array();
		if ( $event->isDevMode() ) {
			$autoloaded_files       = array_merge( $autoloaded_files, $composer_config['autoload-dev']['files'] ?? array() );
			$autoloaded_directories = array_merge( $autoloaded_directories, $composer_package['autoload-dev']['classmap'] ?? array() );
		}

		foreach ( $autoloaded_files as $file ) {
			$file = dirname( $vendor_dir ) . DIRECTORY_SEPARATOR . $file;
			if ( ! file_exists( $file ) ) {
				$file_directory = dirname( $file );
				if ( ! is_dir( $file_directory ) && ! mkdir( $file_directory , 0755, true ) && ! is_dir( $file_directory ) ) {
					throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $file_directory ) );
				}
				if ( ! touch( $file ) ) {
					throw new \RuntimeException( sprintf( 'File "%s" was not created', $file ) );
				}
			}
		}

		foreach ( $autoloaded_directories as $directory ) {
			$directory = dirname( $vendor_dir ) . DIRECTORY_SEPARATOR . $directory;
			if ( ! is_dir( $directory ) && ! mkdir( $directory, 0755, true ) && ! is_dir( $directory ) ) {
				throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $directory ) );
			}
		}
	}

	/**
	 * The PHP scoper and the to-be-scoped packages only exist in the development environment so this method acts
	 * as a wrapper to scope the dependencies only when needed, and always after the packages have been installed or updated.
	 *
	 * @param   Event   $event  Composer event object.
	 *
	 * @return  void
	 */
	public static function postAutoloadDump( Event $event ): void {
		$console_io = $event->getIO();
		$vendor_dir = $event->getComposer()->getConfig()->get( 'vendor-dir' );

		if ( ! $event->isDevMode() ) {
			$console_io->warning( 'Not scoping dependencies because this is not a development environment.' );
			return;
		}
		if ( ! is_file( $vendor_dir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'php-scoper' ) ) {
			$console_io->write( 'Not scoping dependencies because the PHP scoper is not installed.' );
			return;
		}

		$console_io->write( 'Scoping dependencies...' );

		$event_dispatcher = $event->getComposer()->getEventDispatcher();
		$event_dispatcher->dispatchScript( 'scope-php-dependencies', $event->isDevMode() );
	}
}
