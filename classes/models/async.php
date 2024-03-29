<?php

class BEA_CSF_Async {
	/**
	 * Generic method to get data from emitter and sent theses to receivers
	 *
	 * @param boolean $quantity
	 * @param boolean|integer $receiver_blog_id
	 *
	 * @return boolean
	 */
	public static function process_queue( $quantity = false, $receiver_blog_id = false ) {
		// Get data to sync
		if ( (int) $quantity > 0 ) {
			$data_to_sync = self::get_results( $quantity, $receiver_blog_id );
		} else {
			$data_to_sync = self::get_all( $receiver_blog_id );
		}

		// No data ?
		if ( empty( $data_to_sync ) ) {
			return false;
		}

		// Process for row of the queue
		foreach ( $data_to_sync as $sync ) {
			self::process( $sync );
		}

		do_action( 'bea-csf-after-async-queue' );

		return true;
	}

	/**
	 * Exec one item from queue table for sync process
	 *
	 * @param $sync
	 *
	 * @return false|array
	 */
	public static function process( $sync ) {
		// Unserialize complex fields
		$sync->hook_data = maybe_unserialize( $sync->hook_data );
		$sync->fields    = maybe_unserialize( $sync->fields );

		// Complete field with current receiver blog id
		$sync->fields['_current_receiver_blog_id'] = (int) $sync->receiver_blog_id;

		// Explode filter for get object and method
		$current_filter_data = explode( '/', $sync->current_filter );

		// Set data into variable for improve lisibility
		$object  = $current_filter_data[1];
		$method  = $current_filter_data[2];
		$blog_id = $current_filter_data[4];

		// Switch to emitter blog
		switch_to_blog( $blog_id );

		// Get data from SERVER class
		$data_to_transfer = call_user_func(
			array(
				'BEA_CSF_Server_' . $object,
				$method,
			),
			$sync->hook_data,
			$sync->fields
		);

		if ( false === $data_to_transfer ) {
			// Remove from queue
			self::delete( $sync->id );

			return false;
		}

		// Append origin blog id to data to transfer
		$data_to_transfer['blogid'] = (int) $blog_id;

		// Receiver blog exist always ?
		$blog_data = get_blog_details( $sync->receiver_blog_id, false );
		if ( false === $blog_data ) {
			// Remove from queue
			self::delete( $sync->id );

			return false;
		}

		// Restore to receiver blog
		while ( ! empty( $GLOBALS['_wp_switched_stack'] ) ) {
			restore_current_blog();
		}

		// Deactive hooks plugin
		BEA_CSF_Client::unregister_hooks();

		// Allow plugin to hook
		$data_to_transfer = apply_filters( 'bea_csf_client_data_to_transfer', $data_to_transfer, $sync->receiver_blog_id, $sync->fields, $object, $method );
		$data_to_transfer = apply_filters( 'bea_csf_client_' . $object . '_' . $method . '_data_to_transfer', $data_to_transfer, $sync->receiver_blog_id, $sync->fields );

		// Flush POST variables
		$_backup_POST = $_POST;
		$_POST        = array();

		// Send data to CLIENT classes
		$result = call_user_func( array( 'BEA_CSF_Client_' . $object, $method ), $data_to_transfer, $sync->fields );
		// Restore POST variables
		$_POST = $_backup_POST;

		// Reactive hooks plugin
		BEA_CSF_Client::register_hooks();

		// Remove from queue
		self::delete( $sync->id );

		return $result;
	}

	/**
	 * Add an item into queue
	 *
	 * @param $type
	 * @param $hook_data
	 * @param $current_filter
	 * @param $receiver_blog_id
	 * @param $fields
	 *
	 * @return mixed
	 */
	public static function insert( $type, $hook_data, $current_filter, $receiver_blog_id, $fields ) {
		global $wpdb;

		$pre = apply_filters( 'bea-csf-async-insert', false, $hook_data, $current_filter, $receiver_blog_id, $fields, $type );
		if ( false !== $pre ) {
			return $pre;
		}

		// Explode filter for get object and method
		$current_filter_data = explode( '/', $current_filter );

		add_filter( 'query', array( __CLASS__, 'alter_query_ignore_insert' ) );

		/**
		 * Filters the fields for the database query.
		 *
		 * Sometimes you'll need to clear up the data before inserting it.
		 * On large multisite you will maybe want to remove the receivers and emitters entries.
		 *
		 * @param array $data Data that will be sent to database.
		 */
		$data = [
			'type'             => $type,
			'object_name'      => $current_filter_data[3],
			'hook_data'        => maybe_serialize( $hook_data ),
			'current_filter'   => $current_filter,
			'receiver_blog_id' => $receiver_blog_id,
			'fields'           => maybe_serialize( apply_filters( 'bea-csf-async-insert-fields', $fields ) ),
		];

		/** @var WPDB $wpdb */
		$wpdb->insert(
			$wpdb->bea_csf_queue,
			$data,
			array( '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		remove_filter( 'query', array( __CLASS__, 'alter_query_ignore_insert' ) );

		return $wpdb->insert_id;
	}

	/**
	 * Keep only the last action
	 *
	 * @param $type
	 * @param $hook_data
	 * @param $current_filter
	 * @param $receiver_blog_id
	 *
	 * @return bool|int
	 */
	public static function clean_rows_before_insert( $type, $hook_data, $current_filter, $receiver_blog_id ) {
		global $wpdb;
		/** @var WPDB $wpdb */

		// Explode filter for get object and method
		$current_filter_data    = explode( '/', $current_filter );
		$current_filter_data[2] = '%'; // Wildcard SQL for remove all kind of action
		$current_filter         = implode( '/', $current_filter_data );

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $wpdb->bea_csf_queue
			WHERE `type` = %s
			AND `hook_data` = %s
			AND `current_filter` LIKE %s
			AND `receiver_blog_id` = %d",
				$type,
				$hook_data,
				$current_filter,
				$receiver_blog_id
			)
		);
	}

	/**
	 * Update on fly request on MySQL
	 *
	 * @param string $query
	 *
	 * @return string
	 */
	public static function alter_query_ignore_insert( $query ) {
		$query = str_replace( 'INSERT', 'INSERT IGNORE', $query );

		return $query;
	}

	/**
	 * Delte an item from queue
	 *
	 * @param $id
	 *
	 * @return int|false
	 */
	public static function delete( $id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->delete( $wpdb->bea_csf_queue, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Get all items in queue
	 *
	 * @param integer $blog_id
	 *
	 * @return array
	 */
	public static function get_all( $blog_id = 0 ) {
		global $wpdb;
		/** @var WPDB $wpdb */

		if ( 0 < $blog_id ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_queue WHERE receiver_blog_id = %d", $blog_id ) );
		}

		return $wpdb->get_results( "SELECT * FROM $wpdb->bea_csf_queue" );
	}

	/**
	 * Flush table
	 *
	 * @return false|int
	 */
	public static function truncate() {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->query( "TRUNCATE $wpdb->bea_csf_queue" );
	}

	/**
	 * Get items
	 *
	 * @param integer $quantity
	 * @param integer $blog_id
	 *
	 * @return array
	 */
	public static function get_results( $quantity = 100, $blog_id = 0 ) {
		global $wpdb;

		// Allow to customize order queue
		$order_by = "FIELD(type, 'Taxonomy', 'Attachment', 'PostType') ASC";
		$order_by = apply_filters( 'bea_csf_async_get_results_orderby', $order_by, $blog_id );

		/** @var WPDB $wpdb */
		if ( 0 < $blog_id ) {
			return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_queue WHERE receiver_blog_id = %d ORDER BY $order_by LIMIT %d", $blog_id, $quantity ) );
		}

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_queue  ORDER BY $order_by LIMIT %d", $quantity ) );
	}

	/**
	 * Get counter items for a blog
	 *
	 * @param integer $blog_id
	 *
	 * @return integer
	 */
	public static function get_counter( $blog_id = 0 ) {
		global $wpdb;

		/** @var WPDB $wpdb */
		if ( 0 < $blog_id ) {
			return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $wpdb->bea_csf_queue WHERE receiver_blog_id = %d", $blog_id ) );
		}

		return (int) $wpdb->get_var( "SELECT COUNT(id) FROM $wpdb->bea_csf_queue" );
	}

	/**
	 * Get blogs ids with content to sync
	 *
	 * @return array
	 */
	public static function get_blog_ids_from_queue() {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_col( "SELECT receiver_blog_id FROM $wpdb->bea_csf_queue GROUP BY receiver_blog_id ORDER BY receiver_blog_id ASC" );
	}

	/**
	 * Change on real time the variable with tablename for maintenance queue
	 */
	public static function switch_to_maintenance_queue() {
		$GLOBALS['wpdb']->bea_csf_queue = $GLOBALS['wpdb']->bea_csf_queue_maintenance;
	}

	/**
	 * Restore original tablename for queue
	 */
	public static function restore_main_queue() {
		$GLOBALS['wpdb']->bea_csf_queue = $GLOBALS['wpdb']->base_prefix . 'bea_csf_queue';
	}
}
