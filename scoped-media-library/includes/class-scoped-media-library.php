<?php
/**
 * The core plugin class.
 *
 * @package    Scoped_Media_Library
 * @subpackage Scoped_Media_Library/includes
 */

class Scoped_Media_Library {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      SML_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = SCOPED_MEDIA_LIBRARY_VERSION;
        $this->plugin_name = 'scoped-media-library';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_media_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once SCOPED_MEDIA_LIBRARY_PLUGIN_DIR . 'includes/class-sml-loader.php';
        require_once SCOPED_MEDIA_LIBRARY_PLUGIN_DIR . 'includes/class-sml-admin.php';
        require_once SCOPED_MEDIA_LIBRARY_PLUGIN_DIR . 'includes/class-sml-media-filter.php';
        require_once SCOPED_MEDIA_LIBRARY_PLUGIN_DIR . 'includes/class-sml-metadata-sync.php';

        $this->loader = new SML_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new SML_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_filter( 'plugin_action_links_' . plugin_basename( SCOPED_MEDIA_LIBRARY_PLUGIN_DIR . 'scoped-media-library.php' ), $plugin_admin, 'add_action_links' );
    }

    /**
     * Register all of the hooks related to media filtering.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_media_hooks() {
        $media_filter = new SML_Media_Filter();
        $metadata_sync = new SML_Metadata_Sync();

        // Filter media library queries
        $this->loader->add_filter( 'ajax_query_attachments_args', $media_filter, 'filter_media_library_query' );
        
        // Sync metadata on upload
        $this->loader->add_action( 'add_attachment', $metadata_sync, 'sync_image_dimensions' );
        $this->loader->add_action( 'edit_attachment', $metadata_sync, 'sync_image_dimensions' );
        
        // Bulk sync action
        $this->loader->add_action( 'sml_sync_dimensions', $metadata_sync, 'bulk_sync_dimensions' );
        
        // Add custom column to media library
        $this->loader->add_filter( 'manage_media_columns', $media_filter, 'add_dimensions_column' );
        $this->loader->add_action( 'manage_media_custom_column', $media_filter, 'display_dimensions_column', 10, 2 );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    SML_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}