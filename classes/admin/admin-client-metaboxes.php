<?php

class BEA_CSF_Admin_Client_Metaboxes {

	/**
	 * Constructor
	 *
	 * @author Amaury Balmer
	 */
	public function __construct() {
		add_action( 'save_post', array( __CLASS__, 'save_post' ), 10, 1 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ), 10, 2 );
	}

	/**
	 * @param integer $post_id
	 *
	 * @return bool
	 */
	public static function save_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST[ BEA_CSF_OPTION . '-nonce-client' ] ) || ! wp_verify_nonce( $_POST[ BEA_CSF_OPTION . '-nonce-client' ], plugin_basename( __FILE__ ) ) ) {
			return false;
		}

		/* OK, it's safe for us to save the data now. */

		if ( isset( $_POST['exclude_from_futur_sync'] ) && (int) $_POST['exclude_from_futur_sync'] == 1 ) {
			update_post_meta( $post_id, '_exclude_from_futur_sync', 1 );
		} else {
			delete_post_meta( $post_id, '_exclude_from_futur_sync' );
		}

		return true;
	}

	/**
	 * Adds the meta box container in edit post / page
	 *
	 * @param string $post_type
	 * @param WP_Post $post
	 *
	 * @return bool
	 * @author Amaury Balmer
	 */
	public static function add_meta_boxes( $post_type, $post ) {
		global $wpdb;

		// Get emitter for current post
		$emitter_relation = BEA_CSF_Relations::current_object_is_synchronized( 'posttype', $wpdb->blogid, $post->ID );
		if ( ! empty( $emitter_relation ) ) {
			add_meta_box( BEA_CSF_OPTION . 'metabox-client', __( 'Synchronization', 'bea-content-sync-fusion' ), array(
				__CLASS__,
				'metabox_content',
			), $post_type, 'side', 'low', array( 'relation' => $emitter_relation ) );
		}

		return true;
	}

	/**
	 * Form for allow exclusion for synchronization !
	 *
	 * @param WP_Post $post
	 * @param array $metabox
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function metabox_content( $post, $metabox ) {
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), BEA_CSF_OPTION . '-nonce-client' );

		// Get values for current post
		$current_value = (int) get_post_meta( $post->ID, '_exclude_from_futur_sync', true );

		// Get emitter data
		switch_to_blog( $metabox['args']['relation']->emitter_blog_id );
		$emitter_data = array(
			'blog_name'  => get_bloginfo( 'name' ),
			'post_title' => get_the_title( $metabox['args']['relation']->emitter_id )
		);
		restore_current_blog();

		$current_receivers_note = get_post_meta( $post->ID, '_post_receivers_note', true );

		// Include template
		include( BEA_CSF_DIR . 'views/admin/client-metabox.php' );
	}

}
