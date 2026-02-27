# NOTE TO SELF: Remove this file when GC tests are complete

# Action_Scheduler_Garbage_Collection Test Patterns

## What we're testing

`src/Util/Action_Scheduler_Garbage_Collection.php` has cleanup methods that:
1. Query Action Scheduler for FAILED actions of a specific hook
2. Group them by a composite key (varies per event type)
3. Sort each group by `attempt` number (ascending)
4. Delete all except the highest attempt in each group
5. Support an optional `$before` DateTimeInterface param to only clean old actions

## How we create test actions

We **invoke the actual event** to generate real Action Scheduler actions - NOT raw DB inserts or `add_to_queue()` directly.

### Check_Snapshot_Status_Event (has `link_id`, `job_id`, `attempt`)

- Mock snapshot client to return `pending` status so the event reschedules itself
- The event does NOT throw on `pending`, so no try/catch needed
- Each invocation with attempt N creates a new pending action for attempt N+1
- Grouped by `job_id-link_id` composite key

```php
$this->set_snapshot_client_response( array( 'status' => 'pending' ) );
$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );
$event = new Check_Snapshot_Status_Event();
$event->setup();

$event( $link->get_id(), 'fake-job-aaa', 0 ); // creates action for attempt 1
$event( $link->get_id(), 'fake-job-aaa', 1 ); // creates action for attempt 2
```

**Max attempts is 3** - invoking with attempt >= 3 throws "Max attempts reached". Stay under 3.

### Create_New_Snapshot_Event (has `link_id`, `attempt` - NO `job_id`)

- Mock link checker client to throw `Service_Offline_Exception` so the event reschedules
- The event THROWS on error, so wrap each invocation in try/catch
- Each invocation with attempt N creates a new pending action for attempt N+1
- Grouped by `link_id` only

```php
$this->set_link_checker_to_throw();
$link = $this->link_repository->upsert( new Link( 'https://example.com' ) );
$event = new Create_New_Snapshot_Event();

try { $event( $link->get_id(), 0 ); } catch ( \Throwable $th ) {} // creates action for attempt 1
try { $event( $link->get_id(), 1 ); } catch ( \Throwable $th ) {} // creates action for attempt 2
```

**Max attempts is 3** - invoking with attempt > 3 throws "Max attempts reached". Stay at 3 or under.

## After creating actions

Mark them all as failed (since GC only queries failed actions):

```php
$this->wpdb->update(
    "{$this->wpdb->prefix}actionscheduler_actions",
    array( 'status' => 'failed' ),
    array(
        'hook'   => THE_EVENT::HANDLE,
        'status' => 'pending',
    )
);
```

## Testing the `$before` date filter

Action Scheduler requires `date` to be a `DateTime` object (not array, not string). The GC code handles conversion. To test:

1. Create actions as above, mark as failed
2. Update `scheduled_date_gmt` and `scheduled_date_local` directly via SQL for different groups
3. Pass a `DateTimeImmutable` to the cleanup method

```php
// Set old actions to year 2000
$this->wpdb->query(
    $this->wpdb->prepare(
        "UPDATE {$this->wpdb->prefix}actionscheduler_actions
         SET scheduled_date_gmt = '2000-01-01 00:00:00', scheduled_date_local = '2000-01-01 00:00:00'
         WHERE args LIKE %s",
        '%"link_id":' . $link_old->get_id() . '%'
    )
);

// Run with before date
$gc->clean_check_snapshot_status_events( new \DateTimeImmutable( '2020-01-01 00:00:00' ) );
```

## Test naming convention

Test methods are named after the GC method they test:

- `test_clean_check_snapshot_status_events_keeps_last_attempt()`
- `test_clean_check_snapshot_status_events_empty_table_does_nothing()`
- `test_clean_check_snapshot_status_events_single_attempt_not_deleted()`
- `test_clean_check_snapshot_status_events_before_date_only_cleans_old()`
- `test_clean_create_new_snapshot_events_keeps_last_attempt()`
- `test_clean_create_new_snapshot_events_empty_table_does_nothing()`
- `test_clean_create_new_snapshot_events_before_date_only_cleans_old()`

## Test file

`tests/Util/Test_Action_Scheduler_Garbage_Collection.php`

Extends `\WP_UnitTestCase`, namespace `Internet_Archive\Wayback_Machine_Link_Fixer\Tests\Util`.

## Remaining GC methods to implement and test

- `clean_update_archive_url_events()` - check `src/Event/Update_Archive_URL_Event.php` for args structure
- `clean_check_validator_status_events()` - check `src/Event/Link_Access_Validator_Event.php` for args structure
