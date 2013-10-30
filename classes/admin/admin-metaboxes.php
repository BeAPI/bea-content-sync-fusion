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

		self::check_changes_auto_metabox( $post );
		self::check_changes_manual_metabox( $post );

		return true;
	}

	public static function check_changes_auto_metabox( $post ) {
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !isset( $_POST[BEA_CSF_OPTION . '-nonce-auto'] ) || !wp_verify_nonce( $_POST[BEA_CSF_OPTION . '-nonce-auto'], plugin_basename( __FILE__ ) ) ) {
			return false;
		}

		$previous_value = (int) get_post_meta( $post->ID, '_exclude_from_sync', true );
		if ( isset( $_POST['exclude_from_sync'] ) && (int) $_POST['exclude_from_sync'] == 1 ) {
			update_post_meta( $post->ID, '_exclude_from_sync', 1 );
			if ( $previous_value == 0 ) { // This value have just changed, delete content for clients !
				// BEA_CSF_Server_PostType::delete_post( $post->ID );
			}
		} else {
			delete_post_meta( $post->ID, '_exclude_from_sync' );
		}

		return true;
	}

	public static function check_changes_manual_metabox( $post ) {
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !isset( $_POST[BEA_CSF_OPTION . '-nonce-manual'] ) || !wp_verify_nonce( $_POST[BEA_CSF_OPTION . '-nonce-manual'], plugin_basename( __FILE__ ) ) ) {
			return false;
		}

		$previous_value = (array) get_post_meta( $post->ID, '_post_receivers', true );
		if ( isset( $_POST['post_receivers'] ) && !empty( $_POST['post_receivers'] ) ) {
			$_POST['post_receivers'] = array_map( 'intval', $_POST['post_receivers'] );
			update_post_meta( $post->ID, '_post_receivers', $_POST['post_receivers'] );
			
			$deleted_receivers = array_diff($previous_value, $_POST['post_receivers']);
			if ( !empty($deleted_receivers) ) { // Theses values have just deleted, delete content for clients !
				// TODO Make loop
				// BEA_CSF_Server_PostType::delete_post( $post->ID );
			}
		} else {
			delete_post_meta( $post->ID, '_post_receivers' );
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
		// Get syncs for current post_type and mode set to "auto"
		$syncs_with_auto_state = BEA_CSF_Synchronizations::get( array( 'post_type' => $post_type, 'mode' => 'auto' ) );
		if ( !empty( $syncs_with_auto_state ) ) {
			add_meta_box( BEA_CSF_OPTION . 'metabox-auto', __( 'Synchronization (auto)', BEA_CSF_LOCALE ), array( __CLASS__, 'metabox_content_auto' ), $post_type, 'side', 'low', array( 'syncs' => $syncs_with_auto_state ) );
		}

		// Get syncs for current post_type and mode set to "manual"
		$syncs_with_manual_state = BEA_CSF_Synchronizations::get( array( 'post_type' => $post_type, 'mode' => 'manual' ) );
		if ( !empty( $syncs_with_manual_state ) ) {
			add_meta_box( BEA_CSF_OPTION . 'metabox-manual', __( 'Synchronization (manual)', BEA_CSF_LOCALE ), array( __CLASS__, 'metabox_content_manual' ), $post_type, 'side', 'low', array( 'syncs' => $syncs_with_manual_state ) );
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
		$sync_names = array( );
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
		$sync_receivers = array( );
		foreach ( $metabox['args']['syncs'] as $sync_obj ) {
			$sync_receivers = array_merge( $sync_receivers, $sync_obj->get_field( 'receivers' ) );
		}
		$sync_receivers = BEA_CSF_Admin_Synchronizations_Network::get_sites( $sync_receivers );

		// Get names from syncs
		$sync_names = array( );
		foreach ( $metabox['args']['syncs'] as $sync_obj ) {
			$sync_names[] = $sync_obj->get_field( 'label' );
		}

		// Include template
		include( BEA_CSF_DIR . 'views/admin/server-metabox-manual.php' );
	}

}
