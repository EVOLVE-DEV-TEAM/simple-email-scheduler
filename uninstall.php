<?php
/**
 * Uninstall Script
 *
 * @package Email Scheduler
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete all plugin options.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local variable, not global
$options_to_delete = array(
	'simesc_saved_recipients',
);

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local variable, not global
foreach ( $options_to_delete as $option ) {
	\delete_option( $option );
}

// Drop custom database table.
global $wpdb;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local variable, not global
$table_name = esc_sql( $wpdb->prefix . 'simesc_email_logs' );
$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

// Clear scheduled cron events.
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local variable, not global
$timestamp = wp_next_scheduled( 'simesc_monthly_email_check' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'simesc_monthly_email_check' );
}














