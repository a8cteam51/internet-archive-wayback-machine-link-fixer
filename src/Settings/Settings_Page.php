<?php

/**
 * The Settings acpage
 *
 * @since      1.0.0
 * @version    1.0.0
 */

namespace WPCOMSpecialProjects\Wayback_Link_Fixer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Settings Page
 */
class Settings_Page {

	public const PAGE_SLUG        = 'wpcomsp-wayback-link-fixer';
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
		add_action( 'admin_menu', array( $this, 'register_page' ) );
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
		$this->menu_hook = add_options_page(
			__( 'Wayback Link Fixer', 'wpcomsp_wayback_link_fixer' ),
			__( 'Wayback Link Fixer', 'wpcomsp_wayback_link_fixer' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
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

		echo '<form action="options.php" method="post">';

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
}
