<?php
/**
 * Asset Manager for ACF Repeater.
 *
 * @package ACF_Repeater\Admin
 */

namespace ACF_Repeater\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Asset Manager class.
 * Handles enqueueing and managing CSS/JS assets for admin and frontend.
 */
class Asset_Manager {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	private string $plugin_url;

	/**
	 * Plugin path.
	 *
	 * @var string
	 */
	private string $plugin_path;

	/**
	 * Script handles registered.
	 *
	 * @var array<string, array>
	 */
	private array $scripts = array();

	/**
	 * Style handles registered.
	 *
	 * @var array<string, array>
	 */
	private array $styles = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->version     = ACF_REPEATER_VERSION;
		$this->plugin_url  = ACF_REPEATER_PLUGIN_URL;
		$this->plugin_path = ACF_REPEATER_PLUGIN_DIR;

		// Only populate the definition arrays — do NOT call wp_register_* here.
		// WordPress requires registration to happen on an enqueue hook.
		$this->define_assets();
	}

	/**
	 * Populate the scripts/styles definition arrays.
	 *
	 * Safe to call at any time — does not touch WordPress APIs.
	 *
	 * @return void
	 */
	private function define_assets(): void {
		// Admin script (bundled via Vite).
		$this->scripts['acf-repeater-admin'] = array(
			'src'       => 'assets/dist/js/admin/index.js',
			'deps'      => array( 'jquery', 'acf-input', 'wp-util', 'jquery-ui-sortable' ),
			'version'   => $this->version,
			'in_footer' => true,
		);

		// Frontend script (for acf_form() on the frontend).
		$this->scripts['acf-repeater-frontend'] = array(
			'src'       => 'assets/dist/js/public/index.js',
			'deps'      => array( 'jquery', 'acf-input' ),
			'version'   => $this->version,
			'in_footer' => true,
		);

		// Admin stylesheet.
		$this->styles['acf-repeater-admin'] = array(
			'src'     => 'assets/dist/css/index.css',
			'deps'    => array( 'acf-global' ),
			'version' => $this->version,
			'media'   => 'all',
		);

		$this->styles['acf-repeater-admin-2'] = array(
			'src'     => 'assets/dist/css/index2.css',
			'deps'    => array( 'acf-repeater-admin' ),
			'version' => $this->version,
			'media'   => 'all',
		);
	}

	/**
	 * Register all asset handles with WordPress.
	 *
	 * MUST be called from an enqueue hook (admin_enqueue_scripts,
	 * wp_enqueue_scripts, or login_enqueue_scripts) — never from
	 * the constructor or plugin bootstrap.
	 *
	 * This method is idempotent: WordPress silently ignores a handle
	 * that is already registered.
	 *
	 * @return void
	 */
	public function register_with_wordpress(): void {
		foreach ( $this->scripts as $handle => $args ) {
			if ( wp_script_is( $handle, 'registered' ) ) {
				continue;
			}
			$src = $this->plugin_url . $args['src'];
			if ( file_exists( $this->plugin_path . $args['src'] ) ) {
				wp_register_script(
					$handle,
					$src,
					$args['deps'],
					$args['version'],
					$args['in_footer']
				);
			}
		}

		foreach ( $this->styles as $handle => $args ) {
			if ( wp_style_is( $handle, 'registered' ) ) {
				continue;
			}
			$src = $this->plugin_url . $args['src'];
			if ( file_exists( $this->plugin_path . $args['src'] ) ) {
				wp_register_style(
					$handle,
					$src,
					$args['deps'],
					$args['version'],
					$args['media']
				);
			}
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * Loads on all ACF-related admin pages: post edit, term edit, user edit,
	 * ACF field group screens, and the ACF settings page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook = '' ): void {
		// Register handles now that we are inside a valid enqueue hook.
		$this->register_with_wordpress();

		// Load only on relevant admin pages.
		$allowed_hooks = array(
			'post.php',
			'post-new.php',
			'term.php',
			'user-edit.php',
			'profile.php',
			'acf-field-group_page_acf-field-groups',
			'toplevel_page_acf-field-groups',
		);

		$is_allowed = in_array( $hook, $allowed_hooks, true )
			|| strpos( $hook, 'acf' ) !== false;

		if ( ! $is_allowed ) {
			return;
		}

		wp_enqueue_script( 'acf-repeater-admin' );
		wp_enqueue_style( 'acf-repeater-admin' );
		wp_enqueue_style( 'acf-repeater-admin-2' );

		$this->localize_admin_script();
	}

	/**
	 * Enqueue field group admin assets.
	 *
	 * @return void
	 */
	public function enqueue_field_group_assets(): void {
		$this->register_with_wordpress();
		wp_enqueue_script( 'acf-repeater-admin' );
		wp_enqueue_style( 'acf-repeater-admin' );
		wp_enqueue_style( 'acf-repeater-admin-2' );
		$this->localize_admin_script();
	}

	/**
	 * Enqueue input admin assets (post edit screen).
	 *
	 * @return void
	 */
	public function enqueue_input_assets(): void {
		$this->register_with_wordpress();
		wp_enqueue_script( 'acf-repeater-admin' );
		wp_enqueue_style( 'acf-repeater-admin' );
		wp_enqueue_style( 'acf-repeater-admin-2' );
		$this->localize_admin_script();
	}

	/**
	 * Enqueue block editor assets (Gutenberg).
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets(): void {
		$this->register_with_wordpress();
		wp_enqueue_script( 'acf-repeater-admin' );
		wp_enqueue_style( 'acf-repeater-admin' );
		wp_enqueue_style( 'acf-repeater-admin-2' );
		$this->localize_admin_script();
	}

	/**
	 * Enqueue frontend assets.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		// Register handles on the wp_enqueue_scripts hook (this is safe here).
		$this->register_with_wordpress();

		// Only enqueue if ACF frontend form is being used on this page.
		if ( function_exists( 'acf_form_head' ) && did_action( 'acf_form_head' ) ) {
			// Only enqueue script — no standalone frontend CSS bundle is registered.
			wp_enqueue_script( 'acf-repeater-frontend' );
		}
	}

	/**
	 * Localize admin script with data.
	 *
	 * @return void
	 */
	private function localize_admin_script(): void {
		$nonce = wp_create_nonce( 'acf_repeater_nonce' );

		$data = array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'nonce'      => $nonce,
			'version'    => $this->version,
			'plugin_url' => $this->plugin_url,
			'i18n'       => array(
				'add_row'         => __( 'Add Row', 'repeater-field-for-acf' ),
				'delete_row'      => __( 'Delete Row', 'repeater-field-for-acf' ),
				'duplicate_row'   => __( 'Duplicate Row', 'repeater-field-for-acf' ),
				'collapse_row'    => __( 'Collapse Row', 'repeater-field-for-acf' ),
				'expand_row'      => __( 'Expand Row', 'repeater-field-for-acf' ),
				'sort_rows'       => __( 'Sort Rows', 'repeater-field-for-acf' ),
				'confirm_delete'  => __( 'Are you sure you want to delete this row?', 'repeater-field-for-acf' ),
				/* translators: %d: minimum number of rows */
				'min_rows_error'  => __( 'Minimum number of rows required: %d', 'repeater-field-for-acf' ),
				/* translators: %d: maximum number of rows */
				'max_rows_error'  => __( 'Maximum number of rows exceeded: %d', 'repeater-field-for-acf' ),
				'required_field'  => __( 'This field is required', 'repeater-field-for-acf' ),
				'loading'         => __( 'Loading...', 'repeater-field-for-acf' ),
				'no_rows'         => __( 'No rows added yet. Click "Add Row" to get started.', 'repeater-field-for-acf' ),
				'row_collapsed'   => __( 'Row collapsed', 'repeater-field-for-acf' ),
				'row_expanded'    => __( 'Row expanded', 'repeater-field-for-acf' ),
				'drag_to_reorder' => __( 'Drag to reorder', 'repeater-field-for-acf' ),
			),
			'settings'   => get_option( 'acf_repeater_settings', array() ),
		);

		wp_localize_script( 'acf-repeater-admin', 'acfRepeater', $data );
	}

	/**
	 * Check if current page is an ACF page.
	 *
	 * @param string $hook Admin page hook.
	 * @return bool
	 */
	private function is_acf_page( string $hook ): bool {
		$acf_pages = array(
			'toplevel_page_acf-field-groups',
			'acf-field-group_page_acf-field-groups',
			'post.php',
			'post-new.php',
			'edit.php',
			'term.php',
			'user-edit.php',
			'profile.php',
			'widgets.php',
			'customize.php',
		);

		return in_array( $hook, $acf_pages, true ) || strpos( $hook, 'acf' ) !== false;
	}

	/**
	 * Check if current page is field group edit page.
	 *
	 * @param string $hook Admin page hook.
	 * @return bool
	 */
	private function is_field_group_page( string $hook ): bool {
		return in_array( $hook, array( 'post.php', 'post-new.php' ), true )
			&& isset( $_GET['post_type'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			&& 'acf-field-group' === $_GET['post_type']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Check if current page is post edit page.
	 *
	 * @param string $hook Admin page hook.
	 * @return bool
	 */
	private function is_post_edit_page( string $hook ): bool {
		return in_array( $hook, array( 'post.php', 'post-new.php', 'term.php', 'user-edit.php', 'profile.php' ), true );
	}

	/**
	 * Register script.
	 *
	 * @param string $handle Script handle.
	 * @param array  $args Script arguments.
	 * @return void
	 */
	public function register_script( string $handle, array $args ): void {
		$defaults = array(
			'src'       => '',
			'deps'      => array(),
			'version'   => $this->version,
			'in_footer' => true,
		);

		$args                     = wp_parse_args( $args, $defaults );
		$this->scripts[ $handle ] = $args;

		wp_register_script(
			$handle,
			$this->plugin_url . $args['src'],
			$args['deps'],
			$args['version'],
			$args['in_footer']
		);
	}

	/**
	 * Register style.
	 *
	 * @param string $handle Style handle.
	 * @param array  $args Style arguments.
	 * @return void
	 */
	public function register_style( string $handle, array $args ): void {
		$defaults = array(
			'src'     => '',
			'deps'    => array(),
			'version' => $this->version,
			'media'   => 'all',
		);

		$args                    = wp_parse_args( $args, $defaults );
		$this->styles[ $handle ] = $args;

		wp_register_style(
			$handle,
			$this->plugin_url . $args['src'],
			$args['deps'],
			$args['version'],
			$args['media']
		);
	}

	/**
	 * Enqueue script by handle.
	 *
	 * @param string $handle Script handle.
	 * @return void
	 */
	public function enqueue_script( string $handle ): void {
		if ( isset( $this->scripts[ $handle ] ) ) {
			wp_enqueue_script( $handle );
		}
	}

	/**
	 * Enqueue style by handle.
	 *
	 * @param string $handle Style handle.
	 * @return void
	 */
	public function enqueue_style( string $handle ): void {
		if ( isset( $this->styles[ $handle ] ) ) {
			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Get script URL.
	 *
	 * @param string $handle Script handle.
	 * @return string|null
	 */
	public function get_script_url( string $handle ): ?string {
		if ( isset( $this->scripts[ $handle ] ) ) {
			return $this->plugin_url . $this->scripts[ $handle ]['src'];
		}
		return null;
	}

	/**
	 * Get style URL.
	 *
	 * @param string $handle Style handle.
	 * @return string|null
	 */
	public function get_style_url( string $handle ): ?string {
		if ( isset( $this->styles[ $handle ] ) ) {
			return $this->plugin_url . $this->styles[ $handle ]['src'];
		}
		return null;
	}

	/**
	 * Get version.
	 *
	 * @return string
	 */
	public function get_version(): string {
		return $this->version;
	}

	/**
	 * Get plugin URL.
	 *
	 * @return string
	 */
	public function get_plugin_url(): string {
		return $this->plugin_url;
	}

	/**
	 * Get plugin path.
	 *
	 * @return string
	 */
	public function get_plugin_path(): string {
		return $this->plugin_path;
	}
}