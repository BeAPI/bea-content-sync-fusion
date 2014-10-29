<?php
class BEA_CSF_Server_PostType {

	public static function delete( WP_Post $post, BEA_CSF_Synchronization $sync ) {
		return (array) $post;
	}

	public static function merge( WP_Post $post, BEA_CSF_Synchronization $sync ) {
		global $wpdb;
		
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
		if ( isset($post['meta_data']['_post_receivers']) ) {
			unset($post['meta_data']['_post_receivers']);
		}
		if ( isset($post['meta_data']['_exclude_from_sync']) ) {
			unset($post['meta_data']['_exclude_from_sync']);
		}
		
		// Get terms for this object
		$taxonomies = get_object_taxonomies( $post['post_type'] );
		if ( $taxonomies != false ) {
			$post['terms'] = wp_get_object_terms( $post['ID'], $taxonomies );
			$post['taxonomies'] = $taxonomies;
			
			foreach ( $post['terms'] as $key => $term ) {
				$post['terms'][$key] = BEA_CSF_Server_Taxonomy::get_data(  $term );
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