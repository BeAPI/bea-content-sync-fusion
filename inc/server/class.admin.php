<?php
class BEA_CSF_Server_Admin {
	const admin_slug = 'bea-css';
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Register hooks
		add_action( 'network_admin_menu', array(__CLASS__, 'network_admin_menu'), 9 );
		add_action( 'admin_init', array(__CLASS__, 'admin_init') );
		add_action( 'admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts') );
		
		// Ajax Actions
		add_action( 'wp_ajax_'.'cps_getTermsList', array( __CLASS__, 'ajax_get_terms_list' ) );
		add_action( 'wp_ajax_'.'cps_UpdateTerm', array( __CLASS__, 'ajax_update_term' ) );
		
		add_action( 'wp_ajax_'.'cps_getPostsList', array( __CLASS__, 'ajax_get_posts_list' ) );
		add_action( 'wp_ajax_'.'cps_UpdatePost', array( __CLASS__, 'ajax_update_post' ) );
	}

	public static function admin_enqueue_scripts( $hook_suffix = '' ) {
		if( isset( $hook_suffix ) && $hook_suffix == 'content-sync_page_'.self::admin_slug . '-edit' ) { // Edit page
			
			wp_enqueue_script( 'bea-css-jquery-ui', BEA_CSF_URL.'ressources/js/jquery-ui-1.8.16.custom.min.js', array('jquery'), '1.8.16' );
			wp_enqueue_script( 'bea-css-admin-edit', BEA_CSF_URL.'ressources/js/bea-css-admin-edit.js', array( 'jquery', 'bea-css-jquery-ui' ), BEA_CSF_VERSION, true );
			wp_enqueue_style( 'bea-css-jquery-ui', BEA_CSF_URL.'ressources/css/smoothness/jquery-ui-1.8.16.custom.css', array(), '1.8.16' );
			wp_enqueue_style( 'bea-css-admin-edit', BEA_CSF_URL.'ressources/css/bea-css-admin-edit.css', array(), BEA_CSF_VERSION );
			
		} elseif( isset( $hook_suffix ) && $hook_suffix == 'content-sync_page_'.self::admin_slug . '-add' ) {
			
			wp_enqueue_script( 'lou-multi-select', BEA_CSF_URL.'ressources/js/lou-multi-select/js/jquery.multi-select.js', array('jquery'), '0.9.8', true );
			wp_enqueue_script( 'bea-css-admin-add', BEA_CSF_URL.'ressources/js/bea-css-admin-add.js', array( 'lou-multi-select' ), BEA_CSF_VERSION, true );
			wp_localize_script('bea-css-admin-add', 'beaCssAdminAdd', array('selectableHeader' => __('Selectable items', BEA_CSF_LOCALE), 'selectionHeader' => __('Selection items', BEA_CSF_LOCALE)) );
			wp_enqueue_style( 'lou-multi-select', BEA_CSF_URL.'ressources/js/lou-multi-select/css/multi-select.css', array(), '0.9.8', 'screen' );
			wp_enqueue_style( 'bea-css-admin-add', BEA_CSF_URL.'ressources/css/bea-css-admin-add.css', array(), BEA_CSF_VERSION );
			
		}
	}
	/**
	 * Add settings menu page
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function network_admin_menu() {
		add_menu_page( __('Content Sync', BEA_CSF_LOCALE), __('Content Sync', BEA_CSF_LOCALE), 'manage_options', self::admin_slug . '-edit', '', BEA_CSF_URL . '/ressources/images/arrow-continue.png' );
		add_submenu_page( self::admin_slug . '-edit', __('Edit', BEA_CSF_LOCALE), __('Edit', BEA_CSF_LOCALE), 'manage_options', self::admin_slug . '-edit', array( __CLASS__, 'render_page_edit' ) );
		add_submenu_page( self::admin_slug . '-edit', __('Add', BEA_CSF_LOCALE), __('Add', BEA_CSF_LOCALE), 'manage_options', self::admin_slug.'-add', array( __CLASS__, 'render_page_add' ) );
	}

	/**
	 * Display options on admin
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function render_page_edit() {
		global $wpdb;
		
		// Get current options
		$current_options = (array) get_site_option( BEA_CSF_OPTION );
		
		// Get current sum
		$current_sum = self::get_local_sum();
		
		// Get blogs
		$blogs = self::get_blogs($wpdb->siteid);
		
		// Display message
		settings_errors(BEA_CSF_LOCALE);
		
		// Include template
		include( BEA_CSF_DIR . 'views/admin/server-page-settings.php' );
		
		return true;
	}
	
	/**
	 * Display options on admin
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function render_page_add() {
		// Include template
		include( BEA_CSF_DIR . 'views/admin/server-page-add.php' );
		
		return true;
	}
	
	/**
	 * Check for update clients list
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function admin_init() {
		if ( isset($_POST['update-bea-csf-settings']) ) { // Save
		
			check_admin_referer( 'update-bea-csf-settings' );
		
			$option = array('master' => 0, 'clients' => array());
			
			$option['master']  = (isset($_POST['master'])) ? $_POST['master'] : 0;
			$option['clients'] = (isset($_POST['client'])) ? (array) $_POST['client'] : array();
			
			// Remove master from clients
			if ( ($pos = array_search($option['master'], $option['clients'])) !== false ) {
				unset($option['clients'][$pos]);
			}
			
			update_site_option( BEA_CSF_OPTION, $option );
			
		} elseif( isset($_GET['action']) && $_GET['action'] == 'flush' && isset($_GET['blog_id']) && (int) $_GET['blog_id'] > 0 ) { // Resync
			
			check_admin_referer( 'flush-client-'.urlencode($_GET['blog_id']) );
			
			// Get current options
			$current_options = get_site_option( BEA_CSF_OPTION );
			
			// URL Exist on DB ?
			if ( !in_array($_GET['blog_id'], $current_options['clients']) ) {
				add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __('This blog ID are not a client... Tcheater ?', BEA_CSF_LOCALE), 'error' );
			} else {
				self::flush_client( $_GET['blog_id'] );
				add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __('Blog flushed with success.', BEA_CSF_LOCALE), 'updated' );
			}
		} elseif ( isset($_POST['flush-all-bea-csf-settings']) ) { // Flush all
			
			check_admin_referer( 'update-bea-csf-settings' );
			
			// Get current options
			$current_options = get_site_option( BEA_CSF_OPTION );
			foreach( (array) $current_options['clients'] as $blog_id ) {
				self::flush_client( $blog_id, true );
			}
			
			add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __('All blogs flushed with success.', BEA_CSF_LOCALE), 'updated' );
		
		}
		
		return true;
	}
	
	/**
	 * Calcul SUM MD5 for all content to sync, use IDs and hash !
	 */
	public static function get_local_sum() {
		global $wpdb;
		
		// Post types objects
		$objects = $wpdb->get_col( "
			SELECT ID 
			FROM $wpdb->posts 
			WHERE post_type IN ('".implode("', '", BEA_CSF_Server_Client::get_post_types())."') 
			AND post_status = 'publish' 
			AND ID NOT IN ( SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'exclude_from_sync' AND meta_value = '1' )
			ORDER BY post_parent ASC
		" );
		
		// Terms objects
		$terms = get_terms( BEA_CSF_Server_Client::get_taxonomies(), array('hide_empty' => false, 'fields' => 'all') );
		
		// Keep only ID !
		$term_ids = array();
		foreach( $terms as $term ) {
			$term_ids[] = $term->term_taxonomy_id;
		}
		
		return md5( implode('', $objects) . implode('', $term_ids) );
	}

	/**
	 * Get SUM for a client
	 */
	public static function check_client_sum( $blog_id = 0, $master_sum = '' ) {
		switch_to_blog($blog_id);
		$blog_sum = BEA_CSF_Client_Base::integrity();
		restore_current_blog();
		
		// Test SUM ?
		if( $blog_sum != $master_sum ) {
			echo __('KO', BEA_CSF_LOCALE);
		} else {
			echo __('OK', BEA_CSF_LOCALE);
		}
	}
	
	/**
	 * Flush client datas
	 */
	public static function flush_client( $blog_id = 0, $silent = false ) {
		switch_to_blog($blog_id);
		$result = BEA_CSF_Client_Base::flush();
		restore_current_blog();
		
		// Client is valid ?
		if ( $result == false && $silent == false ) {
			add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __('Nothing to flush for this client !', BEA_CSF_LOCALE), 'error' );
		} elseif ( $silent == false ) {
			add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __('Client flushed with success !', BEA_CSF_LOCALE), 'updated' );
		}
	}
	
	/***** AJAX Features ******/
	public static function check_ajax_nonce() {
		if( !wp_verify_nonce( $_POST['nonce'],'resync-client-'. $_POST['blog_id'] ) ) {
			echo json_encode( array() );
			die();
		}
	}

	public static function ajax_get_terms_list() {
		header( 'Content-type: application/jsonrequest' );
		self::check_ajax_nonce();
		
		// init array for output
		$output = array();
		
		// Get objects
		$terms = get_terms( BEA_CSF_Server_Client::get_taxonomies(), array('hide_empty' => false) );
		foreach( $terms as $term ) {
			$output[] = array( 't_id' => $term->term_id ,'tt_id' => $term->term_taxonomy_id, 'taxonomy' => $term->taxonomy );
		}
		
		echo json_encode( $output );
		exit;
	}

	public static function ajax_update_term() {
		header( 'Content-type: application/jsonrequest' );
		self::check_ajax_nonce();
		
		// Check params
		if( !isset( $_POST['blog_id'] ) || !isset( $_POST['tt_id'] ) || !isset( $_POST['term_id'] ) || absint( $_POST['tt_id'] ) == 0 || absint( $_POST['term_id'] ) == 0 || !isset( $_POST['taxonomy'] ) ) {
			echo json_encode( array( 'status' => 'error', 'message' => 'Missing tt_id or term_id' ) );
			exit;
		}
		
		$response = BEA_CSF_Server_Taxonomy::merge_term( $_POST['term_id'], $_POST['tt_id'], $_POST['taxonomy'], (int) $_POST['blog_id'] );
		if( is_numeric( $response ) ) {
			$output = array( 'status' => 'success', 'message' => 'Success' );
		} else {
			if ( is_wp_error($response) ) {
				$output = array( 'status' => 'error', 'message' => $response->get_error_message() );
			} else {
				$output = array( 'status' => 'error', 'message' => 'An unidentified error' );
			}
		}
		
		echo json_encode( $output );
		exit;
	}
	
	public static function ajax_get_posts_list(){
		global $wpdb;
		
		header( 'Content-type: application/jsonrequest' );
		self::check_ajax_nonce();
		
		// init array for output
		$output = array();
		
		// Get objects
		$objects = $wpdb->get_col( $wpdb->prepare( "
			SELECT ID 
			FROM $wpdb->posts 
			WHERE post_type IN ('".implode("', '", BEA_CSF_Server_Client::get_post_types())."') 
			AND post_status = 'publish'
			AND ID NOT IN ( SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'exclude_from_sync' AND meta_value = '1' )
			ORDER BY post_parent ASC
		" ) );
		foreach( $objects as $object_id ) {
			$output[] = array( 'post_id' => $object_id );
		}
		
		echo json_encode( $output );
		exit;
	}
	
	public static function ajax_update_post(){
		header( 'Content-type: application/jsonrequest' );
		self::check_ajax_nonce();
		
		// Check params
		if( !isset( $_POST['blog_id'] ) || !isset( $_POST['post_id'] ) || absint( $_POST['post_id'] ) == 0 ) {
			echo json_encode( array( 'status' => 'error', 'message' => 'Missing post_id or site url' ) );
			exit;
		}

		$response = BEA_CSF_Server_PostType::wp_insert_post( $_POST['post_id'], null, (int) $_POST['blog_id'] );
		if( is_numeric( $response ) ) {
			$output = array( 'status' => 'success', 'message' => 'Sucess' );
		} else {
			if ( is_wp_error($response) ) {
				$output = array( 'status' => 'error', 'message' => $response->get_error_message() );
			} else {
				$output = array( 'status' => 'error', 'message' => 'An unidentified error' );
			}
		}
		
		echo json_encode( $output );
		exit;
	}
	
	public static function get_blogs( $site_id = 0 ) {
		global $wpdb;
		
		if ( $site_id == 0 ) {
			$site_id = $wpdb->siteid;
		}
		
		return $wpdb->get_results( $wpdb->prepare("SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY blog_id ASC", $site_id), ARRAY_A );
	}
}