<?php
/**
 * PHPUnit Bootstrap for ACF Repeater tests.
 *
 * @package ACF_Repeater\Tests
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) && ! defined( 'PHPUNIT_COMPOSER_INSTALL' ) && ! getenv( 'WP_TESTS_DIR' ) ) {
	exit;
}

// Load WordPress test environment.
$acf_repeater_tests_dir = getenv('WP_TESTS_DIR') ?: dirname(__DIR__, 3) . '/wordpress-tests-lib';
require_once $acf_repeater_tests_dir . '/includes/functions.php';

// Load plugin.
require_once dirname(__DIR__, 2) . '/raeen-repeater-field-for-acf.php';

// Initialize test environment.
function _acf_repeater_manually_load_plugin()
{
	require_once dirname(__DIR__, 2) . '/raeen-repeater-field-for-acf.php';
}
tests_add_filter('muplugins_loaded', '_acf_repeater_manually_load_plugin');

// Start WordPress.
require_once $acf_repeater_tests_dir . '/includes/bootstrap.php';

