<?php

/**
 * Class BEA_CSF_Addon_Multisite_Clone_Duplicator
 *
 * Addon for Multisite Clone Duplicator - Very alpha version
 *
 * @see : https://fr.wordpress.org/plugins/multisite-clone-duplicator/
 */
class BEA_CSF_Addon_Multisite_Clone_Duplicator {
	/**
	 * Register hooks
	 */
	public function __construct() {
		// Force deactive files duplication on FS
		add_filter( 'mucd_copy_dirs', '__return_empty_array' );

		add_action( 'mucd_after_copy_data', array( $this, 'mucd_after_copy_data' ), 10, 2 );
	}

	/**
	 * Hook after data copy for post-process manipulations
	 *
	 * @param $from_site_id
	 * @param $to_site_id
	 */
	public function mucd_after_copy_data( $from_site_id, $to_site_id ) {
		$this->duplicate_relations( $from_site_id, $to_site_id );
		$this->create_missing_relations( $from_site_id, $to_site_id );
	}

	/**
	 * Recreate medias relation in plugin table relations
	 *
	 * @param $from_site_id
	 * @param $to_site_id
	 *
	 * @return bool
	 */
	public function duplicate_relations( $from_site_id, $to_site_id ) {
		global $wpdb;

		$relations = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->bea_csf_relations WHERE receiver_blog_id = %d", $from_site_id ) );
		if ( empty( $relations ) ) {
			return false;
		}

		foreach ( $relations as $relation ) {
			BEA_CSF_Relations::insert( $relation->type, $relation->emitter_blog_id, $relation->emitter_id, $to_site_id, $relation->receiver_id );
		}

		return true;
	}

	/**
	 * Find local contents, and flag it as synchronized from "source blog"
	 *
	 * @param $from_site_id
	 * @param $to_site_id
	 */
	public function create_missing_relations( $from_site_id, $to_site_id ) {
		switch_to_blog( $to_site_id );

		$media_query = new WP_Query(
			array(
				'post_type'        => 'attachment',
				'bea_csf_filter'   => 'local-only',
				'nopaging'         => true,
				'fields'           => 'ids',
				'suppress_filters' => false,
				'post_status'      => 'any',
			)
		);

		if ( $media_query->have_posts() ) {
			foreach ( $media_query->posts as $media_query_id ) {
				BEA_CSF_Relations::insert( 'attachment', $from_site_id, $media_query_id, $to_site_id, $media_query_id );
			}
		}

		// TODO: Add support for any other post types ?

		restore_current_blog();
	}
}
