<?php

/**
 * Handles the various post actions.
 *
 * Registered as an integration in src/Integrations.php
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\WP_Post;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Exclusion;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Ajax\Link_Check_Ajax;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Event\Process_Local_Post_Event;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Link\Link_Repository;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Processor\Post_Processor;

/**
 * Handles the various post actions.
 */
class WP_Post_Controller {

	/**
	 * The link repository.
	 *
	 * @var Link_Repository
	 */
	private $link_repository;

	/**
	 * Creates a new instance of the post handler.
	 */
	public function __construct() {
		$this->link_repository = new Link_Repository();
	}

	/**
	 * Initialize and register all hooks
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->register_hooks();
	}

	/**
	 * Register all hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		add_action( 'save_post', array( $this, 'on_save_post_process_post_links' ), 10, 3 );
		add_action( 'save_post', array( $this, 'on_save_post_process_own_post' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_script' ) );
		add_filter( 'render_block', array( $this, 'render_block' ), 999, 2 );  }

	/**
	 * Handles the save post action.
	 *
	 * @param integer  $post_id The post id.
	 * @param \WP_Post $post    The post object.
	 * @param boolean  $update  Whether this is an existing post being updated or not.
	 *
	 * @return void
	 */
	public function on_save_post_process_post_links( int $post_id, \WP_Post $post, bool $update ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		// Check the post type is one we are checking.
		if ( in_array( $post->post_type, Settings::get_allowed_post_types(), true ) === false ) {
			return;
		}

		$this->process_links_in_content( $post_id );
	}

	/**
	 * Handles the save post action for adding own links.
	 *
	 * @param integer  $post_id The post id.
	 * @param \WP_Post $post    The post object.
	 * @param boolean  $update  Whether this is an existing post being updated or not.
	 *
	 * @return void
	 */
	public function on_save_post_process_own_post( int $post_id, \WP_Post $post, bool $update ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// If doing auto save, return.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check if we should be adding our own links.
		if ( ! Settings::add_own_links() ) {
			return;
		}

		// Get the allowed post types.
		if ( ! in_array( $post->post_type, Settings::own_link_allowed_post_types(), true ) ) {
			return;
		}

		// If the post is not published, return.
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		try {
			$this->add_own_post_to_wayback_machine( $post_id );
		} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Do nothing! Squiz.PHP.CommentedOutCode.Found
		}
	}

	/**
	 * Adds the permalink of a post to the wayback machine.
	 *
	 * @param integer $post_id The post id.
	 *
	 * @return void
	 *
	 * @throws \Exception If the post id is not valid.
	 */
	public function add_own_post_to_wayback_machine( int $post_id ): void {

		// Get the post.
		$post = get_post( $post_id );

		// If the post is not valid, throw an exception.
		if ( ! $post ) {
			throw new \Exception( 'Invalid post id' );
		}

		$can_add = \apply_filters( 'wlf_own_content_allow_post', true, $post );

		if ( $can_add ) {
			Process_Local_Post_Event::add_to_queue_with_delay( $post_id );
		}
	}

	/**
	 * Process a single post.
	 *
	 * @param integer $post_id The post id.
	 *
	 * @return void
	 */
	public function process_links_in_content( int $post_id ): void {
		// Create an instance of the processor.
		$post_processor = new Post_Processor( $post_id );
		$links          = $post_processor->process();

		// Remove any excluded links.
		$links = Link_Exclusion::get_instance()->filter_excluded( $links );

		// Update the link meta.
		$this->update_link_meta( $post_id, $links );
	}

	/**
	 * Update link meta for a post.
	 *
	 * @param integer $post_id The post id.
	 * @param Link[]  $links   The links.
	 *
	 * @return void
	 */
	private function update_link_meta( int $post_id, array $links ): void {
		// Get the link ids.
		$links = array_map(
			function ( Link $link ): int {
				return absint( $link->get_id() );
			},
			$links
		);

		// Update the post meta.
		update_post_meta( $post_id, Settings::LINK_META_KEY, array_filter( $links ) );
	}

	/**
	 * Enqueue the frontend script for rendering the links.
	 *
	 * @return void
	 */
	public function enqueue_frontend_script(): void {
		// Get the post id.
		$post_id = get_the_ID();

		// Get the links.
		$links = is_numeric( $post_id ) && get_post_status( $post_id )
			? $this->link_repository->get_links_for_post( $post_id, true )
			: array();

		// Get the scripts assets file.
		$script_assets = WPCOMSP_WAYBACK_LINK_FIXER_PATH . 'assets/js/build/front_link_checker.asset.php';

		// If the script cant be found, return.
		if ( ! file_exists( $script_assets ) ) {
			return;
		}

		$assets = require $script_assets;

		// Register the script.
		\wp_register_script(
			'wpcomsp-wayback-link-fixer-front-link-checker',
			WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/js/build/front_link_checker.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);

		// localise the script.
		\wp_localize_script(
			'wpcomsp-wayback-link-fixer-front-link-checker',
			'wlfArchivedLinks',
			array(
				'links'           => wp_json_encode( $links ),
				'linkCheckAjax'   => Link_Check_Ajax::ACTION,
				'linkCheckNonce'  => wp_create_nonce( Link_Check_Ajax::ACTION ),
				'linkDelayInDays' => Settings::get_link_check_duration(),
				'fixerOption'     => Settings::get_fixer_option(),
				'ajaxUrl'         => \admin_url( 'admin-ajax.php' ),

			)
		);

		// Enqueue the script.
		\wp_enqueue_script( 'wpcomsp-wayback-link-fixer-front-link-checker' );
	}

	/**
	 * Render as part of a block template.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The block.
	 *
	 * @return string
	 */
	public function render_block( string $block_content, array $block ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		static $posts = array();

		$post_id = get_the_ID();

		// If the ID is not set, or is in the array, return.
		if ( ! is_numeric( $post_id ) || in_array( $post_id, $posts, true ) ) {
			return $block_content;
		}

		// Add the post id to the array.
		$posts[] = $post_id;

		// If not a post or a an allowed post type, return.
		$post = \get_post( $post_id );
		if ( ! $post || ! in_array( $post->post_type, Settings::get_allowed_post_types(), true ) ) {
			return $block_content;
		}

		// Compile the data.
		$links = $this->link_repository->get_links_for_post( $post_id );

		if ( empty( $links ) ) {
			return $block_content;
		}
		$json      = wp_json_encode( $links );
		$html_data = "<div class='__wlf-post-loop-links' style='display:none;' data-wlf-post-links='{$json}'></div>";

		return $html_data . $block_content;
	}
}
