<?php
/**
 * Main Plugin class for ACF Repeater.
 *
 * @package ACF_Repeater\Core
 */

namespace ACF_Repeater\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ACF_Repeater\Admin\Settings;
use ACF_Repeater\Admin\Asset_Manager;
use ACF_Repeater\Admin\Ajax_Handler;
use ACF_Repeater\Admin\Rest_API;
use ACF_Repeater\Helpers\Validator;
use ACF_Repeater\Helpers\Sanitizer;

/**
 * Main plugin class - Singleton.
 */
final class Plugin {

	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?self $instance = null;

	/**
	 * Asset manager instance.
	 *
	 * @var Asset_Manager|null
	 */
	private ?Asset_Manager $asset_manager = null;

	/**
	 * Settings instance.
	 *
	 * @var Settings|null
	 */
	private ?Settings $settings = null;

	/**
	 * AJAX handler instance.
	 *
	 * @var Ajax_Handler|null
	 */
	private ?Ajax_Handler $ajax_handler = null;

	/**
	 * REST API instance.
	 *
	 * @var Rest_API|null
	 */
	private ?Rest_API $rest_api = null;

	/**
	 * Validator instance.
	 *
	 * @var Validator|null
	 */
	private ?Validator $validator = null;

	/**
	 * Sanitizer instance.
	 *
	 * @var Sanitizer|null
	 */
	private ?Sanitizer $sanitizer = null;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private string $version = ACF_REPEATER_VERSION;

	/**
	 * Plugin initialization state.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Private constructor for singleton.
	 */
	private function __construct() {}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}

	/**
	 * Get the plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		// Load dependencies.
		$this->load_dependencies();

		// Register autoloader.
		$this->register_autoloader();

		// Initialize core components.
		$this->init_core_components();

		// Hook into WordPress.
		$this->init_hooks();

		$this->initialized = true;
	}

	/**
	 * Load required dependencies.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		// ACF must be loaded.
		if ( ! function_exists( 'acf_get_field_type' ) ) {
			add_action( 'admin_notices', array( $this, 'acf_missing_notice' ) );
			return;
		}
	}

	/**
	 * Register PSR-4 autoloader.
	 *
	 * @return void
	 */
	private function register_autoloader(): void {
		$autoloader = new Autoloader( ACF_REPEATER_PLUGIN_DIR . 'includes/' );
		$autoloader->register();
	}

	/**
	 * Initialize core components.
	 *
	 * @return void
	 */
	private function init_core_components(): void {
		$this->asset_manager  = new Asset_Manager();
		$this->settings       = new Settings();
		$this->ajax_handler   = new Ajax_Handler();
		$this->rest_api       = new Rest_API();
		$this->validator      = new Validator();
		$this->sanitizer      = new Sanitizer();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Plugin lifecycle.
		register_activation_hook( ACF_REPEATER_PLUGIN_FILE, array( self::class, 'activate' ) );
		register_deactivation_hook( ACF_REPEATER_PLUGIN_FILE, array( self::class, 'deactivate' ) );

		// Admin assets.
		add_action( 'admin_enqueue_scripts', array( $this->asset_manager, 'enqueue_admin_assets' ) );
		add_action( 'acf/input/admin_enqueue_scripts', array( $this->asset_manager, 'enqueue_acf_assets' ), 20 );

		// Field registration.
		add_action( 'init', array( $this, 'register_field_type' ) );

		// REST API.
		add_action( 'rest_api_init', array( $this->rest_api, 'register_routes' ) );

		// Settings.
		add_action( 'admin_menu', array( $this->settings, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this->settings, 'register_settings' ) );

		// ACF JSON sync paths.
		add_filter( 'acf/settings/save_json', array( $this->settings, 'modify_save_json_path' ) );
		add_filter( 'acf/settings/load_json', array( $this->settings, 'modify_load_json_paths' ) );

		// Translations.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Fires after WordPress init.
	 *
	 * @return void
	 */
	public function on_init(): void {
		// Check ACF version.
		if ( function_exists( 'acf_get_setting' ) ) {
			$acf_version = acf_get_setting( 'version' );
			if ( version_compare( $acf_version, '6.0', '<' ) ) {
				add_action( 'admin_notices', array( $this, 'acf_version_notice' ) );
			}
		}

		// Load textdomain.
		$this->load_textdomain();
	}

	/**
	 * Register the repeater field type with ACF.
	 *
	 * @return void
	 */
	public function register_field_type(): void {
		if ( ! function_exists( 'acf_register_field_type' ) ) {
			return;
		}

		// Load the field class and register.
		require_once ACF_REPEATER_PLUGIN_DIR . 'includes/Field/Repeater_Field.php';

		if ( class_exists( 'acf_field_repeater' ) ) {
			acf_register_field_type( 'acf_field_repeater' );
		}
	}

	/**
	 * Fires on admin init.
	 *
	 * @return void
	 */
	public function on_admin_init(): void {
		// Register AJAX handlers.
		$this->ajax_handler->register_ajax_actions();
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
			ACF_REPEATER_TEXT_DOMAIN,
			false,
			dirname( plugin_basename( ACF_REPEATER_PLUGIN_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Admin notice: ACF not installed.
	 *
	 * @return void
	 */
	public function acf_missing_notice(): void {
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<strong><?php esc_html_e( 'ACF Repeater', 'repeater-field-for-acf' ); ?></strong>
				<?php
				printf(
					/* translators: %s: Plugin name */
					esc_html__( '%s requires Advanced Custom Fields (free version 5.8 or higher) to be installed and activated.', 'repeater-field-for-acf' ),
					'<strong>ACF Repeater</strong>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Admin notice: ACF version too old.
	 *
	 * @return void
	 */
	public function acf_version_notice(): void {
		$acf_version = function_exists( 'acf_get_setting' ) ? acf_get_setting( 'version' ) : 'unknown';
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'ACF Repeater', 'repeater-field-for-acf' ); ?></strong>
				<?php
				printf(
					/* translators: %s: ACF version */
					esc_html__( 'Advanced Custom Fields version %s is installed. Version 5.8 or higher is required.', 'repeater-field-for-acf' ),
					'<strong>' . esc_html( $acf_version ) . '</strong>'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * General admin notices.
	 *
	 * @return void
	 */
	public function admin_notices(): void {
		// Check for PHP version.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<strong><?php esc_html_e( 'ACF Repeater', 'repeater-field-for-acf' ); ?></strong>
					<?php
					printf(
						/* translators: %s: PHP version */
						esc_html__( 'PHP version %1$s or higher is required. You are running %2$s.', 'repeater-field-for-acf' ),
						'<strong>7.4</strong>',
						'<strong>' . esc_html( PHP_VERSION ) . '</strong>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Plugin activation hook.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Check PHP version.
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			deactivate_plugins( plugin_basename( ACF_REPEATER_PLUGIN_FILE ) );
			wp_die(
				esc_html__( 'ACF Repeater requires PHP 7.4 or higher.', 'repeater-field-for-acf' ),
				esc_html__( 'Plugin Activation Error', 'repeater-field-for-acf' ),
				array( 'response' => 500 )
			);
		}

		// Check WordPress version.
		global $wp_version;
		if ( version_compare( $wp_version, '5.8', '<' ) ) {
			deactivate_plugins( plugin_basename( ACF_REPEATER_PLUGIN_FILE ) );
			wp_die(
				esc_html__( 'ACF Repeater requires WordPress 5.8 or higher.', 'repeater-field-for-acf' ),
				esc_html__( 'Plugin Activation Error', 'repeater-field-for-acf' ),
				array( 'response' => 500 )
			);
		}

		// Check ACF is active.
		if ( ! function_exists( 'acf_get_field_type' ) ) {
			deactivate_plugins( plugin_basename( ACF_REPEATER_PLUGIN_FILE ) );
			wp_die(
				esc_html__( 'ACF Repeater requires Advanced Custom Fields (free version 5.8 or higher) to be installed and activated.', 'repeater-field-for-acf' ),
				esc_html__( 'Plugin Activation Error', 'repeater-field-for-acf' ),
				array( 'response' => 500 )
			);
		}

		// Create database options.
		update_option( 'acf_repeater_version', ACF_REPEATER_VERSION );
		update_option( 'acf_repeater_db_version', ACF_REPEATER_DB_VERSION );

		// Flush rewrite rules for REST API.
		flush_rewrite_rules();

		// Set default settings.
		add_option(
			'acf_repeater_settings',
			array(
				'default_layout'         => 'table',
				'default_button_label'   => __( 'Add Row', 'repeater-field-for-acf' ),
				'default_collapsed'      => '',
				'default_sortable'       => true,
				'default_duplicate'      => true,
				'default_delete_confirm' => true,
				'default_min_rows'       => 0,
				'default_max_rows'       => 0,
				'rest_api_enabled'       => true,
			),
			'',
			'no'
		);

		do_action( 'acf_repeater_activate' );
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Flush rewrite rules.
		flush_rewrite_rules();

		do_action( 'acf_repeater_deactivate' );
	}

	/**
	 * Get plugin version.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Get asset manager.
	 *
	 * @return Asset_Manager
	 */
	public function get_asset_manager(): Asset_Manager {
		return $this->asset_manager;
	}

	/**
	 * Get settings.
	 *
	 * @return Settings
	 */
	public function get_settings(): Settings {
		return $this->settings;
	}

	/**
	 * Get AJAX handler.
	 *
	 * @return Ajax_Handler
	 */
	public function get_ajax_handler(): Ajax_Handler {
		return $this->ajax_handler;
	}

	/**
	 * Get REST API.
	 *
	 * @return Rest_API
	 */
	public function get_rest_api(): Rest_API {
		return $this->rest_api;
	}

	/**
	 * Get validator.
	 *
	 * @return Validator
	 */
	public function get_validator(): Validator {
		return $this->validator;
	}

	/**
	 * Get sanitizer.
	 *
	 * @return Sanitizer
	 */
	public function get_sanitizer(): Sanitizer {
		return $this->sanitizer;
	}

	/**
	 * Check if plugin is initialized.
	 *
	 * @return bool
	 */
	public function is_initialized(): bool {
		return $this->initialized;
	}
}