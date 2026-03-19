<?php

/**
 * Class BEA_CSF_Addon_WooCommerce_Product_Attributes
 */
class BEA_CSF_Addon_WooCommerce_Product_Attributes {
	/**
	 * BEA_CSF_Addon_WooCommerce_Product_Attributes constructor.
	 */
	public function __construct() {
		if ( apply_filters( 'bea_csf_disable_woo_attributes_sync_feature', false ) ) {
			return;
		}

		add_action( 'woocommerce_attribute_updated', [ __CLASS__, 'resync' ] );
		add_action( 'woocommerce_attribute_added', [ __CLASS__, 'resync' ] );
		add_action( 'woocommerce_before_attribute_delete', [ __CLASS__, 'resync' ] );
	}

	public static function resync() {
		if ( ! self::is_current_blog_is_emitter() ) {
			return;
		}

		// Hack WooCommerce check
		global $wp_taxonomies;
		$bk_wp_taxonomies = $wp_taxonomies;
		$wp_taxonomies    = []; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		delete_transient( 'wc_attribute_taxonomies' );
		WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );

		$attribute_taxonomies = wc_get_attribute_taxonomies();

		foreach ( self::get_receiver_blogs() as $receiver_blog ) {
			switch_to_blog( $receiver_blog );

			self::flush_attributes();

			foreach ( $attribute_taxonomies as $attribute_taxonomy ) {
				$data = (array) $attribute_taxonomy;
				wc_create_attribute(
					[
						'name'         => $data['attribute_label'],
						'slug'         => $data['attribute_name'],
						'type'         => $data['attribute_type'],
						'order_by'     => $data['attribute_orderby'],
						'has_archives' => $data['attribute_public'],
					]
				);
			}

			restore_current_blog();
		}

		// Restore backup
		$wp_taxonomies = $bk_wp_taxonomies; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		unset( $bk_wp_taxonomies );
	}

	/**
	 * Check if current is emitter for WooCommerce
	 */
	public static function is_current_blog_is_emitter() {
		// Get syncs for current site and product CPT
		$has_syncs = BEA_CSF_Synchronizations::get(
			[
				'post_type' => 'product',
				'emitters'  => get_current_blog_id(),
			],
			'AND',
			false,
			true
		);

		return ! empty( $has_syncs );
	}

	/**
	 * Get all receivers blogs for WooCommerce, except current emitter
	 */
	public static function get_receiver_blogs() {
		// Get syncs for current site and product CPT
		$syncs = BEA_CSF_Synchronizations::get(
			[
				'post_type' => 'product',
				'emitters'  => get_current_blog_id(),
			],
			'AND',
			false,
			true
		);

		if ( empty( $syncs ) ) {
			return [];
		}

		$blog_ids = [];
		foreach ( $syncs as $sync ) {
			$blog_ids = array_merge( array_values( $blog_ids ), array_values( $sync->get_receivers() ) );
		}

		// Remove current emitter blog
		$blog_ids = array_filter(
			$blog_ids,
			function ( $v ) {
				return get_current_blog_id() !== $v;
			}
		);

		return $blog_ids;
	}

	/**
	 * Remove all attributes from table
	 *
	 * @return bool|int
	 */
	public static function flush_attributes() {
		global $wpdb;

		delete_transient( 'wc_attribute_taxonomies' );
		WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );

		return $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_attribute_taxonomies" );
	}
}
