<?php

class BEA_CSF_Addon_Revisionize {
	/**
	 * BEA_CSF_Addon_Revisionize constructor.
	 */
	public function __construct() {
		add_action( 'bea_csf.client.posttype.merge', array( __CLASS__, 'bea_csf_client_posttype_merge' ), 10, 3 );
		add_filter( 'bea_csf_client_' . 'PostType' . '_' . 'merge' . '_data_to_transfer', array(__CLASS__, 'maybe_transform_data_for_draft'), 10, 3 );
	}

	/**
	 * @param $data
	 * @param $sync_receiver_blog_id
	 * @param $sync_fields
	 *
	 * @return mixed
	 */
	public static function maybe_transform_data_for_draft( $data, $sync_receiver_blog_id, $sync_fields ) {
		if ( 'user_selection' === $sync_fields['status'] && isset( $data['meta_data'][ '_b' . $data['blogid'] . '_post_receivers_status' ] ) ) {
			$_post_receivers_status = maybe_unserialize( $data['meta_data'][ '_b' . $data['blogid'] . '_post_receivers_status' ][0] );

			if ( isset( $_post_receivers_status[ $sync_receiver_blog_id ] ) &&
			     in_array( $_post_receivers_status[ $sync_receiver_blog_id ], [ 'publish-draft', 'pending-draft' ] ) ) {

				// Mapping ID
				$local_id = BEA_CSF_Relations::get_object_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );
				if ( $local_id === false ) { // Do nothing for the first post creation
					return $data;
				}

				// Transform data for revisionize
				unset( $data['ID'] );
				$data['post_parent']           = $local_id;
				$data['protected_post_parent'] = $local_id;
				$data['post_status']           = 'draft';

				// Add meta data for revisionize
				$data['meta_data']['_post_revision_of']      = array( 0 => $local_id );
				$data['meta_data']['_post_revision']         = array( 0 => true );
				$data['meta_data']['_network_post_revision'] = array( 0 => true );
				unset( $data['meta_data']['_pnv_duplicata'] );
			}
		}

		return $data;
	}

	/**
	 * Insert/update metadata from plugin
	 *
	 * @param array $data
	 * @param array $sync_fields
	 * @param WP_Post $new_post
	 *
	 * @return array mixed
	 */
	public static function bea_csf_client_posttype_merge( $data, $sync_fields, $new_post ) {
		global $wpdb;

		// Post have metadata ?
		if ( isset( $data['protected_post_parent'] ) ) {
			$wpdb->update( $wpdb->posts, array( 'post_parent' => $data['protected_post_parent'] ), array( 'ID' => $new_post->ID ) );
		}

		return $data;
	}
}