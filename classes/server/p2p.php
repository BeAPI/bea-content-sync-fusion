<?php

class BEA_CSF_Server_P2P {
	/**
	 * Create connection
	 *
	 * @param  stdClass $connection [description]
	 * @param  array $sync_fields [description]
	 *
	 * @return array                [description]
	 */
	public static function merge( $connection, array $sync_fields ) {
		$data             = (array) $connection;
		$data['p2p_obj']  = p2p_type( $data['p2p_type'] );

		return apply_filters( 'bea_csf.server.p2p.merge', $data, $sync_fields );
	}

	/**
	 * Delete a connection, take the master id, try to find the new ID and delete local connection
	 *
	 * @param  stdClass $connection [description]
	 * @param  array $sync_fields [description]
	 *
	 * @return array                [description]
	 */
	public static function delete( $connection, array $sync_fields ) {
		$data             = (array) $connection;
		$data['p2p_obj']  = p2p_type( $data['p2p_type'] );

		return apply_filters( 'bea_csf.server.p2p.delete', $data, $sync_fields );
	}

}
