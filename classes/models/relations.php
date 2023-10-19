<?php

class BEA_CSF_Relations {

	const BEA_CSF_RELATIONS_CACHE_GROUP = 'relations-cache-group';

	/**
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 *
	 * @param $type
	 *
	 * @return int|mixed
	 *
	 */
	public static function merge( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id, $strict_mode = false ) {
		// Test with right emitter/receiver direction
		$relation_id = self::exists( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id );
		if ( false != $relation_id ) {
			return $relation_id;
		}

		if ( false === $strict_mode ) {
			// Test also on reverse direction emitter/receiver, allow to not create duplicate relations on 2 directions
			$relation_id = self::exists( $type, $receiver_blog_id, $receiver_id, $emitter_blog_id, $emitter_id );
			if ( false != $relation_id ) {
				return $relation_id;
			}
		}

		return self::insert( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id );
	}

	/**
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 *
	 * @param $type
	 *
	 * @return int
	 *
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
	 * @param $id
	 *
	 * @return mixed
	 *
	 */
	public static function delete( $id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		// Clean cache before delete
		$relations = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations WHERE id = %d", $id )
		);
		if ( ! empty( $relations ) ) {
			foreach ( $relations as $relation ) {
				self::delete_relation_cache( $relation );
			}
		}

		return $wpdb->delete( $wpdb->bea_csf_relations, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Delete relation for an object_id for emitter and receiver context
	 *
	 * @param integer $blog_id
	 * @param integer $object_id
	 *
	 * @param string $type
	 *
	 * @return true
	 *
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
	 *
	 */
	public static function delete_by_blog_id( $blog_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		// Clean cache before delete
		$relations = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations WHERE emitter_blog_id = %d", $blog_id )
		);
		if ( ! empty( $relations ) ) {
			foreach ( $relations as $relation ) {
				self::delete_relation_cache( $relation );
			}
		}

		$wpdb->delete(
			$wpdb->bea_csf_relations,
			array(
				'emitter_blog_id' => $blog_id,
			),
			array( '%d' )
		);

		// Clean cache before delete
		$relations = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations WHERE receiver_blog_id = %d", $blog_id )
		);
		if ( ! empty( $relations ) ) {
			foreach ( $relations as $relation ) {
				self::delete_relation_cache( $relation );
			}
		}

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
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 *
	 * @param $type
	 *
	 * @return false|int
	 *
	 */
	public static function delete_by_emitter( $type, $emitter_blog_id, $emitter_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		// Clean cache before delete
		$relations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->bea_csf_relations WHERE type = %s, emitter_blog_id = %d, emitter_id = %d",
				$type,
				$emitter_blog_id,
				$emitter_id
			)
		);
		if ( ! empty( $relations ) ) {
			foreach ( $relations as $relation ) {
				self::delete_relation_cache( $relation );
			}
		}

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
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 *
	 * @param $type
	 *
	 * @return false|int
	 *
	 */
	public static function delete_by_receiver( $type, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		// Clean cache before delete
		$relations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->bea_csf_relations WHERE type = %s AND receiver_blog_id = %d AND receiver_id = %d",
				$type,
				$receiver_blog_id,
				$receiver_id
			)
		);
		if ( ! empty( $relations ) ) {
			foreach ( $relations as $relation ) {
				self::delete_relation_cache( $relation );
			}
		}

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
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 * @param $receiver_blog_id
	 * @param $receiver_id
	 *
	 * @param $type
	 *
	 * @return false|int
	 *
	 */
	public static function delete_by_emitter_and_receiver( $type, $emitter_blog_id, $emitter_id, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		// Clean cache before delete
		$relations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->bea_csf_relations WHERE type = %s AND emitter_blog_id = %d AND emitter_id = %d AND receiver_blog_id = %d AND receiver_id = %d",
				$type,
				$emitter_blog_id,
				$emitter_id,
				$receiver_blog_id,
				$receiver_id
			)
		);
		if ( ! empty( $relations ) ) {
			foreach ( $relations as $relation ) {
				self::delete_relation_cache( $relation );
			}
		}

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
	 * @param $id
	 *
	 * @return mixed
	 *
	 */
	public static function get( $id ) {
		global $wpdb;

		/** @var WPDB $wpdb */

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations WHERE id = %d", $id ) );
	}

	/**
	 * @param int $emitter_blog_id
	 * @param int $receiver_blog_id
	 * @param int $emitter_id
	 * @param int $receiver_id
	 *
	 * @param string|array $type
	 *
	 * @return integer|false
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

		if ( true === $direct ) {
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
	 * @param $emitter_blog_id
	 * @param $emitter_id
	 * @param $type
	 *
	 * @return array
	 *
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
	 * @param int $emitter_blog_id
	 * @param int $receiver_blog_id
	 * @param int $emitter_id
	 *
	 * @param string|array $types
	 *
	 * @return int
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
	 * @param int $emitter_blog_id
	 * @param int $receiver_blog_id
	 * @param int $receiver_id
	 *
	 * @param string|array $types
	 *
	 * @return int
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
	 * @param int $receiver_blog_id
	 * @param int $receiver_id
	 *
	 * @param string|array $types
	 *
	 * @return mixed
	 *
	 * @author Alexandre Sadowski
	 */
	public static function current_object_is_synchronized( $types, $receiver_blog_id, $receiver_id ) {
		global $wpdb;

		// In some contexts the do_action( 'add_meta_boxes') is called and passes wrong parameters
		// to the get_cache_id method.
		if ( (int) $receiver_id <= 0 ) {
			return;
		}

		$cache_id = self::get_cache_id( $types, (int) $receiver_blog_id, (int) $receiver_id );
		$relation = wp_cache_get( $cache_id, self::BEA_CSF_RELATIONS_CACHE_GROUP, false, $found );
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
			wp_cache_set( $cache_id, $result, self::BEA_CSF_RELATIONS_CACHE_GROUP );
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
	 * @param int $quantity
	 *
	 * @return mixed
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
	 *
	 * @return mixed
	 * @example [
	 *  'taxonomy',
	 *  'attachment',
	 *  'posttype',
	 * ]
	 *
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
	 *
	 * @return string
	 */
	private static function get_cache_id( $types, int $receiver_blog_id, int $receiver_id ): string {
		$type = 'post';
		if ( 'taxonomy' === $types || ( is_array( $types ) && in_array( 'taxonomy', $types, true ) ) ) {
			$type = 'taxonomy';
		}

		return $receiver_blog_id . '-' . $receiver_id . '-' . $type;
	}

	/**
	 * Delete all relation caches matching query results
	 *
	 * @param stdClass $relation
	 *
	 * @return void
	 */
	public static function delete_relation_cache( stdClass $relation ) {
		$cache_id = self::get_cache_id( $relation->type, (int) $relation->receiver_blog_id, (int) $relation->receiver_id );
		wp_cache_delete( $cache_id, self::BEA_CSF_RELATIONS_CACHE_GROUP );

	}
}
