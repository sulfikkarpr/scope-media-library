<?php
/**
 * Plugin Name: Scoped Media Library – Filter Images by Dimensions
 * Plugin URI: https://github.com/your-username/scoped-media-library
 * Description: Control which images appear in the WordPress media library selector by defining dimension rules. Perfect for ACF, Beaver Builder, and other plugins that require specific image sizes.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: scoped-media-library
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SML_VERSION', '1.0.0');
define('SML_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SML_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SML_PLUGIN_FILE', __FILE__);

/**
 * Main plugin class
 */
class ScopedMediaLibrary {
    
    /**
     * Single instance of the plugin
     */
    private static $instance = null;
    
    /**
     * Plugin options
     */
    private $options;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the plugin
     */
    private function init() {
        // Load plugin textdomain
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Initialize plugin components
        add_action('init', array($this, 'init_plugin'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('scoped-media-library', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Initialize plugin components
     */
    public function init_plugin() {
        // Load options
        $this->options = get_option('sml_options', array());
        
        // Include required files
        $this->includes();
        
        // Initialize components
        $this->init_components();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once SML_PLUGIN_DIR . 'includes/class-sml-admin.php';
        require_once SML_PLUGIN_DIR . 'includes/class-sml-media-filter.php';
        require_once SML_PLUGIN_DIR . 'includes/class-sml-metadata-sync.php';
        require_once SML_PLUGIN_DIR . 'includes/class-sml-ajax-handler.php';
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize admin interface
        if (is_admin()) {
            new SML_Admin();
        }
        
        // Initialize media filtering
        new SML_Media_Filter($this->options);
        
        // Initialize metadata sync
        new SML_Metadata_Sync();
        
        // Initialize AJAX handler
        new SML_Ajax_Handler();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
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
        
        add_option('sml_options', $default_options);
        
        // Schedule metadata sync if needed
        if (!wp_next_scheduled('sml_sync_metadata')) {
            wp_schedule_single_event(time() + 60, 'sml_sync_metadata');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('sml_sync_metadata');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Get plugin options
     */
    public function get_options() {
        return $this->options;
    }
    
    /**
     * Update plugin options
     */
    public function update_options($new_options) {
        $this->options = array_merge($this->options, $new_options);
        update_option('sml_options', $this->options);
    }
}

// Initialize the plugin
function sml_init() {
    return ScopedMediaLibrary::get_instance();
}

// Start the plugin
sml_init();