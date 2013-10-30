<?php
class BEA_CSF_Admin_Synchronizations_Network {

	private static $_default_fields = array( 'label' => '', 'post_type' => '', 'mode' => 'auto', 'status' => 'publish', 'notifications' => 'true', 'emitters' => array( ), 'receivers' => array( ) );

	const admin_slug = 'bea-csf';

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Register hooks
		add_action( 'network_admin_menu', array( __CLASS__, 'network_admin_menu' ), 9 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		// Ajax Actions
		//add_action( 'wp_ajax_' . 'cps_getTermsList', array( __CLASS__, 'ajax_get_terms_list' ) );
		//add_action( 'wp_ajax_' . 'cps_UpdateTerm', array( __CLASS__, 'ajax_update_term' ) );
		//add_action( 'wp_ajax_' . 'cps_getPostsList', array( __CLASS__, 'ajax_get_posts_list' ) );
		//add_action( 'wp_ajax_' . 'cps_UpdatePost', array( __CLASS__, 'ajax_update_post' ) );
	}

	public static function admin_enqueue_scripts( $hook_suffix = '' ) {
		if ( isset( $hook_suffix ) && $hook_suffix == 'content-sync_page_' . self::admin_slug . '-edit' ) { // Edit page
			//wp_enqueue_script( 'bea-csf-jquery-ui', BEA_CSF_URL . 'assets/js/jquery-ui-1.8.16.custom.min.js', array( 'jquery' ), '1.8.16' );
			//wp_enqueue_script( 'bea-csf-admin-edit', BEA_CSF_URL . 'assets/js/bea-csf-admin-edit.js', array( 'jquery', 'bea-csf-jquery-ui' ), BEA_CSF_VERSION, true );
			//wp_enqueue_style( 'bea-csf-jquery-ui', BEA_CSF_URL . 'assets/css/smoothness/jquery-ui-1.8.16.custom.css', array( ), '1.8.16' );
			//wp_enqueue_style( 'bea-csf-admin-edit', BEA_CSF_URL . 'assets/css/bea-csf-admin-edit.css', array( ), BEA_CSF_VERSION );
		} elseif ( isset( $hook_suffix ) && $hook_suffix == 'content-sync_page_' . self::admin_slug . '-add' ) {

			wp_enqueue_script( 'lou-multi-select', BEA_CSF_URL . 'assets/js/lou-multi-select/js/jquery.multi-select.js', array( 'jquery' ), '0.9.8', true );
			wp_enqueue_script( 'bea-csf-admin-add', BEA_CSF_URL . 'assets/js/bea-csf-admin-add.js', array( 'lou-multi-select' ), BEA_CSF_VERSION, true );
			wp_localize_script( 'bea-csf-admin-add', 'beaCsfAdminAdd', array( 'selectableHeader' => __( 'Selectable items', BEA_CSF_LOCALE ), 'selectionHeader' => __( 'Selection items', BEA_CSF_LOCALE ) ) );
			wp_enqueue_style( 'lou-multi-select', BEA_CSF_URL . 'assets/js/lou-multi-select/css/multi-select.css', array( ), '0.9.8', 'screen' );
			wp_enqueue_style( 'bea-csf-admin-add', BEA_CSF_URL . 'assets/css/bea-csf-admin-add.css', array( ), BEA_CSF_VERSION );
		}
	}

	/**
	 * Add settings menu page
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function network_admin_menu() {
		add_menu_page( __( 'Content Sync', BEA_CSF_LOCALE ), __( 'Content Sync', BEA_CSF_LOCALE ), 'manage_options', self::admin_slug . '-edit', '', BEA_CSF_URL . '/assets/images/arrow-continue.png' );
		add_submenu_page( self::admin_slug . '-edit', __( 'Edit', BEA_CSF_LOCALE ), __( 'Edit', BEA_CSF_LOCALE ), 'manage_options', self::admin_slug . '-edit', array( __CLASS__, 'render_page_edit' ) );
		add_submenu_page( self::admin_slug . '-edit', __( 'Add', BEA_CSF_LOCALE ), __( 'Add', BEA_CSF_LOCALE ), 'manage_options', self::admin_slug . '-add', array( __CLASS__, 'render_page_add' ) );
	}

	/**
	 * Display options on admin
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function render_page_edit() {
		global $wpdb;

		// Get current syncs
		$registered_syncs = BEA_CSF_Synchronizations::get_all();

		// Translation, yes/no
		$i18n_true_false = array( '1' => __( 'Yes', BEA_CSF_LOCALE ), '0' => __( 'No', BEA_CSF_LOCALE ) );

		// Display message
		settings_errors( BEA_CSF_LOCALE );

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
		// Edition or add ?
		$edit = ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' && isset( $_GET['sync_id'] ) ) ? true : false;

		// if edit, merge array
		if ( $edit == true ) {

			$current_sync = BEA_CSF_Synchronizations::get( $_GET['sync_id'] );
			if ( $current_sync == false ) {
				wp_die( __( 'This synchronization ID not exists. Tcheater ?', BEA_CSF_LOCALE ) );
			}
		} else {
			$_POST['sync'] = (!isset( $_POST['sync'] ) ) ? array( ) : $_POST['sync'];

			$current_sync_fields = wp_parse_args( $_POST['sync'], self::$_default_fields );

			$current_sync = new BEA_CSF_Synchronization( $current_sync_fields );
		}

		// Display message
		settings_errors( BEA_CSF_LOCALE );

		// Include template
		include( BEA_CSF_DIR . 'views/admin/server-page-add.php' );

		return true;
	}

	/**
	 * Check for update content sync settings
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function admin_init() {
		if ( isset( $_POST['update-bea-csf-settings'] ) && isset( $_POST['sync'] ) ) { // Save
			check_admin_referer( 'update-bea-csf-settings' );
			
			$_POST['sync'] = stripslashes_deep($_POST['sync']);

			if ( empty( $_POST['sync']['label'] ) ) {
				add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __( 'You must defined a label.', BEA_CSF_LOCALE ), 'error' );
				return true;
			}
			if ( empty( $_POST['sync']['emitters'] ) ) {
				add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __( 'You must defined at least one emitter.', BEA_CSF_LOCALE ), 'error' );
				return true;
			}
			if ( empty( $_POST['sync']['receivers'] ) ) {
				add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __( 'You must defined at least one receiver.', BEA_CSF_LOCALE ), 'error' );
				return true;
			}

			$current_sync_fields = wp_parse_args( $_POST['sync'], self::$_default_fields );
			$new_sync = new BEA_CSF_Synchronization( $current_sync_fields );
			if ( isset( $_POST['sync']['id'] ) ) {
				$result = BEA_CSF_Synchronizations::update( $new_sync );
			} else {
				$result = BEA_CSF_Synchronizations::add( $new_sync );
			}

			if ( is_wp_error( $result ) ) {
				add_settings_error( BEA_CSF_LOCALE, 'settings_updated', $result->get_error_message(), 'error' );
			}

			wp_redirect( network_admin_url( 'admin.php?page=' . self::admin_slug . '-edit' ) );
			exit();
		}

		return true;
	}

	public static function get_sites_names( $blogs_id = array( ) ) {
		if ( empty( $blogs_id ) ) {
			return '';
		}

		// Get all blogs
		$blogs = BEA_CSF_Admin_Synchronizations_Network::get_blogs();

		$output = array( );
		foreach ( $blogs_id as $blog_id ) {
			if ( !isset( $blogs[$blog_id] ) ) {
				continue;
			}

			$output[] = $blogs[$blog_id]['blogname'];
		}

		return implode( ', ', $output );
	}

	/**
	 * Calcul SUM MD5 for all content to sync, use IDs and hash !
	 */
	/*
	  public static function get_local_sum() {
	  global $wpdb;

	  // Post types objects
	  $objects = $wpdb->get_col( "
	  SELECT ID
	  FROM $wpdb->posts
	  WHERE post_type IN ('" . implode( "', '", BEA_CSF_Server_Client::get_post_types() ) . "')
	  AND post_status = 'publish'
	  AND ID NOT IN ( SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'exclude_from_sync' AND meta_value = '1' )
	  ORDER BY post_parent ASC
	  " );

	  // Terms objects
	  $terms = get_terms( BEA_CSF_Server_Client::get_taxonomies(), array( 'hide_empty' => false, 'fields' => 'all' ) );

	  // Keep only ID !
	  $term_ids = array( );
	  foreach ( $terms as $term ) {
	  $term_ids[] = $term->term_taxonomy_id;
	  }

	  return md5( implode( '', $objects ) . implode( '', $term_ids ) );
	  }
	 */

	/**
	 * Get SUM for a client
	 */
	/*
	  public static function check_client_sum( $blog_id = 0, $master_sum = '' ) {
	  switch_to_blog( $blog_id );
	  $blog_sum = BEA_CSF_Client_Base::integrity();
	  restore_current_blog();

	  // Test SUM ?
	  if ( $blog_sum != $master_sum ) {
	  echo __( 'KO', BEA_CSF_LOCALE );
	  } else {
	  echo __( 'OK', BEA_CSF_LOCALE );
	  }
	  }
	 */

	/**
	 * Flush client datas
	 */
	/*
	  public static function flush_client( $blog_id = 0, $silent = false ) {
	  switch_to_blog( $blog_id );
	  $result = BEA_CSF_Client_Base::flush();
	  restore_current_blog();

	  // Client is valid ?
	  if ( $result == false && $silent == false ) {
	  add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __( 'Nothing to flush for this client !', BEA_CSF_LOCALE ), 'error' );
	  } elseif ( $silent == false ) {
	  add_settings_error( BEA_CSF_LOCALE, 'settings_updated', __( 'Client flushed with success !', BEA_CSF_LOCALE ), 'updated' );
	  }
	  }
	 */

	/*	 * *** AJAX Features ***** */
	/*
	  public static function check_ajax_nonce() {
	  if ( !wp_verify_nonce( $_POST['nonce'], 'resync-client-' . $_POST['blog_id'] ) ) {
	  echo json_encode( array( ) );
	  die();
	  }
	  }

	  public static function ajax_get_terms_list() {
	  header( 'Content-type: application/jsonrequest' );
	  self::check_ajax_nonce();

	  // init array for output
	  $output = array( );

	  // Get objects
	  $terms = get_terms( BEA_CSF_Server_Client::get_taxonomies(), array( 'hide_empty' => false ) );
	  foreach ( $terms as $term ) {
	  $output[] = array( 't_id' => $term->term_id, 'tt_id' => $term->term_taxonomy_id, 'taxonomy' => $term->taxonomy );
	  }

	  echo json_encode( $output );
	  exit;
	  }

	  public static function ajax_update_term() {
	  header( 'Content-type: application/jsonrequest' );
	  self::check_ajax_nonce();

	  // Check params
	  if ( !isset( $_POST['blog_id'] ) || !isset( $_POST['tt_id'] ) || !isset( $_POST['term_id'] ) || absint( $_POST['tt_id'] ) == 0 || absint( $_POST['term_id'] ) == 0 || !isset( $_POST['taxonomy'] ) ) {
	  echo json_encode( array( 'status' => 'error', 'message' => 'Missing tt_id or term_id' ) );
	  exit;
	  }

	  $response = BEA_CSF_Server_Taxonomy::merge_term( $_POST['term_id'], $_POST['tt_id'], $_POST['taxonomy'], (int) $_POST['blog_id'] );
	  if ( is_numeric( $response ) ) {
	  $output = array( 'status' => 'success', 'message' => 'Success' );
	  } else {
	  if ( is_wp_error( $response ) ) {
	  $output = array( 'status' => 'error', 'message' => $response->get_error_message() );
	  } else {
	  $output = array( 'status' => 'error', 'message' => 'An unidentified error' );
	  }
	  }

	  echo json_encode( $output );
	  exit;
	  }

	  public static function ajax_get_posts_list() {
	  global $wpdb;

	  header( 'Content-type: application/jsonrequest' );
	  self::check_ajax_nonce();

	  // init array for output
	  $output = array( );

	  // Get objects
	  $objects = $wpdb->get_col( $wpdb->prepare( "
	  SELECT ID
	  FROM $wpdb->posts
	  WHERE post_type IN ('" . implode( "', '", BEA_CSF_Server_Client::get_post_types() ) . "')
	  AND post_status = 'publish'
	  AND ID NOT IN ( SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'exclude_from_sync' AND meta_value = '1' )
	  ORDER BY post_parent ASC
	  " ) );
	  foreach ( $objects as $object_id ) {
	  $output[] = array( 'post_id' => $object_id );
	  }

	  echo json_encode( $output );
	  exit;
	  }

	  public static function ajax_update_post() {
	  header( 'Content-type: application/jsonrequest' );
	  self::check_ajax_nonce();

	  // Check params
	  if ( !isset( $_POST['blog_id'] ) || !isset( $_POST['post_id'] ) || absint( $_POST['post_id'] ) == 0 ) {
	  echo json_encode( array( 'status' => 'error', 'message' => 'Missing post_id or site url' ) );
	  exit;
	  }

	  $response = BEA_CSF_Server_PostType::wp_insert_post( $_POST['post_id'], null, (int) $_POST['blog_id'] );
	  if ( is_numeric( $response ) ) {
	  $output = array( 'status' => 'success', 'message' => 'Sucess' );
	  } else {
	  if ( is_wp_error( $response ) ) {
	  $output = array( 'status' => 'error', 'message' => $response->get_error_message() );
	  } else {
	  $output = array( 'status' => 'error', 'message' => 'An unidentified error' );
	  }
	  }

	  echo json_encode( $output );
	  exit;
	  }
	 */

	public static function get_blogs( $site_id = 0 ) {
		global $wpdb;

		if ( $site_id == 0 ) {
			$site_id = $wpdb->siteid;
		}

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY blog_id ASC", $site_id ), ARRAY_A );
		if ( empty( $results ) ) {
			return false;
		}

		$blogs = array( );
		foreach ( $results as $result ) {
			$blogs[$result['blog_id']] = $result;
			$blogs[$result['blog_id']]['blogname'] = get_blog_option( $result['blog_id'], 'blogname' );
		}

		return $blogs;
	}

}