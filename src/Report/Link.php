<?php

/**
 * Report Logs Link Model
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Report;

/**
 * Report Logs Link
 */
class Link {

	/**
	 * The index/position of the link in the content.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	private int $index;

	/**
	 * The existing link href
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private ?string $href;

	/**
	 * The links contents.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private ?string $contents;

	/**
	 * Is the link broken.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	private bool $broken = false;

	/**
	 * Link redirection target
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	private ?string $redirect_target = null;

	/**
	 * HTTP Code.
	 *
	 * @since 1.0.0
	 *
	 * @var integer|null
	 */
	private ?int $http_code = null;

	/**
	 * Link replacement options.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	private array $replacement_options = array();

	/**
	 * Comments on the link
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	private array $comments = array();

	/**
	 * Denotes if the link has been updated.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	private bool $updated = false;

	/**
	 * Create instance of Report_Logs_Link.
	 *
	 * @since 1.0.0
	 *
	 * @param integer            $index               The index/position of the link in the content.
	 * @param string|null        $href                The link href.
	 * @param string|null        $contents            The link contents.
	 * @param boolean            $broken              Is the link broken.
	 * @param string|null        $redirect_target     The link redirection target.
	 * @param integer|null       $http_code           The HTTP Code of the link.
	 * @param array<int, string> $replacement_options The link replacement options.
	 * @param string[]           $comments            Comments on the link.
	 * @param boolean            $updated             Denotes if the link has been updated.
	 */
	public function __construct(
		int $index,
		?string $href,
		?string $contents,
		bool $broken = false,
		?string $redirect_target = null,
		?int $http_code = null,
		array $replacement_options = array(),
		array $comments = array(),
		bool $updated = false
	) {
		$this->index               = $index;
		$this->href                = $href;
		$this->contents            = $contents;
		$this->broken              = $broken;
		$this->redirect_target     = $redirect_target;
		$this->http_code           = $http_code;
		$this->replacement_options = $replacement_options;
		$this->comments            = $comments;
		$this->updated             = $updated;
	}

	/**
	 * Get the index/position of the link in the content.
	 *
	 * @since 1.0.0
	 *
	 * @return integer
	 */
	public function get_index(): int {
		return $this->index;
	}

	/**
	 * Get the link href.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_href(): ?string {
		return $this->href;
	}

	/**
	 * Get the link contents.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_contents(): ?string {
		return $this->contents;
	}

	/**
	 * Is the link broken.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_broken(): bool {
		return $this->broken;
	}

	/**
	 * Get the link redirection target.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_redirect_target(): ?string {
		return $this->redirect_target;
	}

	/**
	 * Get The HTTP Code of the link.
	 *
	 * @since 1.0.0
	 *
	 * @return integer|null
	 */
	public function get_http_code(): ?int {
		return $this->http_code;
	}

	/**
	 * Get the link replacement options.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int, string>
	 */
	public function get_replacement_options(): array {
		return $this->replacement_options;
	}

	/**
	 * Get the comments on the link.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	public function get_comments(): array {
		return $this->comments;
	}

	/**
	 * Has been updated.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function has_been_updated(): bool {
		return $this->updated;
	}

	/**
	 * Add a comment.
	 *
	 * @since 1.0.0
	 *
	 * @param string $comment The comment.
	 *
	 * @return Link
	 */
	public function add_comment( string $comment ): Link {
		$comments   = $this->comments;
		$comments[] = $comment;
		return new Link(
			$this->index,
			$this->href,
			$this->contents,
			$this->broken,
			$this->redirect_target,
			$this->http_code,
			$this->replacement_options,
			$comments
		);
	}

	/**
	 * Add replacement options.
	 *
	 * @since 1.0.0
	 *
	 * @param array<int, string> $replacement_options The replacement options.
	 *
	 * @return Link
	 */
	public function add_replacement_options( array $replacement_options ): Link {
		return new Link(
			$this->index,
			$this->href,
			$this->contents,
			$this->broken,
			$this->redirect_target,
			$this->http_code,
			$replacement_options,
			$this->comments
		);
	}

	/**
	 * Mark as updated.
	 *
	 * @since 1.0.0
	 *
	 * @return Link
	 */
	public function as_updated(): Link {
		return new Link(
			$this->index,
			$this->href,
			$this->contents,
			$this->broken,
			$this->redirect_target,
			$this->http_code,
			$this->replacement_options,
			$this->comments,
			true
		);
	}
}
