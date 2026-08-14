<?php
/**
 * PHPUnit Bootstrap for Raeen Repeater Field for ACF tests.
 *
 * @package Raeen_Repeater\Tests
 */

// Define ABSPATH if running in standalone test environment.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__, 2 ) . '/' );
}

if ( ! defined( 'RAEEN_REPEATER_VERSION' ) ) {
	define( 'RAEEN_REPEATER_VERSION', '1.0.3' );
}
if ( ! defined( 'RAEEN_REPEATER_DB_VERSION' ) ) {
	define( 'RAEEN_REPEATER_DB_VERSION', '1.0.3' );
}
if ( ! defined( 'RAEEN_REPEATER_PLUGIN_DIR' ) ) {
	define( 'RAEEN_REPEATER_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
}
if ( ! defined( 'RAEEN_REPEATER_PLUGIN_URL' ) ) {
	define( 'RAEEN_REPEATER_PLUGIN_URL', 'http://example.org/wp-content/plugins/raeen-repeater-field-for-acf/' );
}
if ( ! defined( 'RAEEN_REPEATER_PLUGIN_FILE' ) ) {
	define( 'RAEEN_REPEATER_PLUGIN_FILE', dirname( __DIR__, 2 ) . '/raeen-repeater-field-for-acf.php' );
}
if ( ! defined( 'RAEEN_REPEATER_TEXT_DOMAIN' ) ) {
	define( 'RAEEN_REPEATER_TEXT_DOMAIN', 'raeen-repeater-field-for-acf' );
}

// Load WordPress test environment if available.
$raeen_repeater_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : dirname( __DIR__, 3 ) . '/wordpress-tests-lib';
if ( file_exists( $raeen_repeater_tests_dir . '/includes/functions.php' ) ) {
	require_once $raeen_repeater_tests_dir . '/includes/functions.php';

	function _raeen_repeater_manually_load_plugin() {
		require_once dirname( __DIR__, 2 ) . '/raeen-repeater-field-for-acf.php';
	}
	if ( function_exists( 'tests_add_filter' ) ) {
		tests_add_filter( 'muplugins_loaded', '_raeen_repeater_manually_load_plugin' );
	}

	require_once $raeen_repeater_tests_dir . '/includes/bootstrap.php';
} else {
	// Polyfills for WordPress functions when running standalone PHPUnit.
	if ( ! function_exists( '__' ) ) {
		function __( $text, $domain = 'default' ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_html__' ) ) {
		function esc_html__( $text, $domain = 'default' ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_attr__' ) ) {
		function esc_attr__( $text, $domain = 'default' ) {
			return $text;
		}
	}
	if ( ! function_exists( 'esc_html_e' ) ) {
		function esc_html_e( $text, $domain = 'default' ) {
			echo $text;
		}
	}
	if ( ! function_exists( 'esc_attr_e' ) ) {
		function esc_attr_e( $text, $domain = 'default' ) {
			echo $text;
		}
	}
	if ( ! function_exists( 'sanitize_key' ) ) {
		function sanitize_key( $key ) {
			$key = strtolower( (string) $key );
			return preg_replace( '/[^a-z0-9_\-]/', '', $key );
		}
	}
	if ( ! function_exists( 'sanitize_text_field' ) ) {
		function sanitize_text_field( $str ) {
			$filtered = wp_strip_all_tags( (string) $str, false );
			return trim( $filtered );
		}
	}
	if ( ! function_exists( 'wp_strip_all_tags' ) ) {
		function wp_strip_all_tags( $text, $remove_breaks = false ) {
			$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $text );
			$text = strip_tags( $text );
			if ( $remove_breaks ) {
				$text = preg_replace( '/[\r\n\t ]+/', ' ', $text );
			}
			return trim( $text );
		}
	}
	if ( ! function_exists( 'sanitize_textarea_field' ) ) {
		function sanitize_textarea_field( $str ) {
			return trim( strip_tags( (string) $str ) );
		}
	}
	if ( ! function_exists( 'sanitize_hex_color' ) ) {
		function sanitize_hex_color( $color ) {
			if ( '' === $color ) {
				return '';
			}
			if ( preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
				return $color;
			}
			return '';
		}
	}
	if ( ! function_exists( 'sanitize_html_class' ) ) {
		function sanitize_html_class( $class, $fallback = '' ) {
			$sanitized = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', (string) $class );
			$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );
			return '' === $sanitized ? $fallback : $sanitized;
		}
	}
	if ( ! function_exists( 'sanitize_email' ) ) {
		function sanitize_email( $email ) {
			$email = trim( strtolower( (string) $email ) );
			return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : '';
		}
	}
	if ( ! function_exists( 'esc_url_raw' ) ) {
		function esc_url_raw( $url, array $protocols = null ) {
			$url = trim( (string) $url );
			return $url;
		}
	}
	if ( ! function_exists( 'wp_kses_post' ) ) {
		function wp_kses_post( $data ) {
			return preg_replace( '@<script[^>]*?>.*?</script>@si', '', (string) $data );
		}
	}
	if ( ! function_exists( 'apply_filters' ) ) {
		function apply_filters( $hook_name, $value, ...$args ) {
			return $value;
		}
	}
	if ( ! function_exists( 'get_post' ) ) {
		function get_post( $post = null ) {
			if ( ! $post ) {
				return null;
			}
			$obj = new \stdClass();
			$obj->ID = is_object( $post ) ? $post->ID : (int) $post;
			$obj->post_type = 'post';
			$obj->post_title = 'Sample Post';
			$obj->post_status = 'publish';
			return $obj;
		}
	}
	if ( ! function_exists( 'wp_get_attachment_url' ) ) {
		function wp_get_attachment_url( $attachment_id = 0 ) {
			$id = (int) $attachment_id;
			return $id > 0 ? "http://example.com/wp-content/uploads/file-{$id}.jpg" : false;
		}
	}
	if ( ! function_exists( 'get_post_meta' ) ) {
		function get_post_meta( $post_id, $key = '', $single = false ) {
			return $single ? '' : array();
		}
	}
	if ( ! function_exists( 'is_wp_error' ) ) {
		function is_wp_error( $thing ) {
			return is_object( $thing ) && is_a( $thing, 'WP_Error' );
		}
	}

	// Load Autoloader.
	require_once dirname( __DIR__, 2 ) . '/includes/Core/Autoloader.php';
}
