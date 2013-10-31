<?php
class BEA_CSF_Server_Taxonomy {

	/**
	 * Check for term deletion and send it to client
	 */
	public static function delete( stdClass $term ) {
		return (array) $term;
	}

	/**
	 * Check for new term and send it to client
	 */
	public static function merge( stdClass $term ) {
		// Get parent TT_ID
		if ( $term->parent > 0 ) {
			$parent_term = get_term( $term->parent, $term->taxonomy );
			if ( $term != false && !is_wp_error( $term ) ) {
				$term->parent_tt_id = $parent_term->term_taxonomy_id;
			}
		} else {
			$term->parent_tt_id = 0;
		}

		// Get images
		$term->image_id = (int) BEA_CSF_Client_Taxonomy::get_media_id( $term->term_taxonomy_id );

		// Remove unused fields
		unset( $term->term_group, $term->count );

		// Send image to client if exist
		if ( $term->image_id != 0 ) {
			$term->image = get_post( $term->image_id, ARRAY_A );
			$term->image['meta'] = get_post_custom( $term->image_id );
		}

		// Add Server URL
		$term->server_url = home_url( '/' );
		$uploads = wp_upload_dir();
		$term->upload_url = $uploads['baseurl'];

		return (array) $term;
	}

}