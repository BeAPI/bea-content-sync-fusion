<?php

class BEA_CSF_Client_Taxonomy {

	/**
	 * Insert or merge term on client database
	 *
	 * @param array $data
	 * @param array $sync_fields
	 *
	 * @return mixed|WP_Error
	 */
	public static function merge( array $data, array $sync_fields ) {
		global $_bea_origin_blog_id;

		if ( empty( $data ) || ! is_array( $data ) ) {
			return new WP_Error( 'invalid_datas', __( 'Bad call, invalid datas.' ) );
		}

		if ( ! isset( $data['blogid'] ) ) {
			return new WP_Error( 'missing_blog_id', 'Error - Missing a blog ID for allow insertion.' );
		}

		if ( ! isset( $data['term_id'] ) ) {
			return new WP_Error( 'missing_term_id', 'Error - Missing a term ID for allow insertion.' );
		}

		$data = apply_filters( 'bea_csf/client/taxonomy/before_merge', $data, $sync_fields );

		if ( false === $data || empty( $data ) ) {
			return new WP_Error( 'empty_data', 'Error - exclude taxonomy to sync' );
		}

		// Define this variable for skip infinite sync when emetter and receiver are reciprocal
		$_bea_origin_blog_id = $data['blogid'];

		// Prevent PLL same slug for several languages. Check if pll term exist by slug
		if ( function_exists( 'PLL' ) && ! empty( $data['pll']['is_translated'] ) ) {
			$exist_pll_term_id = PLL()->model->term_exists_by_slug( $data['slug'], $data['pll']['language'], $data['taxonomy'], BEA_CSF_Relations::get_object_for_any( 'taxonomy', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['parent'], $data['parent'] ) );
		}

		$local_term_id = BEA_CSF_Relations::get_object_for_any( 'taxonomy', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['term_id'], $data['term_id'] );
		if ( ! empty( $local_term_id ) && (int) $local_term_id > 0 ) {
			// Prevent PLL same slug for several languages on update
			if ( ! empty( $data['pll']['is_translated'] ) && ! empty( $exist_pll_term_id ) ) {
				$data['slug'] = $data['slug'] . '___' . $data['pll']['language']; // Prevent exist term
			}

			$new_term_id = wp_update_term( $local_term_id, $data['taxonomy'], array(
				'name'        => $data['name'],
				'description' => $data['description'],
				'slug'        => $data['slug'],
				'parent'      => BEA_CSF_Relations::get_object_for_any( 'taxonomy', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['parent'], $data['parent'] ),
			) );
		} else {
			// Prevent PLL same slug for several languages on insert
			if ( ! empty( $data['pll']['is_translated'] ) && empty( $exist_pll_term_id ) ) {
				$data['slug'] = $data['slug'] . '___' . $data['pll']['language']; // Create new one
			}

			$new_term_id = wp_insert_term(
				$data['name'],
				$data['taxonomy'],
				array(
					'description' => $data['description'],
					'slug'        => $data['slug'],
					'parent'      => BEA_CSF_Relations::get_object_for_any( 'taxonomy', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['parent'], $data['parent'] ),

				)
			);

			// try to manage error when term already exist with the same name !
			if ( is_wp_error( $new_term_id ) && 'term_exists' === $new_term_id->get_error_code() ) {

				$term_exists_result = term_exists( $data['name'], $data['taxonomy'], $data['parent'] );

				// Prevent PLL same slug for several languages on update
				if ( ! empty( $data['pll']['is_translated'] ) && ! empty( $exist_pll_term_id ) ) {
					$term_exists_result = [ 'term_id' => $exist_pll_term_id ];
					$data['slug']       = $data['slug'] . '___' . $data['pll']['language']; // Prevent exist term
				}

				if ( false !== $term_exists_result && isset( $term_exists_result['term_id'] ) ) {
					$new_term_id = wp_update_term(
						$term_exists_result['term_id'],
						$data['taxonomy'],
						array(
							'name'        => $data['name'],
							'description' => $data['description'],
							'slug'        => $data['slug'],
							'parent'      => BEA_CSF_Relations::get_object_for_any( 'taxonomy', $data['blogid'], $sync_fields['_current_receiver_blog_id'], $data['parent'], $data['parent'] ),
						)
					);
					update_term_meta( $term_exists_result['term_id'], 'already_exists', 1 );
				}
			}
		}

		// Delete this variable for skip conflict with next item to sync
		unset( $_bea_origin_blog_id );

		// Test merge/insertion
		if ( is_wp_error( $new_term_id ) ) {
			return new WP_Error( 'term_insertion', $new_term_id->get_error_message() );
		} elseif ( is_array( $new_term_id ) && isset( $new_term_id['term_id'] ) ) {
			$new_term_id = (int) $new_term_id['term_id'];
		} elseif ( 0 !== (int) $new_term_id ) {
			$new_term_id = (int) $new_term_id;
		}

		// Always valid ?
		if ( 0 === $new_term_id ) {
			return new WP_Error( 'term_id_invalid', 'Error - Term ID is egal to 0' );
		}

		// Get term object
		$new_term_obj = get_term( $new_term_id, $data['taxonomy'] );
		if ( is_wp_error( $new_term_obj ) ) {
			return new WP_Error( 'term_valid', 'Error - Term seems invalid' );
		}

		BEA_CSF_Relations::merge( 'taxonomy', $data['blogid'], $data['term_id'], $GLOBALS['wpdb']->blogid, $new_term_obj->term_id );

		// Save all metas for new post
		if ( isset( $data['meta_data'] ) && is_array( $data['meta_data'] ) && ! empty( $data['meta_data'] ) ) {
			foreach ( $data['meta_data'] as $key => $values ) {
				if ( count( $values ) > 1 || ! isset( $values[0] ) ) {
					// TODO: Management exception, SO RARE in WP !
					continue;
				} else {
					update_term_meta( $new_term_id, $key, $values[0] );
				}
			}
		}

		return apply_filters( 'bea_csf.client.taxonomy.merge', $data, $sync_fields, $new_term_obj );
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
		if ( empty( $term ) || ! is_array( $term ) ) {
			return new WP_Error( 'invalid_datas', __( 'Bad call, invalid datas.' ) );
		}

		$local_term_id = BEA_CSF_Relations::get_object_for_any( 'taxonomy', $term['blogid'], $sync_fields['_current_receiver_blog_id'], $term['term_id'], $term['term_id'] );
		if ( ! empty( $local_term_id ) && (int) $local_term_id > 0 ) {
			BEA_CSF_Relations::delete_by_receiver( 'taxonomy', (int) $GLOBALS['wpdb']->blogid, (int) $local_term_id );

			// Delete additional if reciprocal synchro
			BEA_CSF_Relations::delete_by_emitter_and_receiver( 'taxonomy', (int) $GLOBALS['wpdb']->blogid, (int) $local_term_id, (int) $term['blogid'], (int) $term['term_id'] );

			// Term already exist before sync, keep it !
			$already_exists = get_term_meta( $local_term_id, 'already_exists', true );
			if ( ! empty( $already_exists ) && 1 === absint( $already_exists ) ) {
				return false;
			}

			wp_delete_term( $local_term_id, $term['taxonomy'] );
		}

		return apply_filters( 'bea_csf.client.taxonomy.delete', true, $sync_fields );
	}
}
