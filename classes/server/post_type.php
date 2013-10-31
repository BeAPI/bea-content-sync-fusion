<?php
class BEA_CSF_Server_PostType {

	public static function delete( WP_Post $post ) {
		return $post->ID;
	}

	public static function merge( WP_Post $object ) {
		// Transform objet to array
		$object = (array) $object;

		// Get post metas
		$object['_thumbnail_id'] = (int) get_post_meta( $object['ID'], '_thumbnail_id', true );
		if ( $object['_thumbnail_id'] > 0 ) {
			$object['_thumbnail'] = BEA_CSF_Server_Attachment::get_attachment_data( $object['_thumbnail_id'] );
		} else {
			$object['_thumbnail'] = false;
		}

		// Get terms for this object
		$taxonomies = get_object_taxonomies( $object['post_type'] );
		if ( $taxonomies != false ) {
			$object['terms'] = wp_get_object_terms( $object['ID'], $taxonomies );
			foreach ( $object['terms'] as $taxonomy => $term ) {
				// Get parent TT_ID
				if ( $term->parent > 0 ) {
					$parent_term = get_term( $term->parent, $taxonomy );
					if ( $term != false && !is_wp_error( $term ) ) {
						$term->parent_tt_id = $parent_term->term_taxonomy_id;
					}
				} else {
					$term->parent_tt_id = 0;
				}

				$object['terms'][$taxonomy] = (array) $term;
			}
		}

		// TODO: Optimize code
		// Init medias children
		$object['medias'] = array( );

		// Get medias attachment
		$attachments = get_children( array( 'post_parent' => $object['ID'], 'post_type' => 'attachment' ), ARRAY_A );
		foreach ( $attachments as $attachment ) {
			$attachment['meta'] = get_post_custom( $attachment['ID'] );
			$attachment['attachment_url'] = get_permalink( $attachment['ID'] );
			$attachment['attachment_dir'] = get_attached_file( $attachment['ID'] );
			$object['medias'][] = $attachment;
		}

		// Add Server URL
		$object['server_url'] = home_url( '/' );
		$uploads = wp_upload_dir();
		$object['upload_url'] = $uploads['baseurl'];

		return $object;
	}

}