<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SML_Integration_ACF {
	public static function maybe_boot() {
		if ( ! function_exists( 'acf' ) ) {
			return;
		}
		new static();
	}

	public function __construct() {
		// Add per-field settings to ACF image / gallery fields
		add_action( 'acf/render_field_settings/type=image', array( $this, 'add_field_settings' ) );
		add_action( 'acf/render_field_settings/type=gallery', array( $this, 'add_field_settings' ) );

		// Filter media modal for ACF context based on field settings
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_acf_media_query' ), 20 );

		// Prefer ACF field-specific query filters for reliable scoping
		add_filter( 'acf/fields/image/query', array( $this, 'acf_field_query' ), 20, 3 );
		add_filter( 'acf/fields/gallery/query', array( $this, 'acf_field_query' ), 20, 3 );

		// As a safety net, scope the core WP_Query for attachments in ACF modal
		add_action( 'pre_get_posts', array( $this, 'maybe_scope_pre_get_posts' ), 20 );

		// Validate selected image on save against field rules
		add_filter( 'acf/validate_value/type=image', array( $this, 'validate_field_value' ), 20, 4 );
		add_filter( 'acf/validate_value/type=gallery', array( $this, 'validate_gallery_value' ), 20, 4 );
	}

	/**
	 * Add SML settings to ACF field settings UI.
	 */
	public function add_field_settings( $field ) {
		// Toggle to enable scoping per field
		acf_render_field_setting( $field, array(
			'label' => __( 'Scoped Media: Enable', 'scoped-media-library' ),
			'instructions' => __( 'Limit selectable images by dimensions for this field.', 'scoped-media-library' ),
			'name' => 'sml_enable',
			'type' => 'true_false',
			'ui' => 1,
		) );

		// Dimension fields
		acf_render_field_setting( $field, array(
			'label' => __( 'Min Width (px)', 'scoped-media-library' ),
			'name' => 'sml_min_width',
			'type' => 'number',
			'min' => 0,
			'conditions' => array(
				'rule' => 'sml_enable',
				'operator' => '==',
				'value' => 1,
			),
		) );
		acf_render_field_setting( $field, array(
			'label' => __( 'Max Width (px)', 'scoped-media-library' ),
			'name' => 'sml_max_width',
			'type' => 'number',
			'min' => 0,
			'conditions' => array(
				'rule' => 'sml_enable',
				'operator' => '==',
				'value' => 1,
			),
		) );
		acf_render_field_setting( $field, array(
			'label' => __( 'Min Height (px)', 'scoped-media-library' ),
			'name' => 'sml_min_height',
			'type' => 'number',
			'min' => 0,
			'conditions' => array(
				'rule' => 'sml_enable',
				'operator' => '==',
				'value' => 1,
			),
		) );
		acf_render_field_setting( $field, array(
			'label' => __( 'Max Height (px)', 'scoped-media-library' ),
			'name' => 'sml_max_height',
			'type' => 'number',
			'min' => 0,
			'conditions' => array(
				'rule' => 'sml_enable',
				'operator' => '==',
				'value' => 1,
			),
		) );
	}

	/**
	 * Filter media query when ACF opens the modal for a specific field with rules.
	 */
	public function filter_acf_media_query( $args ) {
		if ( empty( $_POST['query'] ) ) {
			return $args;
		}

		// Detect ACF field key reliably
		$field_key = '';
		if ( isset( $_POST['query']['acf_field_key'] ) ) {
			$field_key = sanitize_text_field( wp_unslash( $_POST['query']['acf_field_key'] ) );
		} elseif ( isset( $_POST['query']['field_key'] ) ) {
			$field_key = sanitize_text_field( wp_unslash( $_POST['query']['field_key'] ) );
		}
		if ( ! $field_key ) {
			return $args;
		}

		$field = function_exists( 'acf_get_field' ) ? acf_get_field( $field_key ) : null;
		if ( ! $field && function_exists( 'get_field_object' ) ) {
			$field = get_field_object( $field_key );
		}
		if ( ! $field ) {
			return $args;
		}

		$rules = $this->extract_rules_from_field( $field );
		if ( ! $rules ) {
			return $args;
		}

		$meta_query = $this->build_meta_query_from_rules( $rules );
		if ( count( $meta_query ) > 1 ) {
			// Ensure attachments + images
			$args['post_type'] = 'attachment';
			$args['post_mime_type'] = 'image';
			$args['meta_query'] = $this->merge_meta_queries( $args, $meta_query );
		}

		return $args;
	}

	/**
	 * Apply scoping using ACF field-specific query filters.
	 *
	 * @param array $args
	 * @param array $field
	 * @param mixed $post_id
	 * @return array
	 */
	public function acf_field_query( $args, $field, $post_id ) {
		$rules = $this->extract_rules_from_field( $field );
		if ( ! $rules ) {
			return $args;
		}

		$meta_query = $this->build_meta_query_from_rules( $rules );
		if ( count( $meta_query ) > 1 ) {
			$args['post_type'] = 'attachment';
			$args['post_mime_type'] = 'image';
			$args['meta_query'] = $this->merge_meta_queries( $args, $meta_query );
		}

		return $args;
	}

	/**
	 * Scope the core WP_Query for attachments during ACF media modal AJAX.
	 */
	public function maybe_scope_pre_get_posts( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( function_exists( 'wp_doing_ajax' ) && ! wp_doing_ajax() ) {
			return;
		}
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';
		if ( 'query-attachments' !== $action ) {
			return;
		}

		// Attempt to detect ACF field key
		$field_key = '';
		if ( isset( $_POST['query']['acf_field_key'] ) ) {
			$field_key = sanitize_text_field( wp_unslash( $_POST['query']['acf_field_key'] ) );
		} elseif ( isset( $_POST['query']['field_key'] ) ) {
			$field_key = sanitize_text_field( wp_unslash( $_POST['query']['field_key'] ) );
		}
		if ( ! $field_key ) {
			return;
		}

		$field = function_exists( 'acf_get_field' ) ? acf_get_field( $field_key ) : null;
		if ( ! $field && function_exists( 'get_field_object' ) ) {
			$field = get_field_object( $field_key );
		}
		if ( ! $field ) {
			return;
		}

		$rules = $this->extract_rules_from_field( $field );
		if ( ! $rules ) {
			return;
		}

		$meta_query = $this->build_meta_query_from_rules( $rules );
		if ( count( $meta_query ) > 1 ) {
			$existing = $query->get( 'meta_query' );
			if ( empty( $existing ) ) {
				$query->set( 'meta_query', $meta_query );
			} else {
				$combined = array( 'relation' => 'AND' );
				foreach ( $meta_query as $key => $clause ) {
					if ( 'relation' === $key ) { continue; }
					$combined[] = $clause;
				}
				foreach ( $existing as $key => $clause ) {
					if ( 'relation' === $key ) { continue; }
					$combined[] = $clause;
				}
				$query->set( 'meta_query', $combined );
			}
			$query->set( 'post_type', 'attachment' );
			$query->set( 'post_mime_type', 'image' );
		}
	}

	/**
	 * Validate selected image against rules on save for image field
	 */
	public function validate_field_value( $valid, $value, $field, $input ) {
		if ( $valid !== true ) {
			return $valid;
		}
		if ( empty( $value ) ) {
			return $valid;
		}
		$rules = $this->extract_rules_from_field( $field );
		if ( ! $rules ) {
			return $valid;
		}
		if ( ! $this->attachment_matches_rules( (int) $value, $rules ) ) {
			return __( 'Selected image does not meet the required dimensions for this field.', 'scoped-media-library' );
		}
		return $valid;
	}

	/**
	 * Validate selected images for gallery field
	 */
	public function validate_gallery_value( $valid, $value, $field, $input ) {
		if ( $valid !== true ) {
			return $valid;
		}
		if ( empty( $value ) || ! is_array( $value ) ) {
			return $valid;
		}
		$rules = $this->extract_rules_from_field( $field );
		if ( ! $rules ) {
			return $valid;
		}
		foreach ( $value as $attachment_id ) {
			if ( ! $this->attachment_matches_rules( (int) $attachment_id, $rules ) ) {
				return __( 'One or more selected images do not meet the required dimensions for this field.', 'scoped-media-library' );
			}
		}
		return $valid;
	}

	/**
	 * Build meta_query from rules
	 */
	protected function build_meta_query_from_rules( $rules ) {
		$meta_query = array( 'relation' => 'AND' );
		if ( isset( $rules['min_width'] ) && null !== $rules['min_width'] ) {
			$meta_query[] = array( 'key' => '_sml_width', 'value' => (int) $rules['min_width'], 'compare' => '>=', 'type' => 'NUMERIC' );
		}
		if ( isset( $rules['max_width'] ) && null !== $rules['max_width'] ) {
			$meta_query[] = array( 'key' => '_sml_width', 'value' => (int) $rules['max_width'], 'compare' => '<=', 'type' => 'NUMERIC' );
		}
		if ( isset( $rules['min_height'] ) && null !== $rules['min_height'] ) {
			$meta_query[] = array( 'key' => '_sml_height', 'value' => (int) $rules['min_height'], 'compare' => '>=', 'type' => 'NUMERIC' );
		}
		if ( isset( $rules['max_height'] ) && null !== $rules['max_height'] ) {
			$meta_query[] = array( 'key' => '_sml_height', 'value' => (int) $rules['max_height'], 'compare' => '<=', 'type' => 'NUMERIC' );
		}
		return $meta_query;
	}

	/**
	 * Merge existing and new meta queries with AND relation
	 */
	protected function merge_meta_queries( $args, $new_meta_query ) {
		if ( empty( $args['meta_query'] ) ) {
			return $new_meta_query;
		}
		$combined = array( 'relation' => 'AND' );
		foreach ( $new_meta_query as $key => $clause ) {
			if ( 'relation' === $key ) continue;
			$combined[] = $clause;
		}
		foreach ( $args['meta_query'] as $key => $clause ) {
			if ( 'relation' === $key ) continue;
			$combined[] = $clause;
		}
		return $combined;
	}

	/**
	 * Helper to verify an attachment meets rules
	 */
	protected function attachment_matches_rules( $attachment_id, $rules ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return false;
		}
		$width = (int) get_post_meta( $attachment_id, '_sml_width', true );
		$height = (int) get_post_meta( $attachment_id, '_sml_height', true );
		if ( $rules['min_width'] !== null && $width < (int) $rules['min_width'] ) return false;
		if ( $rules['max_width'] !== null && $width > (int) $rules['max_width'] ) return false;
		if ( $rules['min_height'] !== null && $height < (int) $rules['min_height'] ) return false;
		if ( $rules['max_height'] !== null && $height > (int) $rules['max_height'] ) return false;
		return true;
	}

	/**
	 * Extract rules from ACF field configuration, preferring native validation rules.
	 * Falls back to plugin-specific per-field settings when present.
	 */
	protected function extract_rules_from_field( $field ) {
		$has_native = ( isset( $field['min_width'] ) || isset( $field['max_width'] ) || isset( $field['min_height'] ) || isset( $field['max_height'] ) );
		if ( $has_native ) {
			$rules = array(
				'min_width'  => ( isset( $field['min_width'] ) && '' !== $field['min_width'] ) ? (int) $field['min_width'] : null,
				'max_width'  => ( isset( $field['max_width'] ) && '' !== $field['max_width'] ) ? (int) $field['max_width'] : null,
				'min_height' => ( isset( $field['min_height'] ) && '' !== $field['min_height'] ) ? (int) $field['min_height'] : null,
				'max_height' => ( isset( $field['max_height'] ) && '' !== $field['max_height'] ) ? (int) $field['max_height'] : null,
			);
			if ( $rules['min_width'] !== null || $rules['max_width'] !== null || $rules['min_height'] !== null || $rules['max_height'] !== null ) {
				return $rules;
			}
		}

		if ( ! empty( $field['sml_enable'] ) ) {
			$rules = array(
				'min_width'  => ( isset( $field['sml_min_width'] ) && '' !== $field['sml_min_width'] ) ? (int) $field['sml_min_width'] : null,
				'max_width'  => ( isset( $field['sml_max_width'] ) && '' !== $field['sml_max_width'] ) ? (int) $field['sml_max_width'] : null,
				'min_height' => ( isset( $field['sml_min_height'] ) && '' !== $field['sml_min_height'] ) ? (int) $field['sml_min_height'] : null,
				'max_height' => ( isset( $field['sml_max_height'] ) && '' !== $field['sml_max_height'] ) ? (int) $field['sml_max_height'] : null,
			);
			if ( $rules['min_width'] !== null || $rules['max_width'] !== null || $rules['min_height'] !== null || $rules['max_height'] !== null ) {
				return $rules;
			}
		}

		return null;
	}
}

