<?php
class BEA_CSF_Client_Attachment {
	/**
	 * Delete a attachment, take the master ID and try to find the new ID for delete it !
	 */
	public static function delete( $master_id, BEA_CSF_Synchronization $sync ) {
		if ( (int) $master_id === 0 ) {
			return new WP_Error('master_id', 'Error - Master ID is invalid.' );
		}
		
		// Post exist
		$local_id = BEA_CSF_Plugin::get_post_id_from_meta( 'master_id', $master_id );
		if ( $local_id > 0 ) {
			wp_delete_attachment( $local_id, true );
		}
		
		return true;
	}
	
	
	/**
	 * Delete a attachment, take the master ID and try to find the new ID for delete it !
	 */
	public static function merge( array $media, BEA_CSF_Synchronization $sync ) {
		// Clean values
		if ( empty($media) || !is_array($media) ) {
			return new WP_Error('invalid_datas', 'Error - Datas is invalid.' );
		}
		
		// Media exists ?
		$current_media_id = BEA_CSF_Plugin::get_post_id_from_meta( 'master_id', $media['ID'] );
		
		// Parent media ?
		$current_master_parent_id = (int) BEA_CSF_Plugin::get_post_id_from_meta( 'master_id', $media['post_parent'] );
		
		// Merge or add ?
		if ( $current_media_id > 0 ) { // Edit, update only main fields
			$updated_datas = array();
			$updated_datas['ID'] = $current_media_id;
			$updated_datas['post_title'] = $media['post_title'];
			$updated_datas['post_content'] = $media['post_content'];
			$updated_datas['post_excerpt'] = $media['post_excerpt'];
			$updated_datas['post_parent'] = $current_master_parent_id;
			wp_update_post($updated_datas);
		} else { // Insert with WP media public static function
			$new_media_id = self::media_sideload_image( $media['attachment_dir'], $current_master_parent_id, null );
			if ( !is_wp_error($new_media_id) && $new_media_id > 0 ) {
				// Stock main fields from server
				$updated_datas = array();
				$updated_datas['ID'] = $new_media_id;
				$updated_datas['post_title'] = $media['post_title'];
				$updated_datas['post_content'] = $media['post_content'];
				$updated_datas['post_excerpt'] = $media['post_excerpt'];
				wp_update_post($updated_datas);
				
				// Save metas
				update_post_meta( $new_media_id, 'master_id', $media['ID']);
				
				// For return
				$current_media_id = $new_media_id;
			}
		}
		
		return $current_media_id;
	}

	/**
	 * Download an image from the specified URL and attach it to a post.
	 *
	 * @since 2.6.0
	 *
	 * @param string $file The full path of the image to copy
	 * @param int $post_id The post ID the media is to be associated with
	 * @param string $desc Optional. Description of the image
	 * @return integer|WP_Error Return media ID on success
	 */
	public static function media_sideload_image($file_path, $post_id, $desc = null) {
		require_once( ABSPATH . '/wp-admin/includes/media.php' );
		
		if ( ! empty($file_path) && is_file($file_path) ) {
			// Create tmp file copy
			$temp_file = tempnam(sys_get_temp_dir(), 'wp');
			file_put_contents( $temp_file, file_get_contents($file_path) );
			
			// Set variables for storage
			// fix file filename for query strings
			$matches = array();
			preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $file_path, $matches);
			$file_array['name'] = basename($matches[0]);
			$file_array['tmp_name'] = $temp_file;
			
			// do the validation and storage stuff
			$id = media_handle_sideload( $file_array, $post_id, $desc );
			
			// If error storing permanently, unlink
			if ( is_wp_error($id) ) {
				@unlink($temp_file);
				return $id;
			}
			
			return $id;
		}
		
		return 0;
	}
}