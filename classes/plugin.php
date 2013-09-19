<?php
class BEA_CSF_Plugin {

	public static function activate() {
		global $wpdb;

		if ( !empty( $wpdb->charset ) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( !empty( $wpdb->collate ) )
			$charset_collate .= " COLLATE $wpdb->collate";

		// Add one library admin function for next function
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	}

	public static function deactivate() {
		
	}

}