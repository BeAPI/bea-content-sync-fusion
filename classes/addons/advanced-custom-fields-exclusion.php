<?php

class BEA_CSF_Addon_ACF_Exclusion {
	static $acf_fields = array();
	static $meta_data = array();

	/**
	 * BEA_CSF_Addon_ACF_Exclusion constructor.
	 */
	public function __construct() {
		if ( !function_exists('acf_get_field_groups' ) ) {
			return false;
		}

		// Fields
		if ( apply_filters( 'bea/csf/acf-addon-exclusion/allow-fieds-exclusion', false ) !== false ) {

			add_action( 'save_post', array(__CLASS__, 'save_post_fields'), 10, 1 );
			add_action( 'acf/include_field_types', array(__CLASS__, 'acf_include_field_types'), 9999999 );
			add_filter( 'bea_csf_client_' . 'Attachment' . '_' . 'merge' . '_data_to_transfer', array(__CLASS__, 'filter_acf_fields'), 11, 3 );
			add_filter( 'bea_csf_client_' . 'PostType' . '_' . 'merge' . '_data_to_transfer', array(__CLASS__, 'filter_acf_fields'), 11, 3 );
		}

		// Groups
		add_action( 'save_post', array(__CLASS__, 'save_post_groups'), 10, 1 );
		add_filter( 'bea_csf_client_' . 'Attachment' . '_' . 'merge' . '_data_to_transfer', array(__CLASS__, 'filter_acf_groups'), 10, 3 );
		add_filter( 'bea_csf_client_' . 'PostType' . '_' . 'merge' . '_data_to_transfer', array(__CLASS__, 'filter_acf_groups'), 10, 3 );
		add_action( 'admin_head', array(__CLASS__, 'admin_head'), 9999999 );

		return true;
	}

	/**
	 * Save field to exclude into a post meta
	 *
	 * @param $post_id
	 */
	public static function save_post_fields( $post_id ) {
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST[ 'bea_csf_exclude_acf_fields_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'bea_csf_exclude_acf_fields_nonce' ], plugin_basename( __FILE__ ) ) ) {
			return false;
		}

		if ( isset($_POST['bea_csf_exclude_acf_fields']) ) {
			$_POST['bea_csf_exclude_acf_fields'] = wp_unslash($_POST['bea_csf_exclude_acf_fields']);

			update_post_meta( $post_id, 'bea_csf_exclude_acf_fields', $_POST['bea_csf_exclude_acf_fields'] );
		} else {
			delete_post_meta( $post_id, 'bea_csf_exclude_acf_fields' );
		}

		return true;
	}

	/**
	 * Save groups to exclude into a post meta
	 *
	 * @param $post_id
	 */
	public static function save_post_groups( $post_id ) {
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
		if ( ! isset( $_POST[ 'bea_csf_exclude_acf_group_nonce' ] ) || ! wp_verify_nonce( $_POST[ 'bea_csf_exclude_acf_group_nonce' ], plugin_basename( __FILE__ ) ) ) {
			return false;
		}

		if ( isset($_POST['bea_csf_exclude_acf_group']) ) {
			$_POST['bea_csf_exclude_acf_group'] = wp_unslash($_POST['bea_csf_exclude_acf_group']);

			update_post_meta( $post_id, 'bea_csf_exclude_acf_group', $_POST['bea_csf_exclude_acf_group'] );
		} else {
			delete_post_meta( $post_id, 'bea_csf_exclude_acf_group' );
		}

		return true;
	}

	/**
	 * Hook all ACF fields registered
	 */
	public static function acf_include_field_types() {
		foreach( acf_get_field_types() as $sections => $fields ) {
			foreach ( $fields as $field_type => $field_label ) {
				add_action('acf/render_field/type='.$field_type, array(__CLASS__, 'acf_render_field_before'), 8, 1 );
				add_action('acf/render_field/type='.$field_type, array(__CLASS__, 'acf_render_field_after'), 10, 1 );
			}
		}
	}

	/**
	 * Do nothing actually.
	 *
	 * @param $field
	 */
	public static function acf_render_field_before( $field ) {
		if ( in_array($field['type'], array('flexible_content', 'repeater') ) ) {
			self::build_html_checkbox( $field, __('Exclude this group from future synchro', 'bea-content-sync-fusion') );
		}
	}

	/**
	 * Add an checkbox after each field for exclude from future synchro
	 *
	 * @param $field
	 */
	public static function acf_render_field_after( $field ) {
		if ( !in_array($field['type'], array('flexible_content', 'repeater') ) ) {
			self::build_html_checkbox( $field, __('Exclude this field from future synchro', 'bea-content-sync-fusion') );
		}
	}

	/**
	 * Helper for display the checkbox before or after ACF fields
	 *
	 * @param array $field
	 * @param string $label
	 *
	 * @return bool
	 */
	public static function build_html_checkbox( $field, $label ) {
		global $post, $wpdb;

		// Get emitter for current post
		$emitter_relation = BEA_CSF_Relations::current_object_is_synchronized( 'posttype', $wpdb->blogid, $post->ID );
		if ( empty( $emitter_relation ) ) {
			return false;
		}

		// Get current checked items
		$current_excluded_items = get_post_meta( $post->ID, 'bea_csf_exclude_acf_fields', true );

		// Show checkbox
		echo '<label class="bea-csf-acf-exclusion"><input type="checkbox" '.checked(in_array($field['name'], (array) $current_excluded_items), true, false).' name="bea_csf_exclude_acf_fields[]" value="'.esc_attr($field['name']).'" />'.$label.'</label>';

		// Call once time
		wp_nonce_field( plugin_basename( __FILE__ ), 'bea_csf_exclude_acf_fields_nonce' );

		return true;
	}

	/**
	 * Delete metadata excluded with ACF fields exclusion form synchro
	 *
	 * @param $data
	 * @param $sync_receiver_blog_id
	 * @param $sync_fields
	 *
	 * @return mixed
	 */
	public static function filter_acf_groups( $data, $sync_receiver_blog_id, $sync_fields ) {
		$local_id = BEA_CSF_Relations::get_object_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );
		if ( $local_id === false ) {
			return $data;
		}

		$current_excluded_groups = (array) get_post_meta( $local_id, 'bea_csf_exclude_acf_group', true );
		if ( $current_excluded_groups == false ) {
			return $data;
		}

		$fields = array();
		foreach ( $current_excluded_groups as $group_id ) {
			$fields += (array) acf_get_fields( $group_id );
		}

		// Get only fields
		self::$acf_fields = array();
		self::prepare_acf_fields( $fields );

		// Loop on each meta
		foreach( (array) $data['meta_data'] as $meta_key => $raw_meta_value ) {
			if ( isset(self::$acf_fields[$raw_meta_value[0]]) ) {
				$meta_key_parent_to_delete = substr( $meta_key, 1 );
				unset( $data['meta_data'][ $meta_key ], $data['meta_data'][ $meta_key_parent_to_delete ] );
			}
		}

		return $data;
	}

	/**
	 * Extract from group fields only ACF field with ID database reference (recursive !)
	 *
	 * @param array $fields
	 */
	public static function prepare_acf_fields( $fields ) {
		foreach ( (array) $fields as $field ) {
			self::$acf_fields[ $field['key'] ] = $field;

			if (in_array($field['type'], array('flexible_content') ) ) { // Flexible is recursive structure with layouts
				foreach( $field['layouts'] as $layout_field ) {
					self::prepare_acf_fields( $layout_field['sub_fields'] );
				}
			} elseif (in_array($field['type'], array('repeater') ) ) { // Repeater is recursive structure
				self::prepare_acf_fields( $field['sub_fields'] );
			}
		}
	}

	/**
	 * Delete metadata excluded with ACF fields exclusion form synchro
	 *
	 * @param $data
	 * @param $sync_receiver_blog_id
	 * @param $sync_fields
	 *
	 * @return mixed
	 */
	public static function filter_acf_fields( $data, $sync_receiver_blog_id, $sync_fields ) {
		$local_id = BEA_CSF_Relations::get_object_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );
		if ( $local_id === false ) {
			return $data;
		}

		$current_excluded_items = (array) get_post_meta( $local_id, 'bea_csf_exclude_acf_fields', true );
		if ( $current_excluded_items == false ) {
			return $data;
		}

		// Set meta_data into static variable
		self::$meta_data = $data['meta_data'];

		// Loop on each meta
		foreach( (array) $data['meta_data'] as $meta_key => $raw_meta_value ) {

			// Loop on each exclusion
			foreach( $current_excluded_items as $current_excluded_item ) {
				preg_match_all( '/\[(\w+)\]/is', $current_excluded_item, $matches );

				if ( isset( $matches[1] ) && count( $matches[1] ) == 1 && $matches[1][0] == $raw_meta_value[0] ) { // Classic field

					// Delete all metadata from flexible/repeater
					$acf_field = acf_maybe_get_field( $raw_meta_value[0] );
					if ( $acf_field != false && in_array($acf_field['type'], array('flexible_content', 'repeater') ) ) {
						foreach( (array) $data['meta_data'] as $sub_meta_key => $sub_meta_value ) {
							if ( !preg_match('/'.preg_quote($acf_field['name']) . '[\_]\d*[\_]/', $sub_meta_key) !== false ) {
								continue;
							}

							$sub_meta_key_parent_to_delete = substr( $sub_meta_key, 1 );
							unset( $data['meta_data'][ $sub_meta_key ], $data['meta_data'][ $sub_meta_key_parent_to_delete ] );

						}
					}

					$meta_key_parent_to_delete = substr( $meta_key, 1 );
					unset( $data['meta_data'][ $meta_key ], $data['meta_data'][ $meta_key_parent_to_delete ] );
					break;

				} else { // Complex field (flexible/repeater)

					$translated_acf_name = '';
					foreach ( $matches[1] as $fragment_match ) {
						$translated_acf_name .= self::get_acf_field_name( $fragment_match, $translated_acf_name );
					}

					if ( $meta_key == $translated_acf_name ) {
						// Delete all metadata from flexible/repeater
						$acf_field = acf_maybe_get_field( $matches[1][0] );
						if ( $acf_field != false && in_array($acf_field['type'], array('flexible_content', 'repeater') ) ) {
							foreach( (array) $data['meta_data'] as $sub_meta_key => $sub_meta_value ) {
								if ( !preg_match('/'.preg_quote($acf_field['name']) . '[\_]\d*[\_]/', $sub_meta_key) !== false ) {
									continue;
								}

								$sub_meta_key_parent_to_delete = substr( $sub_meta_key, 1 );
								unset( $data['meta_data'][ $sub_meta_key ], $data['meta_data'][ $sub_meta_key_parent_to_delete ] );

							}
						}

						$meta_key_parent_to_delete = substr( $meta_key, 1 );
						unset( $data['meta_data'][ $meta_key ], $data['meta_data'][ $meta_key_parent_to_delete ] );
						break;
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Get field name from meta_data array from acf KEY, the second argument allow to select the right flexible|repeater content depending the position into the array...
	 *
	 * @param string $acf_key
	 * @param string $translated_acf_name
	 *
	 * @return string
	 */
	public static function get_acf_field_name( $acf_key, $translated_acf_name = '' ) {
		// If not field_key, return as iteration into acf
		if ( substr($acf_key, 0, 6) !== 'field_' ) {
			return '_' . $acf_key . '_';
		}

		foreach( self::$meta_data as $acf_name => $raw_meta_value ) {
			if ( !empty($translated_acf_name) && $raw_meta_value[0] == $acf_key && strpos($acf_name, $translated_acf_name) !== false ) {
				return str_replace($translated_acf_name, '', $acf_name);
			} elseif ( empty($translated_acf_name) && $raw_meta_value[0] == $acf_key ) {
				return $acf_name;
			}
		}

		return '';
	}

	/**
	 * Hook on admin_head "as ACF" for get all metaboxes declared by this plugin and append a small checkbox
	 */
	public static function admin_head() {
		global $wp_meta_boxes;

		if ( !isset($wp_meta_boxes) ) {
			return false;
		}

		foreach( $wp_meta_boxes as &$_wp_meta_boxes ) { // Page
			foreach( $_wp_meta_boxes as &$_0_wp_meta_boxes ) { // Context
				foreach ($_0_wp_meta_boxes as &$_1_wp_meta_boxes ) { // Priority
					foreach( $_1_wp_meta_boxes as $key => &$meta_box ) { // Metaboxes
						if ( substr($key, 0, 9) == 'acf-group' && isset($meta_box['title']) ) {
							$meta_box['title'] .= ' ' . self::get_html_checkbox_for_metabox( $meta_box, __('Exclude this group from sync', 'bea-content-sync-fusion') );
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Helper for build HTML checkbox for metabox acf field group exclusion
	 *
	 * @param $meta_box
	 * @param $label
	 *
	 * @return bool
	 */
	public static function get_html_checkbox_for_metabox( $meta_box, $label ) {
		global $post, $wpdb;

		// Get emitter for current post
		$emitter_relation = BEA_CSF_Relations::current_object_is_synchronized( 'posttype', $wpdb->blogid, $post->ID );
		if ( empty( $emitter_relation ) ) {
			return false;
		}

		// Get ID from metabox arguments
		$acf_group_id = $meta_box['args']['field_group']['ID'];

		// Get current checked items
		$current_excluded_items = get_post_meta( $post->ID, 'bea_csf_exclude_acf_group', true );

		$output = '<label class="bea-csf-acf-exclusion"><input type="checkbox" '.checked(in_array($acf_group_id, (array) $current_excluded_items), true, false).' name="bea_csf_exclude_acf_group[]" value="'.esc_attr($acf_group_id).'" />'.$label.'</label>';
		$output .= wp_nonce_field( plugin_basename( __FILE__ ), 'bea_csf_exclude_acf_group_nonce', true, false  );

		return $output;
	}
}