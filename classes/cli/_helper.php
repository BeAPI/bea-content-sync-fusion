<?php
// Bail if WP-CLI is not present
if ( ! defined( 'WP_CLI' ) ) {
	return false;
}

class BEA_CSF_Cli_Helper {
	/**
	 *
	 * Synchronization all terms from any taxonomies
	 *
	 * @param array $args
	 * @param array $terms_args
	 *
	 * @return bool
	 */
	public static function get_all_terms( $args = array(), $terms_args = array() ) {
		$args = wp_parse_args( $args, array() );

		// Get taxonomies names only
		$taxonomies = get_taxonomies( $args, 'names' );
		if ( empty( $taxonomies ) ) {
			WP_CLI::debug( 'No taxinomies found' );

			return false;
		}

		// Get terms objects
		$terms_args = wp_parse_args( $terms_args, array( 'hide_empty' => false ) );
		$results    = get_terms( array_keys( $taxonomies ), $terms_args );
		if ( is_wp_error( $results ) || empty( $results ) ) {
			WP_CLI::debug( 'No terms found for taxonomies : %s', implode( ',', array_keys( $taxonomies ) ) );

			return false;
		}

		return $results;
	}

	/**
	 *
	 * Synchronization all terms from any taxonomies
	 *
	 * @param array $taxonomies
	 * @param array $terms_args
	 *
	 * @return bool
	 */
	public static function get_terms( $taxonomies = array(), $terms_args = array() ) {
		// Get terms objects
		$terms_args = wp_parse_args( $terms_args, array( 'hide_empty' => false ) );
		$results    = get_terms( $taxonomies, $terms_args );
		if ( is_wp_error( $results ) || empty( $results ) ) {
			WP_CLI::debug( 'No terms found for taxonomies : %s', implode( ',', $taxonomies ) );

			return false;
		}

		return $results;
	}

	/**
	 *
	 * Get all attachments
	 *
	 * @param array $args
	 *
	 * @return false|array
	 */
	public static function get_attachments( $args = array() ) {
		$default_args = array(
			'post_type'              => 'attachment',
			'post_status'            => 'any',
			'nopaging'               => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
			'cache_results'          => false,
		);

		$args    = wp_parse_args( $args, $default_args );
		$results = get_posts( $args );
		if ( empty( $results ) ) {
			WP_CLI::debug( "No attachment found\n" );

			return false;
		}

		return $results;
	}

	/**
	 *
	 * Synchronization all posts from any post types
	 *
	 * @param array $args
	 *
	 * @return false|array
	 */
	public static function get_posts( $args = array() ) {
		global $wp_post_types;

		$default_args = array(
			'post_type'              => array_keys( $wp_post_types ),
			'post_status'            => 'any',
			'nopaging'               => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
			'cache_results'          => false,
		);

		$args    = wp_parse_args( $args, $default_args );
		$results = get_posts( $args );
		if ( empty( $results ) ) {
			WP_CLI::debug( "No posts found for post_type %s\n", $args['post_type'] );

			return false;
		}

		return $results;
	}

	/**
	 *
	 * Synchronization all P2P connections
	 *
	 * @param array $args
	 *
	 * @return bool|array
	 */
	public static function get_p2p_connections( $args = array() ) {
		global $wpdb;

		// $args = wp_parse_args( $args, array() ); // TODO: Implement P2P restriction query

		$results = (array) $wpdb->get_col( "SELECT p2p_id FROM $wpdb->p2p" );
		if ( empty( $results ) ) {
			WP_CLI::debug( 'No P2P connection found' );

			return false;
		}

		return $results;
	}

}