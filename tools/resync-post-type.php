<?php
if ( php_sapi_name() !== 'cli' || isset( $_SERVER['REMOTE_ADDR'] ) ) {
	die( 'CLI Only' );
}

// Get first arg
if ( ! isset( $argv ) || count( $argv ) < 2 ) {
	echo "Missing parameters.\n";
	echo "script usage: php resync-post-type.php [domain] [path] [post_type]\n";
	die();
}

//Domain
$domain = ( isset( $argv[1] ) ) ? $argv[1] : '';
$path   = ( isset( $argv[2] ) ) ? $argv[2] : '/';
$cpt    = ( isset( $argv[3] ) ) ? $argv[3] : 'all';

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

@ini_set( 'memory_limit', - 1 );
@ini_set( 'display_errors', 1 );

define( 'SFML_ALLOW_LOGIN_ACCESS', true );

require( dirname( __FILE__ ) . '/../../../../wp/wp-load.php' );
require_once( ABSPATH . 'wp-admin/includes/admin.php' );

@ini_set( 'memory_limit', - 1 );
@ini_set( 'display_errors', 1 );

$posts = get_posts( array(
	'post_type'      => $cpt,
	'post_status'    => 'any',
	'posts_per_page' => - 1,
) );

if ( empty( $posts ) ) {
	printf( "No post found\n" );
	die();
}

printf( "Found %s post(s)\n", count( $posts ) );
foreach ( $posts as $e ) {
	if ( ! is_a( $e, 'WP_Post' ) ) {
		continue;
	}

	printf( "Synchronizing post %s\n", $e->ID );

	do_action( 'transition_post_status', $e->post_status, $e->post_status, $e );
	do_action( 'save_post', $e->ID, $e );
}

printf( "Finish synchronizing posts\n" );
die();