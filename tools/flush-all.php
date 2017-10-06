<?php
die('TODO: Need to switch to new tables relations');
if ( php_sapi_name() !== 'cli' || isset( $_SERVER['REMOTE_ADDR'] ) ) {
	die( 'CLI Only' );
}

// Get first arg
if ( ! isset( $argv ) || count( $argv ) < 2 ) {
	echo "Missing parameters.\n";
	echo "script usage: php flush-all.php [domain]\n";
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

/* @var $wpdb wpdb */
global $wpdb;
$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE 1 = 1 AND blog_id <> 1 AND deleted = 0 AND archived = '0' ORDER BY blog_id" );

if ( empty( $blog_ids ) ) {
	print( "No blogs found\n" );
	die();
}

printf( "Found %s blog(s)\n", count( $blog_ids ) );

foreach ( $blog_ids as $b_id ) {
	switch_to_blog( $b_id );

	printf( "Switch to blog %s\n", $b_id );

	$post_ids = $wpdb->get_col( "
					SELECT p.ID
					FROM $wpdb->posts AS p
					INNER JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
					WHERE pm.meta_key = '_origin_key'
					GROUP BY ID" );

	printf( "Found %s post(s)\n", count( $post_ids ) );
	if ( ! empty( $post_ids ) ) {
		foreach ( $post_ids as $post_id ) {
			printf( "Deleting post %s\n", $post_id );
			wp_delete_post( $post_id, true );
		}
	}

	printf( "Finish deleting posts for blog %s\n", $b_id );

	$terms = $wpdb->get_results( "
			SELECT tt.term_id, tt.taxonomy
			FROM $wpdb->term_taxonomy tt
			INNER JOIN $wpdb->termmeta ttm
				ON tt.term_id = ttm.term_id
			WHERE ttm.meta_key = '_origin_key'" );

	printf( "Found %s term(s)\n", count( $terms ) );
	if ( ! empty( $terms ) ) {
		foreach ( $terms as $term ) {
			printf( "Deleting term %s\n", $term->term_id );
			wp_delete_term( $term->term_id, $term->taxonomy );
		}
	}

	printf( "Finish deleting terms for blog %s\n", $b_id );
}

printf( "Finish cleaning sync\n" );
die();