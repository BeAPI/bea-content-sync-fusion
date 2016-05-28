<?php
/**
 * Get a term meta field
 *
 * @param string $taxonomy deprecated since 1.3.0
 * @param integer $term_id
 *
 * @return boolean
 * @author Amaury Balmer
 */
function get_term_custom( $taxonomy = '', $term_id = 0 ) {

	if ( ! empty( $taxonomy ) ) {
		_deprecated_argument( 'get_term_custom', '1.3.0', sprintf( __( 'The %s parameter is no longer needed.' ), 'taxonomy' ) );
	}

	// Term ID exist for this taxonomy ?
	$term = get_term( (int) $term_id );
	if ( false === $term || is_wp_error( $term ) ) {
		return false;
	}

	return get_term_taxonomy_custom( $term->term_taxonomy_id );
}

/**
 * undocumented function
 *
 * @param string $taxonomy deprecated since 1.3.0
 * @param string $term_id
 *
 * @return array|bool|null
 *
 * @author Amaury Balmer
 */
function get_term_custom_keys( $taxonomy = '', $term_id = 0 ) {

	if ( ! empty( $taxonomy ) ) {
		_deprecated_argument( 'get_term_custom_keys', '1.3.0', sprintf( __( 'The %s parameter is no longer needed.' ), 'taxonomy' ) );
	}

	// Term ID exist for this taxonomy ?
	$term = get_term( (int) $term_id );
	if ( false === $term || is_wp_error( $term ) ) {
		return false;
	}

	return get_term_taxonomy_custom_keys( $term->term_taxonomy_id );
}

/**
 * undocumented function
 *
 * @param string $taxonomy
 * @param string $term_id deprecated since 1.3.0
 * @param string $key
 *
 * @return array|bool
 *
 * @author Amaury Balmer
 */
function get_term_custom_values( $taxonomy = '', $term_id = 0, $key = '' ) {

	if ( ! empty( $taxonomy ) ) {
		_deprecated_argument( 'get_term_custom_values', '1.3.0', sprintf( __( 'The %s parameter is no longer needed.' ), 'taxonomy' ) );
	}

	// Term ID exist for this taxonomy ?
	$term = get_term( (int) $term_id );
	if ( false === $term || is_wp_error( $term ) ) {
		return false;
	}

	return get_term_taxonomy_custom_values( $key, $term->term_taxonomy_id );
}