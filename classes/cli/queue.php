<?php
// Bail if WP-CLI is not present
if ( ! defined( 'WP_CLI' ) ) {
	return false;
}

class BEA_CSF_Cli_Queue extends WP_CLI_Command {

	/**
	 * Exec cron by get all blogs with content, and proceed to sync !
	 *
	 * @param $args
	 * @param $params
	 *
	 * @throws \WP_CLI\ExitException
	 */
	public function process( $args, $params ) {
		// Use maintenance queue ?
		if ( isset( $params['alternativeq'] ) && 'true' === $params['alternativeq'] ) {
			BEA_CSF_Async::switch_to_maintenance_queue();
		} else {
			$params['alternativeq'] = 'false';
		}

		// Get blogs ID with content to sync
		$blog_ids = BEA_CSF_Async::get_blog_ids_from_queue();
		if ( empty( $blog_ids ) ) {
			WP_CLI::warning( __( 'No content to synchronize', 'bea-content-sync-fusion' ) );

			return;
		}

		$total = count( $blog_ids );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Loop on site with content to synchronize', $total );
		foreach ( $blog_ids as $blog_id ) {
			WP_CLI::launch_self(
				'content-sync-fusion queue pull',
				array(),
				array(
					'alternativeq' => $params['alternativeq'],
					'url'          => get_home_url( $blog_id, '/' ),
				),
				false,
				false // Allow debug with this value to true
			);

			$progress->tick();
		}
		$progress->finish();
	}

	public function status( $args, $params ) {
		// Use maintenance queue ?
		if ( isset( $params['alternativeq'] ) && 'true' === $params['alternativeq'] ) {
			BEA_CSF_Async::switch_to_maintenance_queue();
		}

		// Get all blogs with content
		$blog_ids = BEA_CSF_Async::get_blog_ids_from_queue();

		$results = array();
		foreach ( $blog_ids as $blog_id ) {
			$results[] = array(
				'blog_id' => $blog_id,
				'counter' => BEA_CSF_Async::get_counter( $blog_id ),
			);
		}
		WP_CLI\Utils\format_items( 'table', $results, array( 'blog_id', 'counter' ) );

		WP_CLI::success( sprintf( __( '%d items waiting on the queue', 'bea-content-sync-fusion' ), BEA_CSF_Async::get_counter() ) );
	}

	public function flush( $args, $params ) {
		// Use maintenance queue ?
		if ( isset( $params['alternativeq'] ) && 'true' === $params['alternativeq'] ) {
			BEA_CSF_Async::switch_to_maintenance_queue();
		}

		BEA_CSF_Async::truncate();
		WP_CLI::success( __( 'Queue flushed with success !', 'bea-content-sync-fusion' ) );
	}

	public function pull( $args, $params ) {
		// Use maintenance queue ?
		if ( isset( $params['alternativeq'] ) && 'true' === $params['alternativeq'] ) {
			BEA_CSF_Async::switch_to_maintenance_queue();
		}

		// Allow override quantity with CLI param
		$quantity = BEA_CSF_CRON_QTY;
		if ( isset( $params['quantity'] ) && intval( $params['quantity'] ) > 0 ) {
			$quantity = intval( $params['quantity'] );
		}

		// Get data to sync
		$items_to_sync = BEA_CSF_Async::get_results( $quantity, get_current_blog_id() );

		if ( empty( $items_to_sync ) ) {
			WP_CLI::warning( __( 'No content to synchronize', 'bea-content-sync-fusion' ) );

			return;
		}

		$total = count( $items_to_sync );

		WP_CLI::success( __( 'Start of content synchronization', 'bea-content-sync-fusion' ) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Loop on content to synchronize', $total );
		foreach ( $items_to_sync as $item_to_sync ) {
			BEA_CSF_Async::process( $item_to_sync );

			$progress->tick();
		}
		$progress->finish();

		WP_CLI::success( __( 'End of content synchronization', 'bea-content-sync-fusion' ) );

		WP_CLI::run_command( array( 'cache', 'flush' ) );
	}

	/**
	 * Get all blogs "url" with content to synchronized
	 *
	 * @param $args
	 * @param $params
	 */
	public function get_sites( $args, $params ) {
		// Use maintenance queue ?
		if ( isset( $params['alternativeq'] ) && 'true' === $params['alternativeq'] ) {
			BEA_CSF_Async::switch_to_maintenance_queue();
		} else {
			$params['alternativeq'] = 'false';
		}

		// Get blogs ID with content to sync
		$blog_ids = BEA_CSF_Async::get_blog_ids_from_queue();
		if ( empty( $blog_ids ) ) {
			return;
		}

		foreach ( $blog_ids as $blog_id ) {
			WP_CLI::line( get_home_url( $blog_id, '/' ) );
		}
	}
}

WP_CLI::add_command( 'content-sync-fusion queue', 'BEA_CSF_Cli_Queue', array(
	'shortdesc' => __( 'All commands related "queue features" to the BEA Content Sync Fusion plugin', 'bea-content-sync-fusion' ),
) );