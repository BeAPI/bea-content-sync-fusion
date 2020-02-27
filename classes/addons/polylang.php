<?php

class BEA_CSF_Addon_Polylang {
	/**
	 * BEA_CSF_Addon_Polylang constructor.
	 */
	public function __construct() {
		if ( ! defined( 'POLYLANG' ) ) {
			return;
		}

		add_filter( 'bea_csf/client/posttype/before_merge', [ $this, 'bea_csf_fix_remove_posts_translations' ], 10 );
		add_filter( 'bea_csf.server.taxonomy.merge', [ $this, 'bea_csf_server_taxonomy_merge' ], 20, 2 );
		add_filter( 'bea_csf.server.posttype.merge', [ $this, 'bea_csf_server_posttype_merge' ], 20, 2 );
		add_filter( 'bea_csf.client.taxonomy.merge', [ $this, 'bea_csf_client_taxo_merge' ], 20, 3 );
		add_filter( 'bea_csf.client.posttype.merge', [ $this, 'bea_csf_client_posttype_merge' ], 20, 3 );
	}

	/**
	 * Remove taxonomy of queue if receiver blog not contains lang
	 *
	 * @param array $emitter_data
	 * @param array $sync_fields
	 *
	 * @return array
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	public function bea_csf_server_taxonomy_merge( array $emitter_data, array $sync_fields ) {

		if ( false === pll_is_translated_taxonomy( $emitter_data['taxonomy'] ) ) {
			return $emitter_data;
		}

		$emitter_term_language = pll_get_term_language( $emitter_data['term_id'] );

		if ( empty( $emitter_term_language ) ) {
			return $emitter_data;
		}

		// Check available languages
		$receiver_blog_id = $sync_fields['_current_receiver_blog_id'];
		switch_to_blog( $receiver_blog_id );
		$receiver_list_lang = pll_languages_list();
		restore_current_blog();

		if ( ! in_array( $emitter_term_language, $receiver_list_lang ) ) {
			return [];
		}

		return $emitter_data;

	}

	/**
	 * Remove post of queue if receiver blog not contains lang
	 *
	 * @param array $emitter_data
	 * @param array $sync_fields
	 *
	 * @return array|bool
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	public function bea_csf_server_posttype_merge( array $emitter_data, array $sync_fields ) {

		if ( false === pll_is_translated_post_type( get_post_type( $emitter_data['ID'] ) ) ) {
			return $emitter_data;
		}

		$emitter_post_language = pll_get_post_language( $emitter_data['ID'] );

		if ( empty( $emitter_post_language ) ) {
			return $emitter_data;
		}

		// Check available languages
		$receiver_blog_id = $sync_fields['_current_receiver_blog_id'];
		switch_to_blog( $receiver_blog_id );
		$receiver_list_lang = pll_languages_list();
		restore_current_blog();

		// Un-sync if languages not exist
		if ( ! in_array( $emitter_post_language, $receiver_list_lang ) ) {
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
	public function bea_csf_client_taxo_merge( array $emitter_data, $sync_fields, WP_Term $new_term_obj ) {

		if ( false === pll_is_translated_taxonomy( $new_term_obj->taxonomy ) ) {
			return $emitter_data;
		}

		switch_to_blog( $emitter_data['blogid'] );
		$emitter_term_language      = pll_get_term_language( $emitter_data['term_id'] );
		$emitter_terms_translations = pll_get_term_translations( $emitter_data['term_id'] );
		restore_current_blog();

		if ( empty( $emitter_term_language ) ) {
			return $emitter_data;
		}

		$receiver_list_lang = pll_languages_list();

		if ( in_array( $emitter_term_language, $receiver_list_lang ) ) {
			pll_set_term_language( $new_term_obj->term_id, $emitter_term_language );
		}

		if ( ! empty( $emitter_terms_translations ) ) {
			$this->sync_relations( 'taxonomy', $emitter_data['blogid'], $sync_fields['_current_receiver_blog_id'], $emitter_terms_translations, $receiver_list_lang );
		}

		return $emitter_data;
	}

	/**
	 * Set language on synced content from emitter
	 *
	 * @param $emitter_data
	 * @param $sync_fields
	 * @param $receiver_post
	 *
	 * @return mixed
	 * @author Alexandre Sadowski
	 */
	public function bea_csf_client_posttype_merge( array $emitter_data, $sync_fields, WP_Post $receiver_post ) {

		if ( false === pll_is_translated_post_type( get_post_type( $receiver_post->ID ) ) ) {
			return $emitter_data;
		}

		switch_to_blog( $emitter_data['blogid'] );
		$emitter_post_language     = pll_get_post_language( $emitter_data['ID'] );
		$emitter_post_translations = pll_get_post_translations( $emitter_data['ID'] );
		restore_current_blog();

		if ( empty( $emitter_post_language ) ) {
			return $emitter_data;
		}

		$receiver_list_lang = pll_languages_list();

		if ( in_array( $emitter_post_language, $receiver_list_lang ) ) {
			pll_set_post_language( $receiver_post->ID, $emitter_post_language );
		}

		if ( ! empty( $emitter_post_translations ) ) {
			$this->sync_relations( 'posttype', $emitter_data['blogid'], $sync_fields['_current_receiver_blog_id'], $emitter_post_translations, $receiver_list_lang );
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
		}

		if ( 'taxonomy' === $type ) {
			pll_save_term_translations( $new_values );
		}

		return;
	}

	/**
	 * Fix duplicate 'post_translations' key on database by remove relation on client insert
	 *
	 * @param $data
	 *
	 * @return mixed
	 *
	 * @author Léonard Phoumpakka
	 *
	 */
	public function bea_csf_fix_remove_posts_translations( $data ) {

		if ( false !== $key = array_search( 'post_translations', $data['taxonomies'] ) ) {
			unset( $data['taxonomies'][ $key ] );
		}

		return $data;
	}
}
