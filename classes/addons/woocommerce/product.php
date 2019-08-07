<?php

/**
 * Class BEA_CSF_Addon_WooCommerce
 *
 * TODO :
 *  shipping class update
 *  product inherit attributes
 *  product inherit URL (slug)
 *  product inherit purchase note
 *  all product reviews
 *
 */
class BEA_CSF_Addon_WooCommerce_Product {
	/**
	 * BEA_CSF_Addon_WooCommerce constructor.
	 */
	public function __construct() {
		add_filter( 'bea_csf.client.posttype.merge', [ __CLASS__, 'client_posttype_merge' ], 10, 2 );
		add_filter( 'bea_csf.client.taxonomy.merge', [ __CLASS__, 'client_taxonomy_merge' ], 10, 3 );

		add_action( 'woocommerce_update_product', [ __CLASS__, 'merge_product' ], 10, 1 );
		add_action( 'woocommerce_new_product', [ __CLASS__, 'merge_product' ], 10, 1 );
	}

	/**
	 * From client part, translate gallery ID, upsell, cross-sell, children-grouped product
	 *
	 * @param array $data
	 * @param array $sync_fields
	 *
	 * @return array mixed
	 */
	public static function client_posttype_merge( $data, $sync_fields ) {
		if ( ! isset( $data['post_type'] ) || 'product' !== $data['post_type'] ) {
			return $data;
		}

		// Product gallery
		$_product_image_gallery = get_post_meta( $data['local_id'], '_product_image_gallery', true );
		if ( ! empty( $_product_image_gallery ) ) {
			$_product_image_gallery = explode( ',', $_product_image_gallery );
			$_product_image_gallery = array_map( function ( $media_id ) use ( $data, $sync_fields ) {
				$media_id     = (int) $media_id;
				$new_media_id = BEA_CSF_Relations::get_object_for_any( array( 'attachment' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $media_id, $media_id );
				if ( false !== $new_media_id ) {
					return $new_media_id;
				}

				return $media_id;
			}, $_product_image_gallery );

			update_post_meta( $data['local_id'], '_product_image_gallery', implode( ',', $_product_image_gallery ) );
		}

		// Up-sell
		$_upsell_ids = get_post_meta( $data['local_id'], '_upsell_ids', true );
		if ( ! empty( $_upsell_ids ) ) {
			$_upsell_ids = array_map( function ( $product_id ) use ( $data, $sync_fields ) {
				$product_id     = (int) $product_id;
				$new_product_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $product_id, $product_id );
				if ( false !== $new_product_id ) {
					return $new_product_id;
				}

				return $product_id;
			}, $_upsell_ids );

			update_post_meta( $data['local_id'], '_upsell_ids', $_upsell_ids );
		}

		// Cross-sell
		$_crosssell_ids = get_post_meta( $data['local_id'], '_crosssell_ids', true );
		if ( ! empty( $_crosssell_ids ) ) {
			$_crosssell_ids = array_map( function ( $product_id ) use ( $data, $sync_fields ) {
				$product_id     = (int) $product_id;
				$new_product_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $product_id, $product_id );
				if ( false !== $new_product_id ) {
					return $new_product_id;
				}

				return $product_id;
			}, $_crosssell_ids );

			update_post_meta( $data['local_id'], '_crosssell_ids', $_crosssell_ids );
		}

		// Grouped product
		$_children_ids = get_post_meta( $data['local_id'], '_children', true );
		if ( ! empty( $_children_ids ) ) {
			$_children_ids = array_map( function ( $product_id ) use ( $data, $sync_fields ) {
				$product_id     = (int) $product_id;
				$new_product_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $product_id, $product_id );
				if ( false !== $new_product_id ) {
					return $new_product_id;
				}

				return $product_id;
			}, $_children_ids );

			update_post_meta( $data['local_id'], '_children', $_children_ids );
		}

		return $data;
	}

	/**
	 * Translate image for product category
	 *
	 * @param $data
	 * @param $sync_fields
	 * @param $new_term_obj
	 *
	 * @return mixed
	 */
	public static function client_taxonomy_merge( $data, $sync_fields, $new_term_obj ) {
		if ( ! isset( $data['taxonomy'] ) || 'product_cat' !== $data['taxonomy'] ) {
			return $data;
		}

		// Product gallery
		if ( isset( $data['meta_data'] ) && ! empty( $data['meta_data']['thumbnail_id'] ) ) {
			$new_media_id = BEA_CSF_Relations::get_object_for_any( array( 'attachment' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['meta_data']['thumbnail_id'][0], $data['meta_data']['thumbnail_id'][0] );
			if ( false !== $new_media_id ) {
				update_term_meta( $new_term_obj->term_id, 'thumbnail_id', $new_media_id );
			}
		}

		return $data;
	}

	/**
	 * Add variations into queue when save product
	 *
	 * @param int $product_id
	 *
	 * @return bool
	 */
	public static function merge_product( $product_id = 0 ) {
		global $wpdb;

		// Get emitter for current post
		$emitter_relation = BEA_CSF_Relations::current_object_is_synchronized( 'posttype', $wpdb->blogid, $product_id );
		if ( ! empty( $emitter_relation ) ) {
			return false;
		}

		// TODO: Use Woo API ?
		$variation_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent = %d AND post_type = %s", $product_id, 'product_variation' ) );
		if ( empty( $variation_ids ) ) {
			return false;
		}

		foreach ( $variation_ids as $variation_id ) {
			$variation = get_post( $variation_id );
			if ( empty( $variation ) ) {
				continue;
			}

			do_action( 'transition_post_status', $variation->post_status, $variation->post_status, $variation );
		}

		return true;
	}
}
