<?php
/**
 * Settings class for ACF Repeater.
 *
 * @package ACF_Repeater\Admin
 */

namespace ACF_Repeater\Admin;

/**
 * Class Settings
 */
class Settings {

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'acf_repeater_settings';

	/**
	 * Default settings.
	 *
	 * @var array<string, mixed>
	 */
	private array $defaults = array(
		'default_layout'         => 'table',
		'default_button_label'   => 'Add Row',
		'default_collapsed'      => '',
		'default_sortable'       => true,
		'default_duplicate'      => true,
		'default_delete_confirm' => true,
		'default_min_rows'       => 0,
		'default_max_rows'       => 0,
		'rest_api_enabled'       => true,
		'json_sync_enabled'      => true,
		'json_save_path'         => '',
		'json_load_paths'        => array(),
	);

	/**
	 * Current settings.
	 *
	 * @var array<string, mixed>
	 */
	private array $settings = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings = $this->get_settings();
	}

	/**
	 * Get all settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_settings(): array {
		$saved = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( $saved, $this->defaults );
	}

	/**
	 * Get a specific setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_setting( string $key, mixed $default = null ): mixed {
		$settings = $this->get_settings();
		return $settings[ $key ] ?? $default;
	}

	/**
	 * Update a setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool
	 */
	public function update_setting( string $key, mixed $value ): bool {
		$settings         = $this->get_settings();
		$settings[ $key ] = $value;
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Update multiple settings.
	 *
	 * @param array<string, mixed> $settings Settings to update.
	 * @return bool
	 */
	public function update_settings( array $settings ): bool {
		$current = $this->get_settings();
		$merged  = array_merge( $current, $settings );
		return update_option( self::OPTION_NAME, $merged );
	}

	/**
	 * Reset settings to defaults.
	 *
	 * @return bool
	 */
	public function reset_settings(): bool {
		return update_option( self::OPTION_NAME, $this->defaults );
	}

	/**
	 * Add admin menu page.
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'edit.php?post_type=acf-field-group',
			__( 'Repeater Settings', 'acf-repeater' ),
			__( 'Repeater Settings', 'acf-repeater' ),
			'manage_options',
			'acf-repeater-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'acf_repeater_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->defaults,
			)
		);

		// General settings section.
		add_settings_section(
			'acf_repeater_general',
			__( 'General Settings', 'acf-repeater' ),
			array( $this, 'render_general_section' ),
			'acf_repeater_settings'
		);

		// Default layout.
		add_settings_field(
			'default_layout',
			__( 'Default Layout', 'acf-repeater' ),
			array( $this, 'render_default_layout_field' ),
			'acf_repeater_settings',
			'acf_repeater_general'
		);

		// Default button label.
		add_settings_field(
			'default_button_label',
			__( 'Default "Add Row" Button Label', 'acf-repeater' ),
			array( $this, 'render_default_button_label_field' ),
			'acf_repeater_settings',
			'acf_repeater_general'
		);

		// Default collapsed.
		add_settings_field(
			'default_collapsed',
			__( 'Default Collapsed Field', 'acf-repeater' ),
			array( $this, 'render_default_collapsed_field' ),
			'acf_repeater_settings',
			'acf_repeater_general'
		);

		// Default sortable.
		add_settings_field(
			'default_sortable',
			__( 'Enable Row Sorting by Default', 'acf-repeater' ),
			array( $this, 'render_default_sortable_field' ),
			'acf_repeater_settings',
			'acf_repeater_general'
		);

		// Default duplicate.
		add_settings_field(
			'default_duplicate',
			__( 'Enable Row Duplication by Default', 'acf-repeater' ),
			array( $this, 'render_default_duplicate_field' ),
			'acf_repeater_settings',
			'acf_repeater_general'
		);

		// Default delete confirmation.
		add_settings_field(
			'default_delete_confirm',
			__( 'Show Delete Confirmation by Default', 'acf-repeater' ),
			array( $this, 'render_default_delete_confirm_field' ),
			'acf_repeater_settings',
			'acf_repeater_general'
		);

		// Default min rows.
		add_settings_field(
			'default_min_rows',
			__( 'Default Minimum Rows', 'acf-repeater' ),
			array( $this, 'render_default_min_rows_field' ),
			'acf_repeater_settings',
			'acf_repeater_general'
		);

		// Default max rows.
		add_settings_field(
			'default_max_rows',
			__( 'Default Maximum Rows (0 = unlimited)', 'acf-repeater' ),
			array( $this, 'render_default_max_rows_field' ),
			'acf_repeater_settings',
			'acf_repeater_general'
		);

		// REST API section.
		add_settings_section(
			'acf_repeater_rest_api',
			__( 'REST API', 'acf-repeater' ),
			array( $this, 'render_rest_api_section' ),
			'acf_repeater_settings'
		);

		add_settings_field(
			'rest_api_enabled',
			__( 'Enable REST API Support', 'acf-repeater' ),
			array( $this, 'render_rest_api_enabled_field' ),
			'acf_repeater_settings',
			'acf_repeater_rest_api'
		);

		// JSON Sync section.
		add_settings_section(
			'acf_repeater_json_sync',
			__( 'ACF JSON Sync', 'acf-repeater' ),
			array( $this, 'render_json_sync_section' ),
			'acf_repeater_settings'
		);

		add_settings_field(
			'json_sync_enabled',
			__( 'Enable JSON Sync', 'acf-repeater' ),
			array( $this, 'render_json_sync_enabled_field' ),
			'acf_repeater_settings',
			'acf_repeater_json_sync'
		);

		add_settings_field(
			'json_save_path',
			__( 'Custom Save Path', 'acf-repeater' ),
			array( $this, 'render_json_save_path_field' ),
			'acf_repeater_settings',
			'acf_repeater_json_sync'
		);

		// Add settings link to plugin action links.
		add_filter( 'plugin_action_links_' . plugin_basename( ACF_REPEATER_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Sanitize settings array.
	 *
	 * @param array<string, mixed> $input Input settings.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( array $input ): array {
		$sanitized = array();

		foreach ( $this->defaults as $key => $default ) {
			if ( isset( $input[ $key ] ) ) {
				$value = $input[ $key ];

				switch ( $key ) {
					case 'default_layout':
						$sanitized[ $key ] = in_array( $value, array( 'table', 'block' ), true ) ? $value : $this->defaults[ $key ];
						break;

					case 'default_button_label':
						$sanitized[ $key ] = sanitize_text_field( $value );
						break;

					case 'default_collapsed':
						$sanitized[ $key ] = sanitize_text_field( $value );
						break;

					case 'default_sortable':
					case 'default_duplicate':
					case 'default_delete_confirm':
					case 'rest_api_enabled':
					case 'json_sync_enabled':
						$sanitized[ $key ] = (bool) $value;
						break;

					case 'default_min_rows':
					case 'default_max_rows':
						$sanitized[ $key ] = max( 0, (int) $value );
						break;

					case 'json_save_path':
						$sanitized[ $key ] = sanitize_text_field( $value );
						break;

					case 'json_load_paths':
						if ( is_array( $value ) ) {
							$sanitized[ $key ] = array_map( 'sanitize_text_field', $value );
						} else {
							$sanitized[ $key ] = $this->defaults[ $key ];
						}
						break;

					default:
						$sanitized[ $key ] = $default;
				}
			} else {
				$sanitized[ $key ] = $default;
			}
		}

		return $sanitized;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ACF Repeater - Settings', 'acf-repeater' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Configure default settings for Repeater fields across your site.', 'acf-repeater' ); ?></p>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'acf_repeater_settings_group' );
				do_settings_sections( 'acf_repeater_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render general section description.
	 *
	 * @return void
	 */
	public function render_general_section(): void {
		echo '<p class="description">' . esc_html__( 'Default settings applied to new Repeater fields. Individual fields can override these.', 'acf-repeater' ) . '</p>';
	}

	/**
	 * Render REST API section description.
	 *
	 * @return void
	 */
	public function render_rest_api_section(): void {
		echo '<p class="description">' . esc_html__( 'Configure REST API exposure for Repeater field data.', 'acf-repeater' ) . '</p>';
	}

	/**
	 * Render JSON sync section description.
	 *
	 * @return void
	 */
	public function render_json_sync_section(): void {
		echo '<p class="description">' . esc_html__( 'Configure ACF JSON synchronization for Repeater fields.', 'acf-repeater' ) . '</p>';
	}

	/**
	 * Render default layout field.
	 *
	 * @return void
	 */
	public function render_default_layout_field(): void {
		$value = $this->get_setting( 'default_layout' );
		?>
		<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_layout]">
			<option value="table" <?php selected( $value, 'table' ); ?>><?php esc_html_e( 'Table', 'acf-repeater' ); ?></option>
			<option value="block" <?php selected( $value, 'block' ); ?>><?php esc_html_e( 'Block', 'acf-repeater' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Default layout style for new Repeater fields.', 'acf-repeater' ); ?></p>
		<?php
	}

	/**
	 * Render default button label field.
	 *
	 * @return void
	 */
	public function render_default_button_label_field(): void {
		$value = $this->get_setting( 'default_button_label' );
		?>
		<input type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_button_label]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Default text for the "Add Row" button.', 'acf-repeater' ); ?></p>
		<?php
	}

	/**
	 * Render default collapsed field.
	 *
	 * @return void
	 */
	public function render_default_collapsed_field(): void {
		$value = $this->get_setting( 'default_collapsed' );
		?>
		<input type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_collapsed]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Enter sub-field key to use as collapsed title', 'acf-repeater' ); ?>" />
		<p class="description"><?php esc_html_e( 'Sub-field name/key to use as the collapsed row title. Leave empty to use row number.', 'acf-repeater' ); ?></p>
		<?php
	}

	/**
	 * Render default sortable field.
	 *
	 * @return void
	 */
	public function render_default_sortable_field(): void {
		$value = $this->get_setting( 'default_sortable' );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_sortable]" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Enable drag-and-drop row sorting by default', 'acf-repeater' ); ?>
		</label>
		<?php
	}

	/**
	 * Render default duplicate field.
	 *
	 * @return void
	 */
	public function render_default_duplicate_field(): void {
		$value = $this->get_setting( 'default_duplicate' );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_duplicate]" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Enable row duplication by default', 'acf-repeater' ); ?>
		</label>
		<?php
	}

	/**
	 * Render default delete confirmation field.
	 *
	 * @return void
	 */
	public function render_default_delete_confirm_field(): void {
		$value = $this->get_setting( 'default_delete_confirm' );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_delete_confirm]" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Show confirmation dialog before deleting rows', 'acf-repeater' ); ?>
		</label>
		<?php
	}

	/**
	 * Render default min rows field.
	 *
	 * @return void
	 */
	public function render_default_min_rows_field(): void {
		$value = $this->get_setting( 'default_min_rows' );
		?>
		<input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_min_rows]" value="<?php echo esc_attr( $value ); ?>" min="0" class="small-text" />
		<p class="description"><?php esc_html_e( 'Minimum number of rows required. 0 = no minimum.', 'acf-repeater' ); ?></p>
		<?php
	}

	/**
	 * Render default max rows field.
	 *
	 * @return void
	 */
	public function render_default_max_rows_field(): void {
		$value = $this->get_setting( 'default_max_rows' );
		?>
		<input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_max_rows]" value="<?php echo esc_attr( $value ); ?>" min="0" class="small-text" />
		<p class="description"><?php esc_html_e( 'Maximum number of rows allowed. 0 = unlimited.', 'acf-repeater' ); ?></p>
		<?php
	}

	/**
	 * Render REST API enabled field.
	 *
	 * @return void
	 */
	public function render_rest_api_enabled_field(): void {
		$value = $this->get_setting( 'rest_api_enabled' );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[rest_api_enabled]" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Expose Repeater field data via WordPress REST API', 'acf-repeater' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When enabled, Repeater field values will be included in REST API responses for posts.', 'acf-repeater' ); ?></p>
		<?php
	}

	/**
	 * Render JSON sync enabled field.
	 *
	 * @return void
	 */
	public function render_json_sync_enabled_field(): void {
		$value = $this->get_setting( 'json_sync_enabled' );
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[json_sync_enabled]" value="1" <?php checked( $value, true ); ?> />
			<?php esc_html_e( 'Enable ACF JSON sync for Repeater fields', 'acf-repeater' ); ?>
		</label>
		<?php
	}

	/**
	 * Render JSON save path field.
	 *
	 * @return void
	 */
	public function render_json_save_path_field(): void {
		$value = $this->get_setting( 'json_save_path' );
		?>
		<input type="text" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[json_save_path]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php echo esc_attr( WP_CONTENT_DIR . '/acf-json' ); ?>" />
		<p class="description"><?php esc_html_e( 'Custom directory path for saving ACF JSON files. Leave empty for default.', 'acf-repeater' ); ?></p>
		<?php
	}

	/**
	 * Add settings link to plugin action links.
	 *
	 * @param array<string> $links Plugin action links.
	 * @return array<string>
	 */
	public function add_settings_link( array $links ): array {
		$settings_link = '<a href="' . esc_url( admin_url( 'edit.php?post_type=acf-field-group&page=acf-repeater-settings' ) ) . '">' . esc_html__( 'Settings', 'acf-repeater' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Modify ACF JSON save path.
	 *
	 * @param string $path Current path.
	 * @return string
	 */
	public function modify_save_json_path( string $path ): string {
		$custom_path = $this->get_setting( 'json_save_path' );
		if ( $custom_path && is_dir( $custom_path ) ) {
			return $custom_path;
		}
		return $path;
	}

	/**
	 * Modify ACF JSON load paths.
	 *
	 * @param array<string> $paths Current paths.
	 * @return array<string>
	 */
	public function modify_load_json_paths( array $paths ): array {
		$custom_paths = $this->get_setting( 'json_load_paths' );
		if ( ! empty( $custom_paths ) ) {
			foreach ( $custom_paths as $custom_path ) {
				if ( is_dir( $custom_path ) && ! in_array( $custom_path, $paths, true ) ) {
					$paths[] = $custom_path;
				}
			}
		}
		return $paths;
	}

	/**
	 * Get default settings.
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults(): array {
		return $this->defaults;
	}

	/**
	 * Get option name.
	 *
	 * @return string
	 */
	public function get_option_name(): string {
		return self::OPTION_NAME;
	}
}