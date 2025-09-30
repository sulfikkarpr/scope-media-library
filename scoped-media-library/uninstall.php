<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    Scoped_Media_Library
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Remove plugin options and metadata.
 */
function sml_uninstall_cleanup() {
    global $wpdb;

    // Remove plugin options
    delete_option( 'sml_settings' );
    delete_option( 'sml_last_sync' );

    // Remove custom meta fields from all attachments
    $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_sml_width'" );
    $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_sml_height'" );
    $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_sml_synced'" );

    // Clear scheduled events
    $timestamp = wp_next_scheduled( 'sml_sync_dimensions' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'sml_sync_dimensions' );
    }

    // Clear any transients
    delete_transient( 'sml_dimension_sync_running' );

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Run cleanup
sml_uninstall_cleanup();