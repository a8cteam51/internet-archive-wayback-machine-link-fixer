<?php

/**
 * The WP_Post Controller.
 *
 * THIS FILE NEEDS WORKING ON.
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\WP_Post;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;

/**
 * The WP_Post Controller.
 */
class WP_Post_Controller {

	/**
	 * The link.
	 *
	 * @var Link
	 */
	private $link;

	/**
	 * Creates a new instance of the WP_Post Controller.
	 *
	 * @param Link $link The link.
	 */
	public function __construct( Link $link ) {
		$this->link = $link;
	}

	/**
	 * Get the link.
	 *
	 * @return Link The link.
	 */
	public function get_link(): Link {
		return $this->link;
	}
}
