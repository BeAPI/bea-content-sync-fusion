<?php
class BEA_CSF_Server_PostType {
	/**
	 * Constructor, register hooks
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		add_action( 'transition_post_status', array( __CLASS__, 'transition_post_status'), 11, 3 );
		add_action( 'delete_post', array(__CLASS__, 'delete_post'), 10, 1 );
	}
	
	public static function transition_post_status( $new_status = '', $old_status = '', $post = null, $blog_id = 0 ) {
		global $wpdb;
		
		// Get current options
		$current_options = (array) get_site_option( BEA_CSF_OPTION );
		if ( $wpdb->blogid != $current_options['master'] ) {
			return false;
		}
		
		// Right post type ?
		if ( !in_array( $post->post_type, BEA_CSF_Server_Client::get_post_types() ) ) {
			return false;
		}
		
		if ( $new_status == 'publish' ) {
			self::wp_insert_post( $post->ID, $post, $blog_id );
		} elseif( $new_status != $old_status && $old_status == 'publish' ) {
			self::delete_post( $post->ID, $blog_id );
		}
		
		return true;
	}
	
	public static function delete_post( $object_id, $blog_id = 0 ) {
		global $wpdb;
		
		// Get current options
		$current_options = (array) get_site_option( BEA_CSF_OPTION );
		if ( $wpdb->blogid != $current_options['master'] ) {
			return false;
		}
		
		$object = get_post( $object_id, ARRAY_A );
		if ( $object == false || is_wp_error($object) ) {
			return false;
		}
		
		// Right post type ?
		if ( !in_array( $object['post_type'], BEA_CSF_Server_Client::get_post_types() ) ) {
			return false;
		}
		
		return BEA_CSF_Server_Client::send_to_clients( 'BEA_CSF_Client_PostType', 'remove_post', $object_id, $blog_id );
	}

	public static function wp_insert_post( $post_ID, $post = null, $blog_id = 0 ) {
		global $wpdb;
		
		// Get current options
		$current_options = (array) get_site_option( BEA_CSF_OPTION );
		if ( $wpdb->blogid != $current_options['master'] ) {
			return false;
		}
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false ;
		}
		
		// Get post
		$object = get_post( $post_ID, ARRAY_A );
		if ( $object == false || is_wp_error($object) ) {
			return false;
		}
		
		// Right post type ?
		if ( !in_array( $object['post_type'], BEA_CSF_Server_Client::get_post_types() ) ) {
			return false;
		}
		
		// Check status ?
		if ( $object['post_status'] != 'publish' ) {
			return false;
		}
		
		// Exclude meta ?
		$current_value = (int) get_post_meta( $post_ID, 'exclude_from_sync', true );
		if ( $current_value == 1 ) {
			return false;
		}
		
		// Get post metas
		$object['_thumbnail_id'] = (int) get_post_meta( $object['ID'], '_thumbnail_id', true );
		if ( $object['_thumbnail_id'] > 0 ) {
			$object['_thumbnail'] = BEA_CSF_Server_Attachment::get_attachment_data($object['_thumbnail_id']);
		} else {
			$object['_thumbnail'] = false;
		}
		
		// Get terms for this object
		$taxonomies = get_object_taxonomies( $object['post_type'] );
		if ( $taxonomies != false ) {
			$object['terms'] = wp_get_object_terms( $object['ID'], $taxonomies );
			foreach( $object['terms'] as $taxonomy => $term ) {
				// Get parent TT_ID
				if( $term->parent > 0 ) {
					$parent_term = get_term( $term->parent, $taxonomy );
					if ( $term != false && !is_wp_error($term) ) {
						$term->parent_tt_id = $parent_term->term_taxonomy_id;
					}
				} else {
					$term->parent_tt_id = 0;
				}
				
				$object['terms'][$taxonomy] = (array) $term;
			}
		}
		
		// Init medias children
		$object['medias'] = array();
		
		// Get medias attachment
		$attachments = & get_children( array('post_parent' => $object['ID'], 'post_type' => 'attachment' ), ARRAY_A );
		foreach( $attachments as $attachment ) {
			$attachment['meta'] = get_post_custom($attachment['ID']);
			$attachment['attachment_url'] = get_permalink($attachment['ID']);
			$attachment['attachment_dir'] = get_attached_file($attachment['ID']);
			$object['medias'][] = $attachment;
		}
		
		// Add Server URL
		$object['server_url'] = home_url('/');
		$uploads = wp_upload_dir();
		$object['upload_url'] =  $uploads['baseurl'];
		
		return BEA_CSF_Server_Client::send_to_clients( 'BEA_CSF_Client_PostType', 'new_post', $object, $blog_id );
	}
}