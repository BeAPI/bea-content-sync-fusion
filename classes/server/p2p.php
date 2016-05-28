<?php

class BEA_CSF_Server_P2P {

	/**
	 * Add connection on DB
	 *
	 * @param $connection
	 * @param array $sync_fields
	 *
	 * @return mixed|void
	 */
	public static function merge( $connection, array $sync_fields ) {
		$connection            = (array) $connection;
		$connection['p2p_obj'] = p2p_type( $connection['p2p_type'] );

		return apply_filters( 'bea_csf.server.p2p.merge', $connection, $sync_fields );
	}

	/**
	 * Delete a connection, take the master id, try to find the new ID and delete local connection
	 *
	 * @param $connection
	 * @param array $sync_fields
	 *
	 * @return bool|WP_Error
	 *
	 */
	public static function delete( $connection, array $sync_fields ) {
		$connection            = (array) $connection;
		$connection['p2p_obj'] = p2p_type( $connection['p2p_type'] );

		return apply_filters( 'bea_csf.server.p2p.delete', $connection, $sync_fields );
	}

}