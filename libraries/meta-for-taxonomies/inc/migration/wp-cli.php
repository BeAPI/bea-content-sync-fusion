<?php
// Bail if WP-CLI is not present
if ( !defined( 'WP_CLI' ) ) {
	return false;
}

class MFT_CLI extends WP_CLI_Command {

	/**
	 * Launch migration with no limitations
	 *
	 *## OPTIONS
	 *
	 * <number>
	 * : the number of terms to do. Apply -1 to have unlimited
	 *
	 * ## EXAMPLES
	 *
	 *  wp mft migrate
	 *
	 * @synopsis
	 */
	function migrate( $args ) {
		global $wpdb;

		if( (bool)get_option( 'finished_splitting_shared_terms', false ) === false ) {
			WP_CLI::line( 'Terms splitting not finished' );
			return;
		}

		$number = isset( $args[0] ) ? (int)$args[0] : 100;
		$limit = $number <= 0 ? '' : ' LIMIT 0, '.$number ;

		// Update the lock, as by this point we've definitely got a lock, just need to fire the actions.
		update_option( MFT_Migration::LOCK_NAME, time() );

		/**
		 * Register the meta tables
		 */
		_mft_maybe_register_taxometa_table();

		/**
		 * Get all the terms to migrate
		 */
		$terms_metas = _mft_migrate_get_terms( $limit );

		WP_CLI::line( 'Start migration' );
		WP_CLI::line( sprintf( '%d meta selected', count( $terms_metas ) ) );

		// No more terms, we're done here.
		if ( ! $terms_metas ) {
			WP_CLI::line( 'Stop migration, no term metas' );
			update_option( 'finished_migrating_terms_metas', true );

			delete_option( MFT_Migration::LOCK_NAME );
			return;
		}

		/**
		 * Migrate the terms
		 */
		$results = _mft_migrate_terms( $terms_metas );
		WP_CLI::line( sprintf( 'Failed %s | deleted %s', count( $results[ 'failed' ] ), $results[ 'deleted' ] ) );

		WP_CLI::line( 'Migration end' );

		delete_option( MFT_Migration::LOCK_NAME );
	}
}
WP_CLI::add_command( 'mft', 'MFT_CLI' );