<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SML_Integration_Beaver {
	public static function maybe_boot() {
		// Basic detection for Beaver Builder
		if ( ! class_exists( 'FLBuilderModel' ) ) {
			return;
		}
		new static();
	}

	public function __construct() {
		// Hook into media modal query for Beaver Builder context
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_beaver_media_query' ), 25 );
	}

	/**
	 * Filter media query when Beaver Builder opens the media modal for modules
	 * that define SML dimension rules via filter below.
	 */
	public function filter_beaver_media_query( $args ) {
		// Allow modules or site code to provide per-field rules.
		$rules = apply_filters( 'sml/beaver/get_dimension_rules', null, $args );
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return $args;
		}

		$rules = array(
			'min_width' => isset( $rules['min_width'] ) ? (int) $rules['min_width'] : null,
			'max_width' => isset( $rules['max_width'] ) ? (int) $rules['max_width'] : null,
			'min_height' => isset( $rules['min_height'] ) ? (int) $rules['min_height'] : null,
			'max_height' => isset( $rules['max_height'] ) ? (int) $rules['max_height'] : null,
		);

		$meta_query = array( 'relation' => 'AND' );
		if ( null !== $rules['min_width'] ) {
			$meta_query[] = array('key' => '_sml_width','value' => (int)$rules['min_width'],'compare' => '>=','type' => 'NUMERIC');
		}
		if ( null !== $rules['max_width'] ) {
			$meta_query[] = array('key' => '_sml_width','value' => (int)$rules['max_width'],'compare' => '<=','type' => 'NUMERIC');
		}
		if ( null !== $rules['min_height'] ) {
			$meta_query[] = array('key' => '_sml_height','value' => (int)$rules['min_height'],'compare' => '>=','type' => 'NUMERIC');
		}
		if ( null !== $rules['max_height'] ) {
			$meta_query[] = array('key' => '_sml_height','value' => (int)$rules['max_height'],'compare' => '<=','type' => 'NUMERIC');
		}

		if ( count( $meta_query ) > 1 ) {
			if ( empty( $args['meta_query'] ) ) {
				$args['meta_query'] = $meta_query;
			} else {
				$combined = array( 'relation' => 'AND' );
				foreach ( $meta_query as $key => $clause ) {
					if ( 'relation' === $key ) continue;
					$combined[] = $clause;
				}
				foreach ( $args['meta_query'] as $key => $clause ) {
					if ( 'relation' === $key ) continue;
					$combined[] = $clause;
				}
				$args['meta_query'] = $combined;
			}
			$args['post_mime_type'] = 'image';
		}

		return $args;
	}
}

