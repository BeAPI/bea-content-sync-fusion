<?php
class BEA_CSF_Multisite {
	protected static $sync_blog_id;

	/**
	 * Register hooks
	 */
	public function __construct() {
		// Deactive media folder structure with sites/blog_id/
		add_filter( 'site_option_' . 'ms_files_rewriting', '__return_true' );

		add_action( 'wpmu_new_blog', array( __CLASS__, 'wpmu_new_blog' ) );
	}

	/**
	 *
	 * Add synchronization taxonomies / attachments / posts when create a new blog
	 *
	 * @param $blog_id
	 */
	public static function wpmu_new_blog( $blog_id ) {
		self::$sync_blog_id = $blog_id;

		add_filter( 'bea_csf.pre_pre_send_data', array( __CLASS__, 'bea_csf_pre_pre_send_data' ), 10, 2 );

		self::sync_all_terms();
		self::sync_all_attachments();
		self::sync_all_posts();

		remove_filter( 'bea_csf.pre_pre_send_data', array( __CLASS__, 'bea_csf_pre_pre_send_data' ), 10, 2 );

		// TODO: Resend content from any blog ? Not only the First/MAIN (with the network admin)
	}

	/**
	 *
	 * Drop variable content for keep blog_id only to the freshly new created blog !
	 *
	 * @param $receiver_blog_id
	 * @param $sync
	 */
	public static function bea_csf_pre_pre_send_data( $receiver_blog_id, BEA_CSF_Synchronization $sync ) {
		if ( ! is_int( self::$sync_blog_id ) || empty( self::$sync_blog_id ) ) {
			return $receiver_blog_id;
		}

		if ( self::$sync_blog_id == $receiver_blog_id ) {
			return $receiver_blog_id;
		}

		return false;
	}

	/**
	 *
	 * Synchronization all terms from any taxinomies
	 *
	 * @return bool
	 */
	public static function sync_all_terms( $args = array(), $terms_args = array(), $verbose = false ) {
		// Get taxonomies names
		$args = wp_parse_args( $args, array() );
		$taxonomies = get_taxonomies( $args, 'names' );
		if ( empty( $taxonomies ) ) {
			if ( true === $verbose ) {
				printf( "No taxinomies found\n" );
			}
			return false;
		}

		// Get terms objects
		$terms_args = wp_parse_args( $terms_args, array('hide_empty' => false) );
		$results = get_terms( array_keys( $taxonomies ), $terms_args );
		if ( is_wp_error( $results ) || empty( $results ) ) {
			if ( true === $verbose ) {
				printf( "No terms found for taxonomies : %s\n", implode(',', array_keys( $taxonomies )) );
			}
			return false;
		}

		if ( true === $verbose ) {
			printf( "Found %s term(s)\n", count( $results ) );
		}

		foreach ( (array) $results as $result ) {
			if ( true === $verbose ) {
				printf( "Synchronizing term %s\n", $result->ID );
			}

			do_action( 'edited_term', $result->term_id, $result->term_taxonomy_id, $result->taxonomy );
		}

		return true;
	}

	/**
	 *
	 * Synchronization all attachments
	 *
	 * @return bool
	 */
	public static function sync_all_attachments( $args = array(), $verbose = false ) {
		$default_args = array(
			'post_type' => 'attachment',
			'post_status' => 'any',
			'nopaging' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'no_found_rows' => true,
			'cache_results' => false
		);

		$args = wp_parse_args( $args, $default_args );
		$results = get_posts( $args );
		if ( empty( $results ) ) {
			if ( true === $verbose ) {
				printf( "No attachment found\n" );
			}
			return false;
		}

		if ( true === $verbose ) {
			printf( "Found %s attachment(s)\n", count( $results ) );
		}

		foreach ( (array) $results as $result ) {
			if ( ! is_a( $result, 'WP_Post' ) ) {
				continue;
			}

			if ( true === $verbose ) {
				printf( "Synchronizing attachment %s\n", $result->ID );
			}

			do_action( 'edit_attachment', $result->ID );
		}
	}

	/**
	 *
	 * Synchronization all posts from any post types
	 *
	 * @return bool
	 */
	public static function sync_all_posts( $args = array(), $verbose = false ) {
		$default_args = array(
			'post_type' => 'any',
			'post_status' => 'any',
			'nopaging' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'no_found_rows' => true,
			'cache_results' => false
		);

		$args = wp_parse_args( $args, $default_args );
		$results = get_posts( $args );
		if ( empty( $results ) ) {
			if ( true === $verbose ) {
				printf( "No posts found for post_type %s\n", $args['post_type'] );
			}
			return false;
		}

		if ( true === $verbose ) {
			printf( "Found %s post(s)\n", count( $results ) );
		}

		foreach ( $results as $result ) {
			if ( ! is_a( $result, 'WP_Post' ) ) {
				continue;
			}

			if ( true === $verbose ) {
				printf( "Synchronizing post %s\n", $result->ID );
			}

			do_action( 'transition_post_status', $result->post_status, $result->post_status, $result );
			do_action( 'save_post', $result->ID, $result );
		}
	}

}