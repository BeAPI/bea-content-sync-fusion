<?php

class BEA_CSF_Plugin {

	public static function activate() {
		self::create_relations_table();

		// Create table for queue
		self::create_queue_table();

		// Create table for queue maintenance
		self::create_queue_table_maintenance();
	}

	public static function create_relations_table() {
		global $wpdb;

		$schema = "CREATE TABLE {$wpdb->bea_csf_relations} (
            `id` INT(20) NOT NULL AUTO_INCREMENT ,
            `type` VARCHAR(255) NOT NULL , 
            `emitter_blog_id` INT(20) NOT NULL , 
            `emitter_id` INT(20) NOT NULL , 
            `receiver_blog_id` INT(20) NOT NULL , 
            `receiver_id` INT(20) NOT NULL , 
            `custom_flag` BOOLEAN NOT NULL , 
            `custom_fields` TEXT NOT NULL,
            PRIMARY KEY (id), 
            UNIQUE KEY `type_emitter_receiver` (`type`(191), `emitter_blog_id`, `emitter_id`, `receiver_blog_id`, `receiver_id`)
        );";

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Ensure we always try to create this, regardless of whether we're on the
		// main site or not. dbDelta will skip creation of global tables on
		// non-main sites.
		$offset = array_search( 'bea_csf_relations', $wpdb->ms_global_tables, true );
		if ( ! empty( $offset ) ) {
			unset( $wpdb->ms_global_tables[ $offset ] );
		}
		$result                   = dbDelta( $schema );
		$wpdb->ms_global_tables[] = 'bea_csf_relations';

		if ( empty( $result ) ) {
			// No changes, database already exists and is up-to-date
			return 'exists';
		}

		return 'created';
	}

	public static function create_queue_table() {
		global $wpdb;

		$schema = "CREATE TABLE {$wpdb->bea_csf_queue} (
            `id` BIGINT(20) NOT NULL auto_increment,
            `hook_data` TEXT NOT NULL,
            `current_filter` TEXT NOT NULL,
            `receiver_blog_id` BIGINT(20),
            `fields` TEXT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY `unicity_key` (`hook_data`(191),`current_filter`(191),`receiver_blog_id`)
        );";

		/**
		 * Indexes for table `wp_bea_csf_queue`
		 * ALTER TABLE `wp_bea_csf_queue` ADD UNIQUE KEY `unicity_key` (`hook_data`(255),`current_filter`(255),`receiver_blog_id`,`fields`(255));
		 **/
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Ensure we always try to create this, regardless of whether we're on the
		// main site or not. dbDelta will skip creation of global tables on
		// non-main sites.
		$offset = array_search( 'bea_csf_queue', $wpdb->ms_global_tables, true );
		if ( ! empty( $offset ) ) {
			unset( $wpdb->ms_global_tables[ $offset ] );
		}
		$result                   = dbDelta( $schema );
		$wpdb->ms_global_tables[] = 'bea_csf_queue';

		if ( empty( $result ) ) {
			// No changes, database already exists and is up-to-date
			return 'exists';
		}

		return 'created';
	}

	public static function create_queue_table_maintenance() {
		global $wpdb;

		$schema = "CREATE TABLE {$wpdb->bea_csf_queue_maintenance} (
            `id` BIGINT(20) NOT NULL auto_increment,
            `hook_data` TEXT NOT NULL,
            `current_filter` TEXT NOT NULL,
            `receiver_blog_id` BIGINT(20),
            `fields` TEXT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY `unicity_key` (`hook_data`(191),`current_filter`(191),`receiver_blog_id`)
        );";

		/**
		 * Indexes for table `wp_bea_csf_queue_maintenance`
		 * ALTER TABLE `wp_bea_csf_queue_maintenance` ADD UNIQUE KEY `unicity_key` (`hook_data`(255),`current_filter`(255),`receiver_blog_id`,`fields`(255));
		 **/

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Ensure we always try to create this, regardless of whether we're on the
		// main site or not. dbDelta will skip creation of global tables on
		// non-main sites.
		$offset = array_search( 'bea_csf_queue_maintenance', $wpdb->ms_global_tables, true );
		if ( ! empty( $offset ) ) {
			unset( $wpdb->ms_global_tables[ $offset ] );
		}
		$result                   = dbDelta( $schema );
		$wpdb->ms_global_tables[] = 'bea_csf_queue_maintenance';

		if ( empty( $result ) ) {
			// No changes, database already exists and is up-to-date
			return 'exists';
		}

		return 'created';
	}

	public static function deactivate() {
	}

	/**
	 * Get post ID from post meta with meta_key and meta_value
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return int
	 */
	public static function get_post_id_from_meta( $key, $value ) {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $key, $value ) );
	}
}
