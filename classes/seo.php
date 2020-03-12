<?php

class BEA_CSF_SEO {
	/**
	 * BEA_CSF_SEO constructor, register hooks as usual
	 *
	 */
	public function __construct() {
		add_filter( 'get_canonical_url', array( __CLASS__, 'get_wp_canonical_url' ), 99, 2 );
		add_filter( 'wpseo_canonical', array( __CLASS__, 'get_wpseo_canonical_url' ), 99 );
	}

	/**
	 * Handle Canonical URL for foreign content for WP
	 *
	 * @param string $canonical_url
	 * @param WP_Post $post
	 *
	 * @return string
	 */
	public static function get_wp_canonical_url( $canonical_url, WP_Post $post ) {
		return self::get_canonical_url( $canonical_url, $post);
	}

	/**
	 * Handle Canonical URL for foreign content for Yoast SEO Plugin.
	 *
	 * @param $canonical_url
	 *
	 * @return false|string
	 *
	 */
	public static function get_wpseo_canonical_url( $canonical_url ) {
		return self::get_canonical_url( $canonical_url );
	}

	/**
	 * Get canonical url only for any post type.
	 *
	 * @param $canonical_url
	 * @param bool $post
	 *
	 * @return false|string
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	private static function get_canonical_url( $canonical_url, $post = false ) {

		if ( ! is_singular() ) {
			return $canonical_url;
		}

		$external = \BEA_CSF_Relations::current_object_is_synchronized( 'posttype', get_current_blog_id(), get_queried_object_id() );
		if ( empty( $external ) ) {
			return $canonical_url;
		}

		switch_to_blog( $external->emitter_blog_id );
		$canonical_url = get_permalink( $external->emitter_id );
		restore_current_blog();

		return $canonical_url;
	}

}
