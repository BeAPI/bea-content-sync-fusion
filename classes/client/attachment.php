<?php

class BEA_CSF_Client_Attachment {
	/**
	 * Delete a attachment, take the master ID and try to find the new ID for delete it !
	 *
	 * @param array $data
	 * @param array $sync_fields
	 *
	 * @return bool|WP_Error
	 */
	public static function delete( array $data, array $sync_fields ) {
		// Clean values
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', 'Error - Datas is invalid.' );
		}

		// Post exist
		$attachment_id = BEA_CSF_Plugin::get_post_id_from_meta( '_origin_key', $data['blogid'] . ':' . $data['ID'] );
		if ( $attachment_id > 0 ) {
			do_action( 'bea_csf.before_delete_attachment', $attachment_id, $data );

			wp_delete_attachment( $attachment_id, true );
			BEA_CSF_Relations::delete_by_receiver( 'attachment', $GLOBALS['wpdb']->blogid, $attachment_id );

			do_action( 'bea_csf.after_delete_attachment', $attachment_id, $data );
		}

		return apply_filters( 'bea_csf.client.attachment.delete', true, $sync_fields );
	}


	/**
	 * Delete a attachment, take the master ID and try to find the new ID for delete it !
	 *
	 * @param array $data
	 * @param array $sync_fields
	 *
	 * @return int
	 */
	public static function merge( array $data, array $sync_fields ) {
		global $wpdb;

		// Clean values
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', 'Error - Datas is invalid.' );
		}

		// Media exists ?
		$current_media_id = BEA_CSF_Plugin::get_post_id_from_meta( '_origin_key', $data['blogid'] . ':' . $data['ID'] );

		// Parent media ?
		$current_master_parent_id = (int) BEA_CSF_Plugin::get_post_id_from_meta( '_origin_key', $data['blogid'] . ':' . $data['post_parent'] );

		// Merge or add ?
		if ( $current_media_id > 0 ) { // Edit, update only main fields
			$updated_datas                 = array();
			$updated_datas['ID']           = $current_media_id;
			$updated_datas['post_title']   = $data['post_title'];
			$updated_datas['post_content'] = $data['post_content'];
			$updated_datas['post_excerpt'] = $data['post_excerpt'];
			$updated_datas['post_parent']  = $current_master_parent_id;
			wp_update_post( $updated_datas );

			// update all meta
			self::post_metas($current_media_id, $data['post_custom']);

			BEA_CSF_Relations::merge( 'attachment', $data['blogid'], $data['ID'], $GLOBALS['wpdb']->blogid, $current_media_id );

			do_action( 'bea_csf.client_attachment_after_update', $current_media_id, $data['attachment_dir'], $current_master_parent_id, $data );
		} else { // Insert with WP media public static function
			$new_media_id = self::copy_file( $data['attachment_dir'], $current_master_parent_id, $data );
			if ( ! is_wp_error( $new_media_id ) && $new_media_id > 0 ) {
				// Stock main fields from server
				$updated_datas                 = array();
				$updated_datas['ID']           = $new_media_id;
				$updated_datas['post_title']   = $data['post_title'];
				$updated_datas['post_content'] = $data['post_content'];
				$updated_datas['post_excerpt'] = $data['post_excerpt'];
				wp_update_post( $updated_datas );

				// Save metas
				update_post_meta( $new_media_id, '_origin_key', $data['blogid'] . ':' . $data['ID'] );

				// update all meta
				self::post_metas($new_media_id, $data['post_custom']);

				BEA_CSF_Relations::merge( 'attachment', $data['blogid'], $data['ID'], $GLOBALS['wpdb']->blogid, $new_media_id );

				// For return
				$current_media_id = $new_media_id;

				do_action( 'bea_csf.client_attachment_after_insert', $current_media_id, $data['attachment_dir'], $current_master_parent_id, $data );
			}
		}

		// Clean data for each taxonomy
		if ( isset( $data['taxonomies'] ) ) {
			wp_delete_object_term_relationships( $current_media_id, $data['taxonomies'] );
		}

		// Association with terms
		if ( isset( $data['terms'] ) && is_array( $data['terms'] ) && ! empty( $data['terms'] ) ) {
			$term_ids = array();

			foreach ( $data['terms'] as $term ) {
				// Sync settings, check if term is in an allowed taxonomy
				if ( ! empty( $sync_fields['taxonomies'] ) && ! in_array( $term['taxonomy'], (array) $sync_fields['taxonomies'] ) ) {
					continue;
				}

				// If term has an "origin_key", use it to get its local ID !
				$term['original_blog_id'] = $term['original_term_taxonomy_id'] = 0;
				if ( isset( $term['meta_data']['_origin_key'][0] ) ) {
					$_origin_key_data                  = explode( ':', $term['meta_data']['_origin_key'][0] );
					$term['original_blog_id']          = (int) $_origin_key_data[0];
					$term['original_term_taxonomy_id'] = (int) $_origin_key_data[1];
				}

				$local_term_id = 0;
				wp_cache_flush();
				if ( $wpdb->blogid == $term['original_blog_id'] ) { // Is blog id origin is the same of current blog ?
					$_origin_term_id = get_term_id_from_term_taxonomy_id( $term['taxonomy'], $term['original_term_taxonomy_id'] );
					$local_term      = get_term( (int) $_origin_term_id, $term['taxonomy'] );
					if ( $local_term != false && ! is_wp_error( $local_term ) ) {
						$local_term_id = (int) $local_term->term_id;
					}
				} else {
					$local_term_id = (int) get_term_id_from_meta( $term['taxonomy'], '_origin_key', $data['blogid'] . ':' . (int) $term['term_taxonomy_id'] );
				}

				/*
				 * Do not allow term creation on this method
				  if ( $local_term_id == 0 ) {
				  $term['blogid'] = $data['blogid'];
				  $local_term_id = BEA_CSF_Client_Taxonomy::merge( $term, $sync_fields );
				  }
				 */
				if ( $local_term_id > 0 ) {
					if ( ! isset( $term_ids[ $term['taxonomy'] ] ) ) {
						$term_ids[ $term['taxonomy'] ] = array();
					}

					$term_ids[ $term['taxonomy'] ][] = $local_term_id;
				}
			}

			foreach ( $term_ids as $taxonomy => $local_term_ids ) {
				wp_set_object_terms( $current_media_id, $local_term_ids, $taxonomy, false );
			}
		}

		return apply_filters( 'bea_csf.client.attachment.merge', $current_media_id );
	}

	/**
	 * Copy an file from the specified path and attach it to a post.
	 *
	 * @param string $file_path The full path of the file to copy
	 * @param int $post_id The post ID the media is to be associated with
	 * @param string $origin_key Origin key with blog_id and post_id
	 *
	 * @return integer Return media ID on success or ZERO !
	 */
	public static function copy_file( $file_path, $post_id, $data_transferred ) {
		require_once( ABSPATH . '/wp-admin/includes/media.php' );

		if ( ! empty( $file_path ) && is_file( $file_path ) ) {

			do_action( 'bea_csf.before_copy_file', $file_path, $post_id, $data_transferred );

			// Get file info from original file path
			$path_parts = pathinfo( $file_path );

			// Create tmp file copy
			$temp_file = tempnam( sys_get_temp_dir(), 'wp' );
			copy($file_path, $temp_file);

			// Set variables for storage / fix file filename for query strings
			$file_array             = array();
			$file_array['name']     = $path_parts['basename'];
			$file_array['tmp_name'] = $temp_file;

			// do the validation and storage stuff
			$attachment_id = media_handle_sideload( $file_array, $post_id );

			// If error storing permanently, unlink
			if ( is_wp_error( $attachment_id ) ) {
				@unlink( $temp_file );

				return $attachment_id;
			}

			do_action( 'bea_csf.after_copy_file', $attachment_id, $file_path, $post_id, $data_transferred );

			return $attachment_id;
		}

		return 0;
	}

	/**
	 * @param $media_id
	 * @param $metas
	 */
	public static function post_metas($media_id, $metas) {

		if ( ! is_array( $metas ) ) {
			return false;
		}

		// unset attachment attached file
		unset( $metas['_wp_attached_file'] );

		// unset attachment metadata
		unset( $metas['_wp_attachment_metadata'] );

		foreach ( $metas as $key_field => $value_field ) {
			foreach ( $value_field as $key => $value ) {
				update_post_meta( $media_id, $key_field, $value );
			}
		}
	}
}