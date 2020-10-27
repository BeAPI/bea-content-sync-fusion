<?php
/**
 * Delete a term by key/value
 *
 * @param string $key
 * @param string $value
 * @return boolean
 * @author Amaury Balmer
 */
function delete_term_taxo_by_key_and_value( $key = '', $value = '' ) {
	global $wpdb;

	// expected_slashed ($key, $value)
	$key    = stripslashes( $key );
	$value  = stripslashes( $value );

	// Meta exist ?
	if ( empty( $value ) ) {
		$term_taxonomy_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT term_taxo_id FROM $wpdb->term_taxometa WHERE meta_key = %s", $key ) );
	} else {
		$term_taxonomy_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT term_taxo_id FROM $wpdb->term_taxometa WHERE meta_key = %s AND meta_value = %s", $key, $value ) );
	}

	if ( $term_taxonomy_ids ) {
		// Get term id to delete
		if ( empty( $value ) ) {
			$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_id FROM $wpdb->term_taxometa WHERE meta_key = %s", $key ) );
		} else {
			$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_id FROM $wpdb->term_taxometa WHERE meta_key = %s AND meta_value = %s", $key, $value ) );
		}

		$in = implode( ',', array_fill( 1, count( $meta_ids ), '%d' ) );

		do_action( 'delete_termmeta', $meta_ids );
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->term_taxometa WHERE meta_id IN ($in)", $meta_ids ) );
		do_action( 'deleted_termmeta', $meta_ids );

		// Delete cache
		foreach ( $term_taxonomy_ids as $term_taxonomy_id ) {
			wp_cache_delete( $term_taxonomy_id, 'term_taxometa' );
		}

		return true;
	}

	return false;
}

/**
 * Delete everything from term taxonomy ID matching $term_taxonomy_id
 *
 * @package Simple Taxonomy Meta
 * @uses $wpdb
 *
 * @param integer $term_taxonomy_id What to search for when deleting
 * @return bool Whether the term meta key was deleted from the database
 */
function delete_term_taxo_by_term_taxonomy_id( $term_taxonomy_id = 0 ) {
	global $wpdb;
	if ( $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->term_taxometa WHERE term_taxo_id = %s", (int) $term_taxonomy_id ) ) ) {
		wp_cache_delete( $term_taxonomy_id, 'term_taxometa' );
		return true;
	}
	return false;
}

/**
 * Retrieve term taxonomy ID by meta_key/meta_value
 *
 * @package Simple Taxonomy Meta
 *
 * @param string $meta_key meta key
 * @param string $meta_value meta value
 * @return mixed {@internal Missing Description}}
 */
function get_term_taxonomy_id_from_meta( $meta_key = '', $meta_value = '' ) {
	global $wpdb;

	$key = md5( $meta_key . $meta_value );

	$result = wp_cache_get( $key, 'term_taxometa' );
	if ( false === $result ) {
		$result = (int) $wpdb->get_var( $wpdb->prepare( "SELECT term_taxo_id FROM $wpdb->term_taxometa WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ) );
		wp_cache_set( $key, $result, 'term_taxometa' );
	}

	return $result;
}

/**
 * Allow to get meta datas for a specificied key.
 *
 * @package Simple Taxonomy Meta
 *
 * @param string $key
 * @return array
 */
function get_term_taxo_by_key( $meta_key = '' ) {
	global $wpdb;

	$key = md5( 'key-' . $meta_key );

	$result = wp_cache_get( $key, 'term_taxo' );
	if ( false === $result ) {
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->term_taxometa WHERE meta_key = %s", $meta_key ) );
		wp_cache_set( $key, $result, 'term_taxometa' );
	}

	return $result;
}

function get_term_id_from_meta( $taxonomy = '', $meta_key = '', $meta_value = '' ) {
	$tt_id = get_term_taxonomy_id_from_meta( $meta_key, $meta_value );
	if ( $tt_id != false ) {
		return get_term_id_from_term_taxonomy_id( $taxonomy, $tt_id );
	}

	return false;
}

function get_term_id_from_term_taxonomy_id( $taxonomy = '', $term_taxonomy_id = 0 ) {
	global $wpdb;
	return $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d AND taxonomy = %s", $term_taxonomy_id, $taxonomy ) );
}
