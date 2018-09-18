<?php

class BEA_CSF_SEO {
	/**
	 * BEA_CSF_SEO constructor, register hooks as usual
	 *
	 */
	public function __construct() {
		add_filter( 'get_canonical_url', array( __CLASS__, 'get_canonical_url' ), 99, 2 );
	}

	/**
	 * Handle Canonical URL for foreign content.
	 *
	 * @param string $canonical_url
	 * @param WP_Post $post
	 *
	 * @return string
	 */
	public static function get_canonical_url( $canonical_url, WP_Post $post ) {
		$external = BEA_CSF_Relations::current_object_is_synchronized( 'posttype', get_current_blog_id(), $post->ID );
		if ( empty( $external ) ) {
			return $canonical_url;
		}

		switch_to_blog( $external->emitter_blog_id );
		$canonical_url = get_permalink( $external->emitter_id );
		restore_current_blog();

		return $canonical_url;
	}

}
