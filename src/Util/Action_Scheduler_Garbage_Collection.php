<?php

/**
 * Class used to handle action scheduler garbage collection tasks.
 *
 * @since 1.3.5
 *
 * @package Internet_Archive\Wayback_Machine_Link_Fixer\Util
 */

declare( strict_types=1 );

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Util;

use ActionScheduler;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Check_Snapshot_Status_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Check_Validator_Status;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Create_New_Snapshot_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Process_Local_Post_Event;
use Internet_Archive\Wayback_Machine_Link_Fixer\Event\Update_Archive_URL_Event;

defined( 'ABSPATH' ) || exit;

/**
 * Action_Scheduler_Garbage_Collection
 */
class Action_Scheduler_Garbage_Collection {

	/**
	 * Access to the Action Scheduler store.
	 *
	 * @var \ActionScheduler_Store|null
	 */
	private $store = null;

	/**
	 * Get the Action Scheduler store instance.
	 * Lazy loads the store when needed.
	 *
	 * @return \ActionScheduler_Store
	 *
	 * @throws \Exception If Action Scheduler is not available. This should never happen as the plugin requires Action Scheduler, but we throw an exception just in case.
	 */
	private function get_store(): \ActionScheduler_Store {
		if ( ! class_exists( 'ActionScheduler' ) ) {
			throw new \Exception( 'Action Scheduler is not available.' );
		}

		if ( ! $this->store ) {
			$this->store = \ActionScheduler::store();
		}
		return $this->store;
	}

	/**
	 * Cleans up failed/completed Check Snapshot Status retry events.
	 *
	 * @param \DateTimeInterface|null $before Only clean events before this date, or all if null.
	 *
	 * @return void
	 */
	public function clean_check_snapshot_status_events( ?\DateTimeInterface $before = null ): void {
		$store = $this->get_store();

		$args = array(
			'per_page' => -1,
			'status'   => array( \ActionScheduler_Store::STATUS_FAILED ),
			'hook'     => Check_Snapshot_Status_Event::HANDLE,
		);

		// If we have a datetime passed, set the date query arg to only get events before that date.
		// Action Scheduler requires a DateTime object (not DateTimeImmutable) for the date param.
		if ( $before ) {
			$args['date']         = new \DateTime( $before->format( 'Y-m-d H:i:s' ), new \DateTimeZone( 'UTC' ) );
			$args['date_compare'] = '<=';
		}

		// Get all failed or completed Check Snapshot Status retry events.
		$events = $store->query_actions( $args );

		// If we have no events, bail.
		if ( empty( $events ) ) {
			return;
		}

		// Get the args for each event
		$events = array_map(
			function ( $event_id ) use ( $store ) {
				$event = $store->fetch_action( $event_id );
				if ( ! $event ) {
					return null;
				}
				return array(
					'id'   => $event_id,
					'args' => $event->get_args(),
				);
			},
			$events
		);

		// Group events by job_id + link_id composite key, skip events without a job_id.
		$grouped = array();
		foreach ( $events as $event ) {
			if ( ! $event || empty( $event['args']['job_id'] ) ) {
				continue;
			}

			$key = $event['args']['job_id'] . '-' . ( $event['args']['link_id'] ?? 0 );
			if ( ! isset( $grouped[ $key ] ) ) {
				$grouped[ $key ] = array();
			}
			$grouped[ $key ][] = $event;
		}

		// Sort each group by attempt number (ascending).
		foreach ( $grouped as &$group ) {
			usort(
				$group,
				function ( $a, $b ) {
					return ( $a['args']['attempt'] ?? 0 ) <=> ( $b['args']['attempt'] ?? 0 );
				}
			);
		}
		unset( $group );

		// Remove all events except the last attempt in each group.
		foreach ( $grouped as $group ) {
			// Pop the last element (highest attempt) - we keep that one.
			array_pop( $group );

			// Delete all remaining (older attempt) events.
			foreach ( $group as $event ) {
				$store->delete_action( (int) $event['id'] );
			}
		}
	}

	/**
	 * Cleans up failed/completed Create New Snapshot retry events.
	 *
	 * @param \DateTimeInterface|null $before Only clean events before this date, or all if null.
	 *
	 * @return void
	 */
	public function clean_create_new_snapshot_events( ?\DateTimeInterface $before = null ): void {
		$store = $this->get_store();

		$args = array(
			'per_page' => -1,
			'status'   => array( \ActionScheduler_Store::STATUS_FAILED ),
			'hook'     => Create_New_Snapshot_Event::HANDLE,
		);

		// If we have a datetime passed, set the date query arg to only get events before that date.
		// Action Scheduler requires a DateTime object (not DateTimeImmutable) for the date param.
		if ( $before ) {
			$args['date']         = new \DateTime( $before->format( 'Y-m-d H:i:s' ), new \DateTimeZone( 'UTC' ) );
			$args['date_compare'] = '<=';
		}

		// Get all failed Create New Snapshot retry events.
		$events = $store->query_actions( $args );

		// If we have no events, bail.
		if ( empty( $events ) ) {
			return;
		}

		// Get the args for each event.
		$events = array_map(
			function ( $event_id ) use ( $store ) {
				$event = $store->fetch_action( $event_id );
				if ( ! $event ) {
					return null;
				}
				return array(
					'id'   => $event_id,
					'args' => $event->get_args(),
				);
			},
			$events
		);

		// Group events by link_id, skip events without a link_id.
		$grouped = array();
		foreach ( $events as $event ) {
			if ( ! $event || empty( $event['args']['link_id'] ) ) {
				continue;
			}

			$key = (string) $event['args']['link_id'];
			if ( ! isset( $grouped[ $key ] ) ) {
				$grouped[ $key ] = array();
			}
			$grouped[ $key ][] = $event;
		}

		// Sort each group by attempt number (ascending).
		foreach ( $grouped as &$group ) {
			usort(
				$group,
				function ( $a, $b ) {
					return ( $a['args']['attempt'] ?? 0 ) <=> ( $b['args']['attempt'] ?? 0 );
				}
			);
		}
		unset( $group );

		// Remove all events except the last attempt in each group.
		foreach ( $grouped as $group ) {
			// Pop the last element (highest attempt) - we keep that one.
			array_pop( $group );

			// Delete all remaining (older attempt) events.
			foreach ( $group as $event ) {
				$store->delete_action( (int) $event['id'] );
			}
		}
	}

	/**
	 * Cleans up failed/completed Update Archive URL retry events.
	 *
	 * @param \DateTimeInterface|null $before Only clean events before this date, or all if null.
	 *
	 * @return void
	 */
	public function clean_update_archive_url_events( ?\DateTimeInterface $before = null ): void {
		$store = $this->get_store();

		$args = array(
			'per_page' => -1,
			'status'   => array( \ActionScheduler_Store::STATUS_FAILED ),
			'hook'     => Update_Archive_URL_Event::HANDLE,
		);

		// If we have a datetime passed, set the date query arg to only get events before that date.
		// Action Scheduler requires a DateTime object (not DateTimeImmutable) for the date param.
		if ( $before ) {
			$args['date']         = new \DateTime( $before->format( 'Y-m-d H:i:s' ), new \DateTimeZone( 'UTC' ) );
			$args['date_compare'] = '<=';
		}

		// Get all failed Update Archive URL retry events.
		$events = $store->query_actions( $args );

		// If we have no events, bail.
		if ( empty( $events ) ) {
			return;
		}

		// Get the args for each event.
		$events = array_map(
			function ( $event_id ) use ( $store ) {
				$event = $store->fetch_action( $event_id );
				if ( ! $event ) {
					return null;
				}
				return array(
					'id'   => $event_id,
					'args' => $event->get_args(),
				);
			},
			$events
		);

		// Group events by link_id, skip events without a link_id.
		$grouped = array();
		foreach ( $events as $event ) {
			if ( ! $event || empty( $event['args']['link_id'] ) ) {
				continue;
			}

			$key = (string) $event['args']['link_id'];
			if ( ! isset( $grouped[ $key ] ) ) {
				$grouped[ $key ] = array();
			}
			$grouped[ $key ][] = $event;
		}

		// Sort each group by attempt number (ascending).
		foreach ( $grouped as &$group ) {
			usort(
				$group,
				function ( $a, $b ) {
					return ( $a['args']['attempt'] ?? 0 ) <=> ( $b['args']['attempt'] ?? 0 );
				}
			);
		}
		unset( $group );

		// Remove all events except the last attempt in each group.
		foreach ( $grouped as $group ) {
			// Pop the last element (highest attempt) - we keep that one.
			array_pop( $group );

			// Delete all remaining (older attempt) events.
			foreach ( $group as $event ) {
				$store->delete_action( (int) $event['id'] );
			}
		}
	}

	/**
	 * Cleans up failed/completed Check Validator Status retry events.
	 *
	 * @param \DateTimeInterface|null $before Only clean events before this date, or all if null.
	 *
	 * @return void
	 */
	public function clean_check_validator_status_events( ?\DateTimeInterface $before = null ): void {
		$store = $this->get_store();

		$args = array(
			'per_page' => -1,
			'status'   => array( \ActionScheduler_Store::STATUS_FAILED ),
			'hook'     => Check_Validator_Status::HANDLE,
		);

		// If we have a datetime passed, set the date query arg to only get events before that date.
		// Action Scheduler requires a DateTime object (not DateTimeImmutable) for the date param.
		if ( $before ) {
			$args['date']         = new \DateTime( $before->format( 'Y-m-d H:i:s' ), new \DateTimeZone( 'UTC' ) );
			$args['date_compare'] = '<=';
		}

		// Get all failed Check Validator Status retry events.
		$events = $store->query_actions( $args );

		// If we have no events, bail.
		if ( empty( $events ) ) {
			return;
		}

		// Get the args for each event.
		$events = array_map(
			function ( $event_id ) use ( $store ) {
				$event = $store->fetch_action( $event_id );
				if ( ! $event ) {
					return null;
				}
				return array(
					'id'   => $event_id,
					'args' => $event->get_args(),
				);
			},
			$events
		);

		// Group events by job_id + link_id composite key, skip events without a job_id.
		$grouped = array();
		foreach ( $events as $event ) {
			if ( ! $event || empty( $event['args']['job_id'] ) ) {
				continue;
			}

			$key = $event['args']['job_id'] . '-' . ( $event['args']['link_id'] ?? 0 );
			if ( ! isset( $grouped[ $key ] ) ) {
				$grouped[ $key ] = array();
			}
			$grouped[ $key ][] = $event;
		}

		// Sort each group by attempt number (ascending).
		foreach ( $grouped as &$group ) {
			usort(
				$group,
				function ( $a, $b ) {
					return ( $a['args']['attempt'] ?? 0 ) <=> ( $b['args']['attempt'] ?? 0 );
				}
			);
		}
		unset( $group );

		// Remove all events except the last attempt in each group.
		foreach ( $grouped as $group ) {
			// Pop the last element (highest attempt) - we keep that one.
			array_pop( $group );

			// Delete all remaining (older attempt) events.
			foreach ( $group as $event ) {
				$store->delete_action( (int) $event['id'] );
			}
		}
	}
}
