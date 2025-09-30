<?php
/**
 * Configuration Examples for Scoped Media Library
 * 
 * This file contains example configurations and code snippets
 * for extending the Scoped Media Library plugin.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Example 1: Custom dimension rules based on context
 * 
 * This example shows how to set different dimension rules
 * for different ACF fields or contexts.
 */
add_filter('sml_dimension_rules', function($rules, $context) {
    
    // Banner images - require large width
    if ($context === 'acf_field_banner_image') {
        return array(
            'min_width' => 1920,
            'max_width' => 9999,
            'min_height' => 400,
            'max_height' => 800
        );
    }
    
    // Icon images - require small square dimensions
    if ($context === 'acf_field_icon') {
        return array(
            'min_width' => 100,
            'max_width' => 200,
            'min_height' => 100,
            'max_height' => 200
        );
    }
    
    // Thumbnail images - medium size
    if ($context === 'acf_field_thumbnail') {
        return array(
            'min_width' => 300,
            'max_width' => 600,
            'min_height' => 200,
            'max_height' => 400
        );
    }
    
    return $rules; // Return default rules for other contexts
}, 10, 2);

/**
 * Example 2: Skip filtering for specific pages or users
 * 
 * This example shows how to disable filtering in certain contexts.
 */
add_filter('sml_should_filter_query', function($should_filter, $query_args) {
    
    // Don't filter on the main media library page
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'upload.php') {
        return false;
    }
    
    // Don't filter for super admins
    if (is_super_admin()) {
        return false;
    }
    
    // Don't filter if a specific parameter is set
    if (isset($_GET['sml_disable_filter'])) {
        return false;
    }
    
    return $should_filter;
}, 10, 2);

/**
 * Example 3: Custom metadata sync actions
 * 
 * This example shows how to hook into the metadata sync process.
 */
add_action('sml_metadata_synced', function($attachment_id, $width, $height) {
    
    // Log the sync for debugging
    error_log("SML: Synced attachment {$attachment_id} - {$width}x{$height}");
    
    // Add custom metadata based on dimensions
    if ($width > $height) {
        update_post_meta($attachment_id, '_custom_orientation', 'landscape');
    } elseif ($height > $width) {
        update_post_meta($attachment_id, '_custom_orientation', 'portrait');
    } else {
        update_post_meta($attachment_id, '_custom_orientation', 'square');
    }
    
    // Categorize by size
    $total_pixels = $width * $height;
    if ($total_pixels > 2000000) { // > 2MP
        update_post_meta($attachment_id, '_image_size_category', 'large');
    } elseif ($total_pixels > 500000) { // > 0.5MP
        update_post_meta($attachment_id, '_image_size_category', 'medium');
    } else {
        update_post_meta($attachment_id, '_image_size_category', 'small');
    }
    
}, 10, 3);

/**
 * Example 4: Custom admin notices based on sync status
 * 
 * This example shows how to display custom admin notices.
 */
add_action('sml_batch_sync_complete', function($synced, $failed, $remaining) {
    
    if ($failed > 0) {
        add_action('admin_notices', function() use ($failed) {
            echo '<div class="notice notice-warning"><p>';
            echo sprintf(__('SML: %d images failed to sync. Please check your media files.', 'scoped-media-library'), $failed);
            echo '</p></div>';
        });
    }
    
    if ($remaining === 0 && $synced > 0) {
        add_action('admin_notices', function() use ($synced) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo sprintf(__('SML: Successfully synced %d images!', 'scoped-media-library'), $synced);
            echo '</p></div>';
        });
    }
    
}, 10, 3);

/**
 * Example 5: Integration with custom post types
 * 
 * This example shows how to apply different rules for different post types.
 */
add_filter('sml_dimension_rules', function($rules, $context) {
    
    // Get current post type
    $post_type = get_post_type();
    
    switch ($post_type) {
        case 'product':
            // Product images should be square and high quality
            return array(
                'min_width' => 800,
                'max_width' => 2000,
                'min_height' => 800,
                'max_height' => 2000
            );
            
        case 'portfolio':
            // Portfolio images can be any size but should be large
            return array(
                'min_width' => 1200,
                'max_width' => 9999,
                'min_height' => 600,
                'max_height' => 9999
            );
            
        case 'testimonial':
            // Testimonial images should be small and square (profile pics)
            return array(
                'min_width' => 150,
                'max_width' => 400,
                'min_height' => 150,
                'max_height' => 400
            );
    }
    
    return $rules;
}, 10, 2);

/**
 * Example 6: JavaScript integration
 * 
 * This example shows how to add custom JavaScript that works with the plugin.
 */
add_action('admin_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        
        // Listen for SML events
        $(document).on('sml:fallback_toggled', function(event, data) {
            console.log('Fallback mode changed:', data.active);
            
            // Custom logic when fallback mode changes
            if (data.active) {
                // User switched to "all images" mode
                $('.custom-warning').show();
            } else {
                // User switched back to scoped mode
                $('.custom-warning').hide();
            }
        });
        
        // Add custom button to media modal
        if (typeof wp !== 'undefined' && wp.media) {
            wp.media.view.MediaFrame.Post.prototype.on('ready', function() {
                var $toolbar = $('.media-toolbar');
                if ($toolbar.length) {
                    $toolbar.append('<button class="button custom-sml-button">Custom Action</button>');
                }
            });
        }
        
    });
    </script>
    <?php
});

/**
 * Example 7: REST API integration
 * 
 * This example shows how to expose SML data via REST API.
 */
add_action('rest_api_init', function() {
    
    register_rest_route('sml/v1', '/stats', array(
        'methods' => 'GET',
        'callback' => function() {
            $options = get_option('sml_options', array());
            $media_filter = new SML_Media_Filter($options);
            return $media_filter->get_dimension_stats();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
    
    register_rest_route('sml/v1', '/sync', array(
        'methods' => 'POST',
        'callback' => function() {
            $metadata_sync = new SML_Metadata_Sync();
            return $metadata_sync->sync_existing_metadata(25);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        }
    ));
    
});

/**
 * Example 8: WP-CLI integration
 * 
 * This example shows how to add WP-CLI commands for the plugin.
 */
if (defined('WP_CLI') && WP_CLI) {
    
    class SML_CLI_Commands extends WP_CLI_Command {
        
        /**
         * Sync metadata for all images
         * 
         * ## OPTIONS
         * 
         * [--batch-size=<size>]
         * : Number of images to process at once
         * ---
         * default: 50
         * ---
         * 
         * ## EXAMPLES
         * 
         *     wp sml sync
         *     wp sml sync --batch-size=100
         */
        public function sync($args, $assoc_args) {
            $batch_size = isset($assoc_args['batch-size']) ? intval($assoc_args['batch-size']) : 50;
            
            $metadata_sync = new SML_Metadata_Sync();
            $result = $metadata_sync->sync_existing_metadata($batch_size);
            
            WP_CLI::success(sprintf(
                'Synced %d images, %d failed, %d remaining',
                $result['synced'],
                $result['failed'],
                $result['remaining']
            ));
        }
        
        /**
         * Get plugin statistics
         */
        public function stats() {
            $options = get_option('sml_options', array());
            $media_filter = new SML_Media_Filter($options);
            $stats = $media_filter->get_dimension_stats();
            
            WP_CLI::line('Scoped Media Library Statistics:');
            WP_CLI::line('Total Images: ' . $stats['total_images']);
            WP_CLI::line('Scoped Images: ' . $stats['scoped_images']);
            WP_CLI::line('Without Metadata: ' . $stats['without_metadata']);
        }
    }
    
    WP_CLI::add_command('sml', 'SML_CLI_Commands');
}