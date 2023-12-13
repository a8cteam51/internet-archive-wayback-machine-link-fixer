<?php

/**
 * The main class which is used to analyse the content.
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Analyzer;

use Symfony\Component\DomCrawler\Crawler;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Link_Cache\Link_Cache;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Way_Back_Machine\Way_Back_Machine;

defined( 'ABSPATH' ) || exit;

/**
 * Content_Analyzer
 */
class Content_Analyzer {

	/**
	 * The raw content to analyse.
	 *
	 * @var string
	 */
	private string $content_raw;

	/**
	 * The post ID.
	 *
	 * @var integer
	 */
	private int $post_id;

	/**
	 * Should the link cache be used.
	 *
	 * @var boolean
	 */
	private bool $use_link_cache;

	/**
	 * All links
	 *
	 * @var array<int, Link> $links
	 */
	private array $links = array();

	/**
	 * Access to the link cache.
	 *
	 * @var Link_Cache
	 */
	private Link_Cache $link_cache;

	/**
	 * Access to the way back machine content.
	 *
	 * @var Way_Back_Machine
	 */
	private Way_Back_Machine $way_back_machine;

	/**
	 * Cache of the content from way back machine.
	 *
	 * @var string|null
	 */
	private ?string $way_back_machine_content = null;

	/**
	 * Create instance of Content_Analyzer.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $content_raw    The raw content to analyse.
	 * @param integer $post_id        The post ID.
	 * @param boolean $use_link_cache Whether to use the link cache or not.
	 */
	public function __construct( string $content_raw, int $post_id, bool $use_link_cache ) {
		$this->content_raw      = $content_raw;
		$this->post_id          = $post_id;
		$this->use_link_cache   = $use_link_cache;
		$this->link_cache       = Link_Cache::get_default();
		$this->way_back_machine = new Way_Back_Machine();
	}

		/**
	 * Adds a link to the link collection.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url  The href of the link.
	 * @param Link   $link The link to add.
	 *
	 * @return void
	 */
	public function add_link( string $url, Link $link ): void {
		// If we are not skipping the link cache, check if we have a cached version.
		if ( $this->use_link_cache ) {
			$this->link_cache->add_link( \untrailingslashit( $url ), $link );
		}

		$this->links[] = $link;
	}

	/**
	 * Add a broken link.
	 *
	 * @since
	 *
	 * @param integer      $index     The index of the link.
	 * @param string|null  $href      The href of the link.
	 * @param string|null  $contents  The contents of the link.
	 * @param string       $message   The error message.
	 * @param integer|null $http_code The status code of the link.
	 *
	 * @return void
	 */
	public function add_broken_link( int $index, ?string $href, ?string $contents, string $message, ?int $http_code = null ) {
		$link = new Link(
			$index,
			\untrailingslashit( $href ),
			$contents,
			true,
			null,
			$http_code,
			$this->find_link_in_way_back_machine_content( $contents ),
			array( $message )
		);

		// If we have a href, use the add_link, else manual.
		if ( $href ) {
			$this->add_link( \untrailingslashit( $href ), $link );
		} else {
			$this->links[] = $link;
		}
	}

	/**
	 * Attempt to find a link in the way back machine content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The content to search.
	 *
	 * @return string[]
	 */
	private function find_link_in_way_back_machine_content( string $content ): array {

		// If the way back machine content is not set, get it.
		if ( ! $this->way_back_machine_content ) {
			$page_url                       = get_permalink( $this->post_id );
			$this->way_back_machine_content = $this->way_back_machine->get_content( $page_url );
		}

		// Get all links from the content using teh DOM walker.
		$crawler = new Crawler( $this->way_back_machine_content );
		$links   = $crawler->filter( 'a' );

		// Iterate through all links and look for any which as the same content.
		$options = array();
		foreach ( $links as $link_node ) {
			if ( $content === $link_node->nodeValue ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$attributes = $link_node->attributes;
				$href_node  = $attributes->getNamedItem( 'href' ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( $href_node ) {
					// Remove and add trailing slash.(avoid dupes)
					$options[] = \untrailingslashit( $href_node->nodeValue ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				}
			}
		}

		// Return only unique options.
		return array_unique( $options );
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
		$chain = array_filter( $chain );

		// Get final url and trim it
		$final_url = $redirects[ array_key_last( $redirects ) ] ?? null;
		if ( $final_url ) {
			$final_url = trim( $final_url );
		}

		$link = new Link(
			$index,
			$href,
			$contents,
			// If $final_status_code status less than 200 and more than 300
			( 200 > $final_status_code || 300 <= $final_status_code ),
			$final_url,
			$redirect_type,
			array(),
			array_filter(
				array(
					$details,
					'Redirection chain:',
					join( ' >> ', array_map( 'trim', $chain ) ),
					"Final status code: {$final_status_code}",
				)
			)
		);

		// Add link.
		$this->add_link( $href, $link );
	}

	/**
	 * Analyze the content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $find_http_codes HTTP codes to look for.
	 *
	 * @return void
	 */
	public function analyze( string $find_http_codes ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		$http_codes = array_map( 'trim', explode( ',', $find_http_codes ) );
		// Cast all to integers.
		$http_codes = array_map( 'intval', $http_codes );

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
			$src = \untrailingslashit( $src );

			// If we dont have a valid URL, add as an error.
			if ( ! is_string( $src ) || ! filter_var( $src, FILTER_VALIDATE_URL ) ) {
				$this->add_broken_link( $index, $src, $link_node->nodeValue, 'malformed_href' );
				continue;
			}

			// If the link is in the excluded list, skip.
			if ( $this->is_excluded( $src ) ) {
				continue;
			}

			// Try to get the link details.
			try {

				// If we are not skipping the link cache, check if we have a cached version.
				if ( $this->use_link_cache ) {
					$cached_link = $this->get_cached_link( $src );
					if ( $cached_link ) {
						$this->links[] = $cached_link;
						continue;
					}
				}

				$link_details = $this->get_link_details( $src );

				// If the links HTTP code is not in the list of HTTP codes, skip.
				if ( ! in_array( (int) $link_details['http_code'], $http_codes, true ) ) {
					continue;
				}

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
					$this->add_link(
						$src,
						new Link(
							$index,
							$src,
							$link_node->nodeValue,
							false,
							null,
							$link_details['http_code'],
							array(),
							array( \wpcomsp_wayback_link_fixer_get_status_code_name( absint( $link_details['http_code'] ) ) )
						)
					);
					continue;
				}

				// Assume we have a 400 or 500 error.
				$this->add_broken_link(
					$index,
					$src,
					$link_node->nodeValue,
					\wpcomsp_wayback_link_fixer_get_status_code_name( absint( $link_details['http_code'] ) ),
					$link_details['http_code']
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
	 * Attempt to get a link from the cache.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The url to get the cached link for.
	 *
	 * @return Link|null
	 */
	private function get_cached_link( string $url ): ?Link {
		return $this->link_cache->find_link( $url );
	}

	/**
	 * Gets an
	 */



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

	/**
	 * Checks if a link is excluded.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url The url to check.
	 *
	 * @return boolean
	 */
	public function is_excluded( string $url ): bool {
		foreach ( Settings::get_link_exclusions() as $excluded_url ) {
			// Using fnmatch to allow for wildcards.
			if ( fnmatch( $excluded_url, $url ) ) {
				return true;
			}
		}
		return false;
	}
}
