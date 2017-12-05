<?php

class BEA_CSF_Relations {
	/**
	 * BEA_CSF_Relations constructor.
	 * Hook delete post, attachment, term, blog...
	 *
	 */
	public function __construct() {
		add_action( 'deleted_post', array(__CLASS__, 'deleted_post'), 10 );
		add_action( 'delete_term', array(__CLASS__, 'delete_term'), 10 );
		add_action( 'deleted_blog', array(__CLASS__, 'deleted_blog'), 10 );
	}

	/**
	 * Delete data from relations table when post is deleted from DB
	 *
	 * @param integer $object_id
	 */
	public static function deleted_post( $object_id ) {
		self::delete_by_receiver( 'attachment', get_current_blog_id(), $object_id );
		self::delete_by_receiver( 'posttype', get_current_blog_id(), $object_id );
	}

	/**
	 * Delete data from relations table when term is deleted from DB
	 *
	 * @param integer $term_id
	 */
	public static function delete_term( $term_id ) {
		self::delete_by_receiver( 'taxonomy', get_current_blog_id(), $term_id );
	}

	/**
	 * Delete data from relations table when blog is deleted from DB
	 *
	 * @param integer $blog_id
	 */
	public static function deleted_blog( $blog_id ) {
		self::delete_by_blog_id( $blog_id );
	}

	/**
	 * @param $type
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 * @param bool $custom_flag
	 * @param string $custom_fields
	 *
	 * @return int|mixed
	 */
	public static function merge( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id, $custom_flag = false, $custom_fields = '' ) {
		$relation_id = self::exists( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id );
		if ( $relation_id != false ) {
			self::update_custom_flag( $relation_id, $custom_flag );
			self::update_custom_flag( $relation_id, $custom_fields );

			return $relation_id;
		} else {
			return self::insert( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id, $custom_flag, $custom_fields );
		}
	}

	/**
	 * @param $type
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 * @param bool $custom_flag
	 * @param string $custom_fields
	 *
	 * @return int
	 */
	public static function insert( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id, $custom_flag = false, $custom_fields = '' ) {
		global $wpdb;

		/** @var WPDB $wpdb */
		$wpdb->insert(
			$wpdb->bea_csf_relations,
			array(
				'type'             => $type,
				'emitter_blog_id'  => $emitter_blog_id,
				'emitter_id'       => $emitter_id,
				'receiver_blog_id' => $receiver_blog_id,
				'receiver_id'      => $receiver_id,
				'custom_flag'      => $custom_flag,
				'custom_fields'    => $custom_fields,
			),
			array( '%s', '%d', '%d', '%d', '%d', '%d', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Delete a row with this primary ID.
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public static function delete( $id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->delete( $wpdb->bea_csf_relations, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Delete relation for an object_id for emitter and receiver context
	 *
	 * @param string $type
     * @param integer $blog_id
	 * @param integer $object_id
	 *
	 * @return true
	 */
	public static function delete_by_object_id( $type, $blog_id, $object_id ) {
        self::delete_by_emitter( $type, $blog_id, $object_id );
        self::delete_by_receiver( $type, $blog_id, $object_id );

		return true;
	}

	/**
	 * Delete data relation for a blog
	 *
	 * @param integer $blog_id
	 *
	 * @return true
	 */
	public static function delete_by_blog_id( $blog_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		$wpdb->delete( $wpdb->bea_csf_relations, array(
			'emitter_blog_id'      => $blog_id
		), array( '%d' ) );

		$wpdb->delete( $wpdb->bea_csf_relations, array(
			'receiver_blog_id'      => $blog_id
		), array( '%d' ) );

		return true;
	}

	/**
	 * @param $type
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 *
	 * @return false|int
	 */
	public static function delete_by_emitter( $type, $emitter_blog_id, $emitter_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->delete( $wpdb->bea_csf_relations, array(
			'type'            => $type,
			'emitter_blog_id' => $emitter_blog_id,
			'emitter_id'      => $emitter_id
		), array( '%s', '%d', '%d' ) );
	}

	/**
	 * @param $type
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 *
	 * @return false|int
	 */
	public static function delete_by_receiver( $type, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->delete( $wpdb->bea_csf_relations, array(
			'type'             => $type,
			'receiver_blog_id' => $receiver_blog_id,
			'receiver_id'      => $receiver_id,
		), array( '%s', '%d', '%d' ) );
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public static function get( $id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations WHERE id = %d", $id ) );
	}

	/**
	 * @param string|array $type
	 * @param int $emitter_blog_id
	 * @param int $receiver_blog_id
	 * @param int $emitter_id
	 * @param int $receiver_id
	 *
	 * @return bool
	 * @author Alexandre Sadowski
	 */
	public static function get_object_for_any( $type, $emitter_blog_id, $receiver_blog_id, $emitter_id, $receiver_id ) {

		$local_id = self::get_object_id_for_receiver( $type, $emitter_blog_id, $receiver_blog_id, $emitter_id );

		if ( ! empty( $local_id ) && (int) $local_id->receiver_id > 0 ) {
			return $local_id->receiver_id;
		} else {
			$local_id = self::get_object_id_for_emitter( $type, $receiver_blog_id, $emitter_blog_id , $receiver_id );
			if ( ! empty( $local_id ) && (int) $local_id->emitter_id > 0 ) {
				return $local_id->emitter_id;
			}
		}

		return false;
	}

	/**
	 * @param string|array $types
	 * @param int $emitter_blog_id
	 * @param int $receiver_blog_id
	 * @param int $emitter_id
	 *
	 * @return mixed
	 * @author Alexandre Sadowski
	 */
	public static function get_object_id_for_receiver( $types, $emitter_blog_id, $receiver_blog_id, $emitter_id ) {
		global $wpdb;

		$types = array_map(function($v) {
			return "'" . esc_sql($v) . "'";
		}, (array) $types);

		/** @var WPDB $wpdb */
		return $wpdb->get_row( $wpdb->prepare( "SELECT receiver_id FROM $wpdb->bea_csf_relations WHERE type IN ( ".implode(', ', $types)." ) AND emitter_blog_id = %d AND receiver_blog_id = %d AND emitter_id = %s", $emitter_blog_id, $receiver_blog_id, $emitter_id ) );
	}

	/**
	 * @param string|array $types
	 * @param int $emitter_blog_id
	 * @param int $receiver_blog_id
	 * @param int $receiver_id
	 *
	 * @return mixed
	 * @author Alexandre Sadowski
	 */
	public static function get_object_id_for_emitter( $types, $emitter_blog_id, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		$types = array_map(function($v) {
			return "'" . esc_sql($v) . "'";
		}, (array) $types);

		/** @var WPDB $wpdb */
		return $wpdb->get_row( $wpdb->prepare( "SELECT emitter_id FROM $wpdb->bea_csf_relations WHERE type IN ( ".implode(', ', $types)." ) AND emitter_blog_id = %d AND receiver_blog_id = %d AND receiver_id = %s", $emitter_blog_id, $receiver_blog_id, $receiver_id ) );
	}

	/**
	 * @param string $types
	 * @param int $receiver_blog_id
	 * @param int $receiver_id
	 *
	 * @return mixed
	 * @author Alexandre Sadowski
	 */
	public static function current_object_is_synchronized( $types, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		$types = array_map(function($v) {
			return "'" . esc_sql($v) . "'";
		}, (array) $types);
		
		/** @var WPDB $wpdb */
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations WHERE type IN ( ".implode(', ', $types)." ) AND receiver_blog_id = %d AND receiver_id = %s", $receiver_blog_id, $receiver_id ) );
	}

	/**
	 *
	 * @return mixed
	 */
	public static function exists( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_var( $wpdb->prepare( "SELECT id
			FROM $wpdb->bea_csf_relations
			WHERE type = %s
			AND emitter_blog_id = %d
			AND emitter_id = %d
			AND receiver_blog_id = %d
			AND receiver_id = %d"
			, $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id ) );
	}

	/**
	 * @return mixed
	 */
	public static function get_all() {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_results( "SELECT * FROM $wpdb->bea_csf_relations" );
	}

	/**
	 * @return mixed
	 */
	public static function get_all_receiver_blog_ids() {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_col( "SELECT receiver_blog_id FROM $wpdb->bea_csf_relations GROUP BY receiver_blog_id" );
	}

	/**
	 * @return mixed
	 */
	public static function get_results_by_receiver_blog_id( $blog_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations WHERE receiver_blog_id = %d", $blog_id ) );
	}

	/**
	 * @param int $quantity
	 *
	 * @return mixed
	 */
	public static function get_results( $quantity = 100 ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations LIMIT %d", $quantity ) );
	}

	/**
	 * @param $id
	 * @param $flag
	 */
	public static function update_custom_flag( $id, $flag ) {
		global $wpdb;

		/** @var WPDB $wpdb */
		$wpdb->update(
			$wpdb->bea_csf_relations,
			array(
				'custom_flag' => (boolean) $flag
			),
			array(
				'id' => $id
			),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * @param $id
	 * @param $fields_value
	 */
	public static function update_custom_fields( $id, $fields_value ) {
		global $wpdb;

		/** @var WPDB $wpdb */
		$wpdb->update(
			$wpdb->bea_csf_relations,
			array(
				'custom_fields' => maybe_serialize( $fields_value )
			),
			array(
				'id' => $id
			),
			array( '%s' ),
			array( '%d' )
		);
	}
}
