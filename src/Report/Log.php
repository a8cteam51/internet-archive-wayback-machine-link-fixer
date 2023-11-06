<?php

/**
 * Report Log Model
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link;

/**
 * Report Log
 */
class Log {

	/**
	 * The Log id
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	private int $id;

	/**
	 * The report id
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	private int $report_id;

	/**
	 * The post id
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	private int $post_id;

	/**
	 * Broken links.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, Link>
	 */
	private array $links = array();

	/**
	 * Create instance of Report Log.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $id        The log id.
	 * @param integer $report_id The report id.
	 * @param integer $post_id   The post id.
	 * @param string  $links     The broken links as serialised array of Link models.
	 */
	public function __construct( int $id, int $report_id, int $post_id, string $links ) {
		$this->id        = $id;
		$this->report_id = $report_id;
		$this->post_id   = $post_id;
		$this->links     = (array) \maybe_unserialize( $links );
	}

	/**
	 * Get the log id.
	 *
	 * @since 1.0.0
	 *
	 * @return integer
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * Get the report id.
	 *
	 * @since 1.0.0
	 *
	 * @return integer
	 */
	public function get_report_id(): int {
		return $this->report_id;
	}

	/**
	 * Get the post id.
	 *
	 * @since 1.0.0
	 *
	 * @return integer
	 */
	public function get_post_id(): int {
		return $this->post_id;
	}

	/**
	 * Get the broken links.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, Link>
	 */
	public function get_links(): array {
		return $this->links;
	}

	/**
	 * Get the broken links as JSON.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_serialized_links(): string {
		$serialized = \serialize( $this->broken_links ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize

		// If we dont have json, throw an error.
		if ( ! is_string( $serialized ) ) {
			throw new \Exception( 'Could not serialize broken links.' );
		}

		return $serialized;
	}
}
