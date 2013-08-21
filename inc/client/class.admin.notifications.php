<?php
class BEA_CSF_Client_Admin_Notifications {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts') );
	}

	public static function admin_menu() {
		add_options_page( __('Content Sync Notifications', BEA_CSF_LOCALE), __('Sync Notification', BEA_CSF_LOCALE), 'manage_options', 'bea-csfc-notifications', array( __CLASS__, 'render_page' ) );
	}
	
	/**
	 * Register JS and CSS for client part
	 * 
	 * @param string $hook_suffix
	 */
	public static function admin_enqueue_scripts( $hook_suffix = '' ) {
		if ( isset( $hook_suffix ) && $hook_suffix == 'settings_page_bea-csfc-notifications' ) {
			wp_enqueue_script( 'lou-multi-select', BEA_CSF_URL.'ressources/js/lou-multi-select/js/jquery.multi-select.js', array('jquery'), '0.9.8', true );
			wp_enqueue_script( 'bea-csc-admin-notifications', BEA_CSF_URL.'ressources/js/bea-csc-admin-notifications.js', array( 'lou-multi-select' ), BEA_CSF_VERSION, true );
			wp_localize_script('bea-csc-admin-notifications', 'beaCscAdminAdd', array('selectableHeader' => __('Selectable users', BEA_CSF_LOCALE), 'selectionHeader' => __('Selection users', BEA_CSF_LOCALE)) );
			wp_enqueue_style( 'lou-multi-select', BEA_CSF_URL.'ressources/js/lou-multi-select/css/multi-select.css', array(), '0.9.8', 'screen' );
			wp_enqueue_style( 'bea-css-admin-notifications', BEA_CSF_URL.'ressources/css/bea-csc-admin-notifications.css', array(), BEA_CSF_VERSION );
		}
	}

	public static function render_page() {
		// Prepare users array
		$users = array();
		
		// Get users admin/editor on this blog
		$roles = array('administrator', 'editor');
		foreach ( $roles as $role ) {
			$users_query = new WP_User_Query( array( 'role' => $role, 'fields' => 'all' ) );
			if ( $users_query->get_total() > 0 ) {
				$users = array_merge( $users_query->get_results(), $users );
			}
		}
		
		// Include template
		include( BEA_CSF_DIR . 'views/admin/client-page-settings-notification.php' );
		
		return true;
	}
}