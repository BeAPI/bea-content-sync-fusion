<?php
class BEA_CSF_Server_Admin_Metabox {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct( ) {
		add_action( 'transition_post_status', array( __CLASS__, 'transition_post_status' ), 10, 3 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 10, 2 );
	}

	public static function transition_post_status( $new_status, $old_status, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}
		
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( !isset($_POST[BEA_CSF_OPTION.'-nonce']) || !wp_verify_nonce( $_POST[BEA_CSF_OPTION.'-nonce'], plugin_basename( __FILE__ ) ) ) {
			return false;
		}
		
		// Right post type ?
		if ( !in_array( $post->post_type, BEA_CSF_Server_Client::get_post_types() ) ) {
			return false;
		}
		
		$previous_value = (int) get_post_meta( $post->ID, 'exclude_from_sync', true );
		if ( isset($_POST['exclude_from_sync']) && (int) $_POST['exclude_from_sync'] == 1 ) {
			update_post_meta( $post->ID, 'exclude_from_sync', 1 );
			if ( $previous_value == 0 ) { // This value have just changed, delete content for clients !
				BEA_CSF_Server_PostType::delete_post( $post->ID );
			}
		} else {
			delete_post_meta( $post->ID, 'exclude_from_sync' );
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
		// Add metabox in admin if redirect exist
		foreach ( BEA_CSF_Server_Client::get_post_types() as $post_type ) {
			add_meta_box( BEA_CSF_OPTION.'metabox', __( 'Synchronization', BEA_CSF_LOCALE), array( __CLASS__, 'metabox_content' ), $post_type, 'side', 'low' );
		}

		return true;
	}

	/**
	 * Form for allow exclusion for synchronization !
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function metabox_content( $post ) {
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), BEA_CSF_OPTION.'-nonce' );
		
		$previous_value = (int) get_post_meta( $post->ID, 'exclude_from_sync', true );
		
		// Checkbox
		echo '<input type="checkbox" id="exclude_from_sync" name="exclude_from_sync" value="1" '.checked($previous_value, 1, false).' />';
		echo '<label for="exclude_from_sync"> ';
			_e( "Exclude this content from synchronization", BEA_CSF_LOCALE );
		echo '</label>';
	}
}
