<?php

/**
 * Class BEA_CSF_Addon_WooCommerce
 *
 * TODO :
 *  Variations
 *  Slave products
 *  children
 *  shipping class update
 *  product inherit attributes
 *  product inherit URL (slug)
 *  product inherit purchase note
 *  all product reviews
 *
 */
class BEA_CSF_Addon_WooCommerce {
	/**
	 * BEA_CSF_Addon_WooCommerce constructor.
	 */
	public function __construct() {
		if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
			return;
		}

		add_filter( 'bea_csf_async_get_results_orderby', [ __CLASS__, 'bea_csf_async_get_results_orderby' ], 10 );

		//add_filter( 'bea_csf.server.posttype.merge', [ __CLASS__, 'server_posttype_merge' ], 10, 2 );
		add_filter( 'bea_csf.client.posttype.merge', [ __CLASS__, 'client_posttype_merge' ], 10, 2 );
		//add_filter( 'bea_csf.client.posttype.merge', [ __CLASS__, 'client_posttype_merge_variations' ], 10, 2 );
		add_filter( 'bea_csf.client.taxonomy.merge', [ __CLASS__, 'client_taxonomy_merge' ], 10, 3 );

		// Update/Insert new product variation
		add_action( 'woocommerce_new_product_variation', [ __CLASS__, 'merge_product_variation' ], 10, 1 );
		add_action( 'woocommerce_update_product_variation', [ __CLASS__, 'merge_product_variation' ], 10, 1 );

		// Update/Insert new product
		add_action( 'woocommerce_update_product', [ __CLASS__, 'merge_product' ], 10, 1 );
		add_action( 'woocommerce_new_product', [ __CLASS__, 'merge_product' ], 10, 1 );
	}

	/**
	 * TODO
	 *
	 * @param string $orderby
	 *
	 * @return string
	 */
	public static function bea_csf_async_get_results_orderby( $orderby = '' ) {
		$orderby .= " , FIELD(object_name, 'product', 'product_variation') ASC ";

		return $orderby;
	}

	/**
	 * Add variation into products variables
	 *
	 * @param $data
	 * @param $sync_fields
	 *
	 * @return mixed
	 */
	public static function server_posttype_merge( $data, $sync_fields ) {
		if ( ! isset( $data['post_type'] ) || 'product' !== $data['post_type'] ) {
			return $data;
		}

		$type = WC_Product_Factory::get_product_type( $data['ID'] );
		if ( empty( $type ) ) {
			return $data;
		}

		$classname = WC_Product_Factory::get_classname_from_product_type( $type );
		if ( ! class_exists( $classname ) ) {
			$classname = 'WC_Product_Simple';
		}

		$product = new $classname( $data['ID'] );
		/** @var WC_Product_Variable $product */

		if ( empty( $product ) ) {
			return $data;
		}

		// Get variations
		$variations = $product->get_available_variations();
		if ( false !== $variations && ! empty( $variations ) ) {
			$data['variations'] = array();
			foreach ( $variations as $variation ) {
				$data['variations'][] = new WC_Product_Variation( $variation );
			}
		}

		return $data;
	}

	/**
	 * From client part, translate gallery ID, upsell, cross-sell
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

		return $data;
	}

	/**
	 * Manage variations with sync
	 *
	 * @param $data
	 * @param $sync_fields
	 *
	 * @return mixed
	 */
	public static function client_posttype_merge_variations( $data, $sync_fields ) {
		// No variations, no sync :)
		if ( ! isset( $data['variations'] ) || empty( $data['variations'] ) ) {
			return $data;
		}

		// Get local product
		$local_product_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );
		if ( false === $local_product_id ) {
			return $data;
		}

		$product = new WC_Product_Variable( $local_product_id );
		if ( empty( $product ) ) {
			return $data;
		}

		// Get variations
		$local_variations    = $product->get_children();
		$local_variations_id = wp_list_pluck( $local_variations, 'ID' );
		var_dump( $local_variations, $local_variations_id );


		// https://stackoverflow.com/questions/47518333/create-programmatically-a-variable-product-and-two-new-attributes-in-woocommerce/47844054#47844054
		// https://stackoverflow.com/questions/52937409/create-programmatically-a-product-using-crud-methods-in-woocommerce-3/52941994#52941994
		// https://stackoverflow.com/questions/47518280/create-programmatically-a-woocommerce-product-variation-with-new-attribute-value/47766413#47766413
		die();
		// Loop on each variations for insertion, and keep ID
		$remote_variations_id = array();
		foreach ( $data['variations'] as &$variation ) {
			// Fix event ID with local value
			if ( isset( $variation['meta_data']['_tribe_rsvp_for_event'] ) ) {
				$variation['meta_data']['_tribe_rsvp_for_event'][0] = $local_product_id;
			}

			$variation['blogid'] = $data['blogid'];
			BEA_CSF_Client_PostType::merge( $variation, $sync_fields );

			// Translated remote variations with current ID
			$local_variation_id = BEA_CSF_Relations::get_object_for_any( array( 'posttype' ), $data['blogid'], $sync_fields['_current_receiver_blog_id'], $variation['ID'], $variation['ID'] );
			if ( false !== $local_variation_id ) {
				$remote_variations_id[] = (int) $local_variation_id;
			}
		}

		// Calcul diff between local and remote for delete "old remote variations deleted"
		$variations_id_to_delete = array_diff( $local_variations_id, $remote_variations_id );
		if ( ! empty( $variations_id_to_delete ) ) {
			foreach ( $variations_id_to_delete as $variation_id ) {
				wp_delete_post( $variation_id, true );
			}
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
	 * Add product into queue when save variations
	 *
	 * @param int $variation_id
	 *
	 * @return bool
	 */
	public static function merge_product_variation( $variation_id = 0 ) {
		global $wpdb;

		// Get emitter for current post
		$emitter_relation = BEA_CSF_Relations::current_object_is_synchronized( 'posttype', $wpdb->blogid, $variation_id );
		if ( ! empty( $emitter_relation ) ) {
			return false;
		}

		// TODO: Use Woo API ? $variableProduct = new WC_Product_Variable($variationId)
		$parent_product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_parent FROM $wpdb->posts WHERE ID = %d", $variation_id ) );
		if ( empty( $parent_product_id ) ) {
			return false;
		}

		$parent_product = get_post( $parent_product_id );
		if ( empty( $parent_product ) ) {
			return false;
		}

		do_action( 'transition_post_status', $parent_product->post_status, $parent_product->post_status, $parent_product );

		return true;
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
