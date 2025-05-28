<?php

/**
 * Implementation of the Wayback Machine Client.
 *
 * Uses the public Wayback Machine API to interact with the Wayback Machine.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\HTTP_Client;

use Exception;
use Throwable;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Snapshot_Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Service_Offline_Exception;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Invalid_Response_Exception;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Exception\Exceeded_Snapshot_Limit_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * The Wayback Machine Rest Client.
 */
class HTTP_Snapshot_Client implements Snapshot_Client {

	/**
	 * Checks if a URL is in the Wayback Machine.
	 *
	 * @since 1.2.0
	 *
	 * @param string $url The URL to check.
	 *
	 * @return boolean True if the URL is in the Wayback Machine, false otherwise.
	 */
	public function has_snapshot( string $url ): bool {
		return null !== $this->get_latest_snapshot( $url );
	}

	/**
	 * Get the base url for finding a snapshot.
	 *
	 * @since 1.2.0
	 *
	 * @return string The base url.
	 */
	public function get_base_url(): string {
		return trailingslashit(
			untrailingslashit(
				apply_filters( 'wlf_find_snapshot_base_url', 'https://archive.org/wayback/available/' )
			)
		);
	}

	/**
	 * Compiles the header with auth credentials if they are set.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, string> The headers.
	 */
	private function get_headers(): array {
		$headers = array();

		// Add the auth header if set.
		if ( Settings::is_archive_api_configured() && Settings::has_valid_archive_api_credentials() ) {
			$headers['Authorization'] = sprintf( 'LOW %s:%s', Settings::get_archive_access_key(), Settings::get_archive_secret_key() );
		}

		return $headers;
	}

	/**
	 * Gets the latest snapshot for a given URL.
	 *
	 * @since 1.2.0
	 *
	 * @param string $url The URL to check.
	 *
	 * @return array{status:int, available:boolean, url:string, timestamp:string}|null
	 */
	public function get_latest_snapshot( string $url ): ?array {

		// Normalize the url.
		$url = wpcomsp_wayback_link_fixer_normalize_url( $url );

		$api_url = $this->get_base_url();

		// add the url to the query string
		$url = add_query_arg( 'url', esc_url_raw( $url ), $api_url );

		$url = apply_filters( 'wlf_get_latest_snapshot_url', $url, $api_url );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => apply_filters( 'wlf_get_latest_snapshot_timeout', 10 ),
				'sslverify' => false,
			)
		);
		return $this->extract_response( $response );
	}

	/**
	 * Gets the closest snapshot to a given date for a given URL.
	 *
	 * @since 1.2.0
	 *
	 * @param string    $url  The URL to check.
	 * @param \DateTime $date The date to check.
	 *
	 * @return array{status:int, available:boolean, url:string, timestamp:string}|null
	 */
	public function get_closest_snapshot( string $url, \DateTime $date ): ?array {

		// Strip any trailing slash from url.
		$url = wpcomsp_wayback_link_fixer_normalize_url( $url );

		$api_url = $this->get_base_url();

		// add the url to the query string
		$api_url = add_query_arg( 'url', esc_url_raw( $url ), $api_url );

		// add the timestamp to the query string
		$api_url = add_query_arg( 'timestamp', $date->format( 'Ymd' ), $api_url );

		// Allow the url to be filtered.
		$api_url = apply_filters( 'wlf_get_closest_snapshot_url', $api_url, $url, $date );

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout'   => apply_filters( 'wlf_get_closest_snapshot_timeout', 10 ),
				'sslverify' => false,
			)
		);

		return $this->extract_response( $response );
	}

	/**
	 * Create a snapshot of a given URL.
	 *
	 * @since 1.2.0
	 *
	 * @param string $url The URL to snapshot.
	 *
	 * @return string The job id.
	 *
	 * @throws Service_Offline_Exception If the service is offline.
	 * @throws Exception If the response is invalid.
	 * @throws Exceeded_Snapshot_Limit_Exception If the snapshot limit is exceeded.
	 */
	public function create_snapshot( string $url ): string {

		// Strip any trailing slash from url.
		$url = wpcomsp_wayback_link_fixer_normalize_url( $url );

		$base_url = 'https://web.archive.org/save/';

		// Base url.
		$snapshot_url = apply_filters( 'wlf_create_snapshot_url', $base_url );

		// Trigger a post request with URL as a body param.
		$response = wp_remote_post(
			esc_url( $snapshot_url ),
			array(
				'timeout'   => apply_filters( 'wlf_create_snapshot_timeout', 1000 ),
				'body'      => array( 'url' => $url ),
				'sslverify' => false,
				'headers'   => $this->get_headers(),
			)
		);

		// dd(
		//  $this->get_headers(),
		//  esc_url( $snapshot_url ),
		//  array(
		//      'timeout'   => apply_filters( 'wlf_create_snapshot_timeout', 1000 ),
		//      'body'      => array( 'url' => $url ),
		//      'sslverify' => false,
		//      'headers'   => $this->get_headers(),
		//  )
		// );

		// If we have a wp error, throw invalid response exception.
		if ( is_wp_error( $response ) ) {
			throw Invalid_Response_Exception::create( esc_html( $response->get_error_message() ) );
		}

		// if we dont have a 200 response, throw an exception.
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			throw Service_Offline_Exception::create( esc_html( 'Response:' . wp_remote_retrieve_response_code( $response ) ) );
		}

		// Attempt to get the job id from body.
		$response_body = wp_remote_retrieve_body( $response );

		// If the body contains exceeded snapshot limit, throw an exception.
		if ( $this->is_exceeded_snapshot_limit( $response_body ) ) {
			throw Exceeded_Snapshot_Limit_Exception::create();
		}

		// using regex attempt to get the id from spn.watchJob("{THIS}", "https://web-static.archive.org/_static/","
		preg_match( '/spn.watchJob\("(.+?)"/', $response_body, $matches );

		// If we have no matches, throw an exception.
		if ( empty( $matches ) ) {
			throw new Exception( 'Failed to find job id.' );
		}

		return esc_attr( $matches[1] );
	}

	/**
	 * Checks if the response contains exceeded snapshot limit.
	 *
	 * @since 1.3.0
	 *
	 * @param string $response_body The response body.
	 *
	 * @return boolean True if the response contains exceeded snapshot limit, false otherwise.
	 */
	private function is_exceeded_snapshot_limit( string $response_body ): bool {
		// Check if the body contains "You cannot make more than (200,) captures per day." (or any number)
		return preg_match( '/You cannot make more than \(\d+,\) captures per day\./', $response_body ) === 1;
	}

	/**
	 * Extracts a HTTP response.
	 *
	 * @param array|WP_Error $response The response to extract.
	 *
	 * @return array{status:int, available:boolean, url:string, timestamp:string}|null The extracted response.
	 */
	private function extract_response( $response ): ?array {
		if ( is_wp_error( $response ) ) {
			return null;
		}

		$response_body = wp_remote_retrieve_body( $response );

		if ( ! $response_body ) {
			return null;
		}

		$response_body = json_decode( $response_body, true );

		// If not an array.
		if ( ! is_array( $response_body ) ) {
			return null;
		}

		// If response has `archived_snapshots`
		if ( ! array_key_exists( 'archived_snapshots', $response_body )
		|| empty( $response_body['archived_snapshots'] )
		|| ! is_array( $response_body['archived_snapshots'] )
		) {
			return null;
		}

		// If response has `closest`
		if ( ! array_key_exists( 'closest', $response_body['archived_snapshots'] )
		|| empty( $response_body['archived_snapshots']['closest'] )
		|| ! is_array( $response_body['archived_snapshots']['closest'] )
		) {
			return null;
		}

		// If response has `available` and is true.
		if ( ! array_key_exists( 'available', $response_body['archived_snapshots']['closest'] )
		|| ! $response_body['archived_snapshots']['closest']['available']
		) {
			return null;
		}

		return array(
			'status'    => $response_body['archived_snapshots']['closest']['status'],
			'available' => $response_body['archived_snapshots']['closest']['available'],
			'url'       => $response_body['archived_snapshots']['closest']['url'],
			'timestamp' => $response_body['archived_snapshots']['closest']['timestamp'],
		);
	}


	/**
	 * Get snapshot creation status.
	 *
	 * @since 1.2.1
	 *
	 * @param string $job_id The job id to check.
	 *
	 * @return array{job_id:string, status:'pending'|'success'|'failure', message:string}
	 *
	 * @throws Exception If the service is offline or the response is invalid.
	 */
	public function get_snapshot_status( string $job_id ): array {

		// Strip any trailing slash from url.
		$job_id = trim( $job_id );

		// If we dont have a ref code, throw an exception.
		if ( empty( $job_id ) ) {
			throw new Exception( 'Invalid snapshot job id' );
		}

		$query_url = 'https://web.archive.org/save/status/' . $job_id;

		// Get the status of the job.
		$response = wp_remote_get( $query_url );

		// If we have a wp error, throw invalid response exception.
		if ( is_wp_error( $response ) ) {
			throw Invalid_Response_Exception::create( esc_html( $response->get_error_message() ) );
		}

		// if we dont have a 200 response, throw an exception.
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			throw Service_Offline_Exception::create( esc_html( 'Response:' . wp_remote_retrieve_response_code( $response ) ) );
		}

		// Get the response body.
		$response_body = wp_remote_retrieve_body( $response );

		// Attempt to decode the response.
		$response_body = json_decode( $response_body, true );

		// if any errors, throw an exception.
		if ( ! is_array( $response_body ) ) {
			throw Invalid_Response_Exception::create( 'Response body is not valid JSON' );
		}

		$return = array(
			'job_id'  => $job_id,
			'status'  => esc_attr( $response_body['status'] ),
			'message' => 'error' === $response_body['status'] && array_key_exists( 'message', $response_body )
				? esc_attr( $response_body['message'] )
				: '',
		);

		// If we have a status_ext key, add it to the return.
		if ( array_key_exists( 'status_ext', $response_body ) ) {
			$return['status_ext'] = esc_attr( $response_body['status_ext'] );
		}

		return $return;
	}

	/**
	 * Checks if the service is online.
	 *
	 * @since 1.3.0
	 *
	 * @return boolean
	 */
	public function is_online(): bool {
		// Call the check status endpoint.
		try {
			$response = wp_remote_get(
				'https://web.archive.org/save/status/wlf-status-check',
				array(
					'timeout'   => apply_filters( 'wlf_is_online_timeout', 10 ),
					'sslverify' => false,
				)
			);
		} catch ( Throwable $th ) {
			return false;
		}

		// If we have a 503 or 404 response, the service is offline.
		if ( 503 === wp_remote_retrieve_response_code( $response ) || 404 === wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		// If the response is a WP Error, the service is offline.
		if ( is_wp_error( $response ) ) {
			return false;
		}

		return true;
	}
}
