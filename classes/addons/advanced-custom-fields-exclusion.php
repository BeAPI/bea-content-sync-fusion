<?php

class BEA_CSF_Addon_ACF_Exclusion {
	static $meta_data = array();

	public function __construct() {
		add_action( 'save_post', array(__CLASS__, 'save_post'), 10, 1 );
		add_action('acf/include_field_types', array(__CLASS__, 'acf_include_field_types'), 9999999 );

		add_filter( 'bea_csf_client_' . 'Attachment' . '_' . 'merge' . '_data_to_transfer', array(__CLASS__, 'bea_csf_client_post_type_attachment_data_to_transfer'), 10, 3 );
		add_filter( 'bea_csf_client_' . 'PostType' . '_' . 'merge' . '_data_to_transfer', array(__CLASS__, 'bea_csf_client_post_type_attachment_data_to_transfer'), 10, 3 );

	}

	/**
	 * Save field to exclude into a post meta
	 *
	 * @param $post_id
	 */
	public static function save_post( $post_id ) {
		if ( isset($_POST['bea_csf_exclude']) ) {
			$_POST['bea_csf_exclude'] = wp_unslash($_POST['bea_csf_exclude']);

			update_post_meta( $post_id, 'bea_csf_exclude', $_POST['bea_csf_exclude'] );
		}
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
			self::build_html_checkbox( $field );
		}
	}

	/**
	 * Add an checkbox after each field for exclude from future synchro
	 *
	 * @param $field
	 */
	public static function acf_render_field_after( $field ) {
		if ( !in_array($field['type'], array('flexible_content', 'repeater') ) ) {
			self::build_html_checkbox( $field );
		}
	}

	public static function build_html_checkbox( $field ) {
		//$output = ob_get_clean();
		global $post, $wpdb;

		// Get emitter for current post
		$emitter_relation = BEA_CSF_Relations::current_object_is_synchronized( 'posttype', $wpdb->blogid, $post->ID );
		if ( empty( $emitter_relation ) ) {
			return false;
		}

		// Get current checked items
		$current_excluded_items = get_post_meta( $post->ID, 'bea_csf_exclude', true );

		echo '<label><input type="checkbox" '.checked(in_array($field['name'], (array) $current_excluded_items), true, false).' name="bea_csf_exclude[]" value="'.esc_attr($field['name']).'" />'.__('Exclude from future synchro', 'bea-content-sync-fusion').'</label>';
	}

	/**
	 * Delete metadata excluded form synchro
	 *
	 * @param $data
	 * @param $sync_receiver_blog_id
	 * @param $sync_fields
	 *
	 * @return mixed
	 */
	public static function bea_csf_client_post_type_attachment_data_to_transfer( $data, $sync_receiver_blog_id, $sync_fields ) {
		$local_id = BEA_CSF_Relations::get_object_for_any( 'posttype', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['ID'], $data['ID'] );
		if ( $local_id === false ) {
			return $data;
		}

		$current_excluded_items = (array) get_post_meta( $local_id, 'bea_csf_exclude', true );
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
				//var_dump(count($matches[1]), $matches[1][0] );
				if ( isset( $matches[1] ) && count( $matches[1] ) == 1 && $matches[1][0] == $raw_meta_value[0] ) { // Classic field

					// acf_maybe_get_field
					// Delete metadata from flexible/repeater

					$meta_key_parent_to_delete = substr( $meta_key, 1 );
					unset( $data['meta_data'][ $meta_key ], $data['meta_data'][ $meta_key_parent_to_delete ] );
					break;

				} else { // Complex field (flexible/repeater)

					$translated_acf_name = '';
					foreach ( $matches[1] as $fragment_match ) {
						$translated_acf_name .= self::get_acf_field_name( $fragment_match, $translated_acf_name );
					}

					if ( $meta_key == $translated_acf_name ) {
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

}