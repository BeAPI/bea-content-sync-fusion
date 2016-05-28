<?php

add_action( 'mft_migrate_term_metas_batch', '_mft_batch_migrate_terms_metas' );
/**
 * Migrate existing meta terms to the new WordPress table
 * Heavily inspired by _wp_batch_split_terms
 *
 * @author Clément Boirie
 */
function _mft_batch_migrate_terms_metas() {
	// Ensure our table is register
	_mft_maybe_register_taxometa_table();

	global $wpdb;

	$lock_name = 'mft_term_metas.lock';

	// Try to lock.
	$lock_result = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO `$wpdb->options` ( `option_name`, `option_value`, `autoload` ) VALUES (%s, %s, 'no') /* LOCK */", $lock_name, time() ) );

	if ( ! $lock_result ) {
		$lock_result = get_option( $lock_name );

		// Bail if we were unable to create a lock, or if the existing lock is still valid.
		if ( ! $lock_result || ( $lock_result > ( time() - HOUR_IN_SECONDS ) ) ) {
			wp_schedule_single_event( time() + ( MINUTE_IN_SECONDS ), 'mft_migrate_term_metas_batch' );

			return;
		}
	}

	// Update the lock, as by this point we've definitely got a lock, just need to fire the actions.
	update_option( $lock_name, time() );

	// Get a list of shared terms (those with more than one associated row in term_taxonomy).
	$terms_metas = _mft_migrate_get_terms();

	// Migrate the terms
	_mft_migrate_terms( $terms_metas );

	// No more terms, we're done here.
	if ( ! $terms_metas ) {
		update_option( 'finished_migrating_terms_metas', true );
		delete_option( $lock_name );
		return;
	}

	// Terms metas found? We'll need to run this script again.
	wp_schedule_single_event( time() + ( MINUTE_IN_SECONDS ), 'mft_migrate_term_metas_batch' );

	delete_option( $lock_name );
}

/**
 * Migrate the terms meta given
 *
 * @param array $terms_meta
 *
 * @return array|mixed|void
 */
function _mft_migrate_terms( $terms_meta ) {
	global $wpdb;

	// Ensure our table is register
	_mft_maybe_register_taxometa_table();

	if( empty( $terms_meta ) ) {
		return array( 'failed' => array(), 'deleted' => 0 );
	}

	$failed_transactions = get_option( 'mft_migrate_fails', array() );
	$previous_failed_transactions_count = count( $failed_transactions );
	$deleted = 0;

	remove_filter( 'get_term_metadata', array( 'MFT_Migration', 'get_term_metadata' ), 10 );

	// Insert metas to the wordpress metas table
	foreach( $terms_meta as $meta ) {

		$update = update_term_meta( $meta->term_id, $meta->meta_key, $meta->meta_value );

		// If something went wrong save the term metas data and continue
		if ( is_wp_error( $update ) || false === $update ) {
			$oops = array(
				'taxonomy' => $meta->taxonomy,
				'term_id' => $meta->term_id,
				'meta_key' => $meta->meta_key,
				'meta_value' => $meta->meta_value,
				'is_wp_error' => is_wp_error( $update ),
				'error' => is_wp_error( $update ) ? $update->get_error_message() : 'false',
			);

			$failed_transactions[] = $oops;
		} else {
			$deleted += $wpdb->delete( $wpdb->term_taxometa, array( 'meta_id' => $meta->meta_id ), array( '%d' ) );
		}
	}

	// Save failed transactions if new ones
	if ( count( $failed_transactions ) !== $previous_failed_transactions_count ) {
		update_option( 'mft_migrate_fails', $failed_transactions );
	}

	return array( 'failed' => $failed_transactions, 'deleted' => $deleted );
}

/**
 * Get all the terms to migrate
 *
 * @param string $limit 100 next by default
 *
 * @return array|null|object
 */
function _mft_migrate_get_terms( $limit = 'LIMIT 100' ) {
	global $wpdb;

	_mft_maybe_register_taxometa_table();

	$limit = esc_sql( $limit );

	return $wpdb->get_results(
		"SELECT ttm.meta_id, tt.taxonomy, tt.term_id, ttm.meta_key, ttm.meta_value
		 FROM {$wpdb->term_taxometa} ttm
		 INNER JOIN {$wpdb->term_taxonomy} tt
		 ON tt.term_taxonomy_id = ttm.term_taxo_id
		 ORDER BY ttm.meta_id
		 $limit;"
	);
}

function mft_get_terms_to_do() {
	global $wpdb;

	_mft_maybe_register_taxometa_table();

	return $wpdb->get_var(
		"SELECT COUNT( term_taxo_id )
		 FROM {$wpdb->term_taxometa};"
	);
}

/**
 * Register legacy metas table
 *
 * @author Clément Boirie
 */
function _mft_maybe_register_taxometa_table() {
	global $wpdb;

	if ( ! isset( $wpdb->term_taxometa ) ) {
		$wpdb->tables[]      = 'term_taxometa';
		$wpdb->term_taxometa = $wpdb->prefix . 'term_taxometa';
	}
}