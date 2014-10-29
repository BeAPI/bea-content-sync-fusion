<?php
class BEA_CSF_Client_P2P {
	/*
	array (
	  'p2p_id' => '7',
	  'p2p_from' => '104',
	  'p2p_to' => '29',
	  'p2p_type' => 'establishment_to_ambassador',
	  'blogid' => 1,
	)
	*/
	
	/**
	 * Add connection on DB
	 */
	public static function merge( array $data, BEA_CSF_Synchronization $sync ) {
		// P2P Type must be sync ?
		if ( !in_array($data['p2p_type'], $sync->get_field('p2p_connections')) ) {
			return false;
		}
		
		// Posts exists ?
		$p2p_from_local = BEA_CSF_Plugin::get_post_id_from_meta( '_origin_key', $data['blogid'] . ':' . $data['p2p_from'] );
		$p2p_to_local = BEA_CSF_Plugin::get_post_id_from_meta( '_origin_key', $data['blogid'] . ':' . $data['p2p_to'] );
		if ( empty( $p2p_from_local ) || empty( $p2p_to_local ) ) {
			return false;
		}
		
		// Create connection
		p2p_type( $data['p2p_type'] )->connect( $p2p_from_local, $p2p_to_local, array(
			'date' => current_time('mysql')
		) );
	}

	/**
	 * Delete a connection, take the master id, try to find the new ID and delete local connection
	 * 
	 * @param array $term
	 * @return \WP_Error|boolean
	 */
	public static function delete( array $data, BEA_CSF_Synchronization $sync ) {
		// P2P Type must be sync ?
		if ( !in_array($data['p2p_type'], $sync->get_field('p2p_connections')) ) {
			return false;
		}
		
		// Posts exists ?
		$p2p_from_local = BEA_CSF_Plugin::get_post_id_from_meta( '_origin_key', $data['blogid'] . ':' . $data['p2p_from'] );
		$p2p_to_local = BEA_CSF_Plugin::get_post_id_from_meta( '_origin_key', $data['blogid'] . ':' . $data['p2p_to'] );
		if ( empty( $p2p_from_local ) || empty( $p2p_to_local ) ) {
			return false;
		}
		
		// Delete connection
		p2p_type( $data['p2p_type'] )->disconnect( $p2p_from_local, $p2p_to_local );
	}

}