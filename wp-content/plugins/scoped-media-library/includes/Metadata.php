<?php

namespace SML;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Metadata {
	public function __construct() {
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'store_primary_dimensions' ], 20, 2 );
		add_action( 'add_attachment', [ $this, 'maybe_store_dimensions_on_add' ] );
		add_action( 'edit_attachment', [ $this, 'maybe_store_dimensions_on_edit' ] );
	}

	public function store_primary_dimensions( $metadata, $attachment_id ) {
		$mime = get_post_mime_type( $attachment_id );
		if ( strpos( $mime, 'image/' ) !== 0 ) {
			return $metadata;
		}
		$width  = isset( $metadata['width'] ) ? intval( $metadata['width'] ) : null;
		$height = isset( $metadata['height'] ) ? intval( $metadata['height'] ) : null;
		if ( $width && $height ) {
			update_post_meta( $attachment_id, '_sml_width', $width );
			update_post_meta( $attachment_id, '_sml_height', $height );
		}
		return $metadata;
	}

	public function maybe_store_dimensions_on_add( $attachment_id ) {
		$this->store_dimensions_if_missing( $attachment_id );
	}

	public function maybe_store_dimensions_on_edit( $attachment_id ) {
		$this->store_dimensions_if_missing( $attachment_id );
	}

	private function store_dimensions_if_missing( $attachment_id ) {
		$mime = get_post_mime_type( $attachment_id );
		if ( strpos( $mime, 'image/' ) !== 0 ) {
			return;
		}
		$has_width  = get_post_meta( $attachment_id, '_sml_width', true );
		$has_height = get_post_meta( $attachment_id, '_sml_height', true );
		if ( $has_width && $has_height ) {
			return;
		}
		$file = get_attached_file( $attachment_id );
		if ( $file && file_exists( $file ) ) {
			$size = @getimagesize( $file );
			if ( is_array( $size ) && isset( $size[0], $size[1] ) ) {
				update_post_meta( $attachment_id, '_sml_width', intval( $size[0] ) );
				update_post_meta( $attachment_id, '_sml_height', intval( $size[1] ) );
			}
		}
	}
}

