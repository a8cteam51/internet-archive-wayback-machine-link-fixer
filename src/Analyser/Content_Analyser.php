<?php

/**
 * The main class which is used to analyse the content.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Analyser;

use Symfony\Component\DomCrawler\Crawler;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link;

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
	 * All links
	 *
	 * @var array<int, Link> $links
	 */
	private array $links = array();

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
	 * Add a broken link.
	 *
	 * @since
	 *
	 * @param integer     $index    The index of the link.
	 * @param string|null $href     The href of the link.
	 * @param string|null $contents The contents of the link.
	 * @param string      $message  The error message.
	 *
	 * @return void
	 */
	public function add_broken_link( int $index, ?string $href, ?string $contents, string $message ) {
		$this->links[] = new Link(
			$index,
			$href,
			$contents,
			true,
			null,
			null,
			array(),
			false,
			array( $message )
		);
	}

	/**
	 * Adds a redirection link.
	 *
	 * @since 1.0.0
	 *
	 * @param integer            $index             The index of the link.
	 * @param string|null        $href              The href of the link.
	 * @param string|null        $contents          The contents of the link.
	 * @param array<int, string> $redirects         The redirects.
	 * @param integer            $redirect_type     The redirect type.
	 * @param integer            $final_status_code The status code of the final url.
	 * @param string|null        $details           The details of the redirection.
	 *
	 * @return void
	 */
	public function add_redirection_link( int $index, ?string $href, ?string $contents, array $redirects, int $redirect_type, int $final_status_code, ?string $details = null ) {
		$chain = array_merge( array( $href ), $redirects );

		// Get final url and trim it
		$final_url = $redirects[ array_key_last( $redirects ) ] ?? null;
		if ( $final_url ) {
			$final_url = trim( $final_url );
		}

		$this->links[] = new Link(
			$index,
			$href,
			$contents,
			200 <= $final_status_code && 300 > $final_status_code,
			$final_url,
			$redirect_type,
			array(),
			false,
			array_filter(
				array(
					$details,
					'Redirection chain:',
					join( ' >> ', array_map( 'trim', $chain ) ),
					"Final status code: {$final_status_code}",
				)
			)
		);
	}

	/**
	 * Analyze the content.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function analyze() {

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// Convert to a DomDocument Model.
		$crawler = new Crawler( $this->content_raw );

		// Get all links.
		$links = $crawler->filter( 'a' );

		// Iterate over all the links.
		foreach ( $links as $index => $link_node ) {
			$attributes = $link_node->attributes;

			// If we have no attributes, add as an error.
			if ( 0 === count( $attributes ) ) {
				$this->add_broken_link( $index, null, $link_node->nodeValue, 'no_attributes' );
				continue;
			}

			$href_node = $attributes->getNamedItem( 'href' );

			// If we have no node, add as an error.
			if ( ! $href_node ) {
				$this->add_broken_link( $index, null, $link_node->nodeValue, 'no_href' );
				continue;
			}

			// Get the node value.
			$src = $href_node->nodeValue;

			// If we dont have a valid URL, add as an error.
			if ( ! is_string( $src ) || ! filter_var( $src, FILTER_VALIDATE_URL ) ) {
				$this->add_broken_link( $index, $src, $link_node->nodeValue, 'malformed_href' );
				continue;
			}

			// Try to get the link details.
			try {
				$link_details = $this->get_link_details( $src );

				// If we have any 30* status code, treat as a redirect
				if ( 300 <= $link_details['http_code'] && 400 > $link_details['http_code'] ) {
					$redirect_details = $this->get_link_details( $src, true );
					$this->add_redirection_link(
						$index,
						$src,
						$link_node->nodeValue,
						$redirect_details['redirects'],
						$link_details['http_code'],
						$redirect_details['http_code']
					);
					continue;
				}

				// If we have a valid link 20* status code, add as a link.
				if ( 200 <= $link_details['http_code'] && 300 > $link_details['http_code'] ) {
					$this->links[] = new Link(
						$index,
						$src,
						$link_node->nodeValue,
						false,
						null,
						null,
						array(),
						false,
						array( 'valid_link', "status_code: {$link_details['http_code']}" )
					);
					continue;
				}

				// Assume we have a 400 or 500 error.
				$this->add_broken_link(
					$index,
					$src,
					$link_node->nodeValue,
					"status_code: {$link_details['http_code']}"
				);
				continue;
			} catch ( \Throwable $th ) {
				$this->add_broken_link(
					$index,
					$src,
					$link_node->nodeValue,
					"Error getting link details : {$th->getMessage()}"
				);
			}
		}
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Get link details.
	 * Follows a url, collects data on its status and will follow any redirects if requested.
	 *
	 * @param string  $url              The URL of the link.
	 * @param boolean $follow_redirects Whether to follow redirects or not.
	 *
	 * @return array<string, string> An array of data about the link.
	 */
	private function get_link_details( string $url, bool $follow_redirects = false ): array {
		// phpcs:disable
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, $follow_redirects );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_TIMEOUT_MS, Settings::get_link_checker_timeout() );
		curl_setopt( $ch, CURLOPT_HEADER, true );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:21.0) Gecko/20100101 Firefox/21.0' ); // Necessary. The server checks for a valid User-Agent.
		curl_exec( $ch );

		// If we get a timeout throw exception.
		if ( curl_errno( $ch ) ) {
			curl_close( $ch );
			throw new \Exception( curl_error( $ch ) );
		}

		$redirect_url = curl_getinfo( $ch, CURLINFO_REDIRECT_URL );
		$http_code    = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$response     = curl_exec( $ch );
		preg_match_all( '/^Location:(.*)$/mi', $response, $matches );
		curl_close( $ch );

		// Collect the statuses
		return array(
			'url'          => $url,
			'redirects'    => $matches[1] ?? array(),
			'http_code'    => $http_code,
			'redirect_url' => $redirect_url,
		);
		// phpcs:enable
	}

	/**
	 * Checks if there are any links.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function has_links() {
		return ! empty( $this->links );
	}

	/**
	 * Get all links.
	 *
	 * @since 1.0.0
	 *
	 * @return Link[]
	 */
	public function get_links() {
		return $this->links;
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
