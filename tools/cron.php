<?php
// Define lock file for skip concurrential process, create file into TMP PHP folder
define( 'LOCKFILE', sys_get_temp_dir() . '/bea-content-sync-fusion-'.get_current_user().'.lock' );

// Lock file exist ?
if ( is_file( LOCKFILE ) ) {
	exit( 1 );
}

if ( ! defined( 'STDIN' ) ) {
	die( 'Only CLI' );
}

if ( count( $argv ) == 3 ) {
	$domain = $argv[1];
	$path   = $argv[2];
} else {
	die( 'Missing args ! Usage : php cron.php [domain] [/path]' );
}

// Fake WordPress, build server array
$_SERVER = array(
	'SERVER_PROTOCOL' => 'http/1.1',
	'SERVER_PORT'     => 80,
	'HTTP_HOST'       => $domain,
	'SERVER_NAME'     => $domain,
	'REQUEST_URI'     => $path . 'wp-cron.php',
	'REQUEST_METHOD'  => 'GET',
	'SCRIPT_NAME'     => 'wp-cron.php',
	'SCRIPT_FILENAME' => 'wp-cron.php',
	'PHP_SELF'        => $path . 'wp-cron.php',
);

// Fake WordPress, plugin maintenance, no maintenance mode, no cache
define( 'WP_ADMIN', true );
define( 'WP_CACHE', false );
define( 'NO_MAINTENANCE', true );
define( 'SUNRISE_LOADED', 1 );
define( 'SFML_ALLOW_LOGIN_ACCESS', true );

// Try to load WordPress !
try {
	if ( ! defined( 'ABSPATH' ) ) {
		require( dirname( __FILE__ ) . '/../../../../wp/wp-load.php' );
	}
} catch ( ErrorException $e ) {
	var_dump( $e->getMessage() ); // Debug
	die( 'Configuration seems incorrect because WordPress is trying to do an HTTP redirect or display anything !' );
}

// PHP Configuration
@error_reporting( E_ALL );
@ini_set( 'display_startup_errors', '1' );
@ini_set( 'display_errors', '1' );
@ini_set( 'memory_limit', '512M' );
@ini_set( 'max_execution_time', - 1 );
if ( function_exists( 'ignore_user_abort' ) ) {
	ignore_user_abort( 1 );
}
if ( function_exists( 'set_time_limit' ) ) {
	set_time_limit( 0 );
}

//Fix for CRON Symlink !
require_once(ABSPATH . 'wp-admin/includes/admin.php');

if ( !class_exists('BEA_CSF_Async') )
	die('Plugin not enabled on this network');

// Create fake _POST variable
$_POST = array();

// Create lock file
touch( LOCKFILE );

// Process 200 items by 200 items
BEA_CSF_Async::process_queue( 500 );

// Remove lock file
unlink( LOCKFILE );

exit( 0 );