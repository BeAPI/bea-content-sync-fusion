<?php

class BEA_CSF_Client_P2P {
	/*
	array (
	  'p2p_id' => '7',
	  'p2p_from' => '104',
	  'p2p_to' => '29',
	  'p2p_type' => 'establishment_to_ambassador',
	  'blogid' => 1,
	)
	*/

	/**
	 * Add connection on DB
	 */
	public static function merge( array $data, array $sync_fields ) {

		// P2P Type must be sync ?
		if ( ! in_array( $data['p2p_type'], $sync_fields['p2p_connections'] ) ) {
			return false;
		}

		// From (post/users)
		if ( $data['p2p_obj']->side['from']->get_object_type() != 'user' ) {
			// Posts exists ?
			$p2p_from_local = BEA_CSF_Relations::get_post_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['p2p_from'], $data['p2p_from'] );
		} else {
			$p2p_from_local = $data['p2p_from'];

			// Prefered role by connection ?
			$role = isset( $data['p2p_obj']->side['from']->query_vars['role'] ) ? $data['p2p_obj']->side['from']->query_vars['role'] : 'subscriber';

			// Try to user to blog (if need) and set right role for this connection
			self::maybe_add_user_to_current_blog( $p2p_from_local, $role );
		}

		// To (post/users)
		if ( $data['p2p_obj']->side['to']->get_object_type() != 'user' ) {
			// Posts exists ?
			$p2p_to_local = BEA_CSF_Relations::get_post_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['p2p_to'], $data['p2p_to'] );
		} else {
			$p2p_to_local = $data['p2p_to'];

			// Prefered role by connection ?
			$role = isset( $data['p2p_obj']->side['to']->query_vars['role'] ) ? $data['p2p_obj']->side['to']->query_vars['role'] : 'subscriber';

			// Try to user to blog (if need) and set right role for this connection
			self::maybe_add_user_to_current_blog( $p2p_to_local, $role );
		}

		// If from or empty not exists, stop process
		if ( empty( $p2p_from_local ) || empty( $p2p_to_local ) ) {
			return false;
		}

		// Create connection
		p2p_type( $data['p2p_type'] )->connect( $p2p_from_local, $p2p_to_local, array(
			'date' => current_time( 'mysql' ),
		) );
	}

	/**
	 * Delete a connection, take the master id, try to find the new ID and delete local connection
	 *
	 * @param array $term
	 *
	 * @return \WP_Error|boolean
	 */
	public static function delete( array $data, array $sync_fields ) {
		// P2P Type must be sync ?
		if ( ! in_array( $data['p2p_type'], $sync_fields['p2p_connections'] ) ) {
			return false;
		}

		// From (post/users)
		if ( $data['p2p_obj']->side['from']->get_object_type() != 'user' ) {
			// Posts exists ?
			$p2p_from_local = BEA_CSF_Relations::get_post_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['p2p_from'], $data['p2p_from'] );
		} else {
			$p2p_from_local = $data['p2p_from'];
		}

		// To (post/users)
		if ( $data['p2p_obj']->side['to']->get_object_type() != 'user' ) {
			// Posts exists ?
			$p2p_to_local = BEA_CSF_Relations::get_post_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['p2p_to'], $data['p2p_to'] );
		} else {
			$p2p_to_local = $data['p2p_to'];
		}

		// If from or empty not exists, stop process
		if ( empty( $p2p_from_local ) || empty( $p2p_to_local ) ) {
			return false;
		}

		// Delete connection
		p2p_type( $data['p2p_type'] )->disconnect( $p2p_from_local, $p2p_to_local );
	}

	public static function maybe_add_user_to_current_blog( $user_id, $prefered_role = 'subscriber' ) {
		global $wpdb;

		// Get blogs for user
		$blogs = get_blogs_of_user( $user_id, true );

		// Add user to current blog if not exist
		if ( ! isset( $blogs[ $wpdb->blogid ] ) ) {
			add_user_to_blog( $wpdb->blogid, $user_id, $prefered_role );
		} else {
			wp_update_user( array(
					'ID'   => $user_id,
					'role' => $prefered_role,
				)
			);
		}
	}

}