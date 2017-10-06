<?php
class BEA_CSF_Multisite {
	protected static $sync_blog_id;

	/**
	 * Register hooks
	 */
	public function __construct() {
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
	public static function sync_all_terms() {
		$taxonomies = get_taxonomies( array(), 'names' );
		if ( empty( $taxonomies ) ) {
			return false;
		}

		$results = get_terms( array_keys( $taxonomies ), array( 'hide_empty' => false ) );
		if ( is_wp_error( $results ) || empty( $results ) ) {
			return false;
		}

		foreach ( (array) $results as $result ) {
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
	public static function sync_all_attachments() {
		$results = get_posts( array(
			'post_type' => 'attachment',
			'post_status' => 'any',
			'nopaging' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false
			'no_found_rows' => true,
			'cache_results' => false
		) );

		if ( empty( $results ) ) {
			return false;
		}

		foreach ( (array) $results as $result ) {
			if ( ! is_a( $result, 'WP_Post' ) ) {
				continue;
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
	public static function sync_all_posts() {
		$results = get_posts( array(
			'post_type' => 'any',
			'post_status' => 'any',
			'nopaging' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false
			'no_found_rows' => true,
			'cache_results' => false
		) );

		if ( empty( $results ) ) {
			return false;
		}

		foreach ( $results as $result ) {
			if ( ! is_a( $result, 'WP_Post' ) ) {
				continue;
			}

			do_action( 'transition_post_status', $result->post_status, $result->post_status, $result );
			do_action( 'save_post', $result->ID, $result );
		}
	}

