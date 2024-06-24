<?php declare( strict_types = 1 );

use Symfony\Component\Finder\Finder;

return array(
	'finders'  => array(
		// Define which files to include when scoping. A good `name` array is array( '*.php', '*.json', 'LICENSE', 'composer.json' ).
		// https://github.com/humbug/php-scoper/blob/main/docs/configuration.md#finders-and-paths
	),
	'patchers' => array(
		static function ( string $filePath, string $prefix, string $content ): string {
			// Perform any manual changes to the file content here, e.g. `str_replace`.
			// https://github.com/humbug/php-scoper/blob/main/docs/configuration.md#patchers

			return $content;
		}
	),
);
