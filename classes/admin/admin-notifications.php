<?php

class BEA_CSF_Admin_Notifications {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );

		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
	}

	public static function admin_init() {
		if ( isset( $_POST['sync_notifications'] ) && is_array( $_POST['sync_notifications'] ) ) {
			check_admin_referer( 'update-bea-csf-notifications' );

			foreach ( $_POST['sync_notifications'] as $sync_id => $users_id ) {
				$_POST['sync_notifications'][ $sync_id ] = array_map( 'intval', $_POST['sync_notifications'][ $sync_id ] );
			}
			update_option( BEA_CSF_OPTION . '-notifications', $_POST['sync_notifications'] );
		}
	}

	public static function admin_menu() {
		add_options_page( __( 'Content Sync Notifications', 'bea-content-sync-fusion' ), __( 'Sync Notification', 'bea-content-sync-fusion' ), 'manage_options', 'bea-csfc-notifications', array(
			__CLASS__,
			'render_page'
		) );
	}

	/**
	 * Register JS and CSS for client part
	 *
	 * @param string $hook_suffix
	 */
	public static function admin_enqueue_scripts( $hook_suffix = '' ) {
		if ( isset( $hook_suffix ) && $hook_suffix == 'settings_page_bea-csfc-notifications' ) {
			wp_enqueue_script( 'lou-multi-select', BEA_CSF_URL . 'assets/js/lou-multi-select/js/jquery.multi-select.js', array( 'jquery' ), '0.9.8', true );
			wp_enqueue_script( 'bea-csf-admin-notifications', BEA_CSF_URL . 'assets/js/bea-csf-admin-notifications.js', array( 'lou-multi-select' ), BEA_CSF_VERSION, true );
			wp_localize_script( 'bea-csf-admin-notifications', 'beaCsfAdminNotifications', array(
				'selectableHeader' => __( 'Selectable users', 'bea-content-sync-fusion' ),
				'selectionHeader'  => __( 'Selection users', 'bea-content-sync-fusion' )
			) );
			wp_enqueue_style( 'lou-multi-select', BEA_CSF_URL . 'assets/js/lou-multi-select/css/multi-select.css', array(), '0.9.8', 'screen' );
			wp_enqueue_style( 'bea-csf-admin-notifications', BEA_CSF_URL . 'assets/css/bea-csf-admin-notifications.css', array(), BEA_CSF_VERSION );
		}
	}

	public static function render_page() {
		global $wpdb;

		// Prepare users array
		$users = array();

		// Get users admin/editor on this blog
		$roles = array( 'administrator', 'editor' );
		foreach ( $roles as $role ) {
			$users_query = new WP_User_Query( array( 'role' => $role, 'fields' => 'all' ) );
			if ( $users_query->get_total() > 0 ) {
				$users = array_merge( $users_query->get_results(), $users );
			}
		}

		// Get syncs with notifications enabled
		$syncs = BEA_CSF_Synchronizations::get( array(
			'notifications' => 1,
			'receivers'     => $wpdb->blogid
		), 'AND', false, true );

		// Get current values
		$current_values = get_option( BEA_CSF_OPTION . '-notifications' );

		// Include template
		include( BEA_CSF_DIR . 'views/admin/client-page-settings-notification.php' );

		return true;
	}
}
