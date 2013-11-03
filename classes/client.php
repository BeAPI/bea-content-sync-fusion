<?php
class BEA_CSF_Client {

	public function __construct() {
		// Attachments
		add_action( 'delete_attachment', array( __CLASS__, 'delete_attachment' ), 20, 1 );
		add_action( 'edit_attachment', array( __CLASS__, 'merge_attachment' ), 20, 1 );
		add_action( 'add_attachment', array( __CLASS__, 'merge_attachment' ), 20, 1 );

		// Attachments - Manage AJAX actions on thumbnail post changes
		if ( isset( $_POST['thumbnail_id'] ) ) {
			add_action( 'updated_' . 'post' . '_meta', array( __CLASS__, 'merge_post_meta' ), 20, 3 );
			add_action( 'deleted_' . 'post' . '_meta', array( __CLASS__, 'merge_post_meta' ), 20, 3 );
		}

		// Post types
		add_action( 'transition_post_status', array( __CLASS__, 'transition_post_status' ), 20, 3 );
		add_action( 'delete_post', array( __CLASS__, 'delete_post' ), 20, 1 );

		// Terms
		add_action( 'create_term', array( __CLASS__, 'merge_term' ), 20, 3 );
		add_action( 'edit_term', array( __CLASS__, 'merge_term' ), 20, 3 );
		add_action( 'delete_term', array( __CLASS__, 'delete_term' ), 20, 3 );

		// Notifications 
		add_action( 'bea-csf-client-notifications', array( __CLASS__, 'send_notifications' ), 10, 5 );
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

		// Exclude content created by sync plugin
		$_origin_key = get_post_meta( $attachment->ID, '_origin_key', true );
		if ( $_origin_key != false ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'Attachment' . '/' . 'delete' . '/attachment/' . $wpdb->blogid, $attachment, false, false, false );
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

		// Exclude content created by sync plugin
		$_origin_key = get_post_meta( $attachment->ID, '_origin_key', true );
		if ( $_origin_key != false ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'Attachment' . '/' . 'merge' . '/attachment/' . $wpdb->blogid, $attachment, false, false, false );
		return true;
	}

	public static function merge_post_meta( $meta_id = 0, $post_id = 0, $meta_key = '' ) {
		if ( $meta_key == '_thumbnail_id' ) {
			$post = get_post( $post_id );
			if ( $post == false || is_wp_error( $post ) ) {
				return false;
			}

			// Use transition post status method (avoid duplicate code)
			self::transition_post_status( $post->post_status, $post->post_status, $post );
			return true;
		}

		return false;
	}

	public static function transition_post_status( $new_status = '', $old_status = '', $post = null ) {
		global $wpdb;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		$post = get_post( $post );
		if ( $post == false || is_wp_error( $post ) ) {
			return false;
		}

		// Exclude content created by sync plugin
		$_origin_key = get_post_meta( $post->ID, '_origin_key', true );
		if ( $_origin_key != false ) {
			return false;
		}
		
		// Auto Sync - Exclude meta ?
		$is_excluded_from_sync = (boolean) get_post_meta( $post->ID, '_exclude_from_sync', true );

		// Manual sync - Selected receivers
		$_post_receivers = get_post_meta( $post->ID, '_post_receivers', true );

		// Check for new publication
		if ( $new_status == 'publish' ) {
			// Check status ?
			if ( $post->post_status != 'publish' ) {
				return false;
			}

			do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'merge' . '/' . $post->post_type . '/' . $wpdb->blogid, $post, $is_excluded_from_sync, $_post_receivers, false );
		} elseif ( $new_status != $old_status && $old_status == 'publish' ) { // Check for unpublish
			do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $post->post_type . '/' . $wpdb->blogid, $post, $is_excluded_from_sync, $_post_receivers, false );
		}

		return true;
	}

	public static function delete_post( $post_id = 0 ) {
		global $wpdb;

		$post = get_post( $post_id );
		if ( $post == false || is_wp_error( $post ) ) {
			return false;
		}

		// Exclude content created by sync plugin
		$_origin_key = get_post_meta( $post->ID, '_origin_key', true );
		if ( $_origin_key != false ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $post->post_type . '/' . $wpdb->blogid, $post, false, false, false );
		return true;
	}

	public static function delete_term( $term_id = 0, $tt_id = 0, $taxonomy = false ) {
		global $wpdb;

		// Get term
		$term = get_term( $term_id, $taxonomy );
		if ( $term == false || is_wp_error( $term ) ) {
			return false;
		}

		// Exclude content created by sync plugin
		$_origin_key = get_term_taxonomy_meta( $term->term_taxonomy_id, '_origin_key', true );
		if ( $_origin_key != false ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'Taxonomy' . '/' . 'delete' . '/' . $taxonomy . '/' . $wpdb->blogid, $term, false, false, false );
		return true;
	}

	public static function merge_term( $term_id = 0, $tt_id = 0, $taxonomy = false ) {
		global $wpdb;

		// Get term
		$term = get_term( $term_id, $taxonomy );
		if ( $term == false || is_wp_error( $term ) ) {
			return false;
		}

		// Exclude content created by sync plugin
		$_origin_key = get_term_taxonomy_meta( $term->term_taxonomy_id, '_origin_key', true );
		if ( $_origin_key != false ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'Taxonomy' . '/' . 'merge' . '/' . $taxonomy . '/' . $wpdb->blogid, $term, false, false, false );
		return true;
	}

	public static function send_notifications( $result, $object, $method, $blogid, BEA_CSF_Synchronization $sync ) {
		// Enable notification only post type edition/addition
		if ( $object != 'PostType' || $method != 'merge' ) {
			return false;
		}

		// Test if result of insertion are an error
		if ( is_wp_error($result) ) {
			return false;
		}

		// Notify only if result is an addition on DB, not edition...
		if( isset($result->is_edition) && $result->is_edition == true ) {
			return false;
		}

		// Get current DB options
		$current_values = get_option( BEA_CSF_OPTION . '-notifications' );

		// Get users ID for current sync
		if ( !isset($current_values[$sync->id]) ) {
			return false;
		}
		
		// Get current user logged
		$current_user = get_current_user();

		// Get post author
		$post_author = new WP_User( $result->post_author );

		// Prepare subject text
		$subject = sprintf( __('New or update post on website : %s', BEA_CSF_LOCALE), get_bloginfo('name') );

		// Loop on users to notify
		foreach( $current_values[$sync->id] as $user_id ) {
			$user = new WP_User( $user_id );
			if ( empty($user) || is_wp_error($user) || $user->ID == $current_user->ID ) { // Test user validity, and exclude current user logged from self-notifications
				continue;
			}

			// Prepare message text
			$message  = sprintf( __( 'Post title : "%s"' ), $result->post_title ) . "\r\n";
			$message .= sprintf( __('Author : %s'), $post_author->display_name ) . "\r\n";
			$message .= sprintf( __('E-mail : %s'), $post_author->user_email ) . "\r\n";
			$message .= sprintf( __('Permalink: %s'), get_permalink( $result ) ) . "\r\n";
			if ( user_can( $user->ID, 'edit_post', $result->ID ) ) {
				if ( EMPTY_TRASH_DAYS ) {
					$message .= sprintf( __('Trash it: %s'), get_delete_post_link($result->ID) ) . "\r\n";
				} else {
					$message .= sprintf( __('Delete it: %s'), get_delete_post_link($result->ID) ) . "\r\n";
				}
				$message .= sprintf( __('Edit it: %s'), get_edit_post_link($result->ID) ) . "\r\n";
			}

			// Send mail
			wp_mail( $user->user_email, $subject, $message );
		}
	}

}