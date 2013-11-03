<?php
/**
 * Add metadata for term taxonomy context
 *
 * @package Simple Taxonomy Meta
 * @uses $wpdb
 *
 * @param int $term_taxonomy_id term ID
 * @param string $key {@internal Missing Description}}
 * @param mixed $value {@internal Missing Description}}
 * @param bool $unique whether to check for a value with the same key
 * @return bool {@internal Missing Description}}
 */
function add_term_taxonomy_meta( $term_taxonomy_id = 0, $meta_key = '', $meta_value = '', $unique = false ) {
	return add_metadata( 'term_taxo', $term_taxonomy_id, $meta_key, $meta_value, $unique );
}

/**
 * Delete term metadata
 *
 * @package Simple Taxonomy Meta
 * @uses $wpdb
 *
 * @param int $term_taxonomy_id term ID
 * @param string $key {@internal Missing Description}}
 * @param mixed $value {@internal Missing Description}}
 * @return bool {@internal Missing Description}}
 */
function delete_term_taxonomy_meta( $term_taxonomy_id = 0, $key = '', $value = '', $delete_all = false ) {
	return delete_metadata( 'term_taxo', $term_taxonomy_id, $key, $value, $delete_all );
}

/**
 * Get a term meta field
 *
 * @package Simple Taxonomy Meta
 * @uses $wpdb
 *
 * @param int $term_taxonomy_id term ID
 * @param string $meta_key The meta key to retrieve
 * @param bool $single Whether to return a single value
 * @return mixed {@internal Missing Description}}
 */
function get_term_taxonomy_meta($term_taxonomy_id, $meta_key = '', $single = false) {
	return get_metadata( 'term_taxo', $term_taxonomy_id, $meta_key, $single );
}

/**
 * Update a term meta field
 *
 *
 * @package Simple Taxonomy Meta
 * @uses $wpdb
 *
 * @param int $term_taxonomy_id term ID
 * @param string $key {@internal Missing Description}}
 * @param mixed $value {@internal Missing Description}}
 * @param mixed $prev_value previous value (for differentiating between meta fields with the same key and term ID)
 * @return bool {@internal Missing Description}}
 */
function update_term_taxonomy_meta($term_taxonomy_id, $meta_key, $meta_value, $prev_value = '') {
	return update_metadata( 'term_taxo', $term_taxonomy_id, $meta_key, $meta_value, $prev_value ); 
}

/**
 * Updates metadata cache for list of term taxonomy IDs.
 *
 * Performs SQL query to retrieve the metadata for the term taxonomy IDs and updates the
 * metadata cache for the term taxonomy. Therefore, the functions, which call this
 * function, do not need to perform SQL queries on their own.
 *
 * @uses $wpdb
 *
 * @param array $term_taxonomy_ids List of term taxonomy IDs.
 * @return bool|array Returns false if there is nothing to update or an array of metadata.
 */
function update_termmeta_cache($term_taxonomy_ids) {
	return update_meta_cache('term_taxo', $term_taxonomy_ids);
}

/**
 * Retrieve term custom fields
 *
 *
 * @package Simple Taxonomy Meta
 *
 * @uses $id
 * @uses $wpdb
 *
 * @param int $term_taxonomy_id term ID
 * @return array {@internal Missing Description}}
 */
function get_term_taxonomy_custom($term_taxonomy_id = 0) {
	global $id;

	if ( !$term_taxonomy_id )
		$term_taxonomy_id = (int) $id;

	$term_taxonomy_id = (int) $term_taxonomy_id;
	
	if ( ! wp_cache_get($term_taxonomy_id, 'term_taxometa') )
		update_termmeta_cache($term_taxonomy_id);

	return wp_cache_get($term_taxonomy_id, 'term_taxometa');
}

/**
 * Retrieve meta field names for a term taxonomy.
 *
 * If there are no meta fields, then nothing (null) will be returned.
 *
 * @param int $term_taxonomy_id term taxonomy ID
 * @return array|null Either array of the keys, or null if keys could not be retrieved.
 */
function get_term_taxonomy_custom_keys( $term_taxonomy_id = 0 ) {
	$custom = get_term_custom( $term_taxonomy_id );

	if ( !is_array($custom) )
		return false;

	if ( $keys = array_keys($custom) )
		return $keys;
		
	return false;
}

/**
 * Retrieve values for a custom term field.
 *
 * The parameters must not be considered optional. All of the term meta fields
 * will be retrieved and only the meta field key values returned.
 *
 * @param string $key Meta field key.
 * @param int $term_taxonomy_id Term Taxonomy ID
 * @return array Meta field values.
 */
function get_term_taxonomy_custom_values( $key = '', $term_taxonomy_id = 0 ) {
	if ( !$key )
		return null;

	$custom = get_term_custom($term_taxonomy_id);

	return isset($custom[$key]) ? $custom[$key] : null;
}

/**
 * Delete everything from term meta matching $term_meta_key
 *
 * @uses $wpdb
 *
 * @param string $term_meta_key What to search for when deleting
 * @return bool Whether the term meta key was deleted from the database
 */
function delete_term_meta_by_key( $term_meta_key = '' ) {
	if ( !$term_meta_key )
		return false;
	
	global $wpdb;
	
	$term_taxonomy_ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT term_taxo_id FROM $wpdb->term_taxometa WHERE meta_key = %s", $term_meta_key));
	if ( $term_taxonomy_ids ) {
		$termmetaids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $wpdb->term_taxometa WHERE meta_key = %s", $term_meta_key ) );
		$in = implode( ',', array_fill(1, count($termmetaids), '%d'));
		
		do_action( 'delete_termmeta', $termmetaids );
		$wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->term_taxometa WHERE meta_id IN ($in)", $termmetaids ));
		do_action( 'deleted_termmeta', $termmetaids );
		
		foreach ( $term_taxonomy_ids as $term_taxonomy_id )
			wp_cache_delete($term_taxonomy_id, 'term_taxometa');
			
		return true;
	}
	return false;
}