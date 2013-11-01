<?php
class BEA_CSF_Admin_Synchronizations_Network {

	private static $_default_fields = array( 'label' => '', 'post_type' => '', 'mode' => 'auto', 'status' => 'publish', 'notifications' => 'true', 'emitters' => array( ), 'receivers' => array( ) );

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
	}

	/**
	 * Register JS/CSS on edit/add page
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function admin_enqueue_scripts( $hook_suffix = '' ) {
		if ( isset( $hook_suffix ) && $hook_suffix == 'content-sync_page_' . 'bea-csf-edit' ) { // Edit page
		} elseif ( isset( $hook_suffix ) && $hook_suffix == 'content-sync_page_' . 'bea-csf-add' ) {
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
		add_menu_page( __( 'Content Sync', BEA_CSF_LOCALE ), __( 'Content Sync', BEA_CSF_LOCALE ), 'manage_options', 'bea-csf-edit', '', BEA_CSF_URL . '/assets/images/arrow-continue.png' );
		add_submenu_page( 'bea-csf-edit', __( 'Edit', BEA_CSF_LOCALE ), __( 'Edit', BEA_CSF_LOCALE ), 'manage_options', 'bea-csf-edit', array( __CLASS__, 'render_page_edit' ) );
		add_submenu_page( 'bea-csf-edit', __( 'Add', BEA_CSF_LOCALE ), __( 'Add', BEA_CSF_LOCALE ), 'manage_options', 'bea-csf-add', array( __CLASS__, 'render_page_add' ) );
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

			$current_sync = BEA_CSF_Synchronizations::get( array( 'id' => $_GET['sync_id'] ) );
			if ( $current_sync == false ) {
				wp_die( __( 'This synchronization ID not exists. Tcheater ?', BEA_CSF_LOCALE ) );
			}
			$current_sync = current( $current_sync ); // take first result
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

			$_POST['sync'] = stripslashes_deep( $_POST['sync'] );

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

			wp_redirect( network_admin_url( 'admin.php?page=' . 'bea-csf-edit&message=merged' ) );
			exit();
		}
		
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'bea-csf-edit' && isset( $_GET['action'] ) && $_GET['action'] == 'delete' && isset( $_GET['sync_id'] ) ) { // Delete
			check_admin_referer( 'delete-sync' );
			
			$current_sync = BEA_CSF_Synchronizations::get( array( 'id' => $_GET['sync_id'] ) );
			if ( $current_sync == false ) {
				wp_die( __( 'This synchronization ID not exists. Tcheater ?', BEA_CSF_LOCALE ) );
			}
			
			$current_sync = current( $current_sync ); // take first result
			BEA_CSF_Synchronizations::delete( $current_sync );
			
			wp_redirect( network_admin_url( 'admin.php?page=' . 'bea-csf-edit&message=deleted' ) );
			exit();
		}

		return true;
	}

	/**
	 * Helper: Get sites list for a network ID
	 *
	 * @return array|boolean
	 * @author Amaury Balmer
	 */
	public static function get_sites_from_network( $network_id = 0 ) {
		global $wpdb;

		if ( $network_id == 0 ) {
			$network_id = $wpdb->siteid;
		}

		$results = $wpdb->get_results( $wpdb->prepare( "SELECT blog_id, domain, path FROM $wpdb->blogs WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY blog_id ASC", $network_id ), ARRAY_A );
		if ( empty( $results ) ) {
			return false;
		}

		$sites = array( );
		foreach ( $results as $result ) {
			$sites[$result['blog_id']] = $result;
			$sites[$result['blog_id']]['blogname'] = get_blog_option( $result['blog_id'], 'blogname' );
		}

		return $sites;
	}

	/**
	 * Helper: Get filtred sites list for a network ID, all data or one field
	 *
	 * @return array|string
	 * @author Amaury Balmer
	 */
	public static function get_sites( $blogs_id = array( ), $field = false ) {
		if ( empty( $blogs_id ) ) {
			return '';
		}

		// Get all sites
		$blogs = BEA_CSF_Admin_Synchronizations_Network::get_sites_from_network();

		$filtered = array( );
		foreach ( $blogs_id as $blog_id ) {
			if ( !isset( $blogs[$blog_id] ) ) {
				continue;
			}

			if ( $field == false ) {
				$filtered[] = $blogs[$blog_id];
			} elseif ( isset( $blogs[$blog_id][$field] ) ) {
				$filtered[] = $blogs[$blog_id][$field];
			} else {
				continue;
			}
		}

		return $filtered;
	}

}