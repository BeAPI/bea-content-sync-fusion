<?php
class BEA_CSF_Server_Taxonomy {

	/**
	 * Check for term deletion and send it to client
	 * 
	 * @param stdClass|boolean|string $post
	 * @param array $sync_fields
	 *
	 * @return mixed|null|void
	 */
	public static function delete( $term, array $sync_fields ) {
		if ( empty($term) ) {
			return false;
		}

		if ( is_string($term) ) { // Internal format : taxonomy|||term_id
			$term_values = explode('|||', $term);
			
			$term = array(
				'term_id'  => $term_values[1],
				'taxonomy'          => $term_values[0] );
			if ( empty($term) ) {
				return false;
			}
		} else { // is_stdClass
			$term = (array) $term;
		}

		return apply_filters( 'bea_csf.server.taxonomy.delete', $term, $sync_fields );
	}

	/**
	 * Check for new term and send it to client
	 * 
	 * @param stdClass|boolean|integer $post
	 * @param array $sync_fields
	 *
	 * @return mixed|null|void
	 */
	public static function merge( $term, array $sync_fields ) {
		if ( empty($term) ) {
			return false;
		}

		if ( is_string($term) ) { // Internal format : taxonomy|||term_id
			$term_values = explode('|||', $term);
			
			$term = get_term( $term_values[1], $term_values[0] );
			if ( empty($term) ) {
				return false;
			}
		}

		$term = self::get_data( $term );

		return apply_filters( 'bea_csf.server.taxonomy.merge', $term, $sync_fields );
	}
	
	/**
	 * Generic method for get all data need for sync
	 * 
	 * @param stdClass $term
	 * @return array
	 */
	public static function get_data( $term ) {	
		// Get all meta for current term
		$term->meta_data = get_term_meta( $term->term_id );
		
		// Remove some internal meta
		if ( isset($term->meta_data['already_exists']) ) {
			unset($term->meta_data['already_exists']);
		}
		
		// Remove unused fields
		unset( $term->term_group, $term->count );
		
		return (array) $term;
	}
}