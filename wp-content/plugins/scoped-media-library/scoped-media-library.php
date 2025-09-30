<?php
/*
Plugin Name: Scoped Media Library – Filter Images by Dimensions
Description: Control which images appear in the media library selector by defining dimension rules (min/max width and height). Compatible with ACF, Beaver Builder, and the WP media modal. Includes optional fallback for admins and stores image dimensions on upload.
Version: 1.0.0
Author: Scoped Tools
License: GPLv2 or later
Text Domain: scoped-media-library
*/

// Exit if accessed directly.
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

// Simple autoloader for this plugin's classes
spl_autoload_register( function ( $class ) {
	if ( strpos( $class, 'SML\\' ) !== 0 ) {
		return;
	}
	$path = SML_PLUGIN_DIR . 'includes/' . str_replace( [ 'SML\\', '\\' ], [ '', '/' ], $class ) . '.php';
	if ( file_exists( $path ) ) {
		require_once $path;
	}
} );

// Activation: set defaults if not present
register_activation_hook( __FILE__, function () {
	$defaults = [
		'min_width'          => '',
		'min_height'         => '',
		'max_width'          => '',
		'max_height'         => '',
		'fallback_enabled'   => 1,
		'fallback_capability'=> 'manage_options',
	];
	$existing = get_option( 'sml_settings', [] );
	update_option( 'sml_settings', wp_parse_args( $existing, $defaults ) );
} );

add_action( 'plugins_loaded', function () {
	// Instantiate core components
	new SML\Admin();
	new SML\Metadata();
	new SML\Filters();
} );

// Public helper to fetch current rules (developers can filter)
function sml_get_current_rules( $context = [] ) {
	$settings = get_option( 'sml_settings', [] );
	$rules = [
		'min_width'  => isset( $settings['min_width'] ) ? intval( $settings['min_width'] ) : null,
		'min_height' => isset( $settings['min_height'] ) ? intval( $settings['min_height'] ) : null,
		'max_width'  => isset( $settings['max_width'] ) ? intval( $settings['max_width'] ) : null,
		'max_height' => isset( $settings['max_height'] ) ? intval( $settings['max_height'] ) : null,
	];

	/**
	 * Filter: allow developers to override rules per-field or context.
	 *
	 * @param array $rules   Array with keys min_width, min_height, max_width, max_height
	 * @param array $context Context of the query (e.g., request params, field key)
	 */
	return apply_filters( 'sml_current_rules', $rules, $context );
}

// Public helper to determine if fallback is allowed for current user
function sml_fallback_enabled_for_user() {
	$settings = get_option( 'sml_settings', [] );
	$enabled  = ! empty( $settings['fallback_enabled'] );
	$cap      = ! empty( $settings['fallback_capability'] ) ? $settings['fallback_capability'] : 'manage_options';

	$allow = $enabled && current_user_can( $cap );

	/**
	 * Filter: force-enable or disable fallback per request/user.
	 *
	 * @param bool  $allow   Whether fallback is allowed
	 * @param array $settings Plugin settings
	 */
	return apply_filters( 'sml_allow_fallback', $allow, $settings );
}

