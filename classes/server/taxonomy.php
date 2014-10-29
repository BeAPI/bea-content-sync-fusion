<?php
class BEA_CSF_Server_Taxonomy {

	/**
	 * Check for term deletion and send it to client
	 */
	public static function delete( stdClass $term, BEA_CSF_Synchronization $sync ) {
		return (array) $term;
	}

	/**
	 * Check for new term and send it to client
	 */
	public static function merge( stdClass $term, BEA_CSF_Synchronization $sync ) {
		return self::get_data( $term );
	}
	
/**
	 * Generic method for get all data need for sync
	 * 
	 * @param WP_Term $term
	 * @return array
	 */
	public static function get_data( $term ) {
		// Get parent TT_ID
		if ( $term->parent > 0 ) {
			$parent_term = get_term( $term->parent, $term->taxonomy );
			if ( $term != false && !is_wp_error( $term ) ) {
				$term->parent_tt_id = $parent_term->term_taxonomy_id;
			}
		} else {
			$term->parent_tt_id = 0;
		}
		
		// Get all meta for current term
		$term->meta_data = get_term_taxonomy_custom( $term->term_taxonomy_id );
		
		// Remove some internal meta
		if ( isset($term->meta_data['already_exists']) ) {
			unset($term->meta_data['already_exists']);
		}
		
		// Remove unused fields
		unset( $term->term_group, $term->count );
		
		return (array) $term;
	}
}