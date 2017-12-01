<?php

class BEA_CSF_Admin_Synchronizations_Network {

	private static $_default_fields = array(
		'label'     => '',
		'post_type' => '',
		'mode'      => 'auto',
		'status'    => 'publish',
		'emitters'  => array(),
		'receivers' => array()
	);

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
			wp_localize_script( 'bea-csf-admin-add', 'beaCsfAdminAdd', array(
				'selectableHeader' => __( 'Selectable items', 'bea-content-sync-fusion' ),
				'selectionHeader'  => __( 'Selection items', 'bea-content-sync-fusion' )
			) );
			wp_enqueue_style( 'lou-multi-select', BEA_CSF_URL . 'assets/js/lou-multi-select/css/multi-select.css', array(), '0.9.8', 'screen' );
			wp_enqueue_style( 'bea-csf-admin-add', BEA_CSF_URL . 'assets/css/bea-csf-admin-add.css', array(), BEA_CSF_VERSION );
		}
	}

	/**
	 * Add settings menu page
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function network_admin_menu() {
		add_menu_page( __( 'Content Sync', 'bea-content-sync-fusion' ), __( 'Content Sync', 'bea-content-sync-fusion' ), 'manage_options', 'bea-csf-edit', '', BEA_CSF_URL . '/assets/images/arrow-continue.png' );
		add_submenu_page( 'bea-csf-edit', __( 'Edit', 'bea-content-sync-fusion' ), __( 'Edit', 'bea-content-sync-fusion' ), 'manage_options', 'bea-csf-edit', array(
			__CLASS__,
			'render_page_edit',
		) );
		add_submenu_page( 'bea-csf-edit', __( 'Add', 'bea-content-sync-fusion' ), __( 'Add', 'bea-content-sync-fusion' ), 'manage_options', 'bea-csf-add', array(
			__CLASS__,
			'render_page_add',
		) );
		add_submenu_page( 'bea-csf-edit', __( 'Queue', 'bea-content-sync-fusion' ), __( 'Queue', 'bea-content-sync-fusion' ), 'manage_options', 'bea-csf-queue', array(
			__CLASS__,
			'render_page_queue',
		) );
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
		$i18n_true_false = array( '1' => __( 'Yes', 'bea-content-sync-fusion' ), '0' => __( 'No', 'bea-content-sync-fusion' ) );

		// Display message
		settings_errors( 'bea-content-sync-fusion' );

		// Get current setting
		$current_settings = get_site_option( 'csf_adv_settings' );

		// Get P2P registered connection
		$p2p_registered_connections = array();
		if ( class_exists( 'P2P_Connection_Type_Factory' ) ) {
			$p2p_registered_connections = P2P_Connection_Type_Factory::get_all_instances();
		}

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
				wp_die( __( 'This synchronization ID not exists. Tcheater ?', 'bea-content-sync-fusion' ) );
			}
			$current_sync = current( $current_sync ); // take first result
		} else {
			$_POST['sync'] = ( ! isset( $_POST['sync'] ) ) ? array() : $_POST['sync'];

			$current_sync_fields = wp_parse_args( $_POST['sync'], self::$_default_fields );

			$current_sync = new BEA_CSF_Synchronization( $current_sync_fields );
		}

		// Get P2P registered connection
		$p2p_registered_connections = array();
		if ( class_exists( 'P2P_Connection_Type_Factory' ) ) {
			$p2p_registered_connections = P2P_Connection_Type_Factory::get_all_instances();
		}

		// Display message
		settings_errors( 'bea-content-sync-fusion' );

		// Include template
		include( BEA_CSF_DIR . 'views/admin/server-page-add.php' );

		return true;
	}

	/**
	 * Display queue on admin
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function render_page_queue() {
		// Include template
		include( BEA_CSF_DIR . 'views/admin/server-page-queue.php' );

		return true;
	}

	/**
	 * Check for update content sync settings
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function admin_init() {
		if ( isset( $_POST['update-bea-csf-adv-settings'] ) ) {
			check_admin_referer( 'update-bea-csf-adv-settings' );

			$option_value = isset( $_POST['csf_adv_settings'] ) ? stripslashes_deep( $_POST['csf_adv_settings'] ) : 0;
			update_site_option( 'csf_adv_settings', $option_value );
			add_settings_error( 'bea-content-sync-fusion', 'settings_updated', __( 'Advanced settings updated with success !', 'bea-content-sync-fusion' ), 'updated' );
		}

		if ( isset( $_POST['update-bea-csf-settings'] ) && isset( $_POST['sync'] ) ) { // Save
			check_admin_referer( 'update-bea-csf-settings' );

			$_POST['sync'] = stripslashes_deep( $_POST['sync'] );

			if ( empty( $_POST['sync']['label'] ) ) {
				add_settings_error( 'bea-content-sync-fusion', 'settings_updated', __( 'You must defined a label.', 'bea-content-sync-fusion' ), 'error' );

				return true;
			}
			if ( empty( $_POST['sync']['emitters'] ) ) {
				add_settings_error( 'bea-content-sync-fusion', 'settings_updated', __( 'You must defined at least one emitter.', 'bea-content-sync-fusion' ), 'error' );

				return true;
			}
			if ( empty( $_POST['sync']['receivers'] ) ) {
				add_settings_error( 'bea-content-sync-fusion', 'settings_updated', __( 'You must defined at least one receiver.', 'bea-content-sync-fusion' ), 'error' );

				return true;
			}

			$current_sync_fields = wp_parse_args( $_POST['sync'], self::$_default_fields );
			$new_sync            = new BEA_CSF_Synchronization( $current_sync_fields );
			if ( isset( $_POST['sync']['id'] ) ) {
				$result = BEA_CSF_Synchronizations::update( $new_sync );
			} else {
				$result = BEA_CSF_Synchronizations::add( $new_sync );
			}

			if ( is_wp_error( $result ) ) {
				add_settings_error( 'bea-content-sync-fusion', 'settings_updated', $result->get_error_message(), 'error' );
			}

			wp_redirect( network_admin_url( 'admin.php?page=' . 'bea-csf-edit&message=merged' ) );
			exit();
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'bea-csf-edit' && isset( $_GET['action'] ) && $_GET['action'] == 'delete' && isset( $_GET['sync_id'] ) ) { // Delete
			check_admin_referer( 'delete-sync' );

			$current_sync = BEA_CSF_Synchronizations::get( array( 'id' => $_GET['sync_id'] ) );
			if ( $current_sync == false ) {
				wp_die( __( 'This synchronization ID not exists. Tcheater ?', 'bea-content-sync-fusion' ) );
			}

			$current_sync = current( $current_sync ); // take first result
			BEA_CSF_Synchronizations::delete( $current_sync );

			wp_redirect( network_admin_url( 'admin.php?page=' . 'bea-csf-edit&message=deleted' ) );
			exit();
		}

		if ( isset( $_POST['delete-bea-csf-file-lock'] ) ) {
			check_admin_referer( 'delete-bea-csf-file-lock' );

			$lock_file = sys_get_temp_dir() . '/bea-content-sync-fusion.lock';
			if ( file_exists( $lock_file ) ) {
				unlink( $lock_file );
			}

			wp_redirect( network_admin_url( 'admin.php?page=' . 'bea-csf-queue&message=deleted' ) );
			exit();
		}

		return true;
	}

	/**
	 * Get sites list formatted for a network ID
	 *
	 * @var int|null $network_id :
	 * - null => no network given, then work on current one
	 * - 0    => work on all networks
	 * - int  => work with the given network id
	 *
	 * @since 3.0.2
	 *
	 * @author Amaury Balmer | Maxime CULEA
	 *
	 * @return array|boolean
	 */
	public static function get_sites_from_network( $network_id = null ) {
		$site_query_args = array(
			'public'   => 1,
			'archived' => 0,
			'mature'   => 0,
			'spam'     => 0,
			'deleted'  => 0,
		);
		if ( is_null( $network_id ) ) {
			$site_query_args['network__in'] = get_current_network_id();
		} elseif ( ! empty( $network_id ) ) {
			$site_query_args['network_id'] = $network_id;
		}

		/**
		 * Filter the query args for getting sites from network.
		 *
		 * @since 3.0.2
		 *
		 * @author Maxime CULEA
		 *
		 * @var array $site_query_args : the query args
		 * @var int|null $network_id : the network id working on
		 */
		$site_query_args = apply_filters( 'bea_csf.admin.admin_synchronization_network.query_args', $site_query_args, $network_id );

		$site_query = new WP_Site_Query( $site_query_args );
		$sites      = $site_query->get_sites();
		if ( empty( $sites ) ) {
			return false;
		}

		$return_sites = array();
		foreach ( $sites as $site ) {
			/* @var $site \WP_Site */
			$return_sites[ $site->blog_id ] = array(
				'network_id' => $site->network_id,
				'blog_id'    => $site->blog_id,
				'domain'     => $site->domain,
				'path'       => $site->path,
			);

			// Set the name : {network_name} {site_name}
			$name = array();
			// Check the query args for network
			if ( isset( $site_query_args['network__in'] ) && empty( $site_query_args['network__in'] ) ) {
				$name[] = get_network_option( $site->network_id, 'site_name' );
			}
			$name[] = get_blog_option( $site->blog_id, 'blogname' );

			$return_sites[ $site->blog_id ]['blogname'] = implode( ' > ', $name );
		}

		// Sort by network id then blog_id
		uasort( $return_sites, function ( $a, $b ) {
			if ( $a['network_id'] == $b ['network_id'] ) {
				return ( $a['blog_id'] < $b ['blog_id'] ) ? - 1 : 1;
			}

			return ( $a['network_id'] < $b ['network_id'] ) ? - 1 : 1;
		} );

		/**
		 * Filter the returned formatted sites.
		 *
		 * @since 3.0.2
		 *
		 * @author Maxime CULEA
		 *
		 * @var array $return_sites : the formatted sites from \WP_Site_Query
		 * @var array $sites : the retrieved sites \WP_Site object from \WP_Site_Query
		 * @var int|null $network_id : the network id working on
		 */
		return apply_filters( 'bea_csf.admin.admin_synchronization_network.sites', $return_sites, $sites, $network_id );
	}

	/**
	 * Helper: Get filtred sites list for a network ID, all data or one field
	 *
	 * @return array|string
	 * @author Amaury Balmer
	 */
	public static function get_sites( $blogs_id = array(), $field = false ) {
		if ( empty( $blogs_id ) ) {
			return '';
		}

		// Get all sites
		$blogs = BEA_CSF_Admin_Synchronizations_Network::get_sites_from_network();

		$filtered = array();
		foreach ( $blogs_id as $blog_id ) {
			if ( $blog_id == 'all' ) {
				$filtered[] = esc_html( 'All, except emitters' );
				continue;
			}

			if ( ! isset( $blogs[ $blog_id ] ) ) {
				continue;
			}

			if ( $field == false ) {
				$filtered[] = $blogs[ $blog_id ];
			} elseif ( isset( $blogs[ $blog_id ][ $field ] ) ) {
				$filtered[] = $blogs[ $blog_id ][ $field ];
			} else {
				continue;
			}
		}

		return $filtered;
	}

}
