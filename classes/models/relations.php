<?php

class BEA_CSF_Relations {
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
			'receiver_id'      => $receiver_id
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
	 * @param $emitter_blog_id
	 * @param $receiver_blog_id
	 * @param $emitter_id
	 * @param $receiver_id
	 *
	 * @return bool
	 * @author Alexandre Sadowski
	 */
	public static function get_post_for_any( $emitter_blog_id, $receiver_blog_id, $emitter_id, $receiver_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */
		$local_id = self::get_post_id_for_receiver( $emitter_blog_id, $receiver_blog_id, $emitter_id );
		if ( ! empty( $local_id ) && (int) $local_id->receiver_id > 0 ) {
			return $local_id->receiver_id;
		} else {
			$local_id = self::get_post_id_for_emitter( $receiver_blog_id, $emitter_blog_id , $receiver_id );
			if ( ! empty( $local_id ) && (int) $local_id->emitter_id > 0 ) {
				return $local_id->emitter_id;
			}
		}

		return false;
	}

	/**
	 * @param $emitter_blog_id
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 *
	 * @return mixed
	 * @author Alexandre Sadowski
	 */
	public static function get_post_id_for_receiver( $emitter_blog_id, $receiver_blog_id, $emitter_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */
		return $wpdb->get_row( $wpdb->prepare( "SELECT receiver_id FROM $wpdb->bea_csf_relations WHERE emitter_blog_id = %d AND receiver_blog_id = %d AND  emitter_id = %s", $emitter_blog_id, $receiver_blog_id, $emitter_id ) );
	}

	/**
	 * @param $emitter_blog_id
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 *
	 * @return mixed
	 * @author Alexandre Sadowski
	 */
	public static function get_post_id_for_emitter( $emitter_blog_id, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */
		return $wpdb->get_row( $wpdb->prepare( "SELECT emitter_id FROM $wpdb->bea_csf_relations WHERE emitter_blog_id = %d AND receiver_blog_id = %d AND  receiver_id = %s", $emitter_blog_id, $receiver_blog_id, $receiver_id ) );
	}

	/**
	 * @param $id
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