<?php
/*
Plugin Name: Meta for Taxonomies
Plugin URI: http://www.beapi.fr
Description: Add table for term taxonomy meta and some methods for use it. Inspiration from core post meta.
Author: Be API
Author URI: http://beapi.fr
Version: 1.3.1

TODO:
	Implement purge cache of term metadata on follow hook : clean_term_cache
*/

/**
 * Before 4.4
 */
if ( ! function_exists( 'get_term_meta' ) ) {

	// 1. Setup table name for term taxonomy meta
	global $wpdb;
	$wpdb->tables[]      = 'term_taxometa';
	$wpdb->term_taxometa = $wpdb->prefix . 'term_taxometa';

	// 2. Library
	require_once( dirname( __FILE__ ) . '/inc/default/functions.meta.php' );
	require_once( dirname( __FILE__ ) . '/inc/default/functions.meta.ext.php' );
	require_once( dirname( __FILE__ ) . '/inc/default/functions.meta.terms.php' );

	// 3. Functions
	require_once( dirname( __FILE__ ) . '/inc/default/functions.hook.php' );
	require_once( dirname( __FILE__ ) . '/inc/default/functions.inc.php' );
	require_once( dirname( __FILE__ ) . '/inc/default/functions.tpl.php' );

	// 4. Meta API hook
	register_activation_hook( __FILE__, 'install_table_termmeta' );
	
	add_action( 'delete_term', 'remove_meta_during_delete', 10, 3 );

} else {
	/**
	 * After 4.4
	 */
	// 1. Migration tools
	require_once( dirname( __FILE__ ) . '/inc/migration/migration.php' );
	require_once( dirname( __FILE__ ) . '/inc/migration/functions.migration.php' );
	require_once( dirname( __FILE__ ) . '/inc/migration/wp-cli.php' );

	// 2. Library
	require_once( dirname( __FILE__ ) . '/inc/compat/functions.meta.php' );
	require_once( dirname( __FILE__ ) . '/inc/compat/functions.meta.ext.php' );
	require_once( dirname( __FILE__ ) . '/inc/compat/functions.meta.terms.php' );

	// 3. Functions
	require_once( dirname( __FILE__ ) . '/inc/compat/functions.tpl.php' );

	if( is_admin() ) {
		require_once( dirname( __FILE__ ) . '/inc/admin.php' );

		new MFT_Admin();
	}

}