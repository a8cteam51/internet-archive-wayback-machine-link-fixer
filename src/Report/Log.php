<?php

/**
 * Report Log Model
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report;

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
	 * @var array<int, array{link: string, href:string, index:integer, message:string, fixed:string}>
	 */
	private array $broken_links = array();

	/**
	 * Replacements
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, array{index:integer, options:string[]}>
	 */
	private array $replacements = array();

	/**
	 * Create instance of Report Log.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $id           The log id.
	 * @param integer $report_id    The report id.
	 * @param integer $post_id      The post id.
	 * @param string  $broken_links The broken links as JSON.
	 * @param string  $replacements The replacements as JSON.
	 */
	public function __construct( int $id, int $report_id, int $post_id, string $broken_links, string $replacements ) {
		$this->id           = $id;
		$this->report_id    = $report_id;
		$this->post_id      = $post_id;
		$this->broken_links = (array) json_decode( $broken_links, true );
		$this->replacements = (array) json_decode( $replacements, true );
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
	 * @return array<int, array{link: string, href:string, index:integer, message:string, fixed:string}>
	 */
	public function get_broken_links(): array {
		return $this->broken_links;
	}

	/**
	 * Get the replacements.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{index:integer, options:string[]}>
	 */
	public function get_replacements(): array {
		return $this->replacements;
	}

	/**
	 * Get the broken links as JSON.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_broken_links_json(): string {
		$json = wp_json_encode( $this->broken_links );

		// If we dont have json, throw an error.
		if ( ! is_string( $json ) ) {
			throw new \Exception( 'Could not encode broken links as JSON.' );
		}

		return $json;
	}

	/**
	 * Get the replacements as JSON.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_replacements_json(): string {
		$json = wp_json_encode( $this->replacements );

		// If we dont have json, throw an error.
		if ( ! is_string( $json ) ) {
			throw new \Exception( 'Could not encode replacements as JSON.' );
		}

		return $json;
	}
}
