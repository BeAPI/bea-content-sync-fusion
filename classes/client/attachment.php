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
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', 'Error - Datas is invalid.' );
		}

		$attachment_id = BEA_CSF_Relations::get_object_for_any( 'attachment', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );

		if ( ! empty( $attachment_id ) && (int) $attachment_id > 0 ) {
			do_action( 'bea_csf.before_delete_attachment', $attachment_id, $data );

			wp_delete_attachment( $attachment_id, true );

			BEA_CSF_Relations::delete_by_receiver( 'attachment', (int) $GLOBALS['wpdb']->blogid, (int) $attachment_id );

			// Delete additional if reciprocal synchro
			BEA_CSF_Relations::delete_by_emitter_and_receiver( 'attachment', (int) $GLOBALS['wpdb']->blogid, (int) $attachment_id, (int) $data['blogid'], (int) $data['ID'] );

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
	 * @return array[WP_Error
	 */
	public static function merge( array $data, array $sync_fields ) {
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', 'Error - Datas is invalid.' );
		}

		if ( ! isset( $data['blogid'] ) ) {
			return new WP_Error( 'missing_blog_id', 'Error - Missing a blog ID for allow insertion.' );
		}

		// Translate for current media ID
		$current_media_id = BEA_CSF_Relations::get_object_for_any( 'attachment', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );

		// Find local parent ?
		if ( isset( $data['post_parent'] ) ) {
			$current_parent_id   = BEA_CSF_Relations::get_object_for_any( 'attachment', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['post_parent'], $data['post_parent'] );
			$data['post_parent'] = ! empty( $current_parent_id ) && (int) $current_parent_id > 0 ? $current_parent_id : 0;
		}

		// Clone data for post insertion
		$data_for_post = $data;
		unset( $data_for_post['taxonomies'], $data_for_post['terms'], $data_for_post['post_custom'], $data_for_post['metadata'] );

		// Merge or add ?
		if ( ! empty( $current_media_id ) && (int) $current_media_id > 0 ) { // Edit, update only main fields

			$data_for_post['ID'] = $current_media_id;
			$new_media_id        = wp_update_post( $data_for_post );
			if ( is_wp_error( $new_media_id ) || $new_media_id === 0 ) {
				return new WP_Error( 'invalid_datas', 'Error - An fatal error occurred during attachment insertion.' );
			}

			do_action( 'bea_csf.client_attachment_after_update', $current_media_id, $data['attachment_dir'], $data['post_parent'], $data );

		} else { // Insert with WP media public static function

			//$data_for_post['import_id'] = $data_for_post['ID'];
			unset( $data_for_post['ID'] );
			$new_media_id = wp_insert_post( $data_for_post );
			if ( is_wp_error( $new_media_id ) || $new_media_id === 0 ) {
				return new WP_Error( 'invalid_datas', 'Error - An fatal error occurred during attachment insertion.' );
			}

			do_action( 'bea_csf.client_attachment_after_insert', $new_media_id, $data['attachment_dir'], $data['post_parent'], $data );

		}


		// Append to relations table
		BEA_CSF_Relations::merge( 'attachment', $data['blogid'], $data['ID'], $GLOBALS['wpdb']->blogid, $new_media_id );

		// Save all metas for new post
		if ( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) && ! empty( $data['meta_data'] ) ) {
			foreach ( $data['meta_data'] as $key => $values ) {
				if ( count( $values ) > 1 || ! isset( $values[0] ) ) {
					// TODO: Management exception, SO RARE in WP !
					continue;
				} else {
					update_post_meta( $new_media_id, $key, maybe_unserialize( $values[0] ) );
				}
			}
		}

		// Clean data for each taxonomy
		if ( isset( $data['taxonomies'] ) ) {
			wp_delete_object_term_relationships( $new_media_id, $data['taxonomies'] );
		}

		// Association with terms
		if ( isset( $data['terms'] ) && is_array( $data['terms'] ) && ! empty( $data['terms'] ) ) {
			$term_ids = array();

			foreach ( $data['terms'] as $term ) {
				// Sync settings, check if term is in an allowed taxonomy
				if ( ! empty( $sync_fields['taxonomies'] ) && ! in_array( $term['taxonomy'], (array) $sync_fields['taxonomies'] ) ) {
					continue;
				}

				$local_term_id = BEA_CSF_Relations::get_object_for_any( 'taxonomy', $data['blogid'], $sync_fields['_current_receiver_blog_id'], (int) $term['term_id'], (int) $term['term_id'] );
				if ( (int) $local_term_id > 0 ) {
					if ( ! isset( $term_ids[ $term['taxonomy'] ] ) ) {
						$term_ids[ $term['taxonomy'] ] = array();
					}

					$term_ids[ $term['taxonomy'] ][] = (int) $local_term_id;
				}
			}

			foreach ( $term_ids as $taxonomy => $local_term_ids ) {
				wp_set_object_terms( $new_media_id, $local_term_ids, $taxonomy, false );
			}
		}

		$new_attachment = get_post( $new_media_id );
		if ( ! empty( $new_attachment ) ) {
			$new_attachment->is_edition = ( ! empty( $current_media_id ) && (int) $current_media_id > 0 ) ? true : false;
		}

		// App new new media for 3rd party plugins
		$data['new_media_id'] = $new_media_id;

		return apply_filters( 'bea_csf.client.attachment.merge', $data, $sync_fields, $new_attachment );
	}
}
