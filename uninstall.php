<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only delete data if the user opted in.
if ( ! get_option( 'q2s_delete_data', false ) ) {
	return;
}

global $wpdb;

$q2s_table = $wpdb->prefix . 'q2s_rules';

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Intentional table drop on uninstall. Table name is safe ($wpdb->prefix + hardcoded constant).
$wpdb->query( "DROP TABLE IF EXISTS {$q2s_table}" );

delete_option( 'q2s_prefix' );
delete_option( 'q2s_delete_data' );
delete_transient( 'q2s_flush_rewrite' );
