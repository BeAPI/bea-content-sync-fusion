<?php

class BEA_CSF_Addon_Yoast_Seo {
	/**
	 * BEA_CSF_Addon_Yoast_Seo constructor.
	 */
	public function __construct() {
		if ( ! defined( 'WPSEO_FILE' ) ) {
			return;
		}

		add_filter( 'bea_csf.client.posttype.merge', [ $this, 'bea_set_yoast_seo_meta' ], 20, 3 );
	}

	/**
	 * Update image meta for synchronised sites on yoast custom table
	 *
	 * @param $data
	 * @param $sync_fields
	 * @param $receiver_post
	 *
	 * @return mixed
	 * @author Alexandre Sadowski
	 */
	public function bea_set_yoast_seo_meta( array $data, $sync_fields, WP_Post $new_post ) {
		$fb_image_meta = isset($data['meta_data']['_yoast_wpseo_opengraph-image-id']) ? $data['meta_data']['_yoast_wpseo_opengraph-image-id'] : null;
		if ( ! empty( $fb_image_meta ) && isset( $fb_image_meta[0] ) ) {
			$fb_seo_id = (int) BEA_CSF_Relations::get_object_for_any( 'attachment', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $fb_image_meta[0], $fb_image_meta[0] );
			
			if ( ! empty( $fb_seo_id ) && (int) $fb_seo_id > 0 ) {
				update_post_meta( $new_post->ID, '_yoast_wpseo_opengraph-image-id', (string) $fb_seo_id );
			}
		}

		$twitter_image_meta = isset($data['meta_data']['_yoast_wpseo_twitter-image-id']) ? $data['meta_data']['_yoast_wpseo_twitter-image-id'] : null;
		if ( ! empty( $twitter_image_meta ) && isset( $twitter_image_meta[0] ) ) {
			$twitter_seo_id = (int) BEA_CSF_Relations::get_object_for_any( 'attachment', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $twitter_image_meta[0], $twitter_image_meta[0] );
			
			if ( ! empty( $twitter_seo_id ) && (int) $twitter_seo_id > 0 ) {
				update_post_meta( $new_post->ID, '_yoast_wpseo_twitter-image-id', (string) $twitter_seo_id );
			}
		}

		return $data;
	}
}
