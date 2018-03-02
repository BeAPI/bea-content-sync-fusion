<?php

/*
 Plugin Name: BEA - Content Sync Fusion
 Version: 3.1-beta
 Plugin URI: https://github.com/BeAPI/bea-content-sync-fusion
 Description: Synchronize contents across your Multisite.
 Author: Be API Technical team
 Author URI: https://beapi.fr
 Domain Path: languages
 Text Domain: bea-content-sync-fusion
 Contributors: Amaury Balmer, Alexandre Sadowski, Clément Boirie, Maxime Culea
 Network: true

 ----
 Copyright 2018 Be API Technical team (human@beapi.fr)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

// TODO : AJAX Taxo for Sync edition

// Plugin constants
define( 'BEA_CSF_VERSION', '3.2-beta' );
define( 'BEA_CSF_OPTION', 'bea-content-sync-fusion' );
define( 'BEA_CSF_CRON_QTY', 500 );

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
require( BEA_CSF_DIR . 'classes/multisite.php' );
require( BEA_CSF_DIR . 'classes/media.php' );
require( BEA_CSF_DIR . 'classes/seo.php' );

// CLI
require( BEA_CSF_DIR . 'classes/cli/queue.php' );
require( BEA_CSF_DIR . 'classes/cli/flush.php' );
require( BEA_CSF_DIR . 'classes/cli/resync.php' );

// Plugins addons
require( BEA_CSF_DIR . 'classes/addons/post-types-order.php' );
require( BEA_CSF_DIR . 'classes/addons/advanced-custom-fields.php' );
require( BEA_CSF_DIR . 'classes/addons/advanced-custom-fields-exclusion.php' );
require( BEA_CSF_DIR . 'classes/addons/events-calendar-series.php' );
require( BEA_CSF_DIR . 'classes/addons/revisionize.php' );

// Functions various
require( BEA_CSF_DIR . 'functions/api.php' );

// Models
require( BEA_CSF_DIR . 'classes/models/async.php' );
require( BEA_CSF_DIR . 'classes/models/relations.php' );
require( BEA_CSF_DIR . 'classes/models/synchronization.php' );
require( BEA_CSF_DIR . 'classes/models/synchronizations.php' );

// Library server
require( BEA_CSF_DIR . 'classes/server/attachment.php' );
require( BEA_CSF_DIR . 'classes/server/post_type.php' );
require( BEA_CSF_DIR . 'classes/server/taxonomy.php' );
require( BEA_CSF_DIR . 'classes/server/p2p.php' );

// Library client
require( BEA_CSF_DIR . 'classes/client/attachment.php' );
require( BEA_CSF_DIR . 'classes/client/post_type.php' );
require( BEA_CSF_DIR . 'classes/client/taxonomy.php' );
require( BEA_CSF_DIR . 'classes/client/p2p.php' );

// Call admin classes
require( BEA_CSF_DIR . 'classes/admin/admin-blog.php' );
require( BEA_CSF_DIR . 'classes/admin/admin-synchronizations-network.php' );
require( BEA_CSF_DIR . 'classes/admin/admin-metaboxes.php' );
require( BEA_CSF_DIR . 'classes/admin/admin-client-metaboxes.php' );
require( BEA_CSF_DIR . 'classes/admin/admin-restrictions.php' );
require( BEA_CSF_DIR . 'classes/admin/admin-terms.php' );
require( BEA_CSF_DIR . 'classes/admin/admin-terms-metaboxes.php' );
require( BEA_CSF_DIR . 'classes/admin/admin-list.php' );
require( BEA_CSF_DIR . 'classes/admin/admin-dashboard-widgets.php' );

// Plugin activate/desactive hooks
register_activation_hook( __FILE__, array( 'BEA_CSF_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BEA_CSF_Plugin', 'deactivate' ) );

// Init !
add_action( 'plugins_loaded', 'init_bea_content_sync_fusion' );
function init_bea_content_sync_fusion() {
	// Load translations
	load_plugin_textdomain( 'bea-content-sync-fusion', false, basename( BEA_CSF_DIR ) . '/languages' );

	// Synchronizations
	BEA_CSF_Synchronizations::init_from_db();

	// Server
	new BEA_CSF_Client();
	new BEA_CSF_Multisite();
	new BEA_CSF_Relations();
	new BEA_CSF_Media();
	new BEA_CSF_SEO();

	// Addons
	new BEA_CSF_Addon_Post_Types_Order();
	new BEA_CSF_Addon_ACF();
	new BEA_CSF_Addon_ACF_Exclusion();
	new BEA_CSF_Addon_Events_Calendar_Series();
	new BEA_CSF_Addon_Revisionize();

	// Admin
	if ( is_admin() ) {
		new BEA_CSF_Admin_Synchronizations_Network();
		new BEA_CSF_Admin_Metaboxes();
		new BEA_CSF_Admin_Client_Metaboxes();
		new BEA_CSF_Admin_Restrictions();
		new BEA_CSF_Admin_Terms();
		new BEA_CSF_Admin_Terms_Metaboxes();
		new BEA_CSF_Admin_Blog();
		new BEA_CSF_Admin_Dashboard_Widgets();
		new BEA_CSF_Admin_List();
	}
}
