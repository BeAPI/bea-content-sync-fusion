<?php
require( BEA_CSF_DIR . 'classes/addons/woocommerce/product.php' );
require( BEA_CSF_DIR . 'classes/addons/woocommerce/product-variation.php' );
require( BEA_CSF_DIR . 'classes/addons/woocommerce/product-attributes.php' );

class BEA_CSF_Addon_WooCommerce {
	public function __construct() {
		if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
			return;
		}

		new BEA_CSF_Addon_WooCommerce_Product();
		new BEA_CSF_Addon_WooCommerce_Product_Variation();
		new BEA_CSF_Addon_WooCommerce_Product_Attributes();
	}
}
