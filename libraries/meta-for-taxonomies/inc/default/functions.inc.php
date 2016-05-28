<?php
/**
 * This function is called when the plugin is activated, it allow to create the SQL table.
 *
 * @return void
 * @author Amaury Balmer
 */
function install_table_termmeta() {
	global $wpdb;

	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	}
	if ( ! empty( $wpdb->collate ) ) {
		$charset_collate .= " COLLATE $wpdb->collate";
	}

	// Add one library admin function for next function
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	// Try to create the meta table
	return maybe_create_table( $wpdb->term_taxometa, "CREATE TABLE " . $wpdb->term_taxometa . " (
			`meta_id` int(20) NOT NULL auto_increment,
			`term_taxo_id` INT( 20 ) NOT NULL ,
			`meta_key` VARCHAR( 255 ) NOT NULL ,
			`meta_value` LONGTEXT NOT NULL,
			PRIMARY KEY  (`meta_id`),
			KEY `term_taxo_id` (`term_taxo_id`),
			KEY `meta_key` (`meta_key`)
		) $charset_collate;" );
}