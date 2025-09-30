<?php
/**
 * Fired during plugin activation.
 *
 * @package    Scoped_Media_Library
 * @subpackage Scoped_Media_Library/includes
 */

class SML_Activator {

    /**
     * Activation hook.
     * 
     * Sets default options and ensures image dimensions are stored.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Set default options
        $default_options = array(
            'enabled' => true,
            'min_width' => '',
            'max_width' => '',
            'min_height' => '',
            'max_height' => '',
            'fallback_mode' => false,
            'fallback_roles' => array( 'administrator' ),
        );

        if ( ! get_option( 'sml_settings' ) ) {
            add_option( 'sml_settings', $default_options );
        }

        // Schedule a one-time event to sync existing image dimensions
        if ( ! wp_next_scheduled( 'sml_sync_dimensions' ) ) {
            wp_schedule_single_event( time() + 10, 'sml_sync_dimensions' );
        }
    }
}