<?php

/**
 * The main class which is used to analyse the content.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Analyser;

use Symfony\Component\DomCrawler\Crawler;
use DOMNode;



defined( 'ABSPATH' ) || exit;

/**
 * Content_Analyser
 */
class Content_Analyser {

	/**
	 * The raw content to analyse.
	 *
	 * @var string
	 */
	private string $content_raw;

	/**
	 * All errors
	 *
	 * @var array<int, array{element: DOMNode, index: int, message: string}> $errors
	 */
	private array $errors = array();

	/**
	 * Create instance of Content_Analyser.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content_raw The raw content to analyse.
	 */
	public function __construct( string $content_raw ) {
		$this->content_raw = $content_raw;
	}

	/**
	 * Add an error.
	 *
	 * @since 1.0.0
	 *
	 * @param DOMNode     $element The element which contains the error.
	 * @param integer     $index   The index of the error.
	 * @param string      $url     The URL which is causing the error.
	 * @param string|null $message The error message.
	 *
	 * @return void
	 */
	public function add_error( DOMNode $element, int $index, string $url, ?string $message = '' ) {
		$this->errors[] = array(
			'element' => $element,
			'index'   => $index,
			'message' => $message ?? '',
			'url'     => $url,
		);
	}

	/**
	 * Analyse the content.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function analyse() {

		// Convert to a DomDocument Model.
		$crawler = new Crawler( $this->content_raw );

		// Get all links.
		$links = $crawler->filter( 'a' );

		// Iterate over all the links.
		foreach ( $links as $index => $link_node ) {
			$attributes = $link_node->attributes;

			// If we have no attributes, add as an error.
			if ( ! $attributes ) {
				$this->add_error( $link_node, $index, 'n/a', 'no_href' );
				continue;
			}

			$node = $attributes->getNamedItem( 'href' );

			// If we have no node, add as an error.
			if ( ! $node ) {
				$this->add_error( $link_node, $index, 'n/a', 'no_href' );
				continue;
			}

			// Get the node value.
			$src = $node->nodeValue;  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			// If we dont have a valid URL, add as an error.
			if ( ! is_string( $src ) || ! filter_var( $src, FILTER_VALIDATE_URL ) ) {
				$this->add_error( $link_node, $index, 'n/a', 'malformed_url' );
			}

			/** @var string $src */ // phpcs:ignore
			$src = $src;

			$response = wp_remote_get( $src );
			// If response is a WP_Error, add as an error.
			if ( is_wp_error( $response ) ) {
				$this->add_error( $link_node, $index, $src, 'invalid_url' );
			}

			// Check we have a valid 200 response.
			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$this->add_error( $link_node, $index, $src, 'non_200' );
			}
		}
	}

	/**
	 * Checks if there are any errors.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function has_errors() {
		return ! empty( $this->errors );
	}

	/**
	 * Get all errors.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, array{element: DOMNode, index: int}>
	 */
	public function get_errors() {
		return $this->errors;
	}

	/**
	 * Returns the content as DomNodes.
	 *
	 * @since 1.0.0
	 *
	 * @return Crawler
	 */
	public function as_dom_crawler(): Crawler {
		return new Crawler( $this->content_raw );
	}
}
