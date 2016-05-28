<?php

class BEA_CSF_Server_P2P {

	/**
	 * Add connection on DB
	 */
	public static function merge( $connection, array $sync_fields ) {
		$connection            = (array) $connection;
		$connection['p2p_obj'] = p2p_type( $connection['p2p_type'] );

		return apply_filters( 'bea_csf.server.p2p.merge', $connection, $sync_fields );
	}

	/**
	 * Delete a connection, take the master id, try to find the new ID and delete local connection
	 *
	 * @param array $term
	 *
	 * @return \WP_Error|boolean
	 */
	public static function delete( $connection, array $sync_fields ) {
		$connection            = (array) $connection;
		$connection['p2p_obj'] = p2p_type( $connection['p2p_type'] );

		return apply_filters( 'bea_csf.server.p2p.delete', $connection, $sync_fields );
	}

}