<?php
// Bail if WP-CLI is not present
if ( ! defined( 'WP_CLI' ) ) {
	return false;
}

class BEA_CSF_Cli_Migration extends WP_CLI_Command {

	/**
	 * Make a mega query on all sites for get contents synchronized
	 * x
	 * @return array|null|object
	 */
	private function get_blog_ids_with_meta_key() {
		global $wpdb;

		// Get current network
		$current_network = get_network();

		$selects = array();
		foreach ( get_sites(
			array(
				'network_id' => $current_network->id,
				'number' => 99999999,
			)
		) as $blog ) {
			switch_to_blog( $blog->blog_id );

			// Table exists ?
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->postmeta ) ) !== $wpdb->postmeta ) {
				restore_current_blog();
				continue;
			}

			$blog_id = (int) $blog->blog_id; // Ensure the blog_id is an integer
			$meta_key = '_origin_key'; // Define the meta_key explicitly

			// Use prepare to ensure safe query construction
			$selects[] = $wpdb->prepare( "(
		            SELECT pm.post_id AS post_id, pm.meta_value AS meta_value, %d AS blog_id 
		            FROM {$wpdb->postmeta} AS pm
		            WHERE pm.meta_key = %s
		        )", $blog_id, $meta_key );

			restore_current_blog();
		}

		$union_all_query = implode( ' UNION ALL ', $selects );

		return $wpdb->get_results( "SELECT post_id, meta_value, blog_id FROM ( $union_all_query ) AS wp" );
	}

	/**
	 * Exec migration and create relation item from metadata
	 *
	 * @param $args
	 * @param $params
	 */
	public function process( $args, $params ) {
		// Get metadata content to sync
		$meta_blog_ids = $this->get_blog_ids_with_meta_key();
		if ( empty( $meta_blog_ids ) ) {
			WP_CLI::warning( __( 'No meta to migrate', 'bea-content-sync-fusion' ) );
			return;
		}

		$total = count( $meta_blog_ids );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Loop on site with metadata to migrate', $total );
		foreach ( $meta_blog_ids as $meta_blog_id ) {
			$_origin_key = explode( ':', $meta_blog_id->meta_value );

			BEA_CSF_Relations::insert( 'attachment', $_origin_key[0], $_origin_key[1], $meta_blog_id->blog_id, $meta_blog_id->post_id );
			$progress->tick();
		}

		$progress->finish();
	}

	/**
	 * Remove all symoblic links from old version of this plugin
	 */
	public function clean_symbolic_links() {
		$output = shell_exec( sprintf( 'find %s -type l -delete', WP_CONTENT_DIR ) );

		WP_CLI::success( 'Symbolic links flushed - ' . $output );
	}
}

WP_CLI::add_command(
	'content-sync-fusion migration',
	'BEA_CSF_Cli_Migration',
	array(
		'shortdesc' => __( 'Allow to migrate old relation structure (meta data) to relations tables for the BEA Content Sync Fusion plugin', 'bea-content-sync-fusion' ),
	)
);
