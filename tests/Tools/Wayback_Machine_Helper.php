<?php

declare(strict_types=1);

/**
 * Helper class for doing test with the Wayback Machine.
 *
 * @since 1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer_Tests\Tools;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Snapshot_Client;
use WPCOMSpecialProjects\Wayback_Link_Fixer\Wayback_Machine\Link_Checker_Client;


/**
 * Helper class.
 */
trait Wayback_Machine_Helper {


	/**
	 * Clears any filters the link checker and snapshot client.
	 *
	 * @return void
	 */
	public function clear_clients(): void {
		remove_all_filters( 'iawmlf_snapshot_client' );
		remove_all_filters( 'iawmlf_link_checker_client' );
	}

	/**
	 * Create service which return a defined state online or not.
	 *
	 * @template ConfigArray = array{snapshot:Snapshot_Client, link_checker:Link_Checker_Client}
	 *
	 * @param boolean                                  $online The state of the service.
	 * @param (callable(ConfigArray):ConfigArray)|null $config The configuration for the service, instances are PHPUnit mocks,
	 *
	 * @return void
	 */
	public function create_service( bool $online = true, ?callable $config = null ): void {

		// If createMock is not available, throw an exception.
		if ( ! method_exists( $this, 'createMock' ) ) {
			throw new \Exception( 'createMock is not available, was this called within' );
		}

		// Create mock snapshot client.
		$snapshot_client = $this->createMock( Snapshot_Client::class );
		$snapshot_client->method( 'is_online' )
			->willReturn( $online );

		// Create mock link checker client.
		$link_client = $this->createMock( Link_Checker_Client::class );
		$link_client->method( 'is_online' )
			->willReturn( $online );

		// If we have a configuration, apply it.
		if ( $config ) {
			$results = (array) $config(
				array(
					'snapshot'     => $snapshot_client,
					'link_checker' => $link_client,
				)
			);

			$snapshot_client = \array_key_exists( 'snapshot', $results ) ? $results['snapshot'] : $snapshot_client;
			$link_client     = \array_key_exists( 'link_checker', $results ) ? $results['link_checker'] : $link_client;
		}

		// Set the mock client
		add_filter(
			'iawmlf_snapshot_client',
			function () use ( $snapshot_client ) {
				return $snapshot_client;
			}
		);

		// Set the mock client
		add_filter(
			'iawmlf_link_checker_client',
			function () use ( $link_client ) {
				return $link_client;
			}
		);
	}
}
