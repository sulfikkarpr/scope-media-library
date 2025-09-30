<?php
/**
 * Media library filtering functionality.
 *
 * @package    Scoped_Media_Library
 * @subpackage Scoped_Media_Library/includes
 */

class SML_Media_Filter {

    /**
     * Filter the media library query.
     *
     * @since    1.0.0
     * @param    array    $query    The query arguments.
     * @return   array              Modified query arguments.
     */
    public function filter_media_library_query( $query ) {
        // Get settings
        $settings = get_option( 'sml_settings', array() );
        
        // Check if filtering is enabled
        if ( empty( $settings['enabled'] ) ) {
            return $query;
        }

        // Check if fallback mode is enabled for current user
        if ( ! empty( $settings['fallback_mode'] ) && $this->user_has_fallback_access( $settings ) ) {
            return $query;
        }

        // Get dimension constraints
        $min_width = ! empty( $settings['min_width'] ) ? absint( $settings['min_width'] ) : null;
        $max_width = ! empty( $settings['max_width'] ) ? absint( $settings['max_width'] ) : null;
        $min_height = ! empty( $settings['min_height'] ) ? absint( $settings['min_height'] ) : null;
        $max_height = ! empty( $settings['max_height'] ) ? absint( $settings['max_height'] ) : null;

        // If no constraints are set, return unmodified query
        if ( $min_width === null && $max_width === null && $min_height === null && $max_height === null ) {
            return $query;
        }

        // Build meta query for dimensions
        $meta_query = isset( $query['meta_query'] ) ? $query['meta_query'] : array();
        
        // Ensure we have an array
        if ( ! is_array( $meta_query ) ) {
            $meta_query = array();
        }

        // Add dimension constraints
        $dimension_queries = array();

        if ( $min_width !== null ) {
            $dimension_queries[] = array(
                'key' => '_wp_attachment_metadata',
                'value' => sprintf( '"width";i:%d', $min_width - 1 ),
                'compare' => 'NOT LIKE'
            );
            $dimension_queries[] = array(
                'key' => '_wp_attachment_metadata',
                'value' => sprintf( '"width";i:%d', $min_width ),
                'compare' => 'LIKE'
            );
        }

        if ( $max_width !== null ) {
            // We need to filter by post IDs after query for max constraints
            add_filter( 'posts_where', array( $this, 'filter_by_max_dimensions' ), 10, 2 );
        }

        // For more precise filtering, we'll use a custom WHERE clause
        add_filter( 'posts_where', array( $this, 'filter_dimensions_where_clause' ), 10, 2 );

        return $query;
    }

    /**
     * Add custom WHERE clause for dimension filtering.
     *
     * @since    1.0.0
     */
    public function filter_dimensions_where_clause( $where, $query ) {
        global $wpdb;

        // Only apply to media library queries
        if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
            return $where;
        }

        // Get settings
        $settings = get_option( 'sml_settings', array() );
        
        if ( empty( $settings['enabled'] ) ) {
            return $where;
        }

        // Check if fallback mode is enabled for current user
        if ( ! empty( $settings['fallback_mode'] ) && $this->user_has_fallback_access( $settings ) ) {
            return $where;
        }

        // Get dimension constraints
        $min_width = ! empty( $settings['min_width'] ) ? absint( $settings['min_width'] ) : null;
        $max_width = ! empty( $settings['max_width'] ) ? absint( $settings['max_width'] ) : null;
        $min_height = ! empty( $settings['min_height'] ) ? absint( $settings['min_height'] ) : null;
        $max_height = ! empty( $settings['max_height'] ) ? absint( $settings['max_height'] ) : null;

        // Build subquery for dimension filtering
        $subquery_parts = array();

        if ( $min_width !== null || $max_width !== null || $min_height !== null || $max_height !== null ) {
            $subquery = "
                AND {$wpdb->posts}.ID IN (
                    SELECT post_id 
                    FROM {$wpdb->postmeta} AS pm1
                    WHERE pm1.meta_key = '_sml_width'
            ";

            if ( $min_width !== null && $max_width !== null ) {
                $subquery .= $wpdb->prepare( " AND CAST(pm1.meta_value AS UNSIGNED) BETWEEN %d AND %d", $min_width, $max_width );
            } elseif ( $min_width !== null ) {
                $subquery .= $wpdb->prepare( " AND CAST(pm1.meta_value AS UNSIGNED) >= %d", $min_width );
            } elseif ( $max_width !== null ) {
                $subquery .= $wpdb->prepare( " AND CAST(pm1.meta_value AS UNSIGNED) <= %d", $max_width );
            }

            $subquery .= "
                )
            ";

            // Add height constraint if specified
            if ( $min_height !== null || $max_height !== null ) {
                $subquery .= "
                    AND {$wpdb->posts}.ID IN (
                        SELECT post_id 
                        FROM {$wpdb->postmeta} AS pm2
                        WHERE pm2.meta_key = '_sml_height'
                ";

                if ( $min_height !== null && $max_height !== null ) {
                    $subquery .= $wpdb->prepare( " AND CAST(pm2.meta_value AS UNSIGNED) BETWEEN %d AND %d", $min_height, $max_height );
                } elseif ( $min_height !== null ) {
                    $subquery .= $wpdb->prepare( " AND CAST(pm2.meta_value AS UNSIGNED) >= %d", $min_height );
                } elseif ( $max_height !== null ) {
                    $subquery .= $wpdb->prepare( " AND CAST(pm2.meta_value AS UNSIGNED) <= %d", $max_height );
                }

                $subquery .= "
                    )
                ";
            }

            $where .= $subquery;
        }

        // Remove this filter to prevent it from running multiple times
        remove_filter( 'posts_where', array( $this, 'filter_dimensions_where_clause' ), 10 );

        return $where;
    }

    /**
     * Filter by maximum dimensions (fallback method).
     *
     * @since    1.0.0
     */
    public function filter_by_max_dimensions( $where, $query ) {
        // Remove this filter after first use
        remove_filter( 'posts_where', array( $this, 'filter_by_max_dimensions' ), 10 );
        return $where;
    }

    /**
     * Check if current user has fallback access.
     *
     * @since    1.0.0
     * @param    array    $settings    Plugin settings.
     * @return   bool                  Whether user has fallback access.
     */
    private function user_has_fallback_access( $settings ) {
        if ( empty( $settings['fallback_roles'] ) ) {
            return false;
        }

        $user = wp_get_current_user();
        
        if ( ! $user ) {
            return false;
        }

        foreach ( $settings['fallback_roles'] as $role ) {
            if ( in_array( $role, $user->roles, true ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add dimensions column to media library.
     *
     * @since    1.0.0
     * @param    array    $columns    Existing columns.
     * @return   array                Modified columns.
     */
    public function add_dimensions_column( $columns ) {
        $columns['sml_dimensions'] = __( 'Dimensions', 'scoped-media-library' );
        return $columns;
    }

    /**
     * Display dimensions in custom column.
     *
     * @since    1.0.0
     * @param    string    $column_name    The column name.
     * @param    int       $post_id        The attachment ID.
     */
    public function display_dimensions_column( $column_name, $post_id ) {
        if ( $column_name === 'sml_dimensions' ) {
            $width = get_post_meta( $post_id, '_sml_width', true );
            $height = get_post_meta( $post_id, '_sml_height', true );

            if ( $width && $height ) {
                echo esc_html( $width . ' × ' . $height . ' px' );
            } elseif ( wp_attachment_is_image( $post_id ) ) {
                // Try to get dimensions from metadata
                $metadata = wp_get_attachment_metadata( $post_id );
                if ( ! empty( $metadata['width'] ) && ! empty( $metadata['height'] ) ) {
                    echo esc_html( $metadata['width'] . ' × ' . $metadata['height'] . ' px' );
                    
                    // Update our custom meta for faster future queries
                    update_post_meta( $post_id, '_sml_width', $metadata['width'] );
                    update_post_meta( $post_id, '_sml_height', $metadata['height'] );
                } else {
                    echo '<span style="color: #999;">' . esc_html__( 'N/A', 'scoped-media-library' ) . '</span>';
                }
            } else {
                echo '<span style="color: #999;">' . esc_html__( 'Not an image', 'scoped-media-library' ) . '</span>';
            }
        }
    }
}