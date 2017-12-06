<?php

class BEA_CSF_Addon_Revisionize {
	/**
	 * BEA_CSF_Addon_Revisionize constructor.
	 */
	public function __construct() {
		add_filter( 'bea/csf/client/allowed_new_status', [ __CLASS__, 'bea_csf_client_allowed_new_status' ], 10, 4 );
	}

	/**
	 *
	 *
	 * @param array $post_status
	 * @param string $new_status
	 * @param string $old_status
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public static function bea_csf_client_allowed_new_status( $post_status, $new_status = '', $old_status = '', $post = null ) {
		$_post_revision_of = get_post_meta( $post->ID, '_post_revision_of', true);
		if ( $_post_revision_of != false ) {
			$post_status[] = 'draft';
		}

		return $post_status;
	}
}