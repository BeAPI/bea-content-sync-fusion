<?php

class BEA_CSF_Addon_Polylang {
	/**
	 * BEA_CSF_Addon_Polylang constructor.
	 */
	public function __construct() {
		if ( ! defined( 'POLYLANG' ) ) {
			return;
		}

		add_filter( 'bea_csf/client/posttype/before_merge', [ $this, 'bea_csf_fix_remove_posts_translations' ], 21 );
		add_filter( 'bea_csf.server.taxonomy.merge', [ $this, 'bea_csf_server_taxonomy_merge' ], 20 );
		add_filter( 'bea_csf/client/taxonomy/before_merge', [ $this, 'bea_csf_client_taxonomy_before_merge' ], 20 );
		add_filter( 'bea_csf.client.taxonomy.merge', [ $this, 'bea_csf_set_term_language' ], 20, 3 );
		add_filter( 'bea_csf.server.posttype.merge', [ $this, 'bea_csf_server_posttype_merge' ], 20 );
		add_filter( 'bea_csf/client/posttype/before_merge', [ $this, 'bea_csf_client_posttype_before_merge' ], 20 );
		add_filter( 'bea_csf.client.posttype.merge', [ $this, 'bea_csf_set_post_languages' ], 20, 3 );
	}

	/**
	 * Prepare additional polylang data for taxonomies
	 *
	 * @param array $emitter_data
	 *
	 * @return array
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	public function bea_csf_server_taxonomy_merge( array $emitter_data ) {

		$emitter_data['pll']['is_translated'] = pll_is_translated_taxonomy( $emitter_data['taxonomy'] );
		$emitter_data['pll']['translations']  = pll_get_term_translations( $emitter_data['term_id'] );
		$emitter_data['pll']['language']      = pll_get_term_language( $emitter_data['term_id'] );

		return $emitter_data;
	}

	/**
	 * Remove taxonomy of queue on receiver blog if lang not exist
	 *
	 * @param array $emitter_data
	 *
	 * @return bool|array
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	public function bea_csf_client_taxonomy_before_merge( array $emitter_data ) {

		// Taxonomy is translated ?
		if ( false === $emitter_data['pll']['is_translated'] ) {
			return $emitter_data;
		}

		// Slug is exist ?
		if ( empty( $emitter_data['pll']['language'] ) ) {
			return $emitter_data;
		}

		// Check available languages
		$receiver_list_lang = pll_languages_list();

		// Un-sync if set language not exist on receiver blog
		if ( ! in_array( $emitter_data['pll']['language'], $receiver_list_lang ) ) {
			return false;
		}

		return $emitter_data;
	}

	/**
	 * Set language on synced terms from emitter
	 *
	 * @param array $emitter_data
	 * @param $sync_fields
	 * @param WP_Term $new_term_obj
	 *
	 * @return array
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	public function bea_csf_set_term_language( array $emitter_data, $sync_fields, WP_Term $new_term_obj ) {

		if ( false === $emitter_data['pll']['is_translated'] ) {
			return $emitter_data;
		}

		$emitter_term_language      = $emitter_data['pll']['language'];
		$emitter_terms_translations = $emitter_data['pll']['translations'];

		$receiver_list_lang = pll_languages_list();
		pll_set_term_language( $new_term_obj->term_id, $emitter_term_language );

		if ( ! empty( $emitter_terms_translations ) ) {
			$this->sync_relations( 'taxonomy', $emitter_data['blogid'], $sync_fields['_current_receiver_blog_id'], $emitter_terms_translations, $receiver_list_lang );
		}

		return $emitter_data;
	}

	/**
	 * Add additional polylang data for post
	 *
	 * @param array $emitter_data
	 *
	 * @return array
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	public function bea_csf_server_posttype_merge( array $emitter_data ) {
		$emitter_data['pll']['is_translated'] = pll_is_translated_post_type( get_post_type( $emitter_data['ID'] ) );
		$emitter_data['pll']['translations']  = pll_get_post_translations( $emitter_data['ID'] );
		$emitter_data['pll']['language']      = pll_get_post_language( $emitter_data['ID'] );

		return $emitter_data;
	}

	/**
	 * Remove post of queue if set language not exist on receiver blog or problem with language
	 *
	 * @param array $emitter_data
	 *
	 * @return array|bool
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	public function bea_csf_client_posttype_before_merge( array $emitter_data ) {

		// Check if post type is not translated
		if ( false === $emitter_data['pll']['is_translated'] ) {
			return $emitter_data;
		}

		// Problem with slug
		if ( empty( $emitter_data['pll']['language'] ) ) {
			return false;
		}

		// Check available languages on receiver blog
		$receiver_list_lang = pll_languages_list();

		// Un-sync if set language not exist on receiver blog
		if ( ! in_array( $emitter_data['pll']['language'], $receiver_list_lang ) ) {
			return false;
		}

		return $emitter_data;
	}

	/**
	 * Set language and relation on synced
	 *
	 * @param $emitter_data
	 * @param $sync_fields
	 * @param $receiver_post
	 *
	 * @return mixed
	 * @author Alexandre Sadowski
	 */
	public function bea_csf_set_post_languages( array $emitter_data, $sync_fields, WP_Post $receiver_post ) {

		// Check if post type is not translated
		if ( false === $emitter_data['pll']['is_translated'] ) {
			return $emitter_data;
		}

		pll_set_post_language( $receiver_post->ID, $emitter_data['pll']['language'] );
		$receiver_list_lang = pll_languages_list();

		if ( ! empty( $emitter_data['pll']['translations'] ) ) {
			$this->sync_relations( 'posttype', $emitter_data['blogid'], $sync_fields['_current_receiver_blog_id'], $emitter_data['pll']['translations'], $receiver_list_lang );
		}

		return $emitter_data;
	}

	/**
	 *  Sync relations
	 *
	 * @param $type
	 * @param $emitter_blog_id
	 * @param $receiver_blog_id
	 * @param $emitter_obj_translations
	 * @param array $receiver_list_lang
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	private function sync_relations( $type, $emitter_blog_id, $receiver_blog_id, $emitter_obj_translations, array $receiver_list_lang ) {
		$new_values = [];

		if ( empty( $emitter_obj_translations ) || empty( $receiver_list_lang ) ) {
			return;
		}

		// Set relations for only exist languages
		$emitter_obj_translations = array_intersect_key( $emitter_obj_translations, array_flip( $receiver_list_lang ) );

		if ( empty( $emitter_obj_translations ) ) {
			return;
		}

		foreach ( $emitter_obj_translations as $lang => $emitter_obj_translation_id ) {
			$new_obj_id = BEA_CSF_Relations::get_object_for_any( [ $type ], $emitter_blog_id, $receiver_blog_id, $emitter_obj_translation_id, $emitter_obj_translation_id );
			if ( false === $new_obj_id ) {
				continue;
			}
			$new_values[ $lang ] = $new_obj_id;
		}

		if ( empty( $new_values ) ) {
			return;
		}

		if ( 'posttype' === $type ) {
			pll_save_post_translations( $new_values );
			$this->bea_csf_update_posts_slug( $new_values );
		}

		if ( 'taxonomy' === $type ) {
			pll_save_term_translations( $new_values );
			$this->bea_csf_update_terms_slug( $new_values );
		}
	}


	/**
	 * Fix duplicate 'post_translations' on bdd by remove relation on insert
	 *
	 * @param $data
	 *
	 * @return mixed
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	public function bea_csf_fix_remove_posts_translations( $data ) {

		if ( ! isset( $data['taxonomies'] ) ) {
			return $data;
		}

		$key = array_search( 'post_translations', $data['taxonomies'] );
		if ( false !== $key ) {
			unset( $data['taxonomies'][ $key ] );
		}

		return $data;
	}

	/**
	 * Prevent post translations with same slugs
	 *
	 * @param $posts
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	public function bea_csf_update_posts_slug( $posts ) {

		foreach ( $posts as $lang => $post_id ) {

			$post = get_post( $post_id );

			if ( empty( $post ) ) {
				continue;
			}

			$args              = $post->to_array();
			$args['post_name'] = sanitize_title( $post->post_title );

			\wp_update_post( $args );
		}
	}

	/**
	 * Prevent taxonomy translations with same slugs
	 *
	 * @param $terms
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	public function bea_csf_update_terms_slug( $terms ) {
		foreach ( $terms as $lang => $term_id ) {

			$term_obj = get_term_by( 'ID', $term_id );

			if ( empty( $term_obj ) || is_wp_error( $term_obj ) ) {
				continue;
			}

			$args = [
				'slug' => sanitize_title( $term_obj->name ),
			];

			\wp_update_term( $term_id, $term_obj->taxonomy, $args );
		}
	}
}
