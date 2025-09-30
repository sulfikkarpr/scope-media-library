<?php
/**
 * Metadata synchronization for Scoped Media Library
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SML_Metadata_Sync {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into attachment upload/update
        add_action('add_attachment', array($this, 'sync_attachment_metadata'));
        add_action('edit_attachment', array($this, 'sync_attachment_metadata'));
        
        // Hook into image regeneration (if plugins like Regenerate Thumbnails are used)
        add_action('wp_generate_attachment_metadata', array($this, 'sync_from_wp_metadata'), 10, 2);
        
        // Scheduled sync for existing images
        add_action('sml_sync_metadata', array($this, 'sync_existing_metadata'));
        
        // AJAX handler for manual sync
        add_action('wp_ajax_sml_sync_metadata', array($this, 'ajax_sync_metadata'));
    }
    
    /**
     * Sync metadata for a specific attachment
     */
    public function sync_attachment_metadata($attachment_id) {
        // Only process images
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }
        
        $file_path = get_attached_file($attachment_id);
        if (!$file_path || !file_exists($file_path)) {
            return;
        }
        
        // Get image dimensions
        $image_size = getimagesize($file_path);
        if (!$image_size) {
            return;
        }
        
        $width = $image_size[0];
        $height = $image_size[1];
        
        // Store dimensions as meta data
        update_post_meta($attachment_id, '_sml_width', $width);
        update_post_meta($attachment_id, '_sml_height', $height);
        update_post_meta($attachment_id, '_sml_synced', current_time('timestamp'));
        
        // Also store additional metadata for future features
        update_post_meta($attachment_id, '_sml_aspect_ratio', round($width / $height, 2));
        update_post_meta($attachment_id, '_sml_orientation', $width > $height ? 'landscape' : ($width < $height ? 'portrait' : 'square'));
        
        do_action('sml_metadata_synced', $attachment_id, $width, $height);
    }
    
    /**
     * Sync from WordPress generated metadata
     */
    public function sync_from_wp_metadata($metadata, $attachment_id) {
        if (isset($metadata['width']) && isset($metadata['height'])) {
            update_post_meta($attachment_id, '_sml_width', $metadata['width']);
            update_post_meta($attachment_id, '_sml_height', $metadata['height']);
            update_post_meta($attachment_id, '_sml_synced', current_time('timestamp'));
            update_post_meta($attachment_id, '_sml_aspect_ratio', round($metadata['width'] / $metadata['height'], 2));
            update_post_meta($attachment_id, '_sml_orientation', 
                $metadata['width'] > $metadata['height'] ? 'landscape' : 
                ($metadata['width'] < $metadata['height'] ? 'portrait' : 'square')
            );
        }
        
        return $metadata;
    }
    
    /**
     * Sync metadata for existing images (batch process)
     */
    public function sync_existing_metadata($batch_size = 50) {
        global $wpdb;
        
        // Get images without synced metadata
        $attachments = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'attachment'
            AND p.post_mime_type LIKE %s
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = p.ID
                AND pm.meta_key = '_sml_synced'
            )
            ORDER BY p.post_date DESC
            LIMIT %d
        ", 'image%', $batch_size));
        
        $synced_count = 0;
        $failed_count = 0;
        
        foreach ($attachments as $attachment) {
            $result = $this->sync_attachment_metadata($attachment->ID);
            
            if ($result !== false) {
                $synced_count++;
            } else {
                $failed_count++;
            }
            
            // Prevent timeout
            if (function_exists('set_time_limit')) {
                set_time_limit(30);
            }
        }
        
        // Schedule next batch if there are more images
        $remaining = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'attachment'
            AND p.post_mime_type LIKE %s
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = p.ID
                AND pm.meta_key = '_sml_synced'
            )
        ", 'image%'));
        
        if ($remaining > 0) {
            wp_schedule_single_event(time() + 60, 'sml_sync_metadata', array($batch_size));
        }
        
        // Store sync statistics
        update_option('sml_last_sync', array(
            'timestamp' => current_time('timestamp'),
            'synced' => $synced_count,
            'failed' => $failed_count,
            'remaining' => $remaining
        ));
        
        do_action('sml_batch_sync_complete', $synced_count, $failed_count, $remaining);
        
        return array(
            'synced' => $synced_count,
            'failed' => $failed_count,
            'remaining' => $remaining
        );
    }
    
    /**
     * AJAX handler for manual metadata sync
     */
    public function ajax_sync_metadata() {
        check_ajax_referer('sml_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Access denied.', 'scoped-media-library'));
        }
        
        $batch_size = isset($_POST['batch_size']) ? intval($_POST['batch_size']) : 50;
        $batch_size = max(10, min(100, $batch_size)); // Limit batch size
        
        $result = $this->sync_existing_metadata($batch_size);
        
        wp_send_json_success(array(
            'synced' => $result['synced'],
            'failed' => $result['failed'],
            'remaining' => $result['remaining'],
            'message' => sprintf(
                __('Synced %d images. %d failed. %d remaining.', 'scoped-media-library'),
                $result['synced'],
                $result['failed'],
                $result['remaining']
            )
        ));
    }
    
    /**
     * Get sync statistics
     */
    public function get_sync_stats() {
        global $wpdb;
        
        $stats = array(
            'total_images' => 0,
            'synced_images' => 0,
            'unsynced_images' => 0,
            'last_sync' => null
        );
        
        // Total images
        $stats['total_images'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'attachment' 
             AND post_mime_type LIKE 'image%'"
        );
        
        // Synced images
        $stats['synced_images'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID) 
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'attachment' 
             AND p.post_mime_type LIKE 'image%'
             AND pm.meta_key = '_sml_synced'"
        );
        
        // Unsynced images
        $stats['unsynced_images'] = $stats['total_images'] - $stats['synced_images'];
        
        // Last sync info
        $stats['last_sync'] = get_option('sml_last_sync');
        
        return $stats;
    }
    
    /**
     * Clean up orphaned metadata
     */
    public function cleanup_metadata() {
        global $wpdb;
        
        // Remove metadata for non-existent attachments
        $deleted = $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key LIKE '_sml_%'
            AND p.ID IS NULL
        ");
        
        return $deleted;
    }
    
    /**
     * Resync specific attachment
     */
    public function resync_attachment($attachment_id) {
        // Remove existing metadata
        delete_post_meta($attachment_id, '_sml_width');
        delete_post_meta($attachment_id, '_sml_height');
        delete_post_meta($attachment_id, '_sml_synced');
        delete_post_meta($attachment_id, '_sml_aspect_ratio');
        delete_post_meta($attachment_id, '_sml_orientation');
        
        // Sync again
        return $this->sync_attachment_metadata($attachment_id);
    }
    
    /**
     * Check if attachment needs sync
     */
    public function needs_sync($attachment_id) {
        if (!wp_attachment_is_image($attachment_id)) {
            return false;
        }
        
        $synced = get_post_meta($attachment_id, '_sml_synced', true);
        return empty($synced);
    }
    
    /**
     * Get attachment dimensions from metadata
     */
    public function get_attachment_dimensions($attachment_id) {
        $width = get_post_meta($attachment_id, '_sml_width', true);
        $height = get_post_meta($attachment_id, '_sml_height', true);
        
        if (empty($width) || empty($height)) {
            // Try to sync if not available
            $this->sync_attachment_metadata($attachment_id);
            $width = get_post_meta($attachment_id, '_sml_width', true);
            $height = get_post_meta($attachment_id, '_sml_height', true);
        }
        
        return array(
            'width' => intval($width),
            'height' => intval($height)
        );
    }
}