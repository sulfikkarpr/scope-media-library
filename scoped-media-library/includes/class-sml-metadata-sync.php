<?php
/**
 * Image metadata synchronization functionality.
 *
 * @package    Scoped_Media_Library
 * @subpackage Scoped_Media_Library/includes
 */

class SML_Metadata_Sync {

    /**
     * Sync image dimensions for a single attachment.
     *
     * @since    1.0.0
     * @param    int    $attachment_id    The attachment ID.
     */
    public function sync_image_dimensions( $attachment_id ) {
        // Only process images
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            return;
        }

        // Get image metadata
        $metadata = wp_get_attachment_metadata( $attachment_id );

        if ( empty( $metadata ) || ! isset( $metadata['width'] ) || ! isset( $metadata['height'] ) ) {
            return;
        }

        // Store dimensions in separate meta fields for efficient querying
        update_post_meta( $attachment_id, '_sml_width', absint( $metadata['width'] ) );
        update_post_meta( $attachment_id, '_sml_height', absint( $metadata['height'] ) );
        update_post_meta( $attachment_id, '_sml_synced', time() );
    }

    /**
     * Bulk sync dimensions for all images in the media library.
     *
     * @since    1.0.0
     */
    public function bulk_sync_dimensions() {
        global $wpdb;

        // Get all image attachments
        $attachments = get_posts( array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ) );

        $synced_count = 0;

        foreach ( $attachments as $attachment_id ) {
            $this->sync_image_dimensions( $attachment_id );
            $synced_count++;
        }

        // Store sync result
        update_option( 'sml_last_sync', array(
            'time'  => time(),
            'count' => $synced_count
        ) );

        return $synced_count;
    }

    /**
     * AJAX handler for bulk sync.
     *
     * @since    1.0.0
     */
    public static function ajax_bulk_sync() {
        // Check nonce
        check_ajax_referer( 'sml_sync_nonce', 'nonce' );

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'scoped-media-library' ) ) );
        }

        // Create instance and run sync
        $sync = new self();
        $count = $sync->bulk_sync_dimensions();

        wp_send_json_success( array(
            'message' => sprintf(
                /* translators: %d: number of images synced */
                _n(
                    '%d image dimension synced successfully.',
                    '%d image dimensions synced successfully.',
                    $count,
                    'scoped-media-library'
                ),
                $count
            ),
            'count' => $count
        ) );
    }
}

// Register AJAX handler
add_action( 'wp_ajax_sml_bulk_sync', array( 'SML_Metadata_Sync', 'ajax_bulk_sync' ) );