<?php
/**
 * Plugin Name: Raeen Repeater Field for ACF
 * Plugin URI: https://github.com/raeenzubair/repeater-field-for-acf
 * Description: Adds a fully functional Repeater field type to the free version of Advanced Custom Fields. Supports table/block/row layouts, drag-and-drop sorting, nested repeaters, and full ACF JSON sync.
 * Version: 1.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: advanced-custom-fields
 * Author: Mohammad Zubair Ali
 * Author URI: https://github.com/raeenzubair
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: repeater-field-for-acf
 * Domain Path: /languages
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin metadata constants.
 */
define( 'RAEEN_REPEATER_VERSION', '1.0.1' );
define( 'RAEEN_REPEATER_DB_VERSION', '1.0.1' );
define( 'RAEEN_REPEATER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RAEEN_REPEATER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RAEEN_REPEATER_PLUGIN_FILE', __FILE__ );
define( 'RAEEN_REPEATER_TEXT_DOMAIN', 'raeen-repeater-field-for-acf' );

/**
 * Autoloader bootstrap.
 *
 * 1. Load the built-in PSR-4 autoloader shipped with the plugin.
 *    This handles all Raeen_Repeater\* classes from the includes/ directory.
 * 2. Optionally load Composer's autoloader for dev dependencies (phpunit, phpcs, etc.).
 */
require_once RAEEN_REPEATER_PLUGIN_DIR . 'includes/Core/Autoloader.php';

if ( file_exists( RAEEN_REPEATER_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once RAEEN_REPEATER_PLUGIN_DIR . 'vendor/autoload.php';
}

use Raeen_Repeater\Admin\Asset_Manager;
use Raeen_Repeater\Admin\Settings;
use Raeen_Repeater\Admin\Ajax_Handler;
use Raeen_Repeater\Admin\Rest_API;

/**
 * Main plugin bootstrap class.
 */
final class Raeen_Repeater_Bootstrap {

	/**
	 * Plugin instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Asset manager instance.
	 *
	 * @var Asset_Manager
	 */
	private Asset_Manager $asset_manager;

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * AJAX handler instance.
	 *
	 * @var Ajax_Handler
	 */
	private Ajax_Handler $ajax_handler;

	/**
	 * REST API instance.
	 *
	 * @var Rest_API
	 */
	private Rest_API $rest_api;

	/**
	 * Get plugin instance (singleton).
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->asset_manager = new Asset_Manager();
		$this->settings      = new Settings();
		$this->ajax_handler  = new Ajax_Handler();
		$this->rest_api      = new Rest_API();

		$this->init_hooks();
	}

	/**
	 * Register all WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Plugin lifecycle.
		register_activation_hook( __FILE__, [ __CLASS__, 'activate' ] );
		register_deactivation_hook( __FILE__, [ __CLASS__, 'deactivate' ] );

		// ACF field type registration.
		add_action( 'acf/include_field_types', [ $this, 'register_repeater_field' ], 5 );
		add_action( 'acf/init', [ $this, 'register_repeater_field' ], 5 );

		// Remove 'repeater' from ACF's PRO field types so it's selectable in the
		// field group editor without the "PRO Only" lock.
		add_action( 'acf/field_group/admin_enqueue_scripts', [ $this, 'unlock_repeater_field_type' ], 1 );
		add_action( 'admin_head', [ $this, 'unlock_repeater_field_type' ], 1 );

		// Admin assets.
		add_action( 'admin_enqueue_scripts', [ $this->asset_manager, 'enqueue_admin_assets' ] );

		// ACF-specific asset hooks (field group editor + post edit screens).
		add_action( 'acf/field_group/admin_enqueue_scripts', [ $this->asset_manager, 'enqueue_field_group_assets' ] );
		add_action( 'acf/input/admin_enqueue_scripts', [ $this->asset_manager, 'enqueue_input_assets' ] );

		// Gutenberg / Block Editor.
		add_action( 'enqueue_block_editor_assets', [ $this->asset_manager, 'enqueue_block_editor_assets' ] );

		// Frontend ACF forms.
		add_action( 'wp_enqueue_scripts', [ $this->asset_manager, 'enqueue_frontend_assets' ] );

		// REST API.
		add_action( 'rest_api_init', [ $this->rest_api, 'register_routes' ] );

		// ACF JSON sync.
		add_filter( 'acf/settings/save_json', [ $this->settings, 'modify_save_json_path' ] );
		add_filter( 'acf/settings/load_json', [ $this->settings, 'modify_load_json_paths' ] );

		// Admin notices.
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
	}

	/**
	 * Register the repeater field type with ACF.
	 *
	 * Hooked on acf/include_field_types so ACF's field registry is fully
	 * initialized before we register our field.
	 *
	 * If ACF PRO is already active, it already has a repeater — skip.
	 *
	 * @return void
	 */
	public function register_repeater_field(): void {
		// Check if repeater field type is already registered in ACF.
		if ( function_exists( 'acf_is_field_type' ) && acf_is_field_type( 'repeater' ) ) {
			return;
		}

		// Prevent registering more than once across multiple hooks.
		static $registered = false;
		if ( $registered ) {
			return;
		}

		// If ACF PRO is providing its own repeater, don't override it.
		if ( $this->is_acf_pro_active() ) {
			return;
		}

		require_once RAEEN_REPEATER_PLUGIN_DIR . 'includes/Field/Repeater_Field.php';

		if ( class_exists( '\Raeen_Repeater\Field\Repeater_Field' ) ) {
			acf_register_field_type( '\Raeen_Repeater\Field\Repeater_Field' );
			$registered = true;
		}
	}

	/**
	 * Remove 'repeater' from ACF's PRO field types list in the JS data.
	 *
	 * ACF Free marks 'repeater' as a PRO-only field in its JS object
	 * (PROFieldTypes), which disables the field type in the editor dropdown.
	 * We override this before the data is output so our field is selectable.
	 *
	 * @return void
	 */
	public function unlock_repeater_field_type(): void {
		if ( $this->is_acf_pro_active() ) {
			return;
		}

		if ( ! function_exists( 'acf_get_pro_field_types' ) ) {
			return;
		}

		$pro_types = acf_get_pro_field_types();

		if ( isset( $pro_types['repeater'] ) ) {
			unset( $pro_types['repeater'] );
			// Override the JS-localized PROFieldTypes array so the editor
			// doesn't show the repeater as locked.
			acf_localize_data( array( 'PROFieldTypes' => $pro_types ) );
		}
	}

	/**
	 * Check whether ACF PRO plugin is active and providing its own repeater.
	 *
	 * @return bool
	 */
	private function is_acf_pro_active(): bool {
		// ACF PRO sets ACF_PRO constant when active.
		return defined( 'ACF_PRO' );
	}

	/**
	 * Show admin notices.
	 *
	 * @return void
	 */
	public function admin_notices(): void {
		// Show notice if ACF is missing.
		if ( ! function_exists( 'acf' ) && ! function_exists( 'acf_get_field_type' ) ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Raeen Repeater Field for ACF', 'raeen-repeater-field-for-acf' ); ?></strong>
					<?php
					printf(
						/* translators: %s: plugin name */
						esc_html__( '%s requires Advanced Custom Fields (free version 5.8 or higher) to be installed and activated.', 'raeen-repeater-field-for-acf' ),
						'<strong>Raeen Repeater Field for ACF</strong>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Plugin activation callback.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( plugin_basename( RAEEN_REPEATER_PLUGIN_FILE ) );
			wp_die(
				esc_html__( 'Raeen Repeater Field for ACF requires PHP 7.4 or higher.', 'raeen-repeater-field-for-acf' ),
				'',
				[ 'response' => 500 ]
			);
		}

		global $wp_version;
		if ( version_compare( $wp_version, '5.8', '<' ) ) {
			deactivate_plugins( plugin_basename( RAEEN_REPEATER_PLUGIN_FILE ) );
			wp_die(
				esc_html__( 'Raeen Repeater Field for ACF requires WordPress 5.8 or higher.', 'raeen-repeater-field-for-acf' ),
				'',
				[ 'response' => 500 ]
			);
		}

		update_option( 'raeen_repeater_version', RAEEN_REPEATER_VERSION );
		update_option( 'raeen_repeater_db_version', RAEEN_REPEATER_DB_VERSION );
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	// Getters for external access.

	/**
	 * @return Asset_Manager
	 */
	public function get_asset_manager(): Asset_Manager {
		return $this->asset_manager;
	}

	/**
	 * @return Settings
	 */
	public function get_settings(): Settings {
		return $this->settings;
	}

	/**
	 * @return Ajax_Handler
	 */
	public function get_ajax_handler(): Ajax_Handler {
		return $this->ajax_handler;
	}

	/**
	 * @return Rest_API
	 */
	public function get_rest_api(): Rest_API {
		return $this->rest_api;
	}
}

/**
 * Global accessor function.
 *
 * @return Raeen_Repeater_Bootstrap
 */
function raeen_repeater(): Raeen_Repeater_Bootstrap {
	return Raeen_Repeater_Bootstrap::instance();
}

// Initialize the plugin.
raeen_repeater();