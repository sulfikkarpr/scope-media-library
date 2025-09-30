<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Scoped_Media_Library {

	/**
	 * Singleton instance
	 *
	 * @var Scoped_Media_Library|null
	 */
	protected static $instance = null;

	/**
	 * Option key for settings
	 *
	 * @var string
	 */
	protected $option_key = 'scoped_media_library_settings';

	/**
	 * Get singleton instance
	 *
	 * @return Scoped_Media_Library
	 */
	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Constructor - register hooks
	 */
	protected function __construct() {
		// Settings UI
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Metadata sync for images (width/height)
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'sync_dimensions_metadata' ), 20, 2 );

		// Filter media modal (used by ACF, Beaver Builder, etc.)
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_ajax_query_attachments' ) );

		// Filter Admin Media Library list/grid
		add_action( 'pre_get_posts', array( $this, 'filter_admin_media_library_query' ) );
	}

	/**
	 * Retrieve plugin settings with safe defaults.
	 *
	 * @return array{min_width:int|null,max_width:int|null,min_height:int|null,max_height:int|null,fallback_enabled:bool}
	 */
	public function get_settings() {
		$defaults = array(
			'min_width' => null,
			'max_width' => null,
			'min_height' => null,
			'max_height' => null,
			'fallback_enabled' => true,
		);

		$settings = get_option( $this->option_key, array() );
		$settings = is_array( $settings ) ? $settings : array();
		$merged = wp_parse_args( $settings, $defaults );

		// Normalize to ints or null, and bool for fallback
		$normalize_int = function( $value ) {
			if ( '' === $value || null === $value ) {
				return null;
			}
			return max( 0, (int) $value );
		};

		return array(
			'min_width' => $normalize_int( $merged['min_width'] ),
			'max_width' => $normalize_int( $merged['max_width'] ] ),
			'min_height' => $normalize_int( $merged['min_height'] ),
			'max_height' => $normalize_int( $merged['max_height'] ),
			'fallback_enabled' => ! empty( $merged['fallback_enabled'] ),
		);
	}

	/**
	 * Determine whether current user can bypass filtering (fallback mode).
	 * Defaults to administrators (manage_options) when fallback is enabled.
	 *
	 * @return bool
	 */
	public function user_can_bypass_filter() {
		$settings = $this->get_settings();
		$can_bypass = (bool) ( $settings['fallback_enabled'] && current_user_can( 'manage_options' ) );
		/**
		 * Filter: Allow developers to customize who can bypass filtering.
		 *
		 * @param bool $can_bypass Default decision.
		 */
		return (bool) apply_filters( 'scoped_media_library/user_can_bypass', $can_bypass );
	}

	/**
	 * Build meta_query array based on rules
	 *
	 * @param array $rules { min_width, max_width, min_height, max_height }
	 * @return array
	 */
	protected function build_meta_query( $rules ) {
		$meta_query = array( 'relation' => 'AND' );

		if ( isset( $rules['min_width'] ) && null !== $rules['min_width'] ) {
			$meta_query[] = array(
				'key' => '_sml_width',
				'value' => (int) $rules['min_width'],
				'compare' => '>=',
				'type' => 'NUMERIC',
			);
		}

		if ( isset( $rules['max_width'] ) && null !== $rules['max_width'] ) {
			$meta_query[] = array(
				'key' => '_sml_width',
				'value' => (int) $rules['max_width'],
				'compare' => '<=',
				'type' => 'NUMERIC',
			);
		}

		if ( isset( $rules['min_height'] ) && null !== $rules['min_height'] ) {
			$meta_query[] = array(
				'key' => '_sml_height',
				'value' => (int) $rules['min_height'],
				'compare' => '>=',
				'type' => 'NUMERIC',
			);
		}

		if ( isset( $rules['max_height'] ) && null !== $rules['max_height'] ) {
			$meta_query[] = array(
				'key' => '_sml_height',
				'value' => (int) $rules['max_height'],
				'compare' => '<=',
				'type' => 'NUMERIC',
			);
		}

		return $meta_query;
	}

	/**
	 * Metadata sync on upload/regen: store _sml_width and _sml_height
	 *
	 * @param array $metadata Attachment metadata
	 * @param int $attachment_id Attachment ID
	 * @return array
	 */
	public function sync_dimensions_metadata( $metadata, $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return $metadata;
		}

		$width = ! empty( $metadata['width'] ) ? (int) $metadata['width'] : null;
		$height = ! empty( $metadata['height'] ) ? (int) $metadata['height'] : null;

		if ( ( null === $width || null === $height ) ) {
			$file = get_attached_file( $attachment_id );
			if ( $file && file_exists( $file ) ) {
				$size = @getimagesize( $file );
				if ( is_array( $size ) && isset( $size[0], $size[1] ) ) {
					$width = (int) $size[0];
					$height = (int) $size[1];
				}
			}
		}

		if ( null !== $width ) {
			update_post_meta( $attachment_id, '_sml_width', $width );
		}
		if ( null !== $height ) {
			update_post_meta( $attachment_id, '_sml_height', $height );
		}

		return $metadata;
	}

	/**
	 * Filter the attachments shown in the media modal (AJAX query)
	 *
	 * @param array $args
	 * @return array
	 */
	public function filter_ajax_query_attachments( $args ) {
		if ( $this->user_can_bypass_filter() ) {
			return $args;
		}

		$rules = $this->get_settings();
		/**
		 * Filter: Allow developers to override rules per-context (e.g., per field)
		 *
		 * @param array $rules The dimension rules.
		 * @param array $args  The AJAX query args from the media modal.
		 */
		$rules = apply_filters( 'scoped_media_library/get_dimension_rules', $rules, $args );

		$meta_query = $this->build_meta_query( $rules );

		// Only apply if at least one rule is set
		if ( count( $meta_query ) > 1 ) {
			if ( empty( $args['meta_query'] ) ) {
				$args['meta_query'] = $meta_query;
			} else {
				$args['meta_query'] = array(
					'relation' => 'AND',
					$meta_query,
					$args['meta_query'],
				);
			}
			$args['post_mime_type'] = 'image';
		}

		return $args;
	}

	/**
	 * Filter the Admin Media Library queries (list and grid views)
	 *
	 * @param WP_Query $query
	 */
	public function filter_admin_media_library_query( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Ensure we are targeting attachments
		$post_type = $query->get( 'post_type' );
		if ( 'attachment' !== $post_type && ! ( is_array( $post_type ) && in_array( 'attachment', $post_type, true ) ) ) {
			return;
		}

		if ( $this->user_can_bypass_filter() ) {
			return;
		}

		$rules = $this->get_settings();
		$rules = apply_filters( 'scoped_media_library/get_dimension_rules', $rules, array( 'context' => 'admin_media_library' ) );
		$meta_query = $this->build_meta_query( $rules );

		if ( count( $meta_query ) > 1 ) {
			$existing_meta = $query->get( 'meta_query' );
			if ( empty( $existing_meta ) ) {
				$query->set( 'meta_query', $meta_query );
			} else {
				$query->set( 'meta_query', array(
					'relation' => 'AND',
					$meta_query,
					$existing_meta,
				) );
			}
			$query->set( 'post_mime_type', 'image' );
		}
	}

	/**
	 * Register settings page under Settings → Scoped Media Library
	 */
	public function register_settings_page() {
		add_options_page(
			__( 'Scoped Media Library', 'scoped-media-library' ),
			__( 'Scoped Media Library', 'scoped-media-library' ),
			'manage_options',
			'scoped-media-library',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting( 'scoped_media_library', $this->option_key, array( $this, 'sanitize_settings' ) );

		add_settings_section(
			'sml_main',
			__( 'Dimension Rules', 'scoped-media-library' ),
			function() {
				echo '<p>' . esc_html__( 'Set the min/max width and height for images allowed in the media library selector.', 'scoped-media-library' ) . '</p>';
			},
			'scoped_media_library'
		);

		add_settings_field( 'min_width', __( 'Minimum Width (px)', 'scoped-media-library' ), array( $this, 'render_number_field' ), 'scoped_media_library', 'sml_main', array( 'key' => 'min_width' ) );
		add_settings_field( 'max_width', __( 'Maximum Width (px)', 'scoped-media-library' ), array( $this, 'render_number_field' ), 'scoped_media_library', 'sml_main', array( 'key' => 'max_width' ) );
		add_settings_field( 'min_height', __( 'Minimum Height (px)', 'scoped-media-library' ), array( $this, 'render_number_field' ), 'scoped_media_library', 'sml_main', array( 'key' => 'min_height' ) );
		add_settings_field( 'max_height', __( 'Maximum Height (px)', 'scoped-media-library' ), array( $this, 'render_number_field' ), 'scoped_media_library', 'sml_main', array( 'key' => 'max_height' ) );
		add_settings_field( 'fallback_enabled', __( 'Enable Fallback (admins see all)', 'scoped-media-library' ), array( $this, 'render_checkbox_field' ), 'scoped_media_library', 'sml_main', array( 'key' => 'fallback_enabled' ) );
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$keys = array( 'min_width', 'max_width', 'min_height', 'max_height' );
		foreach ( $keys as $key ) {
			if ( isset( $input[ $key ] ) && '' !== $input[ $key ] ) {
				$sanitized[ $key ] = max( 0, (int) $input[ $key ] );
			} else {
				$sanitized[ $key ] = null;
			}
		}

		$sanitized['fallback_enabled'] = ! empty( $input['fallback_enabled'] ) ? 1 : 0;

		return $sanitized;
	}

	/**
	 * Render number input field
	 *
	 * @param array $args
	 */
	public function render_number_field( $args ) {
		$key = $args['key'];
		$settings = $this->get_settings();
		$value = isset( $settings[ $key ] ) && null !== $settings[ $key ] ? (int) $settings[ $key ] : '';
		echo '<input type="number" min="0" step="1" name="' . esc_attr( $this->option_name_path( $key ) ) . '" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	/**
	 * Render checkbox field
	 *
	 * @param array $args
	 */
	public function render_checkbox_field( $args ) {
		$key = $args['key'];
		$settings = $this->get_settings();
		$checked = ! empty( $settings[ $key ] ) ? 'checked' : '';
		echo '<label><input type="checkbox" name="' . esc_attr( $this->option_name_path( $key ) ) . '" value="1" ' . $checked . ' /> ' . esc_html__( 'Allow administrators to bypass filtering and see all images.', 'scoped-media-library' ) . '</label>';
	}

	/**
	 * Helper to build option input names
	 *
	 * @param string $key
	 * @return string
	 */
	protected function option_name_path( $key ) {
		return $this->option_key . '[' . $key . ']';
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Scoped Media Library', 'scoped-media-library' ) . '</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'scoped_media_library' );
		do_settings_sections( 'scoped_media_library' );
		submit_button();
		echo '</form>';
		echo '</div>';
	}
}

