<?php

class BEA_CSF_Admin_Terms_Metaboxes {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		add_action( 'edit_category_form_fields', array( __CLASS__, 'form' ), 10, 1 );
		add_action( 'edit_link_category_form_fields', array( __CLASS__, 'form' ), 10, 1 );
		add_action( 'edit_tag_form_fields', array( __CLASS__, 'form' ), 10, 1 );

		add_action( 'created_term', array( __CLASS__, 'save' ), 10, 3 );
		add_action( 'edited_term', array( __CLASS__, 'save' ), 10, 3 );
	}

	public static function taxonomy_has_sync( $taxonomy = '' ) {
		global $wpdb;

		// Get syncs for current post_type and any mode
		$syncs = BEA_CSF_Synchronizations::get(
			array(
				'emitters' => $wpdb->blogid,
			),
			'AND',
			false,
			true
		);
		if ( empty( $syncs ) ) {
			return false;
		}

		foreach ( $syncs as $sync ) {
			if ( ! isset( $sync->taxonomies ) ) {
				continue;
			}

			if ( is_string( $sync->taxonomies ) && $taxonomy === $sync->taxonomies ) {
				return $sync;
			}

			if ( is_array( $sync->taxonomies ) && in_array( $taxonomy, $sync->taxonomies ) ) {
				return $sync;
			}
		}

		return false;
	}

	public static function taxonomy_has_manual_sync( $taxonomy = '' ) {
		global $wpdb;

		// Get syncs for current post_type and mode set to "manual"
		$syncs_with_manual_state = BEA_CSF_Synchronizations::get(
			array(
				'mode'     => 'manual',
				'emitters' => $wpdb->blogid,
			),
			'AND',
			false,
			true
		);
		if ( empty( $syncs_with_manual_state ) ) {
			return false;
		}

		// Can allow filter receivers site
		$syncs_with_manual_state = apply_filters( 'bea_csf_syncs_with_manual_state_term', $syncs_with_manual_state );

		foreach ( $syncs_with_manual_state as $sync ) {
			if ( in_array( $taxonomy, $sync->taxonomies ) ) {
				return $sync;
			}
		}

		return false;
	}

	public static function form( $term ) {
		$sync_obj = self::taxonomy_has_manual_sync( $term->taxonomy );
		if ( false === $sync_obj ) {
			return false;
		}

		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), BEA_CSF_OPTION . '-term-nonce-manual' );

		// Get values for current term
		$current_values = (array) get_term_meta( $term->term_id, '_term_receivers', true );

		// Get sites destination from syncs
		$sync_receivers = $sync_obj->get_field( 'receivers' );
		$sync_receivers = BEA_CSF_Admin_Synchronizations_Network::get_sites( $sync_receivers );

		// Include template
		include( BEA_CSF_DIR . 'views/admin/server-term-metabox-manual.php' );
	}

	public static function save( $term_id, $tt_id, $taxonomy ) {
		global $wpdb;

		$term = get_term( $term_id, $taxonomy );
		if ( false === $term || is_wp_error( $term ) ) {
			return false;
		}

		// verify this came from the our screen and with proper authorization,
		// because hook can be triggered at other times
		if ( ! isset( $_POST[ BEA_CSF_OPTION . '-term-nonce-manual' ] ) || ! wp_verify_nonce( $_POST[ BEA_CSF_OPTION . '-term-nonce-manual' ], plugin_basename( __FILE__ ) ) ) {
			return false;
		}

		// Set default value
		$new_term_receivers = array();

		// Get _POST data if form is filled
		if ( isset( $_POST['term_receivers'] ) && ! empty( $_POST['term_receivers'] ) ) {
			$new_term_receivers = array_map( 'intval', $_POST['term_receivers'] );
		}

		// Get previous values
		$old_term_receivers = (array) get_term_meta( $term->term_id, '_term_receivers', true );
		$old_term_receivers = array_filter( $old_term_receivers, 'trim' );

		// Set new value
		update_term_meta( $term->term_id, '_term_receivers', $new_term_receivers );

		// Calcul difference for send delete notification for uncheck action
		$receivers_to_delete = array_diff( $old_term_receivers, $new_term_receivers );

		if ( ! empty( $receivers_to_delete ) && ! empty( $old_term_receivers ) ) {
			// Theses values have just deleted, delete content for clients !
			do_action( 'bea-csf' . '/' . 'Taxonomy' . '/' . 'delete' . '/' . $taxonomy . '/' . $wpdb->blogid, $term, false, $receivers_to_delete, true, true );
		}

		return true;
	}

}
