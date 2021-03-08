<?php
// Bail if WP-CLI is not present
if ( ! defined( 'WP_CLI' ) ) {
	return false;
}

class BEA_CSF_Cli_Flush extends WP_CLI_Command {

	/**
	 * Get all blogs "url" with content synchronized
	 *
	 * @param $args
	 * @param $params
	 */
	public function get_sites( $args, $params ) {
		// Get blogs ID with content to flush
		$blog_ids = BEA_CSF_Relations::get_all_receiver_blog_ids();
		if ( empty( $blog_ids ) ) {
			return;
		}

		foreach ( $blog_ids as $blog_id ) {
			WP_CLI::line( get_home_url( $blog_id, '/' ) );
		}
	}

	/**
	 * Flush all contents synchronized
	 *
	 * @param $args
	 * @param $params
	 */
	public function all( $args, $params ) {
		// Get blogs ID with contents
		$blog_ids = BEA_CSF_Relations::get_all_receiver_blog_ids();
		if ( empty( $blog_ids ) ) {
			WP_CLI::warning( __( 'No content to flush', 'bea-content-sync-fusion' ) );
			return;
		}

		$total = count( $blog_ids );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Loop on site with content to synchronize', $total );
		foreach ( $blog_ids as $blog_id ) {
			WP_CLI::launch_self(
				'content-sync-fusion flush site',
				array(),
				array(
					'url' => get_home_url( $blog_id, '/' ),
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
		// Get data to delete
		$items_to_delete = BEA_CSF_Relations::get_results_by_receiver_blog_id( get_current_blog_id() );
		if ( empty( $items_to_delete ) ) {
			WP_CLI::warning( __( 'No content to flush', 'bea-content-sync-fusion' ) );
			return;
		}

		$total = count( $items_to_delete );

		WP_CLI::success( __( 'Start of content flushing', 'bea-content-sync-fusion' ) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Loop on content to flush', $total );
		foreach ( $items_to_delete as $relation ) {
			if ( $relation->type === 'taxonomy' ) {
				$term = self::get_term_by_id( $relation->receiver_id );
				if ( $term != false ) {
					wp_delete_term( $term->term_id, $term->taxonomy );
				}
			} else {
				wp_delete_post( $relation->receiver_id, true );
			}

			$progress->tick();
		}
		$progress->finish();

		WP_CLI::success( __( 'End of content flushing', 'bea-content-sync-fusion' ) );

		WP_CLI::run_command( array( 'cache', 'flush' ) );
	}

	/**
	 * Get term without knowing it's taxonomy. Not very nice, though.
	 * Source: https://wordpress.stackexchange.com/questions/23571/get-term-by-id-without-taxonomy
	 *
	 * @uses type $wpdb
	 * @uses get_term()
	 *
	 * @param int|object $term
	 * @param string $output
	 * @param string $filter
	 */
	public static function get_term_by_id( $term, $output = OBJECT, $filter = 'raw' ) {
		global $wpdb;

		if ( empty( $term ) ) {
			$error = new WP_Error( 'invalid_term', __( 'Empty Term' ) );

			return $error;
		}

		$taxonomy = $wpdb->get_var( $wpdb->prepare( "SELECT t.taxonomy FROM $wpdb->term_taxonomy AS t WHERE t.term_id = %s LIMIT 1", $term ) );

		return get_term( $term, $taxonomy, $output, $filter );
	}

}

WP_CLI::add_command(
	'content-sync-fusion flush',
	'BEA_CSF_Cli_Flush',
	array(
		'shortdesc' => __( 'All commands related "flush features" to the BEA Content Sync Fusion plugin', 'bea-content-sync-fusion' ),
	)
);
