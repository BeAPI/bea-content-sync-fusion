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
		// Get parent TT_ID
		if ( $term->parent > 0 ) {
			$parent_term = get_term( $term->parent, $term->taxonomy );
			if ( $term != false && !is_wp_error( $term ) ) {
				$term->parent_tt_id = $parent_term->term_taxonomy_id;
			}
		} else {
			$term->parent_tt_id = 0;
		}

		// Remove unused fields
		unset( $term->term_group, $term->count );
		
		return (array) $term;
	}

}