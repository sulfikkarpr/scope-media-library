<?php

namespace SML;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function register_settings_page() {
		add_options_page(
			__( 'Scoped Media Library', 'scoped-media-library' ),
			__( 'Scoped Media Library', 'scoped-media-library' ),
			'manage_options',
			'scoped-media-library',
			[ $this, 'render_settings_page' ]
		);
	}

	public function register_settings() {
		register_setting( 'sml_settings_group', 'sml_settings', [ $this, 'sanitize_settings' ] );

		add_settings_section(
			'sml_main_section',
			__( 'Dimension Rules', 'scoped-media-library' ),
			function () {
				echo '<p>' . esc_html__( 'Only images within these dimensions will appear in the media modal.', 'scoped-media-library' ) . '</p>';
			},
			'scoped-media-library'
		);

		add_settings_field( 'min_width', __( 'Minimum Width (px)', 'scoped-media-library' ), [ $this, 'field_number' ], 'scoped-media-library', 'sml_main_section', [ 'key' => 'min_width' ] );
		add_settings_field( 'min_height', __( 'Minimum Height (px)', 'scoped-media-library' ), [ $this, 'field_number' ], 'scoped-media-library', 'sml_main_section', [ 'key' => 'min_height' ] );
		add_settings_field( 'max_width', __( 'Maximum Width (px)', 'scoped-media-library' ), [ $this, 'field_number' ], 'scoped-media-library', 'sml_main_section', [ 'key' => 'max_width' ] );
		add_settings_field( 'max_height', __( 'Maximum Height (px)', 'scoped-media-library' ), [ $this, 'field_number' ], 'scoped-media-library', 'sml_main_section', [ 'key' => 'max_height' ] );

		add_settings_section(
			'sml_fallback_section',
			__( 'Fallback', 'scoped-media-library' ),
			function () {
				echo '<p>' . esc_html__( 'Allow privileged users to see all images in addition to scoped results.', 'scoped-media-library' ) . '</p>';
			},
			'scoped-media-library'
		);

		add_settings_field( 'fallback_enabled', __( 'Enable Fallback', 'scoped-media-library' ), [ $this, 'field_checkbox' ], 'scoped-media-library', 'sml_fallback_section', [ 'key' => 'fallback_enabled' ] );
		add_settings_field( 'fallback_capability', __( 'Fallback Capability', 'scoped-media-library' ), [ $this, 'field_text' ], 'scoped-media-library', 'sml_fallback_section', [ 'key' => 'fallback_capability', 'placeholder' => 'manage_options' ] );
	}

	public function sanitize_settings( $input ) {
		$sanitized = [];
		$sanitized['min_width']  = isset( $input['min_width'] ) ? max( 0, intval( $input['min_width'] ) ) : '';
		$sanitized['min_height'] = isset( $input['min_height'] ) ? max( 0, intval( $input['min_height'] ) ) : '';
		$sanitized['max_width']  = isset( $input['max_width'] ) ? max( 0, intval( $input['max_width'] ) ) : '';
		$sanitized['max_height'] = isset( $input['max_height'] ) ? max( 0, intval( $input['max_height'] ) ) : '';
		$sanitized['fallback_enabled'] = ! empty( $input['fallback_enabled'] ) ? 1 : 0;
		$sanitized['fallback_capability'] = isset( $input['fallback_capability'] ) && is_string( $input['fallback_capability'] ) ? sanitize_text_field( $input['fallback_capability'] ) : 'manage_options';
		return $sanitized;
	}

	public function field_number( $args ) {
		$options = get_option( 'sml_settings', [] );
		$key = $args['key'];
		$value = isset( $options[ $key ] ) ? intval( $options[ $key ] ) : '';
		echo '<input type="number" min="0" step="1" name="sml_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="small-text" />';
	}

	public function field_text( $args ) {
		$options = get_option( 'sml_settings', [] );
		$key = $args['key'];
		$placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
		$value = isset( $options[ $key ] ) ? $options[ $key ] : '';
		echo '<input type="text" name="sml_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="regular-text" />';
	}

	public function field_checkbox( $args ) {
		$options = get_option( 'sml_settings', [] );
		$key = $args['key'];
		$checked = ! empty( $options[ $key ] ) ? 'checked' : '';
		echo '<label><input type="checkbox" name="sml_settings[' . esc_attr( $key ) . ']" value="1" ' . $checked . ' /> ' . esc_html__( 'Enabled', 'scoped-media-library' ) . '</label>';
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Scoped Media Library', 'scoped-media-library' ) . '</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'sml_settings_group' );
		do_settings_sections( 'scoped-media-library' );
		submit_button();
		echo '</form>';
		echo '</div>';
	}
}

