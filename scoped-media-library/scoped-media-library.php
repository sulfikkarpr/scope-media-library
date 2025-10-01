<?php
/*
Plugin Name: Scoped Media Library – Filter Images by Dimensions
Description: Control which images appear in the WordPress media library by filtering attachments based on width/height rules. Compatible with ACF, Beaver Builder, and the core media modal. Includes a fallback for administrators.
Version: 1.0.0
Author: Scoped Media
Text Domain: scoped-media-library
Requires at least: 5.8
Requires PHP: 7.4
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SML_PLUGIN_FILE' ) ) {
	define( 'SML_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'SML_PLUGIN_DIR' ) ) {
	define( 'SML_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SML_PLUGIN_URL' ) ) {
	define( 'SML_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once SML_PLUGIN_DIR . 'includes/class-scoped-media-library.php';
// Integrations
require_once SML_PLUGIN_DIR . 'includes/integrations/class-sml-integration-acf.php';
require_once SML_PLUGIN_DIR . 'includes/integrations/class-sml-integration-beaver.php';

add_action( 'plugins_loaded', function() {
	\Scoped_Media_Library::instance();
	\SML_Integration_ACF::maybe_boot();
	\SML_Integration_Beaver::maybe_boot();
} );

register_activation_hook( __FILE__, function() {
	// Placeholder for future activation routines (e.g., backfill tasks).
} );

// No global settings screen; configuration is per-field via integrations.

