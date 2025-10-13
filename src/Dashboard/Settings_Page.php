<?php

/**
 * The Settings page controller class.
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard;

use Internet_Archive\Wayback_Machine_Link_Fixer\Dashboard\Setup_Wizard;
use Internet_Archive\Wayback_Machine_Link_Fixer\Settings\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Settings Page
 */
class Settings_Page {

	public const PAGE_SLUG             = 'iawmlf_settings';
	public const SETTINGS_SECTION      = 'iawmlf-settings';
	public const GROUP_IA_SETTINGS     = 'iawmlf_ia_settings';
	public const GROUP_PLUGIN_SETTINGS = 'iawmlf_plugin_settings';
	public const GROUP_LINK_FIXER      = 'iawmlf_group';
	public const GROUP_AUTO_ARCHIVER   = 'iawmlf_auto_archiver';

	/**
	 * The pages menu hook.
	 *
	 * @since   1.0.0
	 * @var string|false
	 */
	private $menu_hook = false;

	/**
	 * Initialise the settings page.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function initialize(): void {
		add_action( 'admin_init', array( $this, 'register_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'register_page' ), 20, 0 );
		add_action( 'admin_init', array( $this, 'validate_archive_org_keys' ), 99 );
	}

	/**
	 * Get the settings page URL.
	 *
	 * @since   1.3.0
	 *
	 * @return  string
	 */
	public static function get_page_url(): string {
		return admin_url( 'admin.php?page=' . self::PAGE_SLUG );
	}

	/**
	 * Register the settings page.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function register_fields(): void {
		// Register the settings fields.
		$this->register_settings_fields();
		$this->add_settings_fields();
	}


	/**
	 * Registers the settings page.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function register_page(): void {
		$this->menu_hook = add_submenu_page(
			Dashboard_Page::DASHBOARD_SLUG,
			__( 'Wayback Link Fixer Settings', 'internet-archive-wayback-machine-link-fixer' ),
			__( 'Settings', 'internet-archive-wayback-machine-link-fixer' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueue the settings page scripts.
	 *
	 * @since   1.0.0
	 *
	 * @param   string $page_hook The page hook.
	 *
	 * @return  void
	 */
	public function enqueue_scripts( string $page_hook ): void {
		// Only enqueue the scripts on the settings page.
		if ( $this->menu_hook !== $page_hook ) {
			return;
		}

		wp_register_script(
			self::PAGE_SLUG,
			IAWMLF_URL . 'assets/js/build/admin_settings.js',
			array( 'jquery' ),
			IAWMLF_VERSION,
			true
		);

		wp_localize_script(
			self::PAGE_SLUG,
			'IawmlfSettings',
			array(
				'newExcludedTemplate' => $this->render_excluded_url( '{newUrl}', '{newIndex}' ),
			)
		);

		wp_enqueue_script( self::PAGE_SLUG );

		//  Register the styles.
		wp_enqueue_style(
			self::PAGE_SLUG,
			IAWMLF_URL . 'assets/css/build/style-style.scss.css',
			array(),
			IAWMLF_VERSION
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function render_page(): void {
		iawmlf_render_not_authenticated_notice();

		// Check if the wizard has been completed.
		$wizard_link = '';
		if ( Setup_Wizard::is_setup_complete() ) {
			$wizard_link = \sprintf(
				'<a href="%s" class="button button-primary">%s</a>',
				esc_url( Setup_Wizard::get_wizard_url() . '&rerun-wizard=1' ),
				esc_html__( 'Rerun The Setup Wizard', 'internet-archive-wayback-machine-link-fixer' )
			);
		}

		echo '<div class="wrap">';
		printf(
			'<div id="iawmlf_settings_header" class="iawmlf-settings__header">
			<h1 class="wp-heading-inline iawmlf-settings__header">%s</h1>
			%s
			</div>',
			esc_html__( 'Wayback Link Fixer - Settings', 'internet-archive-wayback-machine-link-fixer' ),
			$wizard_link // phpcs:ignore
		);

		echo '<hr class="wp-header-end"><form action="options.php" method="post">';

		do_settings_sections( self::PAGE_SLUG );
		settings_fields( self::PAGE_SLUG );

		submit_button( __( 'Save Changes', 'internet-archive-wayback-machine-link-fixer' ) );

		echo '</form></div>';
	}

	/**
	 * Registers the settings fields.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	private function register_settings_fields(): void {
		register_setting(
			self::PAGE_SLUG,
			Settings::PROCESS_LINKS,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'wp_validate_boolean',
				'default'           => false,
				'show_in_rest'      => array(
					'name'   => Settings::PROCESS_LINKS,
					'schema' => array(
						'type' => 'boolean',
					),
				),
			)
		);

		register_setting(
			self::PAGE_SLUG,
			Settings::ALLOWED_POST_TYPES,
			array(
				'type'              => 'array',
				'sanitize_callback' => fn( $value ): array => array_map( 'sanitize_text_field', (array) $value ),
				'default'           => array( 'page', 'post' ),
				'show_in_rest'      => array(
					'name'   => Settings::ALLOWED_POST_TYPES,
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'string',
						),
					),
				),
			)
		);

		register_setting(
			self::PAGE_SLUG,
			Settings::DROP_TABLES_ON_UNINSTALL_KEY,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'wp_validate_boolean',
				'default'           => false,
				'show_in_rest'      => array(
					'name'   => Settings::DROP_TABLES_ON_UNINSTALL_KEY,
					'schema' => array(
						'type' => 'boolean',
					),
				),
			)
		);

		register_setting(
			self::PAGE_SLUG,
			Settings::SCAN_EXISTING_POSTS,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'wp_validate_boolean',
				'default'           => false,
				'show_in_rest'      => array(
					'name'   => Settings::SCAN_EXISTING_POSTS,
					'schema' => array(
						'type' => 'boolean',
					),
				),
			)
		);

		register_setting(
			self::PAGE_SLUG,
			Settings::LINK_EXCLUSIONS,
			array(
				'type'              => 'array',
				'sanitize_callback' => fn( $value ): array => array_map( 'sanitize_text_field', (array) $value ),
				'default'           => array(),
				'show_in_rest'      => array(
					'name'   => Settings::LINK_EXCLUSIONS,
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'string',
						),
					),
				),
			)
		);

		register_setting(
			self::PAGE_SLUG,
			Settings::ARCHIVE_ORG_SECRET_KEY,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
				'show_in_rest'      => false,
			),
		);

		register_setting(
			self::PAGE_SLUG,
			Settings::ARCHIVE_ORG_ACCESS_KEY,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
				'show_in_rest'      => false,
			)
		);

		register_setting(
			self::PAGE_SLUG,
			Settings::FIXER_OPTION,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => Settings::FIXER_OPTION_REPLACE_LINK,
				'show_in_rest'      => false,
			)
		);

		register_setting(
			self::PAGE_SLUG,
			Settings::ALLOW_OWN_CONTENT_SUBMISSIONS,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'wp_validate_boolean',
				'default'           => false,
				'show_in_rest'      => array(
					'name'   => Settings::ALLOW_OWN_CONTENT_SUBMISSIONS,
					'schema' => array(
						'type' => 'boolean',
					),
				),
			)
		);

		register_setting(
			self::PAGE_SLUG,
			Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'wp_validate_boolean',
				'default'           => false,
				'show_in_rest'      => array(
					'name'   => Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE,
					'schema' => array(
						'type' => 'boolean',
					),
				),
			)
		);

		register_setting(
			self::PAGE_SLUG,
			Settings::ALLOWED_OWN_CONTENT_POST_TYPES,
			array(
				'type'              => 'array',
				'sanitize_callback' => fn( $value ): array => array_map( 'sanitize_text_field', (array) $value ),
				'default'           => array( 'page', 'post' ),
				'show_in_rest'      => array(
					'name'   => Settings::ALLOWED_OWN_CONTENT_POST_TYPES,
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type' => 'string',
						),
					),
				),
			)
		);

		register_setting(
			self::PAGE_SLUG,
			Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE_INTERVAL,
			array(
				'type'              => 'integer',
				'sanitize_callback' => function ( $value ) {
					$value = absint( $value );
					return 0 === $value ? 28 : $value;
				},
				'default'           => 28,
				'show_in_rest'      => array(
					'name'   => Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE_INTERVAL,
					'schema' => array(
						'type' => 'integer',
					),
				),
			)
		);
	}

	/**
	 * Add all settings fields.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	private function add_settings_fields(): void {
		add_settings_section(
			self::GROUP_PLUGIN_SETTINGS,
			__( 'Plugin Settings', 'internet-archive-wayback-machine-link-fixer' ),
			'__return_empty_string',
			self::PAGE_SLUG,
			array(
				'before_section' => '<div id="iawmlf_settings_plugin_section" class="iawmlf_settings_postbox">',
				'after_section'  => '</div>',
			)
		);

		add_settings_section(
			self::GROUP_IA_SETTINGS,
			__( 'Archive.org API', 'internet-archive-wayback-machine-link-fixer' ),
			'__return_empty_string',
			self::PAGE_SLUG,
			array(
				'before_section' => '<div id="iawmlf_settings_ia_section" class="iawmlf_settings_postbox">',
				'after_section'  => $this->render_invalid_api_keys_message() . '<p class="description">' . sprintf(
						// Translators: %s is the link to the Internet account setup.
					__( "To get your API key and secret, please visit the <a href='%s' target='_blank'>Internet Archive</a> and create a new 'S3 access key' (this is a type of credential used by Archive.org).", 'internet-archive-wayback-machine-link-fixer' ),
					esc_url( 'https://archive.org/account/s3.php' )
				) . '</p></div>',
			)
		);

		add_settings_section(
			self::GROUP_LINK_FIXER,
			__( 'Link Fixer', 'internet-archive-wayback-machine-link-fixer' ),
			function () {
				echo '<p class="description">' . esc_html__( 'Enable the Link Fixer to scan links in your selected post types. It will find or create archived versions on the Internet Archive to ensure links remain accessible if they break.', 'internet-archive-wayback-machine-link-fixer' ) . '</p>';
			},
			self::PAGE_SLUG,
			array(
				'before_section' => '<div id="iawmlf_settings_link_fixer_section" class="iawmlf_settings_postbox">',
				'after_section'  => '</div>',
			)
		);

		add_settings_section(
			self::GROUP_AUTO_ARCHIVER,
			__( 'Auto Archiver', 'internet-archive-wayback-machine-link-fixer' ),
			function () {
				echo '<p class="description">' . esc_html__( 'Keep your content securely archived with the Auto Archiver. Each time you update a post, a fresh copy is saved to the Wayback Machine. Ensure your work remains accessible and preserved over time.', 'internet-archive-wayback-machine-link-fixer' ) . '</p>';
			},
			self::PAGE_SLUG,
			array(
				'before_section' => '<div id="iawmlf_settings_auto_archiver_section" class="iawmlf_settings_postbox">',
				'after_section'  => '</div>',
			)
		);

		add_settings_field(
			Settings::PROCESS_LINKS,
			__( 'Enable Link Fixer', 'internet-archive-wayback-machine-link-fixer' ),
			array( $this, 'render_process_links_field' ),
			self::PAGE_SLUG,
			self::GROUP_LINK_FIXER
		);

		add_settings_field(
			Settings::ALLOWED_POST_TYPES,
			__( 'Post Types', 'internet-archive-wayback-machine-link-fixer' ),
			array( $this, 'render_fixer_post_types_field' ),
			self::PAGE_SLUG,
			self::GROUP_LINK_FIXER,
			array( 'class' => Settings::is_link_processing_enabled() ? 'iawmlf_toggle_setting__fixer' : 'iawmlf_toggle_setting__fixer hidden' )
		);

		add_settings_field(
			Settings::DROP_TABLES_ON_UNINSTALL_KEY,
			__( 'Wipe Data on Uninstall', 'internet-archive-wayback-machine-link-fixer' ),
			array( $this, 'render_drop_tables_on_uninstall_field' ),
			self::PAGE_SLUG,
			self::GROUP_PLUGIN_SETTINGS
		);

		add_settings_field(
			Settings::SCAN_EXISTING_POSTS,
			__( 'Existing Posts', 'internet-archive-wayback-machine-link-fixer' ),
			array( $this, 'render_check_existing_posts' ),
			self::PAGE_SLUG,
			self::GROUP_LINK_FIXER,
			array( 'class' => Settings::is_link_processing_enabled() ? 'iawmlf_toggle_setting__fixer' : 'iawmlf_toggle_setting__fixer hidden' )
		);

		add_settings_field(
			Settings::FIXER_OPTION,
			__( 'Fixer Option', 'internet-archive-wayback-machine-link-fixer' ),
			array( $this, 'render_fixer_option' ),
			self::PAGE_SLUG,
			self::GROUP_LINK_FIXER,
			array( 'class' => Settings::is_link_processing_enabled() ? 'iawmlf_toggle_setting__fixer' : 'iawmlf_toggle_setting__fixer hidden' )
		);

		add_settings_field(
			Settings::LINK_EXCLUSIONS,
			__( 'Link Exclusions', 'internet-archive-wayback-machine-link-fixer' ),
			array( $this, 'render_link_exclusions_field' ),
			self::PAGE_SLUG,
			self::GROUP_LINK_FIXER,
			array( 'class' => Settings::is_link_processing_enabled() ? 'iawmlf_toggle_setting__fixer' : 'iawmlf_toggle_setting__fixer hidden' )
		);

		add_settings_field(
			Settings::ARCHIVE_ORG_ACCESS_KEY,
			__( 'Archive.org Access Key', 'internet-archive-wayback-machine-link-fixer' ),
			array( $this, 'render_archive_api_access_key' ),
			self::PAGE_SLUG,
			self::GROUP_IA_SETTINGS,
			array(
				'class' => Settings::has_valid_archive_api_credentials() ? '' : 'iawmlf_toggle_setting__invalid_api_keys',
			)
		);

		add_settings_field(
			Settings::ARCHIVE_ORG_SECRET_KEY,
			__( 'Archive.org Secret Key', 'internet-archive-wayback-machine-link-fixer' ),
			array( $this, 'render_archive_api_secret_key' ),
			self::PAGE_SLUG,
			self::GROUP_IA_SETTINGS,
			array(
				'class' => Settings::has_valid_archive_api_credentials() ? '' : 'iawmlf_toggle_setting__invalid_api_keys',
			)
		);

		add_settings_field(
			Settings::ALLOW_OWN_CONTENT_SUBMISSIONS,
			__( 'Auto Archive Posts', 'internet-archive-wayback-machine-link-fixer' ),
			array( $this, 'render_allow_own_posts' ),
			self::PAGE_SLUG,
			self::GROUP_AUTO_ARCHIVER
		);

		add_settings_field(
			Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE,
			__( 'Routinely Archive', 'internet-archive-wayback-machine-link-fixer' ),
			array( $this, 'render_own_link_routinely_update' ),
			self::PAGE_SLUG,
			self::GROUP_AUTO_ARCHIVER,
			array( 'class' => Settings::add_own_links() ? 'iawmlf_toggle_setting__auto_archiver' : 'iawmlf_toggle_setting__auto_archiver hidden' )
		);

		add_settings_field(
			Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE_INTERVAL,
			__( 'Routine Interval', 'internet-archive-wayback-machine-link-fixer' ),
			array( $this, 'render_own_link_routinely_update_interval' ),
			self::PAGE_SLUG,
			self::GROUP_AUTO_ARCHIVER,
			array( 'class' => Settings::add_own_links() ? 'iawmlf_toggle_setting__auto_archiver' : 'iawmlf_toggle_setting__auto_archiver hidden' )
		);

		add_settings_field(
			Settings::ALLOWED_OWN_CONTENT_POST_TYPES,
			__( 'Allowed Post Types', 'internet-archive-wayback-machine-link-fixer' ),
			array( $this, 'render_archiver_post_types_field' ),
			self::PAGE_SLUG,
			self::GROUP_AUTO_ARCHIVER,
			array( 'class' => Settings::add_own_links() ? 'iawmlf_toggle_setting__auto_archiver' : 'iawmlf_toggle_setting__auto_archiver hidden' )
		);
	}

	/**
	 * Renders the invalid API Keys message.
	 *
	 * @since 1.3.0
	 *
	 * @return string
	 */
	public function render_invalid_api_keys_message(): string {
		if ( ! Settings::has_valid_archive_api_credentials() ) {
			return '<div id="invalid_api_creds"><p class="description">' . esc_html__( 'The Archive.org API keys are invalid. Please check your settings.', 'internet-archive-wayback-machine-link-fixer' ) . '</p></div>';
		}

		return '';
	}

	/**
	 * Validate the Archive.org keys.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function validate_archive_org_keys(): void {
		// If we are on this page.
		if ( ! isset( $_GET['page'] ) || self::PAGE_SLUG !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, we are only reading, not using
			return;
		}

		// If the settings were updated.
		if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, we are only reading, not using
			$key        = Settings::get_archive_secret_key();
			$access_key = Settings::get_archive_access_key();

			$system_client = iawmlf_get_system_client();
			$is_valid      = $system_client->is_valid_user( $access_key, $key );

			Settings::update_archive_api_credentials_validity( $is_valid );
		}
	}

	/**
	 * Render the process links field.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function render_process_links_field(): void {
		?>
		<label for="<?php echo esc_attr( Settings::PROCESS_LINKS ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( Settings::PROCESS_LINKS ); ?>"
				name="<?php echo esc_attr( Settings::PROCESS_LINKS ); ?>"
				value="1"
				<?php checked( Settings::is_link_processing_enabled() ); ?>
			/>
			<?php esc_html_e( 'Enable the Link Fixer to scan links in your selected post types. It will find or create archived versions on the Internet Archive to ensure links remain accessible if they break.', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</label>
		<?php
	}

	/**
	 * Render the post types field to select which post types to check.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function render_fixer_post_types_field(): void {
		echo '<div class="iawmlf_settings_post_types">';
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $post_type ) {
			if ( 'attachment' === $post_type->name ) {
				continue;
			}
			?>
			<label for="<?php echo esc_attr( Settings::ALLOWED_POST_TYPES ); ?>_<?php echo esc_attr( $post_type->name ); ?>">
				<input
					type="checkbox"
					id="<?php echo esc_attr( Settings::ALLOWED_POST_TYPES ); ?>_<?php echo esc_attr( $post_type->name ); ?>"
					name="<?php echo esc_attr( Settings::ALLOWED_POST_TYPES ); ?>[]"
					value="<?php echo esc_attr( $post_type->name ); ?>"
					data-group="link_fixer"
					<?php checked( in_array( $post_type->name, Settings::get_allowed_post_types(), true ) ); ?>
				/>
				<?php echo esc_html( $post_type->label ); ?>
			</label>
			<?php
		}
		echo '</div><p class="description">' . esc_html__( 'Please choose which post types will have their content checked and links added to the Wayback Machine.', 'internet-archive-wayback-machine-link-fixer' ) . '</p>';
	}

	/**
	 * Renders the post type field to select which post types can be auto archived.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function render_archiver_post_types_field(): void {
		echo '<div class="iawmlf_settings_post_types">';
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $post_type ) {
			if ( 'attachment' === $post_type->name ) {
				continue;
			}
			?>
			<label for="<?php echo esc_attr( Settings::ALLOWED_OWN_CONTENT_POST_TYPES ); ?>_<?php echo esc_attr( $post_type->name ); ?>">
				<input
					type="checkbox"
					id="<?php echo esc_attr( Settings::ALLOWED_OWN_CONTENT_POST_TYPES ); ?>_<?php echo esc_attr( $post_type->name ); ?>"
					name="<?php echo esc_attr( Settings::ALLOWED_OWN_CONTENT_POST_TYPES ); ?>[]"
					value="<?php echo esc_attr( $post_type->name ); ?>"
					data-group="auto_archiver"
					<?php checked( in_array( $post_type->name, Settings::own_link_allowed_post_types(), true ) ); ?>
				/>
				<?php echo esc_html( $post_type->label ); ?>
			</label>
			<?php
		}
		echo '</div><p class="description">' . esc_html__( 'Please choose which post types will be automatically archived to the Wayback Machine when they are published.', 'internet-archive-wayback-machine-link-fixer' ) . '</p>';
	}

	/**
	 * Render the drop tables on uninstall field.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function render_drop_tables_on_uninstall_field(): void {
		?>
		<label for="<?php echo esc_attr( Settings::DROP_TABLES_ON_UNINSTALL_KEY ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( Settings::DROP_TABLES_ON_UNINSTALL_KEY ); ?>"
				name="<?php echo esc_attr( Settings::DROP_TABLES_ON_UNINSTALL_KEY ); ?>"
				value="1"
				<?php checked( Settings::drop_tables_on_uninstall() ); ?>
			/><?php esc_html_e( 'If checked, this will remove all local data when the plugin is uninstalled. Leave unchecked if you plan to reinstall this plugin.', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</label>
		<?php
	}

	/**
	 * Renders the field for checking existing posts.
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function render_check_existing_posts(): void {
		?>
		<label for="<?php echo esc_attr( Settings::SCAN_EXISTING_POSTS ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( Settings::SCAN_EXISTING_POSTS ); ?>"
				name="<?php echo esc_attr( Settings::SCAN_EXISTING_POSTS ); ?>"
				value="1"
				data-group="link_fixer"
				<?php checked( Settings::should_scan_existing_posts() ); ?>
			/>
			<?php esc_html_e( 'When enabled, all posts of the allowed types will be scanned, and their links will be archived in the Wayback Machine.', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'This runs in the background and may take hours or days, depending on post and link count.', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the link exclusions field.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function render_link_exclusions_field(): void {
		$urls = Settings::get_link_exclusions();

		echo wp_kses(
			sprintf(
				'<div><p>%s</p></div>',
				__( "Enter a list of URLs (or parts of URLs) to exclude from link processing. You can use an asterisk (<code>*</code>) as a wildcard. For example, <code>https://example.com/some-path/*</code> would exclude anything after '/some-path/', and <code>*twitter.com*</code> would exclude any URL containing 'twitter.com'.", 'internet-archive-wayback-machine-link-fixer' )
			),
			array(
				'code' => array(),
				'p'    => array(),
				'div'  => array(),
			)
		);

		?>
		<div id="iawmlf_excluded_links">
			<div class="new-link">
				<input
					type="text"
					id="iawmlf_excluded_links_new"
					placeholder="<?php esc_html_e( 'Add a new exclusion (https://x.com*)', 'internet-archive-wayback-machine-link-fixer' ); ?>"
					data-group="link_fixer"
				/>
				<button id="iawmlf_excluded_links_new_action" data-group="link_fixer" type="button" class="button button-secondary add-exclusion"><?php esc_html_e( 'Add', 'internet-archive-wayback-machine-link-fixer' ); ?></button>
			</div>

			<div id="iawmlf_excluded_empty" style="display: <?php echo empty( $urls ) ? 'block' : 'none'; ?>;">
				<p>
					<?php esc_html_e( 'No exclusions found.', 'internet-archive-wayback-machine-link-fixer' ); ?>
				</p>
			</div>
				<?php
				foreach ( $urls as $index => $url ) {
					echo wp_kses(
						$this->render_excluded_url( $url, (string) $index ),
						array(
							'input'  => array(
								'type'       => array(),
								'id'         => array(),
								'name'       => array(),
								'value'      => array(),
								'data-link'  => array(),
								'data-index' => array(),
								'data-group' => array(),
							),
							'button' => array(
								'type'       => array(),
								'class'      => array(),
								'data-group' => array(),
							),
							'div'    => array(
								'class' => array(),
							),
						)
					);
				}
				?>
		</div>

		<?php
	}

	/**
	 * Renders a row for excluded urls.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url   The URL to render.
	 * @param string $index The index of the URL.
	 *
	 * @return string
	 */
	private function render_excluded_url( string $url, string $index ): string {
		return sprintf(
			'<div class="link">
				<input
					type="text"
					id="%s_%s"
					name="%s[]"
					value="%s"
					data-link="%s"
					data-index="%s"
					data-group="link_fixer"
				/>
				<button data-group="link_fixer" type="button" class="button button-secondary remove-exclusion">%s</button>
			</div>',
			esc_attr( Settings::LINK_EXCLUSIONS ),
			esc_attr( $index ),
			esc_attr( Settings::LINK_EXCLUSIONS ),
			esc_attr( $url ),
			esc_attr( $url ),
			esc_attr( $index ),
			esc_html__( 'Remove', 'internet-archive-wayback-machine-link-fixer' )
		);
	}

	/**
	 * Render the archive api key field.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function render_archive_api_secret_key(): void {
		?>
		<input
			type="password"
			id="<?php echo esc_attr( Settings::ARCHIVE_ORG_SECRET_KEY ); ?>"
			name="<?php echo esc_attr( Settings::ARCHIVE_ORG_SECRET_KEY ); ?>"
			value="<?php echo esc_attr( Settings::get_archive_secret_key() ); ?>"
			style="width:80%;"
		/>
		<p class="description">
			<?php esc_html_e( 'Archive.org Secret Key', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the archive api secret field.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function render_archive_api_access_key(): void {
		?>
		<input
			type="password"
			id="<?php echo esc_attr( Settings::ARCHIVE_ORG_ACCESS_KEY ); ?>"
			name="<?php echo esc_attr( Settings::ARCHIVE_ORG_ACCESS_KEY ); ?>"
			value="<?php echo esc_attr( Settings::get_archive_access_key() ); ?>"
			style="width:80%;"
		/>
		<p class="description">
			<?php esc_html_e( 'Archive.org Access Key', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the fixer option field.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function render_fixer_option(): void {
		?>
		<select
			id="<?php echo esc_attr( Settings::FIXER_OPTION ); ?>"
			name="<?php echo esc_attr( Settings::FIXER_OPTION ); ?>"
			data-group="link_fixer"
		>
			<option value="<?php echo esc_attr( Settings::FIXER_OPTION_REPLACE_LINK ); ?>" <?php selected( Settings::get_fixer_option(), Settings::FIXER_OPTION_REPLACE_LINK ); ?>>
				<?php esc_html_e( 'Replace Link (No Notification)', 'internet-archive-wayback-machine-link-fixer' ); ?>
			</option>
			<option value="<?php echo esc_attr( Settings::FIXER_OPTION_DO_NOTHING ); ?>" <?php selected( Settings::get_fixer_option(), Settings::FIXER_OPTION_DO_NOTHING ); ?>>
				<?php esc_html_e( 'Do Nothing', 'internet-archive-wayback-machine-link-fixer' ); ?>
			</option>
		</select>
		<p class="description">
			<?php esc_html_e( 'Choose how to handle broken links.', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</p>
		<?php
	}

	/**
	 * Renders the field which is used to set allowing own posts to be added to the wayback machine.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function render_allow_own_posts(): void {
		?>
		<label for="<?php echo esc_attr( Settings::ALLOW_OWN_CONTENT_SUBMISSIONS ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( Settings::ALLOW_OWN_CONTENT_SUBMISSIONS ); ?>"
				name="<?php echo esc_attr( Settings::ALLOW_OWN_CONTENT_SUBMISSIONS ); ?>"
				value="1"
				<?php checked( Settings::add_own_links() ); ?>
			/>
			<?php esc_html_e( 'When active, your own content will be automatically archived on the Wayback Machine each time you publish or update it.', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</label>
		<?php
	}

	/**
	 * Renders the field which is used to allow own posts to be upated routinely.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function render_own_link_routinely_update(): void {
		?>
		<label for="<?php echo esc_attr( Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE ); ?>">
			<input
				type="checkbox"
				id="<?php echo esc_attr( Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE ); ?>"
				name="<?php echo esc_attr( Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE ); ?>"
				value="1"
				data-group="auto_archiver"
				<?php checked( Settings::own_link_routinely_update() ); ?>
			/>
			<?php esc_html_e( 'Regularly archive your posts on the Wayback Machine', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</label>
		<?php
	}

	/**
	 * Renders the field which is used to set the interval between updates.
	 *
	 * @since 1.3.0
	 *
	 * @return void
	 */
	public function render_own_link_routinely_update_interval(): void {
		$interval = Settings::own_link_routine_update_interval();
		?>
		<input
			type="number"
			id="<?php echo esc_attr( Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE_INTERVAL ); ?>"
			name="<?php echo esc_attr( Settings::ROUTINELY_UPDATE_WAYBACK_MACHINE_INTERVAL ); ?>"
			value="<?php echo esc_attr( $interval ); ?>"
			min="1"
			step="1"
			data-group="auto_archiver"
		/>
		<p class="description">
			<?php esc_html_e( 'Interval in days for regular archiving.', 'internet-archive-wayback-machine-link-fixer' ); ?>
		</p>
		<?php
	}
}

