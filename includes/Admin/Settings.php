<?php
/**
 * Settings class for ACF Repeater.
 *
 * Lightweight settings store — no admin UI page.
 * Reads/writes from a single WordPress option with sensible defaults.
 *
 * @package ACF_Repeater\Admin
 */

namespace ACF_Repeater\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 */
class Settings
{
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
	 * Current settings (lazy-loaded).
	 *
	 * @var array<string, mixed>
	 */
	private array $settings = array();

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->settings = $this->get_settings();
	}

	// =========================================================================
	// Getters / Setters
	// =========================================================================

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function get_settings(): array
	{
		$saved = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( $saved, $this->defaults );
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Fallback when key is not set.
	 * @return mixed
	 */
	public function get_setting( string $key, mixed $default = null ): mixed
	{
		$settings = $this->get_settings();
		return $settings[ $key ] ?? $default;
	}

	/**
	 * Update a single setting.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value New value.
	 * @return bool
	 */
	public function update_setting( string $key, mixed $value ): bool
	{
		$settings         = $this->get_settings();
		$settings[ $key ] = $value;
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Update multiple settings at once.
	 *
	 * @param array<string, mixed> $settings Key/value pairs to merge in.
	 * @return bool
	 */
	public function update_settings( array $settings ): bool
	{
		$merged = array_merge( $this->get_settings(), $settings );
		return update_option( self::OPTION_NAME, $merged );
	}

	/**
	 * Reset all settings to factory defaults.
	 *
	 * @return bool
	 */
	public function reset_settings(): bool
	{
		return update_option( self::OPTION_NAME, $this->defaults );
	}

	/**
	 * Return factory defaults.
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults(): array
	{
		return $this->defaults;
	}

	/**
	 * Return the WordPress option name used to store settings.
	 *
	 * @return string
	 */
	public function get_option_name(): string
	{
		return self::OPTION_NAME;
	}

	// =========================================================================
	// ACF JSON Sync
	// =========================================================================

	/**
	 * Override ACF's JSON save path when a custom path is configured.
	 *
	 * Hooked on acf/settings/save_json.
	 *
	 * @param string $path Default ACF JSON save path.
	 * @return string
	 */
	public function modify_save_json_path( string $path ): string
	{
		$custom_path = $this->get_setting( 'json_save_path' );
		if ( $custom_path && is_dir( $custom_path ) ) {
			return $custom_path;
		}
		return $path;
	}

	/**
	 * Append custom paths to ACF's JSON load path list.
	 *
	 * Hooked on acf/settings/load_json.
	 *
	 * @param array<string> $paths Current ACF JSON load paths.
	 * @return array<string>
	 */
	public function modify_load_json_paths( array $paths ): array
	{
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
}