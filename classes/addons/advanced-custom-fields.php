<?php

/**
 * Manage dynamic ACF fields
 *
 * @author Amaury BALMER
 */
class BEA_CSF_Addon_ACF {
	public static $acf_fields = [];

	/**
	 * BEA_CSF_Addon_ACF constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'acf' ) ) {
			return;
		}

		add_action( 'bea_csf.client.posttype.merge', [ __CLASS__, 'bea_csf_client_posttype_merge' ], 10, 3 );
		add_action( 'bea_csf.client.attachment.merge', [ __CLASS__, 'bea_csf_client_posttype_merge' ], 10, 3 );

		add_action( 'bea_csf.client.taxonomy.merge', [ __CLASS__, 'bea_csf_client_taxonomy_merge' ], 10, 3 );
	}

	/**
	 * Translate ACF fields for attachments and posts
	 *
	 * @param array   $data
	 * @param array   $sync_fields
	 * @param WP_Post $new_post
	 *
	 * @return array
	 */
	public static function bea_csf_client_posttype_merge( $data, $sync_fields, $new_post ) {
		// Post have metadata ?
		if ( ! isset( $data['meta_data'] ) || ! is_array( $data['meta_data'] ) || empty( $data['meta_data'] ) ) {
			return $data;
		}

		// Get all groups
		$groups = acf_get_field_groups();
		if ( empty( $groups ) ) {
			return $data;
		}

		$fields = [];
		foreach ( $groups as $group ) {
			$_fields = (array) acf_get_fields( $group );
			foreach ( $_fields as $_field ) {
				$fields[] = $_field;
			}
		}

		if ( empty( $fields ) ) {
			return $data;
		}

		// Get only fields
		self::$acf_fields = [];
		self::prepare_acf_fields( $fields );

		// Translate
		$meta_data_to_update = self::translate_dynamic_acf_fields( $data['meta_data'], $data, $sync_fields );
		if ( is_array( $meta_data_to_update ) && ! empty( $meta_data_to_update ) ) {
			foreach ( $meta_data_to_update as $meta_data_to_update_key => $meta_data_to_update_value ) {
				update_post_meta( $new_post->ID, $meta_data_to_update_key, $meta_data_to_update_value );
			}
		}

		return $data;
	}

	/**
	 * Translate ACF fields for attachments and posts
	 *
	 * @param array   $data
	 * @param array   $sync_fields
	 * @param WP_Term $new_term
	 *
	 * @return array
	 */
	public static function bea_csf_client_taxonomy_merge( $data, $sync_fields, $new_term ) {
		// Post have metadata ?
		if ( ! isset( $data['meta_data'] ) || ! is_array( $data['meta_data'] ) || empty( $data['meta_data'] ) ) {
			return $data;
		}

		// Get all groups
		$groups = acf_get_field_groups();
		if ( empty( $groups ) ) {
			return $data;
		}

		$fields = [];
		foreach ( $groups as $group ) {
			$_fields = (array) acf_get_fields( $group );
			foreach ( $_fields as $_field ) {
				$fields[] = $_field;
			}
		}

		if ( empty( $fields ) ) {
			return $data;
		}

		// Get only fields
		self::$acf_fields = [];
		self::prepare_acf_fields( $fields );

		// Translate
		$meta_data_to_update = self::translate_dynamic_acf_fields( $data['meta_data'], $data, $sync_fields );
		if ( is_array( $meta_data_to_update ) && ! empty( $meta_data_to_update ) ) {
			foreach ( $meta_data_to_update as $meta_data_to_update_key => $meta_data_to_update_value ) {
				update_term_meta( $new_term->term_id, $meta_data_to_update_key, $meta_data_to_update_value );
			}
		}

		return $data;
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
		$meta_data_to_update = [];

		// Reloop on meta from sync
		foreach ( $meta_data as $key => $values ) {
			if ( count( $values ) > 1 || ! isset( $values[0] ) ) {
				// TODO: Management exception, SO RARE in WP !
				continue;
			}

			// Keep only metadata with ID database reference
			if ( ! isset( self::$acf_fields[ $values[0] ] ) ) {
				continue;
			}

			// Get ACF field data
			$acf_field = self::$acf_fields[ $values[0] ];

			// Build meta_key to metadata to translate
			$meta_key_to_translate = substr( $key, 1 );

			// Get data to translate
			$meta_value_to_translate = maybe_unserialize( $meta_data[ $meta_key_to_translate ][0] );

			$types = false;
			if ( in_array( $acf_field['type'], [ 'image', 'post_object', 'file', 'page_link', 'gallery', 'relationship' ] ) ) {
				$types = [ 'attachment', 'posttype' ];
			} elseif ( in_array( $acf_field['type'], [ 'taxonomy' ] ) ) {
				$types = [ 'taxonomy' ];
			}

			// Array or singular value ?
			if ( is_array( $meta_value_to_translate ) ) {
				foreach ( $meta_value_to_translate as $_key => $_value ) {
					$object_id = BEA_CSF_Relations::get_object_for_any( $types, $data['blogid'], $sync_fields['_current_receiver_blog_id'], $_value, $_value );
					// If relation not exist, try to check if the parent relation is an synchronized content for get an indirect relation
					if ( empty( $object_id ) || 0 === (int) $object_id ) {
						$parent_relation = BEA_CSF_Relations::current_object_is_synchronized( $types, $data['blogid'], $meta_value_to_translate );
						if ( false !== $parent_relation ) {
							$object_id = BEA_CSF_Relations::get_object_for_any( $types, $parent_relation->emitter_blog_id, $sync_fields['_current_receiver_blog_id'], $parent_relation->emitter_id, $parent_relation->emitter_id );
						}
					}

					if ( ! empty( $object_id ) && (int) $object_id > 0 ) {
						$meta_value_to_translate[ $_key ] = $object_id;
					}
				}

				$meta_data_to_update[ $meta_key_to_translate ] = $meta_value_to_translate;
			} else {
				$object_id = BEA_CSF_Relations::get_object_for_any( $types, $data['blogid'], $sync_fields['_current_receiver_blog_id'], $meta_value_to_translate, $meta_value_to_translate );
				// If relation not exist, try to check if the parent relation is an synchronized content for get an indirect relation
				if ( empty( $object_id ) || 0 === (int) $object_id ) {
					$parent_relation = BEA_CSF_Relations::current_object_is_synchronized( $types, $data['blogid'], $meta_value_to_translate );
					if ( false !== $parent_relation ) {
						$object_id = BEA_CSF_Relations::get_object_for_any( $types, $parent_relation->emitter_blog_id, $sync_fields['_current_receiver_blog_id'], $parent_relation->emitter_id, $parent_relation->emitter_id );
					}
				}

				if ( ! empty( $object_id ) && (int) $object_id > 0 ) {
					$meta_data_to_update[ $meta_key_to_translate ] = $object_id;
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
			if ( in_array( $field['type'], [ 'flexible_content' ], true ) ) { // Flexible is recursive structure with layouts
				foreach ( $field['layouts'] as $layout_field ) {
					self::prepare_acf_fields( $layout_field['sub_fields'] );
				}
			} elseif ( in_array( $field['type'], [ 'repeater', 'group' ] ) ) { // Repeater is recursive structure
				self::prepare_acf_fields( $field['sub_fields'] );
			} elseif ( in_array( $field['type'], [ 'image', 'gallery', 'post_object', 'relationship', 'file', 'page_link', 'taxonomy' ] ) ) {
				self::$acf_fields[ $field['key'] ] = $field;
			}
		}
	}

}
