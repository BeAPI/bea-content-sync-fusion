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

//Domain
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

@ini_set( 'memory_limit', - 1 );
@ini_set( 'display_errors', 1 );

$attachments = get_posts( array(
	'post_type'      => 'attachment',
	'post_status'    => 'any',
	'posts_per_page' => - 1,
	'post_parent'    => null,
) );

if ( empty( $attachments ) ) {
	printf( "No attachment found\n" );
	die();
}

printf( "Found %s attachment(s)\n", count( $attachments ) );
foreach ( $attachments as $e ) {
	if ( ! is_a( $e, 'WP_Post' ) ) {
		continue;
	}

	printf( "Synchronizing attachment %s\n", $e->ID );

	do_action( 'edit_attachment', $e->ID );
}

printf( "Finish synchronizing attachments\n" );
die();