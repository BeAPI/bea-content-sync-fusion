<?php
/**
 * Get the current term of tax view from DB. Use WP_Query datas.
 *
 * @return object term
 */
if ( ! function_exists( 'get_current_term' ) ) :
	function get_current_term() {
		if ( ! is_tax() ) {
			return false;
		}

		// Build unique key
		$key = 'current-term-' . get_query_var( 'term' ) . '-' . get_query_var( 'taxonomy' );

		// Get current term
		$term = wp_cache_get( $key, 'terms' );
		if ( false === $term || null === $term ) {
			$term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var( 'taxonomy' ), OBJECT );
			if ( false === $term ) {
				return false;
			}
			wp_cache_set( $key, $term, 'terms' );
		}

		return $term;
	}
endif;

/**
 * Return the value of a term meta. Support before and after wrapper. Use WP_Query term by default, with parameters for specificy a custom term.
 *
 * @param string $meta_key
 * @param string $before
 * @param string $after
 * @param null $term_id
 * @param string $taxonomy deprecated since 1.3.0
 * @param array $filters
 *
 * @return mixed|string|void
 */
function st_get_term_meta( $meta_key = '', $before = '', $after = '', $term_id = null, $taxonomy = '', $filters = array() ) {

	if ( ! empty( $taxonomy ) ) {
		_deprecated_argument( 'st_get_term_meta', '1.3.0', sprintf( __( 'The %s parameter is no longer needed.' ), 'taxonomy' ) );
	}

	if ( empty( $meta_key ) || false === $meta_key ) {
		return '';
	}


	$term = false;
	if ( null !== $term_id ) {
		// Manual term with param ?
		$term = get_term( $term_id );
	}

	if ( false === $term || is_wp_error( $term ) || null === $term ) {
		// Get current term from WP_Query
		$term = get_current_term();
		if ( false === $term ) {
			return '';
		}
	}

	if ( 0 === (int) $term->term_id ) { // Last check if term is valid.
		return '';
	}

	$meta_value = get_term_meta( $term->term_id, $meta_key, true );
	$meta_value = maybe_unserialize( $meta_value );
	if ( false === $meta_value || is_wp_error( $meta_value ) || empty( $meta_value ) ) {
		return '';
	}

	if ( is_string( $meta_value ) ) {
		$meta_value = trim( stripslashes( $meta_value ) );
		if ( is_array( $filters ) && ! empty( $filters ) ) {
			foreach ( $filters as $filter ) {
				$meta_value = apply_filters( $filter, $meta_value, $term, $meta_key );
			}
		}
	}

	if ( ! is_string( $meta_value ) ) {
		return apply_filters( 'st_get_term_meta', $meta_value, $meta_key, $term );
	}

	return $before . apply_filters( 'st_get_term_meta', $meta_value, $meta_key, $term ) . $after;
}

/**
 * Display an term term. Just make an echo of st_get_term_meta().
 *
 * @param string $meta_key
 * @param string $before
 * @param string $after
 * @param null $term_id
 * @param string $taxonomy deprecated since 1.3.0
 * @param array $filters
 *
 */
function st_term_meta( $meta_key = '', $before = '', $after = '', $term_id = null, $taxonomy = '', $filters = array() ) {
	if ( ! empty( $taxonomy ) ) {
		_deprecated_argument( 'st_term_meta', '1.3.0', sprintf( __( 'The %s parameter is no longer needed.' ), 'taxonomy' ) );
	}

	echo st_get_term_meta( $meta_key, $before, $after, $term_id, $taxonomy, $filters );
}