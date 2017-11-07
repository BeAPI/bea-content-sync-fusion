<?php class BEA_CSF_Addons {

	function __construct() {

		// Conditionally load P2P classes
		if ( defined( 'P2P_PLUGIN_VERSION' ) ) {
			require( BEA_CSF_DIR . 'classes/addons/p2p.php' );
			new BEA_CSF_Addon_P2P();
		}

		// Conditionally load post types order class
		if ( defined( 'CPTPATH' ) ) {
			require( BEA_CSF_DIR . 'classes/addons/post-types-order.php' );
			new BEA_CSF_Addon_Post_Types_Order();
		}
	}
}