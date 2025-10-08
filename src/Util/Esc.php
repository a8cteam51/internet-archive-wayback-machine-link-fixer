<?php

/**
 * Collection of functions to help with escaping and sanitizing data.
 *
 * @since 1.3.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Util;

defined( 'ABSPATH' ) || exit;

/**
 * Collection of functions to help with escaping and sanitizing data.
 */
class Esc {


	/**
	 * Returns the wp_kses allowed tags for the wizard.
	 *
	 * @return array<string array<string>>
	 */
	public static function wizard_allowed_tags(): array {
		return array(
				'div' => array(
					'class' => array(),
					'id' => array(),
					'style' => array(),
				),
				'form' => array(
					'method' => array(),
					'action' => array(),
				),
				'input' => array(
					'type' => array(),
					'name' => array(),
					'value' => array(),
					'id' => array(),
				),
				'button' => array(
					'type' => array(),
					'name' => array(),
					'class' => array(),
				),

			);
	}
}
