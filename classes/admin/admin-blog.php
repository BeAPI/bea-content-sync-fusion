<?php
class BEA_CSF_Admin_Blog {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Add actions link into sites list
		add_filter( 'manage_sites_action_links', array(__CLASS__, 'manage_sites_action_links'), 10, 3 );
		add_action( 'wpmuadminedit', array(__CLASS__, 'wpmuadminedit') );
		add_action( 'network_sites_updated_message_'.'resync_bea_csf_content', array(__CLASS__, 'network_sites_updated_message') );

		// Add widget on each site dashboard
		add_action( 'wp_dashboard_setup', array(__CLASS__, 'wp_dashboard_setup') );

		// Allow filter content view by origin
		add_filter( 'restrict_manage_posts', array(__CLASS__, 'restrict_manage_posts'), 10, 2 );
		add_action( 'query_vars', array(__CLASS__, 'query_vars'), 10 );
		add_action( 'parse_query', array(__CLASS__, 'parse_query'), 10 );
		add_filter( 'posts_join', array(__CLASS__, 'posts_join'), 10, 2 );
		add_filter( 'posts_where', array(__CLASS__, 'posts_where'), 10, 2 );
	}

	/**
	 * Add a selector for filter local or remote media
	 *
	 * @param $post_type
	 * @param $which
	 *
	 * @return bool
	 */
	public static function restrict_manage_posts( $post_type, $which ) {
		global $wpdb;

		// Get syncs model for current post_type, with any mode status (manual and auto)
		$_has_syncs = BEA_CSF_Synchronizations::get( array(
			'post_type' => $post_type,
			'receivers'  => $wpdb->blogid,
		), 'AND', false, true );

		if ( empty($_has_syncs) ) { // || $which != 'bar'
			return false;
		}

		$current_action = ( !isset($_GET['attachment-bea-csf-filter']) ) ? '' : $_GET['attachment-bea-csf-filter'];

		$output = '<label for="attachment-filter" class="screen-reader-text">'.__('Show all media', 'bea-content-sync-fusion').'</label>';
		$output .= '<select class="attachment-filters" name="attachment-bea-csf-filter" id="attachment-bea-csf-filter">';
		$output .= '<option value="">'.__('Show all content', 'bea-content-sync-fusion').'</option>';
		$output .= '<option '.selected($current_action, 'local-only', false).' value="local-only">'.__('Show only local content', 'bea-content-sync-fusion').'</option>';
		$output .= '<option '.selected($current_action, 'remote-only', false).' value="remote-only">'.__('Show only remote content', 'bea-content-sync-fusion').'</option>';
		$output .= '</select>';

		echo $output;
	}

	/**
	 * Append a custom query var for this new filter on WP_Query
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function query_vars( $vars ) {
		$vars[] = "bea_csf_filter";
		return $vars;
	}


	/**
	 * Set a WP_Query query_var depending $_GET query !
	 *
	 * @param WP_Query $query
	 *
	 * @return boolean
	 */
	public static function parse_query( WP_Query $query ) {
		if ( !isset($_GET['attachment-bea-csf-filter']) || !$query->is_main_query() || empty($_GET['attachment-bea-csf-filter']) ) {
			return false;
		}

		$query->set( 'bea_csf_filter', stripslashes($_GET['attachment-bea-csf-filter']) );
		return true;
	}

	/**
	 * Add join with relations tables for filter local or remote media
	 *
	 * @param $join
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	public static function posts_join( $join, WP_Query $query ) {
		global $wpdb;

		if ( empty($query->get('bea_csf_filter')) ) {
			return $join;
		}

		$join_type = $query->get('bea_csf_filter') == 'local-only' ? 'LEFT' : 'INNER';

		$join .= " $join_type JOIN $wpdb->bea_csf_relations AS bcr ON ( $wpdb->posts.ID = bcr.receiver_id AND bcr.receiver_blog_id = " . get_current_blog_id() . " ) ";
		return $join;
	}

	/**
	 * Add join with relations tables for filter local
	 *
	 * @param $where
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	public static function posts_where( $where, WP_Query $query ) {
		if ( empty($query->get('bea_csf_filter')) || $query->get('bea_csf_filter') != 'local-only' ) {
			return $where;
		}

		$where .= " AND bcr.receiver_id IS NULL ";
		return $where;
	}

	/**
	 * Add an item for resync 
	 * 
	 * @param  array $actions
	 * @param  integer $blog_id
	 * @param  string $blogname
	 * @return array
	 */
	public static function manage_sites_action_links( $actions, $blog_id, $blogname ) {
		$actions['resync'] = '<a href="' . esc_url( wp_nonce_url( network_admin_url( 'sites.php?action=resync_bea_csf_content&amp;id=' . $blog_id ), 'resync_blog_content_' . $blog_id ) ) . '">' . __( 'Resync content', 'bea-content-sync-fusion' ) . '</a>';

		return $actions;
	}

	/**
	 * Check GET call for resync site contents
	 */
	public static function wpmuadminedit() {
		$id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;

		if ( isset($_GET['action']) && $_GET['action'] == 'resync_bea_csf_content' && $id > 0 ) {
			check_admin_referer( 'resync_blog_content_' . $id );

			BEA_CSF_Multisite::wpmu_new_blog( $id );

			wp_safe_redirect( add_query_arg( array( 'updated' => 'resync_bea_csf_content' ), wp_get_referer() ) );
			exit();
		}
	}

	/**
	 * Customize message notifiation
	 * 
	 * @param  string
	 * @return string
	 */
	public static function network_sites_updated_message( $msg = '' ) {
		if ( isset($_GET['updated']) && $_GET['updated'] == 'resync_bea_csf_content' ) {
			return __( 'Resync website OK.', 'bea-content-sync-fusion' );
		}

		return $msg;
	}

	/**
	 * Add a widget to the dashboard.
	 *
	 */
	public static function wp_dashboard_setup() {
		wp_add_dashboard_widget(
			'bea_csf_blog_admin_status_widget',
			__('Content synchronization status', 'bea-content-sync-fusion'),
			array(__CLASS__, 'widget_render_status')
		);

		wp_add_dashboard_widget(
			'bea_csf_blog_admin_list_widget',
			__('Synchronized contents awaiting validation', 'bea-content-sync-fusion'),
			array(__CLASS__, 'widget_render_list')
		);
	}

	/**
	 * Create the function to output the contents of our Dashboard Widget.
	 */
	public static function widget_render_status() {
		if ( isset($_POST['bea_csf_force_blog_refresh']) ) {
			check_admin_referer( 'bea-csf-force-refresh' );

			// Process 30 items only
			BEA_CSF_Async::process_queue( 30, get_current_blog_id() );
			wp_cache_flush();
		}

		// Include template
		include( BEA_CSF_DIR . 'views/admin/blog-widget-status.php' );

		return true;
	}

	/**
	 * Create the function to output the contents of our Dashboard Widget.
	 */
	public static function widget_render_list() {
		// Get contents to validate
		$query_contents = new WP_Query( array(
			'post_type' => 'any',
			'post_status' => 'pending',
			'bea_csf_filter' => 'remote-only',
			'posts_per_page' => 10
		));

		// Include template
		include( BEA_CSF_DIR . 'views/admin/blog-widget-list.php' );

		return true;
	}
}