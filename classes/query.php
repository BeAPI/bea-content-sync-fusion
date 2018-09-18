<?php

class BEA_CSF_Query {

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		add_action( 'query_vars', array( __CLASS__, 'query_vars' ), 10 );
		add_filter( 'posts_join', array( __CLASS__, 'posts_join' ), 10, 2 );
		add_filter( 'posts_where', array( __CLASS__, 'posts_where' ), 10, 2 );
	}

	/**
	 * Append a custom query var for this new filter on WP_Query
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public static function query_vars( $vars ) {
		$vars[] = 'bea_csf_filter';

		return $vars;
	}

	/**
	 * Add join with relations tables for filter local or remote media
	 *
	 * @param $join
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	public static function posts_join( $join, WP_Query $query ) {
		global $wpdb;

		if ( empty( $query->get( 'bea_csf_filter' ) ) ) {
			return $join;
		}

		$join_type = $query->get( 'bea_csf_filter' ) === 'local-only' ? 'LEFT' : 'INNER';

		$join .= " $join_type JOIN $wpdb->bea_csf_relations AS bcr ON ( $wpdb->posts.ID = bcr.receiver_id AND bcr.receiver_blog_id = " . get_current_blog_id() . ' ) ';

		return $join;
	}

	/**
	 * Add join with relations tables for filter local
	 *
	 * @param $where
	 * @param WP_Query $query
	 *
	 * @return string
	 */
	public static function posts_where( $where, WP_Query $query ) {
		if ( empty( $query->get( 'bea_csf_filter' ) ) || $query->get( 'bea_csf_filter' ) !== 'local-only' ) {
			return $where;
		}

		$where .= ' AND bcr.receiver_id IS NULL ';

		return $where;
	}
}

