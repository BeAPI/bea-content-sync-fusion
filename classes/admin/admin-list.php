<?php
class BEA_CSF_Admin_List {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Allow filter content view by origin
		add_filter( 'restrict_manage_posts', array(__CLASS__, 'restrict_manage_posts'), 10 );
		add_action( 'query_vars', array(__CLASS__, 'query_vars'), 10 );
		add_action( 'parse_query', array(__CLASS__, 'parse_query'), 10 );
		add_filter( 'posts_join', array(__CLASS__, 'posts_join'), 10, 2 );
		add_filter( 'posts_where', array(__CLASS__, 'posts_where'), 10, 2 );
	}


	/**
	 * Add a selector for filter local or remote media
	 *
	 * @param $post_type
	 *
	 * @return bool
	 */
	public static function restrict_manage_posts( $post_type ) {
		global $wpdb;

		// Get syncs model for current post_type, with any mode status (manual and auto)
		$_has_syncs = BEA_CSF_Synchronizations::get( array(
			'post_type' => $post_type,
			'receivers'  => $wpdb->blogid,
		), 'AND', false, true );

		if ( empty($_has_syncs) ) {
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
}
