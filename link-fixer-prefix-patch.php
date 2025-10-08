<?php

/**
 * Plugin Name: Link Fixer Prefix Patch
 * Description: This plugin patches the link fixer prefixs from the old to the new.
 * Version: 1.0.0
 * Author: Internet Archive
 * Author URI: https://archive.org
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: link-fixer-prefix-patch
 * Domain Path: /languages
 */
return;
// Handle legacy actions.
add_action(
	'iawmlf_create_new_snapshot',
	function ( $link_id, $attempt ) {
		do_action( 'iawmlf_create_new_snapshot', $link_id, $attempt );
	}
);

add_action(
	'iawmlf_update_archive_url',
	function ( $link_id, $attempt ) {
		do_action( 'iawmlf_update_archive_url', $link_id, $attempt );
	}
);


add_action(
	'iawmlf_scan_existing_posts',
	function () {
		do_action( 'iawmlf_scan_existing_posts' );
	}
);

add_action(
	'iawmlf_check_snapshot_status',
	function ( $link_id, $job_id, $attempt ) {
		do_action( 'iawmlf_check_snapshot_status', $link_id, $job_id, $attempt );
	}
);

add_action(
	'iawmlf_find_or_create_snapshot',
	function ( $link_id ) {
		do_action( 'iawmlf_find_or_create_snapshot', $link_id );
	}
);

add_action(
	'iawmlf_link_access_validator',
	function ( $link_id ) {
		do_action( 'iawmlf_link_access_validator', $link_id );
	}
);

add_action(
	'iawmlf_check_validator_status',
	function ( $link_id, $job_id, $attempt ) {
		do_action( 'iawmlf_check_validator_status', $link_id, $job_id, $attempt );
	}
);

add_action(
	'iawmlf_check_archive_services_online',
	function () {
		do_action( 'iawmlf_check_archive_services_online' );
	}
);

add_action(
	'iawmlf_process_local_post',
	function ( $post_id ) {
		do_action( 'iawmlf_process_local_post', $post_id );
	}
);

add_action(
	'iawmlf_add_own_posts',
	function () {
		do_action( 'iawmlf_add_own_posts' );
	}
);

// Handle legacy filters.
// Handle legacy filters. Map new `iawmlf_` filters to legacy `wlf_` equivalents.
add_filter(
	'iawmlf_find_snapshot_base_url',
	function ( $url ) {
		return apply_filters( 'iawmlf_find_snapshot_base_url', $url );
	}
);

add_filter(
	'iawmlf_get_latest_snapshot_url',
	function ( $url, $api_url ) {
		return apply_filters( 'iawmlf_get_latest_snapshot_url', $url, $api_url );
	}
);

add_filter(
	'iawmlf_get_closest_snapshot_url',
	function ( $api_url, $url, $date ) {
		return apply_filters( 'iawmlf_get_closest_snapshot_url', $api_url, $url, $date );
	}
);

add_filter(
	'iawmlf_get_latest_snapshot_timeout',
	function ( $timeout ) {
		return apply_filters( 'iawmlf_get_latest_snapshot_timeout', $timeout );
	}
);

add_filter(
	'iawmlf_get_closest_snapshot_timeout',
	function ( $timeout ) {
		return apply_filters( 'iawmlf_get_closest_snapshot_timeout', $timeout );
	}
);

add_filter(
	'iawmlf_create_snapshot_url',
	function ( $url ) {
		return apply_filters( 'iawmlf_create_snapshot_url', $url );
	}
);

add_filter(
	'iawmlf_create_snapshot_timeout',
	function ( $timeout ) {
		return apply_filters( 'iawmlf_create_snapshot_timeout', $timeout );
	}
);

add_filter(
	'iawmlf_link_checker_url_params',
	function ( $params ) {
		return apply_filters( 'iawmlf_link_checker_url_params', $params );
	}
);

add_filter(
	'iawmlf_link_checker_url_base',
	function ( $url ) {
		return apply_filters( 'iawmlf_link_checker_url_base', $url );
	}
);

add_filter(
	'iawmlf_dashboard_link_count',
	function ( $count ) {
		return apply_filters( 'iawmlf_dashboard_link_count', $count );
	}
);

add_filter(
	'iawmlf_menu_icon_base64',
	function ( $icon ) {
		return apply_filters( 'iawmlf_menu_icon_base64', $icon );
	}
);

add_filter(
	'iawmlf_link_checker_timeout',
	function ( $timeout ) {
		return apply_filters( 'iawmlf_link_checker_timeout', $timeout );
	}
);

add_filter(
	'iawmlf_link_exclusions',
	function ( $links ) {
		return apply_filters( 'iawmlf_link_exclusions', $links );
	}
);

add_filter(
	'iawmlf_posts_per_batch',
	function ( $per_batch ) {
		return apply_filters( 'iawmlf_posts_per_batch', $per_batch );
	}
);

add_filter(
	'iawmlf_link_check_duration_in_days',
	function ( $days ) {
		return apply_filters( 'iawmlf_link_check_duration_in_days', $days );
	}
);

add_filter(
	'iawmlf_valid_http_status_codes',
	function ( $codes ) {
		return apply_filters( 'iawmlf_valid_http_status_codes', $codes );
	}
);

add_filter(
	'iawmlf_failed_count',
	function ( $count ) {
		return apply_filters( 'iawmlf_failed_count', $count );
	}
);

add_filter(
	'iawmlf_add_own_content_to_wayback_machine',
	function ( $allow ) {
		return apply_filters( 'iawmlf_add_own_content_to_wayback_machine', $allow );
	}
);

add_filter(
	'iawmlf_own_content_post_types',
	function ( $post_types ) {
		return apply_filters( 'iawmlf_own_content_post_types', $post_types );
	}
);

add_filter(
	'iawmlf_own_content_allow_post',
	function ( $can_add, $post ) {
		return apply_filters( 'iawmlf_own_content_allow_post', $can_add, $post );
	}
);

add_filter(
	'iawmlf_show_link_table_debug_data',
	function ( $show ) {
		return apply_filters( 'iawmlf_show_link_table_debug_data', $show );
	}
);

add_filter(
	'iawmlf_scan_own_posts_event_interval',
	function ( $interval ) {
		return apply_filters( 'iawmlf_scan_own_posts_event_interval', $interval );
	}
);

add_filter(
	'iawmlf_scan_own_posts_per_call',
	function ( $count ) {
		return apply_filters( 'iawmlf_scan_own_posts_per_call', $count );
	}
);

add_filter(
	'iawmlf_scan_posts_interval',
	function ( $interval ) {
		return apply_filters( 'iawmlf_scan_posts_interval', $interval );
	}
);

add_filter(
	'iawmlf_create_new_snapshot_attempts',
	function ( $attempts ) {
		return apply_filters( 'iawmlf_create_new_snapshot_attempts', $attempts );
	}
);

add_filter(
	'iawmlf_update_archive_url_attempts',
	function ( $attempts ) {
		return apply_filters( 'iawmlf_update_archive_url_attempts', $attempts );
	}
);

add_filter(
	'iawmlf_exclude_link_from_post',
	function ( $is_excluded, $link, $post_id ) {
		return apply_filters( 'iawmlf_exclude_link_from_post', $is_excluded, $link, $post_id );
	},
	10,
	3
);

add_filter(
	'iawmlf_is_valid_check',
	function ( $is_valid, $check, $link_obj ) {
		return apply_filters( 'iawmlf_is_valid_check', $is_valid, $check, $link_obj );
	},
	10,
	3
);

add_filter(
	'iawmlf_check_snapshot_status_attempts',
	function ( $attempts ) {
		return apply_filters( 'iawmlf_check_snapshot_status_attempts', $attempts );
	}
);

add_filter(
	'iawmlf_check_snapshot_status_interval',
	function ( $interval ) {
		return apply_filters( 'iawmlf_check_snapshot_status_interval', $interval );
	}
);

add_filter(
	'iawmlf_check_validator_status_attempts',
	function ( $attempts ) {
		return apply_filters( 'iawmlf_check_validator_status_attempts', $attempts );
	}
);

add_filter(
	'iawmlf_check_validator_status_interval',
	function ( $interval ) {
		return apply_filters( 'iawmlf_check_validator_status_interval', $interval );
	}
);

add_filter(
	'iawmlf_routinely_update_wayback_machine',
	function ( $allow ) {
		return apply_filters( 'iawmlf_routinely_update_wayback_machine', $allow );
	}
);

add_filter(
	'iawmlf_routinely_update_wayback_machine_interval',
	function ( $interval ) {
		return apply_filters( 'iawmlf_routinely_update_wayback_machine_interval', $interval );
	}
);

add_filter(
	'iawmlf_reporting_page_capability',
	function ( $capability ) {
		return apply_filters( 'iawmlf_reporting_page_capability', $capability );
	}
);

/**
 * Display the admin page for the prefix patch tool.
 *
 * @return void
 */
function link_fixer_prefix_patch_admin_page(): void {
	$results = link_fixer_prefix_patch_admin_page_process_action();
	?>
	<style>
		.link-fixer-patch-container {
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			padding: 20px;
			margin: 20px 0;
			box-shadow: 0 1px 1px rgba(0,0,0,.04);
		}
		.link-fixer-patch-form {
			background: #f9f9f9;
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 15px;
			margin: 15px 0;
		}
		.link-fixer-patch-results {
			background: #f1f1f1;
			border: 1px solid #ddd;
			border-radius: 4px;
			padding: 15px;
			margin: 15px 0;
		}
		.link-fixer-patch-success {
			background: #f0f8ff;
			border: 1px solid #0073aa;
			border-radius: 4px;
			padding: 10px;
			margin: 10px 0;
			color: #0073aa;
		}
		.link-fixer-patch-success-message {
			background: #f0fff0;
			border: 1px solid #46b450;
			border-radius: 4px;
			padding: 10px;
			margin: 10px 0;
			color: #46b450;
		}
		.link-fixer-patch-error {
			background: #fef7f7;
			border: 1px solid #dc3232;
			border-radius: 4px;
			padding: 10px;
			margin: 10px 0;
			color: #dc3232;
		}
		.link-fixer-patch-summary {
			cursor: pointer;
			font-weight: bold;
			padding: 5px 0;
		}
		.link-fixer-patch-list {
			margin: 10px 0;
			padding-left: 20px;
		}
		.link-fixer-patch-list li {
			margin: 5px 0;
		}
	</style>
	<div class="wrap">
		<div class="link-fixer-patch-container">
			<h1>Link Fixer Prefix Patch</h1>
			<p>This tool helps migrate from the old <code>wlf_</code> prefix to the new <code>iawmlf_</code> prefix.</p>

		<div class="link-fixer-patch-form">
			<form method="post" action="">
				<?php wp_nonce_field( 'link_fixer_patch' ); ?>
				<input type="hidden" name="link_fixer_patch" value="patch">

				<table class="form-table">
				<tr>
					<th scope="row">Patch Meta Keys</th>
					<td>
						<select name="meta_action" id="meta_action">
							<option value="none">Select Action</option>
							<option value="list">List</option>
							<option value="replace">Replace</option>
							<option value="duplicate">Duplicate</option>
						</select>
						<p class="description">
							<strong>List:</strong> Show current meta keys with old prefix<br>
							<strong>Replace:</strong> Will remove the old meta/settings<br>
							<strong>Duplicate:</strong> Will make a copy, so the old data still exists
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">Patch Settings</th>
					<td>
						<select name="settings_action" id="settings_action">
							<option value="none">Select Action</option>
							<option value="list">List</option>
							<option value="replace">Replace</option>
							<option value="duplicate">Duplicate</option>
						</select>
						<p class="description">
							<strong>List:</strong> Show current settings with old prefix<br>
							<strong>Replace:</strong> Will remove the old meta/settings<br>
							<strong>Duplicate:</strong> Will make a copy, so the old data still exists
						</p>
					</td>
				</tr>
			</table>

				<?php submit_button( 'Execute Patch Actions' ); ?>
			</form>
		</div>

		<div class="link-fixer-patch-results">
			<h3>Results will appear here:</h3>
			<div id="meta-results">
				<strong>Meta Keys:</strong>
				<?php if ( isset( $_POST['link_fixer_patch'] ) ) : ?>
					<?php
					// Show the meta keys details
					if ( isset( $results['meta'] ) && ! empty( $results['meta'] ) ) :
						?>
						<?php foreach ( $results['meta'] as $meta_key => $posts ) : ?>
							<?php
							if ( 'results' === $meta_key ) {
								continue;}
							?>
							<?php if ( ! empty( $posts ) ) : ?>
								<?php
								$total_posts = 0;
								foreach ( $posts as $post_type => $post_ids ) {
									$total_posts += count( $post_ids );
								}
								?>
								<div class="link-fixer-patch-success" style="margin: 10px 0;">
									<h4 style="margin: 0 0 10px 0; color: #0073aa;"><?php echo esc_html( $meta_key ); ?> (<?php echo $total_posts; ?> posts)</h4>
									<ul class="link-fixer-patch-list">
										<?php foreach ( $posts as $post_type => $post_ids ) : ?>
											<?php if ( ! empty( $post_ids ) ) : ?>
												<li><?php echo esc_html( $post_type ); ?> - <?php echo count( $post_ids ); ?> posts</li>
											<?php endif; ?>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php else : ?>
						<p><em>No meta keys found with old prefix.</em></p>
					<?php endif; ?>

					<?php
					// If we have any errors, show them.
					if ( isset( $results['meta']['results'] ) && count( $results['meta']['results']['errors'] ) !== 0 ) {
						// Get the count.
						$count = count( $results['meta']['results']['errors'] ) + count( $results['meta']['results']['success'] );
						// Show these as a details accordian
						echo '<details class="link-fixer-patch-error">';
						echo '<summary class="link-fixer-patch-summary">Errors (' . esc_html( $count ) . ')</summary>';
						echo '<ul class="link-fixer-patch-list">';
						foreach ( $results['meta']['results']['errors'] as $error ) {
							echo '<li>' . esc_html( $error ) . '</li>';
						}
						echo '</ul>';
						echo '</details>';
					}

					// If we have any success, show the count.
					if ( isset( $results['meta']['results'] ) && count( $results['meta']['results']['success'] ) !== 0 ) {
						$count = count( $results['meta']['results']['success'] );
						echo '<div class="link-fixer-patch-success-message">';
						echo '<h4 style="margin: 0 0 10px 0;">Success: ' . esc_html( $count ) . ' items processed</h4>';
						echo '</div>';
					}
					?>
				<?php else : ?>
					<p><em>Meta keys results will be displayed here...</em></p>
				<?php endif; ?>


			</div>
			<div id="settings-results">
				<strong>Settings:</strong>
				<?php if ( isset( $_POST['link_fixer_patch'] ) ) : ?>
					<?php
					// Show the settings details
					if ( isset( $results['settings'] ) && ! empty( $results['settings'] ) ) :
						?>
						<?php
						foreach ( $results['settings'] as $category => $settings ) :
							// if results, skip.
							if ( 'results' === $category ) {
								continue;
							}
							?>
							<?php if ( ! empty( $settings ) ) : ?>
								<div class="link-fixer-patch-success" style="margin: 10px 0;">
									<h4 style="margin: 0 0 10px 0; color: #0073aa;"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $category ) ) ); ?> (<?php echo count( $settings ); ?>)</h4>
									<ul class="link-fixer-patch-list">
										<?php foreach ( $settings as $setting ) : ?>
											<li><?php echo esc_html( is_array( $setting ) ? print_r( $setting, true ) : $setting ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php else : ?>
						<p><em>No settings found with old prefix.</em></p>
					<?php endif; ?>
				<?php else : ?>
					<p><em>Settings results will be displayed here...</em></p>
				<?php endif; ?>

				<?php
				// If we have any errors, show them.
				if ( isset( $results['settings']['results'] ) && count( $results['settings']['results']['errors'] ) !== 0 ) {
					// Get the count.
					$count = count( $results['settings']['results']['errors'] ) + count( $results['settings']['results']['success'] );
					// Show these as a details accordian
					echo '<details class="link-fixer-patch-error">';
					echo '<summary class="link-fixer-patch-summary">Errors (' . esc_html( $count ) . ')</summary>';
					echo '<ul class="link-fixer-patch-list">';
					foreach ( $results['settings']['results']['errors'] as $error ) {
						echo '<li>' . esc_html( $error ) . '</li>';
					}
					echo '</ul>';
					echo '</details>';
				}

				// If we have any success, show the count.
				if ( isset( $results['settings']['results'] ) && count( $results['settings']['results']['success'] ) !== 0 ) {
					$count = count( $results['settings']['results']['success'] );
					echo '<div class="link-fixer-patch-success-message">';
					echo '<h4 style="margin: 0 0 10px 0;">Success: ' . esc_html( $count ) . ' settings processed</h4>';
					echo '</div>';
				}
				?>
			</div>
		</div>
	</div>
	<?php
}


add_action(
	'admin_menu',
	function () {
		add_submenu_page(
			'wayback-link-fixer-dashboard',
			'Wayback Link Fixer - Links',
			'Link Fixer Patch',
			'manage_options',
			'link-fixer-prefix-patch',
			'link_fixer_prefix_patch_admin_page'
		);
	}
);

/**
 * Process the admin page form submission and return results.
 *
	 * @return array{meta: string[], settings: string[]} The processed results for meta keys and settings.
 */
function link_fixer_prefix_patch_admin_page_process_action(): array {
	$results = array(
		'meta'     => array(),
		'settings' => array(),
	);
	// If the form has not been submitted, return the results.
	if ( ! isset( $_POST['link_fixer_patch'] ) ) {
		return $results;
	}

	// Verify the nonce.
	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'link_fixer_patch' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>Nonce verification failed</p></div>';
			}
		);
		return $results;
	}
	// Get the meta action.
	$meta_action = isset( $_POST['meta_action'] ) ? sanitize_text_field( $_POST['meta_action'] ) : 'list';

	// If the form has been submitted, handle the form.
	$posts            = link_fixer_prefix_patch_get_posts();
	$posts['results'] = link_fixer_prefix_patch_process_post_meta_keys( $posts, $meta_action );

	$results['meta'] = $posts;

	// Get the settings action.
	$settings_action     = isset( $_POST['settings_action'] ) ? sanitize_text_field( $_POST['settings_action'] ) : 'list';
	$settings            = link_fixer_prefix_patch_get_settings();
	$settings['results'] = link_fixer_prefix_patch_process_settings( $settings_action );
	$results['settings'] = $settings;

	return $results;
}

/**
 * Gets all the posts with the old prefixes.
 *
 * @return array<string, array<string, integer[]>> ['wlf_key' => ['post' => [12, 13, 14], 'page' => [1, 2, 3], 'product' => [23, 24, 25]]]
 */
function link_fixer_prefix_patch_get_posts(): array {
	$meta_keys  = array( 't51_wlf_links', 't51_wlf_last_processed' );
	$post_types = get_post_types();

	$counts = array();

	// Loop over each meta key.
	foreach ( $meta_keys as $lfp_meta_key ) {
		$counts[ $lfp_meta_key ] = array();
		// Loop over each post type.
		foreach ( $post_types as $lfp_post_type ) {
			// Query all post in that post type that have this meta key.
			$query = new \WP_Query(
				array(
					'post_type'      => $lfp_post_type,
					'meta_key'       => array(
						'meta_key' => $lfp_meta_key,
						'compare'  => 'EXISTS',
					),
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'cache_results'  => false,
					'post_status'    => 'any',
				)
			);

			// Add the count to the results.
			$counts[ $lfp_meta_key ][ $lfp_post_type ] = $query->get_posts();
		}
	}

	return $counts;
}

/**
 * Process the post meta keys for migration.
 *
 * @param array<string, array<string, integer[]>> $posts  The posts with meta keys.
 * @param string                                  $action The action to perform (list, replace, duplicate).
 *
 * @return array{errors: string[], success: string[]} Results of the processing operation.
 */
function link_fixer_prefix_patch_process_post_meta_keys( array $posts, string $action ): array {
	$results = array(
		'errors'  => array(),
		'success' => array(),
	);

	// if action is not duplicate or replace, return the results.
	if ( 'duplicate' !== $action && 'replace' !== $action ) {
		return $results;
	}

	// Iterate over the posts lists.
	foreach ( $posts as $meta_key => $post_types ) {
		foreach ( $post_types as $post_type => $posts ) {
			foreach ( $posts as $post_id ) {
				$initial_data = get_post_meta( $post_id, $meta_key, true );
				$new_key      = 't51_wlf_links' === $meta_key ? 'iawmlf_links' : 'iawmlf_last_processed';
				$result       = update_post_meta( $post_id, $new_key, $initial_data );
				if ( 'replace' === $action ) {
					delete_post_meta( $post_id, $meta_key );
				}
				if ( false === $result ) {
					$results['errors'][] = sprintf( 'Failed to update meta key %s for %s #%s', esc_html( $meta_key ), esc_html( $post_type ), esc_html( $post_id ) );
				} else {
					$results['success'][] = sprintf( 'Updated meta key %s for %s #%s', esc_html( $new_key ), esc_html( $post_type ), esc_html( $post_id ) );
				}
			}
		}
	}

	return $results;
}

/**
 * Get all the settings keys that need to be migrated.
 *
 * @return string[] Array of setting keys with the old prefix.
 */
function link_fixer_prefix_patch_get_settings_keys(): array {
	return array(
		't51_wlf_process_links',
		't51_wlf_post_types',
		't51_wlf_migration_log',
		't51_wlf_drop_tables_uninstall',
		't51_iawmlf_link_exclusions',
		't51_iawmlf_scan_existing_posts',
		't51_wlf_archive_api_secret',
		't51_wlf_archive_api_access',
		't51_wlf_fixer_option',
		't51_wlf_archive_api_status',
		't51_wlf_archive_api_creds_valid',
		't51_wlf_allow_own_content_submissions',
		't51_wlf_allowed_own_content_post_types',
		't51_iawmlf_routinely_update_wayback_machine',
		't51_iawmlf_routinely_update_wayback_machine_interval',
	);
}

/**
 * Get all the settings with the old prefixes.
 *
 * @return array{with_values: string[], without_values: string[]} Settings grouped by whether they have values.
 */
function link_fixer_prefix_patch_get_settings(): array {

	$results = array(
		'with_values'    => array(),
		'without_values' => array(),
	);
	foreach ( link_fixer_prefix_patch_get_settings_keys() as $setting ) {
		$value = get_option( $setting, null );
		if ( $value ) {
			$results['with_values'][] = $setting;
		} else {
			$results['without_values'][] = $setting;
		}
	}
	return $results;
}

/**
 * Process the settings for migration.
 *
 * @param string $action The action to perform (list, replace, duplicate).
 *
 * @return array{errors: string[], success: string[]} Results of the processing operation.
 */
function link_fixer_prefix_patch_process_settings( string $action ): array {
	$results = array(
		'errors'  => array(),
		'success' => array(),
	);

	// if action is not duplicate or replace, return the results.
	if ( 'duplicate' !== $action && 'replace' !== $action ) {
		return $results;
	}

	// Loop over the settings, back up the old values.
	foreach ( link_fixer_prefix_patch_get_settings_keys() as $setting ) {
		$old_value = get_option( $setting, null );
		$new_key   = str_replace( 't51_wlf_', 'iawmlf_', $setting );
		// Check if option is autoloaded.
		$autoload = 'yes' === get_option( $setting . '_autoload', 'no' );

		$result = update_option( $new_key, $old_value, $autoload );

		if ( 'replace' === $action ) {
			delete_option( $setting );
		}

		if ( false === $result ) {
			$results['errors'][] = sprintf( 'Failed to migrate setting %s → %s', esc_html( $setting ), esc_html( $new_key ) );
		} else {
			$results['success'][] = sprintf( 'Migrated setting %s → %s', esc_html( $setting ), esc_html( $new_key ) );
		}
	}

	return $results;
}

