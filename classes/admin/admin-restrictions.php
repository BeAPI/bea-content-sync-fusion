<?php

class BEA_CSF_Admin_Restrictions {
	private static $capabilities_to_check = array( 'edit_post', 'delete_post', 'delete_page', 'edit_page' );

	/**
	 * Constructor, register hooks
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Get current setting
		$current_settings = get_site_option( 'csf_adv_settings' );
		if ( isset( $current_settings['unlock-mode'] ) && $current_settings['unlock-mode'] == '1' ) {
			return false;
		}

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		// Post row
		add_filter( 'page_row_actions', array( __CLASS__, 'post_row_actions' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'post_row_actions' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init_check_post_publishing' ) );

		// Term row
		add_filter( 'tag_row_actions', array( __CLASS__, 'tag_row_actions' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init_check_term_edition' ) );

		// Play with capabilities
		add_filter( 'map_meta_cap', array( __CLASS__, 'map_meta_cap' ), 10, 4 );

		return true;
	}

	/**
	 * Register JS and CSS for client part
	 *
	 * @param string $hook_suffix
	 */
	public static function admin_enqueue_scripts( $hook_suffix = '' ) {
		if ( isset( $hook_suffix ) && ( $hook_suffix == 'edit.php' || $hook_suffix == 'edit-tags.php' ) ) {
			wp_enqueue_script( 'bea-csf-admin-client', BEA_CSF_URL . 'assets/js/bea-csf-admin-client.js', array( 'jquery' ), BEA_CSF_VERSION, true );
			wp_enqueue_style( 'bea-csf-admin', BEA_CSF_URL . 'assets/css/bea-csf-admin.css', array(), BEA_CSF_VERSION, 'all' );
		}
	}

	/**
	 * Remove some actions on post list when a post have an original key
	 *
	 * @param array $post
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public static function post_row_actions( array $actions, WP_Post $post ) {
		$_origin_key = get_post_meta( $post->ID, '_origin_key', true );
		if ( $_origin_key != false ) {
			if ( $post->post_status == 'pending' ) {
				$actions['view'] = '<a href="' . esc_url( apply_filters( 'preview_post_link', set_url_scheme( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ) ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $post->post_title ) ) . '" rel="permalink">' . __( 'Preview' ) . '</a>';

				if ( current_user_can( 'publish_post', $post->ID ) ) {
					$actions['publish'] = '<a href="' . esc_url( wp_nonce_url( add_query_arg( array(
							'action' => 'bea-csf-publish',
							'ID'     => $post->ID
						) ), 'bea-csf-publish' ) ) . '">Publish</a>';
				}
			}
		}

		return $actions;
	}

	public static function admin_init_check_post_publishing() {
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'bea-csf-publish' && isset( $_GET['ID'] ) && (int) $_GET['ID'] > 0 ) {
			check_admin_referer( 'bea-csf-publish' );

			wp_publish_post( (int) $_GET['ID'] );

			$redirect_url = remove_query_arg( 'action' );
			$redirect_url = remove_query_arg( 'ID', $redirect_url );
			$redirect_url = remove_query_arg( '_wpnonce', $redirect_url );
			wp_redirect( $redirect_url );
			exit();
		}
	}

	/**
	 * Remove some actions on tag list when a post have an original key
	 *
	 * @param array $actions
	 * @param WP_Term $term
	 *
	 * @return array
	 */
	public static function tag_row_actions( array $actions, WP_Term $term ) {
		$_origin_key = get_term_meta( $term->term_id, '_origin_key', true );
		if ( $_origin_key != false ) {
			unset( $actions['edit'], $actions['inline hide-if-no-js'], $actions['delete'] );
			$actions['view'] .= '<span class="locked-term-parent"></span>';
		}

		return $actions;
	}

	/**
	 * Block request for term edition
	 *
	 * @global string $pagenow
	 * @return boolean
	 */
	public static function admin_init_check_term_edition() {
		global $pagenow;

		// Not an edit page ?
		if ( $pagenow != 'edit-tags.php' ) {
			return false;
		}

		// No action on edit page ?
		if ( ! isset( $_GET['taxonomy'] ) || ! isset( $_GET['tag_ID'] ) || $_GET['action'] != 'edit' ) {
			return false;
		}

		// Get current term with tag ID
		$current_term = get_term( (int) $_GET['tag_ID'], $_GET['taxonomy'] );

		// Term not exist ?
		if ( empty( $current_term ) || is_wp_error( $current_term ) ) {
			return false;
		}

		$_origin_key = get_term_meta( $current_term->term_id, '_origin_key', true );
		if ( $_origin_key != false ) {
			wp_die( __( 'You are not allowed to edit this content. You must update it from your master site.', BEA_CSF_LOCALE ) );
		}

		return true;
	}

	/**
	 *  Play with Capabilities API for remove "edit/delete" cap when a post have an original key
	 *
	 * @param array $caps
	 * @param string $cap
	 * @param integer $user_id
	 * @param array $args
	 *
	 * @return array
	 */
	public static function map_meta_cap( $caps, $cap, $user_id, $args ) {
		global $tag;

		$capabilities = apply_filters( 'bea_csf_capabilities', self::$capabilities_to_check );
		if ( ! is_array( $capabilities ) ) {
			return $caps;
		}
		if ( in_array( $cap, $capabilities ) ) {
			$_origin_key = get_post_meta( $args[0], '_origin_key', true );
			if ( $_origin_key != false ) {
				$caps[] = 'do_not_allow';
			}
		}

		return $caps;
	}
}
