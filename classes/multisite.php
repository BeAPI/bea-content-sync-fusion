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

		add_filter( 'bea_csf.pre_pre_send_data', array( __CLASS__, 'bea_csf_pre_pre_send_data' ), 10, 2 );
		// TODO: Need to rebuild into an async item
		self::sync_all_terms();
		self::sync_all_attachments();
		self::sync_all_posts();

		remove_filter( 'bea_csf.pre_pre_send_data', array( __CLASS__, 'bea_csf_pre_pre_send_data' ), 10, 2 );

		// TODO: Resend content from any blog ? Not only the First/MAIN (with the network admin)

	}

	/**
	 *
	 * Drop variable content for keep blog_id only to the freshly new created blog !
	 *
	 * @param $receiver_blog_id
	 * @param $sync
	 */
	public static function bea_csf_pre_pre_send_data( $receiver_blog_id, BEA_CSF_Synchronization $sync ) {
		if ( ! is_int( self::$sync_blog_id ) || empty( self::$sync_blog_id ) ) {
			return $receiver_blog_id;
		}

		if ( self::$sync_blog_id == $receiver_blog_id ) {
			return $receiver_blog_id;
		}

		return false;
	}
}