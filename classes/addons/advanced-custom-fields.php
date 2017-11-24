<?php

/**
 * Class BEA_CSF_Addon_Post_Types_Order
 *
 * Addon for Post Types Order
 * @see : https://fr.wordpress.org/plugins/post-types-order/
 *
 * @author Amaury BALMER
 */
class BEA_CSF_Addon_ACF {
	static $acf_fields = array();

	public function __construct() {
		if ( !function_exists('acf_get_field_groups' ) ) {
			return false;
		}

		add_action( 'bea_csf.client.posttype.merge', array( __CLASS__, 'bea_csf_client_posttype_merge' ), 10, 3 );
		return true;
	}

	public static function bea_csf_client_posttype_merge( $data, $sync_fields, $new_post ) {
		$groups = acf_get_field_groups( array( 'post_type' => $new_post->post_type ) );
		if ( empty( $groups ) ) {
			return false;
		}

		$fields = array();
		foreach ( $groups as $group ) {
			$fields += acf_get_fields( $group );
		}

		// Get only fields
		self::prepare_acf_fields( $fields );

		// Reloop on meta from sync
		if ( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) && ! empty( $data['meta_data'] ) ) {
			foreach ( $data['meta_data'] as $key => $values ) {
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
				$meta_value_to_translate = maybe_unserialize($data['meta_data'][$meta_key_to_translate][0]);

				$types = false;
				if ( in_array($acf_field['type'], array('image', 'post_object', 'file', 'page_link', 'gallery', 'relationship')) ) {
					$types = array('attachment', 'posttype');
				} elseif ( in_array($acf_field['type'], array('taxonomy')) ) {
					$types = array('taxonomy');
				}

				// Array or singular value ?
				if ( is_array($meta_value_to_translate) ) {
					foreach( $meta_value_to_translate as $key => $value ) {
						$object_id = BEA_CSF_Relations::get_object_for_any( $types, $data['blogid'], $sync_fields['_current_receiver_blog_id'], $value, $value );
						if ( ! empty( $object_id ) && (int) $object_id > 0 ) {
							$meta_value_to_translate[$key] = $object_id;
						}
					}

					update_post_meta( $new_post->ID, $meta_key_to_translate, $meta_value_to_translate );
				} else {
					$object_id = BEA_CSF_Relations::get_object_for_any( $types, $data['blogid'], $sync_fields['_current_receiver_blog_id'], $meta_value_to_translate, $meta_value_to_translate );
					if ( ! empty( $object_id ) && (int) $object_id > 0 ) {
						update_post_meta( $new_post->ID, $meta_key_to_translate, $object_id );
					}
				}

			}
		}

	}

	/**
	 * Extract from group fields only ACF field with ID database reference (recursive !)
	 *
	 * @param array $fields
	 */
	public static function prepare_acf_fields( $fields ) {
		foreach ( (array) $fields as $field ) {

			if (in_array($field['type'], array('flexible_content') ) ) {
				foreach( $field['layouts'] as $layout_field ) {
					self::prepare_acf_fields( $layout_field['sub_fields'] );
				}
			} elseif (in_array($field['type'], array('repeater') ) ) {
				self::prepare_acf_fields( $field['sub_fields'] );
			} elseif ( in_array($field['type'], array('image', 'gallery', 'post_object', 'relationship', 'file', 'page_link', 'taxonomy') ) ) {
				self::$acf_fields[ $field['key'] ] = $field;
			}
		}
	}

}