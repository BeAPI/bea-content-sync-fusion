<?php

class BEA_CSF_Client_PostType {

	/**
	 * Add post on DB
	 *
	 * @param array $data
	 * @param array $sync_fields
	 *
	 * @return mixed|null|void
	 */
	public static function merge( array $data, array $sync_fields ) {
		// Clean values
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', 'Error - Datas is invalid.' );
		}

		// Post exists ?
		$local_id = BEA_CSF_Relations::get_post_for_any( $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );

		// Find local parent ?
		if ( isset( $data['post_parent'] ) ) {
			$local_parent_id     = BEA_CSF_Relations::get_post_for_any( $sync_fields['_current_receiver_blog_id'], $data['blogid'], $data['post_parent'], $data['post_parent'] );
			$data['post_parent'] = ! empty( $local_parent_id ) && (int) $local_parent_id > 0 ? $local_parent_id : 0;
		}

		// Clone datas for post insertion
		$data_for_post = $data;
		unset( $data_for_post['medias'], $data_for_post['terms'], $data_for_post['tags_input'], $data_for_post['post_category'] );

		// Merge post
		if ( ! empty( $local_id ) && (int) $local_id > 0 ) {
			$data_for_post['ID'] = $local_id;
			$new_post_id         = wp_update_post( $data_for_post, true );

		} else {
			// Sync settings, allow change post status. Apply only for POST creation
			if ( 'pending' === $sync_fields['status'] ) {
				$data_for_post['post_status'] = 'pending';
			}

			$data_for_post['import_id'] = $data_for_post['ID'];
			unset( $data_for_post['ID'] );
			$new_post_id = wp_insert_post( $data_for_post, true );
		}

		// Post on DB ?
		if ( is_wp_error( $new_post_id ) ) {
			return new WP_Error( 'post_insertion', 'Error during the post insertion ' . $new_post_id->get_error_message() );
		}

		BEA_CSF_Relations::merge( 'posttype', $data['blogid'], $data['ID'], $GLOBALS['wpdb']->blogid, $new_post_id );

		// Save all metas for new post
		if ( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) && ! empty( $data['meta_data'] ) ) {
			foreach ( $data['meta_data'] as $key => $values ) {
				if ( count( $values ) > 1 || ! isset( $values[0] ) ) {
					// TODO: Management exception, SO RARE in WP !
					continue;
				} else {
					update_post_meta( $new_post_id, $key, $values[0] );
				}
			}
		}

		// Remove old thumb
		delete_post_meta( $new_post_id, '_thumbnail_id' );

		// Clean data for each taxonomy
		if ( isset( $data['taxonomies'] ) ) {
			wp_delete_object_term_relationships( $new_post_id, $data['taxonomies'] );
		}

		// Association with terms
		if ( isset( $data['terms'] ) && is_array( $data['terms'] ) && ! empty( $data['terms'] ) ) {
			$term_ids = array();

			foreach ( $data['terms'] as $term ) {
				// Sync settings, check if term is in an allowed taxonomy
				if ( ! empty( $sync_fields['taxonomies'] ) && ! in_array( $term['taxonomy'], (array) $sync_fields['taxonomies'] ) ) {
					continue;
				}

				$local_term_id = BEA_CSF_Relations::get_post_id_for_receiver( $sync_fields['_current_receiver_blog_id'], $data['blogid'], (int) $term['term_id'] );
				if ( is_null( $local_term_id ) ) {
					$local_term_id = BEA_CSF_Relations::get_post_id_for_emitter( $data['blogid'], $sync_fields['_current_receiver_blog_id'], (int) $term['term_id'] );
				}

				$local_t_id = 0;
				if ( ! empty( $local_term_id ) ) {
					if ( isset( $local_term_id->receiver_id ) && (int) $local_term_id->receiver_id > 0 ) {
						$local_t_id = (int) $local_term_id->receiver_id;
					} elseif ( isset( $local_term_id->emitter_id ) && (int) $local_term_id->emitter_id > 0 ) {
						$local_t_id = (int) $local_term_id->emitter_id;
					}
				}

				if ( $local_t_id > 0 ) {
					if ( ! isset( $term_ids[ $term['taxonomy'] ] ) ) {
						$term_ids[ $term['taxonomy'] ] = array();
					}

					$term_ids[ $term['taxonomy'] ][] = $local_t_id;
				}
			}

			foreach ( $term_ids as $taxonomy => $local_term_ids ) {
				wp_set_object_terms( $new_post_id, $local_term_ids, $taxonomy, false );
			}
		}

		// Medias exist ?
		if ( is_array( $data['medias'] ) && ! empty( $data['medias'] ) ) {
			// Loop for medias
			foreach ( $data['medias'] as $media ) {
				// Media exists ?
				$current_media_id = BEA_CSF_Relations::get_post_id_for_emitter( $data['blogid'], $sync_fields['_current_receiver_blog_id'], $media['ID'] );
				if ( empty( $current_media_id ) ) {
					//TO DO Insert new media ???
					continue;
				}

				wp_update_post( array(
						'ID'          => $current_media_id->receiver_id,
						'post_parent' => $new_post_id,
					)
				);
			}
		}

		// Restore post thumb
		$thumbnail_id = BEA_CSF_Relations::get_post_id_for_emitter( $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['_thumbnail_id'] );
		if ( ! empty( $thumbnail_id ) && (int) $thumbnail_id->receiver_id > 0 ) {
			update_post_meta( $new_post_id, '_thumbnail_id', $thumbnail_id->receiver_id );
		} elseif ( false != $data['_thumbnail'] ) {
			$data['_thumbnail']['blogid'] = $data['blogid'];
			$media_id                     = BEA_CSF_Client_Attachment::merge( $data['_thumbnail'], $sync_fields );
			if ( $media_id > 0 ) {
				update_post_meta( $new_post_id, '_thumbnail_id', $media_id );
			}
		}

		$new_post = get_post( $new_post_id );
		if ( ! empty( $new_post ) ) {
			$new_post->is_edition = ( ! empty( $local_id ) && (int) $local_id ) ? true : false;
		}

		return apply_filters( 'bea_csf.client.posttype.merge', $data, $sync_fields, $new_post );
	}

	/**
	 * Delete a post, take the master id, try to find the new ID and delete local post
	 *
	 * @param array $data
	 * @param array $sync_fields
	 *
	 * @return bool|WP_Error
	 * @internal param int $master_id
	 */
	public static function delete( array $data, array $sync_fields ) {
		// Clean values
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', 'Error - Datas is invalid.' );
		}

		// Post exist
		$local_id = BEA_CSF_Relations::get_post_id_for_receiver( $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'] );
		if ( ! empty( $local_id ) && (int) $local_id->receiver_id > 0 ) {
			wp_delete_post( $local_id->receiver_id, true );

			BEA_CSF_Relations::delete_by_emitter( 'posttype', (int) $GLOBALS['wpdb']->blogid, (int) $local_id->receiver_id );
		}

		BEA_CSF_Relations::delete_by_emitter( 'posttype', (int) $data['blogid'], (int) $data['ID'] );

		return apply_filters( 'bea_csf.client.posttype.delete', $data, $sync_fields );
	}

}
