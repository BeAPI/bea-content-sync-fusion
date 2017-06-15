<?php

class BEA_CSF_Client_Taxonomy {

	/**
	 * Add term on DB
	 */
	public static function merge( array $term, array $sync_fields ) {
		// Test datas validity
		if ( empty( $term ) || ! is_array( $term ) ) {
			return new WP_Error( 'invalid_datas', __( 'Bad call, invalid datas.' ) );
		}

		// Term exists ?
		$local_term_id   = (int) get_term_id_from_meta( '_origin_key', $term['blogid'] . ':' . (int) $term['term_id'] );

		if ( $local_term_id == 0 ) {
			//$edit = false;
			$new_term_id = wp_insert_term( $term['name'], $term['taxonomy'], array(
				'description' => $term['description'],
				'slug'        => $term['slug'],
				'parent'      => $term['parent']
			) );

			// try to manage error when term already exist with the same name !
			if ( is_wp_error( $new_term_id ) && $new_term_id->get_error_code() == 'term_exists' ) {
				$term_exists_result = term_exists( $term['name'], $term['taxonomy'], $term['parent'] );
				if ( $term_exists_result != false ) {
					$local_term_id = (int) get_term_id_from_meta( '_origin_key', $term['blogid'] . ':' . (int) $term_exists_result['term_id'] );
					if ( $local_term_id == 0 ) { // No master ID? no sync item !
						$new_term_id = $term_exists_result;
						update_term_meta( $term_exists_result['term_id'], 'already_exists', 1 );
					}
				}
			}
		} else {
			//$edit = true;
			$new_term_id = wp_update_term( $local_term_id, $term['taxonomy'], array(
				'name' => $term['name'],
				'description' => $term['description'],
				'slug' => $term['slug'],
				'parent' => $term['parent']
			) );
		}

		// Test merge/insertion
		if ( is_wp_error( $new_term_id ) ) {
			return new WP_Error( 'term_insertion', $new_term_id->get_error_message() );
		} elseif ( is_array( $new_term_id ) && isset( $new_term_id['term_id'] ) ) {
			$new_term_id = (int) $new_term_id['term_id'];
		} elseif ( (int) $new_term_id != 0 ) {
			$new_term_id = (int) $new_term_id;
		}

		// Always valid ?
		if ( $new_term_id == 0 ) {
			return new WP_Error( 'term_id_invalid', 'Error - Term ID is egal to 0' );
		}

		// Get term object
		$new_term_obj = get_term( $new_term_id, $term['taxonomy'] );
		if ( is_wp_error( $new_term_obj ) ) {
			return new WP_Error( 'term_valid', 'Error - Term seems invalid' );
		}

		BEA_CSF_Relations::merge( 'taxonomy', $term['blogid'], $term['term_id'], $GLOBALS['wpdb']->blogid, $new_term_obj->term_id );

		// Save all metas for new post
		if ( isset( $term['meta_data'] ) && is_array( $term['meta_data'] ) && ! empty( $term['meta_data'] ) ) {
			foreach ( $term['meta_data'] as $key => $values ) {
				// Never sync origin key
				if ( $key == '_origin_key' ) {
					continue;
				}

				if ( count( $values ) > 1 || ! isset( $values[0] ) ) {
					// TODO: Management exception, SO RARE in WP !
					continue;
				} else {
					update_term_meta( $new_term_id, $key, $values[0] );
				}
			}
		}

		// Save master id
		update_term_meta( $new_term_id, '_origin_key', $term['blogid'] . ':' . $term['term_id'] );

		// Get full term datas
		// $new_term = get_term( $new_term_id, $term['taxonomy'] );

		return apply_filters( 'bea_csf.client.taxonomy.merge', (int) $new_term_id, $sync_fields );
	}

	/**
	 * Delete a term, take the master id, try to find the new ID and delete local term
	 *
	 * @param array $term
	 * @param array $sync_fields
	 *
	 * @return bool|WP_Error
	 */
	public static function delete( array $term, array $sync_fields ) {
		// Test datas validity
		if ( empty( $term ) || ! is_array( $term ) ) {
			return new WP_Error( 'invalid_datas', __( 'Bad call, invalid datas.' ) );
		}

		// Term exists ?
		$local_term_id = (int) get_term_id_from_meta( '_origin_key', $term['blogid'] . ':' . (int) $term['term_id'] );
		if ( $local_term_id > 0 ) {
			// Term already exist before sync, keep it !
			$already_exists = (int) get_term_id_from_meta( 'already_exists', 1 );
			if ( $already_exists == 1 ) {
				return false;
			}

			wp_delete_term( $local_term_id, $term['taxonomy'] );
			BEA_CSF_Relations::delete_by_receiver( 'taxonomy', $GLOBALS['wpdb']->blogid, $local_term_id );
		}

		return apply_filters( 'bea_csf.client.taxonomy.delete', true, $sync_fields );
	}
}