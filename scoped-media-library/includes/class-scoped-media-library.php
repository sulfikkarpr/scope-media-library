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

    // Global options removed; scoping is integration-driven per-field/module.

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
		// Metadata sync for images (width/height)
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'sync_dimensions_metadata' ), 20, 2 );

		// Core no longer applies global filtering. Integrations will scope queries.
	}

	/**
	 * Global settings removed.
	 */
	public function get_settings() {
		return array(
			'min_width' => null,
			'max_width' => null,
			'min_height' => null,
			'max_height' => null,
			'fallback_enabled' => false,
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

	// Global query filters removed; integrations will apply scoping.

	// Settings UI removed
}

