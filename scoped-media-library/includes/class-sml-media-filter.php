<?php
/**
 * Media filtering functionality for Scoped Media Library
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SML_Media_Filter {
    
    /**
     * Plugin options
     */
    private $options;
    
    /**
     * Constructor
     */
    public function __construct($options) {
        $this->options = $options;
        
        // Only initialize if filtering is enabled
        if (!empty($this->options['enabled'])) {
            $this->init();
        }
    }
    
    /**
     * Initialize filtering hooks
     */
    private function init() {
        // Filter media library queries
        add_filter('ajax_query_attachments_args', array($this, 'filter_media_query'), 10, 1);
        
        // Add custom query vars for media modal
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Enqueue media scripts
        add_action('wp_enqueue_media', array($this, 'enqueue_media_scripts'));
        
        // Add media modal customization
        add_action('print_media_templates', array($this, 'print_media_templates'));
        
        // Handle fallback mode
        if (!empty($this->options['fallback_mode'])) {
            add_action('wp_ajax_sml_toggle_fallback', array($this, 'ajax_toggle_fallback'));
            add_action('wp_ajax_nopriv_sml_toggle_fallback', array($this, 'ajax_toggle_fallback'));
        }
    }
    
    /**
     * Filter media library queries based on dimensions
     */
    public function filter_media_query($query_args) {
        // Skip filtering if fallback mode is active for this user
        if ($this->is_fallback_active()) {
            return $query_args;
        }
        
        // Only filter image attachments
        if (!isset($query_args['post_mime_type']) || strpos($query_args['post_mime_type'], 'image') === false) {
            return $query_args;
        }
        
        // Add meta query for dimensions
        $meta_query = isset($query_args['meta_query']) ? $query_args['meta_query'] : array();
        
        // Width constraints
        if (!empty($this->options['min_width']) || !empty($this->options['max_width'])) {
            $width_query = array(
                'key' => '_sml_width',
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN',
                'value' => array(
                    max(1, intval($this->options['min_width'])),
                    min(9999, intval($this->options['max_width']))
                )
            );
            $meta_query[] = $width_query;
        }
        
        // Height constraints
        if (!empty($this->options['min_height']) || !empty($this->options['max_height'])) {
            $height_query = array(
                'key' => '_sml_height',
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN',
                'value' => array(
                    max(1, intval($this->options['min_height'])),
                    min(9999, intval($this->options['max_height']))
                )
            );
            $meta_query[] = $height_query;
        }
        
        // Apply meta query if we have constraints
        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $query_args['meta_query'] = $meta_query;
        }
        
        return $query_args;
    }
    
    /**
     * Add custom query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'sml_fallback';
        return $vars;
    }
    
    /**
     * Check if fallback mode is active for current user
     */
    private function is_fallback_active() {
        // Check if fallback mode is disabled
        if (empty($this->options['fallback_mode'])) {
            return false;
        }
        
        // Check admin-only restriction
        if (!empty($this->options['admin_only_fallback']) && !current_user_can('manage_options')) {
            return false;
        }
        
        // Check session or cookie for fallback state
        $fallback_active = false;
        
        // Check session first
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['sml_fallback_active'])) {
            $fallback_active = $_SESSION['sml_fallback_active'];
        } elseif (isset($_COOKIE['sml_fallback_active'])) {
            $fallback_active = $_COOKIE['sml_fallback_active'] === '1';
        }
        
        return $fallback_active;
    }
    
    /**
     * Enqueue media scripts
     */
    public function enqueue_media_scripts() {
        wp_enqueue_script(
            'sml-media-js',
            SML_PLUGIN_URL . 'assets/js/media.js',
            array('media-views'),
            SML_VERSION,
            true
        );
        
        wp_enqueue_style(
            'sml-media-css',
            SML_PLUGIN_URL . 'assets/css/media.css',
            array(),
            SML_VERSION
        );
        
        // Localize script with options and strings
        wp_localize_script('sml-media-js', 'sml_media', array(
            'options' => $this->options,
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sml_media_nonce'),
            'fallback_active' => $this->is_fallback_active(),
            'strings' => array(
                'scoped_mode' => __('Scoped Mode', 'scoped-media-library'),
                'all_images' => __('All Images', 'scoped-media-library'),
                'toggle_fallback' => __('Toggle View', 'scoped-media-library'),
                'dimension_info' => sprintf(
                    __('Showing images: %dx%d to %dx%d pixels', 'scoped-media-library'),
                    $this->options['min_width'],
                    $this->options['min_height'],
                    $this->options['max_width'],
                    $this->options['max_height']
                )
            )
        ));
    }
    
    /**
     * Print media modal templates
     */
    public function print_media_templates() {
        if (!$this->should_show_fallback_toggle()) {
            return;
        }
        ?>
        <script type="text/html" id="tmpl-sml-fallback-toggle">
            <div class="sml-fallback-toggle">
                <button type="button" class="button sml-toggle-btn" data-mode="{{ data.mode }}">
                    <span class="sml-toggle-text">{{ data.text }}</span>
                </button>
                <div class="sml-dimension-info">
                    {{ data.dimension_info }}
                </div>
            </div>
        </script>
        
        <script type="text/html" id="tmpl-sml-media-info">
            <div class="sml-media-info">
                <# if (data.scoped_count !== undefined) { #>
                    <span class="sml-count-info">
                        <?php _e('Scoped:', 'scoped-media-library'); ?> {{ data.scoped_count }} | 
                        <?php _e('Total:', 'scoped-media-library'); ?> {{ data.total_count }}
                    </span>
                <# } #>
            </div>
        </script>
        <?php
    }
    
    /**
     * Check if fallback toggle should be shown
     */
    private function should_show_fallback_toggle() {
        if (empty($this->options['fallback_mode'])) {
            return false;
        }
        
        if (!empty($this->options['admin_only_fallback']) && !current_user_can('manage_options')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * AJAX handler for toggling fallback mode
     */
    public function ajax_toggle_fallback() {
        check_ajax_referer('sml_media_nonce', 'nonce');
        
        if (!$this->should_show_fallback_toggle()) {
            wp_die(__('Access denied.', 'scoped-media-library'));
        }
        
        $mode = sanitize_text_field($_POST['mode']);
        $active = ($mode === 'all');
        
        // Start session if needed
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Store in session
        $_SESSION['sml_fallback_active'] = $active;
        
        // Also store in cookie for persistence
        setcookie('sml_fallback_active', $active ? '1' : '0', time() + (30 * 24 * 60 * 60), COOKIEPATH, COOKIE_DOMAIN);
        
        wp_send_json_success(array(
            'mode' => $mode,
            'active' => $active,
            'message' => $active 
                ? __('Showing all images', 'scoped-media-library')
                : __('Showing scoped images only', 'scoped-media-library')
        ));
    }
    
    /**
     * Get dimension statistics
     */
    public function get_dimension_stats() {
        global $wpdb;
        
        $stats = array(
            'total_images' => 0,
            'scoped_images' => 0,
            'without_metadata' => 0
        );
        
        // Get total image count
        $stats['total_images'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'attachment' 
             AND post_mime_type LIKE 'image%'"
        );
        
        // Get scoped image count
        $meta_query_parts = array();
        
        if (!empty($this->options['min_width']) || !empty($this->options['max_width'])) {
            $min_width = max(1, intval($this->options['min_width']));
            $max_width = min(9999, intval($this->options['max_width']));
            $meta_query_parts[] = "
                EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} 
                    WHERE post_id = p.ID 
                    AND meta_key = '_sml_width' 
                    AND CAST(meta_value AS UNSIGNED) BETWEEN {$min_width} AND {$max_width}
                )
            ";
        }
        
        if (!empty($this->options['min_height']) || !empty($this->options['max_height'])) {
            $min_height = max(1, intval($this->options['min_height']));
            $max_height = min(9999, intval($this->options['max_height']));
            $meta_query_parts[] = "
                EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} 
                    WHERE post_id = p.ID 
                    AND meta_key = '_sml_height' 
                    AND CAST(meta_value AS UNSIGNED) BETWEEN {$min_height} AND {$max_height}
                )
            ";
        }
        
        if (!empty($meta_query_parts)) {
            $where_clause = implode(' AND ', $meta_query_parts);
            $stats['scoped_images'] = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} p
                 WHERE p.post_type = 'attachment' 
                 AND p.post_mime_type LIKE 'image%'
                 AND {$where_clause}"
            );
        }
        
        // Get images without metadata
        $stats['without_metadata'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             WHERE p.post_type = 'attachment' 
             AND p.post_mime_type LIKE 'image%'
             AND NOT EXISTS (
                 SELECT 1 FROM {$wpdb->postmeta} 
                 WHERE post_id = p.ID AND meta_key = '_sml_width'
             )"
        );
        
        return $stats;
    }
}