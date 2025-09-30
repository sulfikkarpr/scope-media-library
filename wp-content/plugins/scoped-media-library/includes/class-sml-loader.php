<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SML_Loader {
	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_filter( 'ajax_query_attachments_args', array( $this, 'filter_media_attachments' ) );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'capture_dimensions_on_metadata' ), 10, 2 );
		add_action( 'add_attachment', array( $this, 'capture_dimensions_on_upload' ) );
	}

	public function get_settings() {
		$settings = get_option( 'sml_settings', array() );
		$defaults = array(
			'min_width'  => 0,
			'max_width'  => 0,
			'min_height' => 0,
			'max_height' => 0,
			'fallback'   => false,
		);
		$settings = wp_parse_args( $settings, $defaults );

		/**
		 * Allow developers to override settings globally or per-context.
		 *
		 * @param array $settings Current settings.
		 */
		return apply_filters( 'sml_settings', $settings );
	}

	public function register_settings() {
		register_setting( 'sml_settings_group', 'sml_settings', array( $this, 'sanitize_settings' ) );
		add_settings_section( 'sml_main', __( 'Dimension Rules', 'scoped-media-library' ), '__return_false', 'sml' );
		add_settings_field( 'sml_min_width', __( 'Min Width', 'scoped-media-library' ), array( $this, 'render_field_min_width' ), 'sml', 'sml_main' );
		add_settings_field( 'sml_max_width', __( 'Max Width', 'scoped-media-library' ), array( $this, 'render_field_max_width' ), 'sml', 'sml_main' );
		add_settings_field( 'sml_min_height', __( 'Min Height', 'scoped-media-library' ), array( $this, 'render_field_min_height' ), 'sml', 'sml_main' );
		add_settings_field( 'sml_max_height', __( 'Max Height', 'scoped-media-library' ), array( $this, 'render_field_max_height' ), 'sml', 'sml_main' );
		add_settings_field( 'sml_fallback', __( 'Enable Fallback (show all)', 'scoped-media-library' ), array( $this, 'render_field_fallback' ), 'sml', 'sml_main' );
	}

	public function sanitize_settings( $input ) {
		$out = array();
		$out['min_width']  = max( 0, intval( $input['min_width'] ?? 0 ) );
		$out['max_width']  = max( 0, intval( $input['max_width'] ?? 0 ) );
		$out['min_height'] = max( 0, intval( $input['min_height'] ?? 0 ) );
		$out['max_height'] = max( 0, intval( $input['max_height'] ?? 0 ) );
		$out['fallback']   = ! empty( $input['fallback'] ) ? (bool) $input['fallback'] : false;
		return $out;
	}

	public function add_settings_page() {
		add_options_page(
			__( 'Scoped Media Library', 'scoped-media-library' ),
			__( 'Scoped Media Library', 'scoped-media-library' ),
			'manage_options',
			'sml',
			array( $this, 'render_settings_page' )
		);
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Scoped Media Library', 'scoped-media-library' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'sml_settings_group' );
				do_settings_sections( 'sml' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	private function print_number_input( $name, $label ) {
		$settings = $this->get_settings();
		$value = intval( $settings[ $name ] );
		echo '<input type=\'number\' min="0" name="sml_settings[' . esc_attr( $name ) . ']" value="' . esc_attr( $value ) . '" class="small-text" />';
		if ( in_array( $name, array( 'max_width', 'max_height' ), true ) ) {
			echo ' <span class="description">' . esc_html__( '0 means no maximum', 'scoped-media-library' ) . '</span>';
		}
	}

	public function render_field_min_width() { $this->print_number_input( 'min_width', __( 'Min Width', 'scoped-media-library' ) ); }
	public function render_field_max_width() { $this->print_number_input( 'max_width', __( 'Max Width', 'scoped-media-library' ) ); }
	public function render_field_min_height() { $this->print_number_input( 'min_height', __( 'Min Height', 'scoped-media-library' ) ); }
	public function render_field_max_height() { $this->print_number_input( 'max_height', __( 'Max Height', 'scoped-media-library' ) ); }
	public function render_field_fallback() {
		$settings = $this->get_settings();
		$checked = ! empty( $settings['fallback'] ) ? 'checked' : '';
		echo '<label><input type=\'checkbox\' name="sml_settings[fallback]" value="1" ' . $checked . ' /> ' . esc_html__( 'Allow showing all images alongside scoped results', 'scoped-media-library' ) . '</label>';
	}

	public function capture_dimensions_on_upload( $attachment_id ) {
		$sizes = $this->read_attachment_dimensions( $attachment_id );
		if ( $sizes ) {
			update_post_meta( $attachment_id, '_sml_width', $sizes['width'] );
			update_post_meta( $attachment_id, '_sml_height', $sizes['height'] );
		}
	}

	public function capture_dimensions_on_metadata( $metadata, $attachment_id ) {
		if ( ! empty( $metadata['width'] ) && ! empty( $metadata['height'] ) ) {
			update_post_meta( $attachment_id, '_sml_width', intval( $metadata['width'] ) );
			update_post_meta( $attachment_id, '_sml_height', intval( $metadata['height'] ) );
		}
		return $metadata;
	}

	private function read_attachment_dimensions( $attachment_id ) {
		$path = get_attached_file( $attachment_id );
		if ( ! $path || ! file_exists( $path ) ) {
			return null;
		}
		$size = @getimagesize( $path );
		if ( ! $size ) {
			return null;
		}
		return array( 'width' => intval( $size[0] ), 'height' => intval( $size[1] ) );
	}

	public function filter_media_attachments( $query ) {
		$settings = $this->get_settings();

		/**
		 * Allow per-context overrides. E.g., developers can inspect $_REQUEST['query'] from media modal.
		 *
		 * @param array $settings
		 * @param array $raw_query Args passed to attachments query.
		 */
		$raw_query = isset( $_REQUEST['query'] ) && is_array( $_REQUEST['query'] ) ? wp_unslash( $_REQUEST['query'] ) : array();
		$settings = apply_filters( 'sml_settings_for_query', $settings, $raw_query );

		$min_w  = intval( $settings['min_width'] );
		$max_w  = intval( $settings['max_width'] );
		$min_h  = intval( $settings['min_height'] );
		$max_h  = intval( $settings['max_height'] );
		$fallback = ! empty( $settings['fallback'] );

		if ( $fallback && current_user_can( 'manage_options' ) ) {
			return $query;
		}

		$meta_query = isset( $query['meta_query'] ) && is_array( $query['meta_query'] ) ? $query['meta_query'] : array();
		$dimension_clauses = array( 'relation' => 'AND' );

		if ( $min_w > 0 ) {
			$dimension_clauses[] = array(
				'key'     => '_sml_width',
				'value'   => $min_w,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $max_w > 0 ) {
			$dimension_clauses[] = array(
				'key'     => '_sml_width',
				'value'   => $max_w,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $min_h > 0 ) {
			$dimension_clauses[] = array(
				'key'     => '_sml_height',
				'value'   => $min_h,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $max_h > 0 ) {
			$dimension_clauses[] = array(
				'key'     => '_sml_height',
				'value'   => $max_h,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
		}

		if ( count( $dimension_clauses ) > 1 ) {
			$meta_query[] = $dimension_clauses;
		}

		$query['meta_query'] = $meta_query;
		$query['post_mime_type'] = 'image';
		return $query;
	}
}
