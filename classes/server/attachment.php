<?php
class BEA_CSF_Server_Attachment {

	/**
	 * Insert or update attachment 
	 *
	 * @param WP_Post $attachment
	 * @return array
	 */
	public static function merge( WP_Post $attachment, BEA_CSF_Synchronization $sync ) {
		return self::get_data( $attachment );
	}

	/**
	 * Get deletation data
	 * 
	 * @param WP_Post $attachment
	 * @return integer|boolean
	 */
	public static function delete( WP_Post $attachment, BEA_CSF_Synchronization $sync ) {
		// Is attachement of post OR term ?
		$parent = get_post( $attachment->post_parent, ARRAY_A );

		// Is post parent ?
		// TODO ? Keep this code ?
		if ( !empty( $parent ) ) {
			return $attachment;
		}

		return false;
	}

	/**
	 * Generic method for get all data need for sync
	 * 
	 * @param integer|WP_Post $attachment_id
	 * @return array|boolean
	 */
	public static function get_data( $attachment = false ) {
		$attachment = get_post( $attachment, ARRAY_A );
		if ( empty( $attachment ) ) {
			return false;
		}

		$attachment['meta'] = get_post_custom( $attachment_id );
		$attachment['attachment_url'] = get_permalink( $attachment_id );
		$attachment['attachment_dir'] = get_attached_file( $attachment_id );

		return $attachment;
	}

}