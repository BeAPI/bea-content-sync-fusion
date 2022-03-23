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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );

		// Get current setting
		$current_settings = get_network_option( BEA_CSF_Synchronizations::get_option_network_id(), 'csf_adv_settings' );
		if ( isset( $current_settings['unlock-mode'] ) && '1' === $current_settings['unlock-mode'] ) {
			return;
		}

		// Post row
		add_filter( 'page_row_actions', array( __CLASS__, 'post_row_actions' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'post_row_actions' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init_check_post_publishing' ) );

		// Term row
		add_filter( 'tag_row_actions', array( __CLASS__, 'tag_row_actions' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init_check_term_edition' ) );

		// Play with capabilities
		add_filter( 'map_meta_cap', array( __CLASS__, 'map_meta_cap' ), 10, 4 );
	}

	/**
	 * Register JS and CSS for client part
	 *
	 * @param string $hook_suffix
	 */
	public static function admin_enqueue_scripts( $hook_suffix = '' ) {
		if ( isset( $hook_suffix ) && in_array(
			$hook_suffix,
			array(
				'edit.php',
				'edit-tags.php',
				'post.php',
			),
			true
		) ) {
			wp_enqueue_script( 'bea-csf-admin-client', BEA_CSF_URL . 'assets/js/bea-csf-admin-client.js', array( 'jquery' ), BEA_CSF_VERSION, true );
			wp_enqueue_style( 'bea-csf-admin', BEA_CSF_URL . 'assets/css/bea-csf-admin.css', array(), BEA_CSF_VERSION, 'all' );
		}
	}

	/**
	 * Remove some actions on post list when a post have an original key
	 *
	 * @param array $actions
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public static function post_row_actions( array $actions, WP_Post $post ) {
		global $wpdb;

		$_origin_key = BEA_CSF_Relations::current_object_is_synchronized(
			array(
				'posttype',
				'attachment',
			),
			$wpdb->blogid,
			$post->ID
		);

		// Get syncs model for current post_type, with any mode status (manual and auto)
		$_has_syncs = BEA_CSF_Synchronizations::get(
			array(
				'post_type' => $post->post_type,
				'emitters'  => $wpdb->blogid,
			),
			'AND',
			false,
			true
		);

		if ( null !== $_origin_key && empty( $_has_syncs ) ) {
			if ( 'pending' === $post->post_status ) {
				$actions['view'] = '<a href="' . esc_url( apply_filters( 'preview_post_link', set_url_scheme( add_query_arg( 'preview', 'true', get_permalink( $post->ID ) ) ) ) ) . '" title="' . esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $post->post_title ) ) . '" rel="permalink">' . __( 'Preview' ) . '</a>';

				if ( current_user_can( 'publish_post', $post->ID ) ) {
					$actions['publish'] = 	sprintf(
						'<a href="%s">%s</a>',
						wp_nonce_url(
							add_query_arg(
								array(
									'action' => 'bea-csf-publish',
									'ID'     => $post->ID,
								)
							),
							'bea-csf-publish'
						),
						__( 'Publish', 'bea-content-sync-fusion' )
					);
				}
			}
		}

		return $actions;
	}

	/**
	 * Check admin GET action for allow publishing from our custom link
	 */
	public static function admin_init_check_post_publishing() {
		if ( isset( $_GET['action'] ) && 'bea-csf-publish' === $_GET['action'] && isset( $_GET['ID'] ) && (int) $_GET['ID'] > 0 ) {
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
		global $wpdb;

		$_origin_key = BEA_CSF_Relations::current_object_is_synchronized( 'taxonomy', $wpdb->blogid, $term->term_id );

		// Get syncs model for current post_type, with any mode status (manual and auto)
		$_has_syncs = BEA_CSF_Admin_Terms_Metaboxes::taxonomy_has_sync( $term->taxonomy );

		if ( null !== $_origin_key && empty( $_has_syncs ) ) {
			unset( $actions['edit'], $actions['inline hide-if-no-js'], $actions['delete'] );
			$actions['view'] .= '<span class="locked-term-parent"></span>';
		}

		return $actions;
	}

	/**
	 * Block request for term edition
	 *
	 * @return void
	 */
	public static function admin_init_check_term_edition() {
		global $wpdb;

		// Not an edit page / edit request / bulk delete
		if ( empty( $_REQUEST['tag_ID'] ) && empty( $_REQUEST['tax_ID'] ) && empty( $_REQUEST['taxonomy'] ) && empty( $_REQUEST['delete_tags'] ) ) {
			return;
		}
		$tags = [];
		if ( ! empty( $_REQUEST['tag_ID'] ) ) {
			$tags[] = $_REQUEST['tag_ID'];
		} elseif ( ! empty( $_REQUEST['tax_ID'] ) ) {
			$tags[] = $_REQUEST['tax_ID'];
		} else {
			$tags = (array) $_REQUEST['delete_tags'];
		}

		foreach ( $tags as $tag ) {
			$current_term = get_term( (int) $tag, $_GET['taxonomy'] );

			// Term not exist ?
			if ( empty( $current_term ) || is_wp_error( $current_term ) ) {
				return;
			}

			$_origin_key = BEA_CSF_Relations::current_object_is_synchronized( 'taxonomy', $wpdb->blogid, $current_term->term_id );

			// Get syncs model for current post_type, with any mode status (manual and auto)
			$_has_syncs = BEA_CSF_Admin_Terms_Metaboxes::taxonomy_has_sync( $current_term->taxonomy );

			$_has_syncs = apply_filters( 'bea_csf_taxonomy_caps', $_has_syncs );

			if ( null !== $_origin_key && empty( $_has_syncs ) ) {
				wp_die( __( 'You are not allowed to edit this content. You must update it from your master site.', 'bea-content-sync-fusion' ) );
			}
		}
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
		global $wpdb;

		$capabilities = apply_filters( 'bea_csf_capabilities', self::$capabilities_to_check );
		if ( ! is_array( $capabilities ) ) {
			return $caps;
		}

		if ( in_array( $cap, $capabilities ) ) {
			$post = get_post( $args[0] );
			if ( empty( $post ) || is_wp_error( $post ) ) {
				return $caps;
			}

			$_origin_key = BEA_CSF_Relations::current_object_is_synchronized(
				array(
					'posttype',
					'attachment',
				),
				$wpdb->blogid,
				$post->ID
			);

			// Get syncs model for current post_type, with any mode status (manual and auto)
			$_has_syncs = BEA_CSF_Synchronizations::get(
				array(
					'post_type' => $post->post_type,
					'emitters'  => $wpdb->blogid,
				),
				'AND',
				false,
				true
			);

			$_has_syncs = apply_filters( 'bea_csf_post_caps', $_has_syncs );

			if ( null !== $_origin_key && empty( $_has_syncs ) ) {
				$caps[] = 'do_not_allow';
			}
		}

		return $caps;
	}
}
