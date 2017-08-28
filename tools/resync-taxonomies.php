<?php
if ( php_sapi_name() !== 'cli' || isset( $_SERVER['REMOTE_ADDR'] ) ) {
	die( 'CLI Only' );
}

// Get first arg
if ( ! isset( $argv ) || count( $argv ) < 3 ) {
	echo "Missing parameters.\n";
	echo "script usage: php resync-taxonomies.php [domain] [path]\n";
	die();
}

//Domain
$domain  = ( isset( $argv[1] ) ) ? $argv[1] : '';
$path = ( isset( $argv[2] ) ) ? $argv[2] : '/';

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


/**
 * Splits a batch of shared taxonomy terms.
 *
 * @since 4.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function bea_wp_batch_split_terms() {
	global $wpdb;

	// Get a list of shared terms (those with more than one associated row in term_taxonomy).
	$shared_terms = $wpdb->get_results(
		"SELECT tt.term_id, t.*, count(*) as term_tt_count FROM {$wpdb->term_taxonomy} tt
		 LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
		 GROUP BY t.term_id
		 HAVING term_tt_count > 1
		 LIMIT 10"
	);

	// No more terms, we're done here.
	if ( ! $shared_terms ) {
		update_option( 'finished_splitting_shared_terms', true );
		//delete_option( $lock_name );
		return;
	}

	// Shared terms found? We'll need to run this script again.
	//wp_schedule_single_event( time() + ( 2 * MINUTE_IN_SECONDS ), 'wp_split_shared_term_batch' );

	// Rekey shared term array for faster lookups.
	$_shared_terms = array();
	foreach ( $shared_terms as $shared_term ) {
		$term_id = intval( $shared_term->term_id );
		$_shared_terms[ $term_id ] = $shared_term;
	}
	$shared_terms = $_shared_terms;

	// Get term taxonomy data for all shared terms.
	$shared_term_ids = implode( ',', array_keys( $shared_terms ) );
	$shared_tts = $wpdb->get_results( "SELECT * FROM {$wpdb->term_taxonomy} WHERE `term_id` IN ({$shared_term_ids})" );

	// Split term data recording is slow, so we do it just once, outside the loop.
	$split_term_data = get_option( '_split_terms', array() );
	$skipped_first_term = $taxonomies = array();
	foreach ( $shared_tts as $shared_tt ) {
		$term_id = intval( $shared_tt->term_id );

		// Don't split the first tt belonging to a given term_id.
		if ( ! isset( $skipped_first_term[ $term_id ] ) ) {
			$skipped_first_term[ $term_id ] = 1;
			continue;
		}

		if ( ! isset( $split_term_data[ $term_id ] ) ) {
			$split_term_data[ $term_id ] = array();
		}

		// Keep track of taxonomies whose hierarchies need flushing.
		if ( ! isset( $taxonomies[ $shared_tt->taxonomy ] ) ) {
			$taxonomies[ $shared_tt->taxonomy ] = 1;
		}

		// Split the term.
		$split_term_data[ $term_id ][ $shared_tt->taxonomy ] = _split_shared_term( $shared_terms[ $term_id ], $shared_tt, false );
	}

	// Rebuild the cached hierarchy for each affected taxonomy.
	foreach ( array_keys( $taxonomies ) as $tax ) {
		delete_option( "{$tax}_children" );
		_get_term_hierarchy( $tax );
	}

	update_option( '_split_terms', $split_term_data );

	//delete_option( $lock_name );
}
//bea_wp_batch_split_terms();die('OK');


$taxonomies = get_taxonomies( array(), 'names' );

if ( empty( $taxonomies ) ) {
	printf( "No taxonomy found\n" );
	die();
}

$terms = get_terms( array( 'taxonomy' => array_keys( $taxonomies ), 'hide_empty' => false ) );
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