<?php

namespace SML;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Filters {
	public function __construct() {
		// AJAX query for media modal
		add_action( 'pre_get_posts', [ $this, 'filter_media_query' ], 9 );
		// REST attachments endpoint used by block editor and some builders
		add_filter( 'rest_attachment_query', [ $this, 'filter_rest_media_query' ], 9, 2 );
	}

	private function is_media_library_request( $query ) {
		if ( is_admin() && $query->is_main_query() ) {
			$p = $query->get( 'post_type' );
			if ( $p === 'attachment' || ( is_array( $p ) && in_array( 'attachment', $p, true ) ) ) {
				return true;
			}
		}
		return false;
	}

	public function filter_media_query( $query ) {
		if ( ! $this->is_media_library_request( $query ) ) {
			return;
		}

		// Respect mime type filter inside modal
		$mime_types = $query->get( 'post_mime_type' );
		if ( $mime_types && strpos( (string) $mime_types, 'image' ) === false ) {
			return; // only scope images
		}

		// Fallback: permit all for allowed users
		if ( \sml_fallback_enabled_for_user() ) {
			return;
		}

		$context = [
			'source' => 'pre_get_posts',
			'query_vars' => $query->query_vars,
		];
		$rules = \sml_get_current_rules( $context );
		$this->apply_dimension_meta_query( $query, $rules );
	}

	public function filter_rest_media_query( $args, $request ) {
		// Only for attachments of image type
		if ( ! empty( $args['post_mime_type'] ) && strpos( (string) $args['post_mime_type'], 'image' ) === false ) {
			return $args;
		}

		if ( \sml_fallback_enabled_for_user() ) {
			return $args;
		}

		$context = [
			'source' => 'rest',
			'params' => $request ? $request->get_params() : [],
		];
		$rules = \sml_get_current_rules( $context );

		// Translate to meta_query constraints
		if ( empty( $args['meta_query'] ) ) {
			$args['meta_query'] = [];
		}
		$args['meta_query'][] = $this->build_meta_query_clause( '_sml_width', 'min', $rules['min_width'] );
		$args['meta_query'][] = $this->build_meta_query_clause( '_sml_width', 'max', $rules['max_width'] );
		$args['meta_query'][] = $this->build_meta_query_clause( '_sml_height', 'min', $rules['min_height'] );
		$args['meta_query'][] = $this->build_meta_query_clause( '_sml_height', 'max', $rules['max_height'] );
		$args['meta_query'] = array_values( array_filter( $args['meta_query'] ) );
		if ( ! empty( $args['meta_query'] ) ) {
			$args['meta_query']['relation'] = 'AND';
		}
		return $args;
	}

	private function apply_dimension_meta_query( $query, $rules ) {
		$meta_query = (array) $query->get( 'meta_query', [] );
		$meta_query[] = $this->build_meta_query_clause( '_sml_width', 'min', $rules['min_width'] );
		$meta_query[] = $this->build_meta_query_clause( '_sml_width', 'max', $rules['max_width'] );
		$meta_query[] = $this->build_meta_query_clause( '_sml_height', 'min', $rules['min_height'] );
		$meta_query[] = $this->build_meta_query_clause( '_sml_height', 'max', $rules['max_height'] );
		$meta_query = array_values( array_filter( $meta_query ) );
		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$query->set( 'meta_query', $meta_query );
		}

		// Ensure we only fetch images
		$query->set( 'post_mime_type', 'image' );
	}

	private function build_meta_query_clause( $meta_key, $bound, $value ) {
		if ( $value === null || $value === '' ) {
			return null;
		}
		$compare = $bound === 'min' ? '>=' : '<=';
		return [
			'key'     => $meta_key,
			'value'   => intval( $value ),
			'compare' => $compare,
			'type'    => 'NUMERIC',
		];
	}
}

