<?php
/**
 * Meta function called by hook when a term are delete. Allow to call an function to delete term meta for a tt_id.
 *
 * @param integer $term
 * @param integer $tt_id
 * @param string $taxonomy
 *
 * @return boolean
 */
function remove_meta_during_delete( $term = null, $tt_id = 0, $taxonomy = '' ) {
	return delete_term_taxo_by_term_taxonomy_id( $tt_id );
}