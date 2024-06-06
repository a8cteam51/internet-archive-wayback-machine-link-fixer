<?php

namespace WP_CLI\I18n;

use eftec\bladeone\BladeOne;

// Modified Gettext Blade extractor that
// uses the up-to-date BladeOne standalone Blade engine,
// correctly supports fromStringMultiple.

/**
 * Class to get gettext strings from blade.php files returning arrays.
 */
class BladeGettextExtractor extends \Gettext\Extractors\PhpCode {

	/**
	 * Prepares a Blade compiler/engine and returns it.
	 *
	 * @return BladeOne
	 */
	protected static function getBladeCompiler() {
		$cache_path     = empty( $options['cachePath'] ) ? sys_get_temp_dir() : $options['cachePath'];
		$blade_compiler = new BladeOne( null, $cache_path );

		if ( method_exists( $blade_compiler, 'withoutComponentTags' ) ) {
			$blade_compiler->withoutComponentTags();
		}

		return $blade_compiler;
	}

	/**
	 * Compiles the Blade template string into a PHP string in one step.
	 *
	 * @param string $text Blade string to be compiled to a PHP string
	 * @return string
	 */
	protected static function compileBladeToPhp( $text ) {
		return static::getBladeCompiler()->compileString( $text );
	}

	/**
	 * {@inheritdoc}
	 *
	 * Note: In the parent PhpCode class fromString() uses fromStringMultiple() (overriden here)
	 */
	public static function fromStringMultiple( $text, array $translations, array $options = [] ) {
		$php_string = static::compileBladeToPhp( $text );
		return parent::fromStringMultiple( $php_string, $translations, $options );
	}
}
