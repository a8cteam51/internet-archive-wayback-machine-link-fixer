<?php

/**
 * WordPress helpers for downloading and adding plugins/themes to test setups.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @since 1.0.0
 * @package Gin0115/WPUnit_Helpers
 *
 * @phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
 * @phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 * @phpcs:disable WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting
 * @phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
 * @phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
 */

declare( strict_types=1 );

namespace Gin0115\WPUnit_Helpers\WP;

use Exception;
use ZipArchive;
use ErrorException;
use Gin0115\WPUnit_Helpers\Output;

class WP_Dependencies {

	/**
	 * Callback for throwinf exception from php core errors.
	 *
	 * @param int $severity
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @return void
	 * @throws ErrorException
	 */
	protected static function exception_error_handler(
		int $severity,
		string $message,
		string $file,
		int $line
	) {
		if ( ! ( \error_reporting() & $severity ) ) {
			// @codeCoverageIgnoreStart
			return;
			// @codeCoverageIgnoreEnd
		}
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}

	/**
	 * Downloads and installs a plugin from a url
	 *
	 * @param string $url
	 * @param string $wp_base_path
	 * @return bool
	 * @throws Exception
	 */
	public static function install_remote_plugin_from_zip( string $url, string $wp_base_path ): bool {
		// Convert errors to exceptions.
		\set_error_handler( array( self::class, 'exception_error_handler' ) );

		Output::println( '' );
		Output::println( '**********************************************************************************' );
		Output::println( '******************************* DOWNLOADING PLUGIN *******************************' );
		Output::println( '************************************ FROM ZIP ************************************' );
		Output::println( '**********************************************************************************' );

		$zip       = new ZipArchive();
		$temp_file = \tmpfile();
		if ( $temp_file === false ) {
			// @codeCoverageIgnoreStart
			throw new Exception( 'Failed to create temp file' );
			// @codeCoverageIgnoreEnd
		}
		$temp_file = \stream_get_meta_data( $temp_file )['uri'];

		Output::println( \sprintf( '** Downloading zip from %s', $url ) );
		$download = \file_put_contents( $temp_file, fopen( $url, 'r' ) );
		// Ensure we have content and its a zip files.
		if ( $download === false || mime_content_type( $temp_file ) !== 'application/zip' ) {
			\unlink( $temp_file );
			\restore_error_handler();
			throw new Exception( "Failed to download remote zip for plugin from {$url}" );
		}

		Output::println( '** Opening Zip file......' );
		$plugin = $zip->open( $temp_file );
		if ( $plugin !== true ) {
			// @codeCoverageIgnoreStart
			\unlink( $temp_file );
			\restore_error_handler();
			throw new Exception( "Failed to open downloaded zip file from {$url}" );
			// @codeCoverageIgnoreEnd
		}

		Output::println( \sprintf( '** Extracting %d files..........', $zip->numFiles ) );
		$result = $zip->extractTo( $wp_base_path . '/wp-content/plugins/' );
		if ( $result === true ) {
			Output::println( \sprintf( '** Plugin installed to %s', $zip->getNameIndex( 0 ) ) );
		} else {
			// @codeCoverageIgnoreStart
			\unlink( $temp_file );
			\restore_error_handler();
			throw new Exception( 'Failed to extract plugin' );
			// @codeCoverageIgnoreEnd
		}

		$zip->close();
		\unlink( $temp_file );
		Output::println( '** Cleaned up all temp files, dont forget to activate this plugin in your bootstrap.' );
		Output::println( '**********************************************************************************' );
		Output::println( '' );

		\restore_error_handler();
		return $result;
	}

	/**
	 * Activates a pluging based on its dir/filename
	 *
	 * @param string $plugin
	 * @return void
	 */
	public static function activate_plugin( $plugin ): void {
		$current = \get_option( 'active_plugins' );
		$plugin  = \plugin_basename( \trim( $plugin ) );
		if ( ! \in_array( $plugin, $current, true ) ) {
			$current[] = $plugin;
			\sort( $current );
			\do_action( 'activate_plugin', \trim( $plugin ) );
			\update_option( 'active_plugins', $current );
			\do_action( 'activate_' . \trim( $plugin ) );
			\do_action( 'activated_plugin', \trim( $plugin ) );
		}
	}

	/**
	 * Checks if the passed plugin is installed.
	 *
	 * @param string $plugin
	 * @return bool
	 */
	public static function plugin_installed( string $plugin, string $wp_base_path ): bool {
		return \file_exists( $wp_base_path . '/wp-content/plugins/' . $plugin );
	}

	/**
	 * Checks if the passed plugin is active.
	 *
	 * @param string $plugin
	 * @return bool
	 */
	public static function plugin_active( $plugin ): bool {
		$current = \get_option( 'active_plugins' );
		$plugin  = \plugin_basename( \trim( $plugin ) );
		return \in_array( $plugin, $current, true );
	}


}
