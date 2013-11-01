<?php
class BEA_CSF_Server_Client {

	public function __construct() {
		// Attachments
		add_action( 'delete_attachment', array( __CLASS__, 'delete_attachment' ), 10, 1 );
		add_action( 'edit_attachment', array( __CLASS__, 'merge_attachment' ), 10, 1 );
		add_action( 'add_attachment', array( __CLASS__, 'merge_attachment' ), 10, 1 );

		// Attachments - Manage AJAX actions on thumbnail post changes
		if ( isset( $_POST['thumbnail_id'] ) ) {
			add_action( 'updated_' . 'post' . '_meta', array( __CLASS__, 'merge_post_meta' ), 10, 3 );
			add_action( 'deleted_' . 'post' . '_meta', array( __CLASS__, 'merge_post_meta' ), 10, 3 );
		}

		// Post types
		add_action( 'transition_post_status', array( __CLASS__, 'transition_post_status' ), 11, 3 );
		add_action( 'delete_post', array( __CLASS__, 'delete_post' ), 10, 1 );

		// Terms
		add_action( 'create_term', array( __CLASS__, 'merge_term' ), 10, 3 );
		add_action( 'edit_term', array( __CLASS__, 'merge_term' ), 10, 3 );
		add_action( 'delete_term', array( __CLASS__, 'delete_term' ), 10, 3 );
	}

	public static function delete_attachment( $attachment_id = 0 ) {
		global $wpdb;

		// Get post
		$attachment = get_post( $attachment_id );
		if ( $attachment == false || is_wp_error( $attachment ) ) {
			return false;
		}

		// Is attachment ?
		if ( $attachment->post_type !== 'attachment' ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'Attachment' . '/' . 'delete' . '/attachment/' . $wpdb->blogid, $attachment );
		return true;
	}

	public static function merge_attachment( $attachment_id = 0 ) {
		global $wpdb;

		// Get post
		$attachment = get_post( $attachment_id );
		if ( $attachment == false || is_wp_error( $attachment ) ) {
			return false;
		}

		// Is attachment ?
		if ( $attachment->post_type !== 'attachment' ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'Attachment' . '/' . 'merge' . '/attachment/' . $wpdb->blogid, $attachment );
		return true;
	}

	public static function merge_post_meta( $meta_id = 0, $object_id = 0, $meta_key = '' ) {
		if ( $meta_key == '_thumbnail_id' ) {
			$object = get_post( $object_id );
			if ( $object == false || is_wp_error( $object ) ) {
				return false;
			}

			// Use transition post status method (avoid duplicate code)
			self::transition_post_status( $object->post_status, $object->post_status, $object );
			return true;
		}

		return false;
	}

	public static function transition_post_status( $new_status = '', $old_status = '', $post = null ) {
		global $wpdb;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		$object = get_post( $post );
		if ( $object == false || is_wp_error( $object ) ) {
			return false;
		}
		
		if ( $new_status == 'publish' ) {
			// Check status ?
			if ( $object->post_status != 'publish' ) {
				return false;
			}
			
			// Exclude meta ?
			$current_value = (int) get_post_meta( $object->ID, '_exclude_from_sync', true );
			if ( $current_value == 1 ) {
				return false;
			}
			
			do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'merge' . '/' . $post->post_type . '/' . $wpdb->blogid, $post );
		} elseif ( $new_status != $old_status && $old_status == 'publish' ) {
			do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $post->post_type . '/' . $wpdb->blogid, $post );
		}

		return true;
	}

	public static function delete_post( $post_id = 0 ) {
		global $wpdb;

		$post = get_post( $post_id );
		if ( $post == false || is_wp_error( $post ) ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $post->post_type . '/' . $wpdb->blogid, $post );
		return true;
	}

	public static function delete_term( $term_id = 0, $tt_id = 0, $taxonomy = false ) {
		global $wpdb;

		// Get term
		$term = get_term( $term_id, $taxonomy );
		if ( $term == false || is_wp_error( $term ) ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'Taxonomy' . '/' . 'delete' . '/' . $taxonomy . '/' . $wpdb->blogid, $term );
		return true;
	}

	public static function merge_term( $term_id = 0, $tt_id = 0, $taxonomy = false ) {
		global $wpdb;

		// Get term
		$term = get_term( $term_id, $taxonomy );
		if ( $term == false || is_wp_error( $term ) ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'Taxonomy' . '/' . 'merge' . '/' . $taxonomy . '/' . $wpdb->blogid, $term );
		return true;
	}

}