<?php
/**
 * Class BEA_CSF_Addon_Post_Types_Order
 *
 * Addon for Post Types Order
 * @see : https://fr.wordpress.org/plugins/post-types-order/
 *
 * @author Maxime CULEA
 */
class BEA_CSF_Addon_Post_Types_Order {
	function __construct() {
		add_filter( 'post-types-order_save-ajax-order', array( $this, 'wp_update_post_order' ), 1, 3 );
	}

	/**
	 * On ajax menu_order update, prefer update the post for CSF to be hooked
	 *
	 * @param $data
	 * @param $key
	 * @param $id
	 *
	 * @author Maxime CULEA
	 *
	 * @return int
	 */
	public function wp_update_post_order( $data, $key, $id ) {
		// $data only contains the menu_order
		wp_update_post( wp_parse_args( $data, [ 'ID' => $id ] ) );
		// Return 0 for not updating twice the same data
		return 0;
	}
}