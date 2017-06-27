<?php

class BEA_CSF_Server_Attachment {

	/**
	 * Insert or update attachment
	 *
	 * @param WP_Post|integer $attachment
	 *
	 * @return array
	 */
	public static function merge( $attachment, array $sync_fields ) {
		return apply_filters( 'bea_csf.server.attachment.merge', self::get_data( $attachment ), $sync_fields );
	}

	/**
	 * Get deletation data
	 *
	 * @param WP_Post|integer $attachment
	 *
	 * @return array
	 */
	public static function delete( $attachment, array $sync_fields ) {
		return apply_filters( 'bea_csf.server.attachment.delete', array( 'ID' => $attachment ), $sync_fields );
	}

	/**
	 * Generic method for get all data need for sync
	 *
	 * @param WP_Post|integer $attachment_id
	 *
	 * @return array|boolean
	 */
	public static function get_data( $attachment = false ) {
		$attachment = get_post( $attachment, ARRAY_A );
		if ( empty( $attachment ) ) {
			return false;
		}

		$attachment['post_custom']    = get_post_custom( $attachment['ID'] );
		$attachment['attachment_url'] = get_permalink( $attachment['ID'] );
		$attachment['attachment_dir'] = get_attached_file( $attachment['ID'] );
		$attachment['metadata']       = wp_get_attachment_metadata( $attachment['ID'] );

		// Get terms for this object
		$taxonomies = get_object_taxonomies( $attachment['post_type'] );
		if ( false != $taxonomies ) {
			$attachment['terms']      = wp_get_object_terms( $attachment['ID'], $taxonomies );
			$attachment['taxonomies'] = $taxonomies;

			foreach ( $attachment['terms'] as $key => $term ) {
				$attachment['terms'][ $key ] = BEA_CSF_Server_Taxonomy::get_data( $term );
			}
		}

		return $attachment;
	}

}