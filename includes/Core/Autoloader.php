<?php
/**
 * PSR-4 Autoloader for ACF Repeater.
 *
 * @package Raeen_Repeater\Core
 * @version 1.0.0
 */

namespace Raeen_Repeater\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Autoloader
 *
 * PSR-4 compliant autoloader.
 */
class Autoloader {

	/**
	 * Base directory for the plugin classes.
	 *
	 * @var string
	 */
	private string $base_dir;

	/**
	 * Namespace prefix.
	 *
	 * @var string
	 */
	private string $namespace_prefix;

	/**
	 * Class map for faster loading.
	 *
	 * @var array<string, string>
	 */
	private array $class_map = array();

	/**
	 * Whether the autoloader is registered.
	 *
	 * @var bool
	 */
	private bool $registered = false;

	/**
	 * Constructor.
	 *
	 * @param string $base_dir        Base directory for classes.
	 * @param string $namespace_prefix Namespace prefix.
	 */
	public function __construct( string $base_dir = '', string $namespace_prefix = 'Raeen_Repeater\\' ) {
		$this->base_dir         = $base_dir ?: RAEEN_REPEATER_PLUGIN_DIR . 'includes/';
		$this->namespace_prefix = $namespace_prefix;


		// Build class map for performance.
		$this->build_class_map();
	}

	/**
	 * Build class map from directory structure.
	 *
	 * @return void
	 */
	private function build_class_map(): void {
		$directories = array(
			'Core',
			'Admin',
			'Field',
			'Assets',
			'Ajax',
			'Helpers',
			'Rest',
			'Export',
			'Validation',
		);

		foreach ( $directories as $dir ) {
			$full_path = $this->base_dir . $dir;
			if ( is_dir( $full_path ) ) {
				$this->scan_directory( $full_path );
			}
		}
	}

	/**
	 * Recursively scan directory for PHP files.
	 *
	 * @param string $dir Directory to scan.
	 * @return void
	 */
	private function scan_directory( string $dir ): void {
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && 'php' === $file->getExtension() ) {
				$relative_path                                = str_replace( $this->base_dir, '', $file->getPathname() );
				$class_name                                   = $this->namespace_prefix . str_replace( array( '/', '.php' ), array( '\\', '' ), $relative_path );
				$this->class_map[ strtolower( $class_name ) ] = $file->getPathname();
			}
		}
	}

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		spl_autoload_register( array( $this, 'load_class' ), true, true );
		$this->registered = true;
	}

	/**
	 * Unregister the autoloader.
	 *
	 * @return void
	 */
	public function unregister(): void {
		spl_autoload_unregister( array( $this, 'load_class' ) );
		$this->registered = false;
	}

	/**
	 * Load a class.
	 *
	 * @param string $class Fully qualified class name.
	 * @return bool True if loaded, false otherwise.
	 */
	public function load_class( string $class ): bool {
		// Check if class belongs to our namespace.
		if ( 0 !== strpos( $class, $this->namespace_prefix ) ) {
			return false;
		}

		$class_lower = strtolower( $class );

		// Try class map first (fastest).
		if ( isset( $this->class_map[ $class_lower ] ) ) {
			require_once $this->class_map[ $class_lower ];
			return true;
		}

		// Fallback to PSR-4 resolution.
		$relative_class = substr( $class, strlen( $this->namespace_prefix ) );
		$file_path      = $this->base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
			return true;
		}

		return false;
	}

	/**
	 * Get the class map.
	 *
	 * @return array<string, string>
	 */
	public function get_class_map(): array {
		return $this->class_map;
	}

	/**
	 * Add a class to the map.
	 *
	 * @param string $class    Class name.
	 * @param string $filepath File path.
	 * @return void
	 */
	public function add_class_map( string $class, string $filepath ): void {
		$this->class_map[ strtolower( $class ) ] = $filepath;
	}

	/**
	 * Check if class exists in map.
	 *
	 * @param string $class Class name.
	 * @return bool
	 */
	public function has_class( string $class ): bool {
		return isset( $this->class_map[ strtolower( $class ) ] );
	}
}

/**
 * Initialize autoloader.
 *
 * @return Autoloader
 */
function acf_repeater_autoloader(): Autoloader {
	static $autoloader = null;

	if ( null === $autoloader ) {
		$autoloader = new Autoloader();
		$autoloader->register();
	}

	return $autoloader;
}

// Auto-initialize when included.
acf_repeater_autoloader();
