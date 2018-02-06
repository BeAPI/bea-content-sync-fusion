<?php

class BEA_CSF_Multisite {
	protected static $sync_blog_id;

	/**
	 * Register hooks
	 */
	public function __construct() {
		//add_action( 'wpmu_new_blog', array( __CLASS__, 'wpmu_new_blog' ) );
	}

	/**
	 *
	 * Add synchronization taxonomies / attachments / posts when create a new blog
     * Caution: This function is also used by "admin-blog.php" resync-content feature !
	 *
	 * @param $blog_id
	 */
	public static function wpmu_new_blog( $blog_id ) {
		self::$sync_blog_id = $blog_id;

		// TODO: Need to rebuild into an async item
		// TODO: Resend content from any blog ? Not only the First/MAIN (with the network admin)
	}
}