<?php
/**
 * AJAX handler for Scoped Media Library
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SML_Ajax_Handler {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Admin AJAX handlers
        add_action('wp_ajax_sml_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_sml_test_query', array($this, 'ajax_test_query'));
        add_action('wp_ajax_sml_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_sml_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_sml_import_settings', array($this, 'ajax_import_settings'));
        
        // Media modal AJAX handlers
        add_action('wp_ajax_sml_get_media_counts', array($this, 'ajax_get_media_counts'));
        add_action('wp_ajax_sml_preview_filter', array($this, 'ajax_preview_filter'));
    }
    
    /**
     * Get plugin statistics
     */
    public function ajax_get_stats() {
        check_ajax_referer('sml_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Access denied.', 'scoped-media-library'));
        }
        
        $options = get_option('sml_options', array());
        $media_filter = new SML_Media_Filter($options);
        $metadata_sync = new SML_Metadata_Sync();
        
        $stats = array(
            'dimension_stats' => $media_filter->get_dimension_stats(),
            'sync_stats' => $metadata_sync->get_sync_stats(),
            'plugin_version' => SML_VERSION,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION
        );
        
        wp_send_json_success($stats);
    }
    
    /**
     * Test media query with current settings
     */
    public function ajax_test_query() {
        check_ajax_referer('sml_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Access denied.', 'scoped-media-library'));
        }
        
        $test_options = array(
            'enabled' => true,
            'min_width' => intval($_POST['min_width']),
            'max_width' => intval($_POST['max_width']),
            'min_height' => intval($_POST['min_height']),
            'max_height' => intval($_POST['max_height'])
        );
        
        $media_filter = new SML_Media_Filter($test_options);
        
        // Simulate media query
        $query_args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => -1
        );
        
        $filtered_args = $media_filter->filter_media_query($query_args);
        
        // Run the query
        $query = new WP_Query($filtered_args);
        
        $results = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $attachment_id = get_the_ID();
                $metadata_sync = new SML_Metadata_Sync();
                $dimensions = $metadata_sync->get_attachment_dimensions($attachment_id);
                
                $results[] = array(
                    'id' => $attachment_id,
                    'title' => get_the_title(),
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'url' => wp_get_attachment_thumb_url($attachment_id)
                );
            }
        }
        wp_reset_postdata();
        
        wp_send_json_success(array(
            'count' => count($results),
            'results' => array_slice($results, 0, 10), // Limit to first 10 for preview
            'query_args' => $filtered_args
        ));
    }
    
    /**
     * Reset plugin settings to defaults
     */
    public function ajax_reset_settings() {
        check_ajax_referer('sml_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Access denied.', 'scoped-media-library'));
        }
        
        $default_options = array(
            'enabled' => true,
            'min_width' => 0,
            'max_width' => 9999,
            'min_height' => 0,
            'max_height' => 9999,
            'fallback_mode' => true,
            'admin_only_fallback' => true,
            'sync_existing_metadata' => false
        );
        
        update_option('sml_options', $default_options);
        
        wp_send_json_success(array(
            'message' => __('Settings reset to defaults.', 'scoped-media-library'),
            'options' => $default_options
        ));
    }
    
    /**
     * Export plugin settings
     */
    public function ajax_export_settings() {
        check_ajax_referer('sml_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Access denied.', 'scoped-media-library'));
        }
        
        $options = get_option('sml_options', array());
        $export_data = array(
            'version' => SML_VERSION,
            'exported' => current_time('Y-m-d H:i:s'),
            'site_url' => get_site_url(),
            'options' => $options
        );
        
        $filename = 'scoped-media-library-settings-' . date('Y-m-d-H-i-s') . '.json';
        
        wp_send_json_success(array(
            'data' => json_encode($export_data, JSON_PRETTY_PRINT),
            'filename' => $filename
        ));
    }
    
    /**
     * Import plugin settings
     */
    public function ajax_import_settings() {
        check_ajax_referer('sml_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Access denied.', 'scoped-media-library'));
        }
        
        if (empty($_POST['settings_data'])) {
            wp_send_json_error(__('No settings data provided.', 'scoped-media-library'));
        }
        
        $import_data = json_decode(stripslashes($_POST['settings_data']), true);
        
        if (!$import_data || !isset($import_data['options'])) {
            wp_send_json_error(__('Invalid settings data format.', 'scoped-media-library'));
        }
        
        // Validate imported options
        $valid_keys = array('enabled', 'min_width', 'max_width', 'min_height', 'max_height', 'fallback_mode', 'admin_only_fallback');
        $validated_options = array();
        
        foreach ($valid_keys as $key) {
            if (isset($import_data['options'][$key])) {
                $validated_options[$key] = $import_data['options'][$key];
            }
        }
        
        if (empty($validated_options)) {
            wp_send_json_error(__('No valid settings found in import data.', 'scoped-media-library'));
        }
        
        // Merge with current options
        $current_options = get_option('sml_options', array());
        $merged_options = array_merge($current_options, $validated_options);
        
        update_option('sml_options', $merged_options);
        
        wp_send_json_success(array(
            'message' => __('Settings imported successfully.', 'scoped-media-library'),
            'imported_count' => count($validated_options),
            'options' => $merged_options
        ));
    }
    
    /**
     * Get media counts for current filter settings
     */
    public function ajax_get_media_counts() {
        check_ajax_referer('sml_media_nonce', 'nonce');
        
        $options = get_option('sml_options', array());
        $media_filter = new SML_Media_Filter($options);
        $stats = $media_filter->get_dimension_stats();
        
        wp_send_json_success($stats);
    }
    
    /**
     * Preview filter results
     */
    public function ajax_preview_filter() {
        check_ajax_referer('sml_media_nonce', 'nonce');
        
        $preview_options = array(
            'enabled' => true,
            'min_width' => isset($_POST['min_width']) ? intval($_POST['min_width']) : 0,
            'max_width' => isset($_POST['max_width']) ? intval($_POST['max_width']) : 9999,
            'min_height' => isset($_POST['min_height']) ? intval($_POST['min_height']) : 0,
            'max_height' => isset($_POST['max_height']) ? intval($_POST['max_height']) : 9999
        );
        
        $media_filter = new SML_Media_Filter($preview_options);
        
        // Get sample results
        $query_args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => 12
        );
        
        $filtered_args = $media_filter->filter_media_query($query_args);
        $query = new WP_Query($filtered_args);
        
        $results = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $attachment_id = get_the_ID();
                $metadata_sync = new SML_Metadata_Sync();
                $dimensions = $metadata_sync->get_attachment_dimensions($attachment_id);
                
                $results[] = array(
                    'id' => $attachment_id,
                    'title' => get_the_title(),
                    'width' => $dimensions['width'],
                    'height' => $dimensions['height'],
                    'thumbnail' => wp_get_attachment_image_url($attachment_id, 'thumbnail'),
                    'edit_url' => admin_url('post.php?post=' . $attachment_id . '&action=edit')
                );
            }
        }
        wp_reset_postdata();
        
        wp_send_json_success(array(
            'results' => $results,
            'total_found' => $query->found_posts
        ));
    }
}