<?php
/**
 * Admin interface for Scoped Media Library
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SML_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(SML_PLUGIN_FILE), array($this, 'add_action_links'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Scoped Media Library', 'scoped-media-library'),
            __('Scoped Media Library', 'scoped-media-library'),
            'manage_options',
            'scoped-media-library',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting(
            'sml_settings_group',
            'sml_options',
            array($this, 'validate_options')
        );
        
        // General Settings Section
        add_settings_section(
            'sml_general_section',
            __('General Settings', 'scoped-media-library'),
            array($this, 'general_section_callback'),
            'scoped-media-library'
        );
        
        // Dimension Rules Section
        add_settings_section(
            'sml_dimensions_section',
            __('Dimension Rules', 'scoped-media-library'),
            array($this, 'dimensions_section_callback'),
            'scoped-media-library'
        );
        
        // Advanced Settings Section
        add_settings_section(
            'sml_advanced_section',
            __('Advanced Settings', 'scoped-media-library'),
            array($this, 'advanced_section_callback'),
            'scoped-media-library'
        );
        
        // Add fields
        $this->add_settings_fields();
    }
    
    /**
     * Add settings fields
     */
    private function add_settings_fields() {
        // Enable/Disable
        add_settings_field(
            'enabled',
            __('Enable Filtering', 'scoped-media-library'),
            array($this, 'checkbox_field'),
            'scoped-media-library',
            'sml_general_section',
            array('field' => 'enabled', 'description' => __('Enable dimension-based filtering in media library', 'scoped-media-library'))
        );
        
        // Minimum Width
        add_settings_field(
            'min_width',
            __('Minimum Width (px)', 'scoped-media-library'),
            array($this, 'number_field'),
            'scoped-media-library',
            'sml_dimensions_section',
            array('field' => 'min_width', 'description' => __('Minimum image width in pixels', 'scoped-media-library'))
        );
        
        // Maximum Width
        add_settings_field(
            'max_width',
            __('Maximum Width (px)', 'scoped-media-library'),
            array($this, 'number_field'),
            'scoped-media-library',
            'sml_dimensions_section',
            array('field' => 'max_width', 'description' => __('Maximum image width in pixels', 'scoped-media-library'))
        );
        
        // Minimum Height
        add_settings_field(
            'min_height',
            __('Minimum Height (px)', 'scoped-media-library'),
            array($this, 'number_field'),
            'scoped-media-library',
            'sml_dimensions_section',
            array('field' => 'min_height', 'description' => __('Minimum image height in pixels', 'scoped-media-library'))
        );
        
        // Maximum Height
        add_settings_field(
            'max_height',
            __('Maximum Height (px)', 'scoped-media-library'),
            array($this, 'number_field'),
            'scoped-media-library',
            'sml_dimensions_section',
            array('field' => 'max_height', 'description' => __('Maximum image height in pixels', 'scoped-media-library'))
        );
        
        // Fallback Mode
        add_settings_field(
            'fallback_mode',
            __('Enable Fallback Mode', 'scoped-media-library'),
            array($this, 'checkbox_field'),
            'scoped-media-library',
            'sml_advanced_section',
            array('field' => 'fallback_mode', 'description' => __('Show all images alongside scoped results', 'scoped-media-library'))
        );
        
        // Admin Only Fallback
        add_settings_field(
            'admin_only_fallback',
            __('Admin Only Fallback', 'scoped-media-library'),
            array($this, 'checkbox_field'),
            'scoped-media-library',
            'sml_advanced_section',
            array('field' => 'admin_only_fallback', 'description' => __('Only show fallback mode to administrators', 'scoped-media-library'))
        );
        
        // Sync Existing Metadata
        add_settings_field(
            'sync_existing_metadata',
            __('Sync Existing Images', 'scoped-media-library'),
            array($this, 'sync_button_field'),
            'scoped-media-library',
            'sml_advanced_section',
            array('field' => 'sync_existing_metadata', 'description' => __('Sync dimension metadata for existing images', 'scoped-media-library'))
        );
    }
    
    /**
     * Section callbacks
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure basic settings for the Scoped Media Library plugin.', 'scoped-media-library') . '</p>';
    }
    
    public function dimensions_section_callback() {
        echo '<p>' . __('Set the dimension rules for filtering images in the media library.', 'scoped-media-library') . '</p>';
    }
    
    public function advanced_section_callback() {
        echo '<p>' . __('Advanced settings and maintenance options.', 'scoped-media-library') . '</p>';
    }
    
    /**
     * Field callbacks
     */
    public function checkbox_field($args) {
        $options = get_option('sml_options');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : false;
        
        echo '<input type="checkbox" name="sml_options[' . $args['field'] . ']" value="1" ' . checked(1, $value, false) . ' />';
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
    }
    
    public function number_field($args) {
        $options = get_option('sml_options');
        $value = isset($options[$args['field']]) ? $options[$args['field']] : 0;
        
        echo '<input type="number" name="sml_options[' . $args['field'] . ']" value="' . esc_attr($value) . '" min="0" max="9999" class="regular-text" />';
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
    }
    
    public function sync_button_field($args) {
        echo '<button type="button" id="sml-sync-metadata" class="button button-secondary">' . __('Sync Now', 'scoped-media-library') . '</button>';
        echo '<span id="sml-sync-status" class="sml-sync-status"></span>';
        if (isset($args['description'])) {
            echo '<p class="description">' . $args['description'] . '</p>';
        }
    }
    
    /**
     * Validate options
     */
    public function validate_options($input) {
        $validated = array();
        
        // Boolean fields
        $boolean_fields = array('enabled', 'fallback_mode', 'admin_only_fallback');
        foreach ($boolean_fields as $field) {
            $validated[$field] = isset($input[$field]) && $input[$field] == 1;
        }
        
        // Number fields
        $number_fields = array('min_width', 'max_width', 'min_height', 'max_height');
        foreach ($number_fields as $field) {
            $validated[$field] = isset($input[$field]) ? absint($input[$field]) : 0;
        }
        
        // Validation rules
        if ($validated['min_width'] > $validated['max_width']) {
            add_settings_error('sml_options', 'width_error', __('Minimum width cannot be greater than maximum width.', 'scoped-media-library'));
            $validated['min_width'] = $validated['max_width'];
        }
        
        if ($validated['min_height'] > $validated['max_height']) {
            add_settings_error('sml_options', 'height_error', __('Minimum height cannot be greater than maximum height.', 'scoped-media-library'));
            $validated['min_height'] = $validated['max_height'];
        }
        
        return $validated;
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="sml-admin-header">
                <div class="sml-plugin-info">
                    <h2><?php _e('Filter Images by Dimensions', 'scoped-media-library'); ?></h2>
                    <p><?php _e('Control which images appear in the WordPress media library selector by defining dimension rules. Perfect for ACF, Beaver Builder, and other plugins that require specific image sizes.', 'scoped-media-library'); ?></p>
                </div>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('sml_settings_group');
                do_settings_sections('scoped-media-library');
                submit_button();
                ?>
            </form>
            
            <div class="sml-admin-footer">
                <h3><?php _e('Usage Examples', 'scoped-media-library'); ?></h3>
                <ul>
                    <li><strong><?php _e('Icons:', 'scoped-media-library'); ?></strong> <?php _e('Set 100-200px width/height for icon selection', 'scoped-media-library'); ?></li>
                    <li><strong><?php _e('Banners:', 'scoped-media-library'); ?></strong> <?php _e('Set minimum 1920px width for banner images', 'scoped-media-library'); ?></li>
                    <li><strong><?php _e('Thumbnails:', 'scoped-media-library'); ?></strong> <?php _e('Set 150-300px dimensions for thumbnail images', 'scoped-media-library'); ?></li>
                </ul>
                
                <h3><?php _e('Compatibility', 'scoped-media-library'); ?></h3>
                <p><?php _e('This plugin works with ACF image fields, Beaver Builder, Elementor, and any other plugin that uses the WordPress media modal.', 'scoped-media-library'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ('settings_page_scoped-media-library' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'sml-admin-js',
            SML_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SML_VERSION,
            true
        );
        
        wp_enqueue_style(
            'sml-admin-css',
            SML_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SML_VERSION
        );
        
        wp_localize_script('sml-admin-js', 'sml_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sml_ajax_nonce'),
            'strings' => array(
                'syncing' => __('Syncing...', 'scoped-media-library'),
                'sync_complete' => __('Sync complete!', 'scoped-media-library'),
                'sync_error' => __('Sync failed. Please try again.', 'scoped-media-library')
            )
        ));
    }
    
    /**
     * Add action links to plugin list
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=scoped-media-library') . '">' . __('Settings', 'scoped-media-library') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}