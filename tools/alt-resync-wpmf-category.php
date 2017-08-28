<?php
if ( php_sapi_name() !== 'cli' || isset( $_SERVER['REMOTE_ADDR'] ) ) {
	die( 'CLI Only' );
}

// Get first arg
if ( ! isset( $argv ) || count( $argv ) < 2 ) {
	echo "Missing parameters.\n";
	echo "script usage: php alt-resync-wpmf-category.php [domain]\n";
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

// Fake WordPress, plugin maintenance, no maintenance mode, no cache
define( 'WP_ADMIN', true );
define( 'WP_CACHE', false );
define( 'NO_MAINTENANCE', true );
define( 'SUNRISE_LOADED', 1 );
define( 'SFML_ALLOW_LOGIN_ACCESS', true );

// Try to load WordPress !
try {
	if ( ! defined( 'ABSPATH' ) ) {
		require_once( dirname( __FILE__ ) . '/../../../../wp-load.php' );
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

if ( ! empty( $GLOBALS['wpdb']->bea_csf_queue ) && ! empty( $GLOBALS['wpdb']->bea_csf_queue_maintenance ) ) {
	$GLOBALS['wpdb']->bea_csf_queue = $GLOBALS['wpdb']->bea_csf_queue_maintenance;
}

$terms = get_terms( array( 'wpmf-category' ), array( 'hide_empty' => false ) );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
	printf( "No term found\n" );
	die();
}

printf( "Found %s term(s)\n", count( $terms ) );
foreach ( $terms as $e ) {
	printf( "Synchronizing term %s\n", $e->term_id );

	do_action( 'edited_term', $e->term_id, $e->term_taxonomy_id, $e->taxonomy );
}

printf( "Finish synchronizing terms\n" );
die();