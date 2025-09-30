<?php
/**
 * Uninstall script for Scoped Media Library
 * 
 * This file is executed when the plugin is deleted from the WordPress admin.
 * It cleans up all plugin data from the database.
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove plugin options
delete_option('sml_options');
delete_option('sml_last_sync');

// Remove all plugin metadata from attachments
global $wpdb;

$wpdb->query("
    DELETE FROM {$wpdb->postmeta} 
    WHERE meta_key LIKE '_sml_%'
");

// Clear any scheduled events
wp_clear_scheduled_hook('sml_sync_metadata');

// Remove any transients
$wpdb->query("
    DELETE FROM {$wpdb->options} 
    WHERE option_name LIKE '_transient_sml_%' 
    OR option_name LIKE '_transient_timeout_sml_%'
");

// Remove any user meta related to the plugin
$wpdb->query("
    DELETE FROM {$wpdb->usermeta} 
    WHERE meta_key LIKE 'sml_%'
");

// Flush rewrite rules
flush_rewrite_rules();