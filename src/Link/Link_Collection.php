<?php

/**
 * Handles the collection of links for a given post.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Link;

/**
 * Link collection class.
 */
class Link_Collection implements \JsonSerializable {

	/**
	 * The post ID.
	 *
	 * @var integer
	 */
	private $post_id;

	/**
	 * The collection of links
	 *
	 * @var Link[]
	 */
	private $links;

	/**
	 * Creates a new instance of the link collection.
	 *
	 * @param integer $post_id The post ID.
	 */
	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
		$this->links   = array();
	}

	/**
	 * Get the post id.
	 *
	 * @return integer
	 */
	public function get_post_id(): int {
		return $this->post_id;
	}

	/**
	 * Add a link to the collection.
	 *
	 * @param Link $link The link to add.
	 *
	 * @return void
	 */
	public function add( Link $link ): void {
		$this->links[] = $link;
	}

	/**
	 * Get links.
	 *
	 * @return Link[]
	 */
	public function get_links(): array {
		return $this->links;
	}

	/**
	 * Check if the collection is empty.
	 *
	 * @return boolean
	 */
	public function is_empty(): bool {
		return empty( $this->links );
	}

	/**
	 * Gets the links as JSON.
	 *
	 * @return string
	 */
	public function to_json(): string {
		return wp_json_encode( $this->jsonSerialize() );
	}

	/**
	 * Get the links as an array.
	 *
	 * @since 1.1.1
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		return $this->links;
	}
}
