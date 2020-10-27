<?php

class BEA_CSF_Relations {
	/**
	 * BEA_CSF_Relations constructor.
	 * Hook delete post, attachment, term, blog...
	 *
	 */
	public function __construct() {
		add_action( 'deleted_post', array( __CLASS__, 'deleted_post' ), 10 );
		add_action( 'delete_term', array( __CLASS__, 'delete_term' ), 10 );
		add_action( 'deleted_blog', array( __CLASS__, 'deleted_blog' ), 10 );
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
	 * @return int|mixed
	 *
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 * @param bool $custom_flag
	 * @param string $custom_fields
	 *
	 * @param $type
	 */
	public static function merge( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id, $custom_flag = false, $custom_fields = '', $strict_mode = false ) {
		// Test with right emitter/receiver direction
		$relation_id = self::exists( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id );
		if ( $relation_id != false ) {
			self::update_custom_flag( $relation_id, $custom_flag );
			self::update_custom_fields( $relation_id, $custom_fields );

			return $relation_id;
		}

		if ( false === $strict_mode ) {
			// Test also on reverse direction emitter/receiver, allow to not create duplicate relations on 2 directions
			$relation_id = self::exists( $type, $receiver_blog_id, $receiver_id, $emitter_blog_id, $emitter_id );
			if ( $relation_id != false ) {
				self::update_custom_flag( $relation_id, $custom_flag );
				self::update_custom_fields( $relation_id, $custom_fields );

				return $relation_id;
			}
		}

		return self::insert( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id, $custom_flag, $custom_fields );
	}

	/**
	 * @return int
	 *
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 * @param bool $custom_flag
	 * @param string $custom_fields
	 *
	 * @param $type
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
	 * @return mixed
	 *
	 * @param $id
	 *
	 */
	public static function delete( $id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->delete( $wpdb->bea_csf_relations, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Delete relation for an object_id for emitter and receiver context
	 *
	 * @return true
	 *
	 * @param integer $blog_id
	 * @param integer $object_id
	 *
	 * @param string $type
	 */
	public static function delete_by_object_id( $type, $blog_id, $object_id ) {
		self::delete_by_emitter( $type, $blog_id, $object_id );
		self::delete_by_receiver( $type, $blog_id, $object_id );

		return true;
	}

	/**
	 * Delete data relation for a blog
	 *
	 * @return true
	 *
	 * @param integer $blog_id
	 *
	 */
	public static function delete_by_blog_id( $blog_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		$wpdb->delete( $wpdb->bea_csf_relations,
		               array(
			               'emitter_blog_id' => $blog_id
		               ),
		               array( '%d' ) );

		$wpdb->delete(
			$wpdb->bea_csf_relations,
			array(
				'receiver_blog_id' => $blog_id
			),
			array( '%d' )
		);

		return true;
	}

	/**
	 * @return false|int
	 *
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 *
	 * @param $type
	 */
	public static function delete_by_emitter( $type, $emitter_blog_id, $emitter_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->delete(
			$wpdb->bea_csf_relations,
			array(
				'type'            => $type,
				'emitter_blog_id' => $emitter_blog_id,
				'emitter_id'      => $emitter_id
			),
			array( '%s', '%d', '%d' )
		);
	}

	/**
	 * @return false|int
	 *
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 *
	 * @param $type
	 */
	public static function delete_by_receiver( $type, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->delete(
			$wpdb->bea_csf_relations,
			array(
				'type'             => $type,
				'receiver_blog_id' => $receiver_blog_id,
				'receiver_id'      => $receiver_id,
			),
			array( '%s', '%d', '%d' )
		);
	}

	/**
	 * @return false|int
	 *
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 *
	 * @param $type
	 */
	public static function delete_by_emitter_and_receiver( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->delete(
			$wpdb->bea_csf_relations,
			array(
				'type'             => $type,
				'emitter_blog_id'  => $emitter_blog_id,
				'emitter_id'       => $emitter_id,
				'receiver_blog_id' => $receiver_blog_id,
				'receiver_id'      => $receiver_id,
			),
			array( '%s', '%d', '%d', '%d', '%d' )
		);
	}

	/**
	 * @return mixed
	 *
	 * @param $id
	 *
	 */
	public static function get( $id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations WHERE id = %d", $id ) );
	}

	/**
	 * @return integer|false
	 *
	 * @param int $emitter_blog_id
	 * @param int $receiver_blog_id
	 * @param int $emitter_id
	 * @param int $receiver_id
	 *
	 * @param string|array $type
	 *
	 * @author Alexandre Sadowski
	 */
	public static function get_object_for_any( $type, $emitter_blog_id, $receiver_blog_id, $emitter_id, $receiver_id, $direct = false ) {
		$result_id = self::get_object_id_for_receiver( $type, $emitter_blog_id, $receiver_blog_id, $emitter_id );
		if ( $result_id > 0 ) {
			return $result_id;
		}

		$result_id = self::get_object_id_for_emitter( $type, $receiver_blog_id, $emitter_blog_id, $receiver_id );
		if ( $result_id > 0 ) {
			return $result_id;
		}

		if ( $direct === true ) {
			return false;
		}

		$derivations = self::get_relations_hierarchy( $type, $emitter_blog_id, $emitter_id );
		if ( ! empty( $derivations ) ) {
			foreach ( $derivations as $derivation ) {
				// Search for multiple heritage of post
				if ( (int) $derivation->emitter_blog_id === $receiver_blog_id ) {
					return (int) $derivation->emitter_id;
				}

				// Search an indirect relation
				$result_id = self::get_object_for_any( $type, $derivation->emitter_blog_id, get_current_blog_id(), $derivation->emitter_id, $derivation->emitter_id, true );
				if ( $result_id > 0 ) {
					return $result_id;
				}
			}
		}

		return false;
	}

	/**
	 * Get all hierachy of relation for a content
	 *
	 * @return array
	 *
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 * @param $type
	 */
	public static function get_relations_hierarchy( $type, $emitter_blog_id, $emitter_id ) {
		$hierarchy = [];
		$i         = 0;
		do {
			$i ++;
			$result = self::current_object_is_synchronized( $type, $emitter_blog_id, $emitter_id );
			if ( ! empty( $result ) ) {
				$hierarchy[] = $result;

				$emitter_blog_id = $result->emitter_blog_id;
				$emitter_id      = $result->emitter_id;
			}

			if ( $i === 50 ) {
				break; // Skip infinite loop error, in theory, never called.
			}
		} while ( ! empty( $result ) );

		return $hierarchy;
	}

	/**
	 * @return int
	 *
	 * @param int $emitter_blog_id
	 * @param int $receiver_blog_id
	 * @param int $emitter_id
	 *
	 * @param string|array $types
	 *
	 * @author Alexandre Sadowski
	 */
	public static function get_object_id_for_receiver( $types, $emitter_blog_id, $receiver_blog_id, $emitter_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT receiver_id FROM $wpdb->bea_csf_relations WHERE type IN ( " . self::get_sql_in_types( $types ) . " ) AND emitter_blog_id = %d AND receiver_blog_id = %d AND emitter_id = %d",
			$emitter_blog_id,
			$receiver_blog_id,
			$emitter_id
		) );
	}

	/**
	 * @return int
	 *
	 * @param int $emitter_blog_id
	 * @param int $receiver_blog_id
	 * @param int $receiver_id
	 *
	 * @param string|array $types
	 *
	 * @author Alexandre Sadowski
	 */
	public static function get_object_id_for_emitter( $types, $emitter_blog_id, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT emitter_id FROM $wpdb->bea_csf_relations WHERE type IN ( " . self::get_sql_in_types( $types ) . " ) AND emitter_blog_id = %d AND receiver_blog_id = %d AND receiver_id = %d",
			$emitter_blog_id,
			$receiver_blog_id,
			$receiver_id ) );
	}

	/**
	 * @return mixed
	 *
	 * @param int $receiver_blog_id
	 * @param int $receiver_id
	 *
	 * @param string|array $types
	 *
	 * @author Alexandre Sadowski
	 */
	public static function current_object_is_synchronized( $types, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $wpdb->bea_csf_relations WHERE type IN ( " . self::get_sql_in_types( $types ) . " ) AND receiver_blog_id = %d AND receiver_id = %d",
			$receiver_blog_id,
			$receiver_id
		) );
	}

	/**
	 *
	 * @return mixed
	 */
	public static function exists( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_var( $wpdb->prepare(
			"SELECT id
			FROM $wpdb->bea_csf_relations
			WHERE type = %s
			AND emitter_blog_id = %d
			AND emitter_id = %d
			AND receiver_blog_id = %d
			AND receiver_id = %d"
			,
			$type,
			$emitter_blog_id,
			$emitter_id,
			$receiver_blog_id,
			$receiver_id
		) );
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
	 * @return mixed
	 *
	 * @param int $quantity
	 *
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

	/**
	 * Helper for clean SQL "types" IN clause, and create string for query
	 *
	 * @return string
	 *
	 * @param $types
	 */
	private static function get_sql_in_types( $types ) {
		$types = array_map(
			function ( $v ) {
				return "'" . esc_sql( $v ) . "'";
			},
			(array) $types
		);

		return implode( ', ', $types );
	}
}
