<?php

class BEA_CSF_Admin_Metaboxes {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		add_action( 'transition_post_status', array( __CLASS__, 'transition_post_status' ), 10, 3 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 10, 2 );
	}

	public static function transition_post_status( $new_status, $old_status, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		// Go out if post is revision
		if ( 'revision' === $post->post_type ) {
			return false;
		}

		self::check_changes_auto_metabox( $post );
		self::check_changes_manual_metabox( $post );

		return true;
	}

	public static function check_changes_auto_metabox( $post ) {
		global $wpdb;

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST[ BEA_CSF_OPTION . '-nonce-auto' ] ) || ! wp_verify_nonce( $_POST[ BEA_CSF_OPTION . '-nonce-auto' ], plugin_basename( __FILE__ ) ) ) {
			return false;
		}

		$previous_value = (int) get_post_meta( $post->ID, '_exclude_from_sync', true );
		if ( isset( $_POST['exclude_from_sync'] ) && (int) $_POST['exclude_from_sync'] == 1 ) {
			update_post_meta( $post->ID, '_exclude_from_sync', 1 );
			if ( 0 == $previous_value ) {
				// This value have just changed, delete content for clients !
				do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $post->post_type . '/' . $wpdb->blogid, $post, false, false, false );
			}
		} else {
			delete_post_meta( $post->ID, '_exclude_from_sync' );
		}

		return true;
	}

	public static function check_changes_manual_metabox( $post ) {
		global $wpdb;

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST[ BEA_CSF_OPTION . '-nonce-manual' ] ) || ! wp_verify_nonce( $_POST[ BEA_CSF_OPTION . '-nonce-manual' ], plugin_basename( __FILE__ ) ) ) {
			return false;
		}

		// Set default value
		$new_post_receivers = array();

		// Get _POST data if form is filled
		if ( isset( $_POST['post_receivers'] ) && ! empty( $_POST['post_receivers'] ) ) {
			$new_post_receivers = array_map( 'intval', $_POST['post_receivers'] );
		}

		// Get previous values
		$old_post_receivers = (array) get_post_meta( $post->ID, '_post_receivers', true );
		$old_post_receivers = array_filter( $old_post_receivers, 'trim' );

		// Set new value
		update_post_meta( $post->ID, '_post_receivers', $new_post_receivers );

		// Calcul difference for send delete notification for uncheck action
		$receivers_to_delete = array_diff( $old_post_receivers, $new_post_receivers );

		if ( ! empty( $receivers_to_delete ) && ! empty( $old_post_receivers ) ) {
			// Theses values have just deleted, delete content for clients !
			do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $post->post_type . '/' . $wpdb->blogid, $post, false, $receivers_to_delete, true );
		}

		return true;
	}

	/**
	 * Adds the meta box container in edit post / page
	 *
	 * @return boolean
	 * @author Amaury Balmer
	 */
	public static function add_meta_boxes( $post_type, $post ) {
		global $wpdb;

		// Get syncs for current post_type and mode set to "auto"
		$syncs_with_auto_state = BEA_CSF_Synchronizations::get( array(
			'post_type' => $post_type,
			'mode'      => 'auto',
			'emitters'  => $wpdb->blogid,
		), 'AND', false, true );
		if ( ! empty( $syncs_with_auto_state ) ) {
			add_meta_box( BEA_CSF_OPTION . 'metabox-auto', __( 'Synchronization (auto)', 'bea-content-sync-fusion' ), array(
				__CLASS__,
				'metabox_content_auto',
			), $post_type, 'side', 'low', array( 'syncs' => $syncs_with_auto_state ) );
		}

		// Get syncs for current post_type and mode set to "manual"
		$syncs_with_manual_state = BEA_CSF_Synchronizations::get( array(
			'post_type' => $post_type,
			'mode'      => 'manual',
			'emitters'  => $wpdb->blogid,
		), 'AND', false, true );
		if ( ! empty( $syncs_with_manual_state ) ) {
			add_meta_box( BEA_CSF_OPTION . 'metabox-manual', __( 'Synchronization (manual)', 'bea-content-sync-fusion' ), array(
				__CLASS__,
				'metabox_content_manual',
			), $post_type, 'side', 'low', array( 'syncs' => $syncs_with_manual_state ) );
		}

		return true;
	}

	/**
	 * Form for allow exclusion for synchronization !
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function metabox_content_auto( $post, $metabox ) {
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), BEA_CSF_OPTION . '-nonce-auto' );

		// Get values for current post
		$current_value = (int) get_post_meta( $post->ID, '_exclude_from_sync', true );

		// Get names from syncs
		$sync_names = array();
		foreach ( $metabox['args']['syncs'] as $sync_obj ) {
			$sync_names[] = $sync_obj->get_field( 'label' );
		}

		// Include template
		include( BEA_CSF_DIR . 'views/admin/server-metabox-auto.php' );
	}

	/**
	 * Form for custom sync, choose receivers !
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function metabox_content_manual( $post, $metabox ) {
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), BEA_CSF_OPTION . '-nonce-manual' );

		// Get values for current post
		$current_values = (array) get_post_meta( $post->ID, '_post_receivers', true );

		// Get sites destination from syncs
		$sync_receivers = array();
		foreach ( $metabox['args']['syncs'] as $sync_obj ) {
			$sync_receivers = array_merge( $sync_receivers, $sync_obj->get_field( 'receivers' ) );
		}
		$sync_receivers = BEA_CSF_Admin_Synchronizations_Network::get_sites( $sync_receivers );

		// Get names from syncs
		$sync_names = array();
		foreach ( $metabox['args']['syncs'] as $sync_obj ) {
			$sync_names[] = $sync_obj->get_field( 'label' );
		}

		// Include template
		include( BEA_CSF_DIR . 'views/admin/server-metabox-manual.php' );
	}

	public static function is_valid_post_id( $post_id = 0, $blog_id = 0 ) {
		if ( self::is_valid_blog_id( $blog_id ) ) {
			switch_to_blog( $blog_id );
			$post_exists = get_post( (int) $post_id );
			$post_exists = ( empty( $post_exists ) || is_wp_error( $post_exists ) ) ? false : true;
			restore_current_blog();

			return $post_exists;
		}

		return false;
	}

	public static function is_valid_blog_id( $blog_id = 0 ) {
		$blogs_id = BEA_CSF_Admin_Synchronizations_Network::get_sites_from_network();
		foreach ( BEA_CSF_Admin_Synchronizations_Network::get_sites_from_network() as $site ) {
			if ( $site['blog_id'] == $blog_id ) {
				return true;
			}
		}

		return false;
	}

}
