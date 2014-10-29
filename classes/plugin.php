<?php
class BEA_CSF_Plugin {

	public static function activate() {
		// Call function activation of Meta for Taxonomies plugin
		if ( function_exists('install_table_termmeta') ) {
			install_table_termmeta();
		}
	}

	public static function deactivate() {
		
	}
	
	public static function wpmu_new_blog() {
		// Call function activation of Meta for Taxonomies plugin
		if ( function_exists('install_table_termmeta') ) {
			install_table_termmeta();
		}
	}

	/**
	 * Get post ID from post meta with meta_key and meta_value
	 */
	public static function get_post_id_from_meta( $key, $value ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $key, $value ) );
	}

}