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
		if ( ! class_exists( 'acf' ) ) {
			return false;
		}

		add_action( 'bea_csf.client.posttype.merge', array( __CLASS__, 'bea_csf_client_posttype_merge' ), 10, 3 );
		add_action( 'bea_csf.client.attachment.merge', array( __CLASS__, 'bea_csf_client_posttype_merge' ), 10, 3 );

		add_action( 'bea_csf.client.taxonomy.merge', array( __CLASS__, 'bea_csf_client_taxonomy_merge' ), 10, 3 );
		add_filter( 'bea_csf_gutenberg_translate_block_attributes', array( __CLASS__, 'translate_acf_blocks' ), 10, 4 );

		return true;
	}

	/**
	 * Translate ACF fields for attachments and posts
	 *
	 * @param array $data
	 * @param array $sync_fields
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

		$fields = array();
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
		self::$acf_fields = array();
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
	 * @param array $data
	 * @param array $sync_fields
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

		$fields = array();
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
		self::$acf_fields = array();
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
		$meta_data_to_update = array();

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
			if ( in_array( $acf_field['type'], array( 'image', 'post_object', 'file', 'page_link', 'gallery', 'relationship' ) ) ) {
				$types = array( 'attachment', 'posttype' );
			} elseif ( in_array( $acf_field['type'], array( 'taxonomy' ) ) ) {
				$types = array( 'taxonomy' );
			}

			// Array or singular value ?
			if ( is_array( $meta_value_to_translate ) ) {
				foreach ( $meta_value_to_translate as $_key => $_value ) {
					$object_id = BEA_CSF_Relations::get_object_for_any( $types, $data['blogid'], $sync_fields['_current_receiver_blog_id'], $_value, $_value );
					// If relation not exist, try to check if the parent relation is an synchronized content for get an indirect relation
					if ( empty( $object_id ) || (int) $object_id == 0 ) {
						$parent_relation = BEA_CSF_Relations::current_object_is_synchronized( $types, $data['blogid'], $meta_value_to_translate );
						if ( $parent_relation != false ) {
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
				if ( empty( $object_id ) || (int) $object_id == 0 ) {
					$parent_relation = BEA_CSF_Relations::current_object_is_synchronized( $types, $data['blogid'], $meta_value_to_translate );
					if ( $parent_relation != false ) {
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
			if ( in_array( $field['type'], array( 'flexible_content' ) ) ) { // Flexible is recursive structure with layouts
				foreach ( $field['layouts'] as $layout_field ) {
					self::prepare_acf_fields( $layout_field['sub_fields'] );
				}
			} elseif ( in_array( $field['type'], array( 'repeater', 'group' ) ) ) { // Repeater is recursive structure
				self::prepare_acf_fields( $field['sub_fields'] );
			} elseif ( in_array( $field['type'], array( 'image', 'gallery', 'post_object', 'relationship', 'file', 'page_link', 'taxonomy' ) ) ) {
				self::$acf_fields[ $field['key'] ] = $field;
			}
		}
	}

	/**
	 * Translate attributs for a acf block.
	 *
	 * @param array $attributes current block's attributs.
	 * @param string $block_name current block's name.
	 * @param int $emitter_blog_id ID of the emitter site.
	 * @param int $receiver_blog_id ID of the receiver site.
	 *
	 * @return array translated attributs.
	 * @author Egidio CORICA
	 */
	public static function translate_acf_blocks( array $attributes, string $block_name, int $emitter_blog_id, int $receiver_blog_id ): array {
		// Skip if it's not an acf block
		if ( false === strpos( $block_name, 'acf' ) ) {
			return $attributes;
		}

		foreach ( $attributes['data'] as $field_name => $field_value ) {
			if ( 0 === strpos( $field_name, '_' ) ) {
				continue;
			}

			$field_object  = acf_get_field( $field_name );
			$type_relation = self::get_type_relation( $field_object );

			if ( empty( $type_relation ) ) {
				continue;
			}

			// Multiple values
			if ( is_array( $field_value ) ) {
				$translate_fields = [];

				foreach ( $field_value as $value ) {
					if ( ! is_numeric( $value ) ) {
						$translate_fields[] = $value;
						continue;
					}

					$translate_fields[] = (string) self::translate_field( (int) $value, $emitter_blog_id, $receiver_blog_id, $type_relation );
				}

				$attributes['data'][ $field_name ] = $translate_fields;

				continue;
			}

			// Single value
			if ( is_numeric( $field_value ) ) {
				$attributes['data'][ $field_name ] = (string) self::translate_field( (int) $field_value, $emitter_blog_id, $receiver_blog_id, $type_relation );
			}
		}

		return $attributes;
	}


	/**
	 * Translate field if a relationship exists
	 *
	 * @param int $value
	 * @param int $emitter_blog_id
	 * @param int $receiver_blog_id
	 * @param string $type_relation
	 *
	 * @return int
	 */
	public static function translate_field( int $value, int $emitter_blog_id, int $receiver_blog_id, string $type_relation ): int {
		$local_id = BEA_CSF_Relations::get_object_for_any(
			$type_relation,
			$emitter_blog_id,
			$receiver_blog_id,
			$value,
			$value
		);

		return ! empty( $local_id ) ? $local_id : $value;
	}

	/**
	 * Get type relation by acf field
	 *
	 * @param $field_object
	 *
	 * @return string
	 */
	public static function get_type_relation( $field_object ): string {
		$types_relation = [
			'posttype'   => apply_filters( 'bea_csf_addon_acf_match_fields_posttype', [ 'post_object', 'relationship', 'page_link' ], $field_object ),
			'attachment' => apply_filters( 'bea_csf_addon_acf_match_fields_attachment', [ 'image', 'gallery', 'file' ], $field_object ),
			'taxonomy'   => apply_filters( 'bea_csf_addon_acf_match_fields_taxonomy', [ 'taxonomy' ], $field_object ),
		];

		foreach ( $types_relation as $type_relation_name => $field_type ) {
			if ( in_array( $field_object['type'], $field_type, true ) ) {
				return $type_relation_name;
			}
		}

		return '';
	}

}
