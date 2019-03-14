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
	public function __construct() {
		add_filter( 'post-types-order_save-ajax-order', array( $this, 'wp_update_post_order' ), 1, 3 );
	}

	/**
	 * On ajax menu_order update, prefer update the post for CSF to be hooked
	 * Also check if menu order has changed, to not load the CSF queue for nothing
	 *
	 * @param array $data : only contains the menu_order
	 * @param $key
	 * @param int $post_id : the post_id
	 *
	 * @author Maxime CULEA
	 *
	 * @return int|array
	 */
	public function wp_update_post_order( $data, $key, $post_id ) {
		// Check $data constancy
		if ( empty( $data ) || ! isset( $data['menu_order'] ) ) {
			return $data;
		}

		// Get post's menu order to compare with the given one
		$menu_order = get_post_field( 'menu_order', $post_id );
		if ( empty( $menu_order ) || $menu_order == $data['menu_order'] ) {
			return $data;
		}

		// Update the post with wp_update_post to allow CSF to hook on
		wp_update_post( wp_parse_args( $data, [ 'ID' => $post_id ] ) );

		// Return 0 for not updating twice the same post
		return 0;
	}
}
