<?php

class BEA_CSF_Synchronizations {

	private static $_bea_csf_synchronizations = array();

	/**
	 * Init synchronization from DB.
	 *
	 * @return boolean
	 */
	public static function init_from_db() {
		$current_options = get_site_option( BEA_CSF_OPTION );
		if ( $current_options == false ) {
			return false;
		}

		foreach ( $current_options as $key => $sync_obj ) {
			/** @var BEA_CSF_Synchronization $sync_obj */
			$sync_obj->unlock();
			$sync_obj->set_field( 'id', $key );
			$sync_obj->register_actions();

			self::$_bea_csf_synchronizations[] = $sync_obj;
		}

		return true;
	}

	/**
	 * Get a list of all registered synchronizations
	 *
	 * @return array A list of objects or object fields
	 */
	public static function get_all() {
		return self::$_bea_csf_synchronizations;
	}

	/**
	 * Get a restricted list of registered synchronizations
	 * Inspiration : get_post_types() / wp_list_filter() / wp_filter_object_list()
	 *
	 * @param array $args An array of key => value arguments to match against each object
	 * @param string $operator The logical operation to perform. 'or' means only one element
	 *      from the array needs to match; 'and' means all elements must match. The default is 'and'.
	 * @param bool|string $field A field from the object to place instead of the entire object
	 * @param bool $in_array Allow usage of "in_array" function for array field object
	 *
	 * @return array A list of objects or object fields
	 */
	public static function get( $args = array(), $operator = 'AND', $field = false, $in_array = false ) {
		$list = self::get_all();
		if ( empty( $list ) ) {
			return array();
		}

		if ( empty( $args ) ) {
			return $list;
		}

		$operator = strtoupper( $operator );
		$count    = count( $args );

		$filtered = array();
		/** @var BEA_CSF_Synchronization $obj */
		foreach ( self::get_all() as $key => $obj ) {
			$matched = 0;

			foreach ( $args as $m_key => $m_value ) {
				$obj_value = $obj->get_field( $m_key );
				if ( $obj_value == $m_value || ( $in_array == true && is_array( $obj_value ) && in_array( $m_value, $obj_value ) ) ) {
					$matched ++;
				}
			}

			if ( ( 'AND' == $operator && $matched == $count ) || ( 'OR' == $operator && $matched > 0 ) || ( 'NOT' == $operator && 0 == $matched ) ) {
				if ( $field == false ) {
					$filtered[ $key ] = $obj;
				} else {
					$filtered[ $key ] = $obj->get_field( $field );
				}
			}
		}

		return $filtered;
	}

	public static function register( array $args ) {
		// Default settings
		$default_args = array(
			'active'        => true,
			'label'         => '',
			'post_type'     => 'post',
			'mode'          => 'auto', // manual OR auto
			'status'        => 'publish', // publish OR pending
			'emitters'      => array(),
			'receivers'     => array()
		);
		$args         = wp_parse_args( $args, $default_args );

		// Check if label is filled ?
		if ( empty( $args['label'] ) ) {
			return false;
		}

		// Instanciate object
		$new_obj = new BEA_CSF_Synchronization();
		$new_obj->set_fields( $args );

		// Append objet in register synchronizations
		self::$_bea_csf_synchronizations[] = $new_obj;

		return true;
	}

	public static function add( BEA_CSF_Synchronization $sync_obj ) {
		$current_options = get_site_option( BEA_CSF_OPTION );
		if ( $current_options == false ) {
			$current_options = array();

			$new_id = 1;
		} else {
			// Get current max 
			$max = max( array_keys( $current_options ) );

			// New key
			$new_id = $max + 1;
		}

		// Add object into options array
		$current_options[ $new_id ] = $sync_obj;

		// Save options
		update_site_option( BEA_CSF_OPTION, $current_options );

		return $new_id;
	}

	public static function update( BEA_CSF_Synchronization $sync_obj, $insert_fallback = false ) {
		$current_options = get_site_option( BEA_CSF_OPTION );
		if ( $current_options == false ) {
			$current_options = array();
		}

		// Get sync id
		$current_sync_id = $sync_obj->get_field( 'id' );

		// Check if object exists
		if ( ! isset( $current_options[ $current_sync_id ] ) ) {
			if ( $insert_fallback == false ) {
				return false;
			} else {
				return self::add( $sync_obj );
			}
		}

		// Update object into options array
		$current_options[ $current_sync_id ] = $sync_obj;

		// Save options
		update_site_option( BEA_CSF_OPTION, $current_options );

		return $current_sync_id;
	}

	public static function delete( BEA_CSF_Synchronization $sync_obj ) {
		$current_options = get_site_option( BEA_CSF_OPTION );
		if ( $current_options == false ) {
			$current_options = array();
		}

		// Get sync id
		$current_sync_id = $sync_obj->get_field( 'id' );

		// Check if object exists
		if ( ! isset( $current_options[ $current_sync_id ] ) ) {
			return false;
		}

		// Remove object from options array
		unset( $current_options[ $current_sync_id ] );

		// Save options
		update_site_option( BEA_CSF_OPTION, $current_options );

		return true;
	}


	/**
	 * Get sites list formatted for a network ID
	 *
	 * @var int|null $network_id :
	 * - null => no network given, then work on current one
	 * - 0    => work on all networks
	 * - int  => work with the given network id
	 *
	 * @since 3.0.2
	 *
	 * @author Amaury Balmer | Maxime CULEA
	 *
	 * @return array|boolean
	 */
	public static function get_sites_from_network( $network_id = null ) {
		$site_query_args = array(
			'public'   => 1,
			'archived' => 0,
			'mature'   => 0,
			'spam'     => 0,
			'deleted'  => 0,
		);
		if ( is_null( $network_id ) ) {
			$site_query_args['network__in'] = get_current_network_id();
		} elseif ( ! empty( $network_id ) ) {
			$site_query_args['network_id'] = $network_id;
		}

		/**
		 * Filter the query args for getting sites from network.
		 *
		 * @since 3.0.2
		 *
		 * @author Maxime CULEA
		 *
		 * @var array $site_query_args : the query args
		 * @var int|null $network_id : the network id working on
		 */
		$site_query_args = apply_filters( 'bea_csf.admin.admin_synchronization_network.query_args', $site_query_args, $network_id );

		$site_query = new WP_Site_Query( $site_query_args );
		$sites      = $site_query->get_sites();
		if ( empty( $sites ) ) {
			return false;
		}

		$return_sites = array();
		foreach ( $sites as $site ) {
			/* @var $site \WP_Site */
			$return_sites[ $site->blog_id ] = array(
				'network_id' => $site->network_id,
				'blog_id'    => $site->blog_id,
				'domain'     => $site->domain,
				'path'       => $site->path,
			);

			// Set the name : {network_name} {site_name}
			$name = array();
			// Check the query args for network
			if ( isset( $site_query_args['network__in'] ) && empty( $site_query_args['network__in'] ) ) {
				$name[] = get_network_option( $site->network_id, 'site_name' );
			}
			$name[] = get_blog_option( $site->blog_id, 'blogname' );

			$return_sites[ $site->blog_id ]['blogname'] = implode( ' > ', $name );
		}

		// Sort by network id then blog_id
		uasort( $return_sites, function ( $a, $b ) {
			if ( $a['network_id'] == $b ['network_id'] ) {
				return ( $a['blog_id'] < $b ['blog_id'] ) ? - 1 : 1;
			}

			return ( $a['network_id'] < $b ['network_id'] ) ? - 1 : 1;
		} );

		/**
		 * Filter the returned formatted sites.
		 *
		 * @since 3.0.2
		 *
		 * @author Maxime CULEA
		 *
		 * @var array $return_sites : the formatted sites from \WP_Site_Query
		 * @var array $sites : the retrieved sites \WP_Site object from \WP_Site_Query
		 * @var int|null $network_id : the network id working on
		 */
		return apply_filters( 'bea_csf.admin.admin_synchronization_network.sites', $return_sites, $sites, $network_id );
	}

}