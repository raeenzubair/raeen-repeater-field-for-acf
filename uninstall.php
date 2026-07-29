<?php
/**
 * Uninstall script for ACF Repeater.
 *
 * @package ACF_Repeater
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options.
delete_option( 'acf_repeater_settings' );
delete_option( 'acf_repeater_version' );
delete_option( 'acf_repeater_db_version' );

// Delete any custom database tables if they exist.
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}acf_repeater_rows" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

// Clear any transients related to this plugin.
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_acf_repeater%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_acf_repeater%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared