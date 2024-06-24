<?php

/**
 * Class used to cache the results of a list table action.
 *
 * @since 1.2.0
 *
 * @package WPCOMSpecialProjects\Wayback_Link_Fixer\Util
 */
declare(strict_types=1);

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Util;

/**
 * List_Table_Action_Notification_Cache
 */
class List_Table_Action_Notification_Cache {

	private const CACHE_KEY_PREFIX = 'wlf_list_table_action_cache_';
	private const CACHE_EXPIRATION = 5 * MINUTE_IN_SECONDS;

	/**
	 * The action being done.
	 *
	 * @var string
	 */
	private $action;

	/**
	 * The user id.
	 *
	 * @var integer
	 */
	private $user_id;

	/**
	 * Notification cache.
	 *
	 * @var array
	 */
	private $cache = array();

	/**
	 * Creates an instance of the class.
	 *
	 * @param string $action The action being done.
	 */
	public function __construct( string $action ) {
		$this->action  = $action;
		$this->user_id = get_current_user_id();
	}

	/**
	 * Push to the cache.
	 *
	 * @param mixed $data The data to cache.
	 *
	 * @return void
	 */
	public function push( $data ): void {
		$this->cache[] = $data;
	}

	/**
	 * Compiles the cache key.
	 *
	 * @param string $partial_key The custom key.
	 *
	 * @return string
	 */
	private function compile_cache_key( string $partial_key ): string {
		return self::CACHE_KEY_PREFIX . $this->user_id . '_' . $this->action . '_' . $partial_key;
	}

	/**
	 * Create the cache.
	 *
	 * @return string
	 */
	public function save(): string {
		$partial_key = wp_generate_password( 12, false );
		$key         = $this->compile_cache_key( $partial_key );

		// Create the transient.
		set_transient( $key, $this->cache, self::CACHE_EXPIRATION );

		return $partial_key;
	}

	/**
	 * Retrieve the cache.
	 *
	 * @param string $key The key to retrieve.
	 *
	 * @return array
	 */
	public function get( string $key ): array {
		$key = $this->compile_cache_key( $key );

		$data = get_transient( $key );

		// Delete the transient.
		delete_transient( $key );

		if ( false === $data ) {
			return array();
		}

		return (array) $data;
	}
}
