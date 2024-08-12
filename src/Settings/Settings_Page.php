<?php

/**
 * The Settings acpage
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Settings;

use WPCOMSpecialProjects\Wayback_Link_Fixer\Report\Viewer\Report_Viewer_Page;

defined( 'ABSPATH' ) || exit;

/**
 * Settings Page
 */
class Settings_Page {

	public const PAGE_SLUG        = 'wpcomsp_wayback_link_fixer_settings';
	public const SETTINGS_SECTION = 'wpcomsp-wayback-link-fixer-settings';

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
		$this->menu_hook = \add_submenu_page(
			'options-general.php',
			__( 'Wayback Link Fixer', 'wpcomsp_wayback_link_fixer' ),
			__( 'Link Fixer Settings', 'wpcomsp_wayback_link_fixer' ),
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

		// Register select2
		wpcomsp_wayback_link_fixer_enqueue_select2_assets( array( self::PAGE_SLUG ) );

		\wp_register_script(
			self::PAGE_SLUG,
			WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/js/build/admin_settings.js',
			array( 'jquery' ),
			WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version'],
			true
		);

		\wp_localize_script(
			self::PAGE_SLUG,
			'WlfSettings',
			array(
				'newExcludedTemplate' => $this->render_excluded_url( '{newUrl}', '{newIndex}' ),
			)
		);

		\wp_enqueue_script( self::PAGE_SLUG );

		//  Register the styles.
		wp_enqueue_style(
			self::PAGE_SLUG,
			WPCOMSP_WAYBACK_LINK_FIXER_URL . 'assets/css/build/style-style.scss.css',
			array(),
			WPCOMSP_WAYBACK_LINK_FIXER_METADATA['Version']
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
		wpcomsp_wayback_link_fixer_render_not_authenticated_notice();
		echo '<form action="options.php" method="post">';

		\do_settings_sections( self::PAGE_SLUG );
		\settings_fields( self::PAGE_SLUG );

		echo wp_kses(
			sprintf(
				// Translators: %s is the link to the Internet account setup.
				__( "To get your API key and secret, please visit the <a href='%s' target='_blank'>Internet Archive</a> and create a new S3 access key.", 'wpcomsp_wayback_link_fixer' ),
				esc_url( 'https://archive.org/account/s3.php' )
			),
			array(
				'a' => array(
					'href'   => array(),
					'target' => array(),
				),
			)
		);

		\submit_button( __( 'Save Changes', 'wpcomsp_wayback_link_fixer' ) );

		echo '</form>';
	}

	/**
	 * Registers the settings fields.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	private function register_settings_fields(): void {
		\register_setting(
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

		\register_setting(
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

		\register_setting(
			self::PAGE_SLUG,
			Settings::SCAN_EXISTING_POSTS,
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'wp_validate_boolean',
				'default'           => true,
				'show_in_rest'      => array(
					'name'   => Settings::SCAN_EXISTING_POSTS,
					'schema' => array(
						'type' => 'boolean',
					),
				),
			)
		);

		\register_setting(
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

		\register_setting(
			self::PAGE_SLUG,
			Settings::ARCHIVE_ORG_SECRET_KEY,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
				'show_in_rest'      => false,
			),
		);

		\register_setting(
			self::PAGE_SLUG,
			Settings::ARCHIVE_ORG_ACCESS_KEY,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
				'show_in_rest'      => false,
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
		\add_settings_section(
			self::SETTINGS_SECTION,
			__( 'Wayback Link Fixer :: Settings', 'wpcomsp_wayback_link_fixer' ),
			'__return_empty_string',
			self::PAGE_SLUG
		);

		\add_settings_field(
			Settings::ALLOWED_POST_TYPES,
			__( 'Post Types', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_post_types_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION
		);

		\add_settings_field(
			Settings::DROP_TABLES_ON_UNINSTALL_KEY,
			__( 'Drop Tables on Uninstall', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_drop_tables_on_uninstall_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION
		);

		\add_settings_field(
			Settings::SCAN_EXISTING_POSTS,
			__( 'Should existing posts be checked', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_check_existing_posts' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION
		);

		\add_settings_field(
			Settings::LINK_EXCLUSIONS,
			__( 'Link Exclusions', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_link_exclusions_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION
		);

		\add_settings_field(
			Settings::ARCHIVE_ORG_SECRET_KEY,
			__( 'Archive.org Secret Key', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_archive_api_secret_key' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION
		);

		\add_settings_field(
			Settings::ARCHIVE_ORG_ACCESS_KEY,
			__( 'Archive.org Access Key', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_archive_api_access_key' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION
		);
	}

	/**
	 * Render the post types field.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function render_post_types_field(): void {
		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $post_type ) {
			?>
			<label for="<?php echo esc_attr( Settings::ALLOWED_POST_TYPES ); ?>_<?php echo esc_attr( $post_type->name ); ?>">
				<input
					type="checkbox"
					id="<?php echo esc_attr( Settings::ALLOWED_POST_TYPES ); ?>_<?php echo esc_attr( $post_type->name ); ?>"
					name="<?php echo esc_attr( Settings::ALLOWED_POST_TYPES ); ?>[]"
					value="<?php echo esc_attr( $post_type->name ); ?>"
					<?php checked( in_array( $post_type->name, Settings::get_allowed_post_types(), true ) ); ?>
				/>
				<?php echo esc_html( $post_type->label ); ?>
			</label>
			<br />
			<?php
		}
		echo '<p class="description">' . esc_html__( 'Which post type should be checked?', 'wpcomsp_wayback_link_fixer' ) . '</p>';
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
			/>
			<?php esc_html_e( 'Drop tables on uninstall', 'wpcomsp_wayback_link_fixer' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'If checked, the plugin will drop the tables on uninstall.', 'wpcomsp_wayback_link_fixer' ); ?>
		</p>
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
				<?php checked( Settings::should_scan_existing_posts() ); ?>
			/>
			<?php esc_html_e( 'Scan existing posts', 'wpcomsp_wayback_link_fixer' ); ?>
		</label>
		<p class="description">
			<?php esc_html_e( 'If checked, the plugin will scan existing posts for broken links.', 'wpcomsp_wayback_link_fixer' ); ?>
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

		echo \wp_kses(
			sprintf(
				'<div><p>%s</p></div>',
				__( 'Enter a list of URLs to exclude from the link checker. These can be added using <code>*</code> wildcards such as <code>*.twitter.com*</code> to exclude any twitter link', 'wpcomsp_wayback_link_fixer' )
			),
			array(
				'code' => array(),
				'p'    => array(),
				'div'  => array(),
			)
		);

		?>
		<div id="wlf_excluded_links">
			<div class="new-link">
				<input
					type="text"
					id="wlf_excluded_links_new"
					placeholder="<?php esc_html_e( 'Add a new exclusion (*.twitter.*)', 'wpcomsp_wayback_link_fixer' ); ?>"
				/>
				<button id="wlf_excluded_links_new_action" type="button" class="button button-secondary add-exclusion"><?php esc_html_e( 'Add', 'wpcomsp_wayback_link_fixer' ); ?></button>
			</div>
			<?php
			// Show the no link message if there are no links.
			if ( empty( $urls ) ) {
				?>
			<div id="wlf_excluded_empty">
				<p>
					<?php esc_html_e( 'No exclusions found.', 'wpcomsp_wayback_link_fixer' ); ?>
				</p>
			</div>
				<?php
			}
			foreach ( $urls as $index => $url ) {
				echo wp_kses(
					$this->render_excluded_url( $url, $index ),
					array(
						'input'  => array(
							'type'       => array(),
							'id'         => array(),
							'name'       => array(),
							'value'      => array(),
							'data-link'  => array(),
							'data-index' => array(),
						),
						'button' => array(
							'type'  => array(),
							'class' => array(),
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
				/>
				<button type="button" class="button button-secondary remove-exclusion">%s</button>
			</div>',
			esc_attr( Settings::LINK_EXCLUSIONS ),
			esc_attr( $index ),
			esc_attr( Settings::LINK_EXCLUSIONS ),
			esc_attr( $url ),
			esc_attr( $url ),
			esc_attr( $index ),
			esc_html__( 'Remove', 'wpcomsp_wayback_link_fixer' )
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
			value="<?php echo esc_attr( Settings::get_archive_api_key() ); ?>"
			style="width:80%;"
		/>
		<p class="description">
			<?php esc_html_e( 'Archive.org S3 Secret Key', 'wpcomsp_wayback_link_fixer' ); ?>
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
			<?php esc_html_e( 'Archive.org S3 Access Key', 'wpcomsp_wayback_link_fixer' ); ?>
		</p>
		<?php
	}
}

