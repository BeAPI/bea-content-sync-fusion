<?php
class BEA_CSF_Plugin {
	public static function activate() {
		global $wpdb;

		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";

		// Add one library admin function for next function
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		// Data table
		maybe_create_table( $wpdb->beac_synchronizations, "CREATE TABLE IF NOT EXISTS `{$wpdb->beac_synchronizations}` (
			`id` int(10) NOT NULL AUTO_INCREMENT,
			`active` tinyint(1) NOT NULL,
			`label` varchar(255) NOT NULL,
			`post_type` varchar(255) NOT NULL,
			`mode` varchar(100) NOT NULL,
			`status` varchar(100) NOT NULL,
			`notifications` tinyint(1) NOT NULL,
			PRIMARY KEY (`id`)
			UNIQUE KEY `label` (`label`)
		) $charset_collate AUTO_INCREMENT=1;");
		
		// Data table
		maybe_create_table( $wpdb->bea_csf_synchronizations_blogs, "CREATE TABLE IF NOT EXISTS `{$wpdb->bea_csf_synchronizations_blogs}` (
			`synchronization_id` int(10) NOT NULL,
			`blog_id` int(10) NOT NULL,
			UNIQUE KEY `idx_unique` (`synchronization_id`,`blog_id`)
		) $charset_collate AUTO_INCREMENT=1;");
	}
	
	public static function deactivate() {
		
	}
}