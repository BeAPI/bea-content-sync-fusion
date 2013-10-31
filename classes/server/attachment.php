<?php
class BEA_CSF_Server_Attachment {

	/**
	 * Insert or update attachment 
	 * @global type $wpdb
	 * @param WP_Post $attachment
	 * @return array
	 */
	public static function merge( WP_Post $attachment ) {
		return self::get_data( $attachment->ID );
	}

	/**
	 * Get deletation data
	 * 
	 * @param WP_Post $attachment
	 * @return array|boolean
	 */
	public static function delete( WP_Post $attachment ) {
		// Is attachement of post OR term ?
		$parent = get_post( $attachment->post_parent );

		// Is post parent ?
		if ( !empty( $parent ) ) {
			return $attachment->ID;
		} elseif ( function_exists( 'taxonomy_image_plugin_get_associations' ) && empty( $parent ) ) { // TODO: Keep this code ?
			// Get associations
			$assocs = taxonomy_image_plugin_get_associations();

			// Search value and delete
			if ( array_search( $attachment->ID, $assocs ) !== false ) {
				return $attachment->ID;
			}
		}

		return false;
	}

	/**
	 * Generic method for get all data need for sync
	 * 
	 * @param integer $attachment_id
	 * @return array|boolean
	 */
	public static function get_data( $attachment_id = 0 ) {
		$attachment = get_post( $attachment_id, ARRAY_A, 'display' );
		if ( empty( $attachment ) ) {
			return false;
		}

		$attachment['meta'] = get_post_custom( $attachment_id );
		$attachment['attachment_url'] = get_permalink( $attachment_id );
		$attachment['attachment_dir'] = get_attached_file( $attachment_id );

		return $attachment;
	}

}