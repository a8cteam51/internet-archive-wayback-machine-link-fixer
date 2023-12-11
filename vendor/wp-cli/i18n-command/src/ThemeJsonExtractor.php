<?php

namespace WP_CLI\I18n;

use Gettext\Extractors\Extractor;
use Gettext\Extractors\ExtractorInterface;
use Gettext\Translations;
use WP_CLI;
use WP_CLI\Utils;

final class ThemeJsonExtractor extends JsonSchemaExtractor {

	/**
	 * @inheritdoc
	 */
	public static function fromString( $text, Translations $translations, array $options = [] ) {
		$file = $options['file'];

		// Only support top-level theme.json file or any JSON file within a top-level styles/ folder.
		if (
			'theme.json' !== $file &&
			0 !== strpos( $file, 'styles/' )
		) {
			return;
		}

		parent::fromString( $text, $translations, $options );
	}
}
