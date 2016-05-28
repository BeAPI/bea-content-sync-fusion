<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class MFT_Migration {

	const LOCK_NAME = 'mft_term_metas.lock';


	/**
	 * MFT_Migration constructor.
	 */
	public function __construct() {

		if( self::is_finished() ) {
			return;
		}
		/**
		 * Handle the possibility of not having finished the job but you need to fully return the
		 * values of the term metas.
		 */
		add_filter( 'get_term_metadata', array( __CLASS__, 'get_term_metadata' ), 10, 4 );

		if( ! self::can_launch_next() ) {
			return;
		}

		/**
		 * Schedule the next element
		 */
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'mft_migrate_term_metas_batch' );
	}

	/**
	 * Handle the values between the updates
	 *
	 * @param $value
	 * @param $object_id
	 * @param $meta_key
	 * @param $single
	 *
	 * @return mixed|null
	 */
	public static function get_term_metadata( $value, $object_id, $meta_key, $single ) {
		_mft_maybe_register_taxometa_table();
		$backcompat = get_metadata( 'term_taxo', $object_id, $meta_key, $single );
		return empty( $backcompat ) ? null : $backcompat;
	}

	public static function is_finished() {
		/**
		 * Bootstrap terms metas migration.
		 */
		$finished_split_terms = (bool)get_option( 'finished_splitting_shared_terms', false );
		$finished_migrating_terms_metas = (bool)get_option( 'finished_migrating_terms_metas', false );

		// We have to wait for wp split terms completion
		if ( false === $finished_split_terms || false === $finished_migrating_terms_metas ) {
			return false;
		}

		return true;
	}

	public static function can_launch_next() {
		$finished_migrating_terms_metas = (bool)get_option( 'finished_migrating_terms_metas', false );
		// Avoid rescheduling our cron
		if ( true === $finished_migrating_terms_metas && wp_next_scheduled( 'mft_migrate_term_metas_batch' ) ) {
			return false;
		}

		return true;
	}
}

new MFT_Migration();