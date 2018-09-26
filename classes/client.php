<?php

class BEA_CSF_Client {

	public function __construct() {
		self::register_hooks();
	}

	public static function register_hooks() {
		// Attachments
		add_action( 'delete_attachment', array( __CLASS__, 'delete_attachment' ), PHP_INT_MAX, 1 );
		add_action( 'edit_attachment', array( __CLASS__, 'merge_attachment' ), PHP_INT_MAX, 1 );
		add_action( 'add_attachment', array( __CLASS__, 'merge_attachment' ), PHP_INT_MAX, 1 );

		// Attachments crop
		add_filter( 'wp_save_image_editor_file', array( __CLASS__, 'wp_save_image_editor_file' ), PHP_INT_MAX, 5 );
		add_filter( 'wp_update_attachment_metadata', array(
			__CLASS__,
			'wp_update_attachment_metadata'
		), PHP_INT_MAX, 2 );

		// Attachments - Manage AJAX actions on thumbnail post changes
		if ( isset( $_POST['thumbnail_id'] ) ) {
			add_action( 'updated_post_meta', array( __CLASS__, 'merge_post_meta' ), PHP_INT_MAX, 3 );
			add_action( 'deleted_post_meta', array( __CLASS__, 'merge_post_meta' ), PHP_INT_MAX, 3 );
		}

		// Post types
		add_action( 'transition_post_status', array( __CLASS__, 'transition_post_status' ), PHP_INT_MAX, 3 );
		add_action( 'delete_post', array( __CLASS__, 'delete_post' ), PHP_INT_MAX, 1 );

		// Terms
		// Use 990 priority for conflict with Polylang
		add_action( 'create_term', array( __CLASS__, 'merge_term' ), 990, 3 );
		add_action( 'edited_term', array( __CLASS__, 'merge_term' ), 990, 3 );
		add_action( 'delete_term', array( __CLASS__, 'delete_term' ), 990, 3 );

		// Terms/Post_type association
		add_action( 'set_object_terms', array( __CLASS__, 'set_object_terms' ), PHP_INT_MAX, 6 );

		// P2P
		add_action( 'p2p_created_connection', array( __CLASS__, 'p2p_created_connection' ), PHP_INT_MAX, 1 );
		add_action( 'p2p_delete_connections', array( __CLASS__, 'p2p_delete_connections' ), PHP_INT_MAX, 1 );
	}

	public static function unregister_hooks() {
		// Attachments
		remove_action( 'delete_attachment', array( __CLASS__, 'delete_attachment' ), PHP_INT_MAX );
		remove_action( 'edit_attachment', array( __CLASS__, 'merge_attachment' ), PHP_INT_MAX );
		remove_action( 'add_attachment', array( __CLASS__, 'merge_attachment' ), PHP_INT_MAX );

		// Attachments crop
		remove_filter( 'wp_save_image_editor_file', array( __CLASS__, 'wp_save_image_editor_file' ), PHP_INT_MAX );
		remove_filter( 'wp_update_attachment_metadata', array(
			__CLASS__,
			'wp_update_attachment_metadata'
		), PHP_INT_MAX, 2 );

		// Attachments - Manage AJAX actions on thumbnail post changes
		if ( isset( $_POST['thumbnail_id'] ) ) {
			remove_action( 'updated_post_meta', array( __CLASS__, 'merge_post_meta' ), PHP_INT_MAX );
			remove_action( 'deleted_post_meta', array( __CLASS__, 'merge_post_meta' ), PHP_INT_MAX );
		}

		// Post types
		remove_action( 'transition_post_status', array( __CLASS__, 'transition_post_status' ), PHP_INT_MAX );
		remove_action( 'delete_post', array( __CLASS__, 'delete_post' ), PHP_INT_MAX );

		// Terms
		remove_action( 'create_term', array( __CLASS__, 'merge_term' ), PHP_INT_MAX );
		remove_action( 'edited_term', array( __CLASS__, 'merge_term' ), PHP_INT_MAX );
		remove_action( 'delete_term', array( __CLASS__, 'delete_term' ), PHP_INT_MAX );

		// Terms/Post_type association
		remove_action( 'set_object_terms', array( __CLASS__, 'set_object_terms' ), PHP_INT_MAX, 6 );

		// P2P
		remove_action( 'p2p_created_connection', array( __CLASS__, 'p2p_created_connection' ), PHP_INT_MAX );
		remove_action( 'p2p_delete_connection', array( __CLASS__, 'p2p_delete_connection' ), PHP_INT_MAX );
	}

	/**
	 * @param int $attachment_id
	 *
	 * @return bool
	 */
	public static function delete_attachment( $attachment_id = 0 ) {
		// Get post
		$attachment = get_post( $attachment_id );
		if ( false === $attachment || is_wp_error( $attachment ) ) {
			return false;
		}

		// Is attachment ?
		if ( 'attachment' !== $attachment->post_type ) {
			return false;
		}

		// Is synchronized content ?
		$emitter_relation = BEA_CSF_Relations::current_object_is_synchronized( array(
			'posttype',
			'attachment'
		), get_current_blog_id(), $attachment->ID );
		if ( ! empty( $emitter_relation ) ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'Attachment' . '/' . 'delete' . '/attachment/' . get_current_blog_id(), $attachment, false, false, false );

		return true;
	}

	/**
	 * @param int $attachment_id
	 *
	 * @return bool
	 */
	public static function merge_attachment( $attachment_id = 0 ) {
		// Get post
		$attachment = get_post( $attachment_id );
		if ( false === $attachment || is_wp_error( $attachment ) ) {
			return false;
		}

		// Is attachment ?
		if ( 'attachment' !== $attachment->post_type ) {
			return false;
		}

		// Is synchronized content ?
		$emitter_relation = BEA_CSF_Relations::current_object_is_synchronized( array(
			'posttype',
			'attachment'
		), get_current_blog_id(), $attachment->ID );
		if ( ! empty( $emitter_relation ) ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'Attachment' . '/' . 'merge' . '/attachment/' . get_current_blog_id(), $attachment, false, false, false );

		return true;
	}

	/**
	 * Manual user image crop, need resync !
	 *
	 * @param $bool
	 * @param $filename
	 * @param $image
	 * @param $mime_type
	 * @param $post_id
	 *
	 * @return bool
	 */
	public static function wp_save_image_editor_file( $bool, $filename, $image, $mime_type, $post_id ) {
		$post = get_post( $post_id );
		if ( false === $post || is_wp_error( $post ) ) {
			return $bool;
		}

		// Use merge_attachment method (avoid duplicate code)
		self::merge_attachment( $post_id );

		return $bool;
	}

	/**
	 * WP image crop, need resync !
	 *
	 * @param $data
	 * @param $post_id
	 *
	 * @return mixed $data
	 */
	public static function wp_update_attachment_metadata( $data, $post_id ) {
		$post = get_post( $post_id );
		if ( false === $post || is_wp_error( $post ) ) {
			return $data;
		}

		// Use merge_attachment method (avoid duplicate code)
		self::merge_attachment( $post_id );

		return $data;
	}

	/**
	 * @param int $meta_id
	 * @param int $post_id
	 * @param string $meta_key
	 *
	 * @return bool
	 */
	public static function merge_post_meta( $meta_id = 0, $post_id = 0, $meta_key = '' ) {
		if ( '_thumbnail_id' == $meta_key ) {
			$post = get_post( $post_id );
			if ( false === $post || is_wp_error( $post ) ) {
				return false;
			}

			// Use transition post status method (avoid duplicate code)
			self::transition_post_status( $post->post_status, $post->post_status, $post );

			return true;
		}

		return false;
	}

	/**
	 * @param string $new_status
	 * @param string $old_status
	 * @param null $post
	 *
	 * @return bool
	 */
	public static function transition_post_status( $new_status = '', $old_status = '', $post = null ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		$post = get_post( $post );
		if ( false === $post || is_wp_error( $post ) ) {
			return false;
		}

		// Go out if post is revision
		if ( 'revision' === $post->post_type ) {
			return false;
		}

		// Auto Sync - Exclude meta ?
		$is_excluded_from_sync = (boolean) get_post_meta( $post->ID, '_exclude_from_sync', true );

		// Manual sync - Selected receivers
		$_post_receivers = get_post_meta( $post->ID, '_b' . get_current_blog_id() . '_post_receivers', true );

		// Allow 3rd plugin manipulation for post_status
		$allowed_new_status = apply_filters( 'bea/csf/client/allowed_new_status', [
			'publish',
			'future',
			'offline',
			'private'
		], $new_status, $old_status, $post );
		$allowed_old_status = apply_filters( 'bea/csf/client/allowed_old_status', [
			'draft',
			'trash',
			'pending'
		], $old_status, $new_status, $post );

		// Ignore post status:  auto-draft, inherit
		// See: https://codex.wordpress.org/Post_Status

		// Check for new publication
		if ( in_array( $new_status, $allowed_new_status, true ) ) {
			if ( class_exists( 'acf' ) ) {
				do_action( 'acf/save_post', $post->ID );
			}
			do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'merge' . '/' . $post->post_type . '/' . get_current_blog_id(), $post, $is_excluded_from_sync, $_post_receivers, false );
		} elseif ( $new_status !== $old_status && in_array( $new_status, $allowed_old_status, true ) ) { // Check for unpublish
			do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $post->post_type . '/' . get_current_blog_id(), $post, $is_excluded_from_sync, $_post_receivers, false );
		}

		return true;
	}

	/**
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public static function delete_post( $post_id = 0 ) {
		$post = get_post( $post_id );
		if ( false === $post || is_wp_error( $post ) ) {
			return false;
		}

		// Go out if post is revision
		if ( 'revision' === $post->post_type ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $post->post_type . '/' . get_current_blog_id(), $post, false, false, false );

		return true;
	}

	/**
	 * @param int $p2p_id
	 *
	 * @return bool
	 */
	public static function p2p_created_connection( $p2p_id = 0 ) {
		$connection = p2p_get_connection( (int) $p2p_id );
		if ( false === $connection ) {
			return false;
		}

		do_action( 'bea-csf' . '/' . 'P2P' . '/' . 'merge' . '/' . $connection->p2p_type . '/' . get_current_blog_id(), $connection, false, false, false );

		return true;
	}

	/**
	 * @param array $p2p_ids
	 *
	 * @return bool
	 */
	public static function p2p_delete_connections( $p2p_ids = array() ) {
		$p2p_ids = (array) $p2p_ids;
		foreach ( $p2p_ids as $p2p_id ) {
			$connection = p2p_get_connection( (int) $p2p_id );
			if ( false === $connection ) {
				continue;
			}

			do_action( 'bea-csf' . '/' . 'P2P' . '/' . 'delete' . '/' . $connection->p2p_type . '/' . get_current_blog_id(), $connection, false, false, false );
		}

		return true;
	}

	/**
	 * @param int $term_id
	 * @param int $tt_id
	 * @param string $taxonomy
	 *
	 * @return bool
	 */
	public static function delete_term( $term_id = 0, $tt_id = 0, $taxonomy = '' ) {
		// Rebuild fake obj
		$deleted_term                   = new stdClass();
		$deleted_term->term_id          = $term_id;
		$deleted_term->term_taxonomy_id = $tt_id;
		$deleted_term->taxonomy         = $taxonomy;

		do_action( 'bea-csf' . '/' . 'Taxonomy' . '/' . 'delete' . '/' . $taxonomy . '/' . get_current_blog_id(), $deleted_term, false, false, false );

		return true;
	}

	/**
	 * @param int $term_id
	 * @param int $tt_id
	 * @param bool $taxonomy
	 *
	 * @return bool
	 */
	public static function merge_term( $term_id = 0, $tt_id = 0, $taxonomy = false ) {
		// Get term
		$term = get_term( $term_id, $taxonomy );
		if ( false === $term || is_wp_error( $term ) ) {
			return false;
		}

		// Manual sync - Selected receivers
		$_term_receivers = (array) get_term_meta( $term->term_id, '_term_receivers', true );
		$_term_receivers = array_filter( $_term_receivers );

		do_action( 'bea-csf' . '/' . 'Taxonomy' . '/' . 'merge' . '/' . $taxonomy . '/' . get_current_blog_id(), $term, false, $_term_receivers, false );

		return true;
	}

	/**
	 * Hook call when a post/attachment is linked with term for any taxonomy
	 *
	 * @param $object_id
	 * @param array $terms
	 * @param array $tt_ids
	 * @param $taxonomy
	 * @param $append
	 * @param array $old_tt_ids
	 *
	 * @return bool
	 */
	public static function set_object_terms( $object_id, array $terms, array $tt_ids, $taxonomy, $append, array $old_tt_ids ) {
		// Resend terms to queue
		if ( ! empty( $terms ) && is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( ! isset( $term->term_id ) ) { // Keep only WP_Term object
					continue;
				}

				self::merge_term( $term->term_id, $term->term_taxonomy_id, $taxonomy );
			}
		}

		// Resend post/attachment to queue
		$post = get_post( $object_id );
		if ( false === $post || is_wp_error( $post ) ) {
			return false;
		}

		if ( 'attachment' === $post->post_type ) {
			self::merge_attachment( $post->ID );
		} else {
			self::transition_post_status( $post->post_status, $post->post_status, $post );
		}

		return true;

	}
}
