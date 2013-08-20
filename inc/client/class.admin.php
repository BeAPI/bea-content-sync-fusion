<?php
class BEA_CSF_Client_Admin {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct( ) {
		global $wpdb;
		
		// Get current options
		$current_options = (array) get_site_option( BEA_CSF_OPTION );
		if ( !isset($current_options['clients']) || !in_array($wpdb->blogid, $current_options['clients']) ) {
			return false;
		}
		
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		
		// Post type
		add_filter( 'post_class', array( __CLASS__, 'post_class' ), 10, 3 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		
		// Taxonomy
		add_filter( 'tag_row_actions', array( __CLASS__, 'tag_row_actions' ), 10, 2 );
	}
	
	public static function admin_enqueue_scripts( $hook_suffix = '' ) {
		if ( isset( $hook_suffix ) && ($hook_suffix == 'edit.php' || $hook_suffix == 'edit-tags.php' ) ) {
			wp_enqueue_script( 'bea-csc-admin-client', BEA_CSF_URL . '/ressources/js/bea-csc-admin-client.js', array( 'jquery' ), BEA_CSF_VERSION, true );
			wp_enqueue_style( 'bea-csc-admin', BEA_CSF_URL . '/ressources/css/bea-css-admin.css', array( ), BEA_CSF_VERSION, 'all' );
		}
	}

	public static function post_class( $classes, $class, $post_ID ) {
		$master_id = get_post_meta( $post_ID, 'master_id', true );
		if ( $master_id != false && (int)$master_id > 0 ) {
			$classes[] = 'locked-content master-' . $master_id;
		}

		return $classes;
	}

	/**
	 * Die if user try to edit content with master ID !
	 */
	public static function admin_init( ) {
		self::check_post_edition();
		self::check_term_edition();
	}
	
	public static function check_post_edition() {
		global $pagenow;

		// Not an edit page ?
		if ( $pagenow != 'post.php' ) {
			return false;
		}

		// No action on edit page ?
		if ( !isset( $_GET['post'] ) || $_GET['action'] != 'edit' ) {
			return false;
		}

		// Get current object with post_id
		$current_object = get_post( $_id = (int)$_GET['post'] );

		// Object not exist ?
		if ( empty( $current_object ) ) {
			return false;
		}

		$master_id = get_post_meta( $current_object->ID, 'master_id', true );
		if ( $master_id != false && (int)$master_id > 0 ) {
			do_action( 'edit_protected_content', $master_id );
			wp_die( __( 'You are not allowed to edit this content. You must update it from your master site.', BEA_CSF_LOCALE ) );
		}

		return true;
	}
	
	public static function tag_row_actions( $actions, $term ) {
		$master_id = get_term_taxonomy_meta( (int) $term->term_taxonomy_id, 'master_id', true );
		if ( $master_id != false && (int)$master_id > 0 ) {
			unset($actions['edit'], $actions['inline hide-if-no-js'], $actions['delete']);
			$actions['view'] .= '<span class="locked-term-parent"></span>';
		}
		
		return $actions;
	}
	
	public static function check_term_edition() {
		global $pagenow;

		// Not an edit page ?
		if ( $pagenow != 'edit-tags.php' ) {
			return false;
		}

		// No action on edit page ?
		if ( !isset( $_GET['taxonomy'] ) || !isset( $_GET['tag_ID'] ) || $_GET['action'] != 'edit' ) {
			return false;
		}

		// Get current object with post_id
		$current_term = get_term( (int) $_GET['tag_ID'], $_GET['taxonomy'] );

		// Object not exist ?
		if ( empty( $current_term ) || is_wp_error($current_term) ) {
			return false;
		}

		$master_id = get_term_taxonomy_meta( $current_term->term_taxonomy_id, 'master_id', true );
		if ( $master_id != false && (int) $master_id > 0 ) {
			wp_die( __( 'You are not allowed to edit this content. You must update it from your master site.', BEA_CSF_LOCALE ) );
		}

		return true;
	}
}
