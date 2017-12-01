<?php
/**
 * Manage dynamic ACF fields
 *
 * @author Amaury BALMER
 */
class BEA_CSF_Addon_ACF {
	static $acf_fields = array();

	/**
	 * BEA_CSF_Addon_ACF constructor.
	 */
	public function __construct() {
		if ( !function_exists('acf_get_field_groups' ) ) {
			return false;
		}

		add_action( 'bea_csf.client.posttype.merge', array( __CLASS__, 'bea_csf_client_posttype_merge' ), 10, 3 );
		add_action( 'bea_csf.client.attachment.merge', array( __CLASS__, 'bea_csf_client_posttype_merge' ), 10, 3 );

		add_action( 'bea_csf.client.taxonomy.merge', array( __CLASS__, 'bea_csf_client_taxonomy_merge' ), 10, 3 );

		return true;
	}

	/**
	 * Translate ACF fields for attachments and posts
	 *
	 * @param array $data
	 * @param array $sync_fields
	 * @param WP_Post $new_post
	 *
	 * @return bool
	 */
	public static function bea_csf_client_posttype_merge( $data, $sync_fields, $new_post ) {
		// Post have metadata ?
		if ( ! isset( $data['meta_data'] ) || ! is_array( $data['meta_data'] ) || empty( $data['meta_data'] ) ) {
			return false;
		}

		if ( 'attachment' === $new_post->post_type ) {
			// get field groups
			$groups = acf_get_field_groups( array( 'attachment' => $new_post->ID ) );
		} else {
			$groups = acf_get_field_groups( array( 'post_type' => $new_post->post_type ) );
		}


		if ( empty( $groups ) ) {
			return false;
		}

		$fields = array();
		foreach ( $groups as $group ) {
			$fields += (array) acf_get_fields( $group );
		}

		// Get only fields
		self::$acf_fields = array();
		self::prepare_acf_fields( $fields );

		// Translate
		$meta_data_to_update = self::translate_dynamic_acf_fields( $data['meta_data'], $data, $sync_fields );
		if ( is_array($meta_data_to_update) && !empty($meta_data_to_update) ) {
			foreach ( $meta_data_to_update as $meta_data_to_update_key => $meta_data_to_update_value ) {
				update_post_meta( $new_post->ID, $meta_data_to_update_key, $meta_data_to_update_value );
			}
		}

		return true;
	}

	/**
	 * Translate ACF fields for attachments and posts
	 *
	 * @param array $data
	 * @param array $sync_fields
	 * @param WP_Term $new_term
	 *
	 * @return bool
	 */
	public static function bea_csf_client_taxonomy_merge( $data, $sync_fields, $new_term ) {
		// Post have metadata ?
		if ( ! isset( $data['meta_data'] ) || ! is_array( $data['meta_data'] ) || empty( $data['meta_data'] ) ) {
			return false;
		}

		// get field groups
		$groups = acf_get_field_groups(array('taxonomy' => $new_term->taxonomy));
		if ( empty( $groups ) ) {
			return false;
		}

		$fields = array();
		foreach ( $groups as $group ) {
			$fields += (array) acf_get_fields( $group );
		}

		// Get only fields
		self::$acf_fields = array();
		self::prepare_acf_fields( $fields );

		// Translate
		$meta_data_to_update = self::translate_dynamic_acf_fields( $data['meta_data'], $data, $sync_fields );
		if ( is_array($meta_data_to_update) && !empty($meta_data_to_update) ) {
			foreach ( $meta_data_to_update as $meta_data_to_update_key => $meta_data_to_update_value ) {
				update_term_meta( $new_term->term_id, $meta_data_to_update_key, $meta_data_to_update_value );
			}
		}

		return true;
	}

	/**
	 * Translate metadata array from ***_meta table
	 *
	 * @param $meta_data
	 * @param $data
	 * @param $sync_fields
	 *
	 * @return array
	 */
	public static function translate_dynamic_acf_fields( $meta_data, $data, $sync_fields ) {
		$meta_data_to_update = array();

		// Reloop on meta from sync
		foreach ( $meta_data as $key => $values ) {
			if ( count( $values ) > 1 || ! isset( $values[0] ) ) {
				// TODO: Management exception, SO RARE in WP !
				continue;
			}

			// Keep only metadata with ID database reference
			if ( !isset(self::$acf_fields[$values[0]]) ) {
				continue;
			}

			// Get ACF field data
			$acf_field = self::$acf_fields[$values[0]];

			// Build meta_key to metadata to translate
			$meta_key_to_translate = substr($key, 1) ;

			// Get data to translate
			$meta_value_to_translate = maybe_unserialize($meta_data[$meta_key_to_translate][0]);

			$types = false;
			if ( in_array($acf_field['type'], array('image', 'post_object', 'file', 'page_link', 'gallery', 'relationship')) ) {
				$types = array('attachment', 'posttype');
			} elseif ( in_array($acf_field['type'], array('taxonomy')) ) {
				$types = array('taxonomy');
			}

			// Array or singular value ?
			if ( is_array($meta_value_to_translate) ) {
				foreach( $meta_value_to_translate as $_key => $_value ) {
					$object_id = BEA_CSF_Relations::get_object_for_any( $types, $data['blogid'], $sync_fields['_current_receiver_blog_id'], $_value, $_value );
					if ( ! empty( $object_id ) && (int) $object_id > 0 ) {
						$meta_value_to_translate[$_key] = $object_id;
					}
				}

				$meta_data_to_update[$meta_key_to_translate] = $meta_value_to_translate;
			} else {
				$object_id = BEA_CSF_Relations::get_object_for_any( $types, $data['blogid'], $sync_fields['_current_receiver_blog_id'], $meta_value_to_translate, $meta_value_to_translate );
				if ( ! empty( $object_id ) && (int) $object_id > 0 ) {
					$meta_data_to_update[$meta_key_to_translate] = $object_id;
				}
			}

		}

		return $meta_data_to_update;
	}

	/**
	 * Extract from group fields only ACF field with ID database reference (recursive !)
	 *
	 * @param array $fields
	 */
	public static function prepare_acf_fields( $fields ) {
		foreach ( (array) $fields as $field ) {
			if (in_array($field['type'], array('flexible_content') ) ) { // Flexible is recursive structure with layouts
				foreach( $field['layouts'] as $layout_field ) {
					self::prepare_acf_fields( $layout_field['sub_fields'] );
				}
			} elseif (in_array($field['type'], array('repeater') ) ) { // Repeater is recursive structure
				self::prepare_acf_fields( $field['sub_fields'] );
			} elseif ( in_array($field['type'], array('image', 'gallery', 'post_object', 'relationship', 'file', 'page_link', 'taxonomy') ) ) {
				self::$acf_fields[ $field['key'] ] = $field;
			}
		}
	}

}