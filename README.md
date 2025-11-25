# Internet Archive Wayback Machine Link Fixer

**Contributors:** wpcomspecialprojects  
**Tags:** wayback machine, internet archive, broken links, archive links  
**Requires at least:** 6.4  
**Tested up to:** 6.8  
**Requires PHP:** 7.4  
**Stable tag:** 1.3.4 
**License:** GPL-3.0-or-later  
**License URI:** https://www.gnu.org/licenses/gpl-3.0.html

[![Unit Tests](https://github.com/a8cteam51/wayback-link-fixer/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/a8cteam51/wayback-link-fixer/actions/workflows/unit-tests.yml)


## Description

**Internet Archive Wayback Machine Link Fixer** is a WordPress plugin designed to combat **link rot**—the gradual decay of web links as pages are moved, changed, or taken down. It automatically scans your post content—on save and across existing posts—to detect outbound links. For each one, it checks the Internet Archive’s Wayback Machine for an archived version and creates a snapshot if one isn’t available.

When a linked page disappears, the plugin helps preserve your user experience by redirecting visitors to a reliable archived version. It also works proactively by archiving your own posts every time they’re updated, creating a consistent backup of your content’s history.

Protect your links, preserve your content, and automate the archiving process—all with minimal effort.

## Key Features

- Automatically scans for outbound links in post content
- Checks the Wayback Machine for existing archives
- Creates new snapshots if no archive exists
- Redirects broken or missing links to archived versions
- Archives your own posts on updates
- Works on both new and existing content
- Helps maintain long-term content reliability and SEO

## Installation

**[Download the latest release](https://github.com/a8cteam51/wayback-link-fixer/releases/latest/download/wayback-link-fixer.zip)**


### Via WP Admin Dashboard

1. Upload the archive using the WordPress plugin uploader.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure the plugin settings by navigating to the 'Wayback Link Fixer' menu in the WordPress admin dashboard.

### Via FTP

1. Extract the archive and upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure the plugin settings by navigating to the 'Wayback Link Fixer' menu in the WordPress admin dashboard.

## Configuration

When the plugin is first installed it will give the option to launch the setup wizard, this will guide you through the initial setup of the plugin. Once this has been done, you will be access the settings again to make any changes.

## Settings

### General Plugin Settings

#### Wipe Data on Uninstall

![Wipe Data on Uninstall](./_docs/settings--drop-tables.png)

Enable this option to remove all plugin data from the database when the plugin is uninstalled.

> Enabled by default.

### Archive.org API
![Archive.org API](./_docs/settings--archive-api.png)

To increase your daily link processing limit, you can enter your free **Archive.org API credentials** in the plugin settings. Just visit [archive.org/account/s3.php](https://archive.org/account/s3.php) to generate your `Access Key` and `Secret Key`.

### Link Fixer Settings

#### Enable Link Fixer
![Link Fixer](./_docs/settings--enable-link-fixer.png)

Enable this option to activate the Link Fixer. Once enabled, additional settings will appear to let you customize how links are scanned, archived, and redirected.

#### Post Types

![Post Types](./_docs/settings--post-types.png)

Choose which post types should be checked whenever a post is saved, updated, or when existing posts are scanned.

> By default, `post` and `page` are selected.

#### Scan Existing Posts

![Scan Existing Posts](./_docs/settings--scan-existing.png)

Enable this option to scan all existing posts for broken links. Only posts that haven't been previously scanned will be checked.

> Disabled by default.

#### Link Exclusions

![Link Exclusions](./_docs/settings--link-exclusions.png)

Specify links to exclude from being checked. This is useful for links known to be broken or irrelevant. The `*` wildcard can be used to match any character.

* `https://example.com/*` - Excludes all links starting with `https://example.com/`
* `https://x.com*` - Excludes all links containing `x/twitter` in the domain name

#### Check Frequency

![Check Frequency](./_docs/settings--check-frequency.png)

Specify how often to recheck each link for validity. Avoid checking too often, as temporary outages or maintenance can cause false “broken” results. The default is 3 days.

#### Failure Threshold

![Failure Threshold](./_docs/settings--failure-threshold.png)

Specify the number of consecutive failed checks before a link is marked as broken. Occasional single failures are normal, so use a value high enough to confirm genuine link loss. The default is 5.


#### Fixer Option

![Fixer Option](./_docs/settings--fixer-option.png)

You can choose what outcome you want to happen when a link is found to be broken. The options are:
 * **Do Nothing** - This will not change the link at all, but useful for monitoring content.
 * **Replace Link (No Notification)** - This will replace the broken link with the archived version, if one exists. If no archived version exists, the link will not be changed. No notice will be given to the user that the link has been replaced.

### Auto Archiver

![Auto Archiver](./_docs/settings--auto-archiver.png)

The Auto Archiver automatically creates Wayback Machine snapshots of your own content whenever it’s created or updated (based on allowed post types). You can also enable scheduled archiving to routinely update snapshots over time.

#### Routinely Auto Archive

![Routinely Auto Archive](./_docs/settings--routinely-auto-archive.png)

Enable this option to routinely update your posts in the Wayback Machine. This ensures all posts remain archived and up to date. You can also set how many days to wait between each snapshot.

#### Allowed Post Types

![Allowed Post Types](./_docs/settings--allowed-auto-archive-post-types.png)

Select which post types should be automatically archived when they are created or updated. Only the selected types will trigger the Auto Archiver on save.


## Dashboard Widget

![Dashboard Widget](./_docs/dashboard--widget.png)

The Dashboard Widget gives you a quick overview of the plugin’s current activity and settings. If you've connected your Archive.org account, it will display how many snapshots have been created today, how many are still pending (waiting to be processed by the Internet Archive), and the current status of various features.

You’ll see whether Link Processing and Auto Archiving are active, whether the plugin is scanning existing posts for broken links, the interval (in days) between routine link checks, and how many consecutive failures are required before a link is considered broken. This widget provides a convenient way to monitor the health and performance of your link preservation setup at a glance.


## Link Fixer

Every link which is scanned, is added to the Link Table, this can be accessed under `Link Fixer` in the `Tools` menu.

![Link Table Overview](./_docs/link-fixer-table--overview.png)

Here you can see the status of each link, the number of snapshots available, and the date of the last snapshot.

You can open the help context at any time for additional information.

![Link Table Help](./_docs/link-fixer-table--help.png)


### URL

You can access the report for the link by clicking on the URL. This will take you to the link report page, which gives more information about the link and its status. You can also access the links target in a new tab by clicking the link icon next to the URL.

### Archive Status

| | |
|---|---|
| ![checkmark icon](./_docs/check-icon.png) | A checkmark indicates that we have a defined archived link for this URL. Clicking this will access the archived snapshot. |
| ![cross icon](./_docs/cross-icon.png) | A cross indicates that we do not have an archived link for this URL. |
| ![clock icon](./_docs/clock-icon.png) | A clock indicates that we are currently trying to create a new snapshot for this URL. |
| ![plus icon](./_docs/plus-icon.png) | A plus indicates that this is a new link that has not started the process yet. This will happen ASAP |
| ![warning icon](./_docs/warning-icon.png) | A warning indicates that the link is excluded from being archived. |


### Link Health

| Icon | Meaning |
|---|---|
| ![checkmark icon](./_docs/check-icon.png) | A checkmark implies that the link was valid on the last check. |
| ![unlinked icon](./_docs/unlink-icon.png) | This indicates that the link is broken |
| ![clock icon](./_docs/clock-icon.png) | A clock indicates that the link has not yet been checked. It might still be being processed. |



### Check Count

Denotes the number of the times we have checked if the link is still active.

### Last Check

Displays the date and time of the last check.

## Actions

![All actions](./_docs/links--actions.png)

You can select which links you wish to apply the bulk actions to by checking the box next to the URL.

### Update To Latest Snapshot

This will update the link to the latest snapshot that exists on the Wayback Machine. *This will not create a new snapshot!*

### Create New Snapshot

This will setup an event using the action scheduler to create a new snapshot of the link. If a new snapshot can be created, the links archived link will be updated to the new snapshot.

### Check Link Status

This will trigger a check of the link to see if it is still active.

### Verify Link Allows Checking

This will verify if a link allows checking. If it does not, the link will be excluded from being checked.

> Please note some urls do not allow bots to check the status of the link, this will often result in links being reported as a 403 even if still active and result in false positives.

## Link Report

![Link Details](./_docs/link--details.png)

Each link has a details page which gives more information about the link.

### Link Details

#### URL 

The URL of the link.

#### Archived URL

The archived URL if one exists.

#### Message 

If there are any issues in creating or finding a snapshot, this will be displayed here.

### Link Checks

This lists all checks, with the date/time plus the resulting http status code. It will also show if the link is broken or not.

### Posts Link Used In

This list all posts which the link appears.

## Post/Page List Table

The number of links and how many are broken is shown on the post list table. 

![image](./_docs/post-list-table.png)

The link count is clickable, this will access a filtered link list for that post.

![Show all links for a given post](./_docs/links--for-post.png)

### Demo

[Watch demo video](https://www.loom.com/share/7551a8aa791849fc81c9aa21ada268f1?sid=510daff9-40db-4986-8137-56471a3c8149)

![Demo](_docs/demo.gif)

![Dashboard](./_docs/dashboard.png)
> The main dashboard page.

![Link List](./_docs/link-list.png)
> The link list page.

![Link List Help Tab](./_docs/help-tab.png)
> The help tab on the link list page.

![Link Details](./_docs/link-details.png)
> The link details page.

## Developer Documentation

### Dependencies

The plugin uses the following dependencies:
* Action Scheduler - This is added using the defined loader, so any later version will be used in its place gracefully.

### Events (Action Scheduler Events)

Almost all operations are carried out using the Action Scheduler, this allows for the plugin to be more performant and not cause issues with timeouts.

#### Find or Create Snapshot

This event attempts to locate the most recent snapshot of a link using the Wayback Machine. If no snapshot is found, it queues a new snapshot request.

**Prerequisites checked:**
- The link exists and is valid
- The Wayback Machine service is online  
- The link is not already an archive.org URL (those are skipped)

**Process:**
If a snapshot is found, it's saved to the link record. If not, the link is marked as pending, and the `Create_New_Snapshot_Event` is queued to create one. The plugin also checks the current status of the original URL and stores that status. If the URL returns a `403 Forbidden`, it is added to the `Link_Access_Validator_Event` queue for further checking.

**Action:** `iawmlf_find_or_create_snapshot`

| **Argument** | **Type** | **Description** |
|--------------|----------|-----------------|
| `link_id` | int | The ID of the link to process |

---

#### Create New Snapshot

When a snapshot for a link doesn't already exist, this event is used to request a new one from the Wayback Machine. If successful, it retrieves a snapshot job ID and queues a separate event to check the status of that snapshot.

**Process:**
- Attempts to resolve the final URL (following redirects)
- Updates the stored link if it has been redirected
- Respects Wayback Machine service availability
- Marks links as `pending` or `done` depending on the outcome
- Avoids duplicate retries once the maximum attempts are reached

This process is retried automatically if it fails, with up to 3 attempts by default. Each retry is delayed by 15 minutes, unless the failure is due to hitting the snapshot rate limit, in which case the delay is 24 hours.

**Action:** `iawmlf_create_new_snapshot`

| **Argument** | **Type** | **Description** |
|--------------|----------|-----------------|
| `link_id` | int | The ID of the link to process |
| `attempt` | int | Current attempt number |

| **Configuration** | **Filter/Setting** | **Default** | **Description** |
|-------------------|-------------------|-------------|-----------------|
| Max attempts | [`iawmlf_create_new_snapshot_attempts`](#iawmlf_create_new_snapshot_attempts) | 3 | Number of retry attempts |

---

#### Check Snapshot Status

Once a snapshot job has been created, this event checks the status of that snapshot in the Wayback Machine. If the snapshot is ready, it queues a follow-up event to update the link with the archived URL.

**Process:**
- Verifies the snapshot status using the Wayback Machine job ID
- Requeues itself if the snapshot is still pending
- Captures and stores error messages when the snapshot fails
- Marks links as `excluded` when access is denied (`error:no-access`)
- Marks links as `done` if the snapshot ultimately fails or reaches maximum retries
- Triggers `iawmlf_update_archive_url` if the snapshot is successfully created

This process is retried automatically until the snapshot is complete or the maximum number of attempts is reached. Each retry is delayed by 5 minutes by default. If the Wayback Machine service is offline, the event will retry after 1 hour.

**Action:** `iawmlf_check_snapshot_status`

| **Argument** | **Type** | **Description** |
|--------------|----------|-----------------|
| `link_id` | int | The ID of the link being checked |
| `job_id` | string | Job ID from the Wayback Machine |
| `attempt` | int | Current attempt number |

| **Configuration** | **Filter/Setting** | **Default** | **Description** |
|-------------------|-------------------|-------------|-----------------|
| Max attempts | [`iawmlf_check_snapshot_status_attempts`](#iawmlf_check_snapshot_status_attempts) | 3 | Number of retry attempts |
| Retry interval | [`iawmlf_check_snapshot_status_interval`](#iawmlf_check_snapshot_status_interval) | 5 minutes | Delay between status checks |

---

#### Update Archive URL

After a snapshot has been created, this event attempts to fetch the final archive URL from the Wayback Machine and update the link with it. Since the archive might not be immediately available, the event retries with a delay until successful or until the maximum number of attempts is reached.

**Process:**
- Attempts to retrieve the archived URL based on the final resolved link
- Updates the link with the archived URL if available
- Retries if the URL is not yet available or if an error occurs
- Marks the link as `done` if the maximum number of attempts is exceeded

This process is retried automatically with up to 3 attempts by default. Each retry is delayed by 15 minutes.

**Action:** `iawmlf_update_archive_url`

| **Argument** | **Type** | **Description** |
|--------------|----------|-----------------|
| `link_id` | int | The ID of the link to update |
| `attempt` | int | Current attempt number |

| **Configuration** | **Filter/Setting** | **Default** | **Description** |
|-------------------|-------------------|-------------|-----------------|
| Max attempts | [`iawmlf_update_archive_url_attempts`](#iawmlf_update_archive_url_attempts) | 3 | Number of retry attempts |

---

#### Scan Existing Posts

This event scans existing posts for links that haven't been processed yet. It runs on a repeating schedule, as long as the feature is enabled in the plugin settings.

**Process:**
- Queries for posts that lack the link metadata
- Processes a fixed number of posts per batch
- Reschedules itself after each run
- Skips processing if the archive API is offline

> **Note:** This ensures imported or legacy posts are eventually scanned and included in snapshot coverage.

**Action:** `iawmlf_scan_existing_posts`

*This event takes no arguments.*

**Filters:**

| **Configuration** | **Filter** | **Default** | **Description** |
|-------------------|------------|-------------|-----------------|
| Batch size | [`iawmlf_posts_per_batch`](#iawmlf_posts_per_batch) | 10 | Number of posts processed per batch |
| Frequency | [`iawmlf_scan_posts_interval`](#iawmlf_scan_posts_interval) | 10 minutes | How often to run the scan |

**Settings:**

| **Configuration** | **Setting** | **Description** |
|-------------------|-------------|-----------------|
| Allowed post types | [Post Types](#post-types) | Which post types to scan |
| Enable/disable | [Scan Existing Posts](#scan-existing-posts) | Whether scanning is enabled |

---

#### Link Access Validator

This event is triggered after an archived link has been found, and is responsible for determining whether the link allows validation via the Wayback Machine.

**Process:**
- Retrieves the link by its `link_id`
- Attempts to initiate a snapshot validation by calling the archive service (`create_snapshot()`)
- If successful, a `job_id` is returned and passed to the `iawmlf_check_validator_status` event
- If the archive service is offline, this event is rescheduled with a 1-hour delay

> **Note:** If the job creation fails or the archive service is unreachable, the system retries with exponential delay logic (via `iawmlf_check_validator_status`).

**Action:** `iawmlf_link_access_validator`

| **Argument** | **Type** | **Description** |
|--------------|----------|-----------------|
| `link_id` | int | The ID of the link to validate |

**Scheduling Options:**
- Immediate: `Link_Access_Validator_Event::add_to_queue( $link_id )`
- With delay: `Link_Access_Validator_Event::add_to_queue_with_delay( $link_id, $delay_in_seconds )`

---

#### Check Validator Status

This event is called by the `Link_Access_Validator_Event` to check the status of a link validation request against the Wayback Machine. It polls the Wayback Machine to check the status of a snapshot validation request.

**Process:**
- Attempts to retrieve the link from the database using its `link_id`
- Queries the Wayback Machine for the status of the snapshot using the `job_id`
- Based on the result:
  - If **success**, the link is marked as not broken
  - If **error**, the link is updated with the error message. If it's a `no-access` error, the link is excluded from further checks
  - If still **pending**, the event is rescheduled with an incremented attempt count
- If the Wayback Machine is offline, it retries after 1 hour

**Action:** `iawmlf_check_validator_status`

| **Argument** | **Type** | **Description** |
|--------------|----------|-----------------|
| `link_id` | int | ID of the link being validated |
| `job_id` | string | Job ID returned by the archive service |
| `attempt` | int | Current attempt number (starts at 0) |

**Filters:**

| **Configuration** | **Filter** | **Default** | **Description** |
|-------------------|------------|-------------|-----------------|
| Max attempts | [`iawmlf_check_validator_status_attempts`](#iawmlf_check_validator_status_attempts) | 3 | Maximum number of retry attempts |
| Retry interval | [`iawmlf_check_validator_status_interval`](#iawmlf_check_validator_status_interval) | 2 minutes | Delay between status checks |

---

#### Scan Posts Event

This event scans existing posts on the site to find those that either do not have link metadata or require reprocessing. It batches the posts and queues them for link processing.

**Process:**
- Queries posts of allowed types without the link meta or with outdated meta
- For each post found, triggers the `Process_Local_Post_Event` by adding it to the queue
- Reschedules itself to run again after a configurable interval
- Checks if the Wayback Machine API service is online before scanning; if offline, reschedules the scan to try later

> **Relationship:** This event serves as the "discovery" phase, identifying posts that need link processing and queuing them for the `Process_Local_Post_Event`.

**Action:** `iawmlf_scan_existing_posts`

*This event takes no arguments.*

**Filters:**

| **Configuration** | **Filter** | **Default** | **Description** |
|-------------------|------------|-------------|-----------------|
| Posts per batch | [`iawmlf_posts_per_batch`](#iawmlf_posts_per_batch) | 10 | Number of posts processed per batch |
| Scan interval | [`iawmlf_scan_posts_interval`](#iawmlf_scan_posts_interval) | 10 minutes | How often to run the scan |

**Settings:**

| **Configuration** | **Setting** | **Description** |
|-------------------|-------------|-----------------|
| Allowed post types | [Post Types](#post-types) | Which post types to scan |
| Enable/disable | [Scan Existing Posts](#scan-existing-posts) | Whether scanning is enabled |

---

#### Process Local Post Event

This event processes a single post to create a Wayback Machine snapshot of its permalink. It is triggered by the `Scan_Posts_Event` for each post identified.

**Process:**
- Checks if the Wayback Machine snapshot service is online; if offline, reschedules itself to retry after 1 hour
- Retrieves the post by ID and validates its existence
- Retrieves the permalink for the post
- Attempts to create a snapshot using the Wayback Machine service
- On success, updates post meta with the timestamp of the last processing
- Throws exceptions if critical steps fail (e.g., post not found, permalink missing, snapshot failure)

> **Relationship:** This event handles the actual archival snapshot creation for posts discovered and queued by the `Scan_Posts_Event`.

**Action:** `iawmlf_process_local_post`

| **Argument** | **Type** | **Description** |
|--------------|----------|-----------------|
| `post_id` | int | ID of the post to process |

---

#### Check Archive Services Online Event

This event verifies the availability of the archive API services, primarily the Wayback Machine, to determine if they are online and responsive.

**Process:**
- Instantiates the Wayback Machine service client
- Forces an immediate check of the archive API status, ignoring cached results
- Updates a transient cache to store the current online/offline status for use by other events and processes

The API status check result is cached for 1 hour by default.

> **Relationship:** This event supports all other events by providing a centralized mechanism to confirm the archive service's availability. Events such as `Scan_Posts_Event` and `Process_Local_Post_Event` rely on this status check to decide whether to proceed or delay their operations.

**Action:** `iawmlf_check_archive_services_online`

*This event takes no arguments.*

---

### Hooks

The plugin is designed to be extensible, with a number of hooks and filters available for developers to use.

---

#### Setting Override Filters

These filters allow you to programmatically override admin panel settings. **Filters take precedence over WordPress admin settings.**

#### `iawmlf_add_own_content_to_wayback_machine`

This filter overrides the admin setting that controls whether your own content is added to the Wayback Machine. The default is false.

```php
add_filter( 'iawmlf_add_own_content_to_wayback_machine', function( bool $add_own_content ): bool {
	return true;
});
```

> Please note when a post is added, a 10 minute delay is added before the post is added to the Wayback Machine. This will prevent the internet archive from blocking the request and creating lots of snapshots with no real changes.

#### `iawmlf_own_content_post_types`

This filter overrides the admin setting that controls which post types are allowed to be added to the Wayback Machine. The default is `post` and `page`.

```php
add_filter( 'iawmlf_own_content_post_types', function( array $post_types ): array {
	$post_types[] = 'custom_post_type';
	return $post_types;
});
```

#### `iawmlf_routinely_update_wayback_machine`

This filter overrides the admin setting that controls whether posts are routinely updated in the Wayback Machine. The default is false.

```php
add_filter( 'iawmlf_routinely_update_wayback_machine', function( bool $routinely_update ): bool {
	return true;
});
```

#### `iawmlf_routinely_update_wayback_machine_interval`

This filter overrides the admin setting that controls how long between each routine update. The default is 14 days. The time is given in seconds.

```php
add_filter( 'iawmlf_routinely_update_wayback_machine_interval', function( int $interval ): int {
	return 7 * \DAY_IN_SECONDS; // 7 days
});
```

#### `iawmlf_link_exclusions`

This filter enhances the link exclusions defined in admin settings by adding additional exclusions to the link checker.

```php
add_filter( 'iawmlf_link_exclusions', function( array $exclusions ): array {
   $exclusions[] = 'https://example.com/*';
   return $exclusions;
});
```

#### `iawmlf_is_production_environment`

This filter allows you to override the production environment detection. By default, the plugin uses WordPress's `wp_get_environment_type()` function to determine if the site is running in a production environment. You can use this filter to customize this behavior for your specific setup.

```php
add_filter( 'iawmlf_is_production_environment', function( bool $is_production ): bool {
   // Example: Force production mode based on domain
   if ( strpos( $_SERVER['HTTP_HOST'], '.com' ) !== false ) {
      return true;
   }
   
   // Example: Check for custom environment variable
   if ( defined( 'MY_CUSTOM_PRODUCTION_FLAG' ) && MY_CUSTOM_PRODUCTION_FLAG ) {
      return true;
   }
   
   return $is_production;
});
```

---

#### Configuration Filters

These filters control various aspects of plugin behavior and performance.

#### `iawmlf_link_checker_timeout`

This is used to determine how long we should wait when checking if a link is still valid. The default is 5000ms (5 seconds).

```php
add_filter( 'iawmlf_link_checker_timeout', function( int $timeout ): int {
   return 10000; // 10 seconds
});
```

#### `iawmlf_should_render_html_link_output`

This filter allows you to control whether the HTML link output should be rendered in the frontend for post loops. By default, this is only enabled when the fixer option is set to "Replace Link".

```php
add_filter( 'iawmlf_should_render_html_link_output', function( bool $allowed, string $option ): bool {
   // Example: Always render the HTML link output regardless of fixer option
   return true;
}, 10, 2 );
```

> **Note:** The HTML link output is used to provide link data to the frontend JavaScript for post loops. If disabled, the link data won't be available for JavaScript processing in loop contexts.

#### `iawmlf_posts_per_batch`

This is used to define how many posts should be checked, when the plugin is scanning existing posts.

```php
add_filter( 'iawmlf_posts_per_batch', function( int $posts_per_batch ): int {
   return 20;
});
```

##### `iawmlf_link_check_duration_in_days`

This is used to define how many days should be between checking if a link is still valid. The default is 3 days.

```php
add_filter( 'iawmlf_link_check_duration_in_days', function( int $days ): int {
   return 14; // 14 days
});
```

#### `iawmlf_valid_http_status_codes`

This return array is used to determine what http status codes are considered valid. The default is `200`, `206` and `429`.

```php
add_filter( 'iawmlf_valid_http_status_codes', function( array $codes ): array {
   $codes[] = 301;
   return $codes;
});
```

#### `iawmlf_failed_count`

This is used to define how many checks with non valid status codes are encountered before marking a link as broken. The default is 3.

```php
add_filter( 'iawmlf_failed_count', function( int $checks ): int {
   return 4;
});

#### `iawmlf_create_new_snapshot_attempts`

This is used to define how many times we should attempt to create a new snapshot. The default is 3.

```php
add_filter( 'iawmlf_create_new_snapshot_attempts', function( int $attempts ): int {
   return 5;
});
```
#### `iawmlf_check_snapshot_status_attempts`

This is used to define how many times we should attempt to check the status of a snapshot. The default is 3.

```php
add_filter( 'iawmlf_check_snapshot_status_attempts', function( int $attempts ): int {
   return 5;
});
```

#### `iawmlf_check_snapshot_status_interval`

This is used to define how long we should wait between checking the status of a snapshot. The default is 300 seconds (5 minutes).

```php
add_filter( 'iawmlf_check_snapshot_status_interval', function( int $interval ): int {
   return 10 * \MINUTE_IN_SECONDS; // 10 minutes
});
```

#### `iawmlf_update_archive_url_attempts`

This is used to define how many times we should attempt to update the archive URL. The default is 3.

```php
add_filter( 'iawmlf_update_archive_url_attempts', function( int $attempts ): int {
   return 5;
});
```

#### `iawmlf_scan_posts_interval`

This is used to define how often we should check for posts which have not been scanned. The default is 10 minutes.

```php
add_filter( 'iawmlf_scan_posts_interval', function( int $interval ): int {
   return 5 * \MINUTE_IN_SECONDS; // 5 minutes
});
```

#### `iawmlf_check_validator_status_interval`

This is used to define how often we should check if the validator is still running. The default is 2 minutes.

```php
add_filter( 'iawmlf_check_validator_status_interval', function( int $interval ): int {
	return 1 * \MINUTE_IN_SECONDS; // 1 minute
});
```

#### `iawmlf_check_validator_status_attempts`

This is used to define how many times we should attempt to check if the validator is still running. The default is 3.

```php
add_filter( 'iawmlf_check_validator_status_attempts', function( int $attempts ): int {
	return 5;
});
```

#### `iawmlf_scan_own_posts_event_interval`

This is used to define how often the scan own posts event should run. The default is 15 minutes.

```php
add_filter( 'iawmlf_scan_own_posts_event_interval', function( int $interval ): int {
	return 30 * \MINUTE_IN_SECONDS; // 30 minutes
});
```

#### `iawmlf_scan_own_posts_per_call`

This is used to define how many posts should be processed per call when scanning own posts. The default is 10.

```php
add_filter( 'iawmlf_scan_own_posts_per_call', function( int $posts_per_call ): int {
	return 20;
});
```

#### `iawmlf_show_link_table_debug_data`

This is used to show additional debug data in the link table. This is for debugging purposes only. The default is false.

```php
add_filter( 'iawmlf_show_link_table_debug_data', function( bool $show_debug ): bool {
	return true;
});
```

#### `iawmlf_dashboard_link_count`

This is used to define how many links are shown in the admin dashboard panels. The default is 10.

```php
add_filter( 'iawmlf_dashboard_link_count', function( int $link_count ): int {
	return 15; // Show 15 links instead of 10
});
```

#### `iawmlf_dashboard_link_stats_cache_expiry`

This is used to define how long dashboard link statistics are cached for. The default is 120 seconds (2 minutes).

```php
add_filter( 'iawmlf_dashboard_link_stats_cache_expiry', function( int $cache_expiry ): int {
	return 5 * \MINUTE_IN_SECONDS; // Cache for 5 minutes
});
```

#### `iawmlf_dashboard_onboarding_stats_cache_expiry`

This is used to define how long dashboard onboarding statistics are cached for. The default is 120 seconds (2 minutes).

```php
add_filter( 'iawmlf_dashboard_onboarding_stats_cache_expiry', function( int $cache_expiry ): int {
	return 5 * \MINUTE_IN_SECONDS; // Cache for 5 minutes
});
```

#### `iawmlf_menu_icon_base64`

This is used to override the base64 encoded PNG for the admin menu item icon. This allows you to customize the plugin's menu icon.

```php
add_filter( 'iawmlf_menu_icon_base64', function( string $icon ): string {
	return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='; // Your custom base64 icon
});
```

---

#### Advanced Customization

These filters allow advanced customization of URLs, timeouts, and client implementations.

#### `iawmlf_is_valid_check`

This filter is used when a url is checked and we are returning if the link is valid or not. The default is to check if the status code is in the `iawmlf_valid_http_status_codes` array.

```php
add_filter( 'iawmlf_is_valid_check', function( bool $is_valid, array $check, Link $link ): bool {
   // If the link is from foo.com and the status code is 301 or 302, treate as valid
   if ( strpos( $link->get_href, 'foo.com' ) !== false && in_array( $check['status_code'], [ 301, 302 ] ) ) {
	  return true;
   }
});
```

> The `$check` array contains the following keys: `status_code (string)`, `date (Y-m-d H:i:s)`.
> For all public methods of the `Link` model, see the codebase (src/Link/Link.php)

#### `iawmlf_exclude_link_from_post`

This allows a link to be excluded from the list of links generated for a post. If a link is excluded, it will not be checked or have its link replaced when viewing the post.

```php
add_filter( 'iawmlf_exclude_link_from_post', function( bool $exclude, Link $link, int $post_id ): bool {
	if ( strpos( $link->get_href(), 'example.com' ) !== false ) {
	  return true;
	}
	return $exclude;
});
```

> Please note if a link is already being excluded, this is likely due to the site blocking any uptime checking bots and allowing these links to be checked will likely result in false positives.

#### `iawmlf_own_content_allow_post`

This filter allows a final decision to be made on if a post should be added to the Wayback Machine. The default is to allow all posts.

```php
add_filter( 'iawmlf_own_content_allow_post', function( bool $allow, int $post_id ): bool {
	if ( get_post_meta( $post_id, 'do_not_archive', true ) ) {
		return false;
	}
	return $allow;
});
```

#### `iawmlf_link_checker_url_params`

This is the array of parameters which are passed to the `wp_safe_remote_get` function when checking if a link is still valid.

> Please note url=https://the-url-to-check.com should always passed.

```php
add_filter( 'iawmlf_link_checker_url_params', function( array $params ): array {
   $params['skip_cache'] = 10; // Skip the IA cache (5 mins by default)
   return $params; 
});
```

Additional args
* `impersonate=1`: use https://github.com/yifeikong/curl_cffi
to impersonate Chrome 110 and potentially avoid TLS fingerprinting blockers.
* `skip_adblocker=1`: The service uses an Adblocker by default https://pypi.org/project/braveblock/
* `skip_cache=1`: The service caches results for 5 minutes. Use this param to skip the cache.
* `kip_wbm_blocker=1`: The service blocks Wayback Machine URLs by default. Use this parameter to skip it
* `user_agent=<str>`: Use a custom `user-agent` HTTP header

#### `iawmlf_link_checker_url_base`

This is the base url of the link checker and doesnt really need changing unless you are running tests or your own custom endpoint for addtional caching.

```php
add_filter( 'iawmlf_link_checker_url_base', function( string $url ): string {
   return 'https://my-custom-link-checker.com';
});
```

#### `iawmlf_find_snapshot_base_url`

This is the url which is used when looking for a snapshot of a link. This should not need changing unless you are running tests or have your own custom endpoint.

```php
add_filter( 'iawmlf_find_snapshot_base_url', function( string $url ): string {
	   return 'https://my-custom-snapshot-finder.com';
});
```

> Please note these only apply when using the default `Link_Checker_Client` class.

#### `iawmlf_get_latest_snapshot_url`

This is the url which is called to get the latest snapshot of a link.

```php
add_filter( 'iawmlf_get_latest_snapshot_url', function( string $base_url, string $url ): string {
	return sprintf( '%s?url=%s', $base_url, urlencode( $url ) );
});
```

#### `iawmlf_get_closest_snapshot_url`

This is the url which is called to get the snapshot closest to a defined date.

```php
add_filter( 'iawmlf_get_closest_snapshot_url', function( string $base_url, string $url, DateTime $date ): string {
	return sprintf( '%s?url=%s&timestamp=%s', $base_url, urlencode( $url ), $date->getTimestamp() );
});
```

#### `iawmlf_get_latest_snapshot_timeout`

This is used to set the timeout for getting the latest snapshot of a link. The default is 10 seconds.

```php
add_filter( 'iawmlf_get_latest_snapshot_timeout', function( int $timeout ): int {
	return 30; // 30 seconds
});
```

#### `iawmlf_get_closest_snapshot_timeout`

This is used to set the timeout for getting the closest snapshot to a defined date. The default is 10 seconds.

```php
add_filter( 'iawmlf_get_closest_snapshot_timeout', function( int $timeout ): int {
	return 30; // 30 seconds
});
```

#### `iawmlf_create_snapshot_url`

This is the url which is used when creating a new snapshot. This should not need changing unless you are running tests or have your own custom endpoint.

```php
add_filter( 'iawmlf_create_snapshot_url', function( string $url ): string {
	return 'https://my-custom-snapshot-creator.com';
});
```

#### `iawmlf_create_snapshot_timeout`

This is used to set the timeout for creating a new snapshot. The default is 1000 seconds (16.7 minutes).

```php
add_filter( 'iawmlf_create_snapshot_timeout', function( int $timeout ): int {
	return 2000; // 33+ minutes
});
```

#### `iawmlf_reporting_page_capability`

This filter allows you to change the required capability for accessing the reporting page. The default is `manage_options`.

```php
add_filter( 'iawmlf_reporting_page_capability', function( string $capability ): string {
	return 'edit_posts'; // Allow editors to access the reporting page
});
```

### Internet Archive / Wayback Link Fixer Instances.

Both the Link Checker and Snapshot clients are all extended from the following interfaces:  

* Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Link_Checker_Client  
* Internet_Archive\Wayback_Machine_Link_Fixer\Wayback_Machine\Snapshot_Client  

Both of these classes return documented arrays of data, so can be overridden to use a different service if needed.

To change which class is used, you can use the following filters:

#### Link Checker Client.

```php
class My_Custom_Link_Checker_Client implements Link_Checker_Client {
   ....
}

add_filter( 'iawmlf_link_checker_client', function( Link_Checker_Client $client ): Link_Checker_Client {
   return new My_Custom_Link_Checker_Client();
});
```

#### Snapshot Client.

```php
class My_Custom_Snapshot_Client implements Snapshot_Client {
   ....
}

add_filter( 'iawmlf_snapshot_client', function( Snapshot_Client $client ): Snapshot_Client {
   return new My_Custom_Snapshot_Client();
});
```

#### Assorted Hints and Tips

If you ever need to restart the wizard, you can access the follow url `wp-admin/admin.php?page=iawmlf-setup-wizard&rerun-wizard=1` while logged in as an admin user, this will reset the wizard and allow you to run through it again. This is helpful for testing or fixing issues where the wizard will keep showing after completion.

### Contribute

If you would like to contribute to the this plugin, feel free to do so. There are a number of tools which can be used to help in your development.

#### PHPCS\PHPCBF

This project is setup to use a customised version of the WordPress Extra ruleset. This is to ensure that the code is following the WordPress coding standards. To run the checks, you can use the following command:

```bash
composer lint:php # This will run the code through phpcs
composer format:php # This will run the code through phpcbf
```

#### PHPUnit

The plugin comes with a small set of unit tests, these must all pass before a PR can be merged. To run the tests, you can use the following command:

```bash
composer test:php
```

> You can run the full set of linting and tests with `composer run:php`, this will install dev dependencies and run the tests and then optimize the autoloader with a production ready version.

#### Frontend build (npm)

Install dependencies and run common tasks:

```bash
npm install # Install JS/CSS dependencies

npm run lint # Lint JS/CSS
npm run format # Auto-fix JS/CSS where possible

npm run start # Start watch builds for blocks, scripts, and styles
npm run build # Create production builds for blocks, scripts, and styles	

```

#### Internationalization (i18n)

Run the full internationalization workflow (generate POT, update PO, build JSON) with a single command:

```bash
composer internationalize
```


