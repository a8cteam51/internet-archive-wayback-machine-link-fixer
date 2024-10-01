<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

require __DIR__ . '/vendor/autoload.php';

$finder = ( new Finder() )
	->files()
	->name( '*.php' )
	->in( __DIR__ . '/src' )
	->in( __DIR__ . '/Tests' );

/**
 * Cache file for PHP-CS
 */
$cacheFilePath = sprintf( '%s%sphp_cs.cache-%s', sys_get_temp_dir(), DIRECTORY_SEPARATOR, md5( __DIR__ ) );

/**
 * Configuration
 *
 * @see https://mlocati.github.io/php-cs-fixer-configurator/#
 */
return ( new Config( 'function-constructors' ) )
	->setCacheFile( $cacheFilePath )
	->setRiskyAllowed( true )
	->setRules(
		array(
			// default
			// '@PSR12'                     => true,
			// '@Symfony'                   => true,
			// additionally
			'array_syntax'               => array( 'syntax' => 'short' ),
			'concat_space'               => array( 'spacing' => 'one' ),
			'cast_spaces'                => array( 'space' => 'single' ),
			'no_unused_imports'          => true,
			'no_useless_else'            => true,
			'no_useless_return'          => true,
			'no_superfluous_phpdoc_tags' => array(
				'allow_mixed'         => true,
				'allow_unused_params' => true,
			),
			'ordered_imports'            => array(
				'sort_algorithm' => 'alpha',
				'imports_order'  => array( 'const', 'class', 'function' ),
			),
			// 'phpdoc_align'               => array( 'align' => 'vertical' ),
			// 'phpdoc_order'               => false,
			// 'phpdoc_order_by_value'      => array( 'author', 'covers', 'coversNothing', 'dataProvider', 'depends', 'group', 'internal', 'method', 'property', 'property-read', 'property-write', 'requires', 'throws', 'uses' ),
			'phpdoc_trim'                => false,
			'phpdoc_types_order'         => array(
				'null_adjustment' => 'always_last',
				'sort_algorithm'  => 'none',
			),
			'phpdoc_summary'             => false,
			'simplified_null_return'     => false,
			'ternary_to_null_coalescing' => true,
			'binary_operator_spaces'     => array( 'default' => 'align' ),
			'global_namespace_import'    => array(
				'import_classes'   => true,
				'import_functions' => true,
			),
		)
	)
	->setFinder( $finder );
