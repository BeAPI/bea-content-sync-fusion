<?php
if ( php_sapi_name() !== 'cli' || isset( $_SERVER['REMOTE_ADDR'] ) ) {
	die( 'CLI Only' );
}

// Get first arg
if ( ! isset( $argv ) || count( $argv ) < 2 ) {
	echo "Missing parameters.\n";
	echo "script usage: php alt-resync-all.php [domain]\n";
	die();
}

//Domain
$domain = ( isset( $argv[1] ) ) ? $argv[1] : '';

// Fake WordPress, build server array
$_SERVER = array(
	'HTTP_HOST'       => $domain,
	'SERVER_NAME'     => $domain,
	'REQUEST_URI'     => '',
	'REQUEST_METHOD'  => 'GET',
	'SCRIPT_NAME'     => basename( __FILE__ ),
	'SCRIPT_FILENAME' => basename( __FILE__ ),
	'PHP_SELF'        => basename( __FILE__ )
);

@ini_set( 'memory_limit', - 1 );
@ini_set( 'display_errors', 1 );

define( 'SFML_ALLOW_LOGIN_ACCESS', true );

require( dirname( __FILE__ ) . '/../../../../wp-load.php' );
require_once( ABSPATH . 'wp-admin/includes/admin.php' );

@ini_set( 'memory_limit', - 1 );
@ini_set( 'display_errors', 1 );

if ( ! empty( $GLOBALS['wpdb']->bea_csf_queue ) && ! empty( $GLOBALS['wpdb']->bea_csf_queue_maintenance ) ) {
	$GLOBALS['wpdb']->bea_csf_queue = $GLOBALS['wpdb']->bea_csf_queue_maintenance;
}

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