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

		/** Attachment **/
		/** ----- Edit Attachment --- **/
		add_action( 'add_meta_boxes_attachment', array( __CLASS__, 'add_meta_boxes_attachment' ), 10 );
		/** ----- Modal Attachment --- **/
		add_filter( 'attachment_fields_to_edit', array( __CLASS__, 'custom_fields_attachment' ), PHP_INT_MAX, 2 );
		/** ----- Save data attachment --- **/
		add_filter( 'attachment_fields_to_save', array( __CLASS__, 'attachment_fields_to_save' ), PHP_INT_MAX, 2 );
	}

	/**
	 * @param $new_status
	 * @param $old_status
	 * @param $post
	 *
	 * @return bool
	 */
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
		self::check_changes_manual_metabox_status( $post );

		return true;
	}

	/**
	 * @param $post
	 *
	 * @return bool
	 */
	public static function check_changes_auto_metabox( $post ) {
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST[ BEA_CSF_OPTION . '-nonce-auto' ] ) || ! wp_verify_nonce( $_POST[ BEA_CSF_OPTION . '-nonce-auto' ], plugin_basename( __FILE__ ) ) ) {
			return false;
		}

		// Allow to edit current post id for revisionary
		$_post_id           = apply_filters( 'bea/csf/save_post_id', $post->ID );
		$is_saving_revision = apply_filters( 'bea/csf/is_save_revision', false, $post->ID );

		// Update receivers note
		if ( isset( $_POST['post_receivers_note'] ) ) {
			update_post_meta( $_post_id, '_post_receivers_note', wp_unslash( $_POST['post_receivers_note'] ) );
		}

		$previous_value = (int) get_post_meta( $_post_id, '_exclude_from_sync', true );
		if ( isset( $_POST['exclude_from_sync'] ) && 1 === (int) $_POST['exclude_from_sync'] ) {
			update_post_meta( $_post_id, '_exclude_from_sync', 1 );
			if ( 0 === $previous_value && false === $is_saving_revision ) {
				// This value have just changed, delete content for clients !
				do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $post->post_type . '/' . get_current_blog_id(), $post, false, false, false, true );
			}
		} else {
			delete_post_meta( $_post_id, '_exclude_from_sync' );
		}

		return true;
	}

	/**
	 * @param $post
	 *
	 * @return bool
	 */
	public static function check_changes_manual_metabox( $post ) {
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST[ BEA_CSF_OPTION . '-nonce-manual' ] ) || ! wp_verify_nonce( $_POST[ BEA_CSF_OPTION . '-nonce-manual' ], plugin_basename( __FILE__ ) ) ) {
			return false;
		}

		// Allow to edit current post id for revisionary
		$_post_id           = apply_filters( 'bea/csf/save_post_id', $post->ID );
		$is_saving_revision = apply_filters( 'bea/csf/is_save_revision', false, $post->ID );

		// Update receivers note
		if ( isset( $_POST['post_receivers_note'] ) ) {
			update_post_meta( $_post_id, '_post_receivers_note', wp_unslash( $_POST['post_receivers_note'] ) );
		}

		// Update receivers features (checkbox)
		$new_post_receivers = array();
		if ( isset( $_POST['post_receivers'] ) && ! empty( $_POST['post_receivers'] ) ) {
			$new_post_receivers = array_map( 'intval', $_POST['post_receivers'] );
		}

		// Get previous values
		$old_post_receivers = (array) get_post_meta( $_post_id, '_b' . get_current_blog_id() . '_post_receivers', true );
		$old_post_receivers = array_filter( $old_post_receivers, 'trim' );

		// Set new value
		update_post_meta( $_post_id, '_b' . get_current_blog_id() . '_post_receivers', $new_post_receivers );

		// Calcul difference for send delete notification for uncheck action
		$receivers_to_delete = array_diff( $old_post_receivers, $new_post_receivers );

		if ( ! empty( $receivers_to_delete ) && ! empty( $old_post_receivers ) && false === $is_saving_revision) {
			// Theses values have just deleted, delete content for clients !
			do_action( 'bea-csf' . '/' . 'PostType' . '/' . 'delete' . '/' . $post->post_type . '/' . get_current_blog_id(), $post, false, $receivers_to_delete, true, true );
		}

		return true;
	}

	/**
	 * Update receivers status (select)
	 *
	 * @param $post
	 *
	 * @return bool
	 */
	public static function check_changes_manual_metabox_status( $post ) {
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST[ BEA_CSF_OPTION . '-nonce-manual-status' ] ) || ! wp_verify_nonce( $_POST[ BEA_CSF_OPTION . '-nonce-manual-status' ], plugin_basename( __FILE__ ) ) ) {
			return false;
		}

		// Allow to edit current post id for revisionary
		$_post_id           = apply_filters( 'bea/csf/save_post_id', $post->ID );

		$post_receivers_status = array();
		if ( isset( $_POST['post_receivers_status'] ) && ! empty( $_POST['post_receivers_status'] ) ) {
			$post_receivers_status = array_map( 'trim', $_POST['post_receivers_status'] );
		}

		update_post_meta( $_post_id, '_b' . get_current_blog_id() . '_post_receivers_status', $post_receivers_status );

		return true;
	}

	/**
	 * Adds the meta box container in edit post / page
	 *
	 * @param $post_type
	 * @param $post
	 *
	 * @return boolean
	 * @author Amaury Balmer
	 */
	public static function add_meta_boxes( $post_type, $post ) {
		// Is synchronized content and media ? => not display metabox
		$emitter_relation = BEA_CSF_Relations::current_object_is_synchronized(
			array(
				'posttype',
				'attachment',
			),
			get_current_blog_id(),
			$post->ID
		);

		if ( ! empty( $emitter_relation ) && 'attachment' === $post_type ) {
			return false;
		}

		// Get syncs for current post_type and mode set to "auto"
		$syncs_with_auto_state = BEA_CSF_Synchronizations::get(
			array(
				'post_type' => $post_type,
				'mode'      => 'auto',
				'emitters'  => get_current_blog_id(),
			),
			'AND',
			false,
			true
		);
		if ( ! empty( $syncs_with_auto_state ) ) {
			add_meta_box(
				BEA_CSF_OPTION . 'metabox-auto',
				__( 'Synchronization (auto)', 'bea-content-sync-fusion' ),
				array(
					__CLASS__,
					'metabox_content_auto',
				),
				$post_type,
				'side',
				'low',
				array( 'syncs' => $syncs_with_auto_state )
			);
		}

		// Get syncs for current post_type and mode set to "manual"
		$syncs_with_manual_state = BEA_CSF_Synchronizations::get(
			array(
				'post_type' => $post_type,
				'mode'      => 'manual',
				'emitters'  => get_current_blog_id(),
			),
			'AND',
			false,
			true
		);
		if ( ! empty( $syncs_with_manual_state ) ) {
			// Can allow filter receivers site
			$syncs_with_manual_state = apply_filters( 'bea_csf_syncs_with_manual_state_post', $syncs_with_manual_state );

			add_meta_box(
				BEA_CSF_OPTION . 'metabox-manual',
				__( 'Synchronization (manual)', 'bea-content-sync-fusion' ),
				array(
					__CLASS__,
					'metabox_content_manual',
				),
				$post_type,
				'side',
				'low',
				array( 'syncs' => $syncs_with_manual_state )
			);
		}

		return true;
	}

	/**
	 * Form for allow exclusion for synchronization !
	 *
	 * @param $post
	 * @param $metabox
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function metabox_content_auto( $post, $metabox ) {
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), BEA_CSF_OPTION . '-nonce-auto' );

		$current_receivers_note = get_post_meta( $post->ID, '_post_receivers_note', true );
		$current_value          = (int) get_post_meta( $post->ID, '_exclude_from_sync', true );

		// Get names from syncs
		$sync_names = array();
		foreach ( $metabox['args']['syncs'] as $sync_obj ) {
			$sync_names[] = $sync_obj->get_field( 'label' );
		}

		// Include template
		include BEA_CSF_DIR . 'views/admin/server-metabox-auto.php';
	}

	/**
	 * Form for custom sync, choose receivers !
	 *
	 * @param $post
	 * @param $metabox
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function metabox_content_manual( $post, $metabox ) {
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), BEA_CSF_OPTION . '-nonce-manual' );

		$current_receivers_note = get_post_meta( $post->ID, '_post_receivers_note', true );

		// Get values for current post
		$current_post_receivers        = (array) get_post_meta( $post->ID, '_b' . get_current_blog_id() . '_post_receivers', true );
		$current_post_receivers_status = (array) get_post_meta( $post->ID, '_b' . get_current_blog_id() . '_post_receivers_status', true );

		// Get sites destination from syncs
		$sync_receivers = array();
		foreach ( $metabox['args']['syncs'] as $sync_obj ) {
			$sync_receivers = array_merge( $sync_receivers, $sync_obj->get_field( 'receivers' ) );
		}
		$sync_receivers = BEA_CSF_Admin_Synchronizations_Network::get_sites( $sync_receivers );

		// Get names from syncs, and also enable a flag for user_selection status
		$show_blog_status = false;
		$sync_names       = array();
		foreach ( $metabox['args']['syncs'] as $sync_obj ) {
			$sync_names[] = $sync_obj->get_field( 'label' );

			if ( $sync_obj->get_field( 'status' ) === 'user_selection' ) {
				$show_blog_status = true;
			}
		}

		if ( true === $show_blog_status ) {
			wp_nonce_field( plugin_basename( __FILE__ ), BEA_CSF_OPTION . '-nonce-manual-status' );
		}

		// Include template
		include BEA_CSF_DIR . 'views/admin/server-metabox-manual.php';
	}

	/**
	 * @param int $post_id
	 * @param int $blog_id
	 *
	 * @return array|bool|null|WP_Post
	 */
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

	/**
	 * @param int $blog_id
	 *
	 * @return bool
	 */
	public static function is_valid_blog_id( $blog_id = 0 ) {
		$sites_id = BEA_CSF_Synchronizations::get_sites_from_network();
		foreach ( $sites_id as $site ) {
			if ( (int) $site['blog_id'] === (int) $blog_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add meta boxes for the attachments editing
	 *
	 * @param $post
	 *
	 * @return bool
	 * @author Egidio CORICA
	 */
	public static function add_meta_boxes_attachment( $post ) {
		$emitter_relation = BEA_CSF_Relations::current_object_is_synchronized(
			array(
				'attachment',
			),
			get_current_blog_id(),
			$post->ID
		);

		if ( ! empty( $emitter_relation ) ) {
			return false;
		}

		// Get syncs for current post_type and mode set to "auto"
		$syncs_with_auto_state = BEA_CSF_Synchronizations::get(
			array(
				'post_type' => $post->post_type,
				'mode'      => 'exclude_default',
				'emitters'  => get_current_blog_id(),
			),
			'AND',
			false,
			true
		);

		if ( ! empty( $syncs_with_auto_state ) ) {
			add_meta_box(
				'meta-box-id',
				__( 'Include in the sync', 'bea-content-sync-fusion' ),
				array( __CLASS__, 'metabox_content_include' ),
				'attachment',
				'side',
				'low',
				array( 'syncs' => $syncs_with_auto_state )
			);
		}

		return true;
	}

	public static function metabox_content_include( $post, $metabox ) {
		// Get values for current post
		$include_attachments = (int) get_post_meta( $post->ID, '_include_from_sync', true );

		// Get names from syncs
		$sync_names = array();
		foreach ( $metabox['args']['syncs'] as $sync_obj ) {
			$sync_names[] = $sync_obj->get_field( 'label' );
		}

		// Include template
		include BEA_CSF_DIR . 'views/admin/server-metabox-attachment.php';
	}

	/**
	 *
	 * Add field for the attachments modal
	 *
	 * @param $form_fields
	 * @param $attachment
	 *
	 * @return array|false|mixed
	 * @author Egidio CORICA
	 */
	public static function custom_fields_attachment( $form_fields, $attachment ) {
		$emitter_relation = BEA_CSF_Relations::current_object_is_synchronized(
			array(
				'attachment',
			),
			get_current_blog_id(),
			$attachment->ID
		);

		if ( ! empty( $emitter_relation ) ) {
			return $form_fields;
		}

		// Get syncs for current post_type and mode set to "auto"
		$syncs_with_auto_state = BEA_CSF_Synchronizations::get(
			array(
				'post_type' => $attachment->post_type,
				'mode'      => 'exclude_default',
				'emitters'  => get_current_blog_id(),
			),
			'AND',
			false,
			true
		);

		if ( ! empty( $syncs_with_auto_state ) ) {
			return self::attachment_fields_to_edit( $form_fields, $attachment, $syncs_with_auto_state );
		}

		return $form_fields;
	}

	/**
	 * Add the checkbox field to the right media to include it in the synchronization
	 *
	 *
	 * @param array $form_fields
	 * @param WP_Post $attachment
	 *
	 * @return array mixed
	 * @author Egidio CORICA
	 */
	public static function attachment_fields_to_edit( $form_fields, $attachment, $syncs ) {
		// Get values for current post
		$include_attachments = (int) get_post_meta( $attachment->ID, '_include_from_sync', true );

		$form_fields['include_sync'] = array(
			'show_in_edit'  => false,
			'show_in_modal' => true,
			'label'         => __( 'Include in the sync', 'bea-content-sync-fusion' ),
			'input'         => 'html',
			'html'          => sprintf( '<input type="checkbox" id="include-from-sync-%s" name="include_from_sync" value="1" %s><input type="hidden" name="mode" value="exclude_default"/>', $attachment->ID, 1 === $include_attachments ? 'checked' : '' ),
			'value'         => $include_attachments,
		);

		// return the modified images
		return $form_fields;
	}

	/**
	 * Save the data if needed for the media
	 *
	 * @param $_attachment
	 * @param array $data
	 *
	 * @return array
	 * @author Egidio CORICA
	 */
	public static function attachment_fields_to_save( $_attachment, $data ) {
		if ( 'attachment' !== $_attachment['post_type'] ) {
			return $_attachment;
		}

		$_attachment_id = apply_filters( 'bea/csf/save_attachment_id', $_attachment['ID'] );

		if ( empty( $_attachment_id ) ) {
			return $_attachment;
		}

		$_attachment_object = get_post( $_attachment_id );

		if ( empty( $_attachment_object ) ) {
			return $_attachment;
		}

		$previous_value = (int) get_post_meta( $_attachment_id, '_include_from_sync', true );

		if ( 1 === $previous_value ) {
			$_attachment['mode']              = 'exclude_default';
			$_attachment['include_from_sync'] = $previous_value;
		}

		if ( isset( $_POST['include_from_sync'] ) && 1 === (int) $_POST['include_from_sync'] ) {
			update_post_meta( $_attachment_id, '_include_from_sync', 1 );
		} else {
			delete_post_meta( $_attachment_id, '_include_from_sync' );
			if ( 1 === $previous_value ) {
				do_action( 'bea-csf' . '/' . 'Attachment' . '/' . 'delete' . '/attachment/' . get_current_blog_id(), $_attachment_object, false, false, false, true );
			}
		}

		add_filter( 'attachment_fields_to_save', array( __CLASS__, 'attachment_fields_to_save' ), PHP_INT_MAX, 2 );

		// return the modified images
		return $_attachment;
	}
}
