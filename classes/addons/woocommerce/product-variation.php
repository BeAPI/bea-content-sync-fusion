<?php

/**
 * Class BEA_CSF_Addon_WooCommerce_Product_Variation
 *
 */
class BEA_CSF_Addon_WooCommerce_Product_Variation {
	/**
	 * BEA_CSF_Addon_WooCommerce constructor.
	 */
	public function __construct() {
		add_filter( 'bea_csf_async_get_results_orderby', [ __CLASS__, 'bea_csf_async_get_results_orderby' ], 10 );

		add_action( 'woocommerce_new_product_variation', [ __CLASS__, 'merge_product_variation' ], 10, 1 );
		add_action( 'woocommerce_update_product_variation', [ __CLASS__, 'merge_product_variation' ], 10, 1 );
	}

	/**
	 * Try to sync product before variation
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
}
