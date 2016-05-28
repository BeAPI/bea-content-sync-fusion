<?php
/*
  Plugin Name: BEA - Content Synchronization - Fusion
  Plugin URI: http://www.beapi.fr
  Description: Manage content synchronization across a WordPress multisite
  Version: 1.1
  Author: BeAPI
  Author URI: http://www.beapi.fr
  Network: true

  Copyright 2013 - BeAPI Team (technique@beapi.fr)
  
  TODO : 
	Mirror mode (deleting inclusion)
	Unlink relation from receivers
	AJAX Taxo for Sync edition
 */

// Plugin constants
define( 'BEA_CSF_VERSION', '1.1' );
define( 'BEA_CSF_OPTION', 'bea-content-sync-fusion' );
define( 'BEA_CSF_LOCALE', 'bea-content-sync-fusion' );

// Define the table relation variables
if ( empty( $GLOBALS['wpdb']->bea_csf_relations ) ) {
	$GLOBALS['wpdb']->bea_csf_relations  = $GLOBALS['wpdb']->base_prefix . 'bea_csf_relations';
	$GLOBALS['wpdb']->ms_global_tables[] = 'bea_csf_relations';
}

// Tables for ASYNC
// Define the table queue variables
if ( empty( $GLOBALS['wpdb']->bea_csf_queue ) ) {
	$GLOBALS['wpdb']->bea_csf_queue      = $GLOBALS['wpdb']->base_prefix . 'bea_csf_queue';
	$GLOBALS['wpdb']->ms_global_tables[] = 'bea_csf_queue';
}

if ( empty( $GLOBALS['wpdb']->bea_csf_queue_maintenance ) ) {
	$GLOBALS['wpdb']->bea_csf_queue_maintenance      = $GLOBALS['wpdb']->base_prefix . 'bea_csf_queue_maintenance';
	$GLOBALS['wpdb']->ms_global_tables[] = 'bea_csf_queue_maintenance';
}

// Plugin URL and PATH
define( 'BEA_CSF_URL', plugin_dir_url( __FILE__ ) );
define( 'BEA_CSF_DIR', plugin_dir_path( __FILE__ ) );

// Plugin various
require( BEA_CSF_DIR . 'classes/plugin.php' );
require( BEA_CSF_DIR . 'classes/client.php' );

// Functions various
require( BEA_CSF_DIR . 'functions/api.php' );
require( BEA_CSF_DIR . 'functions/template.php' );

// Models
require( BEA_CSF_DIR . 'classes/models/async.php' );
require( BEA_CSF_DIR . 'classes/models/relations.php' );
require( BEA_CSF_DIR . 'classes/models/synchronization.php' );
require( BEA_CSF_DIR . 'classes/models/synchronizations.php' );

// Library server
require( BEA_CSF_DIR . 'classes/server/attachment.php' );
require( BEA_CSF_DIR . 'classes/server/post_type.php' );
require( BEA_CSF_DIR . 'classes/server/taxonomy.php' );
// require( BEA_CSF_DIR . 'classes/server/p2p.php' );

// Library client
require( BEA_CSF_DIR . 'classes/client/attachment.php' );
require( BEA_CSF_DIR . 'classes/client/post_type.php' );
require( BEA_CSF_DIR . 'classes/client/taxonomy.php' );
// require( BEA_CSF_DIR . 'classes/client/p2p.php' );

// Call admin classes
if ( is_admin() ) {
	require( BEA_CSF_DIR . 'classes/admin/admin-synchronizations-network.php' );
	require( BEA_CSF_DIR . 'classes/admin/admin-metaboxes.php' );
	require( BEA_CSF_DIR . 'classes/admin/admin-restrictions.php' );
	require( BEA_CSF_DIR . 'classes/admin/admin-notifications.php' );
	require( BEA_CSF_DIR . 'classes/admin/admin-terms.php' );
	require( BEA_CSF_DIR . 'classes/admin/admin-terms-metaboxes.php' );
}

// Load builtin plugin "meta for taxo", if not already installed and actived
if ( ! function_exists( 'get_term_taxonomy_meta' ) ) {
	require( BEA_CSF_DIR . 'libraries/meta-for-taxonomies/meta-for-taxonomies.php' );
}

// Plugin activate/desactive hooks
register_activation_hook( __FILE__, array( 'BEA_CSF_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BEA_CSF_Plugin', 'deactivate' ) );
add_action( 'wpmu_new_blog', array( 'BEA_CSF_Plugin', 'wpmu_new_blog' ) );

// Init !
add_action( 'plugins_loaded', 'init_bea_content_sync_fusion' );
function init_bea_content_sync_fusion() {
	// Load translations
	load_plugin_textdomain( BEA_CSF_LOCALE, false, basename( BEA_CSF_DIR ) . '/languages' );

	// Synchronizations
	BEA_CSF_Synchronizations::init_from_db();

	// Server
	new BEA_CSF_Client();

	// Admin
	if ( is_admin() ) {
		new BEA_CSF_Admin_Synchronizations_Network();
		new BEA_CSF_Admin_Metaboxes();
		new BEA_CSF_Admin_Restrictions();
		new BEA_CSF_Admin_Notifications();
		new BEA_CSF_Admin_Terms();
		new BEA_CSF_Admin_Terms_Metaboxes();
	}
}