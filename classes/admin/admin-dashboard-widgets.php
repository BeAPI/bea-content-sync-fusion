<?php
class BEA_CSF_Admin_Dashboard_Widgets {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Add widget on each site dashboard
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'wp_dashboard_setup' ) );
	}

	/**
	 * Add widgets to the dashboard.
	 *
	 */
	public static function wp_dashboard_setup() {
		wp_add_dashboard_widget(
			'bea_csf_blog_admin_status_widget',
			__( 'Content synchronization status', 'bea-content-sync-fusion' ),
			array( __CLASS__, 'widget_render_status' )
		);

		wp_add_dashboard_widget(
			'bea_csf_blog_admin_list_widget',
			__( 'Synchronized contents awaiting validation', 'bea-content-sync-fusion' ),
			array( __CLASS__, 'widget_render_list' )
		);
	}

	/**
	 * Create the function to output the contents of our Dashboard Widget.
	 */
	public static function widget_render_status() {
		if ( isset( $_POST['bea_csf_force_blog_refresh'] ) ) {
			check_admin_referer( 'bea-csf-force-refresh' );

			// Process 30 items only
			BEA_CSF_Async::process_queue( 30, get_current_blog_id() );
			wp_cache_flush();
		}

		/**
		 * Get counter for the view
		 */
		$counter = BEA_CSF_Async::get_counter( get_current_blog_id() );

		// Include template
		include( BEA_CSF_DIR . 'views/admin/blog-widget-status.php' );
	}

	/**
	 * Create the function to output the contents of our Dashboard Widget.
	 */
	public static function widget_render_list() {
		// Get contents to validate
		$query_contents = new WP_Query(
			array(
				'post_type' => 'any',
				'post_status' => 'pending',
				'bea_csf_filter' => 'remote-only',
				'posts_per_page' => 10,
			)
		);

		// Include template
		include( BEA_CSF_DIR . 'views/admin/blog-widget-list.php' );
	}
}
