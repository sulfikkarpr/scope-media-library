<?php
/**
 * Plugin Name: Scoped Media Library – Filter Images by Dimensions
 * Description: Scope the WordPress media modal to only show images within defined dimension ranges.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0-or-later
 * Text Domain: scoped-media-library
 * Requires at least: 5.8
 * Requires PHP: 7.4
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

// Autoload includes.
require_once SML_PLUGIN_DIR . 'includes/class-sml-loader.php';

function sml_bootstrap() {
	$loader = new SML_Loader();
	$loader->init();
}
add_action( 'plugins_loaded', 'sml_bootstrap' );

register_activation_hook( __FILE__, function () {
	// Default options on first activation.
	$defaults = array(
		'min_width'  => 0,
		'max_width'  => 0,
		'min_height' => 0,
		'max_height' => 0,
		'fallback'   => false,
	);
	add_option( 'sml_settings', $defaults );
} );