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
use WPCOMSpecialProjects\Wayback_Link_Fixer\Ajax\Link_Check_Ajax;
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
		add_action( 'save_post', array( $this, 'on_save_post' ), 10, 3 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_script' ) );
	}

	/**
	 * Handles the save post action.
	 *
	 * @param integer  $post_id The post id.
	 * @param \WP_Post $post    The post object.
	 * @param boolean  $update  Whether this is an existing post being updated or not.
	 *
	 * @return void
	 */
	public function on_save_post( int $post_id, \WP_Post $post, bool $update ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		// Check the post type is one we are checking.
		if ( in_array( $post->post_type, Settings::get_allowed_post_types(), true ) === false ) {
			return;
		}

		$this->process_single_post( $post_id );
	}

	/**
	 * Process a single post.
	 *
	 * @param integer $post_id The post id.
	 *
	 * @return void
	 */
	public function process_single_post( int $post_id ): void {
		// Create an instance of the processor.
		$post_processor = new Post_Processor( $post_id );
		$links          = $post_processor->process();

		// Remove any excluded links.
		$links = $this->remove_excluded_links( $links );

		// Update the link meta.
		$this->update_link_meta( $post_id, $links );
	}


	/**
	 * Remove any excluded links from the links array.
	 *
	 * @param array<Link> $links The links.
	 *
	 * @return array<Link>
	 */
	private function remove_excluded_links( array $links ): array {
		$excluded = Settings::get_link_exclusions();

		// Filter the links using fn match on the url.
		return array_filter(
			$links,
			function ( Link $link ) use ( $excluded ): bool {
				foreach ( $excluded as $ex ) {
					if ( fnmatch( $ex, $link->get_href() ) ) {
						return false;
					}
				}

				return true;
			}
		);
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
			? $this->link_repository->get_links_for_post( $post_id )
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
				'linkDelayInDays' => \apply_filters( 'wlf_link_check_delay_in_days', 7 ),
				'ajaxUrl'         => \admin_url( 'admin-ajax.php' ),
				'isInternal'      => wpcomsp_wayback_link_fixer_can_activate() ? 1 : 0,
			)
		);

		// Enqueue the script.
		\wp_enqueue_script( 'wpcomsp-wayback-link-fixer-front-link-checker' );
	}
}
