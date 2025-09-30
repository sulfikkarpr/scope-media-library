<?php
/**
 * Plugin Name: Scoped Media Library - Filter Images by Dimensions
 * Plugin URI: https://github.com/yourname/scoped-media-library
 * Description: Control which images appear in the WordPress media library selector by defining dimension rules (height and width ranges). Perfect for ACF, Beaver Builder, and other page builders.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: scoped-media-library
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Current plugin version.
 */
define( 'SCOPED_MEDIA_LIBRARY_VERSION', '1.0.0' );
define( 'SCOPED_MEDIA_LIBRARY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCOPED_MEDIA_LIBRARY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_scoped_media_library() {
    require_once SCOPED_MEDIA_LIBRARY_PLUGIN_DIR . 'includes/class-sml-activator.php';
    SML_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_scoped_media_library() {
    require_once SCOPED_MEDIA_LIBRARY_PLUGIN_DIR . 'includes/class-sml-deactivator.php';
    SML_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_scoped_media_library' );
register_deactivation_hook( __FILE__, 'deactivate_scoped_media_library' );

/**
 * The core plugin class.
 */
require SCOPED_MEDIA_LIBRARY_PLUGIN_DIR . 'includes/class-scoped-media-library.php';

/**
 * Begins execution of the plugin.
 */
function run_scoped_media_library() {
    $plugin = new Scoped_Media_Library();
    $plugin->run();
}

run_scoped_media_library();