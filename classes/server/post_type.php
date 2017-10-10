<?php

class BEA_CSF_Server_PostType {

	/**
	 * @param WP_Post|boolean|integer $post
	 * @param array $sync_fields
	 *
	 * @return mixed|null|void
	 */
	public static function delete( $post, array $sync_fields ) {
		if ( empty( $post ) ) {
			return false;
		}

		// Get object from object or ID
		$post = array(
			'ID' => $post,
		);

		if ( empty( $post ) ) {
			return false;
		}

		return apply_filters( 'bea_csf.server.posttype.delete', (array) $post, $sync_fields );
	}

	/**
	 * @param WP_Post|boolean|integer $post
	 * @param array $sync_fields
	 *
	 * @return mixed|null|void
	 */
	public static function merge( $post, array $sync_fields ) {
		return apply_filters( 'bea_csf.server.posttype.merge', self::get_data( $post, $sync_fields ), $sync_fields );
	}

	/**
	 * Generic method for get all data need for sync
	 *
	 * @param WP_Post|integer $post
	 * @param array $sync_fields
	 *
	 * @return array|boolean
	 */
	public static function get_data( $post, array $sync_fields ) {
		global $wpdb;

		if ( empty( $post ) ) {
			return false;
		}

		// Get object from object or ID
		$post = get_post( $post );
		if ( empty( $post ) ) {
			return false;
		}

		// Transform objet to array
		$post = (array) $post;

		// Get post metas
		$post['_thumbnail_id'] = (int) get_post_meta( $post['ID'], '_thumbnail_id', true );
		if ( $post['_thumbnail_id'] > 0 ) {
			$post['_thumbnail'] = BEA_CSF_Server_Attachment::get_data( $post['_thumbnail_id'] );
		} else {
			$post['_thumbnail'] = false;
		}

		// Get metas
		$post['meta_data'] = get_post_custom( $post['ID'] );

		// Remove some internal meta
		if ( isset( $post['meta_data']['_post_receivers'] ) ) {
			unset( $post['meta_data']['_post_receivers'] );
		}
		if ( isset( $post['meta_data']['_exclude_from_sync'] ) ) {
			unset( $post['meta_data']['_exclude_from_sync'] );
		}

		// Get terms for this object
		$taxonomies = get_object_taxonomies( $post['post_type'] );
		if ( false != $taxonomies ) {
			$post['terms']      = wp_get_object_terms( $post['ID'], $taxonomies );
			$post['taxonomies'] = $taxonomies;

			foreach ( $post['terms'] as $key => $term ) {
				$post['terms'][ $key ] = BEA_CSF_Server_Taxonomy::get_data( $term );
			}
		}

		// Init medias children
		$post['medias'] = array();

		// Get medias attachment
		$attachments = get_children(
			array(
				'post_parent' => $post['ID'],
				'post_type'   => 'attachment',
			)
		);

		foreach ( $attachments as $attachment ) {
			$post['medias'][] = BEA_CSF_Server_Attachment::get_data( $attachment );
		}

		// Get P2P connections
		if ( defined('P2P_PLUGIN_VERSION') ) {
			$results = (array) $wpdb->get_col( $wpdb->prepare("SELECT p2p_id FROM $wpdb->p2p WHERE p2p_from = %d OR p2p_to = %d", $post['ID'], $post['ID']) );

			$post['connections'] = array();
			foreach( $results as $result_id ) {
				$post['connections'][] = BEA_CSF_Server_P2P::merge( $result_id, $sync_fields );
			}
		}

		return $post;
	}

}