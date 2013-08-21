<?php
/*
Plugin Name: BEA - Content Synchronisation - Fusion
Plugin URI: http://www.beapi.fr
Description: Manage content synchronisation across a WordPress multisite
Version: 1.1
Author: BeAPI
Author URI: http://www.beapi.fr
Depends: meta-for-taxonomies

Copyright 2013 - BeAPI Team (technique@beapi.fr)
*/

// Plugin constants
define( 'BEA_CSF_VERSION', '1.1' );
define( 'BEA_CSF_OPTION', 'bea-content-sync-fusion' );
define( 'BEA_CSF_LOCALE', 'bea-content-sync-fusion' );

// Plugin URL and PATH
define('BEA_CSF_URL', plugin_dir_url ( __FILE__ ));
define('BEA_CSF_DIR', plugin_dir_path( __FILE__ ));

// Library
require (BEA_CSF_DIR . 'inc/server/class.client.php');
require (BEA_CSF_DIR . 'inc/server/class.attachment.php');
require (BEA_CSF_DIR . 'inc/server/class.post_type.php');
require (BEA_CSF_DIR . 'inc/server/class.taxonomy.php');

require (BEA_CSF_DIR . 'inc/client/class.client.php');
require (BEA_CSF_DIR . 'inc/client/class.attachment.php');
require (BEA_CSF_DIR . 'inc/client/class.post_type.php');
require (BEA_CSF_DIR . 'inc/client/class.taxonomy.php');

// Call admin classes
if ( is_admin() ) { 
	require( BEA_CSF_DIR . 'inc/server/class.admin.php' );
	require( BEA_CSF_DIR . 'inc/server/class.admin.metabox.php' );
	
	require( BEA_CSF_DIR . 'inc/client/class.admin.php' );
}

add_action( 'plugins_loaded', 'init_bea_content_sync_fusion' );
function init_bea_content_sync_fusion() {
	// Load translations
	load_plugin_textdomain( BEA_CSF_LOCALE, false, basename(BEA_CSF_DIR) . '/languages');
	
	// Client
	new BEA_CSF_Server_Attachment();
	new BEA_CSF_Server_PostType();
	new BEA_CSF_Server_Taxonomy();
	
	// Admin
	if ( is_admin() ) {
		new BEA_CSF_Server_Admin();
		new BEA_CSF_Server_Admin_Metabox();
		
		new BEA_CSF_Client_Admin();
	}
}