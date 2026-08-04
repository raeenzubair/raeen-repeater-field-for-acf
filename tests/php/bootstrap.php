<?php
/**
 * PHPUnit Bootstrap for ACF Repeater tests.
 *
 * @package ACF_Repeater\Tests
 */

// Load WordPress test environment.
$tests_dir = getenv('WP_TESTS_DIR') ?: dirname(__DIR__, 3) . '/wordpress-tests-lib';
require_once $tests_dir . '/includes/functions.php';

// Load plugin.
require_once dirname(__DIR__, 2) . '/advanced-repeater-for-custom-fields.php';

// Initialize test environment.
function _manually_load_plugin()
{
	require_once dirname(__DIR__, 2) . '/advanced-repeater-for-custom-fields.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start WordPress.
require_once $tests_dir . '/includes/bootstrap.php';
