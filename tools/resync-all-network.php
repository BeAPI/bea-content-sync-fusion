<?php
if ( php_sapi_name() !== 'cli' || isset( $_SERVER['REMOTE_ADDR'] ) ) {
	die( 'CLI Only' );
}

// Get first arg
if ( ! isset( $argv ) || count( $argv ) < 2 ) {
	echo "Missing parameters.\n";
	echo "script usage: php resync-all-network.php [domain] [path]\n";
	die();
}

// User params
$domain = ( isset( $argv[1] ) ) ? $argv[1] : '';
$path   = ( isset( $argv[2] ) ) ? $argv[2] : '/';

// Fake WordPress, build server array
$_SERVER = array(
	'REQUEST_METHOD'  => 'GET',
	'SERVER_PROTOCOL' => 'http/1.1',
	'SERVER_PORT'     => 80,
	'HTTP_HOST'       => $domain,
	'SERVER_NAME'     => $domain,
	'REQUEST_URI'     => $path,
	'SCRIPT_NAME'     => 'index.php',
	'SCRIPT_FILENAME' => 'index.php',
	'PHP_SELF'        => $path . 'index.php',
);

// Force no limit memory and debug
@ini_set( 'memory_limit', - 1 );
@ini_set( 'display_errors', 1 );

// Skip any cache, domain mapping, "SF move login" redirect
define( 'WP_ADMIN', true );
define( 'WP_CACHE', false );
define( 'NO_MAINTENANCE', true );
define( 'SUNRISE_LOADED', 1 );
define( 'SFML_ALLOW_LOGIN_ACCESS', true );

// Load WP and WPadmin also
require( dirname( __FILE__ ) . '/../../../../wp/wp-load.php' );
require_once( ABSPATH . 'wp-admin/includes/admin.php' );

// Force no limit memory and debug
@ini_set( 'memory_limit', - 1 );
@ini_set( 'display_errors', 1 );

// TODO: use WP_Site_Query
// TODO: Implement network limitation
/* @var $wpdb wpdb */
global $wpdb;

// Get all actives blogs
$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE deleted = 0 AND archived = 0 ORDER BY blog_id ASC" );
if ( empty( $blog_ids ) ) {
	print( "No blogs found\n" );
	die();
}
printf( "Found %s blog(s)\n", count( $blog_ids ) );

// Resync all terms for all blog
foreach ( $blog_ids as $_blog_id ) {
	switch_to_blog( $_blog_id );

	printf( "Switch to blog %s\n", $_blog_id );

	// Terms
	BEA_CSF_Multisite::sync_all_terms( array(), array(), true );
	printf( "Finish synchronizing terms for blog %s\n", $_blog_id );

	restore_current_blog();
}

// Resync all attachments for all blog
foreach ( $blog_ids as $_blog_id ) {
	switch_to_blog( $_blog_id );

	printf( "Switch to blog %s\n", $_blog_id );

	// Attachments
	BEA_CSF_Multisite::sync_all_attachments( array(), true );
	printf( "Finish synchronizing attachments for blog %s\n", $_blog_id );

	restore_current_blog();
}

do_action( 'bea/csf/tools/resync_all' );

printf( "Finish sync all network contents !\n" );
die();