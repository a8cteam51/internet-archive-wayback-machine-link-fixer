<?php

/**
 * The Post Processor.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Processor;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;

/**
 * Post Processor class.
 */
class Post_Processor {

	/**
	 * The post scanner.
	 *
	 * @var Content_Scanner
	 */
	private $post_scanner;

	/**
	 * The Link Repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;


	/**
	 * Creates an instance of the Post Processor.
	 *
	 * @param integer $post_id The post id.
	 */
	public function __construct( int $post_id ) {
		$this->post_scanner    = Content_Scanner::for_post( $post_id );
		$this->link_repository = new Link_Repository();
	}

	/**
	 * Process the post.
	 *
	 * @return Link[]
	 */
	public function process(): array {
		// Get the current links for the post
		return array_map(
			array( $this->link_repository, 'find_or_create' ),
			$this->post_scanner->scan()->get_links()
		);
	}
}
