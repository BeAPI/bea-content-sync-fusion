<?php

class BEA_CSF_Addon_Polylang {
	/**
	 * BEA_CSF_Addon_Polylang constructor.
	 */
	public function __construct() {
		if ( ! defined( 'POLYLANG' ) ) {
			return;
		}

		add_filter( 'bea_csf.client.posttype.merge', [ $this, 'bea_csf_client_posttype_merge' ], 20, 3 );
		add_filter( 'bea_csf.client.taxonomy.merge', [ $this, 'bea_csf_client_taxo_merge' ], 20, 2 );
		add_filter( 'bea_csf.server.posttype.delete', [ $this, 'bea_csf_server_posttype_no_sync_en' ], 10, 3 );
		add_filter( 'bea_csf.server.posttype.merge', [ $this, 'bea_csf_server_posttype_no_sync_en' ], 10, 3 );
		add_filter( 'bea_csf.server.taxonomy.delete', [ $this, 'bea_csf_server_term_no_sync_en' ], 10, 2 );
		add_filter( 'bea_csf.server.taxonomy.merge', [ $this, 'bea_csf_server_term_no_sync_en' ], 10, 2 );
	}

	/**
	 * Set default language on synched contents
	 *
	 * @param $data
	 * @param $sync_fields
	 * @param $new_post
	 *
	 * @return mixed
	 * @author Alexandre Sadowski
	 */
	public function bea_csf_client_posttype_merge( $data, $sync_fields, $new_post ) {
		$has_language = pll_get_post_language( $new_post->ID );
		if ( ! empty( $has_language ) ) {
			return $data;
		}
		
		$default_lang = pll_default_language( 'slug' );
		pll_set_post_language( $new_post->ID, $default_lang );

		return $data;
	}

	/**
	 * Set default language on synched terms
	 *
	 * @param $new_term_id
	 * @param $sync_fields
	 *
	 * @return mixed
	 * @author Alexandre Sadowski
	 */
	public function bea_csf_client_taxo_merge( $new_term_id, $sync_fields ) {
		$has_language = pll_get_term_language( $new_term_id );
		if ( ! empty( $has_language ) ) {
			return $new_term_id;
		}
		$default_lang = pll_default_language( 'slug' );
		pll_set_post_language( $new_term_id, $default_lang );

		return $new_term_id;
	}

	/**
	 * No sync contents who has not default language
	 *
	 * @param $_post
	 * @param $sync_fields
	 *
	 * @return bool
	 * @author Alexandre Sadowski
	 */
	public function bea_csf_server_posttype_no_sync_en( $_post, $sync_fields ) {
		$has_language = pll_get_post_language( $_post['ID'] );
		if ( empty( $has_language ) ) {
			return $_post;
		}

		$default_lang = pll_default_language( 'slug' );
		if ( $default_lang === $has_language ) {
			return $_post;
		}

		return false;
	}

	/**
	 * No sync contents who has not default language
	 *
	 * @param $term
	 * @param $sync_fields
	 *
	 * @return bool
	 * @author Alexandre Sadowski
	 */
	public function bea_csf_server_term_no_sync_en( $term, $sync_fields ) {
		$has_language = pll_get_term_language( $term['term_id'] );
		if ( empty( $has_language ) ) {
			return $term;
		}

		$default_lang = pll_default_language( 'slug' );
		if ( $default_lang === $has_language ) {
			return $term;
		}

		return false;
	}
}
