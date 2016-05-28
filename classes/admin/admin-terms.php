<?php

class BEA_CSF_Admin_Terms {

	/**
	 * Constructor
	 *
	 * @author Amaury Balmer
	 */
	public function __construct() {
		add_action( 'edit_category_form_fields', array( __CLASS__, 'form' ), 10, 1 );
		add_action( 'edit_link_category_form_fields', array( __CLASS__, 'form' ), 10, 1 );
		add_action( 'edit_tag_form_fields', array( __CLASS__, 'form' ), 10, 1 );

		add_action( 'created_term', array( __CLASS__, 'save' ), 10, 3 );
		add_action( 'edited_term', array( __CLASS__, 'save' ), 10, 3 );
	}

	public static function form( $term ) {
		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'bea-csf-admin-terms' );

		// Get origin key
		$_origin_key = get_term_taxonomy_meta( $term->term_taxonomy_id, '_origin_key', true );
		if ( empty( $_origin_key ) ) {
			return;
		}

		// SPLIT data
		$_origin_key = explode( ':', $_origin_key );

		// Set variables
		$_origin_blog_id          = $_origin_key[0];
		$_origin_term_taxonomy_id = $_origin_key[1];
		$_origin_taxonomy         = $term->taxonomy;

		// Test if term id is valid
		if ( self::is_valid_term_taxonomy_id( $_origin_term_taxonomy_id, $_origin_taxonomy, $_origin_blog_id ) == false ) {
			$_origin_term_taxonomy_id = 0;
		}

		// Include template
		include( BEA_CSF_DIR . 'views/admin/client-terms-form.php' );
	}

	public static function save( $term_id, $tt_id, $taxonomy ) {
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST['bea-csf-admin-terms'] ) || ! wp_verify_nonce( $_POST['bea-csf-admin-terms'], plugin_basename( __FILE__ ) ) ) {
			return;
		}

		if ( isset( $_POST['term_emitter'] ) &&
		     isset( $_POST['term_emitter']['blog_id'] ) && intval( $_POST['term_emitter']['blog_id'] ) > 0 &&
		     isset( $_POST['term_emitter']['term_taxonomy_id'] ) && intval( $_POST['term_emitter']['term_taxonomy_id'] ) > 0
		) {

			update_term_taxonomy_meta( $tt_id, '_origin_key', $_POST['term_emitter']['blog_id'] . ':' . $_POST['term_emitter']['term_taxonomy_id'] );
		}

	}

	public static function is_valid_term_taxonomy_id( $term_taxonomy_id = 0, $taxonomy = '', $blog_id = 0 ) {
		if ( self::is_valid_blog_id( $blog_id ) ) {
			switch_to_blog( $blog_id );
			$term_id     = (int) get_term_id_from_term_taxonomy_id( $taxonomy, $term_taxonomy_id );
			$term_exists = get_term( (int) $term_id, $taxonomy );
			$term_exists = ( empty( $term_exists ) || is_wp_error( $term_exists ) ) ? false : true;
			restore_current_blog();

			return $term_exists;
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
