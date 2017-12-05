<?php
// Bail if WP-CLI is not present
if ( ! defined( 'WP_CLI' ) ) {
	return false;
}

class BEA_CSF_Cli_Resync extends WP_CLI_Command {

	/**
	 * Flush all contents synchronized
	 *
	 * @param $args
	 * @param $params
	 *
	 * @throws \WP_CLI\ExitException
	 */
	public function all( $args, $params ) {
		// Use maintenance queue ?
		if ( isset( $params['alternativeq'] ) && $params['alternativeq'] === 'true' ) {
			BEA_CSF_Async::switch_to_maintenance_queue();
		} else {
			$params['alternativeq'] = 'false';
		}

		// Optionally params
		$params['attachments'] = ! isset( $params['attachments'] ) ? 'false' : $params['attachments'];
		$params['post_type']   = ! isset( $params['post_type'] ) ? 'false' : $params['post_type'];
		$params['taxonomies']  = ! isset( $params['taxonomies'] ) ? 'false' : $params['taxonomies'];
		$params['p2p']         = ! isset( $params['p2p'] ) ? 'false' : $params['p2p'];

		// Default WP_Site_Query arguments
		$args = array(
			'public'  => '1',
			'number'  => PHP_INT_MAX,
			'order'   => 'ASC',
			'orderby' => 'id',
			'count'   => false,
			'fields'  => 'ids',
		);

		// Restrict to some emitters ?
		if ( isset( $params['emitters'] ) ) {
			$args['site__in'] = explode( ',', $params['emitters'] );
		}

		// Restrict to some networks ?
		if ( isset( $params['emitters_network'] ) ) {
			$args['network__in'] = explode( ',', $params['emitters_network'] );
		}

		// Get blogs ID to resync
		$site_query = new WP_Site_Query( $args );
		if ( empty( $site_query->sites ) ) {
			WP_CLI::error( __( 'No site to resync', 'bea-content-sync-fusion' ) );
		}

		$progress = \WP_CLI\Utils\make_progress_bar( 'Loop on site with content to resync', $site_query->found_sites );
		foreach ( $site_query->sites as $blog_id ) {

			WP_CLI::launch_self(
				'content-sync-fusion resync site',
				array(),
				array(
					'alternativeq' => $params['alternativeq'],
					'attachments'  => $params['attachments'],
					'post_type'    => $params['post_type'],
					'taxonomies'   => $params['taxonomies'],
					'p2p'          => $params['p2p'],
					'url'          => get_home_url( $blog_id, '/' ),
				),
				false,
				false
			);

			$progress->tick();
		}
		$progress->finish();
	}

	/**
	 * Flush all contents synchronized for a specific blog_id
	 *
	 * @param $args
	 * @param $params
	 */
	public function site( $args, $params ) {
		// Use maintenance queue ?
		if ( isset( $params['alternativeq'] ) && $params['alternativeq'] === 'true' ) {
			BEA_CSF_Async::switch_to_maintenance_queue();
		}

		// Get data to resync
		$data_to_sync = array();

		// Get terms with params argument
		if ( ! isset( $params['taxonomies'] ) || $params['taxonomies'] === 'false' ) {
			$data_to_sync['terms'] = array();
		} else {
			// TODO: Manage "any" and filtering
			$data_to_sync['terms'] = $this->get_terms();
		}

		// Get attachments with params argument
		if ( ! isset( $params['attachments'] ) || $params['attachments'] === 'false' ) {
			$data_to_sync['attachments'] = array();
		} else {
			// TODO: Manage "any" and filtering
			$data_to_sync['attachments'] = $this->get_attachments();
		}

		// Get posts with params argument
		if ( ! isset( $params['post_type'] ) || $params['post_type'] === 'false' ) {
			$data_to_sync['posts'] = array();
		} else {
			// TODO: Manage "any" and filtering
			$data_to_sync['posts'] = $this->get_posts();
		}

		// Get P2P with params argument
		if ( ! isset( $params['p2p'] ) || $params['p2p'] === 'false' ) {
			$data_to_sync['p2p'] = array();
		} else {
			// TODO: Manage "any" and filtering
			$data_to_sync['p2p'] = $this->get_p2p_connections();
		}

		// Make a mega-count
		$total = count( $data_to_sync['terms'] ) + count( $data_to_sync['attachments'] ) + count( $data_to_sync['posts'] + count( $data_to_sync['p2p'] ) );

		// No item ?
		if ( $total == false ) {
			WP_CLI::error( __( 'No content to resync', 'bea-content-sync-fusion' ) );
		}

		WP_CLI::success( __( 'Start of content resyncing', 'bea-content-sync-fusion' ) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Loop on content to resync', $total );

		// Loop on terms
		foreach ( (array) $data_to_sync['terms'] as $result ) {
			if ( ! isset( $result->term_id ) ) {
				continue;
			}

			do_action( 'edited_term', $result->term_id, $result->term_taxonomy_id, $result->taxonomy );

			$progress->tick();
		}

		// Loop on attachments
		foreach ( (array) $data_to_sync['attachments'] as $result ) {
			if ( ! is_a( $result, 'WP_Post' ) ) {
				continue;
			}

			do_action( 'edit_attachment', $result->ID );

			$progress->tick();
		}

		// Loop on posts
		foreach ( $data_to_sync['posts'] as $result ) {
			if ( ! is_a( $result, 'WP_Post' ) ) {
				continue;
			}

			do_action( 'transition_post_status', $result->post_status, $result->post_status, $result );
			do_action( 'save_post', $result->ID, $result, true );

			$progress->tick();
		}

		// Loop on P2P
		foreach ( $data_to_sync['p2p'] as $result_id ) {
			do_action( 'p2p_created_connection', $result_id );

			$progress->tick();
		}


		$progress->finish();

		WP_CLI::success( __( 'End of content resync', 'bea-content-sync-fusion' ) );

		WP_CLI::run_command( array( 'cache', 'flush' ) );
	}

	/**
	 *
	 * Synchronization all terms from any taxinomies
	 *
	 * @param array $args
	 * @param array $terms_args
	 *
	 * @return bool
	 */
	public function get_terms( $args = array(), $terms_args = array() ) {
		$args = wp_parse_args( $args, array() );

		// Get taxonomies names only
		$taxonomies = get_taxonomies( $args, 'names' );
		if ( empty( $taxonomies ) ) {
			WP_CLI::debug( "No taxinomies found" );

			return false;
		}

		// Get terms objects
		$terms_args = wp_parse_args( $terms_args, array( 'hide_empty' => false ) );
		$results    = get_terms( array_keys( $taxonomies ), $terms_args );
		if ( is_wp_error( $results ) || empty( $results ) ) {
			WP_CLI::debug( "No terms found for taxonomies : %s", implode( ',', array_keys( $taxonomies ) ) );

			return false;
		}

		return $results;
	}

	/**
	 *
	 * Get all attachments
	 *
	 * @param array $args
	 *
	 * @return false|array
	 */
	public function get_attachments( $args = array() ) {
		$default_args = array(
			'post_type'              => 'attachment',
			'post_status'            => 'any',
			'nopaging'               => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
			'cache_results'          => false
		);

		$args    = wp_parse_args( $args, $default_args );
		$results = get_posts( $args );
		if ( empty( $results ) ) {
			WP_CLI::debug( "No attachment found\n" );

			return false;
		}

		return $results;
	}

	/**
	 *
	 * Synchronization all posts from any post types
	 *
	 * @param array $args
	 *
	 * @return false|array
	 */
	public function get_posts( $args = array() ) {
		global $wp_post_types;

		$default_args = array(
			'post_type'              => array_keys( $wp_post_types ),
			'post_status'            => 'any',
			'nopaging'               => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
			'cache_results'          => false
		);

		$args    = wp_parse_args( $args, $default_args );
		$results = get_posts( $args );
		if ( empty( $results ) ) {
			WP_CLI::debug( "No posts found for post_type %s\n", $args['post_type'] );

			return false;
		}

		return $results;
	}

	/**
	 *
	 * Synchronization all P2P connections
	 *
	 * @param array $args
	 *
	 * @return bool|array
	 */
	public function get_p2p_connections( $args = array() ) {
		global $wpdb;

		// $args = wp_parse_args( $args, array() ); // TODO: Implement P2P restriction query

		$results = (array) $wpdb->get_col( "SELECT p2p_id FROM $wpdb->p2p" );
		if ( empty( $results ) ) {
			WP_CLI( "No P2P connection found" );

			return false;
		}

		return $results;
	}

}

WP_CLI::add_command( 'content-sync-fusion resync', 'BEA_CSF_Cli_Resync', array(
	'shortdesc' => __( 'All commands related "resync features" to the BEA Content Sync Fusion plugin', 'bea-content-sync-fusion' ),
) );