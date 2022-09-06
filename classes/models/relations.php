<?php

class BEA_CSF_Relations {
	/**
	 * @return int|mixed
	 *
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 *
	 * @param $type
	 */
	public static function merge( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id, $strict_mode = false ) {
		// Test with right emitter/receiver direction
		$relation_id = self::exists( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id );
		if ( $relation_id != false ) {
			return $relation_id;
		}

		if ( false === $strict_mode ) {
			// Test also on reverse direction emitter/receiver, allow to not create duplicate relations on 2 directions
			$relation_id = self::exists( $type, $receiver_blog_id, $receiver_id, $emitter_blog_id, $emitter_id );
			if ( $relation_id != false ) {
				return $relation_id;
			}
		}

		return self::insert( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id );
	}

	/**
	 * @return int
	 *
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 *
	 * @param $type
	 */
	public static function insert( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id ) {
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
			),
			array( '%s', '%d', '%d', '%d', '%d' )
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

		// Clean cache before delete
		$query = $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations WHERE id = %d", $id );
		self::delete_relation_cache( $query );

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

		// Clean cache before delete
		$query = $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations WHERE emitter_blog_id = %d", $blog_id );
		self::delete_relation_cache( $query );

		$wpdb->delete(
			$wpdb->bea_csf_relations,
			array(
				'emitter_blog_id' => $blog_id,
			),
			array( '%d' )
		);

		// Clean cache before delete
		$query = $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations WHERE receiver_blog_id = %d", receiver_blog_id );
		self::delete_relation_cache( $query );

		$wpdb->delete(
			$wpdb->bea_csf_relations,
			array(
				'receiver_blog_id' => $blog_id,
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

		// Clean cache before delete
		$query = $wpdb->prepare(
			"SELECT * FROM $wpdb->bea_csf_relations WHERE type = %s, emitter_blog_id = %d, emitter_id = %d",
			$type,
			$emitter_blog_id,
			$emitter_id
		);
		self::delete_relation_cache( $query );

		return $wpdb->delete(
			$wpdb->bea_csf_relations,
			array(
				'type'            => $type,
				'emitter_blog_id' => $emitter_blog_id,
				'emitter_id'      => $emitter_id,
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

		// Clean cache before delete
		$query = $wpdb->prepare(
			"SELECT * FROM $wpdb->bea_csf_relations WHERE type = %s, receiver_blog_id = %d, receiver_id = %d",
			$type,
			$receiver_blog_id,
			$receiver_id
		);
		self::delete_relation_cache( $query );

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

		// Clean cache before delete
		$query = $wpdb->prepare(
			"SELECT * FROM $wpdb->bea_csf_relations WHERE type = %s, emitter_blog_id = %d, emitter_id = %d, receiver_blog_id = %d, receiver_id = %d",
			$type,
			$emitter_blog_id,
			$emitter_id,
			$receiver_blog_id,
			$receiver_id
		);
		self::delete_relation_cache( $query );

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
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT receiver_id FROM $wpdb->bea_csf_relations WHERE type IN ( " . self::get_sql_in_types( $types ) . ' ) AND emitter_blog_id = %d AND receiver_blog_id = %d AND emitter_id = %d',
				$emitter_blog_id,
				$receiver_blog_id,
				$emitter_id
			)
		);
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
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT emitter_id FROM $wpdb->bea_csf_relations WHERE type IN ( " . self::get_sql_in_types( $types ) . ' ) AND emitter_blog_id = %d AND receiver_blog_id = %d AND receiver_id = %d',
				$emitter_blog_id,
				$receiver_blog_id,
				$receiver_id
			)
		);
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
		$cache_id = self::get_cache_id( self::get_sql_in_types( $types ), $receiver_blog_id, $receiver_id  );
		$relation = wp_cache_get( $cache_id, BEA_CSF_RELATIONS_CACHE_GROUP, false, $found );
		if ( $found ) {
			return $relation;
		}

		/** @var WPDB $wpdb */
		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->bea_csf_relations WHERE type IN ( " . self::get_sql_in_types( $types ) . ' ) AND receiver_blog_id = %d AND receiver_id = %d',
				$receiver_blog_id,
				$receiver_id
			)
		);
		if ( null !== $result ) {
			wp_cache_set( $cache_id, $result, BEA_CSF_RELATIONS_CACHE_GROUP );
		}
		return $result;
	}

	/**
	 *
	 * @return mixed
	 */
	public static function exists( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id
			FROM $wpdb->bea_csf_relations
			WHERE type = %s
			AND emitter_blog_id = %d
			AND emitter_id = %d
			AND receiver_blog_id = %d
			AND receiver_id = %d",
				$type,
				$emitter_blog_id,
				$emitter_id,
				$receiver_blog_id,
				$receiver_id
			)
		);
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
	 * Get results by receiver blog id and relation types
	 *
	 * @param int $blog_id
	 * @param array $types_relation
	 * @example [
	 *  'taxonomy',
	 *  'attachment',
	 *  'posttype',
	 * ]
	 *
	 * @return mixed
	 */
	public static function get_types_relation_by_receiver_blog_id( int $blog_id, array $types_relation = [] ) {
		if ( empty( $types_relation ) ) {
			return self::get_results_by_receiver_blog_id( $blog_id );
		}

		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->bea_csf_relations WHERE type IN ( " . self::get_sql_in_types( $types_relation ) . ' ) AND receiver_blog_id = %d',
				$blog_id
			)
		);
	}

	/**
	 * Helper for clean SQL "types" IN clause, and create string for query
	 *
	 * @param $types
	 *
	 * @return string
	 *
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

	/**
	 * Get a unique relation cache_id
	 *
	 * @param string|array $types
	 * @param int $receiver_blog_id
	 * @param int $receiver_id
	 * @return string
	 */
	private static function get_cache_id ( $types, int $receiver_blog_id, int $receiver_id ): string {
		$type = 'taxonomy';
		if ( is_array( $types ) || $types !== 'taxonomy' ) {
			$type = 'post';
		}
		return $receiver_blog_id . '-' . $receiver_id . '-' . $type;
	}

	/**
	 * Delete all relation caches matching query results
	 *
	 * @param string $query
	 * @return void
	 */
	private static function delete_relation_cache ( string $query ) {
		global $wpdb;

		$relations = $wpdb->get_results( $query );
		if ( ! empty ( $relations ) ) {
			foreach ( $relations as $relation ) {
				$cache_id = self::get_cache_id( $relation->type, $relation->receiver_blog_id, $relation->receiver_id );
				wp_cache_delete( $cache_id, BEA_CSF_RELATIONS_CACHE_GROUP );
			}
		}

	}
}
