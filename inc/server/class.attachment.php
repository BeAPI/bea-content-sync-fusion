<?php
class BEA_CSF_Server_Attachment {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		add_action( 'delete_attachment', array(__CLASS__, 'delete_attachment'), 10, 1 );
		add_action( 'edit_attachment', array(__CLASS__, 'merge_attachment'), 10, 1 );
		add_action( 'add_attachment', array(__CLASS__, 'merge_attachment'), 10, 1 );
		
		// Manage AJAX actions on thumbnail post changes
		if ( isset( $_POST['thumbnail_id'] ) ) {
			add_action( 'updated_'.'post'.'_meta', array(__CLASS__, 'merge_post_meta'), 10, 3 );
			add_action( 'deleted_'.'post'.'_meta', array(__CLASS__, 'merge_post_meta'), 10, 3 );
		}
	}
	
	public static function merge_attachment( $media_id = 0, $blog_id = 0 ) {
		global $wpdb;
		
		// Get current options
		$current_options = (array) get_site_option( BEA_CSF_OPTION );
		if ( $wpdb->blogid != $current_options['master'] ) {
			return false;
		}
		
		return BEA_CSF_Server_Client::send_to_clients( 'BEA_CSF_Client_Attachment', 'merge_attachment', self::get_attachment_data($media_id), $blog_id );
	}
	
	public static function get_attachment_data( $media_id = 0 ) {
		$attachment 					= get_post( $media_id, ARRAY_A, 'display' );
		$attachment['meta'] 			= get_post_custom($media_id);
		$attachment['attachment_url'] 	= get_permalink($media_id);
		$attachment['attachment_dir'] 	= get_attached_file($media_id);
		
		return $attachment;
	}

	/**
	 * Check for attachment deletion and send it to client
	 */
	public static function delete_attachment( $attachment_id = 0, $blog_id = 0 ) {
		global $wpdb;
		
		// Get current options
		$current_options = (array) get_site_option( BEA_CSF_OPTION );
		if ( $wpdb->blogid != $current_options['master'] ) {
			return false;
		}
		
		// Get post
		$attachment = get_post( $attachment_id );
		if ( $attachment == false || is_wp_error($attachment) ) {
			return false;
		}
		
		// Is attachment ?
		if ( $attachment->post_type !== 'attachment' ) {
			return false;
		}
		
		// Is attachement of media ? of term ?
		$parent = get_post( $attachment->post_parent );
		
		// Is post parent ?
		if ( isset($parent) && $parent != false ) {
			return BEA_CSF_Server_Client::send_to_clients( 'BEA_CSF_Client_Attachment', 'remove_attachement', $attachment_id, $blog_id );
		} elseif ( function_exists('taxonomy_image_plugin_get_associations') && $parent == false ) {
			// Get associations
			$assocs = taxonomy_image_plugin_get_associations();
		
			// Search value and delete
			if ( array_search($attachment_id, $assocs) !== false )
				return BEA_CSF_Server_Client::send_to_clients( 'BEA_CSF_Client_Attachment', 'remove_attachement', $attachment_id, $blog_id );
		}
		
		return false;
	}
	
	public static function merge_post_meta( $meta_id = 0, $object_id = 0, $meta_key = '', $blog_id = 0 ) {
		if ( $meta_key == '_thumbnail_id' ) {
			BEA_CSF_Server_PostType::wp_insert_post( $object_id, null, $blog_id );
		}
	}
}