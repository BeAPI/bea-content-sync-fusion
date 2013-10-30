<?php
class BEA_CSF_Synchronizations {

	private static $_bea_csf_synchronizations = array( );

	public static function init_from_db() {
		$current_options = get_site_option( BEA_CSF_OPTION );
		if ( $current_options == false ) {
			return false;
		}

		foreach ( $current_options as $key => $sync_obj ) {
			$sync_obj->unlock();
			$sync_obj->set_field( 'id', $key );
			self::$_bea_csf_synchronizations[] = $sync_obj;
		}
	}

	public static function get_all() {
		return self::$_bea_csf_synchronizations;
	}

	public static function get( $args = array( ), $operator = 'AND', $in_array = false ) {
		$list = self::get_all();
		if ( empty( $list ) ) {
			return array( );
		}

		if ( empty( $args ) ) {
			return $list;
		}

		$operator = strtoupper( $operator );
		$count = count( $args );

		$filtered = array( );
		foreach ( self::get_all() as $key => $obj ) {
			$matched = 0;

			foreach ( $args as $m_key => $m_value ) {
				$obj_value = $obj->get_field($m_key);
				if ( $obj_value == $m_value || ($in_array == true && is_array($obj_value) && in_array($m_value, $obj_value)) ) {
					$matched++;
				}
			}

			if ( ( 'AND' == $operator && $matched == $count ) || ( 'OR' == $operator && $matched > 0 ) || ( 'NOT' == $operator && 0 == $matched ) ) {
				$filtered[$key] = $obj;
			}
		}
		
		return $filtered;
	}

	public static function register( $args ) {
		// Default settings
		$default_args = array(
			'active' => true,
			'label' => '',
			'post_type' => 'post',
			'mode' => 'auto', // manual OR auto
			'status' => 'publish', // publish OR pending
			'notifications' => '1', // 1 OR 0
			'emitters' => array( ),
			'receivers' => array( )
		);
		$args = wp_parse_args( $args, $default_args );

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
			$current_options = array( );

			$new_id = 1;
		} else {
			// Get current max 
			$max = max( array_keys( $current_options ) );

			// New key
			$new_id = $max + 1;
		}

		// Add object into options array
		$current_options[$new_id] = $sync_obj;

		// Save options
		update_site_option( BEA_CSF_OPTION, $current_options );

		return $new_id;
	}

	public static function update( BEA_CSF_Synchronization $sync_obj, $insert_fallback = false ) {
		$current_options = get_site_option( BEA_CSF_OPTION );
		if ( $current_options == false ) {
			$current_options = array( );
		}

		// Get sync id
		$current_sync_id = $sync_obj->get_field( 'id' );

		// Check if object exists
		if ( !isset( $current_options[$current_sync_id] ) ) {
			if ( $insert_fallback == false ) {
				return false;
			} else {
				return $this->add( $sync_obj );
			}
		}

		// Update object into options array
		$current_options[$current_sync_id] = $sync_obj;

		// Save options
		update_site_option( BEA_CSF_OPTION, $current_options );

		return $current_sync_id;
	}

	public static function delete( BEA_CSF_Synchronization $sync_obj ) {
		$current_options = get_site_option( BEA_CSF_OPTION );
		if ( $current_options == false ) {
			$current_options = array( );
		}

		// Get sync id
		$current_sync_id = $sync_obj->get_field( 'id' );

		// Check if object exists
		if ( !isset( $current_options[$current_sync_id] ) ) {
			return false;
		}

		// Remove object from options array
		unset( $current_options[$current_sync_id] );

		// Save options
		update_site_option( BEA_CSF_OPTION, $current_options );

		return true;
	}

}