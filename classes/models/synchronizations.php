<?php
class BEA_CSF_Synchronizations {

	public static $bea_csf_synchronizations = array( );

	public function __construct() {
		
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
		$new_obj->set_fields($args);
		
		// Append objet in register synchronizations
		self::$bea_csf_synchronizations[] = $new_obj;
		
		return true;
	}

	public static function add( BEA_CSF_Synchronization $sync_obj ) {
		$current_options = get_site_option( BEA_CSF_OPTION );
		if ( $current_options == false ) {
			$current_options = array( );
		}

		// Get current max 
		$max = max( array_keys( $current_options ) );

		// New key
		$new_id = $max + 1;

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
		$current_sync_id = $sync_obj->get_id();

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

	public static function get( $sync_id ) {
		$current_options = get_site_option( BEA_CSF_OPTION );
		if ( $current_options == false ) {
			$current_options = array( );
		}

		if ( isset( $current_options[$sync_id] ) ) {
			return $current_options[$sync_id];
		}

		return false;
	}

	public static function delete( BEA_CSF_Synchronization $sync_obj ) {
		$current_options = get_site_option( BEA_CSF_OPTION );
		if ( $current_options == false ) {
			$current_options = array( );
		}

		// Get sync id
		$current_sync_id = $sync_obj->get_id();

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