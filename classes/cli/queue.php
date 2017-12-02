<?php
// Bail if WP-CLI is not present
if ( ! defined( 'WP_CLI' ) ) {
    return false;
}

class BEA_CSF_Cli_Queue extends WP_CLI_Command {

    /**
     * @param $args
     * @param $params
     * @return bool
     */
    public function process( $args, $params ) {
        // Use maintenance queue ?
        if ( isset($params['alternativeq']) && $params['alternativeq'] === 'true' ) {
            BEA_CSF_Async::switch_to_maintenance_queue();
        }

        // Get blogs ID with content to sync
        $blog_ids = BEA_CSF_Async::get_blog_ids_from_queue();
        if ( empty($blog_ids) ) {
            WP_CLI::error( __('No content to synchronize', 'bea-content-sync-fusion') );
        }

        $total_blog = count($blog_ids);

        $progress = \WP_CLI\Utils\make_progress_bar( 'Loop on blog with content to synchronize', $total_blog );
        foreach ( $blog_ids as $blog_id ) {
            WP_CLI::launch_self(
                'content-sync-fusion queue pull',
                array(),
                array(
                    'url' => get_home_url( $blog_id, '/'),
                ),
                false,
                false
            );

            $progress->tick();
        }
        $progress->finish();

        WP_CLI::run_command( array( 'cache', 'flush') );
    }

    public function status( $args, $params ) {

    }

    public function flush( $args, $params ) {

    }

    public function pull( $args, $params ) {
        WP_CLI::success( __('Start of content synchronization', 'bea-content-sync-fusion')  );
        // WP_CLI::success(  BEA_CSF_CRON_QTY );
        var_dump(get_current_blog_id());
        BEA_CSF_Async::process_queue( BEA_CSF_CRON_QTY, get_current_blog_id() );

        WP_CLI::success( __('End of content synchronization', 'bea-content-sync-fusion')  );
    }

}

WP_CLI::add_command( 'content-sync-fusion queue', 'BEA_CSF_Cli_Queue', array(
    'shortdesc' => __('All commands related to the BEA Content Sync Fusion plugin', 'bea-content-sync-fusion'),
) );