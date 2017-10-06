<?php
if ( php_sapi_name() !== 'cli' || isset( $_SERVER['REMOTE_ADDR'] ) ) {
	die( 'CLI Only' );
}


// Get first arg
if ( ! isset( $argv ) || count( $argv ) < 2 ) {
	echo "Missing parameters.\n";
	echo "script usage: php resync-all-attachments.php [domain] [path]\n";
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

// Attachments
BEA_CSF_Multisite::sync_all_attachments( array(), true );
printf( "Finish synchronizing attachments\n" );

die();