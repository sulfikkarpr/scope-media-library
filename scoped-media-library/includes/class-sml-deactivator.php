<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    Scoped_Media_Library
 * @subpackage Scoped_Media_Library/includes
 */

class SML_Deactivator {

    /**
     * Deactivation hook.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear any scheduled events
        $timestamp = wp_next_scheduled( 'sml_sync_dimensions' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'sml_sync_dimensions' );
        }
    }
}