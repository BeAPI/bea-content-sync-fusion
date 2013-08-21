<?php
class BEA_CSF_Client_PostType {
	/**
	 * Add post on DB
	 */
	public static function new_post( $datas ) {
		// Clean values
		if ( $datas == false || !is_array($datas) ) {
			return new WP_Error('invalid_datas', 'Error - Datas is invalid.' );
		}
		
		// Post exists ?
		$local_id = BEA_CSF_Client_Base::get_post_id_from_meta( 'master_id', $datas['ID'] );
		
		// Find local parent ?
		$local_parent_id = BEA_CSF_Client_Base::get_post_id_from_meta( 'master_id', $datas['post_parent'] );
		$datas['post_parent'] = ( $local_parent_id > 0 ) ? $local_parent_id : 0;
		
		// Clone datas for post insertion
		$datas_for_post = $datas;
		unset($datas_for_post['medias'], $datas_for_post['terms'], $datas_for_post['tags_input'], $datas_for_post['post_category']);
		
		// Merge post
		if ( $local_id != 0 ) {
			$datas_for_post['ID'] = $local_id;
			$new_post_id = wp_update_post( $datas_for_post );
		} else {
			$datas_for_post['import_id'] = $datas_for_post['ID'];
			unset($datas_for_post['ID']);
			$new_post_id = wp_insert_post( $datas_for_post );
		}
		
		// Post on DB ?
		if ( (int) $new_post_id === 0 ) {
			return new WP_Error('post_insertion', 'Error during the post insertion ' . $new_post_id->get_error_message() );
		}
		
		// Remove old thumb
		delete_post_meta( $new_post_id, '_thumbnail_id' );
		
		// Save old ID
		update_post_meta( $new_post_id, 'master_id', $datas['ID'] );
		
		// Association with terms
		if ( isset($datas['terms']) && is_array($datas['terms']) && !empty($datas['terms']) ) {
			$term_ids = array();
			
			foreach( $datas['terms'] as $term ) {
				$local_term_id = (int) get_term_id_from_meta( $term['taxonomy'], 'master_id', (int) $term['term_id'] );
				if ( $local_term_id == 0 ) {
					$local_term_id = BEA_CSF_Client_Taxonomy::new_term($term);
				}
				
				if( $local_term_id > 0 ) {
					if ( !isset($term_ids[$term['taxonomy']]) ) {
						$term_ids[$term['taxonomy']] = array();
					}
					
					$term_ids[$term['taxonomy']][] = $local_term_id;
				}
			}
			
			foreach( $term_ids as $taxonomy => $local_term_ids ) {
				wp_set_object_terms( $new_post_id, $local_term_ids, $taxonomy, false );
			}
		}
		
		// Medias array
		$search_replace = array();
		
		// Medias exist ?
		if ( is_array($datas['medias']) && !empty($datas['medias']) ) {
			// Loop for medias
			foreach( $datas['medias'] as $media ) {
				// Media exists ?
				$current_media_id = BEA_CSF_Client_Base::get_post_id_from_meta( 'master_id', $media['ID'] );
				
				// Merge or add ?
				if ( $current_media_id > 0 ) { // Edit, update only main fields
					$updated_datas = array();
					$updated_datas['ID'] = $current_media_id;
					$updated_datas['post_title'] = $media['post_title'];
					$updated_datas['post_content'] = $media['post_content'];
					$updated_datas['post_excerpt'] = $media['post_excerpt'];
					wp_update_post($updated_datas);
				} else { // Insert with WP media public static function
					$new_media_id = BEA_CSF_Client_Attachment::media_sideload_image( $media['attachment_dir'], $new_post_id, null );
					if ( !is_wp_error($new_media_id) && $new_media_id > 0 ) {
						// Stock main fields from server
						$updated_datas = array();
						$updated_datas['ID'] = $new_media_id;
						$updated_datas['post_title'] = $media['post_title'];
						$updated_datas['post_content'] = $media['post_content'];
						$updated_datas['post_excerpt'] = $media['post_excerpt'];
						$current_media_id = wp_update_post($updated_datas);
						
						// Save metas
						update_post_meta( $new_media_id, 'master_id', $media['ID']);
					} else {
						continue;
					}
				}
				
				// Get size array
				$thumbs = maybe_unserialize($media['meta']['_wp_attachment_metadata'][0]);
				$base_url = esc_url( trailingslashit($datas['upload_url']) . trailingslashit(dirname($media['meta']['_wp_attached_file'][0])) );
				
				// Try to replace old link by new (for thumbs)
				foreach ( $thumbs['sizes'] as $key => $size ) {
					$img = wp_get_attachment_image_src($current_media_id, $key);
					$search_replace[$base_url.$size['file']] = $img[0];
				}
				
				// Add url attachment link to replace
				$search_replace[$media['attachment_url']] = get_permalink($current_media_id);
			}
			
			// Update links on content
			if ( !empty($search_replace) ) {
				$post = get_post($new_post_id, ARRAY_A);
				$post['post_content'] = strtr( $post['post_content'], $search_replace );
				wp_update_post($post);
			}
		}
		
		// Restore post thumb
		$thumbnail_id = BEA_CSF_Client_Base::get_post_id_from_meta( 'master_id', $datas['_thumbnail_id'] );
		if ( $thumbnail_id > 0 ) {
			update_post_meta( $new_post_id, '_thumbnail_id', $thumbnail_id);
		} elseif ( $datas['_thumbnail'] != false ) {
			$media_id = BEA_CSF_Client_Attachment::merge_attachment( $datas['_thumbnail'] );
			if ( $media_id > 0 ) {
				update_post_meta( $new_post_id, '_thumbnail_id', $media_id );
			}
		}
	
		return $new_post_id;
	}
	
	/**
	 * Delete a post, take the master id, try to find the new ID and delete local post
	 * 
	 * @param integer $master_id
	 * @return \WP_Error|boolean
	 */
	public static function remove_post( $master_id = 0 ) {
		// Test datas validity
		$master_id = (int) $master_id;
		if ( $master_id == 0 ) {
			return new WP_Error('invalid_id', 'Error - Post ID is invalid.' );
		}
		
		// Post exist
		$local_id = BEA_CSF_Client_Base::get_post_id_from_meta( 'master_id', $master_id );
		if ( $local_id > 0 ) {
			wp_delete_post( $local_id, true );
		}
		
		return true;
	}
}