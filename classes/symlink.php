<?php

class BEA_BROADCAST_Symlink {
	static $backup_sizes = array();

	public function __construct() {
		add_action( 'bea_csf.before_copy_file', array( __CLASS__, 'before_copy_file' ), 10, 3 );

		add_action( 'bea_csf.after_copy_file', array( __CLASS__, 'after_copy_file' ), 10, 4 );
		add_action( 'bea_csf.client_attachment_after_update', array( __CLASS__, 'after_copy_file' ), 10, 4 );

		add_action( 'bea_csf.before_delete_attachment', array( __CLASS__, 'before_delete_attachment' ) );
	}

	public static function before_copy_file( $original_file_path, $post_id, $data_transferred ) {
		add_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );
	}

	/**
	 * Delete and create symbolic link for original and other size's medias
	 * Work when doing sites sync
	 *
	 * @author Maxime CULEA
	 *
	 * @param $new_attachment_id : the current file id
	 * @param $original_file_path : the path of the current file
	 *
	 * @return true
	 */
	public static function after_copy_file( $new_attachment_id, $original_file_path, $post_id, $data_transferred ) {
		global $wpdb;

		remove_filter( 'intermediate_image_sizes_advanced', '__return_empty_array' );

		clearstatcache();

		// get new media full path
		$new_file_path = get_attached_file( $new_attachment_id );
		if ( empty( $new_file_path ) ) {
			return false;
		}

		// Remove existing file
		if ( is_file( $new_file_path ) ) {
			unlink( $new_file_path );
			clearstatcache();
		}

		// Create symlink
		symlink( $original_file_path, $new_file_path );
		clearstatcache();

		// Take attachment data
		$attachment_metadata = wp_get_attachment_metadata( $new_attachment_id );
		if ( isset( $data_transferred['metadata']['sizes'] ) ) {
			// Take thumbs sizes from server
			$attachment_metadata['sizes'] = $data_transferred['metadata']['sizes'];

			// Save it
			wp_update_attachment_metadata( $new_attachment_id, $attachment_metadata );
		}

		foreach ( (array) $attachment_metadata['sizes'] as $size ) {
			// Invalid size
			if ( empty( $size['file'] ) ) {
				continue;
			}

			// Build full paths
			$existing_thumb_path = str_replace( basename( $original_file_path ), $size['file'], $original_file_path );
			$new_thumb_path      = str_replace( basename( $new_file_path ), $size['file'], $new_file_path );
			//$new_thumb_path      = str_replace( 'sites/1/', 'sites/' . $wpdb->blogid . '/', $existing_thumb_path );
			//$new_thumb_path = str_replace( 'sites/1/', 'sites/' . $wpdb->blogid . '/', $existing_thumb_path2 );
			//var_dump($original_file_path, $new_file_path, $existing_thumb_path, $new_thumb_path);
			// Existing and new filepath is the same ? Skip symlink
			if ( $existing_thumb_path == $new_thumb_path ) {
				continue;
			}

			clearstatcache();

			// Check is original file exist
			if ( ! is_file( $existing_thumb_path ) ) {
				continue;
			}

			// Remove existing file
			if ( is_file( $new_thumb_path ) ) {
				@unlink( $new_thumb_path );
				clearstatcache();
			}

			/// Create destination folder if not exist
			wp_mkdir_p( dirname( $new_thumb_path ) );

			// Create symlink for this thumbs
			$r = symlink( $existing_thumb_path, $new_thumb_path );
			if ( false == $r ) { // debug mode
				//var_dump($existing_thumb_path, $new_thumb_path);
			}
		}

		clearstatcache();

		return true;
	}

	/**
	 * Delete files and soft links before WP, to be sure !
	 *
	 * @author Maxime CULEA
	 *
	 * @param $local_attachment_id : the attachment id from local site
	 *
	 * @return true
	 */
	public static function before_delete_attachment( $local_attachment_id ) {
		clearstatcache();

		// get new media full path
		$new_file_path = get_attached_file( $local_attachment_id );
		if ( empty( $new_file_path ) ) {
			return false;
		}

		// Remove existing file
		if ( is_file( $new_file_path ) ) {
			unlink( $new_file_path );
			clearstatcache();
		}

		// Take attachment data
		$attachment_metadata = wp_get_attachment_metadata( $local_attachment_id );
		foreach ( $attachment_metadata['sizes'] as $size ) {
			// Build full path for thumb
			$thumb_path = str_replace( basename( $new_file_path ), $size['file'], $new_file_path );

			// Remove existing file
			if ( is_file( $thumb_path ) ) {
				unlink( $thumb_path );
				clearstatcache();
			}
		}

		return true;
	}
}