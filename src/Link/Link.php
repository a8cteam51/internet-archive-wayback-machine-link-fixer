<?php

/**
 * Model of a link
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Link;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Link Model
 */
class Link implements \JsonSerializable {

	public const LINK_STATUS_VALID   = 'valid_link';
	public const LINK_STATUS_INVALID = 'invalid_link';

	/**
	 * The database row id.
	 *
	 * @var integer|null
	 */
	private $id = null;

	/**
	 * The links href.
	 *
	 * @var string
	 */
	private $href;

	/**
	 * The archived href.
	 *
	 * @var string|null
	 */
	private $archived_href = null;

	/**
	 * The redirect href.
	 *
	 * @var string|null
	 */
	private $redirect_href = null;

	/**
	 * Denotes if a link is broken and should not checked.
	 *
	 * @var boolean
	 */
	private $is_broken = false;

	/**
	 * The checks that have been made to the link.
	 *
	 * @var array<array{date: string, http_code: int}>
	 */
	private $checks = array();

	/**
	 * Any messages that have been generated for the link.
	 *
	 * @var string
	 */
	private $message = '';

	/**
	 * Is the link allowed to be checked.
	 *
	 * @var boolean
	 */
	private $is_excluded = false;

	/**
	 * Creates a new instance of the link model.
	 *
	 * @param string $href The original href.
	 */
	public function __construct( string $href ) {
		$this->href = $href;
	}

	/**
	 * Set the the database row id.
	 *
	 * @param integer $id The database row id.
	 *
	 * @return self
	 */
	public function set_id( int $id ): self {
		$this->id = $id;
		return $this;
	}

	/**
	 * Get the database row id.
	 *
	 * @return integer|null
	 */
	public function get_id(): ?int {
		return $this->id;
	}

	/**
	 * Set the archived href.
	 *
	 * @param string $archived_href The archived href.
	 *
	 * @return self
	 */
	public function set_archived_href( string $archived_href ): self {
		$this->archived_href = $archived_href;
		return $this;
	}

	/**
	 * Gets the redirect href.
	 *
	 * @return string|null
	 */
	public function get_redirect_href(): ?string {
		return $this->redirect_href;
	}

	/**
	 * Sets the redirect href.
	 *
	 * @param string $redirect_href The redirect href.
	 *
	 * @return self
	 */
	public function set_redirect_href( string $redirect_href ): self {
		$this->redirect_href = $redirect_href;
		return $this;
	}

	/**
	 * Sets the link as broken.
	 *
	 * @return self
	 */
	public function set_broken(): self {
		$this->is_broken = true;
		return $this;
	}

	/**
	 * Sets the link as valid.
	 *
	 * @return self
	 */
	public function set_valid(): self {
		$this->is_broken = false;
		return $this;
	}

	/**
	 * Checks if the link is broken.
	 *
	 * @return boolean
	 */
	public function is_broken(): bool {
		return $this->is_broken;
	}

	/**
	 * Sets a message for the link.
	 *
	 * @param string $message The message.
	 *
	 * @return self
	 */
	public function set_message( string $message ): self {
		$this->message = esc_html( $message );
		return $this;
	}

	/**
	 * Sets if the link is excluded.
	 *
	 * @param boolean $is_excluded If the link is excluded.
	 *
	 * @return self
	 */
	public function set_excluded( bool $is_excluded = true ): self {
		$this->is_excluded = $is_excluded;
		return $this;
	}

	/**
	 * Checks if the link is excluded.
	 *
	 * @return boolean
	 */
	public function is_excluded(): bool {
		return $this->is_excluded;
	}

	/**
	 * Gets the message for the link.
	 *
	 * @return string
	 */
	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Add a check to the link.
	 *
	 * @param integer     $http_code The HTTP code.
	 * @param string|null $date      The date of the check in Y-m-d H:i:s format.
	 *
	 * @return self
	 */
	public function add_check( int $http_code, ?string $date = null ): self {
		$this->checks[] = array(
			'date'      => $date ?? gmdate( 'Y-m-d H:i:s' ),
			'http_code' => $http_code,
		);

		return $this;
	}

	/**
	 * Get the href.
	 *
	 * @return string
	 */
	public function get_href(): string {
		return $this->href;
	}

	/**
	 * Checks if a link has an archived href.o
	 *
	 * @return boolean
	 */
	public function has_archived_href(): bool {
		return null !== $this->archived_href && '' !== $this->archived_href;
	}

	/**
	 * Get the archived href.
	 *
	 * @return string
	 */
	public function get_archived_href(): ?string {
		return $this->archived_href;
	}

	/**
	 * Get the checks.
	 *
	 * @return array<array{date: string, http_code: int}>
	 */
	public function get_checks(): array {
		return $this->checks;
	}

	/**
	 * Get the last check.
	 *
	 * @return array{date: string, http_code: int}
	 */
	public function get_last_check(): ?array {
		// If we have no checks, return null.
		if ( empty( $this->checks ) ) {
			return null;
		}

		return end( $this->checks );
	}

	/**
	 * Checks if the link has the following HTTP code.
	 *
	 * @param integer $http_code The HTTP code.
	 *
	 * @return boolean
	 */
	public function has_http_code( int $http_code ): bool {
		foreach ( $this->checks as $check ) {
			if ( $check['http_code'] === $http_code ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks if a link is valid.
	 *
	 * @return boolean
	 */
	public function is_valid(): bool {
		$failed_count = Settings::get_failed_count();

		// Get the last checks based on the failed count.
		$last_checks = array_slice( $this->checks, - $failed_count );
		// If we do not have any checks, then it is valid.
		if ( empty( $last_checks ) ) {
			$this->is_broken = false;
			return true;
		}

		// If we have less checks than the failed count, then it is valid.
		if ( count( $last_checks ) < $failed_count ) {
			$this->is_broken = false;
			return true;
		}

		// Verify the last checks.
		$valid = array_filter(
			$last_checks,
			function ( $check ) {

				// Check if the link has a valid status code.
				$is_valid = in_array( absint( $check['http_code'] ), Settings::get_valid_http_status_codes(), true );

				// Allow additional checks
				return \apply_filters( 'wlf_is_valid_check', $is_valid, $check, $this );
			}
		);

		// If the link is, set its flag.
		$this->is_broken = empty( $valid );

		// If we have any valid checks, then it is valid
		return ! empty( $valid );
	}

	/**
	 * Unpack from JSON
	 *
	 * @param string $json The JSON string.
	 *
	 * @return self
	 */
	public static function from_json( string $json ): self {
		$data = json_decode( $json, true );

		$link = new self( $data['href'] ?? '' );

		// If contains archived href, set it.
		if ( isset( $data['archived_href'] ) ) {
			$link->set_archived_href( $data['archived_href'] ?? '' );
		}

		// If contains redirect href, set it.
		if ( isset( $data['redirect_href'] ) ) {
			$link->set_redirect_href( $data['redirect_href'] ?? '' );
		}

		// Set the id.
		if ( isset( $data['id'] ) ) {
			$link->set_id( absint( $data['id'] ) );
		}
				$link->set_id( $data['id'] );

		foreach ( $data['checks'] as $check ) {
			$link->add_check(
				wpcomsp_wayback_link_fixer_escape_http_status_code( $check['http_code'] ),
				esc_attr( $check['date'] )
			);
		}

		return $link;
	}

	/**
	 * Format for JSON
	 *
	 * @implements JsonSerializable
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		return array(
			'id'            => $this->id,
			'href'          => $this->href,
			'archived_href' => $this->archived_href,
			'redirect_href' => $this->redirect_href,
			'checks'        => $this->checks,
			'broken'        => $this->is_broken,
			'last_checked'  => $this->get_last_check(),
		);
	}
}
