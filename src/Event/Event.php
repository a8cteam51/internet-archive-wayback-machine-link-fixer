<?php

/**
 * Model and handler for an event.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Event;

use Serializable;
use JsonSerializable;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Report;

/**
 * Event Model
 */
class Event implements Serializable {

	/**
	 * The post ids that need to be processed.
	 *
	 * @since 1.0.0
	 *
	 * @var integer[]
	 */
	private array $post_ids;

	/**
	 * The HTTP codes to scan for.
	 *
	 * @since 1.0.0
	 *
	 * @var integer[]
	 */
	private array $http_codes;

	/**
	 * Should the cache be ignored?
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	private bool $ignore_cache;

	/**
	 * Holds the Report Object.
	 *
	 * @since 1.0.0
	 *
	 * @var Report
	 */
	private Report $report;

	/**
	 * Holds all processed post ids.
	 *
	 * @since 1.0.0
	 *
	 * @var integer[]
	 */
	private array $processed_post_ids = array();

	/**
	 * Creates a new instance of the Event model.
	 *
	 * @since 1.0.0
	 *
	 * @param integer[] $post_ids     The post ids that need to be processed.
	 * @param integer[] $http_codes   The HTTP codes to scan for.
	 * @param boolean   $ignore_cache Should the cache be ignored?.
	 * @param Report    $report       The report object.
	 */
	public function __construct(
		array $post_ids,
		array $http_codes,
		bool $ignore_cache,
		Report $report
	) {
		$this->post_ids     = array_map( 'absint', $post_ids );
		$this->http_codes   = array_map( 'sanitize_text_field', $http_codes );
		$this->ignore_cache = $ignore_cache;
		$this->report       = $report;
	}

	/**
	 * Serialize the object.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function serialize(): string {
		return serialize( //phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			array(
				'post_ids'     => $this->post_ids,
				'http_codes'   => $this->http_codes,
				'ignore_cache' => $this->ignore_cache,
				'report'       => serialize( $this->report ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
				'processed'    => $this->processed_post_ids,
			)
		);
	}

	/**
	 * Unserialize the object.
	 *
	 * @since 1.0.0
	 *
	 * @param string $serialized The serialized object.
	 *
	 * @return void
	 */
	public function unserialize( $serialized ): void {
		$data = unserialize( $serialized ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize

		$this->post_ids           = array_map( 'absint', $data['post_ids'] );
		$this->http_codes         = array_map( 'sanitize_text_field', $data['http_codes'] );
		$this->ignore_cache       = (bool) $data['ignore_cache'];
		$this->report             = \unserialize( $data['report'] ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		$this->processed_post_ids = array_map( 'absint', $data['processed'] );
	}

	/**
	 * Get the next post id to process.
	 *
	 * @since 1.0.0
	 *
	 * @return integer|null
	 */
	public function get_next_post_id(): ?int {
		foreach ( $this->post_ids as $key => $post_id ) {
			if ( ! in_array( $post_id, $this->processed_post_ids, true ) ) {
				return $post_id;
			}
		}
		return null;
	}

	/**
	 * Update the report with a new instance.
	 *
	 * @since 1.0.0
	 *
	 * @param Report $report The report object.
	 *
	 * @return self
	 */
	public function update_report( Report $report ): self {
		$this->report = $report;
		return $this;
	}

	/**
	 * Add a processed post id.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $post_id The post id.
	 *
	 * @return self
	 */
	public function add_processed_post_id( int $post_id ): self {
		$this->processed_post_ids[] = $post_id;
		return $this;
	}

	/**
	 * Should the cache be ignored?
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function ignore_cache(): bool {
		return $this->ignore_cache;
	}

	/**
	 * Get the report
	 *
	 * @since 1.0.0
	 *
	 * @return Report
	 */
	public function get_report(): Report {
		return $this->report;
	}

	/**
	 * Get the HTTP codes to scan for.
	 *
	 * @since 1.0.0
	 *
	 * @return integer[]
	 */
	public function get_http_codes(): array {
		return $this->http_codes;
	}

	/**
	 * Get the processed post ids.
	 *
	 * @since 1.0.0
	 *
	 * @return integer[]
	 */
	public function get_processed_post_ids(): array {
		return $this->processed_post_ids;
	}

	/**
	 * Get the post ids that need to be processed.
	 *
	 * @since 1.0.0
	 *
	 * @return integer[]
	 */
	public function get_post_ids(): array {
		return $this->post_ids;
	}

	/**
	 * Checks if there are more events to process.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function has_more_events(): bool {
		return count( $this->post_ids ) > count( $this->processed_post_ids );
	}
}
