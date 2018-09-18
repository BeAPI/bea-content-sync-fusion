<?php

class BEA_CSF_Multisite {
	protected static $sync_blog_id;

	/**
	 * Register hooks
	 */
	public function __construct() {
		add_action( 'wpmu_new_blog', array( __CLASS__, 'wpmu_new_blog' ) );
	}

	/**
	 *
	 * Add blog_id into a site option when create a new blog for async process
	 *
	 * Caution: This function is also used by "admin-blog.php" resync-content feature !
	 *
	 * @param $blog_id
	 */
	public static function wpmu_new_blog( $blog_id ) {
		$current_values = get_network_option( BEA_CSF_Synchronizations::get_option_network_id(), 'bea-csf-multisite-resync-blogs' );
		if ( false === $current_values || ! is_array( $current_values ) ) {
			$current_values = array();
		}

		$current_values[] = (int) $blog_id;
		$current_values   = array_unique( $current_values );

		update_network_option( BEA_CSF_Synchronizations::get_option_network_id(), 'bea-csf-multisite-resync-blogs', $current_values );
	}
}
