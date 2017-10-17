<?php

class BEA_CSF_Server_P2P {
	/**
	 * Create connection
	 * 
	 * @param  integer $connection_id [description]
	 * @param  array  $sync_fields   [description]
	 * @return array                [description]
	 */
	public static function merge( $connection_id, array $sync_fields ) {
		$connection = (array) p2p_get_connection( $connection_id );

		return apply_filters( 'bea_csf.server.p2p.merge', $connection, $sync_fields );
	}
	
	/**
	 * Delete a connection, take the master id, try to find the new ID and delete local connection
	 *
	 * @param  integer $connection_id [description]
	 * @param  array  $sync_fields   [description]
	 * @return array                [description]
	 */
	public static function delete( $connection_id, array $sync_fields ) {
		$connection = (array) p2p_get_connection( $connection_id );

		return apply_filters( 'bea_csf.server.p2p.delete', $connection, $sync_fields );
	}

}