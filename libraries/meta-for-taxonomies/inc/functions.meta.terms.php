<?php
/**
 * Add metadata for term
 *
 * @param string $taxonomy
 * @param integer $term_id
 * @param string $meta_key
 * @param string|array $meta_value
 * @param boolean $unique
 * @return boolean
 * @author Amaury Balmer
 */
function add_term_meta( $taxonomy = '', $term_id = 0, $meta_key = '', $meta_value = '', $unique = false ) {
	// Taxonomy is valid ?
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	// Term ID exist for this taxonomy ?
	$term = get_term( (int) $term_id, $taxonomy );
	if ( $term == false || is_wp_error( $term ) ) {
		return false;
	}

	return add_term_taxonomy_meta( $term->term_taxonomy_id, $meta_key, $meta_value, $unique );
}

/**
 * Delete term meta for term
 *
 * @param string $taxonomy
 * @param integer $term_id
 * @param string $meta_key
 * @param string|array $meta_value
 * @return boolean
 * @author Amaury Balmer
 */
function delete_term_meta( $taxonomy = '', $term_id = 0, $meta_key = '', $meta_value = '' ) {
	// Taxonomy is valid ?
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	// Term ID exist for this taxonomy ?
	$term = get_term( (int) $term_id, $taxonomy );
	if ( $term == false || is_wp_error( $term ) ) {
		return false;
	}

	return delete_term_taxonomy_meta( $term->term_taxonomy_id, $meta_key, $meta_value );
}

/**
 * Get a term meta field
 *
 * @param string $taxonomy
 * @param integer $term_id
 * @param string|array $meta_key
 * @param boolean $single
 * @return boolean
 * @author Amaury Balmer
 */
function get_term_meta( $taxonomy = '', $term_id = 0, $meta_key = '', $single = false ) {
	// Taxonomy is valid ?
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	// Term ID exist for this taxonomy ?
	$term = get_term( (int) $term_id, $taxonomy );
	if ( $term == false || is_wp_error( $term ) ) {
		return false;
	}

	return get_term_taxonomy_meta( $term->term_taxonomy_id, $meta_key, $single );
}

/**
 * Update a term meta field
 *
 * @param string $taxonomy
 * @param integer $term_id
 * @param string $meta_key
 * @param string|array $meta_value
 * @param string|array $prev_value
 * @return boolean
 * @author Amaury Balmer
 */
function update_term_meta( $taxonomy = '', $term_id = 0, $meta_key, $meta_value, $prev_value = '' ) {
	// Taxonomy is valid ?
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	// Term ID exist for this taxonomy ?
	$term = get_term( (int) $term_id, $taxonomy );
	if ( $term == false || is_wp_error( $term ) ) {
		return false;
	}

	return update_term_taxonomy_meta( $term->term_taxonomy_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Get a term meta field
 *
 * @param string $taxonomy
 * @param integer $term_id
 * @return boolean
 * @author Amaury Balmer
 */
function get_term_custom( $taxonomy = '', $term_id = 0 ) {
	// Taxonomy is valid ?
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	// Term ID exist for this taxonomy ?
	$term = get_term( (int) $term_id, $taxonomy );
	if ( $term == false || is_wp_error( $term ) ) {
		return false;
	}

	return get_term_taxonomy_custom( $term->term_taxonomy_id );
}

/**
 * undocumented function
 *
 * @param string $taxonomy
 * @param string $term_id
 * @return void
 * @author Amaury Balmer
 */
function get_term_custom_keys( $taxonomy = '', $term_id = 0 ) {
	// Taxonomy is valid ?
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	// Term ID exist for this taxonomy ?
	$term = get_term( (int) $term_id, $taxonomy );
	if ( $term == false || is_wp_error( $term ) ) {
		return false;
	}

	return get_term_taxonomy_custom_keys( $term->term_taxonomy_id );
}

/**
 * undocumented function
 *
 * @param string $taxonomy
 * @param string $term_id
 * @param string $key
 * @return void
 * @author Amaury Balmer
 */
function get_term_custom_values( $taxonomy = '', $term_id = 0, $key = '' ) {
	// Taxonomy is valid ?
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return false;
	}

	// Term ID exist for this taxonomy ?
	$term = get_term( (int) $term_id, $taxonomy );
	if ( $term == false || is_wp_error( $term ) ) {
		return false;
	}

	return get_term_taxonomy_custom_values( $key, $term->term_taxonomy_id );
}
