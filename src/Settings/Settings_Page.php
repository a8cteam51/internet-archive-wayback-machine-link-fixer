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

		// Enable network support for pages.
		if ( \is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'register_page' ) );
			add_action( 'network_admin_edit_' . self::PAGE_SLUG, array( $this, 'update_multisite_settings' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'register_page' ), 20, 0 );
		}
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
	 * Handle saving settings for multisite.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function update_multisite_settings(): void {
		// If the multiste settings nonce is not set, show an error.
		if ( ! isset( $_POST['_multisite_settings_nonce'] ) ) {
			wp_die( esc_html__( 'Invalid request.', 'wpcomsp_wayback_link_fixer' ) );
		}

		// Verify the nonce.
		if ( ! wp_verify_nonce( $_POST['_multisite_settings_nonce'], self::PAGE_SLUG ) ) {
			wp_die( esc_html__( 'Invalid request.', 'wpcomsp_wayback_link_fixer' ) );
		}

		// Options
		$options = array_filter( $GLOBALS['wp_registered_settings'], fn( $setting ) => 0 === strpos( $setting, 't51_wlf_' ), ARRAY_FILTER_USE_KEY );

		// Iterate over the options and update them.
		foreach ( $options as $name => $option ) {
			$value = isset( $_POST[ $name ] ) ? $_POST[ $name ] : $option['default'];

			// Sanitize the option.
			$value = $option['sanitize_callback']( $value );
			// Update the option.
			update_site_option( $name, $value );
		}

		// Redirect back to the network settings page.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SLUG,
					'updated' => 'true',
				),
				network_admin_url( 'admin.php' )
			)
		);
		exit;
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
			Report_Viewer_Page::PAGE_SLUG,
			__( 'Wayback Link Fixer', 'wpcomsp_wayback_link_fixer' ),
			__( 'Settings', 'wpcomsp_wayback_link_fixer' ),
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
		if ( \is_multisite() ) {
			// If updated, show notice.
			if ( isset( $_GET['updated'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings updated.', 'wpcomsp_wayback_link_fixer' ) . '</p></div>';
			}

			echo '<form action="edit.php?action=' . self::PAGE_SLUG . '" method="post">';
			wp_nonce_field( self::PAGE_SLUG, '_multisite_settings_nonce', false, true );
		} else {
			echo '<form action="options.php" method="post">';
		}

		\do_settings_sections( self::PAGE_SLUG );
		\settings_fields( self::PAGE_SLUG );

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
			Settings::POST_TYPES_OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => fn( $value ): array => array_map( 'sanitize_text_field', (array) $value ),
				'default'           => array( 'page', 'post' ),
				'show_in_rest'      => array(
					'name'   => Settings::POST_TYPES_OPTION_KEY,
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
			Settings::LINK_CHECKER_TIMEOUT,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 1000,
				'show_in_rest'      => array(
					'name'   => Settings::LINK_CHECKER_TIMEOUT,
					'schema' => array(
						'type' => 'integer',
					),
				),
			)
		);

		\register_setting(
			self::PAGE_SLUG,
			Settings::HTTP_STATUS_CODES,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '404,410,500,502,300,301,303',
				'show_in_rest'      => array(
					'name'   => Settings::HTTP_STATUS_CODES,
					'schema' => array(
						'type' => 'string',
					),
				),
			)
		);

		\register_setting(
			self::PAGE_SLUG,
			Settings::LINK_CACHE_EXPIRATION,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => DAY_IN_SECONDS,
				'show_in_rest'      => array(
					'name'   => Settings::LINK_CACHE_EXPIRATION,
					'schema' => array(
						'type' => 'integer',
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
			Settings::EVENT_POSTS_PER_BATCH,
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 10,
				'show_in_rest'      => array(
					'name'   => Settings::EVENT_POSTS_PER_BATCH,
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
		\add_settings_section(
			self::SETTINGS_SECTION,
			__( 'Wayback Link Fixer :: Settings', 'wpcomsp_wayback_link_fixer' ),
			'__return_empty_string',
			self::PAGE_SLUG
		);

		\add_settings_field(
			Settings::POST_TYPES_OPTION_KEY,
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
			Settings::LINK_CHECKER_TIMEOUT,
			__( 'Link Checker Timeout in MS', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_link_checker_timeout_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION
		);

		\add_settings_field(
			Settings::HTTP_STATUS_CODES,
			__( 'HTTP Status Codes', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_http_status_codes_field' ),
			self::PAGE_SLUG,
			self::SETTINGS_SECTION
		);

		\add_settings_field(
			Settings::LINK_CACHE_EXPIRATION,
			__( 'Link Cache Expiration in Seconds', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_link_cache_expiration_field' ),
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
			Settings::EVENT_POSTS_PER_BATCH,
			__( 'Posts per Batch', 'wpcomsp_wayback_link_fixer' ),
			array( $this, 'render_event_posts_per_batch_field' ),
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
			<label for="<?php echo esc_attr( Settings::POST_TYPES_OPTION_KEY ); ?>_<?php echo esc_attr( $post_type->name ); ?>">
				<input
					type="checkbox"
					id="<?php echo esc_attr( Settings::POST_TYPES_OPTION_KEY ); ?>_<?php echo esc_attr( $post_type->name ); ?>"
					name="<?php echo esc_attr( Settings::POST_TYPES_OPTION_KEY ); ?>[]"
					value="<?php echo esc_attr( $post_type->name ); ?>"
					<?php checked( in_array( $post_type->name, Settings::get_post_types(), true ) ); ?>
				/>
				<?php echo esc_html( $post_type->label ); ?>
			</label>
			<br />
			<?php
		}
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
		<?php
	}

	/**
	 * Render the link checker timeout field.
	 *
	 * @since   1.0.0
	 *-+
	 * @return  void
	 */
	public function render_link_checker_timeout_field(): void {
		?>
		<input
			type="number"
			id="<?php echo esc_attr( Settings::LINK_CHECKER_TIMEOUT ); ?>"
			name="<?php echo esc_attr( Settings::LINK_CHECKER_TIMEOUT ); ?>"
			value="<?php echo absint( Settings::get_link_checker_timeout() ); ?>"
			min="0"
			max="5000"
		/>
		<?php
	}

	/**
	 * Render the http status codes field.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function render_http_status_codes_field(): void {
		?>
		<input
			type="text"
			id="<?php echo esc_attr( Settings::HTTP_STATUS_CODES ); ?>"
			name="<?php echo esc_attr( Settings::HTTP_STATUS_CODES ); ?>"
			value="<?php echo esc_attr( Settings::get_http_status_codes() ); ?>"
		/>
		<?php
	}

	/**
	 * Render the link cache expiration field.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function render_link_cache_expiration_field(): void {
		?>
		<input
			type="number"
			id="<?php echo esc_attr( Settings::LINK_CACHE_EXPIRATION ); ?>"
			name="<?php echo esc_attr( Settings::LINK_CACHE_EXPIRATION ); ?>"
			value="<?php echo absint( Settings::get_link_cache_expiration() ); ?>"
			min="0"
		/>
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
	 * Render the event posts per batch field.
	 *
	 * @since   1.0.0
	 *
	 * @return  void
	 */
	public function render_event_posts_per_batch_field(): void {
		?>
		<input
			type="number"
			id="<?php echo esc_attr( Settings::EVENT_POSTS_PER_BATCH ); ?>"
			name="<?php echo esc_attr( Settings::EVENT_POSTS_PER_BATCH ); ?>"
			value="<?php echo absint( Settings::get_posts_per_batch() ); ?>"
			min="0"
		/>
		<?php
	}
}
