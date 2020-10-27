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
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', 'Error - Datas is invalid.' );
		}

		if ( ! isset( $data['blogid'] ) ) {
			return new WP_Error( 'missing_blog_id', 'Error - Missing a blog ID for allow insertion.' );
		}

		$data['local_id'] = BEA_CSF_Relations::get_object_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );

		// Find local parent ?
		if ( isset( $data['post_parent'] ) ) {
			$local_parent_id     = BEA_CSF_Relations::get_object_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['post_parent'], $data['post_parent'] );
			$data['post_parent'] = ! empty( $local_parent_id ) && (int) $local_parent_id > 0 ? $local_parent_id : 0;
		}

		$data = apply_filters( 'bea_csf/client/posttype/before_merge', $data, $sync_fields );

		if ( false === $data || empty( $data ) ) {
			return new WP_Error( 'empty_data', 'Error - exclude post to sync' );
		}

		// Clone datas for post insertion
		$data_for_post = $data;
		unset( $data_for_post['medias'], $data_for_post['terms'], $data_for_post['tags_input'], $data_for_post['post_category'] );

		// Merge post
		if ( ! empty( $data['local_id'] ) && (int) $data['local_id'] > 0 ) {
			$current_value = (int) get_post_meta( $data['local_id'], '_exclude_from_futur_sync', true );
			if ( $current_value == 1 ) {
				return new WP_Error( 'future_sync_exclusion', 'Error - This post is exclude from future sync.' );
			}

			// Sync settings, if pending, never try to change the post status ?
			if ( 'pending' === $sync_fields['status'] || self::is_blog_post_pending_user_selection( $sync_fields, $data ) ) {
				unset( $data_for_post['post_status'] );
			}

			$data_for_post['ID'] = $data['local_id'];
			$new_post_id         = wp_update_post( $data_for_post, true );

		} else {
			// Sync settings, allow change post status. Apply only for post creation
			if ( 'pending' === $sync_fields['status'] || self::is_blog_post_pending_user_selection( $sync_fields, $data ) ) {
				$data_for_post['post_status'] = 'pending';
			}

			//$data_for_post['import_id'] = $data_for_post['ID'];
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
					update_post_meta( $new_post_id, $key, maybe_unserialize( $values[0] ) );
				}
			}
		}

		// Clean data for each taxonomy
		if ( isset( $data['taxonomies'] ) ) {
			wp_delete_object_term_relationships( $new_post_id, $data['taxonomies'] );
		}

		// Association with terms
		if ( isset( $data['terms'] ) && is_array( $data['terms'] ) && ! empty( $data['terms'] ) ) {
			$term_ids = array();

			foreach ( $data['terms'] as $term ) {
				// Sync settings, check if term is in an allowed taxonomy
				if ( ! empty( $sync_fields['taxonomies'] ) && ! in_array( $term['taxonomy'], (array) $sync_fields['taxonomies'], true ) ) {
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
				wp_set_object_terms( $new_post_id, $local_term_ids, $taxonomy, false );
			}
		}

		// Medias exist ?
		if ( is_array( $data['medias'] ) && ! empty( $data['medias'] ) ) {
			// Loop for medias
			foreach ( $data['medias'] as $media ) {
				// Media exists ?
				$current_media_id = (int) BEA_CSF_Relations::get_object_for_any( 'attachment', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $media['ID'], $media['ID'] );
				if ( empty( $current_media_id ) ) {
					continue;
				}

				wp_update_post(
					array(
						'ID'          => $current_media_id,
						'post_parent' => $new_post_id,
					)
				);
			}
		}

		// Remove old thumb
		delete_post_meta( $new_post_id, '_thumbnail_id' );

		// Restore post thumb
		$thumbnail_id = (int) BEA_CSF_Relations::get_object_for_any( 'attachment', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['_thumbnail_id'], $data['_thumbnail_id'] );
		if ( empty( $thumbnail_id ) && (int) $thumbnail_id > 0 ) {
			update_post_meta( $new_post_id, '_thumbnail_id', $thumbnail_id->receiver_id );
		} elseif ( ! empty( $data['_thumbnail'] ) ) {
			$data['_thumbnail']['blogid'] = $data['blogid'];
			$new_media                    = BEA_CSF_Client_Attachment::merge( $data['_thumbnail'], $sync_fields );
			if ( ! is_wp_error( $new_media ) && isset( $new_media['new_media_id'] ) ) {
				update_post_meta( $new_post_id, '_thumbnail_id', $new_media['new_media_id'] );
			}
		}

		// Set P2P connections
		if ( isset( $data['connections'] ) && ! empty( $data['connections'] ) ) {
			foreach ( (array) $data['connections'] as $connection ) {
				$connection['blogid'] = $data['blogid'];
				BEA_CSF_Client_P2P::merge( $connection, $sync_fields );
			}
		}

		$new_post = get_post( $new_post_id );
		if ( ! empty( $new_post ) ) {
			$new_post->is_edition = ( ! empty( $data['local_id'] ) && (int) $data['local_id'] > 0 ) ? true : false;
		}

		return apply_filters( 'bea_csf.client.posttype.merge', $data, $sync_fields, $new_post );
	}

	/**
	 * Check if the current blog have a user selection status
	 *
	 * @param $sync_fields
	 * @param $data
	 *
	 * @return bool
	 */
	private static function is_user_selection_blog_post( $sync_fields, $data ) {
		return ( 'user_selection' === $sync_fields['status'] && isset( $data['meta_data'][ '_b' . $data['blogid'] . '_post_receivers_status' ] ) );
	}

	/**
	 * Get post status from post meta for the current blog
	 *
	 * @param $data
	 *
	 * @return string
	 */
	private static function get_user_selection_blog_post( $data ) {
		$_post_receivers_status = maybe_unserialize( $data['meta_data'][ '_b' . $data['blogid'] . '_post_receivers_status' ][0] );
		$_current_blog_id       = (int) $GLOBALS['wpdb']->blogid;
		if ( isset( $_post_receivers_status[ $_current_blog_id ] ) ) {
			return $_post_receivers_status[ $_current_blog_id ];
		}

		return '';
	}

	/**
	 * Check if blog post have a pending value from user selection
	 *
	 * @param $sync_fields
	 * @param $data
	 *
	 * @return bool
	 */
	private static function is_blog_post_pending_user_selection( $sync_fields, $data ) {
		if ( ! self::is_user_selection_blog_post( $sync_fields, $data ) ) {
			return false;
		}

		if ( ! in_array( self::get_user_selection_blog_post( $data ), [ 'pending', 'pending-draft' ], true ) ) {
			return false;
		}

		return true;
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
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', 'Error - Datas is invalid.' );
		}

		$local_id = BEA_CSF_Relations::get_object_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );
		if ( ! empty( $local_id ) && (int) $local_id > 0 ) {
			wp_delete_post( $local_id, true );

			BEA_CSF_Relations::delete_by_receiver( 'posttype', (int) $GLOBALS['wpdb']->blogid, (int) $local_id );

			// Delete additional if reciprocal synchro
			BEA_CSF_Relations::delete_by_emitter_and_receiver( 'posttype', (int) $GLOBALS['wpdb']->blogid, (int) $local_id, (int) $data['blogid'], (int) $data['ID'] );
		}

		return apply_filters( 'bea_csf.client.posttype.delete', $data, $sync_fields );
	}

}
