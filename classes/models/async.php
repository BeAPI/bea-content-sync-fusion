<?php

class BEA_CSF_Async {
	/**
	 * Generic method to get data from emitter and sent theses to receivers
	 *
	 * @return boolean
	 */
	public static function process_queue( $quantity = false ) {
		// Get data to sync
		if ( (int) $quantity > 0 ) {
			$data_to_sync = self::get_results( $quantity );
		} else {
			$data_to_sync = self::get_all();
		}


		// No data ?
		if ( empty( $data_to_sync ) ) {
			return false;
		}

		// Process for row of the queue
		foreach ( $data_to_sync as $sync ) {
			// Unserialize complex fields
			$sync->hook_data = maybe_unserialize( $sync->hook_data );
			$sync->fields    = maybe_unserialize( $sync->fields );

			// Complete field with current receiver blog id
			$sync->fields['_current_receiver_blog_id'] = $sync->receiver_blog_id;

			// Explode filter for get object and method
			$current_filter_data = explode( '/', $sync->current_filter );

			// Set data into variable for improve lisibility
			$object  = $current_filter_data[1];
			$method  = $current_filter_data[2];
			$blog_id = $current_filter_data[4];

			// Switch to emitter blog
			switch_to_blog( $blog_id );

			// Get data from SERVER class
			$data_to_transfer = call_user_func( array(
				'BEA_CSF_Server_' . $object,
				$method,
			), $sync->hook_data, $sync->fields );
			if ( $data_to_transfer == false ) {
				// Remove from queue
				self::delete( $sync->id );
				
				continue;
			}

			// Append origin blog id to data to transfer
			$data_to_transfer['blogid'] = (int) $blog_id;

			// Receiver blog exist always ?
			$blog_data = get_blog_details( $sync->receiver_blog_id, false );
			if( $blog_data === false ) {
				// Remove from queue
				self::delete( $sync->id );

				continue;
			}

			// Switch to receiver blog
			switch_to_blog( $sync->receiver_blog_id );

			// Deactive hooks plugin
			BEA_CSF_Client::unregister_hooks();

			// Allow plugin to hook
			$data_to_transfer = apply_filters( 'bea_csf_client_' . $object . '_' . $method . '_data_to_transfer', $data_to_transfer, $sync->receiver_blog_id, $sync->fields );

			// Flush POST variables
			$_backup_POST = $_POST;
			$_POST = array();

			// Send data to CLIENT classes
			$result = call_user_func( array( 'BEA_CSF_Client_' . $object, $method ), $data_to_transfer, $sync->fields );

			// Restore POST variables
			$_POST = $_backup_POST;

			// Reactive hooks plugin
			BEA_CSF_Client::register_hooks();

			// Allow users notifications
			if ( (int) $sync->fields['notifications'] == 1 ) {
				do_action( 'bea-csf-client-notifications', $result, $object, $method, $blog_id, $sync->fields );
			}

			// Remove from queue
			self::delete( $sync->id );
		}

		do_action( 'bea-csf-after-async-queue' );

		return true;
	}

	/**
	 * @param $hook_data
	 * @param $current_filter
	 * @param $receiver_blog_id
	 * @param $fields
	 *
	 * @return mixed
	 */
	public static function insert( $hook_data, $current_filter, $receiver_blog_id, $fields ) {
		global $wpdb;

		$pre = apply_filters( 'bea-csf-async-insert', false, $hook_data, $current_filter, $receiver_blog_id, $fields );
		if ( false !== $pre ) {
			return $pre;
		}

		add_filter( 'query', array(__CLASS__, 'alter_query_ignore_insert') );

		/** @var WPDB $wpdb */
		$wpdb->insert(
			$wpdb->bea_csf_queue,
			array(
				'hook_data'        => maybe_serialize( $hook_data ),
				'current_filter'   => $current_filter,
				'receiver_blog_id' => $receiver_blog_id,
				'fields'           => maybe_serialize( $fields )
			),
			array( '%s', '%s', '%d', '%s' ),
			'INSERT'
		);

		remove_filter( 'query', array(__CLASS__, 'alter_query_ignore_insert') );

		return $wpdb->insert_id;
	}

	/**
	 * Update on fly request on MySQL
	 */
	public static function alter_query_ignore_insert( $query ) {
		$query = str_replace('INSERT', 'INSERT IGNORE', $query);
		return $query;
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public static function delete( $id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->delete( $wpdb->bea_csf_queue, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public static function get_all() {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_results( "SELECT * FROM $wpdb->bea_csf_queue" );
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public static function get_results( $quantity = 100 ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_queue LIMIT %d", $quantity ) );
	}
}