<?php class BEA_CSF_Addon_P2P {
	/**
	 * The connection looks like :
	 * array (
	 *  'p2p_id'    => '7',
	 *  'p2p_from'  => '104',
	 *  'p2p_to'    => '29',
	 *  'p2p_type'  => 'establishment_to_ambassador',
	 *  'blogid'    => 1
	 * )
	 */
	public function __construct() {
		// Client
		add_action( 'bea_csf.client.posttype.merge', array( $this, 'set_p2p_connection' ), 10, 3 );

		// P2P Client
		add_action( 'p2p_created_connection', array( $this, 'p2p_created_connection' ), PHP_INT_MAX, 1 );
		add_action( 'p2p_delete_connections', array( $this, 'p2p_delete_connections' ), PHP_INT_MAX, 1 );

		add_action( 'bea/csf/client/unregister_hooks', array( $this, 'unregister_hooks' ) );

		// Server
		add_filter( 'bea_csf.server.posttype.get_data', [ $this, 'get_connections' ], 10, 2 );

		// Sync
		add_action( 'bea/csf/tools/resync_all', [ $this, 'resync_all' ], 10, 2 );
	}

	// Unregister hooks
	public function unregister_hooks() {
		remove_action( 'p2p_created_connection', array( $this, 'p2p_created_connection' ), PHP_INT_MAX, 1 );
		remove_action( 'p2p_delete_connection', array( $this, 'p2p_delete_connection' ), PHP_INT_MAX, 1 );
	}

	/**
	 * Handle the resync all posts for all blog
	 *
	 * @param array $blog_ids
	 */
	public function resync_all( $blog_ids ) {
		foreach ( $blog_ids as $_blog_id ) {
			switch_to_blog( $_blog_id );

			printf( "Switch to blog %s\n", $_blog_id );

			// Posts
			BEA_CSF_Multisite::sync_all_posts( array(), true );
			printf( "Finish synchronizing posts for blog %s\n", $_blog_id );

			restore_current_blog();
		}
	}

	/**
	 * Set P2P connections
	 *
	 * @author Maxime CULEA
	 *
	 * @param $data
	 * @param $sync_fields
	 * @param $new_post
	 */
	public function set_p2p_connection( $data, $sync_fields, $new_post ) {
		if ( ! isset( $data['connections'] ) || empty( $data['connections'] ) ) {
			return;
		}

		foreach ( (array) $data['connections'] as $connection ) {
			$connection['blogid'] = $data['blogid'];
			$this->merge( $connection, $sync_fields );
		}
	}

	/**
	 * Add connection on DB
	 */
	public function merge( array $data, array $sync_fields ) {
		// P2P Type must be sync ?
		if ( ! in_array( $data['p2p_type'], $sync_fields['p2p_connections'] ) ) {
			return false;
		}

		// From (post/users)
		if ( $data['p2p_obj']->side['from']->get_object_type() != 'user' ) {
			// Posts exists ?
			$p2p_from_local = BEA_CSF_Relations::get_object_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['p2p_from'], $data['p2p_from'] );
		} else {
			$p2p_from_local = $data['p2p_from'];

			// Prefered role by connection ?
			$role = isset( $data['p2p_obj']->side['from']->query_vars['role'] ) ? $data['p2p_obj']->side['from']->query_vars['role'] : 'subscriber';

			// Try to user to blog (if need) and set right role for this connection
			$this->maybe_add_user_to_current_blog( $p2p_from_local, $role );
		}

		// To (post/users)
		if ( $data['p2p_obj']->side['to']->get_object_type() != 'user' ) {
			// Posts exists ?
			$p2p_to_local = BEA_CSF_Relations::get_object_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['p2p_to'], $data['p2p_to'] );
		} else {
			$p2p_to_local = $data['p2p_to'];

			// Prefered role by connection ?
			$role = isset( $data['p2p_obj']->side['to']->query_vars['role'] ) ? $data['p2p_obj']->side['to']->query_vars['role'] : 'subscriber';

			// Try to user to blog (if need) and set right role for this connection
			$this->maybe_add_user_to_current_blog( $p2p_to_local, $role );
		}

		// If from or empty not exists, stop process
		if ( empty( $p2p_from_local ) || empty( $p2p_to_local ) ) {
			return false;
		}

		// Create connection
		return p2p_type( $data['p2p_type'] )->connect( $p2p_from_local, $p2p_to_local, [ 'date' => current_time( 'mysql' ) ] );
	}

	/**
	 * @param $user_id
	 * @param string $prefered_role
	 *
	 */
	public function maybe_add_user_to_current_blog( $user_id, $prefered_role = 'subscriber' ) {
		global $wpdb;

		$blogs = get_blogs_of_user( $user_id, true );

		// Add user to current blog if not exist
		if ( ! isset( $blogs[ $wpdb->blogid ] ) ) {
			add_user_to_blog( $wpdb->blogid, $user_id, $prefered_role );
		} else {
			wp_update_user( array( 'ID' => $user_id, 'role' => $prefered_role ) );
		}
	}

	/**
	 * @param int $p2p_id
	 *
	 * @return bool
	 */
	public function p2p_created_connection( $p2p_id = 0 ) {
		global $wpdb;

		$connection = p2p_get_connection( (int) $p2p_id );
		if ( false === $connection ) {
			return false;
		}

		do_action( 'bea-csf/P2P/merge/' . $connection->p2p_type . '/' . $wpdb->blogid, $connection, false, false, false );

		return true;
	}

	/**
	 * @param array $p2p_ids
	 *
	 * @return bool
	 */
	public function p2p_delete_connections( $p2p_ids = array() ) {
		global $wpdb;

		$p2p_ids = (array) $p2p_ids;
		foreach ( $p2p_ids as $p2p_id ) {
			$connection = p2p_get_connection( (int) $p2p_id );
			if ( false === $connection ) {
				continue;
			}

			do_action( 'bea-csf/P2P/delete/' . $connection->p2p_type . '/' . $wpdb->blogid, $connection, false, false, false );
		}

		return true;
	}

	/**
	 * Get P2P connections
	 *
	 */
	public function get_connections( $post, $sync_fields ) {
		global $wpdb;
		$results = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->p2p WHERE p2p_from = %d OR p2p_to = %d", $post['ID'], $post['ID'] ) );

		$post['connections'] = array();
		foreach ( $results as $result ) {
			$post['connections'][] = $this->merge_server( $result, $sync_fields );
		}
	}

	/**
	 * Create connection
	 *
	 * @param  stdClass $connection [description]
	 * @param  array $sync_fields [description]
	 *
	 * @return array                [description]
	 */
	public function merge_server( $connection, array $sync_fields ) {
		$data            = (array) $connection;
		$data['p2p_obj'] = p2p_type( $data['p2p_type'] );

		return apply_filters( 'bea_csf.server.p2p.merge', $data, $sync_fields );
	}
}