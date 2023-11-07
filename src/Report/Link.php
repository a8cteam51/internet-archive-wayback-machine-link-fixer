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
	 * Redirection type.
	 *
	 * @since 1.0.0
	 *
	 * @var integer|null
	 */
	private ?int $redirect_type = null;

	/**
	 * Link replacement options.
	 *
	 * @since 1.0.0
	 *
	 * @var array<int, string>
	 */
	private array $replacement_options = array();

	/**
	 * Was the link fixed.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	private bool $fixed = false;

	/**
	 * Comments on the link
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	private array $comments = array();

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
	 * @param integer|null       $redirect_type       The link redirection type.
	 * @param array<int, string> $replacement_options The link replacement options.
	 * @param boolean            $fixed               Was the link fixed.
	 * @param string[]           $comments            Comments on the link.
	 */
	public function __construct(
		int $index,
		?string $href,
		?string $contents,
		bool $broken = false,
		?string $redirect_target = null,
		?int $redirect_type = null,
		array $replacement_options = array(),
		bool $fixed = false,
		array $comments = array()
	) {
		$this->index               = $index;
		$this->href                = $href;
		$this->contents            = $contents;
		$this->broken              = $broken;
		$this->redirect_target     = $redirect_target;
		$this->redirect_type       = $redirect_type;
		$this->replacement_options = $replacement_options;
		$this->fixed               = $fixed;
		$this->comments            = $comments;
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
	 * Get the link redirection type.
	 *
	 * @since 1.0.0
	 *
	 * @return integer|null
	 */
	public function get_redirect_type(): ?int {
		return $this->redirect_type;
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
	 * Was the link fixed.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_fixed(): bool {
		return $this->fixed;
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
}
