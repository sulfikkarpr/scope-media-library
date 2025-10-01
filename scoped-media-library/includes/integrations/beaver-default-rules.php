<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Toggleable defaults for Beaver Builder rules (no admin UI)
if ( ! defined( 'SML_BB_ENABLE_EXACT_RULE' ) ) {
	define( 'SML_BB_ENABLE_EXACT_RULE', false ); // set true to enable exact size rule
}
if ( ! defined( 'SML_BB_EXACT_WIDTH' ) ) {
	define( 'SML_BB_EXACT_WIDTH', 150 );
}
if ( ! defined( 'SML_BB_EXACT_HEIGHT' ) ) {
	define( 'SML_BB_EXACT_HEIGHT', 60 );
}

if ( ! defined( 'SML_BB_ENABLE_MIN_WIDTH_RULE' ) ) {
	define( 'SML_BB_ENABLE_MIN_WIDTH_RULE', false ); // set true to enable min-width rule
}
if ( ! defined( 'SML_BB_MIN_WIDTH' ) ) {
	define( 'SML_BB_MIN_WIDTH', 1920 );
}

// Apply example rules when Beaver Builder is active
add_filter( 'sml/beaver/get_dimension_rules', function( $rules, $args ) {
	if ( ! class_exists( 'FLBuilderModel' ) || ! FLBuilderModel::is_builder_active() ) {
		return $rules;
	}

	// Exact size rule takes precedence if enabled
	if ( SML_BB_ENABLE_EXACT_RULE ) {
		return array(
			'min_width'  => (int) SML_BB_EXACT_WIDTH,
			'max_width'  => (int) SML_BB_EXACT_WIDTH,
			'min_height' => (int) SML_BB_EXACT_HEIGHT,
			'max_height' => (int) SML_BB_EXACT_HEIGHT,
		);
	}

	// Fallback: minimum width rule
	if ( SML_BB_ENABLE_MIN_WIDTH_RULE ) {
		return array(
			'min_width'  => (int) SML_BB_MIN_WIDTH,
			'max_width'  => null,
			'min_height' => null,
			'max_height' => null,
		);
	}

	return $rules;
}, 5, 2 );

