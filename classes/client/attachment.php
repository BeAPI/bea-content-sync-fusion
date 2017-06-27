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
		$attachment_id = BEA_CSF_Relations::get_post_for_any( 'attachment', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );
		if ( ! empty( $attachment_id ) && (int) $attachment_id > 0 ) {
			do_action( 'bea_csf.before_delete_attachment', $attachment_id, $data );

			wp_delete_attachment( $attachment_id, true );
			BEA_CSF_Relations::delete_by_emitter( 'attachment', (int) $GLOBALS['wpdb']->blogid, (int) $attachment_id );

			do_action( 'bea_csf.after_delete_attachment', $attachment_id, $data );
		}

		BEA_CSF_Relations::delete_by_emitter( 'attachment', (int) $data['blogid'], (int) $data['ID'] );

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
		$current_media_id = BEA_CSF_Relations::get_post_for_any( 'attachment', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );

		// Parent media ?
		$current_master_parent_id = BEA_CSF_Relations::get_post_for_any( 'attachment', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['post_parent'], $data['post_parent'] );
		$current_master_parent_id = ! empty( $current_master_parent_id ) && (int) $current_master_parent_id > 0 ? (int) $current_master_parent_id : 0;

		// Merge or add ?
		if ( ! empty( $current_media_id ) && (int) $current_media_id > 0 ) { // Edit, update only main fields
			$updated_datas                   = array();
			$updated_datas['ID']             = $current_media_id;
			$updated_datas['post_title']     = $data['post_title'];
			$updated_datas['post_content']   = $data['post_content'];
			$updated_datas['post_excerpt']   = $data['post_excerpt'];
			$updated_datas['post_mime_type'] = $data['post_mime_type'];
			$updated_datas['post_parent']    = $current_master_parent_id;

			wp_update_post( $updated_datas );

			// update all meta
			self::post_metas( $current_media_id, $data['post_custom'] );

			BEA_CSF_Relations::merge( 'attachment', $data['blogid'], $data['ID'], $GLOBALS['wpdb']->blogid, $current_media_id );

			do_action( 'bea_csf.client_attachment_after_update', $current_media_id, $data['attachment_dir'], $current_master_parent_id, $data );
		} else { // Insert with WP media public static function

			// Stock main fields from server
			$updated_datas                   = array();
			$updated_datas['post_title']     = $data['post_title'];
			$updated_datas['post_content']   = $data['post_content'];
			$updated_datas['post_excerpt']   = $data['post_excerpt'];
			$updated_datas['post_type']      = 'attachment';
			$updated_datas['post_mime_type'] = $data['post_mime_type'];
			$new_media_id                    = wp_insert_post( $updated_datas );

			if ( ! is_wp_error( $new_media_id ) && $new_media_id > 0 ) {

				// update all meta
				self::post_metas( $new_media_id, $data['post_custom'] );

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

				$local_term_id = BEA_CSF_Relations::get_post_for_any( 'attachment', $data['blogid'], $sync_fields['_current_receiver_blog_id'], (int) $term['term_id'], (int) $term['term_id'] );
				if ( (int) $local_term_id > 0 ) {
					if ( ! isset( $term_ids[ $term['taxonomy'] ] ) ) {
						$term_ids[ $term['taxonomy'] ] = array();
					}

					$term_ids[ $term['taxonomy'] ][] = (int) $local_term_id;
				}

				//TODO Doit on insérer le term s'il n'existe pas en liaison ? Un jour :)
			}

			foreach ( $term_ids as $taxonomy => $local_term_ids ) {
				wp_set_object_terms( $current_media_id, $local_term_ids, $taxonomy, false );
			}
		}

		return apply_filters( 'bea_csf.client.attachment.merge', $current_media_id );
	}

	/**
	 * @param $media_id
	 * @param $metas
	 */
	public static function post_metas( $media_id, $metas ) {
		if ( ! is_array( $metas ) ) {
			return false;
		}

		foreach ( $metas as $key_field => $value_field ) {
			foreach ( $value_field as $key => $value ) {
				update_post_meta( $media_id, $key_field, $value );
			}
		}
	}
}
