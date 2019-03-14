<?php

class BEA_CSF_Addon_Revisionize {
	/**
	 * BEA_CSF_Addon_Revisionize constructor.
	 */
	public function __construct() {
		if ( ! defined( 'REVISIONIZE_VERSION' ) ) {
			return false;
		}

		add_filter( 'bea_csf/client/posttype/before_merge', array(
			__CLASS__,
			'bea_csf_client_posttype_before_merge',
		), 10, 2 );
		//add_filter( 'bea_csf.client.posttype.merge', array( __CLASS__, 'bea_csf_client_posttype_merge' ), 10, 3 );
		add_filter( 'bea_csf_client_' . 'PostType' . '_' . 'merge' . '_data_to_transfer', array(
			__CLASS__,
			'maybe_transform_data_for_draft',
		), 10, 3 );

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 11, 2 );
		add_action( 'display_post_states', array( __CLASS__, 'display_post_states' ), 11, 2 );

		add_action( 'revisionize/get_post_custom_keys', array( __CLASS__, 'get_post_custom_keys' ), 11, 3 );

		return true;
	}

	public static function get_post_custom_keys( $meta_keys, $id, $context ) {
		if ( 'copy' === $context ) {
			foreach ( [ '_network_post_revision' ] as $meta_key_delete ) {
				if ( ( $key = array_search( $meta_key_delete, $meta_keys ) ) !== false ) {
					unset( $meta_keys[ $key ] );
				}
			}
		}

		return $meta_keys;
	}

	/**
	 * Remove metabox from revision
	 *
	 * @param $post_type
	 * @param $post
	 */
	public static function add_meta_boxes( $post_type, $post ) {
		if ( \Revisionize\is_revision_post( $post ) ) {
			remove_meta_box( BEA_CSF_OPTION . 'metabox-auto', get_current_screen(), 'side' );
			remove_meta_box( BEA_CSF_OPTION . 'metabox-manual', get_current_screen(), 'side' );
		}
	}

	/**
	 * Add a custom post states for remote revision
	 *
	 * @param array $states
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public static function display_post_states( $states, $post ) {
		if ( Revisionize\get_revision_of( $post ) && get_post_meta( $post->ID, '_network_post_revision', true ) ) {
			$states['revisionize-revision-remote'] = __( 'Remote revision', 'bea-content-sync-fusion' );
		}

		return $states;
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
			     in_array( $_post_receivers_status[ $sync_receiver_blog_id ], [
				     'publish-draft',
				     'pending-draft',
			     ], true ) ) {

				// Mapping ID
				$local_id = BEA_CSF_Relations::get_object_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );
				if ( false === $local_id ) { // Do nothing for the first post creation
					return $data;
				}

				// Create a local revision
				$data['new_revision_id'] = Revisionize\create_revision( get_post( $local_id ), false );

				// Transform data for revisionize
				unset( $data['ID'] );
				unset( $data['post_date'], $data['post_date_gmt'] );
				$data['post_parent']           = $local_id;
				$data['protected_post_parent'] = $local_id;
				$data['post_status']           = 'draft';

				// Add meta data for revisionize
				$data['meta_data']['_post_revision_of']      = array( 0 => $local_id );
				$data['meta_data']['_post_revision']         = array( 0 => true );
				$data['meta_data']['_network_post_revision'] = array( 0 => true );
			}
		}

		return $data;
	}

	/**
	 * Allow to override local_id and local_parent_id
	 *
	 * @param array $data
	 * @param array $sync_fields
	 *
	 * @return array mixed
	 */
	public static function bea_csf_client_posttype_before_merge( $data, $sync_fields ) {
		if ( isset( $data['new_revision_id'] ) ) {
			$data['local_id']    = $data['new_revision_id'];
			$data['post_parent'] = $data['protected_post_parent'];
		}

		return $data;
	}
}
