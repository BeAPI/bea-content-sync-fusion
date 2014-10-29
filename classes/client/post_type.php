<?php
class BEA_CSF_Client_PostType {

	/**
	 * Add post on DB
	 */
	public static function merge( array $data, BEA_CSF_Synchronization $sync ) {
		global $wpdb;

		// Clean values
		if ( empty( $data ) || !is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', 'Error - Datas is invalid.' );
		}

		// Post exists ?
		$local_id = BEA_CSF_Plugin::get_post_id_from_meta( '_origin_key', $data['blogid'] . ':' . $data['ID'] );

		// Find local parent ?
		$local_parent_id = BEA_CSF_Plugin::get_post_id_from_meta( '_origin_key', $data['blogid'] . ':' . $data['post_parent'] );
		$data['post_parent'] = ( $local_parent_id > 0 ) ? $local_parent_id : 0;

		// Clone datas for post insertion
		$data_for_post = $data;
		unset( $data_for_post['medias'], $data_for_post['terms'], $data_for_post['tags_input'], $data_for_post['post_category'] );

		// Merge post
		if ( $local_id != 0 ) {
			$data_for_post['ID'] = $local_id;
			$new_post_id = wp_update_post( $data_for_post );
		} else {
			// Sync settings, allow change post status. Apply only for POST creation
			if ( $sync->status == 'pending' ) {
				$data_for_post['post_status'] = 'pending';
			}

			$data_for_post['import_id'] = $data_for_post['ID'];
			unset( $data_for_post['ID'] );
			$new_post_id = wp_insert_post( $data_for_post );
		}

		// Post on DB ?
		if ( (int) $new_post_id === 0 ) {
			return new WP_Error( 'post_insertion', 'Error during the post insertion ' . $new_post_id->get_error_message() );
		}

		// Save all metas for new post
		if ( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) && !empty( $data['meta_data'] ) ) {
			foreach ( $data['meta_data'] as $key => $values ) {
				if ( count( $values ) > 1 || !isset( $values[0] ) ) {
					// TODO: Management exception, SO RARE in WP !
					continue;
				} else {
					update_post_meta( $new_post_id, $key, $values[0] );
				}
			}
		}

		// Remove old thumb
		delete_post_meta( $new_post_id, '_thumbnail_id' );

		// Save old ID
		update_post_meta( $new_post_id, '_origin_key', $data['blogid'] . ':' . $data['ID'] );

		// Clean data for each taxonomy
		if ( isset( $data['taxonomies'] ) ) {
			wp_delete_object_term_relationships( $new_post_id, $data['taxonomies'] );
		}
		
		// Association with terms
		if ( isset( $data['terms'] ) && is_array( $data['terms'] ) && !empty( $data['terms'] ) ) {
			$term_ids = array();
			
			foreach ( $data['terms'] as $term ) {
				// Sync settings, check if term is in an allowed taxonomy
				if ( !empty( $sync->taxonomies ) && !in_array( $term['taxonomy'], (array) $sync->taxonomies ) ) {
					continue;
				}

				// If term avec a "origin_key" method, check to get local ID with this !
				$term['original_blog_id'] = $term['original_term_taxonomy_id'] = 0;
				if ( isset( $term['meta_data']['_origin_key'][0] ) ) {
					$_origin_key_data = explode( ':', $term['meta_data']['_origin_key'][0] );
					$term['original_blog_id'] = (int) $_origin_key_data[0];
					$term['original_term_taxonomy_id'] = (int) $_origin_key_data[1];
				}
				
				$local_term_id = 0;
				if ( $wpdb->blogid == $term['original_blog_id'] ) { // Is blog id origin is the same of current blog ?
					$_origin_term_id = get_term_id_from_term_taxonomy_id( $term['taxonomy'],  $term['original_term_taxonomy_id'] );
					$local_term = get_term( (int) $_origin_term_id, $term['taxonomy'] );
					if ( $local_term != false && !is_wp_error( $local_term ) ) {
						$local_term_id = (int) $local_term->term_id;
					}
				} else {
					$local_term_id = (int) get_term_id_from_meta( $term['taxonomy'], '_origin_key', $data['blogid'] . ':' . (int) $term['term_taxonomy_id'] );
				}
				
				/*
				 * Do not allow term creation on this method
				  if ( $local_term_id == 0 ) {
				  $term['blogid'] = $data['blogid'];
				  $local_term_id = BEA_CSF_Client_Taxonomy::merge( $term, $sync );
				  }
				 */
				if ( $local_term_id > 0 ) {
					if ( !isset( $term_ids[$term['taxonomy']] ) ) {
						$term_ids[$term['taxonomy']] = array();
					}

					$term_ids[$term['taxonomy']][] = $local_term_id;
				}
			}

			foreach ( $term_ids as $taxonomy => $local_term_ids ) {
				wp_set_object_terms( $new_post_id, $local_term_ids, $taxonomy, false );
			}
		}

		// Medias array
		$search_replace = array();

		// Medias exist ?
		if ( is_array( $data['medias'] ) && !empty( $data['medias'] ) ) {
			// Loop for medias
			foreach ( $data['medias'] as $media ) {
				// Media exists ?
				$current_media_id = BEA_CSF_Plugin::get_post_id_from_meta( '_origin_key', $data['blogid'] . ':' . $media['ID'] );

				// Merge or add ?
				if ( $current_media_id > 0 ) { // Edit, update only main fields
					$updated_datas = array();
					$updated_datas['ID'] = $current_media_id;
					$updated_datas['post_title'] = $media['post_title'];
					$updated_datas['post_content'] = $media['post_content'];
					$updated_datas['post_excerpt'] = $media['post_excerpt'];
					wp_update_post( $updated_datas );
				} else { // Insert with WP media public static function
					$new_media_id = BEA_CSF_Client_Attachment::copy_file( $media['attachment_dir'], $new_post_id, null );
					if ( !is_wp_error( $new_media_id ) && $new_media_id > 0 ) {
						// Stock main fields from server
						$updated_datas = array();
						$updated_datas['ID'] = $new_media_id;
						$updated_datas['post_title'] = $media['post_title'];
						$updated_datas['post_content'] = $media['post_content'];
						$updated_datas['post_excerpt'] = $media['post_excerpt'];
						$current_media_id = wp_update_post( $updated_datas );

						// Save metas
						update_post_meta( $new_media_id, '_origin_key', $data['blogid'] . ':' . $media['ID'] );
					} else {
						continue;
					}
				}

				// Get size array
				$thumbs = maybe_unserialize( $media['meta_data']['_wp_attachment_metadata'][0] );
				$base_url = esc_url( trailingslashit( $data['upload_url'] ) . trailingslashit( dirname( $media['meta_data']['_wp_attached_file'][0] ) ) );

				// Try to replace old link by new (for thumbs)
				foreach ( $thumbs['sizes'] as $key => $size ) {
					$img = wp_get_attachment_image_src( $current_media_id, $key );
					$search_replace[$base_url . $size['file']] = $img[0];
				}

				// Add url attachment link to replace
				$search_replace[$media['attachment_url']] = get_permalink( $current_media_id );
			}

			// Update links on content
			if ( !empty( $search_replace ) ) {
				$post = get_post( $new_post_id, ARRAY_A );
				$post['post_content'] = strtr( $post['post_content'], $search_replace );
				wp_update_post( $post );
			}
		}

		// Restore post thumb
		$thumbnail_id = BEA_CSF_Plugin::get_post_id_from_meta( '_origin_key', $data['blogid'] . ':' . $data['_thumbnail_id'] );
		if ( $thumbnail_id > 0 ) {
			update_post_meta( $new_post_id, '_thumbnail_id', $thumbnail_id );
		} elseif ( $data['_thumbnail'] != false ) {
			$data['blogid'] = $data['blogid'];
			$media_id = BEA_CSF_Client_Attachment::merge( $data['_thumbnail'], $sync );
			if ( $media_id > 0 ) {
				update_post_meta( $new_post_id, '_thumbnail_id', $media_id );
			}
		}

		$new_post = get_post( $new_post_id );
		$new_post->is_edition = ( $local_id != 0 ) ? true : false;
		return $new_post;
	}

	/**
	 * Delete a post, take the master id, try to find the new ID and delete local post
	 * 
	 * @param integer $master_id
	 * @return \WP_Error|boolean
	 */
	public static function delete( array $data, BEA_CSF_Synchronization $sync ) {
		// Clean values
		if ( empty( $data ) || !is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', 'Error - Datas is invalid.' );
		}

		// Post exist
		$local_id = BEA_CSF_Plugin::get_post_id_from_meta( '_origin_key', $data['blogid'] . ':' . $data['ID'] );
		if ( $local_id > 0 ) {
			wp_delete_post( $local_id, true );
		}

		return true;
	}

}
