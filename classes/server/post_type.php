<?php
class BEA_CSF_Server_PostType {

	public static function delete( WP_Post $post, BEA_CSF_Synchronization $sync ) {
		return (array) $post;
	}

	public static function merge( WP_Post $post, BEA_CSF_Synchronization $sync ) {
		// Transform objet to array
		$post = (array) $post;

		// Get post metas
		$post['_thumbnail_id'] = (int) get_post_meta( $post['ID'], '_thumbnail_id', true );
		if ( $post['_thumbnail_id'] > 0 ) {
			$post['_thumbnail'] = BEA_CSF_Server_Attachment::get_attachment_data( $post['_thumbnail_id'] );
		} else {
			$post['_thumbnail'] = false;
		}

		// Get terms for this object
		$taxonomies = get_object_taxonomies( $post['post_type'] );
		if ( $taxonomies != false ) {
			$post['terms'] = wp_get_object_terms( $post['ID'], $taxonomies );
			foreach ( $post['terms'] as $taxonomy => $term ) {
				// Get parent TT_ID
				if ( $term->parent > 0 ) {
					$parent_term = get_term( $term->parent, $taxonomy );
					if ( $term != false && !is_wp_error( $term ) ) {
						$term->parent_tt_id = $parent_term->term_taxonomy_id;
					}
				} else {
					$term->parent_tt_id = 0;
				}

				$post['terms'][$taxonomy] = (array) $term;
			}
		}

		// Init medias children
		$post['medias'] = array( );

		// Get medias attachment
		$attachments = get_children( array( 'post_parent' => $post['ID'], 'post_type' => 'attachment' ) );
		foreach ( $attachments as $attachment ) {
			$post['medias'][] = BEA_CSF_Server_Attachment::get_data( $attachment );
		}

		// Add Server URL
		$post['server_url'] = home_url( '/' );
		$uploads = wp_upload_dir();
		$post['upload_url'] = $uploads['baseurl'];

		return $post;
	}

}